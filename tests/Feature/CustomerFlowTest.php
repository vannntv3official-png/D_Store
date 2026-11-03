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
}
