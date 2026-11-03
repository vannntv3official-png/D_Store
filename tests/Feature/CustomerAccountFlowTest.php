<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address;
use App\Models\ContactMessage;
use App\Models\ContentPage;
use App\Models\Product;
use App\Models\Reward;
use App\Models\User;
use App\Services\Pricing\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerAccountFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'name' => 'Sokha Chan',
            'phone' => '012999001',
            'email' => 'sokha.test@example.com',
            'is_active' => true,
        ]);
    }

    public function test_customer_can_manage_saved_addresses(): void
    {
        $response = $this->actingAs($this->customer)->get('/account/addresses');
        $response->assertStatus(200);

        // Add new address
        $storeResponse = $this->actingAs($this->customer)->post('/account/addresses', [
            'recipient_name' => 'Sokha Chan',
            'phone' => '012999001',
            'province' => 'Phnom Penh',
            'district' => 'Chamkar Mon',
            'commune' => 'Tonle Bassac',
            'street_address' => '#128, St. 51',
            'is_default' => true,
        ]);
        $storeResponse->assertRedirect();

        $address = Address::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($address);
        $this->assertEquals('Phnom Penh', $address->province_name);
        $this->assertTrue($address->is_default_shipping);

        // Delete address
        $delResponse = $this->actingAs($this->customer)->delete("/account/addresses/{$address->id}");
        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_customer_can_toggle_wishlist(): void
    {
        $product = Product::where('status', 'active')->first();
        $this->assertNotNull($product);

        $response = $this->actingAs($this->customer)->get('/account/wishlist');
        $response->assertStatus(200);

        // Add to wishlist
        $addResponse = $this->actingAs($this->customer)->post("/account/wishlist/{$product->id}");
        $addResponse->assertRedirect();
        $this->assertDatabaseHas('wishlist_items', ['product_id' => $product->id]);

        // Remove from wishlist
        $removeResponse = $this->actingAs($this->customer)->post("/account/wishlist/{$product->id}");
        $removeResponse->assertRedirect();
        $this->assertDatabaseMissing('wishlist_items', ['product_id' => $product->id]);
    }

    public function test_customer_can_view_loyalty_and_redeem(): void
    {
        $response = $this->actingAs($this->customer)->get('/account/loyalty');
        $response->assertStatus(200);

        $reward = Reward::firstOrCreate(
            ['points_cost' => 50],
            [
                'name' => ['en' => 'Welcome Gift $5 Off', 'km' => 'កាដូស្វាគមន៍បញ្ចុះ ៥ដុល្លារ'],
                'discount_type' => 'fixed',
                'discount_value' => '5.0000',
                'is_active' => true,
            ]
        );

        // Customer was granted 100 welcome bonus on registration/account creation
        $redeemResponse = $this->actingAs($this->customer)->post("/account/loyalty/redeem/{$reward->id}");
        $redeemResponse->assertRedirect();
    }

    public function test_visitor_can_submit_contact_form(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);

        $submitResponse = $this->post('/contact', [
            'name' => 'Dara Tester',
            'phone' => '012888777',
            'email' => 'dara@example.com',
            'subject' => 'Inquiry regarding shipping to Siem Reap',
            'message' => 'Do you provide express courier delivery to Siem Reap province?',
        ]);

        $submitResponse->assertRedirect();
        $this->assertDatabaseHas('contact_messages', [
            'phone' => '012888777',
            'subject' => 'Inquiry regarding shipping to Siem Reap',
        ]);
    }

    public function test_cms_pages_render_cleanly(): void
    {
        ContentPage::firstOrCreate(
            ['slug' => 'about'],
            [
                'title' => [
                    'en' => 'About D STORE',
                    'km' => 'អំពី D STORE',
                ],
                'body' => [
                    'en' => 'D STORE is Cambodia premier luxury destination.',
                    'km' => 'D STORE ជាទីតាំងទិញទំនិញប្រណិតឈានមុខគេនៅកម្ពុជា។',
                ],
                'is_active' => true,
            ]
        );

        $response = $this->get('/page/about');
        $response->assertStatus(200);
    }

    public function test_sitemap_xml_renders_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/xml');
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $response->getContent());
        $this->assertStringContainsString('<loc>', $response->getContent());
    }
}
