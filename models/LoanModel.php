<?php
require_once 'BaseModel.php';

class LoanModel extends BaseModel {
    protected $table = 'loans';

    public function getLoansWithDetails() {
        $stmt = $this->pdo->prepare("
            SELECT 
                l.*,
                b.title,
                b.author,
                b.isbn,
                r.name as reader_name,
                r.card_number,
                c.name as category_name
            FROM loans l
            JOIN books b ON l.book_id = b.id
            JOIN readers r ON l.reader_id = r.id
            JOIN categories c ON l.category_id = c.id
            ORDER BY l.loan_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveLoans() {
        $stmt = $this->pdo->prepare("
            SELECT l.*, b.title, r.name as reader_name 
            FROM loans l
            JOIN books b ON l.book_id = b.id
            JOIN readers r ON l.reader_id = r.id
            WHERE l.status = 'active'
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOverdueLoans() {
        $stmt = $this->pdo->prepare("
            SELECT l.*, b.title, r.name as reader_name 
            FROM loans l
            JOIN books b ON l.book_id = b.id
            JOIN readers r ON l.reader_id = r.id
            WHERE l.status = 'active' AND l.return_date < NOW()
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>