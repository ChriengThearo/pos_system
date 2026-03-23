<?php

return [
    'base_url' => env('BAKONG_BASE_URL', 'https://sit-api-bakong.nbc.gov.kh'),
    'token' => env('BAKONG_TOKEN'),
    'merchant_account' => env('BAKONG_MERCHANT_ACCOUNT'),
    'merchant_name' => env('BAKONG_MERCHANT_NAME', 'My Store'),
    'city' => env('BAKONG_CITY', 'PHNOM_PENH'),
    'store_label' => env('BAKONG_STORE_LABEL', 'WEBSTORE'),
    'terminal_label' => env('BAKONG_TERMINAL_LABEL', 'ONLINE'),
    'qr_expire_minutes' => (int) env('BAKONG_QR_EXPIRE_MINUTES', 10),
    'default_currency' => env('BAKONG_DEFAULT_CURRENCY', 'USD'),
];
