<?php

namespace App\Console\Commands;

use App\Services\GoldPriceService;
use Illuminate\Console\Command;

class SyncGoldPrices extends Command
{
    protected $signature = 'gold:sync-prices';

    protected $description = 'Fetch and store rates from the configured authorized gold-price API';

    public function handle(GoldPriceService $service): int
    {
        try {
            $rates = $service->sync();
            $rates->each(fn ($rate) => $this->info("{$rate->carat}: {$rate->currency} {$rate->price_per_gram}/g"));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
