<?php
require_once 'BaseController.php';
require_once 'models/ReportModel.php';

class HomeController extends BaseController {
    private $reportModel;

    public function __construct() {
        $this->reportModel = new ReportModel();
    }

    public function index() {
        try {
            $visits = $this->reportModel->updateVisitCounter();
            $reports = $this->reportModel->getTransportReport();

            $this->renderView('home/index.php', [
                'visits' => $visits,
                'reports' => $reports
            ]);
        } catch (Exception $e) {
            $this->renderView('home/index.php', [
                'visits' => 1,
                'reports' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>