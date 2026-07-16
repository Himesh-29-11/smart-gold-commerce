<?php

use Illuminate\Support\Facades\Schedule;

if (config('gold.provider') === 'database') {
    if (app()->isLocal()) {
        Schedule::command('gold:refresh-demo-history --days=365')
            ->dailyAt('00:05')
            ->withoutOverlapping();
    }
} else {
    Schedule::command('gold:sync-prices')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->onOneServer();
}
