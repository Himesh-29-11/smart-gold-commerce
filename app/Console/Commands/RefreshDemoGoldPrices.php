<?php

namespace App\Console\Commands;

use App\Services\DemoGoldPriceService;
use Illuminate\Console\Command;

class RefreshDemoGoldPrices extends Command
{
    protected $signature = 'gold:refresh-demo-history
                            {--days=365 : Calendar days ending today}
                            {--if-stale : Skip rebuilding when a demo observation already exists for today}';

    protected $description = 'Rebuild explicitly labelled demonstration gold history through today';

    public function handle(DemoGoldPriceService $service): int
    {
        try {
            if ($this->option('if-stale') && $service->isCurrent()) {
                $this->info('Demo gold history already includes today ('.now()->toDateString().').');

                return self::SUCCESS;
            }

            $count = $service->refresh((int) $this->option('days'));
            $this->warn('DEMONSTRATION DATA ONLY — these records are not market quotes.');
            $this->info("Stored {$count} labelled demo observations through ".now()->toDateString().'.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
