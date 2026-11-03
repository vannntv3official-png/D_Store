<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'discount_type',
        'discount_value',
        'min_spend_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'points_cost' => 'integer',
            'discount_value' => 'decimal:4',
            'min_spend_amount' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function getLocalizedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? (is_array($this->name) ? reset($this->name) : (string) $this->name);
    }
}
