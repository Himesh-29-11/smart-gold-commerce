<?php

use Illuminate\Support\Facades\Schedule;

if (config('gold.provider') === 'database') {
    if (app()->isLocal()) {
        Schedule::command('gold:refresh-demo-history --days=365 --if-stale')
            ->hourly()
            ->withoutOverlapping();
    }
} else {
    Schedule::command('gold:sync-prices')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->onOneServer();
}
