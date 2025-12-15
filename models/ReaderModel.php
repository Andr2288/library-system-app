<?php
require_once 'BaseModel.php';

class ReaderModel extends BaseModel {
    protected $table = 'readers';

    public function searchByName($name) {
        $stmt = $this->pdo->prepare("SELECT * FROM readers WHERE name LIKE ?");
        $stmt->execute(["%$name%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReadersWithBooks() {
        $stmt = $this->pdo->prepare("
            SELECT r.*, COUNT(l.id) as active_loans
            FROM readers r 
            LEFT JOIN loans l ON r.id = l.reader_id AND l.status = 'active'
            GROUP BY r.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCardNumber($cardNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM readers WHERE card_number = ?");
        $stmt->execute([$cardNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
