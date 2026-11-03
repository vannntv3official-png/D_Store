<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_account_id',
        'order_id',
        'initial_points',
        'remaining_points',
        'expires_at',
        'is_expired',
    ];

    protected function casts(): array
    {
        return [
            'initial_points' => 'integer',
            'remaining_points' => 'integer',
            'expires_at' => 'datetime',
            'is_expired' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'loyalty_account_id');
    }
}
