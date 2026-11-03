<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'cost_price',
        'is_default',
        'is_active',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'compare_at_price' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getAvailableStockAttribute(): int
    {
        if (!$this->inventory) {
            return 0;
        }
        return max(0, $this->inventory->on_hand_quantity - $this->inventory->reserved_quantity);
    }
}
