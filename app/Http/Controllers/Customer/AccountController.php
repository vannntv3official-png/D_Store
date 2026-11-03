<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\LoyaltyAccount;
use App\Models\Product;
use App\Models\Reward;
use App\Models\Setting;
use App\Models\Wishlist;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Pricing\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function profile(Request $request): Response
    {
        return Inertia::render('Customer/Account/Profile', [
            'user' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone,' . $user->id],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'] ?? null;
        $user->phone = $validated['phone'];

        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function addresses(Request $request): Response
    {
        $addresses = $request->user()->addresses()
            ->orderBy('is_default_shipping', 'desc')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'recipient_name' => $a->contact_name,
                'phone' => $a->contact_phone,
                'province' => $a->province_name,
                'district' => $a->district_name,
                'commune' => $a->commune,
                'street_address' => $a->address_line,
                'is_default' => (bool) $a->is_default_shipping,
            ]);

        return Inertia::render('Customer/Account/Addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'province' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ]);

        $user = $request->user();

        if (!empty($validated['is_default'])) {
            $user->addresses()->update(['is_default_shipping' => false]);
        }

        $user->addresses()->create([
            'contact_name' => $validated['recipient_name'],
            'contact_phone' => $validated['phone'],
            'province_id' => \Illuminate\Support\Str::slug($validated['province']),
            'province_name' => $validated['province'],
            'district_id' => \Illuminate\Support\Str::slug($validated['district']),
            'district_name' => $validated['district'],
            'commune' => $validated['commune'] ?? null,
            'address_line' => $validated['street_address'],
            'is_default_shipping' => $validated['is_default'] ?? false,
            'is_default_billing' => $validated['is_default'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Address saved successfully.');
    }

    public function deleteAddress(Request $request, int $id): RedirectResponse
    {
        $request->user()->addresses()->findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Address deleted.');
    }

    public function loyalty(Request $request, LoyaltyService $loyaltyService): Response
    {
        $user = $request->user();
        $account = $loyaltyService->getOrCreateAccount($user);

        $transactions = $account->transactions()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'points' => $t->points_change,
                'type' => $t->event_type,
                'description' => $t->notes ?? $t->event_type,
                'created_at' => $t->created_at->format('M d, Y H:i'),
            ]);

        $rewards = Reward::where('is_active', true)
            ->orderBy('points_cost')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name_en' => $r->name['en'] ?? (is_array($r->name) ? reset($r->name) : (string) $r->name),
                'name_km' => $r->name['km'] ?? $r->name['en'] ?? '',
                'description_en' => $r->description['en'] ?? '',
                'description_km' => $r->description['km'] ?? '',
                'points_required' => $r->points_cost,
                'reward_type' => $r->discount_type,
                'reward_value' => Money::format($r->discount_value),
            ]);

        return Inertia::render('Customer/Account/Loyalty', [
            'balance' => $account->spendable_points,
            'currentBalance' => $account->current_points_balance,
            'lifetimeEarned' => $account->lifetime_points_earned,
            'pointsDebt' => $account->points_debt,
            'transactions' => $transactions,
            'rewards' => $rewards,
        ]);
    }

    public function redeemReward(Request $request, int $rewardId, LoyaltyService $loyaltyService): RedirectResponse
    {
        $user = $request->user();
        $reward = Reward::where('is_active', true)->findOrFail($rewardId);

        try {
            $loyaltyService->redeemReward($user, $reward);
            return redirect()->back()->with('success', 'Reward redeemed successfully! Your discount coupon has been generated.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['loyalty' => $e->getMessage()]);
        }
    }

    public function wishlist(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);
        $user = $request->user();
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

        $products = $wishlist->products()
            ->where('status', 'active')
            ->with(['brand:id,name', 'images', 'variants.inventory'])
            ->get()
            ->map(function ($p) use ($rate) {
                $primaryImage = $p->images->first()?->image_url ?? '/images/placeholder.svg';
                $stock = $p->variants->sum(fn ($v) => $v->inventory?->available_quantity ?? 0);
                $minPrice = $p->variants->min('price') ?? '0.0000';

                return [
                    'id' => $p->id,
                    'name_en' => $p->name['en'] ?? (is_array($p->name) ? reset($p->name) : (string) $p->name),
                    'name_km' => $p->name['km'] ?? $p->name['en'] ?? '',
                    'slug' => $p->slug,
                    'brand' => $p->brand?->name,
                    'base_price' => Money::format($minPrice),
                    'base_price_khr' => Money::toKhr($minPrice, $rate),
                    'primary_image' => $primaryImage,
                    'in_stock' => $stock > 0,
                ];
            });

        return Inertia::render('Customer/Account/Wishlist', [
            'products' => $products,
        ]);
    }

    public function toggleWishlist(Request $request, int $productId): RedirectResponse
    {
        $user = $request->user();
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

        if ($wishlist->products()->where('products.id', $productId)->exists()) {
            $wishlist->products()->detach($productId);
            return redirect()->back()->with('success', 'Removed from wishlist.');
        }

        $wishlist->products()->attach($productId);
        return redirect()->back()->with('success', 'Added to wishlist.');
    }
}
