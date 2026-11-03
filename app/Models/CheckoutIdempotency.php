<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutIdempotency extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_scope',
        'idempotency_key',
        'request_hash',
        'order_id',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
