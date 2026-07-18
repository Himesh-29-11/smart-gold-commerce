<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\GoldPriceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, GoldPriceService $prices): View
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:80',
            'category' => 'nullable|string|max:80',
            'purity' => 'nullable|in:22K,24K',
            'weight' => 'nullable|in:under-5,5-20,over-20',
            'sort' => 'nullable|in:newest,weight-asc,weight-desc,name-asc',
        ]);

        $query = Product::active()->with(['category', 'partner', 'approvedReviews']);

        if ($search = $data['q'] ?? null) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($category = $data['category'] ?? null) {
            $query->whereHas('category', fn ($builder) => $builder->where('slug', $category));
        }
        if ($purity = $data['purity'] ?? null) {
            $query->where('purity', $purity);
        }

        match ($data['weight'] ?? null) {
            'under-5' => $query->where('weight_grams', '<', 5),
            '5-20' => $query->whereBetween('weight_grams', [5, 20]),
            'over-20' => $query->where('weight_grams', '>', 20),
            default => null,
        };
        match ($data['sort'] ?? 'newest') {
            'weight-asc' => $query->orderBy('weight_grams'),
            'weight-desc' => $query->orderByDesc('weight_grams'),
            'name-asc' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return view('catalog.index', [
            'products' => $query->paginate(16)->withQueryString(),
            'categories' => Category::where('is_active', true)->get(),
            'wishlistProductIds' => auth()->check()
                ? auth()->user()->wishlist()->pluck('product_id')->all()
                : [],
            'priceService' => $prices,
        ]);
    }

    public function show(Product $product, GoldPriceService $prices): View
    {
        abort_unless($product->is_active, 404);
        $product->load(['category', 'partner', 'approvedReviews.user']);
        $related = Product::active()
            ->with(['category', 'partner', 'approvedReviews'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();
        $wishlisted = auth()->check()
            && auth()->user()->wishlist()->where('product_id', $product->id)->exists();

        return view('catalog.show', compact('product', 'related', 'wishlisted') + [
            'priceService' => $prices,
        ]);
    }
}
