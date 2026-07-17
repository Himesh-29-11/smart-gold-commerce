<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'tracking_number', 'carrier', 'carrier_tracking_number', 'status',
        'public_tracking_url', 'current_latitude', 'current_longitude', 'location_updated_at',
        'estimated_delivery_at', 'dispatched_at', 'delivered_at', 'provider_meta',
    ];

    protected function casts(): array
    {
        return [
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
            'location_updated_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'provider_meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('occurred_at');
    }

    public function hasLocation(): bool
    {
        return $this->current_latitude !== null && $this->current_longitude !== null;
    }
}
