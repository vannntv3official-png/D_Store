<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_khqr_webhook_marks_payment_and_order_paid_and_is_idempotent(): void
    {
        $unique = bin2hex(random_bytes(4));
        $order = Order::create([
            'order_number' => "ORD-WH1-{$unique}",
            'guest_token' => bin2hex(random_bytes(32)),
            'customer_name' => 'Sokha Chan',
            'customer_phone' => '012345678',
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'payment_status' => Order::PAYMENT_PENDING,
            'base_currency' => 'USD',
            'display_currency' => 'USD',
            'exchange_rate' => '4100.0000',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'settlement_amount' => '100.0000',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'khqr_sandbox',
            'merchant_reference' => "KHQR-TXN-{$unique}",
            'amount' => '100.0000',
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $payload = [
            'bill_number' => "KHQR-TXN-{$unique}",
            'transaction_id' => "EXT-BAKONG-{$unique}",
            'status' => 'SUCCESS',
            'amount' => 100.00,
        ];

        // First delivery
        $response1 = $this->postJson(route('webhook.khqr'), $payload);
        $response1->assertStatus(200);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('paid', $payment->status);
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertEquals(Order::FULFILLMENT_CONFIRMED, $order->fulfillment_status);
        $this->assertDatabaseHas('payment_webhook_events', [
            'gateway' => 'khqr_sandbox',
            'event_id' => "khqr_EXT-BAKONG-{$unique}",
            'status' => 'processed',
        ]);

        // Second delivery (duplicate replay)
        $response2 = $this->postJson(route('webhook.khqr'), $payload);
        $response2->assertStatus(200);
        $response2->assertJson(['message' => 'Event already processed']);

        $this->assertEquals(1, PaymentWebhookEvent::where('event_id', "khqr_EXT-BAKONG-{$unique}")->count());
    }

    public function test_aba_payway_webhook_marks_payment_paid(): void
    {
        $unique = bin2hex(random_bytes(4));
        $order = Order::create([
            'order_number' => "ORD-WH2-{$unique}",
            'guest_token' => bin2hex(random_bytes(32)),
            'customer_name' => 'Dara Vong',
            'customer_phone' => '098765432',
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'payment_status' => Order::PAYMENT_PENDING,
            'base_currency' => 'USD',
            'display_currency' => 'USD',
            'exchange_rate' => '4100.0000',
            'subtotal' => '50.0000',
            'grand_total' => '50.0000',
            'settlement_amount' => '50.0000',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'aba_payway_sandbox',
            'merchant_reference' => "PAYWAY-TR-{$unique}",
            'amount' => '50.0000',
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $payload = [
            'tran_id' => "PAYWAY-TR-{$unique}",
            'status' => 0, // 0 = Approved
            'apv' => "APPROVAL-{$unique}",
        ];

        $response = $this->postJson(route('webhook.aba_payway'), $payload);
        $response->assertStatus(200);
        $response->assertJson(['status' => '00', 'description' => 'Success']);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals('paid', $payment->status);
        $this->assertEquals(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertEquals(Order::FULFILLMENT_CONFIRMED, $order->fulfillment_status);
    }
}
