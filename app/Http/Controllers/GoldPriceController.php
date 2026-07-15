<?php

namespace App\Http\Controllers;

use App\Models\GoldPriceHistory;
use App\Services\GoldPriceService;
use Illuminate\View\View;

class GoldPriceController extends Controller
{
    public function __invoke(GoldPriceService $service): View
    {
        $rates = $service->latestRates();
        $history = GoldPriceHistory::where('fetched_at', '>=', now()->subDays(30))->oldest('fetched_at')->get()->groupBy('carat');
        $change = (float) ($rates['24K']?->market_change ?? 0);
        $recommendation = $change < -50 ? 'Favourable buying window' : ($change > 100 ? 'Consider watching the market' : 'Market is steady');

        return view('gold-prices', compact('rates', 'history', 'recommendation', 'service'));
    }
}
