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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'n8n' => [
        'confirmacion_webhook_url' => env('N8N_CONFIRMACION_WEBHOOK_URL'),
        'pagos_webhook_url' => env('URL_WEBHOOK_N8N'),
    ],

    'tours_api' => [
        'forzar_sandbox' => env('TOURS_API_FORZAR_SANDBOX', true),
    ],

    'exchange_rate' => [
        'url' => env('EXCHANGE_RATE_API_URL', 'https://open.er-api.com/v6/latest/USD'),
        'cache_ttl_horas' => env('EXCHANGE_RATE_CACHE_TTL_HORAS', 6),
    ],

    'seed_users' => [
        'admin' => [
            'email' => env('SEED_ADMIN_EMAIL', 'admin@attitours.com'),
            'password' => env('SEED_ADMIN_PASSWORD'),
        ],
        'proveedor' => [
            'email' => env('SEED_PROVEEDOR_EMAIL', 'proveedor@attitours.com'),
            'password' => env('SEED_PROVEEDOR_PASSWORD'),
        ],
        'cliente' => [
            'email' => env('SEED_CLIENTE_EMAIL', 'cliente@attitours.com'),
            'password' => env('SEED_CLIENTE_PASSWORD'),
        ],
    ],

];
