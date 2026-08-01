<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['user_id', 'order_code', 'reference', 'status', 'payment_status', 'subtotal', 'discount', 'tax', 'delivery_charge', 'total', 'coupon_code', 'shipping_address', 'notes'];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->order_code)) {
                $max = (int) static::max('order_code');
                $orderCode = max(5001, $max + 1);
                $order->order_code = $orderCode;
                if (empty($order->reference)) {
                    $order->reference = 'SGC-'.$orderCode;
                }
            }
        });
    }

    protected function casts(): array
    {
        return ['shipping_address' => 'array', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2', 'delivery_charge' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
