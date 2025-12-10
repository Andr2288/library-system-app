<?php
require_once 'BaseModel.php';

class VehicleModel extends BaseModel {
    protected $table = 'vehicles';

    public function getVehiclesWithDrivers() {
        $stmt = $this->pdo->prepare("
            SELECT v.*, d.name as driver_name 
            FROM vehicles v 
            LEFT JOIN drivers d ON v.driver_id = d.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByLicensePlate($licensePlate) {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE license_plate = ?");
        $stmt->execute([$licensePlate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchByLicensePlate($plate) {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE license_plate LIKE ?");
        $stmt->execute(["%$plate%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>