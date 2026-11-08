<?php

namespace App\Core;

/**
 * Encapsulates the Incoming HTTP Request
 */
class Request
{
    /**
     * Cached parsed body data
     * @var array|null
     */
    private ?array $body = null;

    /**
     * Cached headers
     * @var array|null
     */
    private ?array $headers = null;

    /**
     * Get the HTTP request method
     *
     * @return string (GET, POST, PUT, PATCH, DELETE, OPTIONS, etc.)
     */
    public function getMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Allow method spoofing via POST parameter '_method' or X-HTTP-Method-Override header
        if ($method === 'POST') {
            $override = $this->getHeader('X-HTTP-Method-Override');
            if (!$override && isset($_POST['_method'])) {
                $override = $_POST['_method'];
            }
            if ($override) {
                $method = strtoupper(trim($override));
            }
        }

        return $method;
    }

    /**
     * Get the sanitized request URI path (relative to the API root)
     * Handles both root and subfolder installations.
     *
     * @return string
     */
    public function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Detect base directory from SCRIPT_NAME to support subfolder deployments (e.g. /my-api/)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = str_replace('\\', '/', dirname($scriptName));

        if ($baseDir !== '/' && !empty($baseDir) && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        }

        // Ensure leading slash and strip trailing slashes (unless it's just '/')
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * Get the parsed JSON request body or POST array
     *
     * @return array
     */
    public function getBody(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }

        $rawInput = file_get_contents('php://input');
        $contentType = $this->getHeader('Content-Type') ?? '';

        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->body = $decoded;
            } else {
                $this->body = $_POST;
            }
        } else {
            $this->body = $_POST;
        }

        return $this->body;
    }

    /**
     * Retrieve a specific input value from the request body
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $body = $this->getBody();

        if ($key === null) {
            return $body;
        }

        return $body[$key] ?? $default;
    }

    /**
     * Get URL query parameters ($_GET)
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    /**
     * Get all parameters (query parameters merged with body data)
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($_GET, $this->getBody());
    }

    /**
     * Get all request headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        if ($this->headers !== null) {
            return $this->headers;
        }

        $headers = [];

        if (function_exists('getallheaders')) {
            $rawHeaders = getallheaders();
            if ($rawHeaders !== false) {
                foreach ($rawHeaders as $key => $val) {
                    $headers[strtolower($key)] = $val;
                }
                $this->headers = $headers;
                return $this->headers;
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[strtolower($headerName)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($key))));
                $headers[strtolower($headerName)] = $value;
            }
        }

        $this->headers = $headers;
        return $this->headers;
    }

    /**
     * Get a specific header value
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $key, ?string $default = null): ?string
    {
        $headers = $this->getHeaders();
        $normalizedKey = strtolower($key);

        return $headers[$normalizedKey] ?? $default;
    }

    /**
     * Get an uploaded file array from $_FILES
     *
     * @param string $key
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Check if a file was uploaded under the given input name
     *
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && is_array($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Get all uploaded files
     *
     * @return array
     */
    public function files(): array
    {
        return $_FILES;
    }
}
