<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin.test@dstore.com.kh',
            'phone' => '012999002',
            'is_active' => true,
        ]);

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Administrator']
        );

        $this->adminUser->roles()->sync([$superAdminRole->id]);
    }

    public function test_admin_dashboard_renders_with_metrics_and_chart(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics.paid_revenue')
            ->has('metrics.total_orders')
            ->has('chart.labels')
            ->has('chart.data')
            ->has('recentOrders')
        );
    }

    public function test_admin_can_browse_products_and_export_csv(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/products');
        $response->assertStatus(200);

        // CSV export
        $csvResponse = $this->actingAs($this->adminUser)->get('/admin/products/export/csv');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_manage_categories(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/categories');
        $response->assertStatus(200);

        $storeResponse = $this->actingAs($this->adminUser)->post('/admin/categories', [
            'name_en' => 'Test Audio & Acoustics',
            'name_km' => 'ឧបករណ៍សំឡេងសាកល្បង',
            'display_order' => 15,
            'is_active' => true,
        ]);
        $storeResponse->assertRedirect('/admin/categories');
        $this->assertDatabaseHas('categories', ['slug' => 'test-audio-acoustics']);
    }

    public function test_admin_can_view_orders_and_invoice(): void
    {
        $order = Order::first();
        if ($order) {
            $response = $this->actingAs($this->adminUser)->get('/admin/orders');
            $response->assertStatus(200);

            $showResponse = $this->actingAs($this->adminUser)->get("/admin/orders/{$order->id}");
            $showResponse->assertStatus(200);

            $invoiceResponse = $this->actingAs($this->adminUser)->get("/admin/orders/{$order->id}/invoice");
            $invoiceResponse->assertStatus(200);
            $invoiceResponse->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Invoice')
                ->has('order.order_number')
                ->has('store.name')
            );
        }
    }

    public function test_admin_can_view_and_adjust_inventory(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/inventory');
        $response->assertStatus(200);

        $variant = ProductVariant::first();
        if ($variant) {
            $adjustResponse = $this->actingAs($this->adminUser)->post('/admin/inventory/adjust', [
                'variant_id' => $variant->id,
                'delta' => 15,
                'reason' => 'Cycle count arrival PO #TEST-100',
            ]);
            $adjustResponse->assertRedirect();
        }

        $movementsResponse = $this->actingAs($this->adminUser)->get('/admin/inventory/movements');
        $movementsResponse->assertStatus(200);
    }

    public function test_admin_can_manage_marketing_coupons_and_banners(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/marketing');
        $response->assertStatus(200);

        $couponCode = 'VIP' . rand(1000, 9999);
        $couponResponse = $this->actingAs($this->adminUser)->post('/admin/marketing/coupons', [
            'code' => $couponCode,
            'discount_type' => 'percentage',
            'discount_value' => '20.00',
            'min_spend' => '100.00',
            'usage_limit' => 50,
            'is_active' => true,
        ]);
        $couponResponse->assertRedirect();
        $this->assertDatabaseHas('coupons', ['code' => $couponCode]);
    }

    public function test_admin_can_view_loyalty_and_settings(): void
    {
        $loyaltyResponse = $this->actingAs($this->adminUser)->get('/admin/loyalty');
        $loyaltyResponse->assertStatus(200);

        $settingsResponse = $this->actingAs($this->adminUser)->get('/admin/settings');
        $settingsResponse->assertStatus(200);

        $updateSettingsResponse = $this->actingAs($this->adminUser)->put('/admin/settings', [
            'store_name' => 'D STORE CAMBODIA',
            'store_phone' => '+855 23 888 999',
            'store_email' => 'contact@dstore.com.kh',
            'store_address_en' => 'Norodom Blvd, Phnom Penh, Cambodia',
            'store_address_km' => 'មហាវិថីព្រះនរោត្តម រាជធានីភ្នំពេញ ព្រះរាជាណាចក្រកម្ពុជា',
            'exchange_rate_khr_per_usd' => 4120,
            'bank_name' => 'ABA Bank Cambodia',
            'bank_account_name' => 'D STORE CO., LTD.',
            'bank_account_usd' => '001 234 567',
            'bank_account_khr' => '001 234 568',
            'payment_cod_enabled' => true,
            'payment_bank_enabled' => true,
            'payment_khqr_enabled' => true,
            'payment_payway_enabled' => true,
        ]);
        $updateSettingsResponse->assertRedirect();
    }

    public function test_admin_can_login_with_email_and_phone(): void
    {
        $this->adminUser->update(['password' => 'SecretPassword123!']);

        // 1. Email login on /admin/login
        $responseEmail = $this->post('/admin/login', [
            'email' => $this->adminUser->email,
            'password' => 'SecretPassword123!',
        ]);
        $responseEmail->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);

        $this->post('/admin/logout');
        $this->assertGuest();

        // 2. Phone login on /admin/login
        $responsePhone = $this->post('/admin/login', [
            'email' => $this->adminUser->phone,
            'password' => 'SecretPassword123!',
        ]);
        $responsePhone->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);

        $this->post('/admin/logout');
        $this->assertGuest();

        // 3. Customer portal auto-redirect to admin
        $responseCustomerLogin = $this->post('/login', [
            'login' => $this->adminUser->email,
            'password' => 'SecretPassword123!',
        ]);
        $responseCustomerLogin->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);
    }
}
