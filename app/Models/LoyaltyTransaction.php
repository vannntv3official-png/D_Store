<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_account_id',
        'points_change',
        'resulting_balance',
        'resulting_debt',
        'event_type',
        'order_id',
        'event_key',
        'signature',
        'notes',
        'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'points_change' => 'integer',
            'resulting_balance' => 'integer',
            'resulting_debt' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'loyalty_account_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
