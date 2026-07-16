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
        $signal = $service->marketSignal($rates['24K']);

        return view('gold-prices', [
            'rates' => $rates,
            'history' => $history,
            'recommendation' => $signal['label'],
            'marketTrend' => $signal['trend'],
            'dataMode' => $service->dataMode(),
            'service' => $service,
            'pollSeconds' => (int) config('gold.dashboard_poll_seconds'),
        ]);
    }
}
