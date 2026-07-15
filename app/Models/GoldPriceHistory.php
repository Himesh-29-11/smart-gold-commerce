<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPriceHistory extends Model
{
    protected $fillable = ['carat', 'price_per_gram', 'currency', 'market_change', 'source', 'fetched_at'];

    protected function casts(): array
    {
        return ['price_per_gram' => 'decimal:2', 'market_change' => 'decimal:2', 'fetched_at' => 'datetime'];
    }
}
