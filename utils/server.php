<?php
/**
 * Вбудований PHP веб-сервер для бібліотечної системи API
 * Використання: php server.php [порт]
 * За замовчуванням: http://localhost:8000
 */

class LibraryApiServer {
    private $host = '127.0.0.1';
    private $port = 8000;
    private $docRoot;

    public function __construct($port = null) {
        if ($port) {
            $this->port = (int)$port;
        }
        $this->docRoot = dirname(__DIR__); // Корінь проекту
    }

    public function start() {
        $this->checkRequirements();

        $command = sprintf(
            'php -S %s:%d -t "%s" "%s"',
            $this->host,
            $this->port,
            $this->docRoot,
            __FILE__
        );

        echo "🚀 Запуск API сервера...\n";
        echo "🌐 API доступне: http://{$this->host}:{$this->port}/api/\n";
        echo "📚 Книги: http://{$this->host}:{$this->port}/api/books\n";
        echo "👥 Читачі: http://{$this->host}:{$this->port}/api/readers\n";
        echo "📋 Категорії: http://{$this->host}:{$this->port}/api/categories\n";
        echo "📝 Видачі: http://{$this->host}:{$this->port}/api/loans\n";
        echo "⏹️  Для зупинки натисніть Ctrl+C\n\n";

        passthru($command);
    }

    private function checkRequirements() {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            die("❌ Потрібен PHP 5.4.0 або новіший. Поточна версія: " . PHP_VERSION . "\n");
        }

        if (!extension_loaded('pdo_mysql')) {
            die("❌ Розширення PDO MySQL не встановлено\n");
        }

        $requiredFiles = ['api/index.php', 'config/database.php'];
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                die("❌ Файл $file не знайдено\n");
            }
        }

        echo "✅ Всі вимоги виконано\n";
    }

    public static function handleRequest($uri, $query) {
        // Якщо це API запит
        if (preg_match('/^\/api\//', $uri)) {
            $_GET = array_merge($_GET, $query);
            require_once 'api/index.php';
            return true;
        }

        // Статичні файли
        if (self::isStaticFile($uri)) {
            return false; // Дозволити PHP серверу обробити
        }

        // Головна сторінка - показати API документацію
        if ($uri === '/' || $uri === '/index.php') {
            self::showApiDocs();
            return true;
        }

        // 404 для всього іншого
        http_response_code(404);
        echo json_encode([
            'error' => 'Not Found',
            'message' => 'API доступне за адресою /api/',
            'endpoints' => [
                'books' => '/api/books',
                'readers' => '/api/readers',
                'categories' => '/api/categories',
                'loans' => '/api/loans'
            ]
        ], JSON_UNESCAPED_UNICODE);
        return true;
    }

    private static function isStaticFile($uri) {
        $extension = pathinfo($uri, PATHINFO_EXTENSION);
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'];
        return in_array(strtolower($extension), $staticExtensions);
    }

    private static function showApiDocs() {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Library System API</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .endpoint { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .method { font-weight: bold; color: #007bff; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 Library System API</h1>
        <p>Вітаємо в API бібліотечної системи! Нижче доступні endpoints:</p>

        <div class="endpoint">
            <div class="method">GET</div>
            <a href="/api/books">/api/books</a> - Список всіх книг
        </div>

        <div class="endpoint">
            <div class="method">GET</div>
            <a href="/api/readers">/api/readers</a> - Список всіх читачів
        </div>

        <div class="endpoint">
            <div class="method">GET</div>
            <a href="/api/categories">/api/categories</a> - Список всіх категорій
        </div>

        <div class="endpoint">
            <div class="method">GET</div>
            <a href="/api/loans">/api/loans</a> - Список всіх видач
        </div>

        <div class="endpoint">
            <div class="method">GET</div>
            <a href="/api/loans/active">/api/loans/active</a> - Активні видачі
        </div>

        <div class="endpoint">
            <div class="method">GET</div>
            <a href="/api/loans/overdue">/api/loans/overdue</a> - Прострочені видачі
        </div>

        <h3>💡 Приклад використання:</h3>
        <pre>fetch("/api/books")
  .then(response => response.json())
  .then(data => console.log(data));</pre>

        <p><strong>Документація:</strong> Повна API документація доступна в файлі README.md</p>
        <p><strong>Тести:</strong> Імпортуйте Library_API_Tests.postman_collection.json у Postman</p>
    </div>
</body>
</html>';
    }
}

if (php_sapi_name() === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $query = [];
    $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    parse_str($queryString, $query);

    return LibraryApiServer::handleRequest($uri, $query);
}

if (php_sapi_name() === 'cli') {
    $port = isset($argv[1]) ? $argv[1] : null;
    $server = new LibraryApiServer($port);
    $server->start();
} else {
    echo "Цей скрипт призначений для запуску з командного рядка.\n";
    echo "Використання: php server.php [порт]\n";
}
?>