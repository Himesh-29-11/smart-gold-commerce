<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Review;
use App\Services\GoldPriceService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(GoldPriceService $prices): View
    {
        return view('home', ['categories' => Category::where('is_active', true)->withCount(['products' => fn ($q) => $q->active()])->get(), 'products' => Product::active()->where('is_featured', true)->with(['category', 'partner', 'approvedReviews'])->limit(8)->get(), 'partners' => Partner::where('type', 'jewelry')->where('is_verified', true)->where('is_active', true)->get(), 'reviews' => Review::where('is_approved', true)->with(['user', 'product'])->latest()->limit(3)->get(), 'rates' => $prices->latestRates(), 'priceService' => $prices]);
    }
}
