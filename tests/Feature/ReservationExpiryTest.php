<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReservationExpiryTest extends TestCase
{
    use DatabaseTransactions;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }

    public function test_reservation_and_expiry_lifecycle(): void
    {
        $variant = ProductVariant::where('sku', 'ACH-ANC-BLK')->first();
        $inv = Inventory::where('product_variant_id', $variant->id)->first();
        $initialOnHand = $inv->on_hand_quantity;
        $initialReserved = $inv->reserved_quantity;

        // 1. Reserve 3 units
        $res = $this->inventoryService->reserveStock($variant, 3, orderId: 99991);
        $this->assertEquals('reserved', $res->status);

        $inv->refresh();
        $this->assertEquals($initialReserved + 3, $inv->reserved_quantity);
        $this->assertEquals($initialOnHand, $inv->on_hand_quantity);

        // 2. Release reservation (simulating timeout or cancellation)
        $this->inventoryService->releaseReservation($res, 'reservation_expired');
        $this->assertEquals('released', $res->fresh()->status);

        $inv->refresh();
        $this->assertEquals($initialReserved, $inv->reserved_quantity);
        $this->assertEquals($initialOnHand, $inv->on_hand_quantity);
    }

    public function test_consumption_on_shipment_reduces_on_hand_stock_permanently(): void
    {
        $variant = ProductVariant::where('sku', 'ACH-ANC-BLK')->first();
        $inv = Inventory::where('product_variant_id', $variant->id)->first();
        $initialOnHand = $inv->on_hand_quantity;
        $initialReserved = $inv->reserved_quantity;

        // 1. Reserve 2 units
        $res = $this->inventoryService->reserveStock($variant, 2, orderId: 99992);

        // 2. Consume on shipment
        $this->inventoryService->consumeReservation($res);
        $this->assertEquals('consumed', $res->fresh()->status);

        $inv->refresh();
        $this->assertEquals($initialOnHand - 2, $inv->on_hand_quantity);
        $this->assertEquals($initialReserved, $inv->reserved_quantity);
    }
}
