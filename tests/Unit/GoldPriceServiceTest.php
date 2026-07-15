<?php

namespace Tests\Unit;

use App\Models\GoldPriceHistory;
use App\Services\GoldPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoldPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_provider_payload_is_normalized_and_stored(): void
    {
        config([
            'gold.provider' => 'licensed-test-provider',
            'gold.endpoint' => 'https://prices.example/latest',
            'gold.api_key' => 'test-key',
            'gold.unit' => 'gram',
        ]);
        Http::fake([
            'https://prices.example/latest' => Http::response([
                'rates' => ['22K' => 9350.25, '24K' => 10200.50],
                'changes' => ['22K' => -12.10, '24K' => -13.20],
                'timestamp' => '2026-07-11T16:30:00+05:30',
            ]),
        ]);

        $rates = app(GoldPriceService::class)->sync();

        $this->assertCount(2, $rates);
        $this->assertDatabaseHas('gold_price_histories', [
            'carat' => '24K',
            'price_per_gram' => 10200.50,
            'source' => 'licensed-test-provider',
        ]);
        $this->assertSame('-13.20', GoldPriceHistory::where('carat', '24K')->firstOrFail()->market_change);
        Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'test-key'));
    }

    public function test_history_backfill_uses_real_provider_only_and_never_mixes_demo_rows(): void
    {
        $this->travelTo('2026-07-15 12:00:00');
        config([
            'gold.provider' => 'licensed-test-provider',
            'gold.history_endpoint' => 'https://prices.example/history/{date}',
            'gold.api_key' => 'test-key',
            'gold.unit' => 'gram',
            'gold.history_paths.timestamp' => '',
        ]);
        GoldPriceHistory::create([
            'carat' => '24K',
            'price_per_gram' => 99999,
            'currency' => 'INR',
            'market_change' => 0,
            'source' => 'demo-seed-not-live',
            'fetched_at' => now()->subDay(),
        ]);
        Http::fake(fn () => Http::response([
            'rates' => ['22K' => 9350.25, '24K' => 10200.50],
            'changes' => ['22K' => -12.10, '24K' => -13.20],
        ]));

        $stored = app(GoldPriceService::class)->backfill(2);
        app(GoldPriceService::class)->backfill(2);
        $history = app(GoldPriceService::class)->dailyHistory(30);

        $this->assertCount(4, $stored);
        $this->assertSame(4, GoldPriceHistory::where('source', 'licensed-test-provider')->count());
        $this->assertCount(2, $history['24K']);
        $this->assertSame(
            ['licensed-test-provider'],
            $history['24K']->pluck('source')->unique()->values()->all(),
        );
        $this->assertNotContains(99999.0, $history['24K']->pluck('price_per_gram')->map(fn ($price) => (float) $price));
        Http::assertSentCount(4);
    }
}
