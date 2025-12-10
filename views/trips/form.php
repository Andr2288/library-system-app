<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($trip) ? 'Редагувати' : 'Додати'; ?> рейс</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 800px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .form-row { display: flex; gap: 15px; }
        .form-group { margin-bottom: 15px; flex: 1; }
        .form-group.full-width { flex: 100%; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:invalid, select:invalid { border-color: #dc3545; }
        input:valid, select:valid { border-color: #28a745; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
        .required { color: #dc3545; }
        .info-box { background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .calc-box { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .route-info { background: #e2e3e5; padding: 10px; border-radius: 4px; margin: 5px 0; font-size: 14px; }

        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
        }
    </style>
    <script>
        function validateForm() {
            let isValid = true;

            // Очистити попередні помилки
            document.querySelectorAll('.error').forEach(el => el.textContent = '');

            // Перевірка автомобіля
            const vehicleId = document.getElementById('vehicle_id').value;
            if (!vehicleId) {
                document.getElementById('vehicle_error').textContent = 'Оберіть автомобіль';
                isValid = false;
            }

            // Перевірка водія
            const driverId = document.getElementById('driver_id').value;
            if (!driverId) {
                document.getElementById('driver_error').textContent = 'Оберіть водія';
                isValid = false;
            }

            // Перевірка маршруту
            const routeId = document.getElementById('route_id').value;
            if (!routeId) {
                document.getElementById('route_error').textContent = 'Оберіть маршрут';
                isValid = false;
            }

            // Перевірка часу початку
            const startTime = document.getElementById('start_time').value;
            if (!startTime) {
                document.getElementById('start_time_error').textContent = 'Вкажіть час початку';
                isValid = false;
            } else {
                // Перевірка минулого часу ТІЛЬКИ для нових рейсів (не для редагування)
                const isEditMode = document.querySelector('input[name="trip_id"]') !== null;
                if (!isEditMode) {
                    const startDate = new Date(startTime);
                    const now = new Date();
                    if (startDate < now) {
                        document.getElementById('start_time_error').textContent = 'Час початку не може бути в минулому';
                        isValid = false;
                    }
                }
            }

            // Перевірка часу закінчення (якщо вказано)
            const endTime = document.getElementById('end_time').value;
            if (endTime && startTime) {
                const startDate = new Date(startTime);
                const endDate = new Date(endTime);
                if (endDate <= startDate) {
                    document.getElementById('end_time_error').textContent = 'Час закінчення має бути пізніше часу початку';
                    isValid = false;
                }
            }

            // Перевірка витрати палива (якщо вказано)
            const fuelConsumed = document.getElementById('fuel_consumed').value;
            if (fuelConsumed && (fuelConsumed < 0 || fuelConsumed > 1000)) {
                document.getElementById('fuel_error').textContent = 'Витрата палива від 0 до 1000 літрів';
                isValid = false;
            }

            return isValid;
        }

        function loadRouteInfo() {
            const routeSelect = document.getElementById('route_id');
            const routeInfo = document.getElementById('route_info');

            if (routeSelect.value) {
                const selectedOption = routeSelect.options[routeSelect.selectedIndex];
                const distance = selectedOption.getAttribute('data-distance');
                const duration = selectedOption.getAttribute('data-duration');
                const startPoint = selectedOption.getAttribute('data-start');
                const endPoint = selectedOption.getAttribute('data-end');

                if (distance) {
                    routeInfo.innerHTML = `
                        <strong>Маршрут:</strong> ${startPoint} → ${endPoint}<br>
                        <strong>Відстань:</strong> ${distance} км<br>
                        <strong>Тривалість:</strong> ${duration} годин
                    `;

                    // Автоматичний розрахунок приблизної витрати палива
                    calculateFuelEstimate(distance);
                }
            } else {
                routeInfo.innerHTML = 'Оберіть маршрут для відображення деталей';
            }
        }

        function calculateFuelEstimate(distance) {
            const vehicleSelect = document.getElementById('vehicle_id');
            if (vehicleSelect.value && distance) {
                // Приблизний розрахунок: 25 літрів на 100 км для вантажівки, 8 літрів для легкового
                const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
                const vehicleInfo = selectedOption.text;

                let fuelPer100km = 25; // За замовчуванням для вантажівки
                if (vehicleInfo.includes('Transit') || vehicleInfo.includes('Sprinter')) {
                    fuelPer100km = 12;
                }

                const estimatedFuel = (distance * fuelPer100km / 100).toFixed(1);
                const calcBox = document.getElementById('calc_box');
                calcBox.innerHTML = `
                    <strong>Автоматичний розрахунок:</strong><br>
                    Приблизна витрата палива: ${estimatedFuel} л<br>
                    <small>(${fuelPer100km} л/100км × ${distance} км)</small>
                `;
                calcBox.style.display = 'block';
            }
        }

        function setCurrentDateTime() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('start_time').value = now.toISOString().slice(0, 16);
        }

        // Встановити поточний час при завантаженні сторінки (тільки для нових рейсів)
        window.onload = function() {
            const startTimeInput = document.getElementById('start_time');
            const isEditMode = document.querySelector('input[name="trip_id"]') !== null;

            if (!isEditMode && !startTimeInput.value) {
                setCurrentDateTime();
            }
            loadRouteInfo();
        };
    </script>
</head>
<body>
<div class="nav">
    <a href="index.php">Головна</a>
    <a href="index.php?controller=vehicles">Автомобілі</a>
    <a href="index.php?controller=drivers">Водії</a>
    <a href="index.php?controller=trips">Рейси</a>
    <a href="search.php">Пошук</a>
</div>

<div class="form-container">
    <h1><?php echo isset($trip) ? 'Редагувати рейс' : 'Створити новий рейс'; ?></h1>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <strong>Виправте помилки:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <?php foreach ($errors as $field => $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" onsubmit="return validateForm()">
        <!-- Приховане поле для захисту від IDOR (тільки для edit) -->
        <?php if (isset($trip)): ?>
            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="vehicle_id">Автомобіль <span class="required">*</span></label>
                <select id="vehicle_id" name="vehicle_id" onchange="calculateFuelEstimate(document.getElementById('route_id').options[document.getElementById('route_id').selectedIndex]?.getAttribute('data-distance'))" required>
                    <option value="">Оберіть автомобіль</option>
                    <?php if (isset($vehicles) && !empty($vehicles)): ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>"
                                    <?php
                                    $selected = false;
                                    if (isset($trip) && $trip['vehicle_id'] == $vehicle['id']) {
                                        $selected = true;
                                    } elseif (isset($formData['vehicle_id']) && $formData['vehicle_id'] == $vehicle['id']) {
                                        $selected = true;
                                    }
                                    echo $selected ? 'selected' : '';
                                    ?>>
                                <?php echo htmlspecialchars($vehicle['license_plate'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['capacity'] . 'т)'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="error" id="vehicle_error"></div>
            </div>

            <div class="form-group">
                <label for="driver_id">Водій <span class="required">*</span></label>
                <select id="driver_id" name="driver_id" required>
                    <option value="">Оберіть водія</option>
                    <?php if (isset($drivers) && !empty($drivers)): ?>
                        <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo $driver['id']; ?>"
                                    <?php
                                    $selected = false;
                                    if (isset($trip) && $trip['driver_id'] == $driver['id']) {
                                        $selected = true;
                                    } elseif (isset($formData['driver_id']) && $formData['driver_id'] == $driver['id']) {
                                        $selected = true;
                                    }
                                    echo $selected ? 'selected' : '';
                                    ?>>
                                <?php echo htmlspecialchars($driver['name'] . ' (Кат. ' . $driver['category'] . ', ' . $driver['experience_years'] . ' років досвіду)'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="error" id="driver_error"></div>
            </div>
        </div>

        <div class="form-group">
            <label for="route_id">Маршрут <span class="required">*</span></label>
            <select id="route_id" name="route_id" onchange="loadRouteInfo()" required>
                <option value="">Оберіть маршрут</option>
                <?php if (isset($routes) && !empty($routes)): ?>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?php echo $route['id']; ?>"
                                data-distance="<?php echo $route['distance_km']; ?>"
                                data-duration="<?php echo $route['duration_hours']; ?>"
                                data-start="<?php echo htmlspecialchars($route['start_point']); ?>"
                                data-end="<?php echo htmlspecialchars($route['end_point']); ?>"
                                <?php
                                $selected = false;
                                if (isset($trip) && $trip['route_id'] == $route['id']) {
                                    $selected = true;
                                } elseif (isset($formData['route_id']) && $formData['route_id'] == $route['id']) {
                                    $selected = true;
                                }
                                echo $selected ? 'selected' : '';
                                ?>>
                            <?php echo htmlspecialchars($route['name'] . ' (' . $route['start_point'] . ' → ' . $route['end_point'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="error" id="route_error"></div>

            <div id="route_info" class="route-info">
                Оберіть маршрут для відображення деталей
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Час початку <span class="required">*</span></label>
                <input type="datetime-local" id="start_time" name="start_time"
                       value="<?php echo isset($trip) && $trip['start_time'] ? date('Y-m-d\TH:i', strtotime($trip['start_time'])) : (isset($formData['start_time']) ? $formData['start_time'] : ''); ?>"
                       required>
                <div class="error" id="start_time_error"></div>
                <?php if (isset($trip)): ?>
                    <small>При редагуванні можна змінювати час на будь-який</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="end_time">Час закінчення (планований)</label>
                <input type="datetime-local" id="end_time" name="end_time"
                       value="<?php echo isset($trip) && $trip['end_time'] ? date('Y-m-d\TH:i', strtotime($trip['end_time'])) : (isset($formData['end_time']) ? $formData['end_time'] : ''); ?>">
                <div class="error" id="end_time_error"></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="fuel_consumed">Витрата палива (л)</label>
                <input type="number" id="fuel_consumed" name="fuel_consumed"
                       min="0" max="1000" step="0.1"
                       placeholder="Буде розраховано автоматично"
                       value="<?php echo isset($trip) ? $trip['fuel_consumed'] : (isset($formData['fuel_consumed']) ? $formData['fuel_consumed'] : ''); ?>">
                <div class="error" id="fuel_error"></div>
                <small>Залиште пустим для автоматичного розрахунку</small>
            </div>

            <div class="form-group">
                <label for="status">Статус рейсу</label>
                <select id="status" name="status">
                    <?php
                    $currentStatus = isset($trip) ? $trip['status'] : (isset($formData['status']) ? $formData['status'] : 'planned');
                    ?>
                    <option value="planned" <?php echo ($currentStatus == 'planned') ? 'selected' : ''; ?>>🕐 Запланований</option>
                    <option value="active" <?php echo ($currentStatus == 'active') ? 'selected' : ''; ?>>🚀 Активний</option>
                    <option value="completed" <?php echo ($currentStatus == 'completed') ? 'selected' : ''; ?>>✅ Завершений</option>
                </select>
            </div>
        </div>

        <!-- Розрахункова інформація -->
        <div id="calc_box" class="calc-box" style="display: none;">
            <!-- JavaScript заповнить це поле -->
        </div>

        <?php if (isset($trip)): ?>
            <div class="info-box">
                <strong>Інформація про рейс:</strong><br>
                ID рейсу: <?php echo $trip['id']; ?><br>
                Створено: <?php echo isset($trip['created_at']) ? date('d.m.Y H:i', strtotime($trip['created_at'])) : 'Невідомо'; ?><br>
                <?php if (isset($trip['distance_km'])): ?>
                    Відстань маршруту: <?php echo $trip['distance_km']; ?> км<br>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <button type="submit" class="btn">
                <?php echo isset($trip) ? '📝 Оновити рейс' : '🚀 Створити рейс'; ?>
            </button>
            <a href="index.php?controller=trips" class="btn btn-secondary">❌ Скасувати</a>
        </div>
    </form>
</div>

<div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 4px; font-size: 14px; max-width: 800px; margin-left: auto; margin-right: auto;">
    <strong>Поради для створення рейсу:</strong>
    <ul style="margin: 10px 0 0 20px;">
        <li>Оберіть тільки активні автомобілі та вільних водіїв</li>
        <?php if (!isset($trip)): ?>
            <li>Час початку не може бути в минулому</li>
        <?php else: ?>
            <li>При редагуванні можна змінювати час на будь-який</li>
        <?php endif; ?>
        <li>Витрата палива розраховується автоматично на основі відстані</li>
        <li>Для завершених рейсів обов'язково вкажіть час закінчення</li>
    </ul>
</div>
</body>
</html>