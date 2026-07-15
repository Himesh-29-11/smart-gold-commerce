<?php

namespace App\Http\Controllers;

use App\Services\GoldPriceService;
use Illuminate\View\View;

class GoldPriceController extends Controller
{
    public function __invoke(GoldPriceService $service): View
    {
        $rates = $service->latestRates();
        $history = $service->dailyHistory(30);
        $change = (float) ($rates['24K']?->market_change ?? 0);
        $recommendation = $change < -50
            ? 'Favourable buying window'
            : ($change > 100 ? 'Consider watching the market' : 'Market is steady');

        return view('gold-prices', [
            'rates' => $rates,
            'history' => $history,
            'recommendation' => $recommendation,
            'service' => $service,
            'pollSeconds' => (int) config('gold.dashboard_poll_seconds'),
        ]);
    }
}
