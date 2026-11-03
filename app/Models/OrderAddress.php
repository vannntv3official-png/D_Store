<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'contact_name',
        'contact_phone',
        'province_name',
        'district_name',
        'address_line',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
