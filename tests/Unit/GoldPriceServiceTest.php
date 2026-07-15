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
}
