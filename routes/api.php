<?php
class Router
{
    private $routes = [];

    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                $this->executeHandler($route['handler']);
                return;
            }

            // Handle routes with parameters
            $pattern = preg_replace('/\{[a-z]+\}/', '([^/]+)', $route['path']);
            if ($route['method'] === $method && preg_match("#^$pattern$#", $uri, $matches)) {
                array_shift($matches);
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        Response::notFound("Endpoint not found");
    }

    private function executeHandler($handler, $params = [])
    {
        if (is_array($handler)) {
            $controller = new $handler[0]();
            $method = $handler[1];
            call_user_func_array([$controller, $method], $params);
        } elseif (is_callable($handler)) {
            call_user_func_array($handler, $params);
        }
    }
}

$router = new Router();

// Auth Routes
$router->add('POST', '/auth/register', [AuthController::class, 'register']);
$router->add('POST', '/auth/login', [AuthController::class, 'login']);
$router->add('GET', '/auth/me', [AuthController::class, 'me']);
$router->add('POST', '/auth/logout', [AuthController::class, 'logout']);

// User Routes
$router->add('GET', '/users', [UserController::class, 'index']);
$router->add('GET', '/users/{id}', [UserController::class, 'show']);
$router->add('PUT', '/users/{id}', [UserController::class, 'update']);
$router->add('DELETE', '/users/{id}', [UserController::class, 'delete']);
$router->add('PUT', '/users/{id}/status', [UserController::class, 'updateStatus']);

// Blog Routes
$router->add('GET', '/blogs', [BlogController::class, 'index']);
$router->add('GET', '/blogs/{id}', [BlogController::class, 'show']);
$router->add('POST', '/blogs', [BlogController::class, 'create']);
$router->add('PUT', '/blogs/{id}', [BlogController::class, 'update']);
$router->add('DELETE', '/blogs/{id}', [BlogController::class, 'delete']);
$router->add('GET', '/blog-categories', [BlogController::class, 'categories']);
$router->add('PUT', '/blogs/{id}/status', [BlogController::class, 'updateStatus']);
$router->add('POST', '/categories', [BlogController::class, 'createCategory']);
$router->add('PUT', '/categories/{id}', [BlogController::class, 'updateCategory']);
$router->add('DELETE', '/categories/{id}', [BlogController::class, 'deleteCategory']);

// Project Routes
$router->add('GET', '/projects', [ProjectController::class, 'index']);
$router->add('GET', '/projects/{id}', [ProjectController::class, 'show']);
$router->add('POST', '/projects', [ProjectController::class, 'create']);
$router->add('PUT', '/projects/{id}', [ProjectController::class, 'update']);
$router->add('DELETE', '/projects/{id}', [ProjectController::class, 'delete']);
$router->add('GET', '/project-categories', [ProjectController::class, 'categories']);

// Comment Routes
$router->add('GET', '/comments/{type}/{id}', [CommentController::class, 'index']);
$router->add('POST', '/comments', [CommentController::class, 'create']);
$router->add('PUT', '/comments/{id}/status', [CommentController::class, 'updateStatus']);
$router->add('DELETE', '/comments/{id}', [CommentController::class, 'delete']);

// Message Routes
$router->add('POST', '/messages', [MessageController::class, 'create']);
$router->add('GET', '/messages', [MessageController::class, 'index']);
$router->add('GET', '/messages/{id}', [MessageController::class, 'show']);
$router->add('PUT', '/messages/{id}/status', [MessageController::class, 'updateStatus']);
$router->add('DELETE', '/messages/{id}', [MessageController::class, 'delete']);

// Team Routes
$router->add('GET', '/teams', [TeamController::class, 'index']);
$router->add('GET', '/teams/{id}', [TeamController::class, 'show']);
$router->add('POST', '/teams', [TeamController::class, 'create']);
$router->add('PUT', '/teams/{id}', [TeamController::class, 'update']);
$router->add('DELETE', '/teams/{id}', [TeamController::class, 'delete']);
$router->add('POST', '/teams/{id}/members', [TeamController::class, 'addMember']);
$router->add('DELETE', '/teams/{id}/members/{userId}', [TeamController::class, 'removeMember']);

// Block Routes
$router->add('GET', '/blocks', [BlockController::class, 'index']);
$router->add('GET', '/blocks/{id}', [BlockController::class, 'show']);
$router->add('POST', '/blocks', [BlockController::class, 'create']);
$router->add('PUT', '/blocks/{id}', [BlockController::class, 'update']);
$router->add('DELETE', '/blocks/{id}', [BlockController::class, 'delete']);
$router->add('GET', '/blocks/category/{slug}', [BlockController::class, 'getByCategory']);

// Media Routes
$router->add('POST', '/upload', [MediaController::class, 'upload']);
$router->add('GET', '/albums', [MediaController::class, 'getAlbums']);
$router->add('POST', '/albums', [MediaController::class, 'createAlbum']);
$router->add('POST', '/albums/{id}/images', [MediaController::class, 'addImage']);
