<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'is_flash_sale',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'discount_value' => 'decimal:4',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_flash_sale' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products')
            ->withPivot('promotional_price', 'flash_sale_quota', 'flash_sale_sold');
    }

    public function campaignProducts(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->isBetween($this->starts_at, $this->ends_at);
    }
}
