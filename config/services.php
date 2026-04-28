<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cj_dropshipping' => [
        'base_url' => env('CJ_BASE_URL'),
        'api_token' => env('CJ_API_TOKEN'),
        'product_endpoint' => env('CJ_PRODUCT_ENDPOINT', '/api2.0/v1/product/list'),
        'token_header' => env('CJ_TOKEN_HEADER', 'CJ-Access-Token'),
        'query_key' => env('CJ_QUERY_KEY', 'keywords'),
        'timeout' => (int) env('CJ_TIMEOUT', 15),
        'page_size' => (int) env('CJ_PAGE_SIZE', 10),
        'cache_ttl' => (int) env('CJ_CACHE_TTL', 120),
        'max_image_bytes' => (int) env('CJ_MAX_IMAGE_BYTES', 4194304),
        'image_cache_ttl' => (int) env('CJ_IMAGE_CACHE_TTL', 86400),
        'max_products' => (int) env('CJ_MAX_PRODUCTS', 1000),
    ],

];
