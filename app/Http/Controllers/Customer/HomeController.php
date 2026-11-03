<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Services\Pricing\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        // Active hero banners
        $banners = Banner::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('display_order')
            ->get()
            ->map(fn (Banner $b) => [
                'id' => $b->id,
                'title_en' => $b->title['en'] ?? (is_array($b->title) ? reset($b->title) : (string) $b->title),
                'title_km' => $b->title['km'] ?? $b->title['en'] ?? '',
                'subtitle_en' => $b->subtitle['en'] ?? '',
                'subtitle_km' => $b->subtitle['km'] ?? '',
                'link_url' => $b->link_url,
                'image_url' => $b->image_url,
            ]);

        // Featured top-level categories
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('display_order')
            ->take(8)
            ->get()
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'name_en' => $cat->name['en'] ?? (is_array($cat->name) ? reset($cat->name) : (string) $cat->name),
                'name_km' => $cat->name['km'] ?? $cat->name['en'] ?? '',
                'slug' => $cat->slug,
                'image_url' => $cat->image_url ?? '/images/placeholder.svg',
                'description_en' => $cat->description['en'] ?? '',
                'description_km' => $cat->description['km'] ?? '',
            ]);

        // Trending / Featured products
        $trendingProducts = Product::where('status', 'active')
            ->where('is_featured', true)
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'images', 'variants.inventory'])
            ->take(8)
            ->get()
            ->map(function (Product $product) use ($rate) {
                $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
                $minPrice = $product->variants->min('price') ?? '0.0000';
                $compareAt = $product->variants->first()?->compare_at_price;
                $totalStock = $product->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);

                return [
                    'id' => $product->id,
                    'name_en' => $product->name['en'] ?? (is_array($product->name) ? reset($product->name) : (string) $product->name),
                    'name_km' => $product->name['km'] ?? $product->name['en'] ?? '',
                    'slug' => $product->slug,
                    'brand' => $product->brand?->name,
                    'category' => $product->category ? [
                        'name_en' => $product->category->name['en'] ?? '',
                        'name_km' => $product->category->name['km'] ?? '',
                        'slug' => $product->category->slug,
                    ] : null,
                    'base_price' => Money::format($minPrice),
                    'base_price_khr' => Money::toKhr($minPrice, $rate),
                    'compare_at_price' => $compareAt ? Money::format($compareAt) : null,
                    'primary_image' => $primaryImage?->image_url ?? '/images/placeholder.svg',
                    'rating_cache' => 5.0,
                    'review_count' => $product->reviews()->count(),
                    'in_stock' => $totalStock > 0,
                    'total_stock' => $totalStock,
                ];
            });

        // New Arrivals
        $newArrivals = Product::where('status', 'active')
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'images', 'variants.inventory'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function (Product $product) use ($rate) {
                $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
                $minPrice = $product->variants->min('price') ?? '0.0000';
                $compareAt = $product->variants->first()?->compare_at_price;
                $totalStock = $product->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);

                return [
                    'id' => $product->id,
                    'name_en' => $product->name['en'] ?? (is_array($product->name) ? reset($product->name) : (string) $product->name),
                    'name_km' => $product->name['km'] ?? $product->name['en'] ?? '',
                    'slug' => $product->slug,
                    'brand' => $product->brand?->name,
                    'base_price' => Money::format($minPrice),
                    'base_price_khr' => Money::toKhr($minPrice, $rate),
                    'compare_at_price' => $compareAt ? Money::format($compareAt) : null,
                    'primary_image' => $primaryImage?->image_url ?? '/images/placeholder.svg',
                    'rating_cache' => 5.0,
                    'review_count' => $product->reviews()->count(),
                    'in_stock' => $totalStock > 0,
                ];
            });

        // Approved reviews
        $reviews = Review::where('is_approved', true)
            ->with(['user:id,name', 'product:id,name,slug'])
            ->latest()
            ->take(4)
            ->get()
            ->map(fn (Review $r) => [
                'id' => $r->id,
                'user_name' => $r->user?->name ?? 'Verified Member',
                'product_title' => $r->product?->getLocalizedName() ?? 'Luxury Selection',
                'rating' => $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'is_verified' => $r->is_verified_purchase,
                'created_at' => $r->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Customer/Home', [
            'banners' => $banners,
            'categories' => $categories,
            'trendingProducts' => $trendingProducts,
            'newArrivals' => $newArrivals,
            'reviews' => $reviews,
        ]);
    }
}
