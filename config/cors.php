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
        'api/*',
        'sanctum/csrf-cookie', // utile seulement si tu fais du SPA cookie-based
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000', // CRA
        'http://localhost:5173', // Vite
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',

        // PROD (exemples)
        // 'https://docspace.com',
        // 'https://app.docspace.com',
    ],

    'allowed_origins_patterns' => [
        // Si tu veux accepter n'importe quel sous-domaine en prod:
        // '/^https:\/\/(.+\.)?docspace\.com$/',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-CSRF-TOKEN',
    ],

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
