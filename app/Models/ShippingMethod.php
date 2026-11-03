<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_zone_id',
        'name',
        'description',
        'rate',
        'min_order_free_shipping',
        'estimated_delivery_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'rate' => 'decimal:4',
            'min_order_free_shipping' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function getLocalizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? (is_array($this->name) ? reset($this->name) : (string) $this->name);
    }
}
