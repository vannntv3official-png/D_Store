<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Reward;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Pricing\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyAdminController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService,
    ) {}

    public function index(Request $request): Response
    {
        $query = LoyaltyAccount::with('user:id,name,email,phone');

        if ($search = $request->query('q')) {
            $term = '%' . trim((string) $search) . '%';
            $query->whereHas('user', function ($u) use ($term) {
                $u->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        $accounts = $query->paginate(15)->through(fn ($acc) => [
            'id' => $acc->id,
            'user_id' => $acc->user_id,
            'customer_name' => $acc->user?->name ?? 'D STORE Member',
            'customer_phone' => $acc->user?->phone,
            'customer_email' => $acc->user?->email,
            'spendable_balance' => $acc->spendable_points,
            'current_balance' => $acc->current_points_balance,
            'lifetime_earned' => $acc->lifetime_points_earned,
            'points_debt' => $acc->points_debt,
            'updated_at' => $acc->updated_at->format('Y-m-d H:i'),
        ]);

        $rewards = Reward::orderBy('points_cost')->get()->map(fn ($r) => [
            'id' => $r->id,
            'name_en' => $r->name['en'] ?? (is_array($r->name) ? reset($r->name) : (string) $r->name),
            'name_km' => $r->name['km'] ?? $r->name['en'] ?? '',
            'points_cost' => $r->points_cost,
            'discount_type' => $r->discount_type,
            'discount_value' => Money::format($r->discount_value),
            'min_spend_amount' => $r->min_spend_amount ? Money::format($r->min_spend_amount) : 'None',
            'is_active' => (bool) $r->is_active,
        ]);

        return Inertia::render('Admin/Loyalty/Index', [
            'accounts' => $accounts,
            'rewards' => $rewards,
            'filters' => ['q' => $request->query('q', '')],
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:loyalty_accounts,id'],
            'points' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $account = LoyaltyAccount::with('user')->findOrFail($validated['account_id']);

        try {
            $newBalance = max(0, $account->current_points_balance + (int) $validated['points']);
            $account->update(['current_points_balance' => $newBalance]);

            LoyaltyTransaction::create([
                'loyalty_account_id' => $account->id,
                'points_change' => (int) $validated['points'],
                'resulting_balance' => $newBalance,
                'resulting_debt' => $account->points_debt,
                'event_type' => 'adjustment',
                'event_key' => 'adj_' . $account->id . '_' . time(),
                'actor_id' => $request->user()->id,
                'notes' => $validated['reason'],
            ]);

            return redirect()->back()->with('success', 'Customer loyalty balance adjusted.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['loyalty' => $e->getMessage()]);
        }
    }

    public function storeReward(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'discount_type' => ['required', 'in:fixed,percentage,free_shipping'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_spend_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        Reward::create([
            'name' => [
                'en' => $validated['name_en'],
                'km' => $validated['name_km'],
            ],
            'description' => [
                'en' => $validated['description_en'] ?? '',
                'km' => $validated['description_km'] ?? '',
            ],
            'points_cost' => (int) $validated['points_cost'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => Money::format((string) $validated['discount_value']),
            'min_spend_amount' => isset($validated['min_spend_amount']) ? Money::format((string) $validated['min_spend_amount']) : null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Reward added to catalog.');
    }
}
