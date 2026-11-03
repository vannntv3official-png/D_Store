<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'max_discount_amount',
        'min_spend_amount',
        'usage_limit_total',
        'usage_limit_per_user',
        'times_used',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'value' => 'decimal:4',
            'max_discount_amount' => 'decimal:4',
            'min_spend_amount' => 'decimal:4',
            'usage_limit_total' => 'integer',
            'usage_limit_per_user' => 'integer',
            'times_used' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isPercentage(): bool
    {
        return $this->type === 'percentage';
    }

    public function getDiscountTypeAttribute(): string
    {
        return $this->type;
    }

    public function getDiscountValueAttribute(): mixed
    {
        return $this->value;
    }

    public function getMinSpendAttribute(): mixed
    {
        return $this->min_spend_amount;
    }

    public function getUsageLimitAttribute(): mixed
    {
        return $this->usage_limit_total;
    }

    public function getUsageCountAttribute(): int
    {
        return (int) ($this->times_used ?? 0);
    }

    public function isValidNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit_total && $this->times_used >= $this->usage_limit_total) {
            return false;
        }

        return true;
    }
}
