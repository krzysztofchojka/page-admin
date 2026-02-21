<?php
namespace CMS\Core;

class Router {
    protected $routes = [];
    protected $notFoundCallback = null; // Stores the fallback controller

    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    // This was the missing method causing the crash
    public function setNotFoundHandler($callback) {
        $this->notFoundCallback = $callback;
    }

    public function resolve() {
        // 1. Get Path
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // 2. Remove Query String
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        // 3. Subdirectory Fix
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptDir !== '/' && $scriptDir !== '\\' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }

        // 4. Clean Path
        $path = '/' . ltrim($path, '/');

        $method = $_SERVER['REQUEST_METHOD'];
        $callback = $this->routes[$method][$path] ?? false;

        // 5. Route Not Found Logic
        if ($callback === false) {
            // Check if we have a fallback (for dynamic slugs like /incentive-trip)
            if ($this->notFoundCallback) {
                if (is_array($this->notFoundCallback)) {
                    $controller = new $this->notFoundCallback[0]();
                    $method = $this->notFoundCallback[1];
                    // Pass the path (slug) to the controller
                    return $controller->$method($path);
                }
            }
            
            // Real 404 if no fallback handles it
            http_response_code(404);
            echo "404 - Not Found";
            return;
        }

        // 6. Execute Route
        if (is_array($callback)) {
            $controller = new $callback[0]();
            $method = $callback[1];
            return $controller->$method();
        }

        echo call_user_func($callback);
    }
}