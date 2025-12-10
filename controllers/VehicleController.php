<?php
require_once 'BaseController.php';
require_once 'models/VehicleModel.php';

class VehicleController extends BaseController {
    private $vehicleModel;

    public function __construct() {
        $this->vehicleModel = new VehicleModel();
    }

    public function index() {
        try {
            $vehicles = $this->vehicleModel->getVehiclesWithDrivers();

            // Перевірка на повідомлення після видалення
            $message = isset($_GET['message']) ? $_GET['message'] : null;
            $messageType = isset($_GET['type']) ? $_GET['type'] : 'error';

            $data = ['vehicles' => $vehicles];

            if ($message) {
                $data['message'] = $message;
                $data['messageType'] = $messageType;
            }

            $this->renderView('vehicles/index.php', $data);
        } catch (Exception $e) {
            $this->renderView('vehicles/index.php', [
                'vehicles' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create() {
        $errors = [];

        if ($_POST) {
            // Валідація на стороні сервера
            $licensePlate = $this->validateInput($_POST['license_plate']);
            $brand = $this->validateInput($_POST['brand']);
            $model = $this->validateInput($_POST['model']);
            $year = (int)$_POST['year'];
            $capacity = (float)$_POST['capacity'];

            // Перевірка номерного знаку
            if (empty($licensePlate)) {
                $errors['license_plate'] = 'Номерний знак обов\'язковий';
            } elseif (!preg_match('/^[A-Z]{2}\d{4}[A-Z]{2}$/i', $licensePlate)) {
                $errors['license_plate'] = 'Невірний формат номерного знаку (AA1234BB)';
            } else {
                // Перевірка на унікальність
                try {
                    $existing = $this->vehicleModel->getByLicensePlate($licensePlate);
                    if ($existing) {
                        $errors['license_plate'] = 'Автомобіль з таким номером вже існує';
                    }
                } catch (Exception $e) {
                    // Ігноруємо помилки перевірки
                }
            }

            // Перевірка марки
            if (empty($brand)) {
                $errors['brand'] = 'Марка обов\'язкова';
            } elseif (strlen($brand) < 2) {
                $errors['brand'] = 'Марка має містити мінімум 2 символи';
            }

            // Перевірка моделі
            if (empty($model)) {
                $errors['model'] = 'Модель обов\'язкова';
            }

            // Перевірка року
            $currentYear = date('Y');
            if ($year < 1990 || $year > $currentYear + 1) {
                $errors['year'] = "Рік має бути між 1990 та " . ($currentYear + 1);
            }

            // Перевірка вантажності
            if ($capacity <= 0 || $capacity > 100) {
                $errors['capacity'] = 'Вантажність має бути від 0.1 до 100 тонн';
            }

            // Якщо немає помилок - створюємо запис
            if (empty($errors)) {
                $data = [
                    'license_plate' => strtoupper($licensePlate),
                    'brand' => $brand,
                    'model' => $model,
                    'year' => $year,
                    'capacity' => $capacity,
                    'driver_id' => !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null,
                    'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
                ];

                // Обробка завантаження фото
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Перевірка типу файлу
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($_FILES['photo']['type'], $allowedTypes)) {
                        // Перевірка розміру (5MB)
                        if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                            $fileName = time() . '_' . basename($_FILES['photo']['name']);
                            $uploadPath = $uploadDir . $fileName;

                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                                $data['photo'] = $uploadPath;
                            } else {
                                $errors['photo'] = 'Помилка завантаження фото';
                            }
                        } else {
                            $errors['photo'] = 'Розмір фото перевищує 5MB';
                        }
                    } else {
                        $errors['photo'] = 'Дозволені формати: JPG, PNG, GIF';
                    }
                }

                // Спроба створити запис
                if (empty($errors)) {
                    try {
                        if ($this->vehicleModel->create($data)) {
                            $this->redirect('index.php?controller=vehicles');
                        } else {
                            $errors['general'] = 'Помилка створення запису';
                        }
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $errors['license_plate'] = 'Автомобіль з таким номером вже існує';
                        } else {
                            $errors['general'] = 'Помилка бази даних: ' . $e->getMessage();
                        }
                    } catch (Exception $e) {
                        $errors['general'] = 'Помилка: ' . $e->getMessage();
                    }
                }
            }
        }

        // Отримати список водіїв для форми
        require_once 'models/DriverModel.php';
        $driverModel = new DriverModel();
        try {
            $drivers = $driverModel->getAll();
        } catch (Exception $e) {
            $drivers = [];
        }

        $this->renderView('vehicles/form.php', [
            'drivers' => $drivers,
            'errors' => $errors,
            'formData' => $_POST
        ]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];

        if (!$id) {
            $this->redirect('index.php?controller=vehicles');
        }

        // Отримати дані автомобіля
        $vehicle = $this->vehicleModel->getById($id);
        if (!$vehicle) {
            $this->redirect('index.php?controller=vehicles');
        }

        if ($_POST) {
            // Захист від IDOR - перевірка що ID з форми співпадає з ID з URL
            $postedId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
            if ($postedId !== $id) {
                $this->redirect('index.php?controller=vehicles');
            }

            // Валідація (аналогічна до create)
            $licensePlate = $this->validateInput($_POST['license_plate']);
            $brand = $this->validateInput($_POST['brand']);
            $model = $this->validateInput($_POST['model']);
            $year = (int)$_POST['year'];
            $capacity = (float)$_POST['capacity'];

            // Перевірка номерного знаку (крім поточного запису)
            if (empty($licensePlate)) {
                $errors['license_plate'] = 'Номерний знак обов\'язковий';
            } elseif (!preg_match('/^[A-Z]{2}\d{4}[A-Z]{2}$/i', $licensePlate)) {
                $errors['license_plate'] = 'Невірний формат номерного знаку (AA1234BB)';
            } else {
                $existing = $this->vehicleModel->getByLicensePlate($licensePlate);
                if ($existing && $existing['id'] != $id) {
                    $errors['license_plate'] = 'Автомобіль з таким номером вже існує';
                }
            }

            if (empty($brand)) {
                $errors['brand'] = 'Марка обов\'язкова';
            }
            if (empty($model)) {
                $errors['model'] = 'Модель обов\'язкова';
            }

            $currentYear = date('Y');
            if ($year < 1990 || $year > $currentYear + 1) {
                $errors['year'] = "Рік має бути між 1990 та " . ($currentYear + 1);
            }

            if ($capacity <= 0 || $capacity > 100) {
                $errors['capacity'] = 'Вантажність має бути від 0.1 до 100 тонн';
            }

            if (empty($errors)) {
                $data = [
                    'license_plate' => strtoupper($licensePlate),
                    'brand' => $brand,
                    'model' => $model,
                    'year' => $year,
                    'capacity' => $capacity,
                    'driver_id' => !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null,
                    'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
                ];

                // Обробка нового фото
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($_FILES['photo']['type'], $allowedTypes)) {
                        if ($_FILES['photo']['size'] <= 5 * 1024 * 1024) {
                            // Видалити старе фото
                            if ($vehicle['photo'] && file_exists($vehicle['photo'])) {
                                unlink($vehicle['photo']);
                            }

                            $fileName = time() . '_' . basename($_FILES['photo']['name']);
                            $uploadPath = $uploadDir . $fileName;

                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                                $data['photo'] = $uploadPath;
                            }
                        }
                    }
                }

                try {
                    if ($this->vehicleModel->update($id, $data)) {
                        $this->redirect('index.php?controller=vehicles');
                    } else {
                        $errors['general'] = 'Помилка оновлення запису';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        // Отримати список водіїв
        require_once 'models/DriverModel.php';
        $driverModel = new DriverModel();
        $drivers = $driverModel->getAll();

        $this->renderView('vehicles/form.php', [
            'vehicle' => $vehicle,
            'drivers' => $drivers,
            'errors' => $errors
        ]);
    }

    public function delete() {
        if ($_POST && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id) {
                try {
                    // Отримати дані про фото для видалення
                    $vehicle = $this->vehicleModel->getById($id);

                    if ($this->vehicleModel->delete($id)) {
                        // Видалити фото файл
                        if ($vehicle && $vehicle['photo'] && file_exists($vehicle['photo'])) {
                            unlink($vehicle['photo']);
                        }

                        // Успішне видалення
                        $this->redirect('index.php?controller=vehicles&message=' . urlencode('Автомобіль успішно видалено') . '&type=success');
                    }
                } catch (PDOException $e) {
                    // Перевірка на помилку foreign key constraint
                    if (strpos($e->getMessage(), 'foreign key constraint') !== false ||
                        strpos($e->getMessage(), 'Cannot delete') !== false) {
                        $this->redirect('index.php?controller=vehicles&message=' . urlencode('Неможливо видалити автомобіль: він використовується в рейсах. Спочатку видаліть всі рейси з цим автомобілем.') . '&type=error');
                    } else {
                        $this->redirect('index.php?controller=vehicles&message=' . urlencode('Помилка видалення: ' . $e->getMessage()) . '&type=error');
                    }
                } catch (Exception $e) {
                    $this->redirect('index.php?controller=vehicles&message=' . urlencode('Помилка: ' . $e->getMessage()) . '&type=error');
                }
            }
        }

        $this->redirect('index.php?controller=vehicles');
    }
}
?>