<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_browse_homepage_and_shop(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $responseShop = $this->get(route('shop.index'));
        $responseShop->assertStatus(200);
    }

    public function test_customer_can_view_product_detail_and_interact_with_cart(): void
    {
        $category = Category::first();
        $this->assertNotNull($category);

        $product = Product::where('status', 'active')->first();
        $this->assertNotNull($product);

        $responseProduct = $this->get(route('products.show', ['slug' => $product->slug]));
        $responseProduct->assertStatus(200);

        $variant = $product->variants()->first();
        $this->assertNotNull($variant);

        // Add to cart
        $addResponse = $this->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        $addResponse->assertRedirect();
        $addResponse->assertSessionHas('success');

        // View Cart
        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200);

        // Switch locale to Khmer
        $localeResponse = $this->post(route('locale.set', ['locale' => 'km']));
        $localeResponse->assertRedirect();
        $this->assertEquals('km', session('locale'));

        // Switch currency to KHR
        $currResponse = $this->post(route('currency.set', ['currency' => 'KHR']));
        $currResponse->assertRedirect();
        $this->assertEquals('KHR', session('currency'));
    }

    public function test_customer_can_load_checkout_with_items_in_cart(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = Product::where('status', 'active')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        // Add to cart as authenticated user
        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        // Load checkout
        $checkoutResponse = $this->actingAs($user)->get(route('checkout.create'));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertInertia(fn ($page) => $page
            ->component('Customer/Checkout')
            ->has('cartItems')
            ->has('pricing')
        );
    }

    public function test_customer_can_submit_checkout_and_place_order(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = Product::where('status', 'active')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $shipping = \App\Models\ShippingMethod::firstOrFail();

        // Add to cart
        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $checkoutData = [
            'recipient_name' => 'Kong Vanndy',
            'phone' => '0969015314',
            'email' => 'kongvanndyofficial@gmail.com',
            'province' => 'Phnom Penh',
            'district' => 'Daun Penh',
            'commune' => 'Phsar Kandal',
            'street_address' => 'Norodom Blvd, House 12',
            'delivery_notes' => 'Call on delivery',
            'shipping_method_id' => $shipping->id,
            'payment_method' => 'cod',
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ];

        $response = $this->actingAs($user)->post(route('checkout.store'), $checkoutData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Order exists in database
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_name' => 'Kong Vanndy',
            'customer_phone' => '0969015314',
            'fulfillment_status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_customer_can_checkout_with_khqr_and_view_qr_confirmation(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = Product::where('status', 'active')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $shipping = \App\Models\ShippingMethod::firstOrFail();

        // Add to cart
        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $checkoutData = [
            'recipient_name' => 'Kong Vanndy',
            'phone' => '0969015314',
            'email' => 'kongvanndyofficial@gmail.com',
            'province' => 'Phnom Penh',
            'district' => 'Daun Penh',
            'commune' => 'Phsar Kandal',
            'street_address' => 'Norodom Blvd, House 12',
            'delivery_notes' => 'Call on delivery',
            'shipping_method_id' => $shipping->id,
            'payment_method' => 'khqr_sandbox',
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ];

        $response = $this->actingAs($user)->post(route('checkout.store'), $checkoutData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = \App\Models\Order::where('user_id', $user->id)->latest()->firstOrFail();

        // Follow redirect to confirmed page
        $confirmedResponse = $this->actingAs($user)->get(route('orders.confirmed', ['orderNumber' => $order->order_number]));
        $confirmedResponse->assertStatus(200);
        $confirmedResponse->assertInertia(fn ($page) => $page
            ->component('Customer/OrderConfirmed')
            ->has('khqrPayload')
            ->has('bankDetails')
        );
    }

    public function test_checkout_handles_idempotency_mismatch_transparently_without_500_error(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = Product::where('status', 'active')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $shipping = \App\Models\ShippingMethod::firstOrFail();

        // Add 1st item
        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $reusedKey = \Illuminate\Support\Str::uuid()->toString();

        $firstCheckoutData = [
            'recipient_name' => 'Kong Vanndy',
            'phone' => '0969015314',
            'email' => 'kong@example.com',
            'province' => 'Phnom Penh',
            'district' => 'Daun Penh',
            'commune' => 'Phsar Kandal',
            'street_address' => 'Norodom Blvd, House 12',
            'shipping_method_id' => $shipping->id,
            'payment_method' => 'cod',
            'idempotency_key' => $reusedKey,
        ];

        $firstResponse = $this->actingAs($user)->post(route('checkout.store'), $firstCheckoutData);
        $firstResponse->assertRedirect();
        $firstResponse->assertSessionHas('success');

        // Now add another item to cart and re-submit using the SAME idempotency key but DIFFERENT payment method / payload
        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $secondCheckoutData = array_merge($firstCheckoutData, [
            'payment_method' => 'khqr_sandbox', // Changed payment method
            'idempotency_key' => $reusedKey,     // Reused stale key!
        ]);

        // Controller must catch IdempotencyMismatchException and transparently succeed without 500 error!
        $secondResponse = $this->actingAs($user)->post(route('checkout.store'), $secondCheckoutData);
        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHas('success');

        // Two orders should now exist
        $orders = \App\Models\Order::where('user_id', $user->id)->get();
        $this->assertCount(2, $orders);
    }

    public function test_customer_can_checkout_with_aba_payway_and_complete_sandbox_payment(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = Product::where('status', 'active')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $shipping = \App\Models\ShippingMethod::firstOrFail();

        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $checkoutData = [
            'recipient_name' => 'Kong Vanndy',
            'phone' => '0969015314',
            'email' => 'kongvanndyofficial@gmail.com',
            'province' => 'Phnom Penh',
            'district' => 'Daun Penh',
            'commune' => 'Phsar Kandal',
            'street_address' => 'Norodom Blvd, House 12',
            'delivery_notes' => 'Call on delivery',
            'shipping_method_id' => $shipping->id,
            'payment_method' => 'aba_payway_sandbox',
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ];

        $response = $this->actingAs($user)->post(route('checkout.store'), $checkoutData);
        $response->assertRedirect();

        $order = \App\Models\Order::where('user_id', $user->id)->latest()->firstOrFail();
        $payment = $order->payments->first();
        $this->assertNotNull($payment);
        $this->assertEquals('aba_payway_sandbox', $payment->gateway);
        $this->assertEquals('pending', $payment->status);

        // Load the ABA PayWay Sandbox Checkout Page
        $paywayResponse = $this->actingAs($user)->get(route('payway.sandbox.checkout', ['reference' => $payment->merchant_reference]));
        $paywayResponse->assertStatus(200);
        $paywayResponse->assertInertia(fn ($page) => $page
            ->component('Customer/PayWayCheckout')
            ->has('order')
            ->has('payway')
            ->has('reference')
        );

        // Simulate successful payment
        $simulateResponse = $this->actingAs($user)->post(route('payway.sandbox.simulate', ['reference' => $payment->merchant_reference]));
        $simulateResponse->assertRedirect(route('orders.confirmed', [
            'orderNumber' => $order->order_number,
            'token' => $order->guest_token,
        ]));

        $payment->refresh();
        $order->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('paid', $order->payment_status);

        // Confirmed page reflects paid ABA PayWay status
        $confirmedResponse = $this->actingAs($user)->get(route('orders.confirmed', ['orderNumber' => $order->order_number]));
        $confirmedResponse->assertStatus(200);
        $confirmedResponse->assertInertia(fn ($page) => $page
            ->component('Customer/OrderConfirmed')
            ->where('order.payment_status', 'paid')
            ->has('paywayPayload')
        );
    }

    public function test_aba_payway_webhook_marks_order_paid(): void
    {
        $user = \App\Models\User::factory()->create();
        $product = Product::where('status', 'active')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $shipping = \App\Models\ShippingMethod::firstOrFail();

        $this->actingAs($user)->post(route('cart.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $checkoutData = [
            'recipient_name' => 'Kong Vanndy',
            'phone' => '0969015314',
            'email' => 'kongvanndyofficial@gmail.com',
            'province' => 'Phnom Penh',
            'district' => 'Daun Penh',
            'commune' => 'Phsar Kandal',
            'street_address' => 'Norodom Blvd, House 12',
            'shipping_method_id' => $shipping->id,
            'payment_method' => 'aba_payway_sandbox',
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
        ];

        $this->actingAs($user)->post(route('checkout.store'), $checkoutData);
        $order = \App\Models\Order::where('user_id', $user->id)->latest()->firstOrFail();
        $payment = $order->payments->first();

        // Webhook request from ABA PayWay with status 0
        $webhookResponse = $this->postJson(route('webhook.aba_payway'), [
            'tran_id' => $payment->merchant_reference,
            'status' => 0,
            'apv' => 'APV-WEBHOOK-999',
            'amount' => (string) $payment->amount,
            'currency' => 'USD',
        ]);

        $webhookResponse->assertStatus(200);
        $webhookResponse->assertJson(['status' => '00', 'description' => 'Success']);

        $payment->refresh();
        $order->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('paid', $order->payment_status);
    }
}
