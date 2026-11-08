<?php

namespace App\Core;

/**
 * Universal REST API Router
 * 
 * Supports dynamic routing, parameter extraction ({id}),
 * HTTP method detection, 404 and 405 handling, and controller dispatching.
 */
class Router
{
    /**
     * Registered routes grouped by HTTP method
     * @var array<string, array<int, array{path: string, pattern: string, handler: mixed}>>
     */
    private array $routes = [];

    /**
     * Register a GET route
     *
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function get(string $path, mixed $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     *
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function post(string $path, mixed $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     *
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function put(string $path, mixed $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a PATCH route
     *
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function patch(string $path, mixed $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register a DELETE route
     *
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function delete(string $path, mixed $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register an OPTIONS route
     *
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function options(string $path, mixed $handler): self
    {
        return $this->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * Register a route with a specific HTTP method
     *
     * @param string $method
     * @param string $path
     * @param mixed $handler
     * @return self
     */
    public function addRoute(string $method, string $path, mixed $handler): self
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        // Convert path with {param} placeholders to regex pattern
        // e.g. /api/users/{id} -> #^/api/users/(?P<id>[^/]+)$#
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $normalizedPath);
        $regex = '#^' . $pattern . '$#';

        $this->routes[$normalizedMethod][] = [
            'path'    => $normalizedPath,
            'pattern' => $regex,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Dispatch the current HTTP request to the matching controller/action
     *
     * @param Request|null $request
     * @return void
     */
    public function dispatch(?Request $request = null): void
    {
        $request = $request ?? new Request();
        $method = $request->getMethod();
        $path = $request->getPath();

        // 1. Check for exact or regex match on the current method
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $path, $matches)) {
                    // Extract named parameters only
                    $params = [];
                    foreach ($matches as $key => $val) {
                        if (!is_int($key)) {
                            $params[$key] = $val;
                        }
                    }

                    $this->executeHandler($route['handler'], $params, $request);
                    return;
                }
            }
        }

        // 2. If no match found for current method, check if path exists under other HTTP methods (405 Check)
        $allowedMethods = [];
        foreach ($this->routes as $otherMethod => $routes) {
            if ($otherMethod === $method) {
                continue;
            }
            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $path)) {
                    $allowedMethods[] = $otherMethod;
                    break;
                }
            }
        }

        if (!empty($allowedMethods)) {
            // Handle automatic OPTIONS preflight if not explicitly registered
            if ($method === 'OPTIONS') {
                $allowedMethods[] = 'OPTIONS';
                $methodsList = implode(', ', array_unique($allowedMethods));
                header("Allow: {$methodsList}");
                header("Access-Control-Allow-Methods: {$methodsList}");
                http_response_code(204);
                exit;
            }

            $methodsList = implode(', ', array_unique($allowedMethods));
            header("Allow: {$methodsList}");
            Response::error(
                "Method {$method} not allowed for {$path}. Allowed methods: {$methodsList}",
                405
            );
            return;
        }

        // 3. No match found for any method -> 404 Not Found
        Response::error("Endpoint '{$path}' not found", 404);
    }

    /**
     * Execute the route handler (Controller action array or Closure)
     *
     * @param mixed $handler
     * @param array $params
     * @param Request $request
     * @return void
     */
    private function executeHandler(mixed $handler, array $params, Request $request): void
    {
        // Case 1: Callable Closure or Function
        if (is_callable($handler) && !is_array($handler)) {
            call_user_func_array($handler, array_values($params));
            return;
        }

        // Case 2: Array [ControllerClass, 'methodName']
        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;

            if (!class_exists($controllerClass)) {
                Response::error("Controller '{$controllerClass}' not found", 500);
                return;
            }

            // Instantiate controller with the current Request
            $controller = new $controllerClass($request);

            if (!method_exists($controller, $method)) {
                Response::error("Action '{$method}' not found on '{$controllerClass}'", 500);
                return;
            }

            // Call controller action with route params
            call_user_func_array([$controller, $method], array_values($params));
            return;
        }

        Response::error("Invalid route handler configuration", 500);
    }

    /**
     * Get all registered routes (useful for debugging/testing)
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
