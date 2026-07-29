<?php

namespace App\Console\Commands;

use App\Services\GoldPriceService;
use Illuminate\Console\Command;

class FetchAhmedabadGoldRate extends Command
{
    protected $signature = 'gold:fetch-ahmedabad {--key= : Optional API key for GoldAPI.io or MetalpriceAPI}';

    protected $description = 'Fetch real-time Ahmedabad Sarafa Bazaar (MCX Market Linked) 22K and 24K gold spot rates with Gujarat local premium';

    public function handle(GoldPriceService $prices): int
    {
        $apiKey = $this->option('key');
        $this->info('Fetching live Ahmedabad Bullion Market gold rates (with MCX/Sarafa Bazaar differential)...');

        try {
            $rates = $prices->fetchAhmedabadLiveRate($apiKey);
            $this->table(
                ['Carat', 'Price / gram (INR)', 'Market Change', 'Source', 'Fetched At'],
                $rates->map(fn ($r) => [
                    $r->carat,
                    '₹' . number_format($r->price_per_gram, 2),
                    ($r->market_change >= 0 ? '+' : '') . '₹' . number_format($r->market_change, 2),
                    $r->source,
                    $r->fetched_at->format('Y-m-d H:i:s'),
                ])
            );
            $this->info('Successfully synchronized Ahmedabad Sarafa Bazaar gold rates!');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to fetch Ahmedabad bullion rates: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
