<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    | Allowed origins. Use CORS_ALLOWED_ORIGINS in .env for production (comma-separated).
    | Default includes production admin + localhost for local dev (admin at localhost:5174).
    */
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'https://shopitadmin-production.up.railway.app,http://localhost:5174,http://localhost:5173,http://127.0.0.1:5174,http://127.0.0.1:5173'
    ))))),

    'allowed_origins_patterns' => [
        // Railway: *.up.railway.app (use # delimiter so preg_match does not treat ^ as delimiter)
        '#^https?://([a-z0-9-]+\\.)*up\\.railway\\.app$#',
        // Local dev: localhost (any port)
        '#^http://localhost(:\\d+)?$#',
        '#^http://127\\.0\\.0\\.1(:\\d+)?$#',
    ],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Accept',
        'Origin',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
