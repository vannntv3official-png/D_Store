<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lifetime_points_earned',
        'lifetime_points_spent',
        'current_points_balance',
        'points_debt',
    ];

    protected function casts(): array
    {
        return [
            'lifetime_points_earned' => 'integer',
            'lifetime_points_spent' => 'integer',
            'current_points_balance' => 'integer',
            'points_debt' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->orderBy('created_at', 'desc');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(PointLot::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function getSpendablePointsAttribute(): int
    {
        return ((int) ($this->points_debt ?? 0) > 0) ? 0 : (int) ($this->current_points_balance ?? 0);
    }
}
