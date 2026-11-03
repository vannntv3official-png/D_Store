<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\LoyaltyAccount;
use App\Models\Order;
use App\Models\PointLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_expire_reservations_command_releases_stale_stock_and_cancels_order(): void
    {
        $unique = bin2hex(random_bytes(4));

        $category = Category::first() ?? Category::create([
            'name' => ['en' => 'Test Cat', 'km' => 'Test Cat KM'],
            'slug' => "test-cat-{$unique}",
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => ['en' => "Cmd Test Prod {$unique}", 'km' => "Cmd Test Prod KM {$unique}"],
            'slug' => "cmd-test-prod-{$unique}",
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => "CMD-VAR-{$unique}",
            'price' => '100.0000',
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'on_hand_quantity' => 10,
            'reserved_quantity' => 2,
        ]);

        $order = Order::create([
            'order_number' => "ORD-CMD-{$unique}",
            'customer_name' => 'Cmd Test',
            'customer_phone' => '012111222',
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'payment_status' => Order::PAYMENT_PENDING,
            'subtotal' => '200.0000',
            'grand_total' => '200.0000',
            'settlement_amount' => '200.0000',
        ]);

        $reservation = InventoryReservation::create([
            'product_variant_id' => $variant->id,
            'order_id' => $order->id,
            'quantity' => 2,
            'status' => 'reserved',
            'expires_at' => now()->subMinutes(10), // Expired 10 min ago
        ]);

        $this->artisan('orders:expire-reservations')
            ->assertSuccessful();

        $reservation->refresh();
        $inventory->refresh();
        $order->refresh();

        $this->assertEquals('released', $reservation->status);
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals(Order::FULFILLMENT_CANCELLED, $order->fulfillment_status);
    }

    public function test_expire_loyalty_lots_command_expires_outdated_points(): void
    {
        $unique = bin2hex(random_bytes(4));

        $user = User::create([
            'name' => "Loyalty Expire User {$unique}",
            'phone' => "088{$unique}",
            'password' => 'secret',
            'is_active' => true,
        ]);

        $account = LoyaltyAccount::create([
            'user_id' => $user->id,
            'current_points_balance' => 150,
            'lifetime_points_earned' => 200,
            'lifetime_points_spent' => 50,
            'points_debt' => 0,
        ]);

        $expiredLot = PointLot::create([
            'loyalty_account_id' => $account->id,
            'initial_points' => 50,
            'remaining_points' => 50,
            'is_expired' => false,
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $this->artisan('loyalty:expire-lots')
            ->assertSuccessful();

        $expiredLot->refresh();
        $account->refresh();

        $this->assertTrue((bool) $expiredLot->is_expired);
        $this->assertEquals(0, $expiredLot->remaining_points);
        $this->assertEquals(100, $account->current_points_balance); // 150 - 50 = 100
        $this->assertDatabaseHas('loyalty_transactions', [
            'loyalty_account_id' => $account->id,
            'event_type' => 'expiry',
            'points_change' => -50,
        ]);
    }
}
