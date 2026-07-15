<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id', 'partner_id', 'name', 'slug', 'sku', 'description', 'purity', 'weight_grams', 'certification', 'hallmark_number', 'pricing_mode', 'base_price', 'making_charge', 'gst_percentage', 'stock_quantity', 'image_url', 'gallery', 'is_featured', 'is_active'];

    protected function casts(): array
    {
        return ['gallery' => 'array', 'weight_grams' => 'decimal:3', 'base_price' => 'decimal:2', 'making_charge' => 'decimal:2', 'gst_percentage' => 'decimal:2', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
