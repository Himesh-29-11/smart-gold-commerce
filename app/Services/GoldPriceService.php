<?php

namespace App\Services;

use App\Models\GoldPriceHistory;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GoldPriceService
{
    private const GRAMS_PER_TROY_OUNCE = 31.1034768;

    /** @var array<string, GoldPriceHistory|null> */
    private array $latestCache = [];

    public function latest(string $carat): ?GoldPriceHistory
    {
        $carat = strtoupper($carat);
        if (! array_key_exists($carat, $this->latestCache)) {
            $this->latestCache[$carat] = GoldPriceHistory::where('carat', $carat)
                ->latest('fetched_at')
                ->first();
        }

        return $this->latestCache[$carat];
    }

    public function latestRates(): Collection
    {
        return collect(['22K', '24K'])
            ->mapWithKeys(fn (string $carat) => [$carat => $this->latest($carat)]);
    }

    public function productPrice(Product $product): float
    {
        if ($product->pricing_mode === 'fixed') {
            return round((float) $product->base_price + (float) $product->making_charge, 2);
        }

        $rate = $this->latest($product->purity);
        if (! $rate) {
            throw new RuntimeException("No {$product->purity} rate is available for this live-priced product.");
        }

        return round(
            ((float) $rate->price_per_gram * (float) $product->weight_grams)
                + (float) $product->making_charge,
            2,
        );
    }

    public function assertCheckoutAvailable(Product $product): void
    {
        if ($product->pricing_mode !== 'live') {
            return;
        }

        $rate = $this->latest($product->purity);
        if (! $rate) {
            throw ValidationException::withMessages([
                'cart' => "The {$product->purity} market rate is unavailable. Checkout is temporarily paused.",
            ]);
        }

        if (config('gold.block_stale_checkout') && $this->isStale($rate)) {
            throw ValidationException::withMessages([
                'cart' => "The {$product->purity} market rate is stale. Please try checkout after the authorized feed refreshes.",
            ]);
        }

        $configuredProvider = (string) config('gold.provider');
        if ($configuredProvider !== 'database' && $rate->source !== $configuredProvider) {
            throw ValidationException::withMessages([
                'cart' => 'The latest rate does not match the configured authorized provider. Checkout is paused.',
            ]);
        }
    }

    public function sync(): Collection
    {
        $provider = (string) config('gold.provider');
        $endpoint = (string) config('gold.endpoint');
        $apiKey = (string) config('gold.api_key');

        if ($provider === 'database') {
            throw new RuntimeException('GOLD_PRICE_PROVIDER is database. Configure an authorized API before syncing.');
        }
        if ($endpoint === '' || $apiKey === '') {
            throw new RuntimeException('Gold price API endpoint or key is not configured.');
        }
        if (app()->isProduction() && ! str_starts_with($endpoint, 'https://')) {
            throw new RuntimeException('The gold price API must use HTTPS in production.');
        }

        $response = Http::withoutVerifying()
            ->timeout((int) config('gold.timeout'))
            ->retry(2, 300)
            ->withHeaders([(string) config('gold.api_key_header') => $apiKey])
            ->acceptJson()
            ->get($endpoint);
        $response->throw();

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('The gold price API returned an invalid JSON object.');
        }

        $timestampValue = data_get($payload, config('gold.paths.timestamp'));
        $fetchedAt = $timestampValue ? CarbonImmutable::parse($timestampValue) : now();
        $rows = collect(['22K', '24K'])->map(function (string $carat) use ($payload, $provider, $fetchedAt): array {
            $rawPrice = data_get($payload, config("gold.paths.$carat"));
            if (! is_numeric($rawPrice) || (float) $rawPrice <= 0) {
                throw new RuntimeException("Missing or invalid positive $carat price at the configured JSON path.");
            }

            $price = (float) $rawPrice;
            if (config('gold.unit') === 'troy_ounce') {
                $price /= self::GRAMS_PER_TROY_OUNCE;
            }

            $change = data_get($payload, config('gold.paths.change_'.$carat), 0);

            return [
                'carat' => $carat,
                'price_per_gram' => round($price, 2),
                'currency' => config('gold.currency'),
                'market_change' => is_numeric($change) ? $change : 0,
                'source' => $provider,
                'fetched_at' => $fetchedAt,
            ];
        });

        return DB::transaction(
            fn () => $rows->map(fn (array $row) => GoldPriceHistory::create($row))
        );
    }

    public function isStale(?GoldPriceHistory $rate): bool
    {
        return ! $rate
            || $rate->fetched_at->lt(now()->subMinutes((int) config('gold.stale_after_minutes')));
    }
}
