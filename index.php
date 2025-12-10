<?php
// Головний файл додатку
require_once 'config/database.php';
require_once 'controllers/HomeController.php';

// Простий роутер
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

switch ($controller) {
    case 'home':
        $homeController = new HomeController();
        if ($action === 'index') {
            $homeController->index();
        }
        break;
    case 'vehicles':
        require_once 'controllers/VehicleController.php';
        $vehicleController = new VehicleController();
        if (method_exists($vehicleController, $action)) {
            $vehicleController->$action();
        }
        break;
    case 'drivers':
        require_once 'controllers/DriverController.php';
        $driverController = new DriverController();
        if (method_exists($driverController, $action)) {
            $driverController->$action();
        }
        break;
    case 'trips':
        require_once 'controllers/TripController.php';
        $tripController = new TripController();
        if (method_exists($tripController, $action)) {
            $tripController->$action();
        }
        break;
    default:
        $homeController = new HomeController();
        $homeController->index();
        break;
}
?>