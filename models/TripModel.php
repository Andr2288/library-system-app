<?php
require_once 'BaseModel.php';

class TripModel extends BaseModel {
    protected $table = 'trips';
    
    public function getTripsWithDetails() {
        $stmt = $this->pdo->prepare("
            SELECT 
                t.*,
                v.license_plate,
                v.brand,
                v.model,
                d.name as driver_name,
                r.name as route_name,
                r.start_point,
                r.end_point,
                r.distance_km
            FROM trips t
            JOIN vehicles v ON t.vehicle_id = v.id
            JOIN drivers d ON t.driver_id = d.id
            JOIN routes r ON t.route_id = r.id
            ORDER BY t.start_time DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveTrips() {
        $stmt = $this->pdo->prepare("
            SELECT t.*, v.license_plate, d.name as driver_name 
            FROM trips t
            JOIN vehicles v ON t.vehicle_id = v.id
            JOIN drivers d ON t.driver_id = d.id
            WHERE t.status = 'active'
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
