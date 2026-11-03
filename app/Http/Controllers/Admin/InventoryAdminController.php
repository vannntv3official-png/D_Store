<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryAdminController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Inventory::with(['variant.product']);

        if ($search = $request->query('q')) {
            $term = '%' . trim((string) $search) . '%';
            $query->whereHas('variant', function ($v) use ($term) {
                $v->where('sku', 'like', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term));
            });
        }

        $stockItems = $query->paginate(20)->through(fn (Inventory $inv) => [
            'id' => $inv->id,
            'variant_id' => $inv->product_variant_id,
            'sku' => $inv->variant->sku,
            'product_name' => $inv->variant->product->getLocalizedName(),
            'on_hand' => $inv->on_hand_quantity,
            'reserved' => $inv->reserved_quantity,
            'available' => $inv->available_quantity,
            'updated_at' => $inv->updated_at->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Admin/Inventory/Index', [
            'stockItems' => $stockItems,
            'filters' => [
                'q' => $request->query('q', ''),
            ],
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $variant = ProductVariant::findOrFail($validated['variant_id']);

        try {
            $this->inventoryService->adjustStock(
                variant: $variant,
                delta: (int) $validated['delta'],
                reason: $validated['reason'],
                actor: $request->user(),
            );

            return redirect()->back()->with('success', 'Stock level adjusted successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['stock' => $e->getMessage()]);
        }
    }

    public function movements(Request $request): Response
    {
        $movements = InventoryMovement::with(['variant.product', 'actor:id,name'])
            ->latest()
            ->paginate(25)
            ->through(fn ($m) => [
                'id' => $m->id,
                'sku' => $m->variant->sku,
                'product_name' => $m->variant->product->getLocalizedName(),
                'quantity_change' => $m->quantity_change,
                'resulting_on_hand' => $m->resulting_on_hand,
                'resulting_reserved' => $m->resulting_reserved,
                'reason' => $m->reason,
                'actor' => $m->actor?->name ?? 'System',
                'created_at' => $m->created_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Admin/Inventory/Movements', [
            'movements' => $movements,
        ]);
    }
}
