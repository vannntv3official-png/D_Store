<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use Exception;
use Illuminate\Support\Facades\DB;

class InvalidOrderTransitionException extends Exception {}

class OrderStateMachineService
{
    protected array $allowedFulfillmentTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['shipped'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    protected array $allowedPaymentTransitions = [
        'pending' => ['authorized', 'paid', 'failed'],
        'authorized' => ['paid', 'failed'],
        'paid' => ['partially_refunded', 'refunded'],
        'partially_refunded' => ['refunded'],
        'refunded' => [],
        'failed' => ['pending'],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected LoyaltyService $loyaltyService,
    ) {}

    public function transitionFulfillment(Order $order, string $toStatus, ?User $actor = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $actor, $reason) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            $fromStatus = $lockedOrder->fulfillment_status;

            if ($fromStatus === $toStatus) {
                return $lockedOrder;
            }

            $allowed = $this->allowedFulfillmentTransitions[$fromStatus] ?? [];
            if (!in_array($toStatus, $allowed, true)) {
                throw new InvalidOrderTransitionException("Cannot transition fulfillment status from [{$fromStatus}] to [{$toStatus}].");
            }

            // Side effects:
            if ($toStatus === 'shipped') {
                // Consume inventory reservations into finalized stock deduction
                foreach ($lockedOrder->reservations as $res) {
                    $this->inventoryService->consumeReservation($res);
                }
            } elseif ($toStatus === 'cancelled') {
                $lockedOrder->cancelled_at = now();
                // Release active inventory reservations back to available stock
                foreach ($lockedOrder->reservations as $res) {
                    $this->inventoryService->releaseReservation($res, 'order_cancelled');
                }
            } elseif ($toStatus === 'delivered') {
                $lockedOrder->delivered_at = now();
            }

            $lockedOrder->fulfillment_status = $toStatus;
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'type' => 'fulfillment',
                'actor_id' => $actor?->id,
                'reason' => $reason,
            ]);

            // If order is delivered AND paid, award loyalty points
            if ($lockedOrder->fulfillment_status === 'delivered' && $lockedOrder->payment_status === 'paid') {
                $this->loyaltyService->awardOrderPoints($lockedOrder);
            }

            return $lockedOrder;
        });
    }

    public function transitionPayment(Order $order, string $toStatus, ?User $actor = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $actor, $reason) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
            $fromStatus = $lockedOrder->payment_status;

            if ($fromStatus === $toStatus) {
                return $lockedOrder;
            }

            $allowed = $this->allowedPaymentTransitions[$fromStatus] ?? [];
            if (!in_array($toStatus, $allowed, true)) {
                throw new InvalidOrderTransitionException("Cannot transition payment status from [{$fromStatus}] to [{$toStatus}].");
            }

            $lockedOrder->payment_status = $toStatus;

            // Auto-confirm fulfillment if paid and currently pending
            if ($toStatus === 'paid' && $lockedOrder->fulfillment_status === 'pending') {
                $lockedOrder->fulfillment_status = 'confirmed';
            }

            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'type' => 'payment',
                'actor_id' => $actor?->id,
                'reason' => $reason,
            ]);

            // If order is delivered AND paid, award loyalty points
            if ($lockedOrder->fulfillment_status === 'delivered' && $lockedOrder->payment_status === 'paid') {
                $this->loyaltyService->awardOrderPoints($lockedOrder);
            }

            return $lockedOrder;
        });
    }
}
