<?php
/**
 * Консольний застосунок для управління базою даних
 * Бібліотечна система
 *
 * Використання: php setup.php
 */

class DatabaseSetup {
    private $host = 'localhost';
    private $dbname = 'library_db';
    private $rootUser = 'root';
    private $rootPassword = '';

    public function run() {
        echo "\n=== Налаштування бази даних: Бібліотечна система ===\n\n";

        while (true) {
            $this->showMenu();
            $choice = $this->getInput("Оберіть опцію (1-6): ");

            switch ($choice) {
                case '1':
                    $this->checkDatabase();
                    break;
                case '2':
                    $this->dropDatabase();
                    break;
                case '3':
                    $this->createDatabase();
                    break;
                case '4':
                    $this->fillDatabase();
                    break;
                case '5':
                    $this->fullInitialization();
                    break;
                case '6':
                    echo "\nДо побачення!\n";
                    exit(0);
                default:
                    echo "\nНевірний вибір. Спробуйте ще раз.\n";
            }

            echo "\nНатисніть Enter для продовження...";
            fgets(STDIN);
            $this->clearScreen();
        }
    }

    private function showMenu() {
        echo "Доступні опції:\n";
        echo "1. Перевірити стан бази даних\n";
        echo "2. Видалити базу даних\n";
        echo "3. Створити базу даних\n";
        echo "4. Заповнити тестовими даними\n";
        echo "5. Повна ініціалізація (створити + заповнити)\n";
        echo "6. Вийти\n\n";
    }

    private function getInput($prompt) {
        echo $prompt;
        return trim(fgets(STDIN));
    }

    private function clearScreen() {
        system('clear || cls');
        echo "\n=== Налаштування бази даних: Бібліотечна система ===\n\n";
    }

    private function getDatabaseConnection($includeDb = true) {
        try {
            $dsn = "mysql:host={$this->host}" . ($includeDb ? ";dbname={$this->dbname}" : "");
            $pdo = new PDO($dsn, $this->rootUser, $this->rootPassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            return null;
        }
    }

    private function checkDatabase() {
        echo "\n=== Перевірка стану бази даних ===\n";

        echo "Перевірка підключення до MySQL...";
        $pdo = $this->getDatabaseConnection(false);
        if (!$pdo) {
            echo " ❌ ПОМИЛКА\n";
            echo "Не вдалося підключитися до MySQL сервера\n";
            return;
        }
        echo " ✅ OK\n";

        echo "Перевірка існування БД library_db...";
        try {
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute(['library_db']);
            $dbExists = $stmt->fetch();

            if (!$dbExists) {
                echo " ❌ НЕ ІСНУЄ\n";
                return;
            }
            echo " ✅ ІСНУЄ\n";

            echo "Підключення до БД library_db...";
            $pdo = $this->getDatabaseConnection(true);
            if (!$pdo) {
                echo " ❌ ПОМИЛКА\n";
                return;
            }
            echo " ✅ OK\n";

            $tables = ['readers', 'books', 'categories', 'loans'];
            echo "\nПеревірка таблиць:\n";

            foreach ($tables as $table) {
                echo sprintf("  %-12s", $table . ":");
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
                    $stmt->execute();
                    $count = $stmt->fetch()['count'];
                    echo " ✅ існує ($count записів)\n";
                } else {
                    echo " ❌ відсутня\n";
                }
            }

            echo sprintf("  %-12s", "view:");
            $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
            $stmt->execute(['library_db', 'library_report']);
            if ($stmt->fetch()) {
                echo " ✅ library_report існує\n";
            } else {
                echo " ❌ library_report відсутнє\n";
            }

            echo sprintf("  %-12s", "користувач:");
            $stmt = $pdo->prepare("SELECT User FROM mysql.user WHERE User = ?");
            $stmt->execute(['library_user']);
            if ($stmt->fetch()) {
                echo " ✅ library_user існує\n";
            } else {
                echo " ❌ library_user відсутній\n";
            }

        } catch (PDOException $e) {
            echo " ❌ ПОМИЛКА: " . $e->getMessage() . "\n";
        }
    }

    private function dropDatabase() {
        echo "\n=== Видалення бази даних ===\n";

        $confirm = $this->getInput("УВАГА! Всі дані будуть втрачені. Продовжити? (y/N): ");
        if (strtolower($confirm) !== 'y') {
            echo "Операцію скасовано.\n";
            return;
        }

        $pdo = $this->getDatabaseConnection(false);
        if (!$pdo) {
            echo "❌ Не вдалося підключитися до MySQL\n";
            return;
        }

        try {
            echo "Видалення бази даних...";
            $pdo->exec("DROP DATABASE IF EXISTS library_db");
            echo " ✅ OK\n";

            echo "Видалення користувача...";
            $pdo->exec("DROP USER IF EXISTS 'library_user'@'localhost'");
            echo " ✅ OK\n";

            echo "\nБазу даних успішно видалено!\n";

        } catch (PDOException $e) {
            echo " ❌ ПОМИЛКА: " . $e->getMessage() . "\n";
        }
    }

    private function createDatabase() {
        echo "\n=== Створення бази даних ===\n";

        if (!file_exists('create_db.sql')) {
            echo "❌ Файл create_db.sql не знайдено\n";
            return;
        }

        $pdo = $this->getDatabaseConnection(false);
        if (!$pdo) {
            echo "❌ Не вдалося підключитися до MySQL\n";
            return;
        }

        try {
            echo "Читання SQL файлу...";
            $sql = file_get_contents('create_db.sql');
            if (!$sql) {
                echo " ❌ ПОМИЛКА\n";
                return;
            }
            echo " ✅ OK\n";

            echo "Виконання SQL команд...\n";

            $commands = explode(';', $sql);
            $executed = 0;

            foreach ($commands as $command) {
                $command = trim($command);
                if (!empty($command)) {
                    $pdo->exec($command);
                    $executed++;
                }
            }

            echo "  Виконано $executed команд ✅\n";
            echo "\nБазу даних успішно створено!\n";
            echo "  ✅ База даних library_db\n";
            echo "  ✅ Таблиці (readers, books, categories, loans)\n";
            echo "  ✅ Користувач library_user\n";
            echo "  ✅ Подання library_report\n";

        } catch (PDOException $e) {
            echo " ❌ ПОМИЛКА: " . $e->getMessage() . "\n";
        }
    }

    private function fillDatabase() {
        echo "\n=== Заповнення тестовими даними ===\n";

        if (!file_exists('sample_data.sql')) {
            echo "❌ Файл sample_data.sql не знайдено\n";
            return;
        }

        $pdo = $this->getDatabaseConnection(true);
        if (!$pdo) {
            echo "❌ Не вдалося підключитися до БД library_db\n";
            echo "Спочатку створіть базу даних (опція 3)\n";
            return;
        }

        try {
            echo "Читання SQL файлу...";
            $sql = file_get_contents('sample_data.sql');
            if (!$sql) {
                echo " ❌ ПОМИЛКА\n";
                return;
            }
            echo " ✅ OK\n";

            echo "Додавання тестових даних...\n";

            $commands = explode(';', $sql);
            $executed = 0;

            foreach ($commands as $command) {
                $command = trim($command);
                if (!empty($command) && !preg_match('/^USE\s+/i', $command)) {
                    $pdo->exec($command);
                    $executed++;
                }
            }

            echo "  Виконано $executed команд ✅\n";
            echo "\nТестові дані успішно додані!\n";
            echo "  ✅ Читачі: 3 записи\n";
            echo "  ✅ Категорії: 3 записи\n";
            echo "  ✅ Книги: 3 записи\n";
            echo "  ✅ Видачі: 3 записи\n";

        } catch (PDOException $e) {
            echo " ❌ ПОМИЛКА: " . $e->getMessage() . "\n";
        }
    }

    private function fullInitialization() {
        echo "\n=== Повна ініціалізація ===\n";
        echo "Виконується створення БД + заповнення даними...\n\n";

        $this->createDatabase();
        echo "\n" . str_repeat("-", 50) . "\n";
        $this->fillDatabase();

        echo "\n🚀 Повна ініціалізація завершена!\n";
        echo "Тепер можете запускати додаток: http://localhost/library-system-app/\n";
    }
}

if (php_sapi_name() === 'cli') {
    $app = new DatabaseSetup();
    $app->run();
} else {
    echo "Цей скрипт призначений для запуску з командного рядка.\n";
    echo "Використання: php setup.php\n";
}
?>
