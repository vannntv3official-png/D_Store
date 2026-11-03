<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryAdminController extends Controller
{
    public function index(): Response
    {
        $categories = Category::with('parent')
            ->withCount('products')
            ->orderBy('display_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name_en' => $c->name['en'] ?? (is_array($c->name) ? reset($c->name) : (string) $c->name),
                'name_km' => $c->name['km'] ?? $c->name['en'] ?? '',
                'slug' => $c->slug,
                'parent_id' => $c->parent_id,
                'parent_name' => $c->parent ? ($c->parent->name['en'] ?? '') : null,
                'display_order' => $c->display_order,
                'is_active' => (bool) $c->is_active,
                'products_count' => $c->products_count,
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'display_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
        ]);

        $slug = Str::slug($validated['name_en']);
        if (Category::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::lower(Str::random(4));
        }

        Category::create([
            'name' => [
                'en' => $validated['name_en'],
                'km' => $validated['name_km'],
            ],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'description' => [
                'en' => $validated['description_en'] ?? '',
                'km' => $validated['description_km'] ?? '',
            ],
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'display_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
        ]);

        // Prevent circular dependency
        if (!empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === $category->id) {
                return redirect()->back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
            }

            $descendantIds = $this->getAllDescendantIds($category);
            if (in_array((int) $validated['parent_id'], $descendantIds, true)) {
                return redirect()->back()->withErrors(['parent_id' => 'Cannot select a descendant category as parent.']);
            }
        }

        $category->update([
            'name' => [
                'en' => $validated['name_en'],
                'km' => $validated['name_km'],
            ],
            'parent_id' => $validated['parent_id'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'description' => [
                'en' => $validated['description_en'] ?? '',
                'km' => $validated['description_km'] ?? '',
            ],
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);
        Category::where('parent_id', $category->id)->update(['parent_id' => null]);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    protected function getAllDescendantIds(Category $category): array
    {
        $ids = [];
        $children = Category::where('parent_id', $category->id)->get();
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }
        return $ids;
    }
}
