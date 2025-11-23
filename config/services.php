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
    | Social Login (OAuth)
    |--------------------------------------------------------------------------
    |
    | Configuration for social login providers (Google, NAVER, etc.)
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | NAVER Developers (openapi.naver.com)
    |--------------------------------------------------------------------------
    |
    | Configuration for NAVER Developers APIs (Client ID/Secret based)
    | Documentation: https://developers.naver.com/
    | Registration: https://developers.naver.com/apps/#/register
    |
    */

    'naver_developers' => [
        // NAVER Local Search API - Place search (openapi.naver.com)
        'local_search' => [
            'client_id' => env('NAVER_DEVELOPERS_CLIENT_ID'),
            'client_secret' => env('NAVER_DEVELOPERS_CLIENT_SECRET'),
            'enabled' => env('NAVER_LOCAL_SEARCH_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | NAVER Cloud Platform Services
    |--------------------------------------------------------------------------
    |
    | Configuration for NAVER Cloud Platform APIs (NCP - ntruss.com)
    | Documentation: https://guide.ncloud-docs.com/docs/en
    | Console: https://console.ncloud.com/
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

        // Green-Eye API (Content Moderation) - Adult/Violence detection
        'greeneye' => [
            'url' => env('NAVER_GREENEYE_URL'),
            'secret_key' => env('NAVER_GREENEYE_SECRET_KEY'),
            'enabled' => env('NAVER_GREENEYE_ENABLED', true),
            'timeout' => env('NAVER_GREENEYE_TIMEOUT', 30),
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

    /*
    |--------------------------------------------------------------------------
    | Amadeus API Services
    |--------------------------------------------------------------------------
    |
    | Configuration for Amadeus Travel APIs
    | Documentation: https://developers.amadeus.com/
    | Registration: https://developers.amadeus.com/my-apps
    |
    */

    'amadeus' => [
        'api_key' => env('AMADEUS_API_KEY'),
        'api_secret' => env('AMADEUS_API_SECRET'),
        'base_url' => env('AMADEUS_BASE_URL', 'https://test.api.amadeus.com/v1'),
        'token_url' => env('AMADEUS_TOKEN_URL', 'https://test.api.amadeus.com/v1/security/oauth2/token'),
        'timeout' => env('AMADEUS_TIMEOUT', 30),
        'retry_times' => env('AMADEUS_RETRY_TIMES', 3),
        'retry_sleep' => env('AMADEUS_RETRY_SLEEP', 1000),
        'enabled' => env('AMADEUS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SerpAPI Services
    |--------------------------------------------------------------------------
    |
    | Configuration for SerpAPI (Google Search Results API)
    | Documentation: https://serpapi.com/
    | Registration: https://serpapi.com/users/sign_up
    |
    */

    'serpapi' => [
        'api_key' => env('SERPAPI_API_KEY'),
        'base_url' => env('SERPAPI_BASE_URL', 'https://serpapi.com/search.json'),
        'timeout' => env('SERPAPI_TIMEOUT', 30),
        'retry_times' => env('SERPAPI_RETRY_TIMES', 3),
        'retry_sleep' => env('SERPAPI_RETRY_SLEEP', 1000),
        'enabled' => env('SERPAPI_ENABLED', true),
    ],

];
