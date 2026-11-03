<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\IdempotencyMismatchException;
use App\Services\Inventory\InsufficientStockException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutConcurrencyTest extends TestCase
{
    use DatabaseTransactions;

    protected CheckoutService $checkoutService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checkoutService = app(CheckoutService::class);
    }

    public function test_can_checkout_order_successfully_and_reserves_stock(): void
    {
        $variant = ProductVariant::where('sku', 'HZN-ULT-BLK-256')->first();
        $initialInv = Inventory::where('product_variant_id', $variant->id)->first();
        $initialReserved = $initialInv->reserved_quantity;

        $user = User::factory()->create();
        $shipping = ShippingMethod::first();

        $cart = Cart::create([
            'user_id' => $user->id,
            'session_token' => Str::random(40),
            'shipping_method_id' => $shipping->id,
            'currency' => 'USD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price_snapshot' => $variant->price,
        ]);

        $addressData = [
            'contact_name' => 'Sokha Chan',
            'contact_phone' => '+85512345678',
            'province_name' => 'Phnom Penh',
            'district_name' => 'Daun Penh',
            'address_line' => 'Street 130, House 12',
        ];

        $idempotencyKey = Str::uuid()->toString();

        $result = $this->checkoutService->processCheckout(
            cart: $cart,
            shippingAddressData: $addressData,
            paymentMethod: 'cod',
            idempotencyKey: $idempotencyKey,
            user: $user
        );

        $order = $result['order'];
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->fulfillment_status);
        $this->assertEquals(2, $order->items->first()->quantity);

        // Verify reserved stock incremented by 2
        $updatedInv = Inventory::where('product_variant_id', $variant->id)->first();
        $this->assertEquals($initialReserved + 2, $updatedInv->reserved_quantity);

        // Verify cart cleared
        $this->assertEquals(0, CartItem::where('cart_id', $cart->id)->count());
    }

    public function test_checkout_rejects_when_stock_is_insufficient(): void
    {
        $variant = ProductVariant::where('sku', 'HZN-ULT-BLK-256')->first();
        $inv = Inventory::where('product_variant_id', $variant->id)->first();
        $available = $inv->on_hand_quantity - $inv->reserved_quantity;

        $user = User::factory()->create();
        $cart = Cart::create([
            'user_id' => $user->id,
            'session_token' => Str::random(40),
        ]);

        // Request available + 10 units
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => $available + 10,
            'unit_price_snapshot' => $variant->price,
        ]);

        $this->expectException(InsufficientStockException::class);

        $this->checkoutService->processCheckout(
            cart: $cart,
            shippingAddressData: [
                'contact_name' => 'Test Customer',
                'contact_phone' => '+85512999888',
                'province_name' => 'Phnom Penh',
                'district_name' => 'Sen Sok',
                'address_line' => 'House 45',
            ],
            paymentMethod: 'cod',
            idempotencyKey: Str::uuid()->toString(),
            user: $user
        );
    }

    public function test_checkout_idempotency_returns_identical_order_and_rejects_hash_mismatch(): void
    {
        $variant = ProductVariant::where('sku', 'HZN-ULT-BLK-256')->first();
        $user = User::factory()->create();
        $cart = Cart::create([
            'user_id' => $user->id,
            'session_token' => Str::random(40),
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_snapshot' => $variant->price,
        ]);

        $addressData = [
            'contact_name' => 'Bopha Pich',
            'contact_phone' => '+85512777888',
            'province_name' => 'Phnom Penh',
            'district_name' => 'Toul Kork',
            'address_line' => 'St 289',
        ];

        $idempotencyKey = Str::uuid()->toString();

        // 1. Initial checkout
        $firstResult = $this->checkoutService->processCheckout(
            cart: $cart,
            shippingAddressData: $addressData,
            paymentMethod: 'cod',
            idempotencyKey: $idempotencyKey,
            user: $user
        );

        $this->assertFalse($firstResult['is_replay']);
        $firstOrderNumber = $firstResult['order']->order_number;

        // 2. Replay with identical key & same cart state
        $replayResult = $this->checkoutService->processCheckout(
            cart: $cart,
            shippingAddressData: $addressData,
            paymentMethod: 'cod',
            idempotencyKey: $idempotencyKey,
            user: $user
        );

        $this->assertTrue($replayResult['is_replay']);
        $this->assertEquals($firstOrderNumber, $replayResult['order']->order_number);

        // 3. Add item back to cart, unset cached relation, and replay with identical key but mismatched address payload -> throws IdempotencyMismatchException
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_snapshot' => $variant->price,
        ]);
        $cart->unsetRelation('items');

        $this->expectException(IdempotencyMismatchException::class);

        $differentAddress = array_merge($addressData, ['contact_name' => 'Different Person']);
        $this->checkoutService->processCheckout(
            cart: $cart,
            shippingAddressData: $differentAddress,
            paymentMethod: 'cod',
            idempotencyKey: $idempotencyKey,
            user: $user
        );
    }
}
