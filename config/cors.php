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

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'X-Authentication-JWT',
        'X-Refresh-JWT',
        'X-Encode-ID',
        'X-Temp-JWT',
        'X-Banished',
        'Accept',
        'Content-Type'
    ],

    'exposed_headers' => [
        'X-Authentication-JWT',
        'X-Refresh-JWT',
        'X-Encode-ID',
        'X-Temp-JWT',
        'X-Banished'
    ],

    'max_age' => 0,

    'supports_credentials' => false,

];
