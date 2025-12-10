<?php
require_once 'config/database.php';
require_once 'models/VehicleModel.php';
require_once 'models/DriverModel.php';
require_once 'models/TripModel.php';

// Отримуємо підключення до БД
$database = new Database();
$pdo = $database->getConnection();

$vehicleModel = new VehicleModel();
$driverModel = new DriverModel();
$tripModel = new TripModel();

$results = [];
$searchType = '';
$searchQuery = '';
$errors = [];

// Функція валідації
function validateInput($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

// Обробка пошуку
if ($_POST) {
    $searchType = validateInput($_POST['search_type']);
    $searchQuery = validateInput($_POST['search_query']);

    // Валідація на стороні сервера
    if (empty($searchType)) {
        $errors[] = "Оберіть тип пошуку";
    }

    if (empty($searchQuery) || strlen(trim($searchQuery)) === 0) {
        $errors[] = "Введіть пошуковий запит";
    } elseif (strlen($searchQuery) < 2) {
        $errors[] = "Пошуковий запит має містити мінімум 2 символи";
    }

    // Якщо немає помилок - виконуємо пошук
    if (empty($errors)) {
        try {
            switch ($searchType) {
                case 'vehicle_plate':
                    // Пошук автомобіля за номером
                    if (!preg_match('/^[A-Za-z0-9]+$/', $searchQuery)) {
                        $errors[] = "Номерний знак може містити лише літери та цифри";
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT v.*, d.name as driver_name 
                            FROM vehicles v 
                            LEFT JOIN drivers d ON v.driver_id = d.id 
                            WHERE v.license_plate LIKE ?
                        ");
                        $stmt->execute(["%$searchQuery%"]);
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    break;

                case 'vehicle_brand':
                    // Пошук автомобіля за маркою
                    $stmt = $pdo->prepare("
                        SELECT v.*, d.name as driver_name 
                        FROM vehicles v 
                        LEFT JOIN drivers d ON v.driver_id = d.id 
                        WHERE v.brand LIKE ? OR v.model LIKE ?
                    ");
                    $stmt->execute(["%$searchQuery%", "%$searchQuery%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'driver_name':
                    // Пошук водія за ім'ям
                    $stmt = $pdo->prepare("
                        SELECT d.*, v.license_plate, v.brand, v.model 
                        FROM drivers d 
                        LEFT JOIN vehicles v ON d.id = v.driver_id 
                        WHERE d.name LIKE ?
                    ");
                    $stmt->execute(["%$searchQuery%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'trip_status':
                    // Пошук рейсів за статусом
                    $validStatuses = ['planned', 'active', 'completed'];
                    if (!in_array(strtolower($searchQuery), $validStatuses)) {
                        $errors[] = "Невірний статус. Доступні: planned, active, completed";
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT t.*, v.license_plate, v.brand, v.model, 
                                   d.name as driver_name, r.name as route_name,
                                   r.start_point, r.end_point 
                            FROM trips t
                            JOIN vehicles v ON t.vehicle_id = v.id
                            JOIN drivers d ON t.driver_id = d.id  
                            JOIN routes r ON t.route_id = r.id
                            WHERE t.status LIKE ?
                            ORDER BY t.start_time DESC
                        ");
                        $stmt->execute(["%$searchQuery%"]);
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    break;

                default:
                    $errors[] = "Невірний тип пошуку";
            }
        } catch (Exception $e) {
            $errors[] = "Помилка пошуку: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пошук - Автотранспортне підприємство</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; border-radius: 4px; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
        .nav a:hover, .nav a.active { color: #007bff; font-weight: bold; }

        .search-form { background: #e9ecef; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-row { display: flex; gap: 15px; align-items: end; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group select:invalid, .form-group input:invalid { border-color: #dc3545; }

        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }

        .error-list { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error-list ul { margin: 5px 0 0 20px; }

        .results { margin-top: 20px; }
        .results-header { background: #17a2b8; color: white; padding: 10px; border-radius: 4px 4px 0 0; }
        .no-results { text-align: center; padding: 40px; color: #6c757d; }

        table { width: 100%; border-collapse: collapse; border: 1px solid #ddd; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        .required { color: #dc3545; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-completed { color: #6c757d; }
        .status-planned { color: #ffc107; font-weight: bold; }

        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .container { margin: 10px; padding: 15px; }
        }
    </style>
    <script>
        function validateSearchForm() {
            // Очистити попередні помилки
            const errorDiv = document.getElementById('client-errors');
            errorDiv.innerHTML = '';

            const searchType = document.getElementById('search_type').value.trim();
            const searchQuery = document.getElementById('search_query').value.trim();
            let errors = [];

            // Валідація типу пошуку
            if (!searchType) {
                errors.push('Оберіть тип пошуку');
            }

            // Валідація запиту
            if (!searchQuery) {
                errors.push('Введіть пошуковий запит');
            } else if (searchQuery.length < 2) {
                errors.push('Пошуковий запит має містити мінімум 2 символи');
            }

            // Специфічна валідація для номерних знаків
            if (searchType === 'vehicle_plate' && searchQuery) {
                if (!/^[A-Za-z0-9]+$/.test(searchQuery)) {
                    errors.push('Номерний знак може містити лише літери та цифри');
                }
            }

            // Валідація статусу рейсу
            if (searchType === 'trip_status' && searchQuery) {
                const validStatuses = ['planned', 'active', 'completed'];
                if (!validStatuses.includes(searchQuery.toLowerCase())) {
                    errors.push('Статус має бути: planned, active або completed');
                }
            }

            // Показати помилки
            if (errors.length > 0) {
                errorDiv.innerHTML = '<div class="error-list"><strong>Виправте помилки:</strong><ul>' +
                    errors.map(error => '<li>' + error + '</li>').join('') + '</ul></div>';
                return false;
            }

            return true;
        }

        function updateSearchPlaceholder() {
            const searchType = document.getElementById('search_type').value;
            const searchInput = document.getElementById('search_query');

            const placeholders = {
                'vehicle_plate': 'AA1234BB',
                'vehicle_brand': 'Mercedes, Ford...',
                'driver_name': 'Іван Петренко',
                'trip_status': 'active, planned, completed'
            };

            searchInput.placeholder = placeholders[searchType] || 'Введіть пошуковий запит';
        }
    </script>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="index.php">Головна</a>
        <a href="index.php?controller=vehicles">Автомобілі</a>
        <a href="index.php?controller=drivers">Водії</a>
        <a href="index.php?controller=trips">Рейси</a>
        <a href="search.php" class="active">Пошук</a>
    </div>

    <h1>Пошук по базі даних</h1>

    <form method="post" onsubmit="return validateSearchForm()" class="search-form">
        <div class="form-row">
            <div class="form-group">
                <label for="search_type">Тип пошуку <span class="required">*</span></label>
                <select id="search_type" name="search_type" onchange="updateSearchPlaceholder()" required>
                    <option value="">Оберіть тип пошуку</option>
                    <option value="vehicle_plate" <?php echo ($searchType == 'vehicle_plate') ? 'selected' : ''; ?>>
                        🚗 Автомобіль за номером
                    </option>
                    <option value="vehicle_brand" <?php echo ($searchType == 'vehicle_brand') ? 'selected' : ''; ?>>
                        🏭 Автомобіль за маркою/моделлю
                    </option>
                    <option value="driver_name" <?php echo ($searchType == 'driver_name') ? 'selected' : ''; ?>>
                        👤 Водій за іменем
                    </option>
                    <option value="trip_status" <?php echo ($searchType == 'trip_status') ? 'selected' : ''; ?>>
                        📋 Рейси за статусом
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="search_query">Пошуковий запит <span class="required">*</span></label>
                <input type="text"
                       id="search_query"
                       name="search_query"
                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                       placeholder="Введіть пошуковий запит"
                       minlength="2"
                       maxlength="100"
                       required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Шукати</button>
            </div>
        </div>
    </form>

    <!-- Помилки клієнтської валідації -->
    <div id="client-errors"></div>

    <!-- Помилки серверної валідації -->
    <?php if (!empty($errors)): ?>
        <div class="error-list">
            <strong>Помилки:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Результати пошуку -->
    <?php if ($_POST && empty($errors)): ?>
        <div class="results">
            <div class="results-header">
                📊 Результати пошуку: "<?php echo htmlspecialchars($searchQuery); ?>"
                (знайдено <?php echo count($results); ?> записів)
            </div>

            <?php if (empty($results)): ?>
                <div class="no-results">
                    <p>За вашим запитом нічого не знайдено.</p>
                    <p>Спробуйте змінити критерії пошуку.</p>
                </div>
            <?php else: ?>
                <!-- Результати для автомобілів -->
                <?php if ($searchType == 'vehicle_plate' || $searchType == 'vehicle_brand'): ?>
                    <table>
                        <tr>
                            <th>Номер</th>
                            <th>Марка</th>
                            <th>Модель</th>
                            <th>Рік</th>
                            <th>Вантажність</th>
                            <th>Водій</th>
                            <th>Статус</th>
                        </tr>
                        <?php foreach ($results as $vehicle): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                <td><?php echo $vehicle['year']; ?></td>
                                <td><?php echo $vehicle['capacity']; ?> т</td>
                                <td><?php echo $vehicle['driver_name'] ? htmlspecialchars($vehicle['driver_name']) : 'Не призначений'; ?></td>
                                <td><?php echo htmlspecialchars($vehicle['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <!-- Результати для водіїв -->
                <?php elseif ($searchType == 'driver_name'): ?>
                    <table>
                        <tr>
                            <th>Ім'я</th>
                            <th>Посвідчення</th>
                            <th>Телефон</th>
                            <th>Досвід</th>
                            <th>Категорія</th>
                            <th>Автомобіль</th>
                        </tr>
                        <?php foreach ($results as $driver): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($driver['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                                <td><?php echo $driver['experience_years']; ?> років</td>
                                <td><?php echo htmlspecialchars($driver['category']); ?></td>
                                <td>
                                    <?php if ($driver['license_plate']): ?>
                                        <?php echo htmlspecialchars($driver['license_plate'] . ' (' . $driver['brand'] . ' ' . $driver['model'] . ')'); ?>
                                    <?php else: ?>
                                        Не призначений
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <!-- Результати для рейсів -->
                <?php elseif ($searchType == 'trip_status'): ?>
                    <table>
                        <tr>
                            <th>Автомобіль</th>
                            <th>Водій</th>
                            <th>Маршрут</th>
                            <th>Час початку</th>
                            <th>Час закінчення</th>
                            <th>Паливо</th>
                            <th>Статус</th>
                        </tr>
                        <?php foreach ($results as $trip): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trip['license_plate'] . ' (' . $trip['brand'] . ' ' . $trip['model'] . ')'); ?></td>
                                <td><?php echo htmlspecialchars($trip['driver_name']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($trip['route_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($trip['start_point'] . ' → ' . $trip['end_point']); ?></small>
                                </td>
                                <td><?php echo $trip['start_time'] ? date('d.m.Y H:i', strtotime($trip['start_time'])) : '-'; ?></td>
                                <td><?php echo $trip['end_time'] ? date('d.m.Y H:i', strtotime($trip['end_time'])) : '-'; ?></td>
                                <td><?php echo $trip['fuel_consumed'] ? $trip['fuel_consumed'] . ' л' : '-'; ?></td>
                                <td>
                                        <span class="status-<?php echo $trip['status']; ?>">
                                            <?php
                                            $statuses = [
                                                    'planned' => 'Запланований',
                                                    'active' => 'Активний',
                                                    'completed' => 'Завершений'
                                            ];
                                            echo isset($statuses[$trip['status']]) ? $statuses[$trip['status']] : $trip['status'];
                                            ?>
                                        </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>