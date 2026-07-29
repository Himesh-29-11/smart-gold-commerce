<?php

namespace App\Console\Commands;

use App\Services\GoldPriceService;
use Illuminate\Console\Command;

class FetchPublicRealGoldRate extends Command
{
    protected $signature = 'gold:fetch-public-real';

    protected $description = 'Fetch real-time live Indian market 22K and 24K gold rates from free open public API (no key required)';

    public function handle(GoldPriceService $prices): int
    {
        $this->info('Fetching live gold spot rates in INR from open public API...');

        try {
            $rates = $prices->fetchLivePublicSpotRate();
            $this->table(
                ['Carat', 'Price / gram (INR)', 'Market Change', 'Source', 'Fetched At'],
                $rates->map(fn ($r) => [
                    $r->carat,
                    '₹'.number_format($r->price_per_gram, 2),
                    ($r->market_change >= 0 ? '+' : '').'₹'.number_format($r->market_change, 2),
                    $r->source,
                    $r->fetched_at->format('Y-m-d H:i:s'),
                ])
            );
            $this->info('Successfully synchronized real live gold rates!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to fetch real live rates: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
