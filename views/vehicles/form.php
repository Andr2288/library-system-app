<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($vehicle) ? 'Редагувати' : 'Додати'; ?> автомобіль</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
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
        .current-photo { margin: 10px 0; }
        .current-photo img { max-width: 200px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
    <script>
        function validateForm() {
            let isValid = true;
            
            // Очистити попередні помилки
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            
            // Перевірка номерного знаку
            const licensePlate = document.getElementById('license_plate').value.trim();
            if (!licensePlate) {
                document.getElementById('license_plate_error').textContent = 'Номерний знак обов\'язковий';
                isValid = false;
            } else if (!/^[A-Z]{2}\d{4}[A-Z]{2}$/i.test(licensePlate)) {
                document.getElementById('license_plate_error').textContent = 'Формат: AA1234BB';
                isValid = false;
            }
            
            // Перевірка марки
            const brand = document.getElementById('brand').value.trim();
            if (!brand) {
                document.getElementById('brand_error').textContent = 'Марка обов\'язкова';
                isValid = false;
            } else if (brand.length < 2) {
                document.getElementById('brand_error').textContent = 'Мінімум 2 символи';
                isValid = false;
            }
            
            // Перевірка моделі
            const model = document.getElementById('model').value.trim();
            if (!model) {
                document.getElementById('model_error').textContent = 'Модель обов\'язкова';
                isValid = false;
            }
            
            // Перевірка року
            const year = document.getElementById('year').value;
            const currentYear = new Date().getFullYear();
            if (!year) {
                document.getElementById('year_error').textContent = 'Рік обов\'язковий';
                isValid = false;
            } else if (year < 1990 || year > currentYear) {
                document.getElementById('year_error').textContent = `Рік має бути між 1990 та ${currentYear}`;
                isValid = false;
            }
            
            // Перевірка вантажності
            const capacity = document.getElementById('capacity').value;
            if (!capacity) {
                document.getElementById('capacity_error').textContent = 'Вантажність обов\'язкова';
                isValid = false;
            } else if (capacity <= 0 || capacity > 100) {
                document.getElementById('capacity_error').textContent = 'Вантажність від 0.1 до 100 тонн';
                isValid = false;
            }
            
            // Перевірка фото
            const photo = document.getElementById('photo').files[0];
            if (photo) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(photo.type)) {
                    document.getElementById('photo_error').textContent = 'Дозволені формати: JPG, PNG, GIF';
                    isValid = false;
                } else if (photo.size > 5 * 1024 * 1024) {
                    document.getElementById('photo_error').textContent = 'Максимальний розмір: 5MB';
                    isValid = false;
                }
            }
            
            return isValid;
        }
    </script>
</head>
<body>
    <div class="nav">
        <a href="index.php">Головна</a>
        <a href="index.php?controller=vehicles">Автомобілі</a>
        <a href="index.php?controller=drivers">Водії</a>
        <a href="index.php?controller=trips">Рейси</a>
    </div>

    <div class="form-container">
        <h1><?php echo isset($vehicle) ? 'Редагувати автомобіль' : 'Додати новий автомобіль'; ?></h1>
        
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
        
        <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <!-- Приховане поле для захисту від IDOR (тільки для edit) -->
            <?php if (isset($vehicle)): ?>
                <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="license_plate">Номерний знак <span class="required">*</span></label>
                <input type="text" id="license_plate" name="license_plate" 
                       pattern="[A-Za-z]{2}\d{4}[A-Za-z]{2}" 
                       placeholder="AA1234BB" 
                       value="<?php echo isset($vehicle) ? htmlspecialchars($vehicle['license_plate']) : (isset($formData['license_plate']) ? htmlspecialchars($formData['license_plate']) : ''); ?>"
                       required>
                <div class="error" id="license_plate_error"></div>
            </div>

            <div class="form-group">
                <label for="brand">Марка <span class="required">*</span></label>
                <input type="text" id="brand" name="brand" 
                       minlength="2" maxlength="50"
                       value="<?php echo isset($vehicle) ? htmlspecialchars($vehicle['brand']) : (isset($formData['brand']) ? htmlspecialchars($formData['brand']) : ''); ?>"
                       required>
                <div class="error" id="brand_error"></div>
            </div>

            <div class="form-group">
                <label for="model">Модель <span class="required">*</span></label>
                <input type="text" id="model" name="model" 
                       maxlength="50"
                       value="<?php echo isset($vehicle) ? htmlspecialchars($vehicle['model']) : (isset($formData['model']) ? htmlspecialchars($formData['model']) : ''); ?>"
                       required>
                <div class="error" id="model_error"></div>
            </div>

            <div class="form-group">
                <label for="year">Рік випуску <span class="required">*</span></label>
                <input type="number" id="year" name="year" 
                       min="1990" max="<?php echo date('Y') + 1; ?>"
                       value="<?php echo isset($vehicle) ? $vehicle['year'] : (isset($formData['year']) ? $formData['year'] : ''); ?>"
                       required>
                <div class="error" id="year_error"></div>
            </div>

            <div class="form-group">
                <label for="capacity">Вантажність (тонни) <span class="required">*</span></label>
                <input type="number" id="capacity" name="capacity" 
                       min="0.1" max="100" step="0.1"
                       value="<?php echo isset($vehicle) ? $vehicle['capacity'] : (isset($formData['capacity']) ? $formData['capacity'] : ''); ?>"
                       required>
                <div class="error" id="capacity_error"></div>
            </div>

            <div class="form-group">
                <label for="driver_id">Призначити водія</label>
                <select id="driver_id" name="driver_id">
                    <option value="">Оберіть водія</option>
                    <?php if (isset($drivers) && !empty($drivers)): ?>
                        <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo $driver['id']; ?>"
                                    <?php 
                                    $selected = false;
                                    if (isset($vehicle) && $vehicle['driver_id'] == $driver['id']) {
                                        $selected = true;
                                    } elseif (isset($formData['driver_id']) && $formData['driver_id'] == $driver['id']) {
                                        $selected = true;
                                    }
                                    echo $selected ? 'selected' : '';
                                    ?>>
                                <?php echo htmlspecialchars($driver['name'] . ' (' . $driver['license_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Статус</label>
                <select id="status" name="status">
                    <?php 
                    $currentStatus = isset($vehicle) ? $vehicle['status'] : (isset($formData['status']) ? $formData['status'] : 'active');
                    ?>
                    <option value="active" <?php echo ($currentStatus == 'active') ? 'selected' : ''; ?>>Активний</option>
                    <option value="repair" <?php echo ($currentStatus == 'repair') ? 'selected' : ''; ?>>В ремонті</option>
                    <option value="inactive" <?php echo ($currentStatus == 'inactive') ? 'selected' : ''; ?>>Неактивний</option>
                </select>
            </div>

            <div class="form-group">
                <label for="photo">Фото автомобіля</label>
                
                <?php if (isset($vehicle) && $vehicle['photo'] && file_exists($vehicle['photo'])): ?>
                    <div class="current-photo">
                        <p><strong>Поточне фото:</strong></p>
                        <img src="<?php echo htmlspecialchars($vehicle['photo']); ?>" alt="Фото автомобіля">
                        <p><small>Оберіть новий файл, щоб замінити поточне фото</small></p>
                    </div>
                <?php endif; ?>
                
                <input type="file" id="photo" name="photo" accept="image/*">
                <div class="error" id="photo_error"></div>
                <small>Дозволені формати: JPG, PNG, GIF. Максимальний розмір: 5MB</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">
                    <?php echo isset($vehicle) ? 'Оновити автомобіль' : 'Додати автомобіль'; ?>
                </button>
                <a href="index.php?controller=vehicles" class="btn btn-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</body>
</html>
