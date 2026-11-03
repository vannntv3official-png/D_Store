<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'quantity_change',
        'resulting_on_hand',
        'resulting_reserved',
        'reason',
        'actor_id',
        'operation_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'resulting_on_hand' => 'integer',
            'resulting_reserved' => 'integer',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
