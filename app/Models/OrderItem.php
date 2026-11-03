<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'product_name_snapshot',
        'sku_snapshot',
        'unit_price',
        'quantity',
        'line_subtotal',
        'allocated_discount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'product_name_snapshot' => 'array',
            'unit_price' => 'decimal:4',
            'quantity' => 'integer',
            'line_subtotal' => 'decimal:4',
            'allocated_discount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getLocalizedProductName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->product_name_snapshot[$locale] ?? $this->product_name_snapshot['en'] ?? (is_array($this->product_name_snapshot) ? reset($this->product_name_snapshot) : (string) $this->product_name_snapshot);
    }
}
