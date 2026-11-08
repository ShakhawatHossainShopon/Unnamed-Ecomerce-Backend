<?php

/**
 * Native PHP REST API Framework
 * 
 * Single API Entry Point.
 * Handles autoloading, configuration, CORS, auto-route registration,
 * and request dispatching.
 */

declare(strict_types=1);

// 1. Load the native PSR-4 autoloader
require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// 2. Load application configuration
$appConfig = require __DIR__ . '/config/app.php';
$isDebug = (bool)($appConfig['debug'] ?? false);

// Set timezone
if (!empty($appConfig['timezone'])) {
    date_default_timezone_set($appConfig['timezone']);
}

// Global Exception and Error Handler
set_exception_handler(function (\Throwable $e) use ($isDebug) {
    error_log('Unhandled Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    $debugData = null;
    if ($isDebug) {
        $debugData = [
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => explode("\n", $e->getTraceAsString()),
        ];
    }

    \App\Core\Response::error(
        $isDebug ? $e->getMessage() : 'Internal Server Error',
        500,
        $debugData
    );
});

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Apply Cross-Origin Resource Sharing (CORS) Middleware
\App\Middleware\CorsMiddleware::handle($appConfig['cors'] ?? []);

// 3. Initialize the database
try {
    \App\Core\Database::getConnection();
} catch (\Throwable $e) {
    error_log('Initial DB check: ' . $e->getMessage());
    // If debug is on, report DB configuration error; otherwise generic message
    \App\Core\Response::error(
        $isDebug ? 'Database connection error: ' . $e->getMessage() : 'Database connection error. Please verify config/database.php settings.',
        500
    );
}

// 4. Initialize the universal router
$router = new \App\Core\Router();

// 5. Automatically scan app/Routes/ and 6. Load every PHP route file
$routesDir = __DIR__ . '/app/Routes';
if (is_dir($routesDir)) {
    $routeFiles = glob($routesDir . '/*.php');
    if ($routeFiles !== false) {
        sort($routeFiles);
        foreach ($routeFiles as $routeFile) {
            // 7. Register all routes by including the route file with $router in scope
            require $routeFile;
        }
    }
}

// 8. Dispatch the incoming HTTP request
$request = new \App\Core\Request();
$router->dispatch($request);
