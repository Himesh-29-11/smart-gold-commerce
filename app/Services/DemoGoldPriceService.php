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
            $seasonal = sin($ordinal / 13) * 135 + cos($ordinal / 31) * 90;
            $price24K = round(9000 + ($ordinal * 5.15) + $seasonal, 2);
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
