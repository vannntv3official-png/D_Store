<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Pricing\Money;
use App\Services\Refund\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundAdminController extends Controller
{
    public function __construct(
        protected RefundService $refundService,
    ) {}

    public function index(): Response
    {
        $refunds = Refund::with(['order:id,order_number,user_id', 'order.user:id,name', 'items'])
            ->latest()
            ->paginate(15)
            ->through(fn ($r) => [
                'id' => $r->id,
                'order_id' => $r->order_id,
                'order_number' => $r->order->order_number,
                'customer_name' => $r->order->user?->name ?? 'Guest',
                'amount' => Money::format($r->amount),
                'reason' => $r->reason,
                'status' => $r->status,
                'is_restocked' => $r->is_restocked,
                'created_at' => $r->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Refunds/Index', [
            'refunds' => $refunds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.order_item_id' => ['required_with:items', 'exists:order_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.restock' => ['boolean'],
        ]);

        $order = Order::with('items')->findOrFail($validated['order_id']);

        try {
            $this->refundService->createRefund(
                order: $order,
                amount: Money::format((string) $validated['amount']),
                reason: $validated['reason'],
                items: $validated['items'] ?? [],
                user: $request->user(),
            );

            return redirect()->back()->with('success', 'Refund processed successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['refund' => $e->getMessage()]);
        }
    }

    public function inspectAndRestock(Request $request, int $id): RedirectResponse
    {
        $refund = Refund::with('items.orderItem')->findOrFail($id);

        try {
            $this->refundService->inspectAndRestock($refund, $request->user());
            return redirect()->back()->with('success', 'Physical restock inspection verified and inventory restocked.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['restock' => $e->getMessage()]);
        }
    }
}
