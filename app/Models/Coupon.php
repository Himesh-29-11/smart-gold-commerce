<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'minimum_order', 'maximum_discount', 'usage_limit', 'used_count', 'starts_at', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean', 'value' => 'decimal:2', 'minimum_order' => 'decimal:2', 'maximum_discount' => 'decimal:2'];
    }

    public function isAvailable(float $subtotal): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->isPast()) && (! $this->expires_at || $this->expires_at->isFuture()) && (! $this->usage_limit || $this->used_count < $this->usage_limit) && $subtotal >= (float) $this->minimum_order;
    }
}
