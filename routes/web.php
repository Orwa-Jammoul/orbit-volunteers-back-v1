<?php
require_once __DIR__ . '/docs.php';

class WebRouter {
    private $routes = [];
    
    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function dispatch($method, $uri) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                call_user_func($route['handler']);
                return;
            }
        }
        
        // Serve API documentation or health check
        if ($uri === '/' || $uri === '/health') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'ok',
                'api_version' => API_VERSION,
                'api_name' => $_ENV['API_NAME'] ?? 'Orbit Volunteers API',
                'timestamp' => date('Y-m-d H:i:s'),
                'documentation' => '/docs'
            ]);
            return;
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Page not found']);
    }
}

$webRouter = new WebRouter();

// Health check
$webRouter->add('GET', '/health', function() {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'healthy']);
});

// API documentation route
$webRouter->add('GET', '/docs', function() {
    ApiDocs::render();
});

// Also handle /docs/ (with trailing slash)
$webRouter->add('GET', '/docs/', function() {
    ApiDocs::render();
});
?>