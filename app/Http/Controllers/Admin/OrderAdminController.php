<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Order\OrderStateMachineService;
use App\Services\Pricing\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderAdminController extends Controller
{
    public function __construct(
        protected OrderStateMachineService $orderStateMachine,
    ) {}

    public function index(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $query = Order::with(['user:id,name,phone', 'shippingAddress', 'payments']);

        if ($status = $request->query('status')) {
            $query->where('fulfillment_status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($search = $request->query('q')) {
            $term = '%' . trim((string) $search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term);
            });
        }

        $orders = $query->latest()->paginate(15)->through(function (Order $order) use ($rate) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'status' => $order->fulfillment_status,
                'payment_status' => $order->payment_status,
                'total_amount' => Money::format($order->grand_total),
                'total_khr' => Money::toKhr($order->grand_total, $rate),
                'created_at' => $order->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->query('status', ''),
                'payment_status' => $request->query('payment_status', ''),
                'q' => $request->query('q', ''),
            ],
            'statuses' => [
                Order::FULFILLMENT_PENDING,
                Order::FULFILLMENT_CONFIRMED,
                Order::FULFILLMENT_PROCESSING,
                Order::FULFILLMENT_PACKED,
                Order::FULFILLMENT_SHIPPED,
                Order::FULFILLMENT_DELIVERED,
                Order::FULFILLMENT_CANCELLED,
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $order = Order::with([
            'user:id,name,email,phone',
            'items.variant.product',
            'shippingAddress',
            'payments.transactions',
            'shipments',
            'statusHistories.actor:id,name',
            'refunds.items',
            'notes.actor:id,name',
        ])->findOrFail($id);

        $allowedFulfillmentTransitions = match ($order->fulfillment_status) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['packed', 'cancelled'],
            'packed' => ['shipped'],
            'shipped' => ['delivered'],
            default => [],
        };

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->fulfillment_status,
                'payment_status' => $order->payment_status,
                'subtotal' => Money::format($order->subtotal),
                'discount' => Money::format($order->discount_total),
                'shipping_fee' => Money::format($order->shipping_total),
                'total_amount' => Money::format($order->grand_total),
                'total_khr' => Money::toKhr($order->grand_total, $rate),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'customer' => [
                    'id' => $order->user_id,
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'shipping_address' => $order->shippingAddress,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name_snapshot['en'] ?? (is_array($item->product_name_snapshot) ? reset($item->product_name_snapshot) : (string) $item->product_name_snapshot),
                    'sku' => $item->sku_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => Money::format($item->unit_price),
                    'subtotal' => Money::format($item->subtotal),
                ]),
                'payments' => $order->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'method' => $p->gateway,
                    'status' => $p->status,
                    'amount' => Money::format($p->amount),
                    'created_at' => $p->created_at->format('Y-m-d H:i'),
                ]),
                'shipments' => $order->shipments->map(fn ($s) => [
                    'id' => $s->id,
                    'carrier' => $s->carrier_name,
                    'tracking_number' => $s->tracking_number,
                    'status' => $s->status,
                    'shipped_at' => $s->shipped_at?->format('Y-m-d H:i'),
                    'delivered_at' => $s->delivered_at?->format('Y-m-d H:i'),
                ]),
                'timeline' => $order->statusHistories->map(fn ($h) => [
                    'from' => $h->from_status,
                    'to' => $h->to_status,
                    'comment' => $h->reason,
                    'actor' => $h->actor?->name ?? 'System',
                    'created_at' => $h->created_at->format('Y-m-d H:i:s'),
                ]),
                'refunds' => $order->refunds->map(fn ($r) => [
                    'id' => $r->id,
                    'amount' => Money::format($r->amount),
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'created_at' => $r->created_at->format('Y-m-d H:i'),
                ]),
            ],
            'allowedTransitions' => $allowedFulfillmentTransitions,
        ]);
    }

    public function transition(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'to_status' => ['required', 'string'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::findOrFail($id);

        try {
            $this->orderStateMachine->transitionFulfillment(
                order: $order,
                toStatus: $validated['to_status'],
                actor: $request->user(),
                reason: $validated['comment'] ?? null,
            );

            return redirect()->back()->with('success', "Order transitioned to {$validated['to_status']}.");
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['transition' => $e->getMessage()]);
        }
    }

    public function invoice(int $id): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $order = Order::with(['user', 'shippingAddress', 'items', 'payments'])->findOrFail($id);

        return Inertia::render('Admin/Orders/Invoice', [
            'order' => [
                'order_number' => $order->order_number,
                'created_at' => $order->created_at->format('d M Y, H:i'),
                'subtotal' => Money::format($order->subtotal),
                'discount' => Money::format($order->discount_total),
                'shipping_fee' => Money::format($order->shipping_total),
                'total_amount' => Money::format($order->grand_total),
                'total_khr' => Money::toKhr($order->grand_total, $rate),
                'customer' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'shipping_address' => $order->shippingAddress,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name_snapshot['en'] ?? (is_array($item->product_name_snapshot) ? reset($item->product_name_snapshot) : (string) $item->product_name_snapshot),
                    'sku' => $item->sku_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => Money::format($item->unit_price),
                    'subtotal' => Money::format($item->subtotal),
                ]),
                'payment_method' => $order->payments->last()?->gateway ?? 'COD',
            ],
            'store' => [
                'name' => Setting::get('store_name', 'D STORE CAMBODIA'),
                'address' => Setting::get('store_address_en', 'Norodom Blvd, Phnom Penh, Cambodia'),
                'phone' => Setting::get('store_phone', '+855 23 888 999'),
                'email' => Setting::get('store_email', 'billing@dstore.com.kh'),
            ],
        ]);
    }
}
