<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['rating' => 'required|integer|min:1|max:5', 'comment' => 'required|string|min:10|max:1000']);
        $hasPurchased = $request->user()->orders()->where('payment_status', 'paid')->whereHas('items', fn ($q) => $q->where('product_id', $product->id))->exists();
        if (! $hasPurchased) {
            return back()->withErrors(['review' => 'Only verified buyers can review this product.']);
        } $request->user()->reviews()->updateOrCreate(['product_id' => $product->id], $data + ['is_approved' => false]);

        return back()->with('success', 'Thank you. Your review will appear after moderation.');
    }
}
