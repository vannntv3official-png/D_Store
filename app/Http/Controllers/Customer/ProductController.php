<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Services\Pricing\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug',
                'images' => fn ($q) => $q->orderBy('display_order'),
                'variants.inventory',
                'variants.attributeValues.definition',
                'reviews' => fn ($q) => $q->where('is_approved', true)->with('user:id,name')->latest(),
            ])
            ->firstOrFail();

        // Related products in same category
        $relatedProducts = Product::where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->with(['brand:id,name', 'images', 'variants.inventory'])
            ->take(4)
            ->get()
            ->map(function (Product $rel) use ($rate) {
                $primaryImage = $rel->images->where('is_primary', true)->first() ?? $rel->images->first();
                $minPrice = $rel->variants->min('price') ?? '0.0000';
                $stock = $rel->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);

                return [
                    'id' => $rel->id,
                    'name_en' => $rel->name['en'] ?? (is_array($rel->name) ? reset($rel->name) : (string) $rel->name),
                    'name_km' => $rel->name['km'] ?? $rel->name['en'] ?? '',
                    'slug' => $rel->slug,
                    'brand' => $rel->brand?->name,
                    'base_price' => Money::format($minPrice),
                    'base_price_khr' => Money::toKhr($minPrice, $rate),
                    'primary_image' => $primaryImage?->image_url ?? '/images/placeholder.svg',
                    'in_stock' => $stock > 0,
                ];
            });

        // Formatted variants
        $variants = $product->variants->map(function ($v) use ($rate) {
            $availableStock = $v->inventory?->available_quantity ?? 0;
            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'price' => Money::format($v->price),
                'price_khr' => Money::toKhr($v->price, $rate),
                'compare_at_price' => $v->compare_at_price ? Money::format($v->compare_at_price) : null,
                'available_stock' => $availableStock,
                'is_in_stock' => $availableStock > 0,
                'image_url' => $v->images?->first()?->image_url,
                'attributes' => $v->attributeValues->map(fn ($va) => [
                    'attribute_id' => $va->attribute_definition_id,
                    'attribute_name' => $va->definition->name['en'] ?? (is_array($va->definition->name) ? reset($va->definition->name) : (string) $va->definition->name),
                    'value' => $va->text_value ?? (string) $va->number_value ?? ($va->boolean_value ? 'Yes' : 'No'),
                ]),
            ];
        });

        // Check if current user has purchased this product
        $canReview = false;
        if ($user = $request->user()) {
            $canReview = OrderItem::whereHas('order', function ($o) use ($user) {
                $o->where('user_id', $user->id)->where('fulfillment_status', Order::FULFILLMENT_DELIVERED);
            })->whereHas('variant', function ($v) use ($product) {
                $v->where('product_id', $product->id);
            })->exists();
        }

        $minPrice = $product->variants->min('price') ?? '0.0000';
        $compareAt = $product->variants->first()?->compare_at_price;

        return Inertia::render('Customer/ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name_en' => $product->name['en'] ?? (is_array($product->name) ? reset($product->name) : (string) $product->name),
                'name_km' => $product->name['km'] ?? $product->name['en'] ?? '',
                'slug' => $product->slug,
                'description_en' => $product->description['en'] ?? '',
                'description_km' => $product->description['km'] ?? '',
                'short_description_en' => $product->short_description['en'] ?? '',
                'short_description_km' => $product->short_description['km'] ?? '',
                'base_price' => Money::format($minPrice),
                'base_price_khr' => Money::toKhr($minPrice, $rate),
                'compare_at_price' => $compareAt ? Money::format($compareAt) : null,
                'brand' => $product->brand ? ['name' => $product->brand->name, 'slug' => $product->brand->slug] : null,
                'category' => $product->category ? [
                    'name_en' => $product->category->name['en'] ?? '',
                    'name_km' => $product->category->name['km'] ?? '',
                    'slug' => $product->category->slug,
                ] : null,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'image_url' => $img->image_url,
                    'alt_text' => $img->alt_text,
                    'is_primary' => (bool) $img->is_primary,
                ]),
                'variants' => $variants,
                'warranty' => $product->warranty_information,
                'rating_cache' => 5.0,
                'review_count' => $product->reviews->count(),
            ],
            'reviews' => $product->reviews->map(fn ($r) => [
                'id' => $r->id,
                'user_name' => $r->user?->name ?? 'Verified Member',
                'rating' => $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'is_verified' => $r->is_verified_purchase,
                'created_at' => $r->created_at->format('M d, Y'),
            ]),
            'relatedProducts' => $relatedProducts,
            'canReview' => $canReview,
        ]);
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $product = Product::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['required', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $isVerified = OrderItem::whereHas('order', function ($o) use ($user) {
            $o->where('user_id', $user->id)->where('fulfillment_status', Order::FULFILLMENT_DELIVERED);
        })->whereHas('variant', function ($v) use ($product) {
            $v->where('product_id', $product->id);
        })->exists();

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'comment' => $validated['comment'],
            'is_verified_purchase' => $isVerified,
            'is_approved' => true,
        ]);

        return redirect()->back()->with('success', 'Thank you! Your review has been submitted.');
    }
}
