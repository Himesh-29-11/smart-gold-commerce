<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'provider', 'provider_order_id', 'provider_payment_id', 'status', 'amount', 'currency', 'provider_payload', 'paid_at'];

    protected function casts(): array
    {
        return ['provider_payload' => 'array', 'amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
