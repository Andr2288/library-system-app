<?php
require_once 'BaseModel.php';

class BookModel extends BaseModel {
    protected $table = 'books';

    public function getBooksWithCategories() {
        $stmt = $this->pdo->prepare("
            SELECT b.*, c.name as category_name 
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByISBN($isbn) {
        $stmt = $this->pdo->prepare("SELECT * FROM books WHERE isbn = ?");
        $stmt->execute([$isbn]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchByTitle($title) {
        $stmt = $this->pdo->prepare("SELECT * FROM books WHERE title LIKE ?");
        $stmt->execute(["%$title%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByAuthor($author) {
        $stmt = $this->pdo->prepare("SELECT * FROM books WHERE author LIKE ?");
        $stmt->execute(["%$author%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>