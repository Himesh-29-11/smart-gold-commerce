<?php

return [
    'currency' => env('COMMERCE_CURRENCY', 'INR'),
    'free_delivery_threshold' => (float) env('FREE_DELIVERY_THRESHOLD', 50000),
    'delivery_charge' => (float) env('DELIVERY_CHARGE', 499),
    'payment_provider' => env('PAYMENT_PROVIDER', 'razorpay'),
    'invoice_prefix' => env('INVOICE_PREFIX', 'SGC'),
    'support_email' => env('SUPPORT_EMAIL', 'trustedgolds@gmail.com'),
    'support_phone' => env('SUPPORT_PHONE', '+91 95123 48850'),
];
