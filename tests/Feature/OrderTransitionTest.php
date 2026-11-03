<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Order\InvalidOrderTransitionException;
use App\Services\Order\OrderStateMachineService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTransitionTest extends TestCase
{
    use DatabaseTransactions;

    protected OrderStateMachineService $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = app(OrderStateMachineService::class);
    }

    public function test_order_fulfillment_lifecycle_transitions(): void
    {
        $admin = User::first();
        $order = Order::create([
            'order_number' => 'TEST-ORD-001',
            'customer_name' => 'John Doe',
            'customer_phone' => '+85512000111',
            'subtotal' => 100.00,
            'grand_total' => 100.00,
            'settlement_amount' => 410000.00,
            'fulfillment_status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // pending -> confirmed
        $order = $this->stateMachine->transitionFulfillment($order, 'confirmed', $admin);
        $this->assertEquals('confirmed', $order->fulfillment_status);

        // confirmed -> processing
        $order = $this->stateMachine->transitionFulfillment($order, 'processing', $admin);
        $this->assertEquals('processing', $order->fulfillment_status);

        // processing -> packed
        $order = $this->stateMachine->transitionFulfillment($order, 'packed', $admin);
        $this->assertEquals('packed', $order->fulfillment_status);

        // packed -> shipped
        $order = $this->stateMachine->transitionFulfillment($order, 'shipped', $admin);
        $this->assertEquals('shipped', $order->fulfillment_status);

        // shipped -> delivered
        $order = $this->stateMachine->transitionFulfillment($order, 'delivered', $admin);
        $this->assertEquals('delivered', $order->fulfillment_status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_rejects_illegal_fulfillment_jump(): void
    {
        $admin = User::first();
        $order = Order::create([
            'order_number' => 'TEST-ORD-002',
            'customer_name' => 'Jane Smith',
            'customer_phone' => '+85512000222',
            'subtotal' => 50.00,
            'grand_total' => 50.00,
            'settlement_amount' => 205000.00,
            'fulfillment_status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->expectException(InvalidOrderTransitionException::class);

        // Cannot jump directly from pending to delivered without packing/shipping
        $this->stateMachine->transitionFulfillment($order, 'delivered', $admin);
    }
}
