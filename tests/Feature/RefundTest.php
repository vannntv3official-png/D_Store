<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Refund\OverRefundException;
use App\Services\Refund\RefundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use DatabaseTransactions;

    protected RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refundService = app(RefundService::class);
    }

    public function test_can_process_partial_refund_and_inspect_restock(): void
    {
        $admin = User::first();
        $variant = ProductVariant::where('sku', 'DS-TOT-BLK')->first();
        $inv = Inventory::where('product_variant_id', $variant->id)->first();
        $initialOnHand = $inv->on_hand_quantity;

        // Create paid order for 2 totes ($440 total)
        $order = Order::create([
            'order_number' => 'REFUND-TEST-001',
            'customer_name' => 'Sophea Vuth',
            'customer_phone' => '+85512999111',
            'subtotal' => 440.00,
            'grand_total' => 440.00,
            'settlement_amount' => 1804000.00,
            'fulfillment_status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => ['en' => 'Italian Leather Tote'],
            'sku_snapshot' => $variant->sku,
            'unit_price' => 220.00,
            'quantity' => 2,
            'line_subtotal' => 440.00,
            'line_total' => 440.00,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'khqr_sandbox',
            'currency' => 'USD',
            'amount' => 440.00,
            'status' => 'paid',
            'merchant_reference' => 'KHQR-REFUND-001',
        ]);

        // 1. Customer returns 1 tote -> partial refund of $220
        $refund = $this->refundService->processRefund(
            order: $order,
            amount: 220.00,
            reason: 'Customer requested return of 1 item',
            actor: $admin,
            itemQuantities: [$orderItem->id => 1]
        );

        $this->assertEquals('succeeded', $refund->status);
        $this->assertEquals('partially_refunded', $order->fresh()->payment_status);

        // Invariant: At this point, physical stock has NOT been restocked automatically
        $inv->refresh();
        $this->assertEquals($initialOnHand, $inv->on_hand_quantity);

        // 2. Staff inspects physical returned item and approves restock
        $this->refundService->inspectAndRestock($refund, $admin, 'Item inspected: mint condition');

        $inv->refresh();
        $this->assertEquals($initialOnHand + 1, $inv->on_hand_quantity);
        $this->assertTrue($refund->items->first()->fresh()->restock_approved);
    }

    public function test_rejects_refund_exceeding_order_grand_total(): void
    {
        $admin = User::first();
        $order = Order::create([
            'order_number' => 'REFUND-TEST-002',
            'customer_name' => 'Over Refund Test',
            'customer_phone' => '+85512999222',
            'subtotal' => 100.00,
            'grand_total' => 100.00,
            'settlement_amount' => 410000.00,
            'fulfillment_status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'cod',
            'currency' => 'USD',
            'amount' => 100.00,
            'status' => 'paid',
            'merchant_reference' => 'COD-REFUND-002',
        ]);

        $this->expectException(OverRefundException::class);

        // Attempting to refund $150 on a $100 order
        $this->refundService->processRefund(
            order: $order,
            amount: 150.00,
            reason: 'Excessive refund',
            actor: $admin
        );
    }
}
