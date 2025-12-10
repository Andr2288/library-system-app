<?php
require_once 'BaseModel.php';

class DriverModel extends BaseModel {
    protected $table = 'drivers';

    public function searchByName($name) {
        $stmt = $this->pdo->prepare("SELECT * FROM drivers WHERE name LIKE ?");
        $stmt->execute(["%$name%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDriversWithVehicles() {
        $stmt = $this->pdo->prepare("
            SELECT d.*, v.license_plate, v.brand, v.model 
            FROM drivers d 
            LEFT JOIN vehicles v ON d.id = v.driver_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByLicenseNumber($licenseNumber) {
        $stmt = $this->pdo->prepare("SELECT * FROM drivers WHERE license_number = ?");
        $stmt->execute([$licenseNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>