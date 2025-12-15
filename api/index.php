<?php
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config/database.php';

class ApiRouter {
    private $routes = [];

    public function get($path, $controller, $method) {
        $this->routes['GET'][$path] = [$controller, $method];
    }

    public function post($path, $controller, $method) {
        $this->routes['POST'][$path] = [$controller, $method];
    }

    public function put($path, $controller, $method) {
        $this->routes['PUT'][$path] = [$controller, $method];
    }

    public function delete($path, $controller, $method) {
        $this->routes['DELETE'][$path] = [$controller, $method];
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove /api prefix if present
        $uri = preg_replace('#^/api#', '', $uri);

        // Handle parameterized routes (e.g., /books/123)
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{[^}]+\}/', '(\d+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $controller = new $handler[0]();
                $method = $handler[1];

                // Pass ID parameter if exists
                if (isset($matches[1])) {
                    return $controller->$method($matches[1]);
                } else {
                    return $controller->$method();
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
    }
}

// Initialize router
$router = new ApiRouter();

// Books routes
require_once 'controllers/BooksApiController.php';
$router->get('/books', 'BooksApiController', 'index');
$router->get('/books/{id}', 'BooksApiController', 'show');
$router->post('/books', 'BooksApiController', 'create');
$router->put('/books/{id}', 'BooksApiController', 'update');
$router->delete('/books/{id}', 'BooksApiController', 'delete');

// Readers routes
require_once 'controllers/ReadersApiController.php';
$router->get('/readers', 'ReadersApiController', 'index');
$router->get('/readers/{id}', 'ReadersApiController', 'show');
$router->post('/readers', 'ReadersApiController', 'create');
$router->put('/readers/{id}', 'ReadersApiController', 'update');
$router->delete('/readers/{id}', 'ReadersApiController', 'delete');

// Loans routes
require_once 'controllers/LoansApiController.php';
$router->get('/loans', 'LoansApiController', 'index');
$router->get('/loans/{id}', 'LoansApiController', 'show');
$router->get('/loans/active', 'LoansApiController', 'active');
$router->get('/loans/overdue', 'LoansApiController', 'overdue');
$router->post('/loans', 'LoansApiController', 'create');
$router->put('/loans/{id}', 'LoansApiController', 'update');
$router->delete('/loans/{id}', 'LoansApiController', 'delete');

// Categories routes
require_once 'controllers/CategoriesApiController.php';
$router->get('/categories', 'CategoriesApiController', 'index');
$router->get('/categories/{id}', 'CategoriesApiController', 'show');
$router->get('/categories/popular', 'CategoriesApiController', 'popular');
$router->post('/categories', 'CategoriesApiController', 'create');
$router->put('/categories/{id}', 'CategoriesApiController', 'update');
$router->delete('/categories/{id}', 'CategoriesApiController', 'delete');

// Handle the request
$router->handleRequest();
?>