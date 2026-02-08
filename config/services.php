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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'paystack' => [
        'mode' => env('PAYSTACK_MODE', 'live'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'live_secret_key' => env('PAYSTACK_LIVE_SECRET_KEY'),
        'live_public_key' => env('PAYSTACK_LIVE_PUBLIC_KEY'),
        'test_secret_key' => env('PAYSTACK_TEST_SECRET_KEY'),
        'test_public_key' => env('PAYSTACK_TEST_PUBLIC_KEY'),
    ],

    'qoreid' => [
        'mode' => env('QOREID_MODE', 'live'),
        'base_url' => env('QOREID_BASE_URL', 'https://api.qoreid.com'),
        'production_client_id' => env('QOREID_LIVE_CLIENT_ID'),
        'production_secret_key' => env('QOREID_LIVE_SECRET_KEY'),
        'test_client_id' => env('QOREID_TEST_CLIENT_ID'),
        'test_secret_key' => env('QOREID_TEST_SECRET_KEY'),
    ],

    'ebulksms' => [
        'base_url' => env('EBULKSMS_BASE_URL', 'https://api.ebulksms.com/sendsms.json'),
        'username' => env('EBULKSMS_USERNAME'),
        'api_key' => env('EBULKSMS_API_KEY'),
        'sender' => env('EBULKSMS_SENDER', 'ShopittPlus'),
        'dndsender' => env('EBULKSMS_DNDSENDER', 0),
        'country_code' => env('EBULKSMS_COUNTRY_CODE', '234'),
    ],
];