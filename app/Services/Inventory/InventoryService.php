<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\ProductVariant;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class InsufficientStockException extends Exception {}
class CannotReduceBelowReservedException extends Exception {}

class InventoryService
{
    /**
     * Atomically reserve stock for an order or pending checkout.
     */
    public function reserveStock(ProductVariant $variant, int $quantity, ?int $orderId = null, int $ttlMinutes = 30): InventoryReservation
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($variant, $quantity, $orderId, $ttlMinutes) {
            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $variant->id)->lockForUpdate()->first();

            if (!$inventory) {
                throw new InsufficientStockException("Inventory record not found for variant SKU: {$variant->sku}");
            }

            $available = $inventory->on_hand_quantity - $inventory->reserved_quantity;

            if ($available < $quantity) {
                throw new InsufficientStockException("Insufficient stock for SKU {$variant->sku}. Requested: {$quantity}, Available: {$available}");
            }

            $inventory->reserved_quantity += $quantity;
            $inventory->save();

            $reservation = InventoryReservation::create([
                'order_id' => $orderId,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'status' => 'reserved',
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);

            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'quantity_change' => 0,
                'resulting_on_hand' => $inventory->on_hand_quantity,
                'resulting_reserved' => $inventory->reserved_quantity,
                'reason' => 'order_reserved',
                'actor_id' => null,
                'operation_reference' => $orderId ? "ORDER_{$orderId}" : "RESERVATION_{$reservation->id}",
                'notes' => "Reserved {$quantity} units for order {$orderId}",
            ]);

            return $reservation;
        });
    }

    /**
     * Consume an active reservation when order is shipped / fulfilled.
     * Decrements both on_hand and reserved quantities exactly once.
     */
    public function consumeReservation(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            /** @var InventoryReservation $lockedRes */
            $lockedRes = InventoryReservation::where('id', $reservation->id)->lockForUpdate()->first();

            if ($lockedRes->status !== 'reserved') {
                return; // Already consumed or released
            }

            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $lockedRes->product_variant_id)->lockForUpdate()->first();

            $inventory->on_hand_quantity = max(0, $inventory->on_hand_quantity - $lockedRes->quantity);
            $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $lockedRes->quantity);
            $inventory->save();

            $lockedRes->status = 'consumed';
            $lockedRes->save();

            InventoryMovement::create([
                'product_variant_id' => $lockedRes->product_variant_id,
                'quantity_change' => -$lockedRes->quantity,
                'resulting_on_hand' => $inventory->on_hand_quantity,
                'resulting_reserved' => $inventory->reserved_quantity,
                'reason' => 'order_fulfilled',
                'actor_id' => null,
                'operation_reference' => "ORDER_{$lockedRes->order_id}_SHIP",
                'notes' => "Consumed reservation {$lockedRes->id} on shipment",
            ]);
        });
    }

    /**
     * Release a reservation back into available stock.
     */
    public function releaseReservation(InventoryReservation $reservation, string $reason = 'order_cancelled'): void
    {
        DB::transaction(function () use ($reservation, $reason) {
            /** @var InventoryReservation $lockedRes */
            $lockedRes = InventoryReservation::where('id', $reservation->id)->lockForUpdate()->first();

            if ($lockedRes->status !== 'reserved') {
                return; // Already consumed or released
            }

            /** @var Inventory $inventory */
            $inventory = Inventory::where('product_variant_id', $lockedRes->product_variant_id)->lockForUpdate()->first();

            $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $lockedRes->quantity);
            $inventory->save();

            $lockedRes->status = 'released';
            $lockedRes->save();

            InventoryMovement::create([
                'product_variant_id' => $lockedRes->product_variant_id,
                'quantity_change' => 0,
                'resulting_on_hand' => $inventory->on_hand_quantity,
                'resulting_reserved' => $inventory->reserved_quantity,
                'reason' => $reason === 'reservation_expired' ? 'reservation_expired' : 'order_cancelled',
                'actor_id' => null,
                'operation_reference' => "RELEASE_RES_{$lockedRes->id}",
                'notes' => "Released reservation {$lockedRes->id} ({$reason})",
            ]);
        });
    }

    /**
     * Admin manual adjustment or restock.
     */
    public function adjustStock(
        ProductVariant $variant,
        int $delta,
        string $reason,
        ?User $actor = null,
        ?string $reference = null,
        ?string $notes = null
    ): Inventory {
        return DB::transaction(function () use ($variant, $delta, $reason, $actor, $reference, $notes) {
            /** @var Inventory $inventory */
            $inventory = Inventory::firstOrCreate(
                ['product_variant_id' => $variant->id],
                ['on_hand_quantity' => 0, 'reserved_quantity' => 0]
            );

            $lockedInv = Inventory::where('id', $inventory->id)->lockForUpdate()->first();

            $newOnHand = $lockedInv->on_hand_quantity + $delta;

            if ($newOnHand < 0) {
                throw new \InvalidArgumentException('On-hand quantity cannot be negative.');
            }

            if ($newOnHand < $lockedInv->reserved_quantity) {
                throw new CannotReduceBelowReservedException("Cannot reduce stock below active reservations ({$lockedInv->reserved_quantity}).");
            }

            $lockedInv->on_hand_quantity = $newOnHand;
            $lockedInv->save();

            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'quantity_change' => $delta,
                'resulting_on_hand' => $lockedInv->on_hand_quantity,
                'resulting_reserved' => $lockedInv->reserved_quantity,
                'reason' => $reason,
                'actor_id' => $actor?->id,
                'operation_reference' => $reference,
                'notes' => $notes,
            ]);

            return $lockedInv;
        });
    }
}
