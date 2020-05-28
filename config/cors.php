<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => [
        'GET',
        'POST',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'HEAD'
    ],

    'allowed_origins' => [
        'http://api.deepmedia.dev.com',
        'http://api.deepmedia.com',
        'http://deepmedia.dev.com',
        'http://deepmedia.com',
        'http://127.0.0.1',
        'http://127.0.0.1:4200',
        'http://localhost',
        'http://localhost:4200',
        'http://10.8.125.30'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'X-Authentication-JWT',
        'X-Refresh-JWT',
        'X-Encode-ID',
        'X-TEMP-JWT',
        'Accept',
        'Content-Type'
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
