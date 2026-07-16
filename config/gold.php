<?php

return [
    /*
    | Authorized market-data integration. Never scrape retail websites.
    | The endpoint must return 22K and 24K rates in the configured unit.
    */
    'provider' => env('GOLD_PRICE_PROVIDER', 'database'),
    'endpoint' => env('GOLD_PRICE_API_URL'),
    'history_endpoint' => env('GOLD_PRICE_HISTORY_API_URL'),
    'api_key' => env('GOLD_PRICE_API_KEY'),
    'auth_mode' => env('GOLD_PRICE_API_AUTH_MODE', 'header'), // header, bearer, query, none
    'api_key_header' => env('GOLD_PRICE_API_KEY_HEADER', 'X-API-Key'),
    'api_key_query' => env('GOLD_PRICE_API_KEY_QUERY', 'api_key'),
    'api_key_prefix' => env('GOLD_PRICE_API_KEY_PREFIX', ''),
    'currency' => env('GOLD_PRICE_CURRENCY', 'INR'),
    'unit' => env('GOLD_PRICE_API_UNIT', 'gram'), // gram or troy_ounce
    'timeout' => (int) env('GOLD_PRICE_API_TIMEOUT', 10),
    'history_date_format' => env('GOLD_PRICE_HISTORY_DATE_FORMAT', 'Y-m-d'),
    'paths' => [
        '22K' => env('GOLD_PRICE_22K_PATH', 'rates.22K'),
        '24K' => env('GOLD_PRICE_24K_PATH', 'rates.24K'),
        'change_22K' => env('GOLD_PRICE_22K_CHANGE_PATH', 'changes.22K'),
        'change_24K' => env('GOLD_PRICE_24K_CHANGE_PATH', 'changes.24K'),
        'timestamp' => env('GOLD_PRICE_TIMESTAMP_PATH', 'timestamp'),
    ],
    'history_paths' => [
        '22K' => env('GOLD_PRICE_HISTORY_22K_PATH', env('GOLD_PRICE_22K_PATH', 'rates.22K')),
        '24K' => env('GOLD_PRICE_HISTORY_24K_PATH', env('GOLD_PRICE_24K_PATH', 'rates.24K')),
        'change_22K' => env('GOLD_PRICE_HISTORY_22K_CHANGE_PATH', env('GOLD_PRICE_22K_CHANGE_PATH', 'changes.22K')),
        'change_24K' => env('GOLD_PRICE_HISTORY_24K_CHANGE_PATH', env('GOLD_PRICE_24K_CHANGE_PATH', 'changes.24K')),
        'timestamp' => env('GOLD_PRICE_HISTORY_TIMESTAMP_PATH', env('GOLD_PRICE_TIMESTAMP_PATH', 'timestamp')),
    ],
    'stale_after_minutes' => (int) env('GOLD_PRICE_STALE_MINUTES', 30),
    'block_stale_checkout' => (bool) env('GOLD_PRICE_BLOCK_STALE_CHECKOUT', true),
    'allow_demo_checkout' => (bool) env('GOLD_PRICE_ALLOW_DEMO_CHECKOUT', false),
    'dashboard_poll_seconds' => max(30, (int) env('GOLD_PRICE_DASHBOARD_POLL_SECONDS', 60)),
];
