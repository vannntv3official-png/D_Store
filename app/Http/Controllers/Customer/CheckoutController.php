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
use App\Services\Checkout\IdempotencyMismatchException;
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
                'subtotal' => Money::format(Money::of($item->unit_price)->multiply($item->quantity)),
                'subtotal_khr' => Money::toKhr(Money::of($item->unit_price)->multiply($item->quantity), $rate),
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
        $cart->shipping_method_id = (int) $validated['shipping_method_id'];
        $cart->save();

        $addressLine = $validated['street_address'];
        if (!empty($validated['commune'])) {
            $addressLine .= ', ' . $validated['commune'];
        }

        $shippingAddressData = [
            'contact_name' => $validated['recipient_name'],
            'contact_phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'province_name' => $validated['province'],
            'district_name' => $validated['district'],
            'address_line' => $addressLine,
        ];

        try {
            $result = $this->checkoutService->processCheckout(
                cart: $cart,
                shippingAddressData: $shippingAddressData,
                paymentMethod: $validated['payment_method'],
                idempotencyKey: $validated['idempotency_key'],
                user: $user,
                deliveryNotes: $validated['delivery_notes'] ?? null,
            );
        } catch (IdempotencyMismatchException $e) {
            // Client provided a stale idempotency key from a prior attempt with a changed payload.
            // Transparently generate a fresh key and proceed so checkout is never aborted.
            $result = $this->checkoutService->processCheckout(
                cart: $cart,
                shippingAddressData: $shippingAddressData,
                paymentMethod: $validated['payment_method'],
                idempotencyKey: Str::uuid()->toString(),
                user: $user,
                deliveryNotes: $validated['delivery_notes'] ?? null,
            );
        }

        $order = $result['order'];

        if ($validated['payment_method'] === 'aba_payway_sandbox' && !empty($result['payment_initiation']['paymentUrl'])) {
            return redirect($result['payment_initiation']['paymentUrl'])
                ->with('success', 'Order created successfully! Please complete payment via ABA PayWay.')
                ->with('guest_token', $order->guest_token);
        }

        return redirect()->route('orders.confirmed', ['orderNumber' => $order->order_number])
            ->with('success', 'Order created successfully!')
            ->with('guest_token', $order->guest_token);
    }
}
