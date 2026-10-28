<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
        'cart_template' => env('WHATSAPP_CART_TEMPLATE', 'cart_reminder'),
        'checkout_template' => env('WHATSAPP_CHECKOUT_TEMPLATE'),
    ],

    // Tracking ids and server tokens. Values saved in the admin (Settings → Tracking) take priority.
    'gtm' => [
        'container_id' => env('GTM_CONTAINER_ID'),
    ],

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
        'capi_token' => env('META_CAPI_TOKEN'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),
        'graph_version' => env('META_GRAPH_VERSION', 'v24.0'),
    ],

    'tiktok' => [
        'pixel_id' => env('TIKTOK_PIXEL_ID'),
        'access_token' => env('TIKTOK_ACCESS_TOKEN'),
        'test_event_code' => env('TIKTOK_TEST_EVENT_CODE'),
    ],

    'snap' => [
        'pixel_id' => env('SNAP_PIXEL_ID'),
        'capi_token' => env('SNAP_CAPI_TOKEN'),
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
    ],

    'myfatoorah' => [
        'key' => env('MYFATOORAH_API_KEY'),
        'base_url' => env('MYFATOORAH_BASE_URL', 'https://apitest.myfatoorah.com'),
    ],

];
