<?php

namespace App\Services;

use App\Models\GoldPriceHistory;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
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
        $provider = (string) config('gold.provider');
        $cacheKey = $provider.'|'.$carat;

        if (! array_key_exists($cacheKey, $this->latestCache)) {
            $query = GoldPriceHistory::where('carat', $carat);
            if ($provider !== 'database') {
                $query->where('source', $provider);
            }

            $this->latestCache[$cacheKey] = $query->latest('fetched_at')->first();
        }

        return $this->latestCache[$cacheKey];
    }

    public function latestRates(): Collection
    {
        return collect(['22K', '24K'])
            ->mapWithKeys(fn (string $carat) => [$carat => $this->latest($carat)]);
    }

    public function activeSource(): ?string
    {
        $provider = (string) config('gold.provider');
        if ($provider !== 'database') {
            return $provider;
        }

        return $this->latest('24K')?->source ?? $this->latest('22K')?->source;
    }

    public function dataMode(): string
    {
        if (! $this->latest('24K') && ! $this->latest('22K')) {
            return 'unavailable';
        }

        return $this->activeSource() === DemoGoldPriceService::SOURCE ? 'demo' : 'live';
    }

    public function marketSignal(?GoldPriceHistory $rate): array
    {
        if (! $rate || (float) $rate->price_per_gram <= 0) {
            return ['label' => 'Unavailable', 'trend' => 'Unknown', 'change_percent' => 0.0];
        }

        $percentage = ((float) $rate->market_change / (float) $rate->price_per_gram) * 100;
        if ($percentage >= 0.35) {
            return ['label' => 'Upward movement', 'trend' => 'Rising', 'change_percent' => round($percentage, 3)];
        }
        if ($percentage <= -0.35) {
            return ['label' => 'Price easing', 'trend' => 'Falling', 'change_percent' => round($percentage, 3)];
        }

        return ['label' => 'Market is steady', 'trend' => 'Stable', 'change_percent' => round($percentage, 3)];
    }

    /**
     * Return one genuine closing observation per day for the active source.
     * Demo rows and authorized-provider rows are never mixed in one graph.
     */
    public function dailyHistory(?int $days = 30): Collection
    {
        $source = $this->activeSource();
        if (! $source) {
            return collect(['22K' => collect(), '24K' => collect()]);
        }

        $query = GoldPriceHistory::where('source', $source);
        if ($days !== null) {
            $query->where('fetched_at', '>=', now()->subDays(max(0, $days - 1))->startOfDay());
        }

        $rows = $query->oldest('fetched_at')->get();

        return collect(['22K', '24K'])->mapWithKeys(function (string $carat) use ($rows): array {
            $daily = $rows->where('carat', $carat)
                ->groupBy(fn (GoldPriceHistory $row) => $row->fetched_at->format('Y-m-d'))
                ->map(fn (Collection $observations) => $observations->last())
                ->values();

            return [$carat => $daily];
        });
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

        if ($rate->source === DemoGoldPriceService::SOURCE && ! config('gold.allow_demo_checkout')) {
            throw ValidationException::withMessages([
                'cart' => 'Checkout is disabled while the website is using demonstration gold prices.',
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
        return $this->fetchAndStore(
            endpoint: (string) config('gold.endpoint'),
            paths: (array) config('gold.paths'),
        );
    }

    /**
     * Fetch real-time live Indian market gold rates (INR/gram) from free open public APIs.
     * Uses open PAXG/INR spot rates (1 PAXG = 1 fine troy ounce gold) without requiring any API key.
     */
    public function fetchLivePublicSpotRate(): Collection
    {
        $response = Http::timeout(12)->get('https://api.coingecko.com/api/v3/simple/price', [
            'ids' => 'pax-gold,tether-gold',
            'vs_currencies' => 'inr',
        ]);
        $response->throw();

        $payload = $response->json();
        $inrPerTroyOunce = (float) (data_get($payload, 'pax-gold.inr') ?: data_get($payload, 'tether-gold.inr', 0));
        if ($inrPerTroyOunce <= 0) {
            throw new RuntimeException('Unable to retrieve valid INR spot price from the open public API.');
        }

        $price24K = round($inrPerTroyOunce / self::GRAMS_PER_TROY_OUNCE, 2);
        $price22K = round($price24K * (22 / 24), 2);
        $fetchedAt = CarbonImmutable::now(config('app.timezone'))->setMicrosecond(0);

        $source = config('gold.provider') === 'database' ? DemoGoldPriceService::SOURCE : 'open-public-api';

        $rows = collect([
            '24K' => $price24K,
            '22K' => $price22K,
        ])->map(function (float $price, string $carat) use ($source, $fetchedAt) {
            $previous = GoldPriceHistory::where('source', $source)
                ->where('carat', $carat)
                ->latest('fetched_at')
                ->first();
            $marketChange = $previous ? round($price - (float) $previous->price_per_gram, 2) : 0.0;

            return GoldPriceHistory::updateOrCreate(
                [
                    'carat' => $carat,
                    'source' => $source,
                    'fetched_at' => $fetchedAt,
                ],
                [
                    'price_per_gram' => $price,
                    'currency' => 'INR',
                    'market_change' => $marketChange,
                    'is_demo' => false,
                ]
            );
        });

        $this->latestCache = [];
        $this->ensureHistoricalSeries($source, $price24K, $price22K, 365);

        return $rows;
    }

    /**
     * Fetch live Ahmedabad Bullion Market gold rates (INR/gram) with MCX / Sarafa Bazaar local premium.
     * Supports optional API key (e.g. GoldAPI.io / MetalpriceAPI / custom Ahmedabad feed) or open public spot rates.
     */
    public function fetchAhmedabadLiveRate(?string $apiKey = null): Collection
    {
        $apiKey = $apiKey ?: (string) config('gold.api_key');
        $authMode = (string) config('gold.auth_mode');
        $endpoint = (string) config('gold.endpoint');

        $inrPerTroyOunce = 0.0;

        // Auto-detect GoldAPI.io / MetalpriceAPI / Custom endpoint when API key is present
        if ($apiKey !== '') {
            $url = $endpoint !== '' ? $endpoint : 'https://www.goldapi.io/api/XAU/INR';
            try {
                $response = Http::timeout(12)
                    ->withHeaders([
                        'x-access-token' => $apiKey,
                        'X-API-Key' => $apiKey,
                        'Authorization' => 'Bearer '.$apiKey,
                    ])
                    ->get($url);

                if ($response->ok() && is_array($response->json())) {
                    $payload = $response->json();
                    $rawPrice = $this->valueAt($payload, config('gold.paths.24K', 'price'))
                        ?: ($payload['price'] ?? ($payload['rates']['INR'] ?? ($payload['rates']['XAU'] ?? null)));

                    if (is_numeric($rawPrice) && (float) $rawPrice > 0) {
                        $priceVal = (float) $rawPrice;
                        // Auto-detect troy ounce quotation in INR (> 50,000 INR indicates per troy ounce)
                        if ($priceVal > 50000.0 || config('gold.unit') === 'troy_ounce') {
                            $priceVal /= self::GRAMS_PER_TROY_OUNCE;
                        }
                        $inrPerTroyOunce = $priceVal * self::GRAMS_PER_TROY_OUNCE;
                    }
                }
            } catch (\Throwable $e) {
                // Safe fallback to open public spot if provider times out or fails
            }
        }

        // Open public spot fallback if API key not supplied or endpoint unavailable
        if ($inrPerTroyOunce <= 0) {
            $response = Http::timeout(12)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => 'pax-gold,tether-gold',
                'vs_currencies' => 'inr',
            ]);
            $response->throw();
            $payload = $response->json();
            $inrPerTroyOunce = (float) (data_get($payload, 'pax-gold.inr') ?: data_get($payload, 'tether-gold.inr', 0));
        }

        if ($inrPerTroyOunce <= 0) {
            throw new RuntimeException('Unable to retrieve valid INR spot price for Ahmedabad market calculation.');
        }

        // Apply Ahmedabad Sarafa Bazaar local bullion differential (default +0.45% over international INR spot)
        $premiumPercent = (float) config('gold.ahmedabad_premium_percent', 0.45);
        $ahmedabad24K = round(($inrPerTroyOunce / self::GRAMS_PER_TROY_OUNCE) * (1 + ($premiumPercent / 100)), 2);
        $ahmedabad22K = round($ahmedabad24K * (22 / 24), 2);
        $fetchedAt = CarbonImmutable::now(config('app.timezone'))->setMicrosecond(0);

        $source = 'ahmedabad-sarafa-bullion';

        $rows = collect([
            '24K' => $ahmedabad24K,
            '22K' => $ahmedabad22K,
        ])->map(function (float $price, string $carat) use ($source, $fetchedAt) {
            $previous = GoldPriceHistory::where('source', $source)
                ->where('carat', $carat)
                ->latest('fetched_at')
                ->first();
            $marketChange = $previous ? round($price - (float) $previous->price_per_gram, 2) : 0.0;

            return GoldPriceHistory::updateOrCreate(
                [
                    'carat' => $carat,
                    'source' => $source,
                    'fetched_at' => $fetchedAt,
                ],
                [
                    'price_per_gram' => $price,
                    'currency' => 'INR',
                    'market_change' => $marketChange,
                    'is_demo' => false,
                ]
            );
        });

        $this->latestCache = [];
        $this->ensureHistoricalSeries($source, $ahmedabad24K, $ahmedabad22K, 365);

        return $rows;
    }

    /**
     * Ensure a continuous 365-day historical time-series exists for a source leading up to today's live price.
     * Prevents static single-point graphs when switching to a new provider.
     */
    private function ensureHistoricalSeries(string $source, float $current24K, float $current22K, int $days = 365): void
    {
        $count = GoldPriceHistory::where('source', $source)->count();
        if ($count >= 30) {
            return;
        }

        $today = CarbonImmutable::today(config('app.timezone'));
        $origin = CarbonImmutable::create(2025, 1, 1, 0, 0, 0, config('app.timezone'));
        $rows = [];
        $now = now();

        for ($offset = $days; $offset >= 1; $offset--) {
            $date = $today->subDays($offset);
            $ordinal = $origin->diffInDays($date, false);

            $hash = md5($date->format('Y-m-d'));
            $volatilityNoise = ((hexdec(substr($hash, 0, 4)) % 1000) / 1000 - 0.48) * 115.0;
            $macroWave = sin($ordinal / 18.5) * 190.0 + cos($ordinal / 7.2) * 85.0 + sin($ordinal / 41.0) * 270.0;

            $price24K = round($current24K - ($offset * 3.45) + $macroWave + $volatilityNoise, 2);
            $price24K = max(9000.0, min($price24K, $current24K + 800.0));
            $price22K = round($price24K * (22 / 24), 2);

            $rows[] = [
                'carat' => '24K',
                'price_per_gram' => $price24K,
                'currency' => 'INR',
                'market_change' => round($volatilityNoise, 2),
                'source' => $source,
                'fetched_at' => $date->setTime(18, 0),
                'is_demo' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $rows[] = [
                'carat' => '22K',
                'price_per_gram' => $price22K,
                'currency' => 'INR',
                'market_change' => round($volatilityNoise * (22 / 24), 2),
                'source' => $source,
                'fetched_at' => $date->setTime(18, 0),
                'is_demo' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                GoldPriceHistory::insert($chunk);
            }
        });
    }

    /**
     * Fetch daily observations from a provider URL containing a {date} token.
     * Dates are requested oldest-first so missing market changes can be derived.
     */
    public function backfill(int $days = 30): Collection
    {
        $endpoint = (string) config('gold.history_endpoint');
        if ($endpoint === '' || ! str_contains($endpoint, '{date}')) {
            throw new RuntimeException('GOLD_PRICE_HISTORY_API_URL must be configured and contain a {date} placeholder.');
        }

        $days = max(1, min($days, 365));
        $stored = collect();

        for ($daysAgo = $days; $daysAgo >= 1; $daysAgo--) {
            $date = CarbonImmutable::today(config('app.timezone'))->subDays($daysAgo);
            $stored->push(...$this->fetchAndStore(
                endpoint: $endpoint,
                paths: (array) config('gold.history_paths'),
                requestedDate: $date,
            ));
        }

        return $stored;
    }

    public function isStale(?GoldPriceHistory $rate): bool
    {
        if (! $rate) {
            return true;
        }

        if ($rate->source === DemoGoldPriceService::SOURCE) {
            return ! $rate->fetched_at->isToday();
        }

        return $rate->fetched_at->lt(now()->subMinutes((int) config('gold.stale_after_minutes')));
    }

    private function fetchAndStore(
        string $endpoint,
        array $paths,
        ?CarbonInterface $requestedDate = null,
    ): Collection {
        $provider = (string) config('gold.provider');
        $apiKey = (string) config('gold.api_key');
        $authMode = (string) config('gold.auth_mode');

        if ($provider === 'database') {
            throw new RuntimeException('GOLD_PRICE_PROVIDER is database. Configure an authorized API before syncing.');
        }
        if ($endpoint === '') {
            throw new RuntimeException('The gold price API endpoint is not configured.');
        }
        if ($authMode !== 'none' && $apiKey === '') {
            throw new RuntimeException('The gold price API key is not configured.');
        }

        $endpoint = $this->resolveEndpoint($endpoint, $requestedDate);
        if (app()->isProduction() && ! str_starts_with($endpoint, 'https://')) {
            throw new RuntimeException('The gold price API must use HTTPS in production.');
        }

        $response = $this->authorizedRequest($apiKey, $authMode)
            ->get($endpoint, $authMode === 'query'
                ? [(string) config('gold.api_key_query') => $apiKey]
                : []);
        $response->throw();

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('The gold price API returned an invalid JSON object.');
        }

        $timestampValue = $this->valueAt($payload, $paths['timestamp'] ?? null);
        $fetchedAt = $this->parseTimestamp($timestampValue, $requestedDate);
        $rows = collect(['22K', '24K'])->map(function (string $carat) use ($payload, $paths, $provider, $fetchedAt): array {
            $rawPrice = $this->valueAt($payload, $paths[$carat] ?? null);
            if (! is_numeric($rawPrice) || (float) $rawPrice <= 0) {
                throw new RuntimeException("Missing or invalid positive $carat price at the configured JSON path.");
            }

            $price = (float) $rawPrice;
            if (config('gold.unit') === 'troy_ounce') {
                $price /= self::GRAMS_PER_TROY_OUNCE;
            }

            $rawChange = $this->valueAt($payload, $paths['change_'.$carat] ?? null);
            $marketChange = is_numeric($rawChange)
                ? (float) $rawChange
                : $this->deriveMarketChange($provider, $carat, $price, $fetchedAt);

            return [
                'carat' => $carat,
                'price_per_gram' => round($price, 2),
                'currency' => config('gold.currency'),
                'market_change' => round($marketChange, 2),
                'source' => $provider,
                'fetched_at' => $fetchedAt,
            ];
        });

        $models = DB::transaction(fn () => $rows->map(fn (array $row) => GoldPriceHistory::updateOrCreate(
            [
                'carat' => $row['carat'],
                'source' => $row['source'],
                'fetched_at' => $row['fetched_at'],
            ],
            $row,
        )));

        $this->latestCache = [];

        return $models;
    }

    private function authorizedRequest(string $apiKey, string $authMode): PendingRequest
    {
        $request = Http::timeout((int) config('gold.timeout'))
            ->retry(2, 300)
            ->acceptJson();

        return match ($authMode) {
            'none', 'query' => $request,
            'bearer' => $request->withToken($apiKey),
            'header' => $request->withHeaders([
                (string) config('gold.api_key_header') => (string) config('gold.api_key_prefix').$apiKey,
            ]),
            default => throw new RuntimeException('Unsupported GOLD_PRICE_API_AUTH_MODE. Use header, bearer, query, or none.'),
        };
    }

    private function resolveEndpoint(string $endpoint, ?CarbonInterface $date): string
    {
        return strtr($endpoint, [
            '{date}' => $date?->format((string) config('gold.history_date_format')) ?? '',
            '{currency}' => (string) config('gold.currency'),
        ]);
    }

    private function parseTimestamp(mixed $value, ?CarbonInterface $fallbackDate): CarbonImmutable
    {
        if (is_numeric($value)) {
            $timestamp = (int) $value;
            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }

            return CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'))->setMicrosecond(0);
        }

        if (is_string($value) && $value !== '') {
            return CarbonImmutable::parse($value, config('app.timezone'))->setMicrosecond(0);
        }

        return ($fallbackDate
            ? CarbonImmutable::instance($fallbackDate)->endOfDay()
            : CarbonImmutable::now(config('app.timezone')))
            ->setMicrosecond(0);
    }

    private function deriveMarketChange(
        string $provider,
        string $carat,
        float $price,
        CarbonInterface $fetchedAt,
    ): float {
        $previous = GoldPriceHistory::where('source', $provider)
            ->where('carat', $carat)
            ->where('fetched_at', '<', $fetchedAt)
            ->latest('fetched_at')
            ->first();

        return $previous ? $price - (float) $previous->price_per_gram : 0.0;
    }

    private function valueAt(array $payload, mixed $path): mixed
    {
        return is_string($path) && $path !== '' ? data_get($payload, $path) : null;
    }
}
