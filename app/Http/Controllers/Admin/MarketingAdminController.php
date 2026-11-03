<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Coupon;
use App\Services\Pricing\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketingAdminController extends Controller
{
    public function index(): Response
    {
        $coupons = Coupon::latest()->get()->map(fn ($c) => [
            'id' => $c->id,
            'code' => $c->code,
            'type' => $c->discount_type,
            'value' => $c->isPercentage() ? "{$c->discount_value}%" : "\${$c->discount_value}",
            'min_spend' => $c->min_spend ? Money::format($c->min_spend) : 'None',
            'usage_count' => $c->usage_count,
            'usage_limit' => $c->usage_limit ?? 'Unlimited',
            'is_active' => $c->is_active,
            'expires_at' => $c->expires_at?->format('Y-m-d') ?? 'Never',
        ]);

        $banners = Banner::orderBy('display_order')->get()->map(fn ($b) => [
            'id' => $b->id,
            'title_en' => $b->title['en'] ?? (is_array($b->title) ? reset($b->title) : (string) $b->title),
            'title_km' => $b->title['km'] ?? $b->title['en'] ?? '',
            'subtitle_en' => $b->subtitle['en'] ?? '',
            'subtitle_km' => $b->subtitle['km'] ?? '',
            'link_url' => $b->link_url,
            'image_url' => $b->image_url,
            'display_order' => $b->display_order,
            'is_active' => (bool) $b->is_active,
        ]);
        $flashSales = Campaign::where('is_flash_sale', true)->with('products')->latest()->get();

        return Inertia::render('Admin/Marketing/Index', [
            'coupons' => $coupons,
            'banners' => $banners,
            'flashSales' => $flashSales,
        ]);
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        Coupon::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => [
                'en' => strtoupper(trim($validated['code'])),
                'km' => strtoupper(trim($validated['code'])),
            ],
            'type' => $validated['discount_type'],
            'value' => Money::format((string) $validated['discount_value']),
            'min_spend_amount' => isset($validated['min_spend']) ? Money::format((string) $validated['min_spend']) : null,
            'usage_limit_total' => $validated['usage_limit'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Coupon created successfully.');
    }

    public function toggleCoupon(int $id): RedirectResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);

        return redirect()->back()->with('success', 'Coupon status updated.');
    }

    public function storeBanner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['required', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'subtitle_km' => ['nullable', 'string', 'max:255'],
            'link_url' => ['required', 'string', 'max:500'],
            'image_url' => ['required', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        Banner::create([
            'title' => [
                'en' => $validated['title_en'],
                'km' => $validated['title_km'],
            ],
            'subtitle' => [
                'en' => $validated['subtitle_en'] ?? '',
                'km' => $validated['subtitle_km'] ?? '',
            ],
            'link_url' => $validated['link_url'],
            'image_url' => $validated['image_url'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Showcase banner added.');
    }

    public function updateBanner(Request $request, int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['required', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'subtitle_km' => ['nullable', 'string', 'max:255'],
            'link_url' => ['required', 'string', 'max:500'],
            'image_url' => ['required', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $banner->update([
            'title' => [
                'en' => $validated['title_en'],
                'km' => $validated['title_km'],
            ],
            'subtitle' => [
                'en' => $validated['subtitle_en'] ?? '',
                'km' => $validated['subtitle_km'] ?? '',
            ],
            'link_url' => $validated['link_url'],
            'image_url' => $validated['image_url'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Showcase banner updated successfully.');
    }

    public function toggleBanner(int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => !$banner->is_active]);

        return redirect()->back()->with('success', 'Showcase banner status updated.');
    }

    public function deleteBanner(int $id): RedirectResponse
    {
        Banner::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Banner removed.');
    }
}
