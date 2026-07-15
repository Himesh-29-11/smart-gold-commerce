<?php

return [
    /*
    | A normalized HTTPS connector for one contracted provider. Additional
    | providers should receive dedicated adapters because schemas and consent
    | requirements differ. With no matching slug, requests stay in submitted.
    */
    'primary' => [
        'slug' => env('LOAN_PROVIDER_PRIMARY_SLUG'),
        'endpoint' => env('LOAN_PROVIDER_PRIMARY_ENDPOINT'),
        'token' => env('LOAN_PROVIDER_PRIMARY_TOKEN'),
        'timeout' => (int) env('LOAN_PROVIDER_TIMEOUT', 10),
    ],
];
