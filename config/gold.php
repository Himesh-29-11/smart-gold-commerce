<?php

return [
    /*
    | Authorized market-data integration. Never scrape retail websites.
    | The endpoint must return the latest 22K/24K rates in the configured unit.
    */
    'provider' => env('GOLD_PRICE_PROVIDER', 'database'),
    'endpoint' => env('GOLD_PRICE_API_URL'),
    'api_key' => env('GOLD_PRICE_API_KEY'),
    'api_key_header' => env('GOLD_PRICE_API_KEY_HEADER', 'X-API-Key'),
    'currency' => env('GOLD_PRICE_CURRENCY', 'INR'),
    'unit' => env('GOLD_PRICE_API_UNIT', 'gram'), // gram or troy_ounce
    'timeout' => (int) env('GOLD_PRICE_API_TIMEOUT', 10),
    'paths' => [
        '22K' => env('GOLD_PRICE_22K_PATH', 'rates.22K'),
        '24K' => env('GOLD_PRICE_24K_PATH', 'rates.24K'),
        'change_22K' => env('GOLD_PRICE_22K_CHANGE_PATH', 'changes.22K'),
        'change_24K' => env('GOLD_PRICE_24K_CHANGE_PATH', 'changes.24K'),
        'timestamp' => env('GOLD_PRICE_TIMESTAMP_PATH', 'timestamp'),
    ],
    'stale_after_minutes' => (int) env('GOLD_PRICE_STALE_MINUTES', 90),
    'block_stale_checkout' => (bool) env('GOLD_PRICE_BLOCK_STALE_CHECKOUT', true),
];
