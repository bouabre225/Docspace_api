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
        // PROD (exemples)
        'https://medi-kado.com',
        'https://www.medi-kado.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        // optionnel
    ],

    'max_age' => 0,

    /*
     * Token-based (Bearer): false (recommandé).
     * Cookie-based Sanctum SPA: true.
     */
    'supports_credentials' => false,
];
