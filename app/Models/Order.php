<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const FULFILLMENT_PENDING = 'pending';
    public const FULFILLMENT_CONFIRMED = 'confirmed';
    public const FULFILLMENT_PROCESSING = 'processing';
    public const FULFILLMENT_PACKED = 'packed';
    public const FULFILLMENT_SHIPPED = 'shipped';
    public const FULFILLMENT_DELIVERED = 'delivered';
    public const FULFILLMENT_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_AUTHORIZED = 'authorized';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_FAILED = 'failed';

    protected $fillable = [
        'order_number',
        'guest_token',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'base_currency',
        'display_currency',
        'exchange_rate',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'grand_total',
        'settlement_amount',
        'fulfillment_status',
        'payment_status',
        'shipping_method_name',
        'delivery_notes',
        'cancelled_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'shipping_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'settlement_amount' => 'decimal:4',
            'cancelled_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'billing');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->orderBy('created_at', 'desc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isDelivered(): bool
    {
        return $this->fulfillment_status === 'delivered';
    }

    public function canCancel(): bool
    {
        return in_array($this->fulfillment_status, ['pending', 'confirmed'])
            && $this->fulfillment_status !== 'cancelled';
    }
}
