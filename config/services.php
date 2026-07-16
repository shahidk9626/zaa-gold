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

    'cashfree' => [
        'app_id' => env('CASHFREE_APP_ID'),
        'secret_key' => env('CASHFREE_SECRET_KEY'),
        'env' => env('CASHFREE_ENV', 'sandbox'),
        'base_url' => env('CASHFREE_BASE_URL', env('CASHFREE_ENV') === 'production'
            ? 'https://api.cashfree.com'
            : 'https://sandbox.cashfree.com'),
        'api_version' => env('CASHFREE_API_VERSION', '2023-08-01'),
        'sdk_url' => env('CASHFREE_ENV') === 'production'
            ? 'https://sdk.cashfree.com/js/v3/cashfree.js'
            : 'https://sdk.cashfree.com/js/v3/cashfree.js',
    ],

];
