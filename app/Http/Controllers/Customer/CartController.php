<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\Cart\CartService;
use App\Services\Pricing\Money;
use App\Services\Pricing\PricingPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected PricingPipelineService $pricingService,
    ) {}

    public function index(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);
        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($request->user(), $cartToken);

        $pricingResult = $this->pricingService->calculateAuthoritativeCartPricing($cart);

        $items = $cart->items->map(function ($item) use ($rate) {
            $variant = $item->variant;
            $product = $variant->product;
            $primaryImg = $product->images->first()?->image_url ?? '/images/placeholder.svg';
            $available = $variant->inventory?->available_quantity ?? 0;

            return [
                'id' => $item->id,
                'variant_id' => $variant->id,
                'product_name_en' => $product->name['en'] ?? (is_array($product->name) ? reset($product->name) : (string) $product->name),
                'product_name_km' => $product->name['km'] ?? $product->name['en'] ?? '',
                'product_slug' => $product->slug,
                'variant_sku' => $variant->sku,
                'image_url' => $primaryImg,
                'unit_price' => Money::format($item->unit_price),
                'unit_price_khr' => Money::toKhr($item->unit_price, $rate),
                'quantity' => $item->quantity,
                'available_stock' => $available,
                'subtotal' => Money::format(Money::multiply($item->unit_price, $item->quantity)),
                'subtotal_khr' => Money::toKhr(Money::multiply($item->unit_price, $item->quantity), $rate),
            ];
        });

        return Inertia::render('Customer/Cart', [
            'items' => $items,
            'summary' => [
                'subtotal' => Money::format($pricingResult->subtotal),
                'subtotal_khr' => Money::toKhr($pricingResult->subtotal, $rate),
                'discount' => Money::format($pricingResult->discountTotal),
                'discount_khr' => Money::toKhr($pricingResult->discountTotal, $rate),
                'total' => Money::format($pricingResult->total),
                'total_khr' => Money::toKhr($pricingResult->total, $rate),
                'coupon_code' => $cart->coupon?->code,
                'coupon_discount' => $cart->coupon ? ($cart->coupon->isPercentage() ? "{$cart->coupon->discount_value}%" : "\${$cart->coupon->discount_value}") : null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::with('inventory')->findOrFail($validated['variant_id']);
        $available = $variant->inventory?->available_quantity ?? 0;

        if ($available < $validated['quantity']) {
            return redirect()->back()->withErrors([
                'quantity' => "Insufficient stock. Only {$available} units available.",
            ]);
        }

        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($request->user(), $cartToken);
        $this->cartService->addItem($cart, $variant, (int) $validated['quantity']);

        $response = redirect()->back()->with('success', 'Item added to your shopping bag.');

        if (!$request->user() && (!$cartToken || $cartToken !== $cart->guest_token)) {
            $response->cookie('dstore_cart_token', $cart->guest_token, 60 * 24 * 30);
        }

        return $response;
    }

    public function update(Request $request, int $itemId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($request->user(), $cartToken);

        $item = $cart->items()->findOrFail($itemId);
        $available = $item->variant->inventory?->available_quantity ?? 0;

        if ($available < $validated['quantity']) {
            return redirect()->back()->withErrors([
                'quantity' => "Insufficient stock. Only {$available} units available.",
            ]);
        }

        $this->cartService->updateItemQuantity($cart, $itemId, (int) $validated['quantity']);

        return redirect()->back()->with('success', 'Shopping bag updated.');
    }

    public function destroy(Request $request, int $itemId): RedirectResponse
    {
        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($request->user(), $cartToken);

        $this->cartService->removeItem($cart, $itemId);

        return redirect()->back()->with('success', 'Item removed from bag.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($validated['code'])))->first();

        if (!$coupon || !$coupon->isValid()) {
            return redirect()->back()->withErrors([
                'coupon' => 'Invalid or expired promo code.',
            ]);
        }

        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($request->user(), $cartToken);

        $cart->update(['coupon_id' => $coupon->id]);

        return redirect()->back()->with('success', 'Promo code applied successfully.');
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $cartToken = $request->cookie('dstore_cart_token');
        $cart = $this->cartService->getOrCreateCart($request->user(), $cartToken);

        $cart->update(['coupon_id' => null]);

        return redirect()->back()->with('success', 'Promo code removed.');
    }
}
