<?php

/**
 * Application Configuration
 */

return [
    'name' => 'Native PHP REST API Framework',
    'env' => 'production', // 'development' or 'production'
    'debug' => false,       // set to true during local debugging, false in production
    'timezone' => 'UTC',

    // Cross-Origin Resource Sharing (CORS) Settings
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'max_age' => 86400,
    ],
];
