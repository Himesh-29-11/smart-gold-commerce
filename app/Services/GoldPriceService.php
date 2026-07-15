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
        return ! $rate
            || $rate->fetched_at->lt(now()->subMinutes((int) config('gold.stale_after_minutes')));
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
