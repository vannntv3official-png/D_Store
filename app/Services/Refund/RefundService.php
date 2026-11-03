<?php

namespace App\Services\Refund;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Order\OrderStateMachineService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Pricing\Money;
use Exception;
use Illuminate\Support\Facades\DB;

class OverRefundException extends Exception {}

class RefundService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected LoyaltyService $loyaltyService,
        protected PaymentGatewayManager $paymentManager,
        protected OrderStateMachineService $stateMachine,
    ) {}

    /**
     * Request and execute an audited refund against captured order payment.
     *
     * @param array<int, int> $itemQuantities [order_item_id => quantity_to_refund]
     */
    public function processRefund(
        Order $order,
        float $amount,
        string $reason,
        User $actor,
        array $itemQuantities = []
    ): Refund {
        return DB::transaction(function () use ($order, $amount, $reason, $actor, $itemQuantities) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if (!in_array($lockedOrder->payment_status, ['paid', 'partially_refunded'])) {
                throw new Exception("Cannot refund an order with payment status [{$lockedOrder->payment_status}].");
            }

            $refundMoney = Money::of($amount);
            $grandTotalMoney = Money::of($lockedOrder->grand_total);

            // Calculate cumulative existing refunds
            $existingRefunds = Refund::where('order_id', $lockedOrder->id)
                ->whereIn('status', ['succeeded', 'pending'])
                ->lockForUpdate()
                ->sum('amount');

            $existingRefundMoney = Money::of($existingRefunds);
            $totalAfterThis = $existingRefundMoney->add($refundMoney);

            if ($totalAfterThis->isGreaterThan($grandTotalMoney)) {
                $maxPossible = $grandTotalMoney->subtract($existingRefundMoney);
                throw new OverRefundException("Refund amount ({$refundMoney->format()}) exceeds refundable balance ({$maxPossible->format()}).");
            }

            // Find primary captured payment
            $payment = Payment::where('order_id', $lockedOrder->id)
                ->where('status', 'paid')
                ->first();

            if (!$payment) {
                throw new Exception("No captured payment found on order #{$lockedOrder->order_number}.");
            }

            $refund = Refund::create([
                'order_id' => $lockedOrder->id,
                'amount' => $refundMoney->getAmount(),
                'reason' => $reason,
                'status' => 'pending',
                'requested_by' => $actor->id,
                'approved_by' => $actor->id,
            ]);

            // Record item refund records
            $merchandiseRefundAmount = Money::zero();
            foreach ($itemQuantities as $orderItemId => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                $orderItem = OrderItem::where('order_id', $lockedOrder->id)->where('id', $orderItemId)->first();
                if ($orderItem) {
                    RefundItem::create([
                        'refund_id' => $refund->id,
                        'order_item_id' => $orderItem->id,
                        'quantity' => $qty,
                        'restock_approved' => false,
                    ]);

                    $itemRefundShare = Money::of($orderItem->unit_price)->multiply($qty);
                    $merchandiseRefundAmount = $merchandiseRefundAmount->add($itemRefundShare);
                }
            }

            // Call Payment Gateway Refund Adapter
            $gateway = $this->paymentManager->driver($payment->gateway);
            $gatewayTx = PaymentTransaction::where('payment_id', $payment->id)->where('type', 'charge')->first();

            $gatewayResult = $gateway->refund(
                $gatewayTx ?: new PaymentTransaction(['payment_id' => $payment->id, 'amount' => $payment->amount]),
                $refundMoney,
                $reason
            );

            if ($gatewayResult->isSuccess) {
                $refund->status = 'succeeded';
                $refund->provider_reference = $gatewayResult->refundTransactionId;
                $refund->save();

                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'type' => 'refund',
                    'amount' => $refundMoney->getAmount(),
                    'status' => 'succeeded',
                    'gateway_transaction_id' => $gatewayResult->refundTransactionId,
                    'gateway_response' => $gatewayResult->rawResponse,
                ]);

                // Transition order payment status
                if ($totalAfterThis->isGreaterThanOrEqual($grandTotalMoney)) {
                    $this->stateMachine->transitionPayment($lockedOrder, 'refunded', $actor, 'Full refund completed');
                } else {
                    $this->stateMachine->transitionPayment($lockedOrder, 'partially_refunded', $actor, 'Partial refund completed');
                }

                // Claw back loyalty points for merchandise amount
                if ($merchandiseRefundAmount->isPositive()) {
                    $this->loyaltyService->clawbackRefundPoints($lockedOrder, $merchandiseRefundAmount);
                }
            } else {
                $refund->status = 'failed';
                $refund->notes = $gatewayResult->errorMessage;
                $refund->save();
            }

            return $refund;
        });
    }

    /**
     * Authorized return inspection and physical restock.
     * Separately audited: physically verifies goods before adding back into sellable inventory.
     */
    public function inspectAndRestock(Refund $refund, User $inspector, ?string $inspectionNotes = null): void
    {
        DB::transaction(function () use ($refund, $inspector, $inspectionNotes) {
            /** @var Refund $lockedRefund */
            $lockedRefund = Refund::where('id', $refund->id)->lockForUpdate()->first();

            if ($lockedRefund->status !== 'succeeded') {
                throw new Exception('Can only restock items for a succeeded refund.');
            }

            foreach ($lockedRefund->items as $refundItem) {
                if ($refundItem->restock_approved) {
                    continue; // Already restocked
                }

                $variant = $refundItem->orderItem->variant;

                // Adjust stock back into inventory
                $this->inventoryService->adjustStock(
                    variant: $variant,
                    delta: $refundItem->quantity,
                    reason: 'return_inspected',
                    actor: $inspector,
                    reference: "REFUND_RESTOCK_{$lockedRefund->id}",
                    notes: $inspectionNotes ?: 'Approved sellable physical returned inventory'
                );

                $refundItem->restock_approved = true;
                $refundItem->inspection_notes = $inspectionNotes;
                $refundItem->save();
            }
        });
    }
}
