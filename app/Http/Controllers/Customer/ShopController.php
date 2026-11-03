<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Pricing\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        $query = Product::where('status', 'active')
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'images', 'variants.inventory']);

        // Search term
        if ($search = $request->query('q')) {
            $searchTerm = '%' . trim((string) $search) . '%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('slug', 'like', $searchTerm)
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $searchTerm))
                    ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $searchTerm));
            });
        }

        // Category filter
        if ($categorySlug = $request->query('category')) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $categoryIds = Category::where('id', $category->id)
                    ->orWhere('parent_id', $category->id)
                    ->pluck('id');
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // Brand filter
        if ($brandSlug = $request->query('brand')) {
            $query->whereHas('brand', fn ($b) => $b->where('slug', $brandSlug));
        }

        // Price range
        if ($minPrice = $request->query('min_price')) {
            $query->whereHas('variants', fn ($v) => $v->where('price', '>=', (float) $minPrice));
        }
        if ($maxPrice = $request->query('max_price')) {
            $query->whereHas('variants', fn ($v) => $v->where('price', '<=', (float) $maxPrice));
        }

        // Stock availability
        if ($request->boolean('in_stock')) {
            $query->whereHas('variants.inventory', function ($inv) {
                $inv->whereRaw('on_hand_quantity - reserved_quantity > 0');
            });
        }

        // Sort allowlist
        $sort = $request->query('sort', 'popular');
        match ($sort) {
            'newest' => $query->latest(),
            'price_asc' => $query->orderBy(
                \App\Models\ProductVariant::select('price')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderBy('price', 'asc')
                    ->limit(1),
                'asc'
            ),
            'price_desc' => $query->orderBy(
                \App\Models\ProductVariant::select('price')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderBy('price', 'desc')
                    ->limit(1),
                'desc'
            ),
            default => $query->orderBy('is_featured', 'desc')->latest(),
        };

        $perPage = 12;
        $paginator = $query->paginate($perPage)->withQueryString();

        $products = $paginator->through(function (Product $product) use ($rate) {
            $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
            $minPrice = $product->variants->min('price') ?? '0.0000';
            $compareAt = $product->variants->first()?->compare_at_price;
            $totalStock = $product->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);

            return [
                'id' => $product->id,
                'name_en' => $product->name['en'] ?? (is_array($product->name) ? reset($product->name) : (string) $product->name),
                'name_km' => $product->name['km'] ?? $product->name['en'] ?? '',
                'slug' => $product->slug,
                'brand' => $product->brand ? ['name' => $product->brand->name, 'slug' => $product->brand->slug] : null,
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

        // Filter metadata
        $allCategories = Category::where('is_active', true)
            ->withCount(['products' => fn ($p) => $p->where('status', 'active')])
            ->orderBy('display_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'parent_id' => $c->parent_id,
                'name_en' => $c->name['en'] ?? (is_array($c->name) ? reset($c->name) : (string) $c->name),
                'name_km' => $c->name['km'] ?? $c->name['en'] ?? '',
                'slug' => $c->slug,
                'products_count' => $c->products_count,
            ]);

        $allBrands = Brand::where('is_active', true)
            ->withCount(['products' => fn ($p) => $p->where('status', 'active')])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'products_count']);

        return Inertia::render('Customer/Shop', [
            'products' => $products,
            'categories' => $allCategories,
            'brands' => $allBrands,
            'filters' => [
                'q' => $request->query('q', ''),
                'category' => $request->query('category', ''),
                'brand' => $request->query('brand', ''),
                'min_price' => $request->query('min_price', ''),
                'max_price' => $request->query('max_price', ''),
                'in_stock' => $request->boolean('in_stock'),
                'sort' => $sort,
            ],
        ]);
    }
}
