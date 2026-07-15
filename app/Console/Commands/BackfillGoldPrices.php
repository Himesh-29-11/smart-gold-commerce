<?php

namespace App\Console\Commands;

use App\Services\GoldPriceService;
use Illuminate\Console\Command;

class BackfillGoldPrices extends Command
{
    protected $signature = 'gold:backfill-prices {--days=30 : Number of completed calendar days to import}';

    protected $description = 'Import genuine historical rates from the configured authorized provider';

    public function handle(GoldPriceService $service): int
    {
        $days = (int) $this->option('days');

        try {
            $this->info("Importing up to {$days} completed days from the authorized provider...");
            $rates = $service->backfill($days);
            $this->info("Stored or updated {$rates->count()} carat observations.");
            $this->line('Run `php artisan gold:sync-prices` to append the current live observation.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
