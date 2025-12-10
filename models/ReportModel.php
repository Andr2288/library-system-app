<?php
require_once 'BaseModel.php';

class ReportModel extends BaseModel {

    public function getLibraryReport() {
        $stmt = $this->pdo->prepare("SELECT * FROM library_report");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateVisitCounter() {
        $counterFile = 'visits.txt';

        if (file_exists($counterFile)) {
            $count = (int)file_get_contents($counterFile);
        } else {
            $count = 0;
        }

        $count++;
        file_put_contents($counterFile, $count);

        return $count;
    }
}
?>