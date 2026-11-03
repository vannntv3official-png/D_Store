<?php

namespace App\Services\Pricing;

use App\Models\Coupon;
use App\Models\ShippingMethod;

class PricingCalculationResult
{
    /**
     * @param array<int, array<string, mixed>> $lineItems
     */
    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $discountTotal,
        public readonly Money $taxTotal,
        public readonly Money $shippingTotal,
        public readonly Money $grandTotal,
        public readonly Money $settlementKhr,
        public readonly float $exchangeRate,
        public readonly ?Coupon $coupon,
        public readonly ?ShippingMethod $shippingMethod,
        public readonly array $lineItems,
    ) {}

    public function __get(string $name): mixed
    {
        if ($name === 'total') {
            return $this->grandTotal;
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal->getAmount(),
            'subtotal_formatted' => $this->subtotal->format(),
            'discount_total' => $this->discountTotal->getAmount(),
            'discount_total_formatted' => $this->discountTotal->format(),
            'tax_total' => $this->taxTotal->getAmount(),
            'tax_total_formatted' => $this->taxTotal->format(),
            'shipping_total' => $this->shippingTotal->getAmount(),
            'shipping_total_formatted' => $this->shippingTotal->format(),
            'grand_total' => $this->grandTotal->getAmount(),
            'grand_total_formatted' => $this->grandTotal->format(),
            'settlement_khr' => $this->settlementKhr->getAmount(),
            'settlement_khr_formatted' => $this->settlementKhr->format(),
            'exchange_rate' => $this->exchangeRate,
            'coupon_code' => $this->coupon?->code,
            'shipping_method_id' => $this->shippingMethod?->id,
            'line_items' => array_map(function ($item) {
                return [
                    'item_id' => $item['item_id'],
                    'variant_id' => $item['variant_id'],
                    'sku' => $item['sku'],
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price']->getAmount(),
                    'unit_price_formatted' => $item['unit_price']->format(),
                    'quantity' => $item['quantity'],
                    'line_subtotal' => $item['line_subtotal']->getAmount(),
                    'allocated_discount' => $item['allocated_discount']->getAmount(),
                    'line_total' => $item['line_total']->getAmount(),
                    'line_total_formatted' => $item['line_total']->format(),
                ];
            }, $this->lineItems),
        ];
    }
}
