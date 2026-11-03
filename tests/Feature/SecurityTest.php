<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductAdminController;
use App\Models\Address;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_responses_include_defensive_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_customer_cannot_view_another_customer_order(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orderB = Order::create([
            'order_number' => 'ORD-SEC-' . rand(10000, 99999),
            'guest_token' => bin2hex(random_bytes(32)),
            'user_id' => $userB->id,
            'customer_name' => 'User B',
            'customer_phone' => '012111222',
            'base_currency' => 'USD',
            'subtotal' => '100.00',
            'discount_total' => '0.00',
            'shipping_total' => '5.00',
            'grand_total' => '105.00',
            'settlement_amount' => '105.00',
            'fulfillment_status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // User A attempts to view User B's order
        $response = $this->actingAs($userA)->get("/account/orders/{$orderB->order_number}");
        $response->assertStatus(404);
    }

    public function test_order_confirmation_page_rejects_unauthorized_strangers(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SEC-STRANGER-' . rand(10000, 99999),
            'guest_token' => bin2hex(random_bytes(32)),
            'user_id' => null,
            'customer_name' => 'Secret Customer',
            'customer_phone' => '012999888',
            'base_currency' => 'USD',
            'subtotal' => '100.00',
            'discount_total' => '0.00',
            'shipping_total' => '5.00',
            'grand_total' => '105.00',
            'settlement_amount' => '105.00',
            'fulfillment_status' => 'pending',
            'payment_status' => 'pending',
        ]);

        Address::create([
            'addressable_type' => Order::class,
            'addressable_id' => $order->id,
            'type' => 'shipping',
            'contact_name' => 'Secret Customer',
            'contact_phone' => '012999888',
            'province_id' => 'phnom-penh',
            'province_name' => 'Phnom Penh',
            'district_id' => 'daun-penh',
            'district_name' => 'Daun Penh',
            'address_line' => 'St 200, House 10',
        ]);

        // 1. Unauthenticated stranger with no token -> 403 Forbidden
        $strangerResponse = $this->get("/orders/{$order->order_number}/confirmed");
        $strangerResponse->assertStatus(403);

        // 2. Stranger with invalid query token -> 403 Forbidden
        $invalidTokenResponse = $this->get("/orders/{$order->order_number}/confirmed?token=invalid_forged_token");
        $invalidTokenResponse->assertStatus(403);

        // 3. User with valid guest_token in query string -> 200 OK
        $authorizedResponse = $this->get("/orders/{$order->order_number}/confirmed?token={$order->guest_token}");
        $authorizedResponse->assertStatus(200);

        // 4. User with session guest token (right after checkout) -> 200 OK
        $sessionResponse = $this->withSession(['guest_token' => $order->guest_token])
            ->get("/orders/{$order->order_number}/confirmed");
        $sessionResponse->assertStatus(200);
    }

    public function test_staff_rbac_least_privilege_enforcement(): void
    {
        // Create an Order Manager staff member (who lacks staff.manage and settings.manage)
        $orderManager = User::factory()->create(['is_active' => true]);
        $orderManagerRole = Role::firstOrCreate(
            ['name' => 'order_manager'],
            ['display_name' => 'Order Manager']
        );
        $orderManager->roles()->sync([$orderManagerRole->id]);

        // 1. Order Manager can view orders
        $orderResponse = $this->actingAs($orderManager)->get('/admin/orders');
        $orderResponse->assertStatus(200);

        // 2. Order Manager is FORBIDDEN from staff management
        $staffResponse = $this->actingAs($orderManager)->get('/admin/staff');
        $staffResponse->assertStatus(403);

        // 3. Order Manager is FORBIDDEN from settings management
        $settingsResponse = $this->actingAs($orderManager)->get('/admin/settings');
        $settingsResponse->assertStatus(403);

        // 4. Super Admin has unrestricted access to staff management
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Administrator']
        );
        $superAdmin->roles()->sync([$superAdminRole->id]);

        $superAdminResponse = $this->actingAs($superAdmin)->get('/admin/staff');
        $superAdminResponse->assertStatus(200);
    }

    public function test_deactivated_staff_is_denied_access_and_logged_out(): void
    {
        $inactiveStaff = User::factory()->create(['is_active' => false]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Administrator']);
        $inactiveStaff->roles()->sync([$superAdminRole->id]);

        $response = $this->actingAs($inactiveStaff)->get('/admin');
        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_csv_export_sanitizes_formula_injection_prefixes(): void
    {
        // Test dangerous spreadsheet injection prefixes
        $dangerousInputs = [
            '=cmd|"/C calc"!A0',
            '+12345678',
            '-5+5',
            '@SUM(A1:A10)',
            "\t=HYPERLINK(\"http://evil.com\")",
            "   =1+1",
            "|dangerous",
        ];

        foreach ($dangerousInputs as $input) {
            $sanitized = ProductAdminController::sanitizeCsvField($input);
            $this->assertStringStartsWith("'", $sanitized, "Failed sanitizing input: {$input}");
        }

        // Test safe string is preserved
        $safeInput = "Apple iPhone 16 Pro Max";
        $this->assertEquals($safeInput, ProductAdminController::sanitizeCsvField($safeInput));
    }

    public function test_login_rate_limiting_protects_against_brute_force(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'login' => 'nonexistent_test@dstore.com.kh',
                'password' => 'WrongPassword123!',
            ]);
            $response->assertSessionHasErrors('login');
        }

        // 6th attempt should be rate limited with HTTP 429 Too Many Requests
        $rateLimitedResponse = $this->post('/login', [
            'login' => 'nonexistent_test@dstore.com.kh',
            'password' => 'WrongPassword123!',
        ]);

        $rateLimitedResponse->assertStatus(429);
    }
}
