<?php
require_once 'BaseModel.php';

class CategoryModel extends BaseModel {
    protected $table = 'categories';

    public function getAllWithStats() {
        $stmt = $this->pdo->prepare("
            SELECT c.*, COUNT(b.id) as books_count 
            FROM categories c 
            LEFT JOIN books b ON c.id = b.category_id 
            GROUP BY c.id
            ORDER BY c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPopularCategories($limit = 5) {
        // Ensure limit is an integer
        $limit = (int)$limit;

        $stmt = $this->pdo->prepare("
            SELECT c.*, COUNT(l.id) as loans_count
            FROM categories c
            LEFT JOIN loans l ON c.id = l.category_id
            GROUP BY c.id
            ORDER BY loans_count DESC
            LIMIT :limit
        ");

        // Bind as integer
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>