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
}
