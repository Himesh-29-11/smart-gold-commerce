<?php

namespace App\Http\Controllers;

use App\Models\GoldPriceHistory;
use App\Services\GoldPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GoldPriceDataController extends Controller
{
    public function __invoke(Request $request, GoldPriceService $service): JsonResponse
    {
        $data = $request->validate(['range' => 'nullable|in:5d,1m,1y,max']);
        $range = $data['range'] ?? '1m';
        $days = match ($range) {
            '5d' => 5,
            '1y' => 365,
            'max' => null,
            default => 30,
        };
        $rates = $service->latestRates();
        $history = $service->dailyHistory($days);

        $response = response()->json([
            'currency' => config('gold.currency'),
            'unit' => 'gram',
            'chart_unit_grams' => 10,
            'range' => $range,
            'source' => $service->activeSource(),
            'server_time' => now()->toIso8601String(),
            'poll_after_seconds' => (int) config('gold.dashboard_poll_seconds'),
            'rates' => $rates->map(fn (?GoldPriceHistory $rate) => $rate ? [
                'carat' => $rate->carat,
                'price_per_gram' => (float) $rate->price_per_gram,
                'market_change' => (float) $rate->market_change,
                'fetched_at' => $rate->fetched_at->toIso8601String(),
                'fetched_at_display' => $rate->fetched_at->format('d M Y, h:i A'),
                'source' => $rate->source,
                'stale' => $service->isStale($rate),
            ] : null),
            'history' => $history->map(fn (Collection $rows) => $rows->map(fn (GoldPriceHistory $row) => [
                'x' => $row->fetched_at->format('Y-m-d'),
                'y' => (float) $row->price_per_gram,
            ])->values()),
        ]);
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }
}
