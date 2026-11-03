<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_variant_id',
        'on_hand_quantity',
        'reserved_quantity',
    ];

    protected function casts(): array
    {
        return [
            'on_hand_quantity' => 'integer',
            'reserved_quantity' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->on_hand_quantity - $this->reserved_quantity);
    }
}
