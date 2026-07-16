<?php

namespace Tests\Unit;

use App\Models\GoldPriceHistory;
use App\Services\DemoGoldPriceService;
use App\Services\GoldPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoGoldPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_history_is_explicitly_labelled_and_runs_through_today(): void
    {
        $this->travelTo('2026-07-16 14:30:00');
        config(['gold.provider' => 'database']);

        $count = app(DemoGoldPriceService::class)->refresh(365);
        $history = app(GoldPriceService::class)->dailyHistory(365);
        $latest = GoldPriceHistory::where('source', DemoGoldPriceService::SOURCE)
            ->latest('fetched_at')
            ->firstOrFail();

        $this->assertSame(730, $count);
        $this->assertSame(DemoGoldPriceService::SOURCE, $latest->source);
        $this->assertTrue($latest->fetched_at->isToday());
        $this->assertCount(365, $history['24K']);
        $this->assertCount(365, $history['22K']);
        $this->assertSame('demo', app(GoldPriceService::class)->dataMode());
    }
}
