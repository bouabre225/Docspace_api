<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Pour une API token-based (Bearer), on peut laisser supports_credentials=false.
    | Si plus tard tu passes en cookie-based Sanctum SPA, tu mettras true + CSRF.
    |
    */

    'paths' => [
        'api/*'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // PROD
        'https://docspace.bj',
        'https://www.docspace.bj',
        // Staging + dev local
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        // optionnel
    ],

    'max_age' => 86400,

    /*
     * Token-based (Bearer): false (recommandé).
     * Cookie-based Sanctum SPA: true.
     */
    'supports_credentials' => false,
];

