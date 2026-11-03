<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Payment\KhqrSandboxGateway;
use App\Services\Pricing\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function confirmed(Request $request, string $orderNumber): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $order = Order::where('order_number', $orderNumber)
            ->with(['items.variant.product.images', 'shippingAddress', 'payments', 'shipments'])
            ->firstOrFail();

        // If KHQR, generate EMVCo QR code payload for display
        $khqrPayload = null;
        $latestPayment = $order->payments->last();
        if ($latestPayment && $latestPayment->gateway === 'khqr_sandbox' && $latestPayment->status === 'pending') {
            $gateway = new KhqrSandboxGateway();
            $initResult = $gateway->initiatePayment($order, [
                'currency' => $order->base_currency,
                'store_name' => Setting::get('store_name', 'D STORE CAMBODIA'),
            ]);
            $khqrPayload = $initResult['qr_string'] ?? null;
        }

        // Bank transfer details
        $bankDetails = [
            'bank_name' => Setting::get('bank_name', 'ABA Bank Cambodia'),
            'account_name' => Setting::get('bank_account_name', 'D STORE CO., LTD.'),
            'account_usd' => Setting::get('bank_account_usd', '001 234 567'),
            'account_khr' => Setting::get('bank_account_khr', '001 234 568'),
        ];

        return Inertia::render('Customer/OrderConfirmed', [
            'order' => [
                'order_number' => $order->order_number,
                'guest_token' => $order->guest_token,
                'status' => $order->fulfillment_status,
                'payment_status' => $order->payment_status,
                'currency' => $order->base_currency,
                'subtotal' => Money::format($order->subtotal),
                'discount' => Money::format($order->discount_total),
                'shipping_fee' => Money::format($order->shipping_total),
                'total' => Money::format($order->grand_total),
                'total_khr' => Money::toKhr($order->grand_total, $rate),
                'created_at' => $order->created_at->format('M d, Y H:i'),
                'shipping_address' => $order->shippingAddress,
                'items' => $order->items->map(fn ($item) => [
                    'name_en' => $item->product_name_snapshot['en'] ?? (is_array($item->product_name_snapshot) ? reset($item->product_name_snapshot) : (string) $item->product_name_snapshot),
                    'name_km' => $item->product_name_snapshot['km'] ?? $item->product_name_snapshot['en'] ?? '',
                    'sku' => $item->sku_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => Money::format($item->unit_price),
                    'subtotal' => Money::format($item->subtotal),
                ]),
                'payment_method' => $latestPayment?->gateway ?? 'cod',
            ],
            'khqrPayload' => $khqrPayload,
            'bankDetails' => $bankDetails,
        ]);
    }

    public function trackGuest(string $token): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $order = Order::where('guest_token', $token)
            ->with(['items.variant.product.images', 'shippingAddress', 'payments', 'shipments', 'statusHistories'])
            ->firstOrFail();

        return Inertia::render('Customer/OrderDetail', [
            'order' => $this->formatOrderData($order, $rate),
            'isGuest' => true,
        ]);
    }

    public function accountOrders(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['items.variant.product.images', 'payments', 'shipments'])
            ->latest()
            ->paginate(10)
            ->through(fn ($order) => $this->formatOrderData($order, $rate));

        return Inertia::render('Customer/Account/Orders', [
            'orders' => $orders,
        ]);
    }

    public function accountOrderDetail(Request $request, string $orderNumber): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->with(['items.variant.product.images', 'shippingAddress', 'payments', 'shipments', 'statusHistories.actor'])
            ->firstOrFail();

        return Inertia::render('Customer/OrderDetail', [
            'order' => $this->formatOrderData($order, $rate),
            'isGuest' => false,
        ]);
    }

    protected function formatOrderData(Order $order, int $rate): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'guest_token' => $order->guest_token,
            'status' => $order->fulfillment_status,
            'payment_status' => $order->payment_status,
            'currency' => $order->base_currency,
            'subtotal' => Money::format($order->subtotal),
            'discount' => Money::format($order->discount_total),
            'shipping_fee' => Money::format($order->shipping_total),
            'total' => Money::format($order->grand_total),
            'total_khr' => Money::toKhr($order->grand_total, $rate),
            'created_at' => $order->created_at->format('M d, Y H:i'),
            'shipping_address' => $order->shippingAddress,
            'items' => $order->items->map(fn ($item) => [
                'name_en' => $item->product_name_snapshot['en'] ?? (is_array($item->product_name_snapshot) ? reset($item->product_name_snapshot) : (string) $item->product_name_snapshot),
                'name_km' => $item->product_name_snapshot['km'] ?? $item->product_name_snapshot['en'] ?? '',
                'sku' => $item->sku_snapshot,
                'quantity' => $item->quantity,
                'unit_price' => Money::format($item->unit_price),
                'subtotal' => Money::format($item->subtotal),
                'image_url' => $item->variant?->product?->images?->first()?->image_url ?? '/images/placeholder.svg',
            ]),
            'timeline' => $order->statusHistories->map(fn ($h) => [
                'from_status' => $h->from_status,
                'to_status' => $h->to_status,
                'comment' => $h->reason,
                'created_at' => $h->created_at->format('M d, Y H:i'),
            ]),
            'shipments' => $order->shipments->map(fn ($s) => [
                'tracking_number' => $s->tracking_number,
                'carrier_name' => $s->carrier_name,
                'status' => $s->status,
                'shipped_at' => $s->shipped_at?->format('M d, Y H:i'),
                'delivered_at' => $s->delivered_at?->format('M d, Y H:i'),
            ]),
            'payment' => $order->payments->last() ? [
                'method' => $order->payments->last()->gateway,
                'status' => $order->payments->last()->status,
                'amount' => Money::format($order->payments->last()->amount),
            ] : null,
        ];
    }
}
