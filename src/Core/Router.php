<?php
namespace CMS\Core;

class Router {
    protected $routes = [];
    protected $notFoundCallback = null;

    public function get($path, $callback, $middlewares = []) {
        $this->routes['GET'][$path] = ['callback' => $callback, 'middlewares' => $middlewares];
    }

    public function post($path, $callback, $middlewares = []) {
        $this->routes['POST'][$path] = ['callback' => $callback, 'middlewares' => $middlewares];
    }

    public function setNotFoundHandler($callback) {
        $this->notFoundCallback = $callback;
    }

    public function resolve() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) $path = substr($path, 0, $position);

        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptDir !== '/' && $scriptDir !== '\\' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        }
        $path = '/' . ltrim($path, '/');
        $method = $_SERVER['REQUEST_METHOD'];

        // Traktuj zapytania HEAD (np. z testów lub botów) tak samo jak GET
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        
        $route = $this->routes[$method][$path] ?? false;

        if ($route === false) {
            if ($this->notFoundCallback) {
                if (is_array($this->notFoundCallback)) {
                    $controller = new $this->notFoundCallback[0]();
                    $m = $this->notFoundCallback[1];
                    return $controller->$m($path);
                }
            }
            http_response_code(404);
            echo "404 - Not Found";
            return;
        }

        // Uruchomienie Middlewares
        if (!empty($route['middlewares'])) {
            foreach ($route['middlewares'] as $middlewareClass) {
                $middleware = new $middlewareClass();
                $middleware->handle();
            }
        }

        $callback = $route['callback'];
        if (is_array($callback)) {
            $controller = new $callback[0]();
            $m = $callback[1];
            return $controller->$m();
        }

        echo call_user_func($callback);
    }
}