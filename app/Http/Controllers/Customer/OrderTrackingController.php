<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Models\Setting;
use App\Services\Order\OrderStateMachineService;
use App\Services\Payment\Adapters\AbaPaywaySandboxGateway;
use App\Services\Payment\Adapters\KhqrSandboxGateway;
use App\Services\Pricing\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        // IDOR Defense: Authorization check
        $user = $request->user();
        $sessionGuestToken = session('guest_token');
        $requestToken = $request->query('token');

        $isOwner = $user && $order->user_id === $user->id;
        $isStaff = $user && $user->isStaff();
        $hasMatchingGuestToken = !empty($order->guest_token) && (
            $sessionGuestToken === $order->guest_token || $requestToken === $order->guest_token
        );

        if (!$isOwner && !$isStaff && !$hasMatchingGuestToken) {
            abort(403, 'Unauthorized access to order.');
        }

        // If KHQR, generate EMVCo QR code payload for display
        $khqrPayload = null;
        $paywayPayload = null;
        $latestPayment = $order->payments->last();

        if ($latestPayment && $latestPayment->gateway === 'khqr_sandbox' && $latestPayment->status === 'pending') {
            $gateway = new KhqrSandboxGateway();
            $initResult = $gateway->initiatePayment($order, [
                'currency' => $order->base_currency,
                'store_name' => Setting::get('store_name', 'D STORE CAMBODIA'),
            ]);
            $khqrPayload = $initResult->qrCodePayload ?? null;
        }

        if ($latestPayment && $latestPayment->gateway === 'aba_payway_sandbox') {
            $paywayGateway = new AbaPaywaySandboxGateway();
            $initResult = $paywayGateway->initiatePayment($order);
            $paywayPayload = [
                'merchant_reference' => $latestPayment->merchant_reference,
                'checkout_url' => route('payway.sandbox.checkout', ['reference' => $latestPayment->merchant_reference]),
                'deeplink' => $initResult->deeplink,
                'qr_code_payload' => $initResult->qrCodePayload,
                'qr_image_url' => $initResult->qrImageUrl,
                'status' => $latestPayment->status,
                'amount' => Money::format($order->grand_total),
                'amount_khr' => Money::toKhr($order->grand_total, $rate),
            ];
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
            'paywayPayload' => $paywayPayload,
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

    public function khqrSandboxCheckout(Request $request, string $reference): RedirectResponse
    {
        $payment = Payment::where('merchant_reference', $reference)->firstOrFail();
        return redirect()->route('orders.confirmed', [
            'orderNumber' => $payment->order->order_number,
            'token' => $payment->order->guest_token,
        ]);
    }

    public function paywaySandboxCheckout(Request $request, string $reference): Response|RedirectResponse
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $payment = Payment::where('merchant_reference', $reference)
            ->with(['order.items.variant.product.images', 'order.shippingAddress'])
            ->firstOrFail();

        $order = $payment->order;

        // If payment is already paid, redirect straight to confirmed page
        if ($payment->status === 'paid' || $order->payment_status === 'paid') {
            return redirect()->route('orders.confirmed', [
                'orderNumber' => $order->order_number,
                'token' => $order->guest_token,
            ]);
        }

        $gateway = new AbaPaywaySandboxGateway();
        $initResult = $gateway->initiatePayment($order);

        return Inertia::render('Customer/PayWayCheckout', [
            'reference' => $payment->merchant_reference,
            'order' => [
                'order_number' => $order->order_number,
                'guest_token' => $order->guest_token,
                'grand_total' => Money::format($order->grand_total),
                'grand_total_khr' => Money::toKhr($order->grand_total, $rate),
                'currency' => $order->base_currency,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'items' => $order->items->map(fn ($item) => [
                    'name_en' => $item->product_name_snapshot['en'] ?? (is_array($item->product_name_snapshot) ? reset($item->product_name_snapshot) : (string) $item->product_name_snapshot),
                    'quantity' => $item->quantity,
                    'unit_price' => Money::format($item->unit_price),
                    'subtotal' => Money::format($item->subtotal),
                ]),
            ],
            'payway' => [
                'merchant_id' => config('services.aba_payway.merchant_id', 'ec432100'),
                'merchant_name' => Setting::get('store_name', 'D STORE CO., LTD.'),
                'tran_id' => $payment->merchant_reference,
                'deeplink' => $initResult->deeplink,
                'qr_code_payload' => $initResult->qrCodePayload,
                'qr_image_url' => $initResult->qrImageUrl,
                'hash' => $initResult->rawResponse['hash'] ?? null,
                'req_time' => $initResult->rawResponse['req_time'] ?? null,
            ],
            'returnUrl' => route('orders.confirmed', [
                'orderNumber' => $order->order_number,
                'token' => $order->guest_token,
            ]),
        ]);
    }

    public function paywaySandboxSimulate(
        Request $request,
        string $reference,
        OrderStateMachineService $orderStateMachine
    ): JsonResponse|RedirectResponse {
        $payment = Payment::where('merchant_reference', $reference)
            ->with('order')
            ->firstOrFail();

        $order = $payment->order;
        $apv = 'APV-' . strtoupper(Str::random(8));

        DB::transaction(function () use ($payment, $order, $apv, $orderStateMachine) {
            $payment->update([
                'status' => 'paid',
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'type' => 'charge',
                'amount' => $payment->amount,
                'status' => 'success',
                'gateway_transaction_id' => $apv,
                'gateway_response' => [
                    'simulated' => true,
                    'apv' => $apv,
                    'tran_id' => $payment->merchant_reference,
                    'status' => 0,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            PaymentWebhookEvent::create([
                'gateway' => 'aba_payway_sandbox',
                'event_id' => 'sim_payway_' . $payment->merchant_reference . '_' . now()->timestamp,
                'payload' => [
                    'tran_id' => $payment->merchant_reference,
                    'status' => 0,
                    'apv' => $apv,
                    'amount' => (string) $payment->amount,
                ],
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            $orderStateMachine->transitionPayment(
                order: $order,
                toStatus: 'paid',
                actor: null,
                reason: "Payment verified via ABA PayWay Sandbox Simulation (APV: {$apv})"
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => '00',
                'message' => 'Payment approved successfully.',
                'apv' => $apv,
                'redirect' => route('orders.confirmed', [
                    'orderNumber' => $order->order_number,
                    'token' => $order->guest_token,
                ]),
            ]);
        }

        return redirect()->route('orders.confirmed', [
            'orderNumber' => $order->order_number,
            'token' => $order->guest_token,
        ])->with('success', 'ABA PayWay payment approved successfully!');
    }
}
