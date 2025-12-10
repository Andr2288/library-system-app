<?php
class BaseModel {
    protected $pdo;
    protected $table;

    public function __construct() {
        $this->pdo = $this->getDatabaseConnection();
    }

    private function getDatabaseConnection() {
        static $pdo = null;

        if ($pdo === null) {
            $host = 'localhost';
            $dbname = 'library_db';
            $username = 'library_user';
            $password = 'password123';

            try {
                $pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8",
                    $username,
                    $password
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                die("Помилка підключення до БД: " . $e->getMessage());
            }
        }

        return $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        return $stmt->execute($data);
    }

    public function update($id, $data) {
        $set = '';
        foreach (array_keys($data) as $column) {
            $set .= "{$column} = :{$column}, ";
        }
        $set = rtrim($set, ', ');

        $data['id'] = $id;
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET {$set} WHERE id = :id");
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? LIMIT 1");
        return $stmt->execute([$id]);
    }
}
?>