<?php

namespace App\Services\Pricing;

use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;

class PricingPipelineService
{
    public function calculateAuthoritativeCartPricing(Cart $cart, ?Coupon $coupon = null, ?ShippingMethod $shippingMethod = null): PricingCalculationResult
    {
        return $this->calculate($cart, $coupon, $shippingMethod);
    }

    public function calculate(Cart $cart, ?Coupon $coupon = null, ?ShippingMethod $shippingMethod = null): PricingCalculationResult
    {
        $items = $cart->items()->with(['variant.product'])->get();
        $lineResults = [];
        $subtotal = Money::zero();
        $subtotalRatios = [];

        // 1. Calculate effective unit price and subtotal for each line item
        foreach ($items as $item) {
            $effectiveUnitPrice = $this->resolveEffectivePrice($item->variant);
            $lineSubtotal = $effectiveUnitPrice->multiply($item->quantity);

            $subtotal = $subtotal->add($lineSubtotal);
            $subtotalRatios[$item->id] = $lineSubtotal->toMinorUnits();

            $lineResults[$item->id] = [
                'item_id' => $item->id,
                'variant_id' => $item->product_variant_id,
                'sku' => $item->variant->sku,
                'product_name' => $item->variant->product->name,
                'unit_price' => $effectiveUnitPrice,
                'quantity' => $item->quantity,
                'line_subtotal' => $lineSubtotal,
                'allocated_discount' => Money::zero(),
                'line_total' => $lineSubtotal,
            ];
        }

        // 2. Evaluate Coupon Discount
        $discountTotal = Money::zero();
        $activeCoupon = $coupon ?: $cart->coupon;

        if ($activeCoupon && $activeCoupon->isValidNow()) {
            $minSpend = $activeCoupon->min_spend_amount ? Money::of($activeCoupon->min_spend_amount) : Money::zero();

            if ($subtotal->isGreaterThanOrEqual($minSpend)) {
                if ($activeCoupon->type === 'percentage') {
                    $rawDiscount = $subtotal->multiply((float) $activeCoupon->value / 100);
                    if ($activeCoupon->max_discount_amount) {
                        $cap = Money::of($activeCoupon->max_discount_amount);
                        $discountTotal = $rawDiscount->isGreaterThan($cap) ? $cap : $rawDiscount;
                    } else {
                        $discountTotal = $rawDiscount;
                    }
                } else { // Fixed
                    $fixedVal = Money::of($activeCoupon->value);
                    $discountTotal = $subtotal->isGreaterThan($fixedVal) ? $fixedVal : $subtotal;
                }
            }
        }

        // Clamp discount so it never exceeds subtotal
        if ($discountTotal->isGreaterThan($subtotal)) {
            $discountTotal = $subtotal;
        }

        // 3. Allocate Coupon Discount Pro-Rata across items with exact zero-penny discrepancy
        if ($discountTotal->isPositive() && count($subtotalRatios) > 0) {
            $allocatedDiscounts = $discountTotal->allocate(array_values($subtotalRatios));
            $idx = 0;
            foreach ($subtotalRatios as $itemId => $ratio) {
                $itemAllocDiscount = $allocatedDiscounts[$idx++];
                $lineResults[$itemId]['allocated_discount'] = $itemAllocDiscount;
                $lineResults[$itemId]['line_total'] = $lineResults[$itemId]['line_subtotal']->subtract($itemAllocDiscount);
            }
        }

        // 4. Tax Calculation (Configured rate, default 0.00)
        $taxRate = (float) Setting::get('tax_rate_percentage', 0.0);
        $taxTotal = $taxRate > 0 ? $subtotal->subtract($discountTotal)->multiply($taxRate / 100) : Money::zero();

        // 5. Shipping Calculation
        $shippingTotal = Money::zero();
        $selectedShipping = $shippingMethod ?: $cart->shippingMethod;

        if ($selectedShipping) {
            if ($selectedShipping->min_order_free_shipping && $subtotal->isGreaterThanOrEqual(Money::of($selectedShipping->min_order_free_shipping))) {
                $shippingTotal = Money::zero();
            } else {
                $shippingTotal = Money::of($selectedShipping->rate);
            }
        }

        // 6. Grand Total
        $grandTotal = $subtotal->subtract($discountTotal)->add($taxTotal)->add($shippingTotal);
        if ($grandTotal->isNegative()) {
            $grandTotal = Money::zero();
        }

        // 7. Exchange Rate & KHR Settlement
        $exchangeRate = (float) Setting::get('usd_khr_rate', 4100.0);
        $settlementKhr = $grandTotal->toKhr($exchangeRate);

        return new PricingCalculationResult(
            subtotal: $subtotal,
            discountTotal: $discountTotal,
            taxTotal: $taxTotal,
            shippingTotal: $shippingTotal,
            grandTotal: $grandTotal,
            settlementKhr: $settlementKhr,
            exchangeRate: $exchangeRate,
            coupon: $activeCoupon,
            shippingMethod: $selectedShipping,
            lineItems: array_values($lineResults)
        );
    }

    public function resolveEffectivePrice(ProductVariant $variant): Money
    {
        $basePrice = Money::of($variant->price);

        // Check for active campaigns or flash sales
        $activeCampaignProduct = Campaign::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereHas('campaignProducts', function ($q) use ($variant) {
                $q->where('product_id', $variant->product_id);
            })
            ->with(['campaignProducts' => fn ($q) => $q->where('product_id', $variant->product_id)])
            ->first();

        if ($activeCampaignProduct && $activeCampaignProduct->campaignProducts->isNotEmpty()) {
            $pivot = $activeCampaignProduct->campaignProducts->first();
            if ($pivot->promotional_price && (float) $pivot->promotional_price > 0) {
                return Money::of($pivot->promotional_price);
            }

            if ($activeCampaignProduct->discount_type === 'percentage') {
                $discount = $basePrice->multiply((float) $activeCampaignProduct->discount_value / 100);
                return $basePrice->subtract($discount);
            }

            if ($activeCampaignProduct->discount_type === 'fixed') {
                $fixedDiscount = Money::of($activeCampaignProduct->discount_value);
                return $basePrice->isGreaterThan($fixedDiscount) ? $basePrice->subtract($fixedDiscount) : Money::zero();
            }
        }

        return $basePrice;
    }
}
