<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Inventory\InventoryService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Pricing\PricingPipelineService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IdempotencyMismatchException extends Exception {}

class CheckoutService
{
    public function __construct(
        protected PricingPipelineService $pricingPipeline,
        protected InventoryService $inventoryService,
        protected PaymentGatewayManager $paymentManager,
    ) {}

    public function processCheckout(
        Cart $cart,
        array $shippingAddressData,
        string $paymentMethod,
        string $idempotencyKey,
        ?User $user = null,
        ?string $deliveryNotes = null
    ): array {
        $customerScope = $user ? "user_{$user->id}" : "session_{$cart->session_token}";

        // Check if an existing idempotency record exists for this key
        $idempotency = CheckoutIdempotency::where('customer_scope', $customerScope)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($idempotency && $idempotency->order_id && $idempotency->order) {
            // Recompute request hash for payload mismatch check
            $canonicalPayload = json_encode([
                'address' => $shippingAddressData,
                'payment_method' => $paymentMethod,
            ]);
            $requestHash = hash('sha256', $canonicalPayload);

            if ($idempotency->request_hash !== $requestHash) {
                throw new IdempotencyMismatchException('Idempotency key reused with mismatched payload.');
            }

            return [
                'order' => $idempotency->order->load(['items', 'shippingAddress']),
                'payment_initiation' => $idempotency->response_payload['payment_initiation'] ?? null,
                'is_replay' => true,
            ];
        }

        $cart->load(['items.variant.product', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw new Exception('Cannot checkout with an empty cart.');
        }

        $canonicalPayload = json_encode([
            'address' => $shippingAddressData,
            'payment_method' => $paymentMethod,
        ]);
        $requestHash = hash('sha256', $canonicalPayload);

        // Perform transactional order creation & atomic reservation
        $order = DB::transaction(function () use (
            $cart,
            $shippingAddressData,
            $paymentMethod,
            $customerScope,
            $idempotencyKey,
            $requestHash,
            $user,
            $deliveryNotes
        ) {
            // 1. Sort variant IDs ascending to prevent database deadlocks on concurrent orders
            $variantIds = $cart->items->pluck('product_variant_id')->sort()->values()->all();

            // Lock all inventory rows in order
            $inventories = Inventory::whereIn('product_variant_id', $variantIds)
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_variant_id');

            // 2. Verify available stock for every item
            foreach ($cart->items as $item) {
                $inv = $inventories[$item->product_variant_id] ?? null;
                if (!$inv) {
                    throw new InsufficientStockException("Inventory not initialized for SKU {$item->variant->sku}");
                }
                $available = $inv->on_hand_quantity - $inv->reserved_quantity;
                if ($available < $item->quantity) {
                    throw new InsufficientStockException("Item {$item->variant->product->getLocalizedName()} ({$item->variant->sku}) has only {$available} units available.");
                }
            }

            // 3. Calculate authoritative pricing
            $shippingMethod = $cart->shipping_method_id ? ShippingMethod::find($cart->shipping_method_id) : null;
            $calc = $this->pricingPipeline->calculate($cart, $cart->coupon, $shippingMethod);

            // 4. Generate order identifiers
            $orderNumber = 'DS-' . date('Y') . '-' . strtoupper(Str::random(6));
            $guestToken = $user ? null : bin2hex(random_bytes(32));

            $order = Order::create([
                'order_number' => $orderNumber,
                'guest_token' => $guestToken,
                'user_id' => $user?->id,
                'customer_name' => $shippingAddressData['contact_name'],
                'customer_phone' => $shippingAddressData['contact_phone'],
                'customer_email' => $shippingAddressData['email'] ?? $user?->email,
                'base_currency' => 'USD',
                'display_currency' => 'KHR',
                'exchange_rate' => $calc->exchangeRate,
                'subtotal' => $calc->subtotal->getAmount(),
                'discount_total' => $calc->discountTotal->getAmount(),
                'tax_total' => $calc->taxTotal->getAmount(),
                'shipping_total' => $calc->shippingTotal->getAmount(),
                'grand_total' => $calc->grandTotal->getAmount(),
                'settlement_amount' => $calc->settlementKhr->getAmount(),
                'fulfillment_status' => 'pending',
                'payment_status' => 'pending',
                'shipping_method_name' => $calc->shippingMethod?->getLocalizedName(),
                'delivery_notes' => $deliveryNotes,
            ]);

            // 5. Reserve Inventory for each item
            foreach ($cart->items as $item) {
                $this->inventoryService->reserveStock($item->variant, $item->quantity, $order->id);
            }

            // 6. Record Order Items
            foreach ($calc->lineItems as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $line['variant_id'],
                    'product_name_snapshot' => $line['product_name'],
                    'sku_snapshot' => $line['sku'],
                    'unit_price' => $line['unit_price']->getAmount(),
                    'quantity' => $line['quantity'],
                    'line_subtotal' => $line['line_subtotal']->getAmount(),
                    'allocated_discount' => $line['allocated_discount']->getAmount(),
                    'line_total' => $line['line_total']->getAmount(),
                ]);
            }

            // 7. Record Order Address
            OrderAddress::create([
                'order_id' => $order->id,
                'type' => 'shipping',
                'contact_name' => $shippingAddressData['contact_name'],
                'contact_phone' => $shippingAddressData['contact_phone'],
                'province_name' => $shippingAddressData['province_name'],
                'district_name' => $shippingAddressData['district_name'],
                'address_line' => $shippingAddressData['address_line'],
            ]);

            // 8. Commit Coupon Usage
            if ($calc->coupon && $calc->discountTotal->isPositive()) {
                CouponUsage::create([
                    'coupon_id' => $calc->coupon->id,
                    'order_id' => $order->id,
                    'user_id' => $user?->id,
                    'discount_amount' => $calc->discountTotal->getAmount(),
                    'status' => 'committed',
                ]);
                $calc->coupon->increment('times_used');
            }

            // 9. Initial Status History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => 'none',
                'to_status' => 'pending',
                'type' => 'fulfillment',
                'actor_id' => $user?->id,
                'reason' => 'Order created via checkout',
            ]);

            // 10. Record Idempotency
            CheckoutIdempotency::create([
                'customer_scope' => $customerScope,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'order_id' => $order->id,
            ]);

            // 11. Clear Cart
            $cart->items()->delete();
            $cart->coupon_id = null;
            $cart->save();

            return $order;
        });

        // Outside DB transaction: initiate payment with gateway adapter
        $gateway = $this->paymentManager->driver($paymentMethod);
        $initiation = $gateway->initiatePayment($order);

        // Update idempotency response payload
        CheckoutIdempotency::where('customer_scope', $customerScope)
            ->where('idempotency_key', $idempotencyKey)
            ->update([
                'response_payload' => [
                    'payment_initiation' => $initiation->toArray(),
                ],
            ]);

        return [
            'order' => $order->load(['items', 'shippingAddress']),
            'payment_initiation' => $initiation->toArray(),
            'is_replay' => false,
        ];
    }
}
