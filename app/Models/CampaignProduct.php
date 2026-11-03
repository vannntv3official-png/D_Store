<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'product_id',
        'promotional_price',
        'flash_sale_quota',
        'flash_sale_sold',
    ];

    protected function casts(): array
    {
        return [
            'promotional_price' => 'decimal:4',
            'flash_sale_quota' => 'integer',
            'flash_sale_sold' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
