<?php

namespace App\Middleware;

/**
 * Cross-Origin Resource Sharing (CORS) Middleware
 */
class CorsMiddleware
{
    /**
     * Handle incoming CORS headers and preflight OPTIONS requests
     *
     * @param array $config CORS configuration from config/app.php
     * @return void
     */
    public static function handle(array $config = []): void
    {
        $allowedOrigins = $config['allowed_origins'] ?? ['*'];
        $allowedMethods = $config['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $allowedHeaders = $config['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With'];
        $maxAge         = $config['max_age'] ?? 86400;

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array('*', $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: *');
        } elseif (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
        header("Access-Control-Max-Age: {$maxAge}");

        // Handle preflight OPTIONS request
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
