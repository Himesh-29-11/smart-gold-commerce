<?php

namespace Tests\Unit;

use App\Models\GoldPriceHistory;
use App\Services\DemoGoldPriceService;
use App\Services\GoldPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoGoldPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_history_is_explicitly_labelled_and_runs_through_today(): void
    {
        $this->travelTo('2026-07-16 14:30:00');
        config(['gold.provider' => 'database']);

        $service = app(DemoGoldPriceService::class);
        $this->assertFalse($service->isCurrent());

        $count = $service->refresh(365);
        $history = app(GoldPriceService::class)->dailyHistory(365);
        $latest = GoldPriceHistory::where('source', DemoGoldPriceService::SOURCE)
            ->latest('fetched_at')
            ->firstOrFail();

        $this->assertSame(730, $count);
        $this->assertSame(DemoGoldPriceService::SOURCE, $latest->source);
        $this->assertTrue($latest->fetched_at->isToday());
        $this->assertTrue($service->isCurrent());
        $this->assertCount(365, $history['24K']);
        $this->assertCount(365, $history['22K']);
        $goldPrices = app(GoldPriceService::class);
        $this->assertSame('demo', $goldPrices->dataMode());
        $this->travelTo('2026-07-16 23:59:00');
        $this->assertFalse($goldPrices->isStale($latest));

        $this->assertSame(0, Artisan::call('gold:refresh-demo-history', [
            '--days' => 365,
            '--if-stale' => true,
        ]));
        $this->assertStringContainsString('already includes today', Artisan::output());
    }
}
