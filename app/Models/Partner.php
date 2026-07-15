<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = ['type', 'name', 'slug', 'logo_url', 'description', 'interest_rate_min', 'interest_rate_max', 'tenure_min_months', 'tenure_max_months', 'website_url', 'is_verified', 'is_active', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_verified' => 'boolean', 'is_active' => 'boolean', 'interest_rate_min' => 'decimal:2', 'interest_rate_max' => 'decimal:2'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function loanRequests(): HasMany
    {
        return $this->hasMany(LoanRequest::class);
    }
}
