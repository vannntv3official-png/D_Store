<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Pricing\Money;
use App\Services\Pricing\PricingPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PricingPipelineService $pricingService,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);
        $user = $request->user();
        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($user, $cartToken);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Your shopping bag is empty.');
        }

        $pricingResult = $this->pricingService->calculateAuthoritativeCartPricing($cart);

        // Fetch shipping methods
        $shippingMethods = ShippingMethod::where('is_active', true)
            ->with('zone')
            ->orderBy('rate')
            ->get()
            ->map(fn ($sm) => [
                'id' => $sm->id,
                'zone_id' => $sm->shipping_zone_id,
                'name_en' => $sm->name['en'] ?? (is_array($sm->name) ? reset($sm->name) : (string) $sm->name),
                'name_km' => $sm->name['km'] ?? $sm->name['en'] ?? '',
                'estimated_days' => $sm->estimated_delivery_days ?? '1-3 days',
                'price' => Money::format($sm->rate),
                'price_khr' => Money::toKhr($sm->rate, $rate),
            ]);

        // Shipping zones & locations for address dropdowns
        $zones = ShippingZone::where('is_active', true)
            ->with(['locations' => fn ($q) => $q->orderBy('province_id')->orderBy('district_id')])
            ->get()
            ->map(fn ($z) => [
                'id' => $z->id,
                'name_en' => $z->name['en'] ?? (is_array($z->name) ? reset($z->name) : (string) $z->name),
                'name_km' => $z->name['km'] ?? $z->name['en'] ?? '',
                'code' => $z->code,
                'locations' => $z->locations->map(fn ($l) => [
                    'province' => $l->province_id,
                    'district' => $l->district_id,
                ]),
            ]);

        // Customer saved addresses
        $savedAddresses = $user ? $user->addresses()->get() : [];

        // Customer loyalty balance
        $loyaltyBalance = $user?->loyaltyAccount?->spendable_points ?? 0;

        return Inertia::render('Customer/Checkout', [
            'cartItems' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'name_en' => $item->variant->product->name['en'] ?? (is_array($item->variant->product->name) ? reset($item->variant->product->name) : (string) $item->variant->product->name),
                'name_km' => $item->variant->product->name['km'] ?? $item->variant->product->name['en'] ?? '',
                'sku' => $item->variant->sku,
                'quantity' => $item->quantity,
                'price' => Money::format($item->unit_price),
                'price_khr' => Money::toKhr($item->unit_price, $rate),
                'subtotal' => Money::format(Money::multiply($item->unit_price, $item->quantity)),
                'subtotal_khr' => Money::toKhr(Money::multiply($item->unit_price, $item->quantity), $rate),
            ]),
            'pricing' => [
                'subtotal' => Money::format($pricingResult->subtotal),
                'subtotal_khr' => Money::toKhr($pricingResult->subtotal, $rate),
                'discount' => Money::format($pricingResult->discountTotal),
                'discount_khr' => Money::toKhr($pricingResult->discountTotal, $rate),
                'total' => Money::format($pricingResult->total),
                'total_khr' => Money::toKhr($pricingResult->total, $rate),
                'coupon_code' => $cart->coupon?->code,
            ],
            'shippingMethods' => $shippingMethods,
            'shippingZones' => $zones,
            'savedAddresses' => $savedAddresses,
            'loyaltyBalance' => $loyaltyBalance,
            'initialIdempotencyKey' => Str::uuid()->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:500'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'payment_method' => ['required', 'in:cod,bank_transfer,khqr_sandbox,aba_payway_sandbox'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($user, $cartToken);

        $order = $this->checkoutService->processCheckout(
            cart: $cart,
            user: $user,
            customerData: [
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'province' => $validated['province'],
                'district' => $validated['district'],
                'commune' => $validated['commune'] ?? null,
                'street_address' => $validated['street_address'],
                'delivery_notes' => $validated['delivery_notes'] ?? null,
            ],
            shippingMethodId: (int) $validated['shipping_method_id'],
            paymentMethod: $validated['payment_method'],
            idempotencyKey: $validated['idempotency_key'],
            pointsToRedeem: (int) ($validated['points_to_redeem'] ?? 0),
        );

        return redirect()->route('orders.confirmed', ['orderNumber' => $order->order_number])
            ->with('success', 'Order created successfully!')
            ->with('guest_token', $order->guest_token);
    }
}
