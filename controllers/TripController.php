<?php
require_once 'BaseController.php';
require_once 'models/TripModel.php';
require_once 'models/VehicleModel.php';
require_once 'models/DriverModel.php';
require_once 'models/RouteModel.php';

class TripController extends BaseController {
    private $tripModel;
    private $vehicleModel;
    private $driverModel;
    private $routeModel;

    public function __construct() {
        $this->tripModel = new TripModel();
        $this->vehicleModel = new VehicleModel();
        $this->driverModel = new DriverModel();
        $this->routeModel = new RouteModel();
    }

    public function index() {
        try {
            $trips = $this->tripModel->getTripsWithDetails();

            // Перевірка на повідомлення
            $message = isset($_GET['message']) ? $_GET['message'] : null;
            $messageType = isset($_GET['type']) ? $_GET['type'] : 'error';

            $data = ['trips' => $trips];

            if ($message) {
                $data['message'] = $message;
                $data['messageType'] = $messageType;
            }

            $this->renderView('trips/index.php', $data);
        } catch (Exception $e) {
            $this->renderView('trips/index.php', [
                'trips' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create() {
        $errors = [];

        if ($_POST) {
            // Валідація на стороні сервера
            $vehicleId = (int)$_POST['vehicle_id'];
            $driverId = (int)$_POST['driver_id'];
            $routeId = (int)$_POST['route_id'];
            $startTime = $this->validateInput($_POST['start_time']);
            $endTime = !empty($_POST['end_time']) ? $this->validateInput($_POST['end_time']) : null;
            $fuelConsumed = !empty($_POST['fuel_consumed']) ? (float)$_POST['fuel_consumed'] : null;
            $status = isset($_POST['status']) ? $_POST['status'] : 'planned';

            // Перевірка обов'язкових полів
            if (!$vehicleId) {
                $errors['vehicle_id'] = 'Оберіть автомобіль';
            } else {
                // Перевірка що автомобіль активний
                $vehicle = $this->vehicleModel->getById($vehicleId);
                if (!$vehicle || $vehicle['status'] !== 'active') {
                    $errors['vehicle_id'] = 'Обраний автомобіль неактивний';
                }
            }

            if (!$driverId) {
                $errors['driver_id'] = 'Оберіть водія';
            }

            if (!$routeId) {
                $errors['route_id'] = 'Оберіть маршрут';
            }

            // Перевірка часу початку
            if (empty($startTime)) {
                $errors['start_time'] = 'Вкажіть час початку';
            } else {
                $startDateTime = new DateTime($startTime);
                $now = new DateTime();
                if ($startDateTime < $now) {
                    $errors['start_time'] = 'Час початку не може бути в минулому';
                }
            }

            // Перевірка часу закінчення
            if ($endTime && $startTime) {
                $startDateTime = new DateTime($startTime);
                $endDateTime = new DateTime($endTime);
                if ($endDateTime <= $startDateTime) {
                    $errors['end_time'] = 'Час закінчення має бути пізніше часу початку';
                }
            }

            // Перевірка витрати палива
            if ($fuelConsumed !== null && ($fuelConsumed < 0 || $fuelConsumed > 1000)) {
                $errors['fuel_consumed'] = 'Витрата палива має бути від 0 до 1000 літрів';
            }

            // Перевірка статусу
            $validStatuses = ['planned', 'active', 'completed'];
            if (!in_array($status, $validStatuses)) {
                $errors['status'] = 'Невірний статус рейсу';
            }

            // Автоматичний розрахунок витрати палива
            if ($fuelConsumed === null && $routeId && $vehicleId) {
                try {
                    $route = $this->routeModel->getById($routeId);
                    $vehicle = $this->vehicleModel->getById($vehicleId);

                    if ($route && $vehicle) {
                        // Розрахунок на основі типу автомобіля
                        $fuelPer100km = 25; // За замовчуванням для вантажівки

                        if (stripos($vehicle['model'], 'Transit') !== false ||
                            stripos($vehicle['model'], 'Sprinter') !== false) {
                            $fuelPer100km = 12; // Менше для мікроавтобусів
                        }

                        $fuelConsumed = round(($route['distance_km'] * $fuelPer100km / 100), 1);
                    }
                } catch (Exception $e) {
                    // Ігноруємо помилки розрахунку
                }
            }

            // Якщо немає помилок - створюємо запис
            if (empty($errors)) {
                $data = [
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $driverId,
                    'route_id' => $routeId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'fuel_consumed' => $fuelConsumed,
                    'status' => $status
                ];

                try {
                    if ($this->tripModel->create($data)) {
                        $this->redirect('index.php?controller=trips');
                    } else {
                        $errors['general'] = 'Помилка створення рейсу';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        // Отримуємо дані для форми
        try {
            $vehicles = $this->vehicleModel->getAll();
            $drivers = $this->driverModel->getAll();
            $routes = $this->routeModel->getAll();
        } catch (Exception $e) {
            $vehicles = [];
            $drivers = [];
            $routes = [];
            $errors['general'] = 'Помилка завантаження даних: ' . $e->getMessage();
        }

        $this->renderView('trips/form.php', [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'routes' => $routes,
            'errors' => $errors,
            'formData' => $_POST
        ]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];

        if (!$id) {
            $this->redirect('index.php?controller=trips');
        }

        // Отримати дані рейсу
        $trip = $this->tripModel->getById($id);
        if (!$trip) {
            $this->redirect('index.php?controller=trips');
        }

        if ($_POST) {
            // Захист від IDOR
            $postedId = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;
            if ($postedId !== $id) {
                $this->redirect('index.php?controller=trips');
            }

            // Валідація (аналогічна до create)
            $vehicleId = (int)$_POST['vehicle_id'];
            $driverId = (int)$_POST['driver_id'];
            $routeId = (int)$_POST['route_id'];
            $startTime = $this->validateInput($_POST['start_time']);
            $endTime = !empty($_POST['end_time']) ? $this->validateInput($_POST['end_time']) : null;
            $fuelConsumed = !empty($_POST['fuel_consumed']) ? (float)$_POST['fuel_consumed'] : null;
            $status = isset($_POST['status']) ? $_POST['status'] : 'planned';

            // Валідація (схожа до create, але без перевірки минулого часу для існуючих рейсів)
            if (!$vehicleId) {
                $errors['vehicle_id'] = 'Оберіть автомобіль';
            }
            if (!$driverId) {
                $errors['driver_id'] = 'Оберіть водія';
            }
            if (!$routeId) {
                $errors['route_id'] = 'Оберіть маршрут';
            }
            if (empty($startTime)) {
                $errors['start_time'] = 'Вкажіть час початку';
            }
            // ПРИМІТКА: Для існуючих рейсів НЕ перевіряємо чи час в минулому

            if ($endTime && $startTime) {
                $startDateTime = new DateTime($startTime);
                $endDateTime = new DateTime($endTime);
                if ($endDateTime <= $startDateTime) {
                    $errors['end_time'] = 'Час закінчення має бути пізніше часу початку';
                }
            }

            if ($fuelConsumed !== null && ($fuelConsumed < 0 || $fuelConsumed > 1000)) {
                $errors['fuel_consumed'] = 'Витрата палива має бути від 0 до 1000 літрів';
            }

            $validStatuses = ['planned', 'active', 'completed'];
            if (!in_array($status, $validStatuses)) {
                $errors['status'] = 'Невірний статус рейсу';
            }

            if (empty($errors)) {
                $data = [
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $driverId,
                    'route_id' => $routeId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'fuel_consumed' => $fuelConsumed,
                    'status' => $status
                ];

                try {
                    if ($this->tripModel->update($id, $data)) {
                        $this->redirect('index.php?controller=trips');
                    } else {
                        $errors['general'] = 'Помилка оновлення рейсу';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        // Отримуємо дані для форми
        try {
            $vehicles = $this->vehicleModel->getAll();
            $drivers = $this->driverModel->getAll();
            $routes = $this->routeModel->getAll();
        } catch (Exception $e) {
            $vehicles = [];
            $drivers = [];
            $routes = [];
        }

        $this->renderView('trips/form.php', [
            'trip' => $trip,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'routes' => $routes,
            'errors' => $errors
        ]);
    }

    public function delete() {
        if ($_POST && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id) {
                try {
                    $this->tripModel->delete($id);
                } catch (Exception $e) {
                    // Помилки ігноруємо для простоти
                }
            }
        }

        $this->redirect('index.php?controller=trips');
    }
}
?>