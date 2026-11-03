<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_id',
        'order_item_id',
        'quantity',
        'restock_approved',
        'inspection_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'restock_approved' => 'boolean',
        ];
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
