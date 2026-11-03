<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\Cart\CartService;
use App\Services\Pricing\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $currency = $request->session()->get('currency', 'USD');
        $exchangeRate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        // Fast static translation cache in worker RAM
        static $translationCache = [];
        if (!isset($translationCache[$locale])) {
            $translationPath = base_path("lang/{$locale}.json");
            $translationCache[$locale] = file_exists($translationPath)
                ? (json_decode((string) file_get_contents($translationPath), true) ?? [])
                : [];
        }
        $translations = $translationCache[$locale];

        // Cart summary (only needed for customer/public facing pages)
        $cartSummary = ['item_count' => 0, 'subtotal_usd' => '0.00', 'subtotal_khr' => 0];
        if (!$request->is('admin*')) {
            try {
                /** @var CartService $cartService */
                $cartService = app(CartService::class);
                $cart = $cartService->getOrCreateCart($request->user(), $request->cookie('dstore_cart_token'));
                $itemCount = $cart->items->sum('quantity');
                $subtotal = Money::zero();
                foreach ($cart->items as $item) {
                    $subtotal = $subtotal->add(Money::of($item->unit_price)->multiply($item->quantity));
                }
                $cartSummary = [
                    'item_count' => $itemCount,
                    'subtotal_usd' => Money::format($subtotal),
                    'subtotal_khr' => Money::toKhr($subtotal, $exchangeRate),
                ];
            } catch (\Throwable $e) {
                // Cart loading should never break global page rendering
            }
        }

        $user = $request->user();

        $adminBadges = null;
        if ($request->is('admin*') && $user && $user->roles->isNotEmpty()) {
            try {
                $adminBadges = Cache::remember('admin_nav_badges', 15, function () {
                    return [
                        'pending_orders' => \App\Models\Order::whereIn('fulfillment_status', [
                            \App\Models\Order::FULFILLMENT_PENDING,
                            \App\Models\Order::FULFILLMENT_CONFIRMED,
                            \App\Models\Order::FULFILLMENT_PROCESSING,
                        ])->count(),
                        'low_stock' => \App\Models\Inventory::whereRaw('(on_hand_quantity - reserved_quantity) <= 5')->count(),
                        'pending_refunds' => \App\Models\Refund::where('status', 'requested')->count(),
                    ];
                });
            } catch (\Throwable $e) {
                // Graceful fallback if database schema is initializing
                $adminBadges = null;
            }
        }

        return [
            ...parent::share($request),
            'adminBadges' => $adminBadges,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray(),
                    'is_staff' => $user->roles->isNotEmpty(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
            'cart' => $cartSummary,
            'localization' => [
                'locale' => $locale,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'translations' => $translations,
            ],
            'settings' => [
                'store_name' => Setting::get('store_name', 'D STORE'),
                'store_phone' => Setting::get('store_phone', '+855 23 888 999'),
                'store_email' => Setting::get('store_email', 'contact@dstore.com.kh'),
                'store_address' => Setting::get('store_address_en', 'Norodom Blvd, Phnom Penh, Cambodia'),
            ],
        ];
    }
}
