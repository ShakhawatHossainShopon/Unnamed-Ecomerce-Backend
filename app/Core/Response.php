<?php

namespace App\Core;

/**
 * Standardized JSON API Response
 */
class Response
{
    /**
     * Send a standardized JSON response and exit
     *
     * @param mixed $data Response payload
     * @param string $message Description message
     * @param int $statusCode HTTP status code (200, 201, 400, 404, etc.)
     * @param bool $success Whether the operation succeeded
     * @return void
     */
    public static function json(
        mixed $data = null,
        string $message = '',
        int $statusCode = 200,
        bool $success = true
    ): void {
        // Clear any previous output buffering to ensure clean JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        $payload = [
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ];

        echo json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        exit;
    }

    /**
     * Send a successful JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return void
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json($data, $message, $statusCode, true);
    }

    /**
     * Send an error JSON response
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $data
     * @return void
     */
    public static function error(string $message = 'An error occurred', int $statusCode = 400, mixed $data = null): void
    {
        self::json($data, $message, $statusCode, false);
    }
}
