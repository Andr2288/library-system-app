<?php
/**
 * Вбудований PHP веб-сервер для бібліотечної системи
 * Використання: php server.php [порт]
 * За замовчуванням: http://localhost:8000
 */

class LibraryServer {
    private $host = '127.0.0.1';
    private $port = 8000;
    private $docRoot = __DIR__;

    public function __construct($port = null) {
        if ($port) {
            $this->port = (int)$port;
        }
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

        echo "🚀 Запуск сервера...\n";
        echo "🌐 Сайт доступний: http://{$this->host}:{$this->port}\n";
        echo "🔍 Пошук: http://{$this->host}:{$this->port}/search.php\n";
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

        $requiredFiles = ['index.php', 'config/database.php'];
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                die("❌ Файл $file не знайдено\n");
            }
        }

        echo "✅ Всі вимоги виконано\n";
    }

    public static function handleRequest($uri, $query) {
        if (self::isStaticFile($uri)) {
            return false;
        }

        if (preg_match('/^\/api\//', $uri)) {
            self::handleApiRequest($uri, $query);
            return true;
        }

        if ($uri === '/search.php') {
            $_GET = array_merge($_GET, $query);
            require_once 'search.php';
            return true;
        }

        $_GET = array_merge($_GET, $query);
        require_once 'index.php';
        return true;
    }

    private static function isStaticFile($uri) {
        $extension = pathinfo($uri, PATHINFO_EXTENSION);
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'];
        return in_array(strtolower($extension), $staticExtensions);
    }

    private static function handleApiRequest($uri, $query) {
        header('Content-Type: application/json');

        if ($uri === '/api/stats') {
            $stats = [
                'status' => 'online',
                'timestamp' => date('Y-m-d H:i:s'),
                'server' => 'PHP Built-in Server',
                'version' => PHP_VERSION,
                'available_pages' => [
                    'home' => '/',
                    'search' => '/search.php',
                    'books' => '/?controller=books',
                    'readers' => '/?controller=readers',
                    'loans' => '/?controller=loans'
                ]
            ];
            echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found'], JSON_UNESCAPED_UNICODE);
    }
}

if (php_sapi_name() === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $query = [];
    $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    parse_str($queryString, $query);

    return LibraryServer::handleRequest($uri, $query);
}

if (php_sapi_name() === 'cli') {
    $port = isset($argv[1]) ? $argv[1] : null;
    $server = new LibraryServer($port);
    $server->start();
} else {
    echo "Цей скрипт призначений для запуску з командного рядка.\n";
    echo "Використання: php server.php [порт]\n";
}
?>
