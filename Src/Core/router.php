<?php
namespace App\Core;

class Router {
    // Array to store our registered routes
    private $routes = [];

    // Register a GET route
    public function get($route, $callback) {
        $this->routes['GET'][$route] = $callback;
    }

    // Register a POST route
    public function post($route, $callback) {
        $this->routes['POST'][$route] = $callback;
    }

    // Match the incoming request URL to our registered routes
    public function handleRequest($uri, $method) {
        // Strip out query strings if there are any (e.g., /api/test?id=1 -> /api/test)
        $uri = explode('?', $uri)[0];

        // Check if the route exists for this specific HTTP method
        if (isset($this->routes[$method][$uri])) {
            $callback = $this->routes[$method][$uri];
            
            // If it's a valid function/callback, execute it
            if (is_callable($callback)) {
                return call_user_func($callback);
            }
        }

        // If no route matches, return a clean 404 JSON response
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "API Route not found"
        ]);
    }
}