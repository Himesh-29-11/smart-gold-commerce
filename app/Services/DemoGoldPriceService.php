<?php

namespace App\Services;

use App\Models\GoldPriceHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoGoldPriceService
{
    public const SOURCE = 'demo-seed-not-live';

    public function isCurrent(): bool
    {
        $latest = GoldPriceHistory::where('source', self::SOURCE)
            ->latest('fetched_at')
            ->first();

        return $latest?->fetched_at?->isToday() ?? false;
    }

    /**
     * Rebuild deterministic demonstration history through the current local date.
     * These values are illustrative and must never be represented as live prices.
     */
    public function refresh(int $days = 365): int
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demonstration gold data cannot be generated in production.');
        }
        if (config('gold.provider') !== 'database') {
            throw new RuntimeException('Demo history can only be generated when GOLD_PRICE_PROVIDER=database.');
        }

        $days = max(5, min($days, 730));
        $today = CarbonImmutable::today(config('app.timezone'));
        $origin = CarbonImmutable::create(2025, 1, 1, 0, 0, 0, config('app.timezone'));
        $rows = [];
        $previous = ['24K' => null, '22K' => null];
        $now = now();

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = $today->subDays($offset);
            $ordinal = $origin->diffInDays($date, false);
            
            // Realistic financial market random-walk volatility with multi-frequency bullion cycles
            $hash = md5($date->format('Y-m-d'));
            $volatilityNoise = ((hexdec(substr($hash, 0, 4)) % 1000) / 1000 - 0.48) * 125.0; // daily fluctuation between -₹60 and +₹65
            $macroWave = sin($ordinal / 18.5) * 210.0 + cos($ordinal / 7.2) * 95.0 + sin($ordinal / 41.0) * 310.0;
            
            // Base Indian 24K spot price per gram in INR (~10,800 up to ~12,385 INR/g)
            $price24K = round(10650 + ($ordinal * 4.65) + $macroWave + $volatilityNoise, 2);
            $price24K = max(9500, min($price24K, 14800));
            $prices = [
                '24K' => $price24K,
                '22K' => round($price24K * (22 / 24), 2),
            ];
            $observedAt = $date->isToday()
                ? CarbonImmutable::now(config('app.timezone'))->setMicrosecond(0)
                : $date->setTime(18, 0);

            foreach ($prices as $carat => $price) {
                $rows[] = [
                    'carat' => $carat,
                    'price_per_gram' => $price,
                    'currency' => 'INR',
                    'market_change' => $previous[$carat] === null ? 0 : round($price - $previous[$carat], 2),
                    'source' => self::SOURCE,
                    'fetched_at' => $observedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $previous[$carat] = $price;
            }
        }

        DB::transaction(function () use ($rows): void {
            GoldPriceHistory::where('source', self::SOURCE)->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                GoldPriceHistory::insert($chunk);
            }
        });

        return count($rows);
    }
}
