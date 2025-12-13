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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://192.168.18.42:8000',
        'http://192.168.18.34:3030',
        'http://192.168.18.253:3030',
        'http://localhost:3000',
        'http://192.168.56.1:3030',
        'http://10.214.57.58:3030',
        'http://192.168.110.222:3030',
        'http://localhost:3030',
        'http://localhost:4121',
        'http://localhost:4212',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'https://localhost:3000',
        'https://127.0.0.1:3000',
        'https://staging.m-health.id',
    ],

    'allowed_origins_patterns' => [
        '*://*.ngrok-free.app',
        '*://*.ngrok.app',
        '*://localhost:*',
        '*://127.0.0.1:*',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'ngrok-skip-browser-warning',
        'Cache-Control',
        'Pragma',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];
