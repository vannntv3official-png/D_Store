<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Pricing\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with(['brand:id,name', 'category:id,name', 'variants.inventory']);

        if ($search = $request->query('q')) {
            $term = '%' . trim((string) $search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        if ($catId = $request->query('category_id')) {
            $query->where('category_id', $catId);
        }

        $products = $query->latest()->paginate(15)->through(function (Product $p) {
            $stock = $p->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);
            $minPrice = $p->variants->min('price') ?? '0.0000';
            return [
                'id' => $p->id,
                'name_en' => $p->name['en'] ?? (is_array($p->name) ? reset($p->name) : (string) $p->name),
                'name_km' => $p->name['km'] ?? $p->name['en'] ?? '',
                'slug' => $p->slug,
                'brand' => $p->brand?->name,
                'category' => $p->category ? ($p->category->name['en'] ?? '') : null,
                'base_price' => Money::format($minPrice),
                'stock' => $stock,
                'status' => $p->status,
                'is_featured' => (bool) $p->is_featured,
                'created_at' => $p->created_at->format('Y-m-d'),
            ];
        });

        $categories = Category::where('is_active', true)->get()->map(fn ($c) => [
            'id' => $c->id,
            'name_en' => $c->name['en'] ?? (is_array($c->name) ? reset($c->name) : (string) $c->name),
        ]);
        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'filters' => [
                'q' => $request->query('q', ''),
                'category_id' => $request->query('category_id', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $categories = Category::where('is_active', true)->orderBy('display_order')->get()->map(fn ($c) => [
            'id' => $c->id,
            'name_en' => $c->name['en'] ?? (is_array($c->name) ? reset($c->name) : (string) $c->name),
            'name_km' => $c->name['km'] ?? $c->name['en'] ?? '',
        ]);
        $brands = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
            'status' => ['required', 'in:active,draft,archived'],
            'is_featured' => ['boolean'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'unique:product_variants,sku'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.initial_stock' => ['required', 'integer', 'min:0'],
            'variants.*.barcode' => ['nullable', 'string', 'max:64'],
        ]);

        DB::transaction(function () use ($validated) {
            $slug = Str::slug($validated['name_en']);
            if (Product::where('slug', $slug)->exists()) {
                $slug .= '-' . Str::lower(Str::random(5));
            }

            $product = Product::create([
                'category_id' => (int) $validated['category_id'],
                'brand_id' => $validated['brand_id'] ? (int) $validated['brand_id'] : null,
                'name' => [
                    'en' => $validated['name_en'],
                    'km' => $validated['name_km'],
                ],
                'slug' => $slug,
                'description' => [
                    'en' => $validated['description_en'] ?? '',
                    'km' => $validated['description_km'] ?? '',
                ],
                'status' => $validated['status'],
                'is_featured' => $validated['is_featured'] ?? false,
            ]);

            foreach ($validated['variants'] as $vData) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper($vData['sku']),
                    'barcode' => $vData['barcode'] ?? null,
                    'price' => Money::format((string) $vData['price']),
                    'is_active' => true,
                ]);

                Inventory::create([
                    'product_variant_id' => $variant->id,
                    'on_hand_quantity' => (int) $vData['initial_stock'],
                    'reserved_quantity' => 0,
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(int $id): Response
    {
        $product = Product::with(['category', 'brand', 'variants.inventory', 'images'])->findOrFail($id);
        $categories = Category::where('is_active', true)->orderBy('display_order')->get()->map(fn ($c) => [
            'id' => $c->id,
            'name_en' => $c->name['en'] ?? (is_array($c->name) ? reset($c->name) : (string) $c->name),
            'name_km' => $c->name['km'] ?? $c->name['en'] ?? '',
        ]);
        $brands = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Products/Form', [
            'product' => [
                'id' => $product->id,
                'name_en' => $product->name['en'] ?? '',
                'name_km' => $product->name['km'] ?? '',
                'slug' => $product->slug,
                'description_en' => $product->description['en'] ?? '',
                'description_km' => $product->description['km'] ?? '',
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'status' => $product->status,
                'is_featured' => (bool) $product->is_featured,
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => Money::format($v->price),
                    'barcode' => $v->barcode,
                    'stock' => $v->inventory?->on_hand_quantity ?? 0,
                ]),
                'images' => $product->images,
            ],
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
            'status' => ['required', 'in:active,draft,archived'],
            'is_featured' => ['boolean'],
        ]);

        $product->update([
            'category_id' => (int) $validated['category_id'],
            'brand_id' => $validated['brand_id'] ? (int) $validated['brand_id'] : null,
            'name' => [
                'en' => $validated['name_en'],
                'km' => $validated['name_km'],
            ],
            'description' => [
                'en' => $validated['description_en'] ?? '',
                'km' => $validated['description_km'] ?? '',
            ],
            'status' => $validated['status'],
            'is_featured' => $validated['is_featured'] ?? false,
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function exportCsv(): StreamedResponse
    {
        $products = Product::with(['brand', 'category', 'variants.inventory'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="dstore_products_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($products) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['ID', 'Slug', 'Name (EN)', 'Name (KM)', 'Brand', 'Category', 'Variants Count', 'Total Stock', 'Status']);

            foreach ($products as $p) {
                $stock = $p->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);
                $nameEn = $p->name['en'] ?? (is_array($p->name) ? reset($p->name) : (string) $p->name);
                $nameKm = $p->name['km'] ?? '';

                fputcsv($out, [
                    $p->id,
                    $this->sanitizeCsvField($p->slug),
                    $this->sanitizeCsvField($nameEn),
                    $this->sanitizeCsvField($nameKm),
                    $p->brand?->name ?? 'N/A',
                    $p->category ? ($p->category->name['en'] ?? '') : 'N/A',
                    $p->variants->count(),
                    $stock,
                    $p->status,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public static function sanitizeCsvField(string $field): string
    {
        $trimmed = ltrim($field);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r", '|'], true)) {
            return "'" . $field;
        }
        return $field;
    }
}
