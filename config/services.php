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

    /*
    |--------------------------------------------------------------------------
    | NAVER Cloud Platform Services
    |--------------------------------------------------------------------------
    |
    | Configuration for NAVER Cloud Platform APIs
    | Documentation: https://guide.ncloud-docs.com/docs/en
    |
    */

    'naver' => [
        // NAVER Maps API - POI search, geocoding, distance
        'maps' => [
            'client_id' => env('NAVER_MAPS_CLIENT_ID'),
            'client_secret' => env('NAVER_MAPS_CLIENT_SECRET'),
            'base_url' => 'https://maps.apigw.ntruss.com',
            'enabled' => env('NAVER_MAPS_ENABLED', true),
        ],

        // NAVER Local Search API - Place search
        'local_search' => [
            'client_id' => env('NAVER_LOCAL_SEARCH_CLIENT_ID', env('NAVER_MAPS_CLIENT_ID')),
            'client_secret' => env('NAVER_LOCAL_SEARCH_CLIENT_SECRET', env('NAVER_MAPS_CLIENT_SECRET')),
            'enabled' => env('NAVER_LOCAL_SEARCH_ENABLED', true),
        ],

        // Papago Translation API
    'papago' => [
        'client_id' => env('NAVER_PAPAGO_CLIENT_ID'),
        'client_secret' => env('NAVER_PAPAGO_CLIENT_SECRET'),
        'base_url' => 'https://papago.apigw.ntruss.com',
        'enabled' => env('NAVER_PAPAGO_ENABLED', true),
    ],        // Clova OCR API
        'ocr' => [
            'url' => env('NAVER_CLOVA_OCR_URL'),
            'secret_key' => env('NAVER_CLOVA_OCR_SECRET_KEY'),
            'enabled' => env('NAVER_OCR_ENABLED', true),
        ],

        // Clova Speech API (STT)
        'speech' => [
            'url' => env('NAVER_CLOVA_SPEECH_URL'),
            'secret_key' => env('NAVER_CLOVA_SPEECH_SECRET_KEY'),
            'enabled' => env('NAVER_SPEECH_ENABLED', true),
        ],

        // Search Trend API (DataLab) - Keyword search volume trends
        'search_trend' => [
            'client_id' => env('NAVER_SEARCH_TREND_CLIENT_ID'),
            'client_secret' => env('NAVER_SEARCH_TREND_CLIENT_SECRET'),
            'base_url' => 'https://naveropenapi.apigw.ntruss.com',
            'enabled' => env('NAVER_SEARCH_TREND_ENABLED', true),
        ],

        // OAuth (Login with NAVER)
        'oauth' => [
            'client_id' => env('NAVER_CLIENT_ID'),
            'client_secret' => env('NAVER_CLIENT_SECRET'),
            'redirect' => env('NAVER_REDIRECT_URI'),
        ],

        // Common settings
        'timeout' => env('NAVER_API_TIMEOUT', 30),
        'retry_times' => env('NAVER_API_RETRY_TIMES', 3),
        'retry_sleep' => env('NAVER_API_RETRY_SLEEP', 1000),
    ],

];
