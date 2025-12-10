<?php
require_once 'BaseModel.php';

class RouteModel extends BaseModel {
    protected $table = 'routes';
    
    public function getAllWithStats() {
        $stmt = $this->pdo->prepare("
            SELECT r.*, COUNT(t.id) as trips_count 
            FROM routes r 
            LEFT JOIN trips t ON r.id = t.route_id 
            GROUP BY r.id
            ORDER BY r.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPopularRoutes($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, COUNT(t.id) as trips_count 
            FROM routes r 
            LEFT JOIN trips t ON r.id = t.route_id 
            GROUP BY r.id 
            ORDER BY trips_count DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>