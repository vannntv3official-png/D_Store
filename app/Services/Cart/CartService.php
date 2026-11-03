<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Pricing\PricingPipelineService;
use Exception;
use Illuminate\Support\Str;

class CartService
{
    public function __construct(
        protected PricingPipelineService $pricingPipeline
    ) {}

    public function getOrCreateCart(?User $user, ?string $sessionToken = null): Cart
    {
        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();
            if ($cart) {
                return $cart;
            }
        }

        if ($sessionToken) {
            $cart = Cart::where('session_token', $sessionToken)->first();
            if ($cart) {
                if ($user && !$cart->user_id) {
                    $cart->user_id = $user->id;
                    $cart->save();
                }
                return $cart;
            }
        }

        $token = $sessionToken ?: Str::random(40);
        return Cart::create([
            'user_id' => $user?->id,
            'session_token' => $token,
            'currency' => 'USD',
            'expires_at' => now()->addDays(14),
        ]);
    }

    public function addItem(Cart $cart, ProductVariant $variant, int $quantity = 1): CartItem
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (!$variant->is_active || !$variant->product || $variant->product->status !== 'active') {
            throw new Exception('Product or variant is currently unavailable.');
        }

        $effectivePrice = $this->pricingPipeline->resolveEffectivePrice($variant);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->unit_price_snapshot = $effectivePrice->getAmount();
            $item->save();
            return $item;
        }

        return CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price_snapshot' => $effectivePrice->getAmount(),
        ]);
    }

    public function updateQuantity(Cart $cart, int $itemId, int $quantity): ?CartItem
    {
        $item = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->first();
        if (!$item) {
            return null;
        }

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        $item->quantity = $quantity;
        $item->save();
        return $item;
    }

    public function removeItem(Cart $cart, int $itemId): void
    {
        CartItem::where('cart_id', $cart->id)->where('id', $itemId)->delete();
    }

    public function applyCoupon(Cart $cart, string $code): bool
    {
        $normalizedCode = strtoupper(trim($code));
        $coupon = Coupon::where('code', $normalizedCode)->first();

        if (!$coupon || !$coupon->isValidNow()) {
            return false;
        }

        $cart->coupon_id = $coupon->id;
        $cart->save();
        return true;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->coupon_id = null;
        $cart->save();
    }

    public function setShippingMethod(Cart $cart, int $shippingMethodId): void
    {
        $method = ShippingMethod::find($shippingMethodId);
        if ($method && $method->is_active) {
            $cart->shipping_method_id = $method->id;
            $cart->save();
        }
    }

    public function mergeGuestCart(Cart $guestCart, User $user): Cart
    {
        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['session_token' => Str::random(40), 'currency' => 'USD']
        );

        foreach ($guestCart->items as $guestItem) {
            $existing = CartItem::where('cart_id', $userCart->id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existing) {
                $existing->quantity += $guestItem->quantity;
                $existing->save();
            } else {
                $guestItem->cart_id = $userCart->id;
                $guestItem->save();
            }
        }

        if ($guestCart->coupon_id && !$userCart->coupon_id) {
            $userCart->coupon_id = $guestCart->coupon_id;
            $userCart->save();
        }

        $guestCart->delete();
        return $userCart;
    }
}
