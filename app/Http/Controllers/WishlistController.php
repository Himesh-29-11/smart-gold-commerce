<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.wishlist', ['items' => $request->user()->wishlist()->with('product.category')->latest()->get()]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $existing = $request->user()->wishlist()->where('product_id', $product->id)->first();
        if ($existing) {
            $existing->delete();
            $message = 'Removed from wishlist.';
        } else {
            $request->user()->wishlist()->create(['product_id' => $product->id]);
            $message = 'Saved to wishlist.';
        }

return back()->with('success', $message);
    }
}
