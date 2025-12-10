<?php
// Конфігурація підключення до БД
class Database {
    private $host = 'localhost';
    private $dbname = 'transport_db';
    private $username = 'transport_user';
    private $password = 'password123';
    private $pdo;

    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8", 
                    $this->username, 
                    $this->password
                );
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                die("Помилка підключення: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}

// Глобальне підключення для сумісності
$database = new Database();
$pdo = $database->getConnection();
?>
