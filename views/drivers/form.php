<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($driver) ? 'Редагувати' : 'Додати'; ?> водія</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:invalid, select:invalid { border-color: #dc3545; }
        input:valid, select:valid { border-color: #28a745; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
        .required { color: #dc3545; }
        .info-box { background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; font-size: 14px; }
    </style>
    <script>
        function validateForm() {
            let isValid = true;
            
            // Очистити попередні помилки
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            
            // Перевірка імені
            const name = document.getElementById('name').value.trim();
            if (!name) {
                document.getElementById('name_error').textContent = 'Ім\'я обов\'язкове';
                isValid = false;
            } else if (name.length < 2) {
                document.getElementById('name_error').textContent = 'Ім\'я має містити мінімум 2 символи';
                isValid = false;
            } else if (!/^[а-яА-ЯіІїЇєЄ\s]+$/u.test(name)) {
                document.getElementById('name_error').textContent = 'Ім\'я може містити лише українські літери та пробіли';
                isValid = false;
            }
            
            // Перевірка номера посвідчення
            const licenseNumber = document.getElementById('license_number').value.trim();
            if (!licenseNumber) {
                document.getElementById('license_error').textContent = 'Номер посвідчення обов\'язковий';
                isValid = false;
            } else if (!/^[A-Z]{2}\d{6}$/i.test(licenseNumber)) {
                document.getElementById('license_error').textContent = 'Формат: AA123456 (2 літери + 6 цифр)';
                isValid = false;
            }
            
            // Перевірка телефону
            const phone = document.getElementById('phone').value.trim();
            if (!phone) {
                document.getElementById('phone_error').textContent = 'Телефон обов\'язковий';
                isValid = false;
            } else if (!/^\+380\d{9}$/.test(phone)) {
                document.getElementById('phone_error').textContent = 'Формат: +380501234567';
                isValid = false;
            }
            
            // Перевірка досвіду
            const experience = document.getElementById('experience_years').value;
            if (!experience) {
                document.getElementById('experience_error').textContent = 'Досвід обов\'язковий';
                isValid = false;
            } else if (experience < 0 || experience > 50) {
                document.getElementById('experience_error').textContent = 'Досвід від 0 до 50 років';
                isValid = false;
            }
            
            // Перевірка категорії
            const category = document.getElementById('category').value;
            if (!category) {
                document.getElementById('category_error').textContent = 'Категорія обов\'язкова';
                isValid = false;
            }
            
            return isValid;
        }
        
        function formatPhone() {
            const phoneInput = document.getElementById('phone');
            let value = phoneInput.value.replace(/\D/g, ''); // Видалити все окрім цифр
            
            if (value.length > 0 && !value.startsWith('380')) {
                if (value.startsWith('0')) {
                    value = '380' + value.slice(1);
                } else if (value.length <= 9) {
                    value = '380' + value;
                }
            }
            
            if (value.length > 12) {
                value = value.slice(0, 12);
            }
            
            phoneInput.value = value ? '+' + value : '';
        }
        
        function formatLicenseNumber() {
            const input = document.getElementById('license_number');
            let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            if (value.length > 8) {
                value = value.slice(0, 8);
            }
            
            input.value = value;
        }
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
        <h1><?php echo isset($driver) ? 'Редагувати водія' : 'Додати нового водія'; ?></h1>
        
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
            <?php if (isset($driver)): ?>
                <input type="hidden" name="driver_id" value="<?php echo $driver['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Повне ім'я <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       minlength="2" maxlength="100"
                       pattern="[а-яА-ЯіІїЇєЄ\s]+"
                       placeholder="Іван Петренко"
                       value="<?php echo isset($driver) ? htmlspecialchars($driver['name']) : (isset($formData['name']) ? htmlspecialchars($formData['name']) : ''); ?>"
                       required>
                <div class="error" id="name_error"></div>
            </div>

            <div class="form-group">
                <label for="license_number">Номер водійського посвідчення <span class="required">*</span></label>
                <input type="text" id="license_number" name="license_number" 
                       pattern="[A-Za-z]{2}\d{6}"
                       placeholder="AA123456"
                       maxlength="8"
                       oninput="formatLicenseNumber()"
                       value="<?php echo isset($driver) ? htmlspecialchars($driver['license_number']) : (isset($formData['license_number']) ? htmlspecialchars($formData['license_number']) : ''); ?>"
                       required>
                <div class="error" id="license_error"></div>
                <small>Формат: 2 літери + 6 цифр (наприклад: AA123456)</small>
            </div>

            <div class="form-group">
                <label for="phone">Номер телефону <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" 
                       pattern="\+380\d{9}"
                       placeholder="+380501234567"
                       maxlength="13"
                       oninput="formatPhone()"
                       value="<?php echo isset($driver) ? htmlspecialchars($driver['phone']) : (isset($formData['phone']) ? htmlspecialchars($formData['phone']) : ''); ?>"
                       required>
                <div class="error" id="phone_error"></div>
                <small>Формат: +380XXXXXXXXX</small>
            </div>

            <div class="form-group">
                <label for="experience_years">Досвід водіння (років) <span class="required">*</span></label>
                <input type="number" id="experience_years" name="experience_years" 
                       min="0" max="50" step="1"
                       value="<?php echo isset($driver) ? $driver['experience_years'] : (isset($formData['experience_years']) ? $formData['experience_years'] : ''); ?>"
                       required>
                <div class="error" id="experience_error"></div>
            </div>

            <div class="form-group">
                <label for="category">Категорія водійських прав <span class="required">*</span></label>
                <select id="category" name="category" required>
                    <option value="">Оберіть категорію</option>
                    <?php 
                    $categories = ['A', 'B', 'C', 'D', 'BE', 'CE', 'DE', 'C+E', 'D+E'];
                    $currentCategory = isset($driver) ? $driver['category'] : (isset($formData['category']) ? $formData['category'] : '');
                    ?>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($currentCategory == $cat) ? 'selected' : ''; ?>>
                            Категорія <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="error" id="category_error"></div>
            </div>

            <?php if (isset($driver)): ?>
                <div class="info-box">
                    <strong>Додаткова інформація:</strong><br>
                    ID в системі: <?php echo $driver['id']; ?><br>
                    <?php if (isset($driver['license_plate']) && $driver['license_plate']): ?>
                        Призначений автомобіль: <?php echo htmlspecialchars($driver['license_plate'] . ' (' . $driver['brand'] . ' ' . $driver['model'] . ')'); ?>
                    <?php else: ?>
                        Автомобіль не призначений
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <button type="submit" class="btn">
                    <?php echo isset($driver) ? 'Оновити водія' : 'Додати водія'; ?>
                </button>
                <a href="index.php?controller=drivers" class="btn btn-secondary">Скасувати</a>
            </div>
        </form>
    </div>

    <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 4px; font-size: 14px; max-width: 600px; margin-left: auto; margin-right: auto;">
        <strong>Поради для заповнення:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <li>Вводьте повне ім'я українською мовою</li>
            <li>Номер посвідчення має формат: 2 літери + 6 цифр</li>
            <li>Телефон автоматично форматується при введенні</li>
            <li>Оберіть відповідну категорію для типу транспорту</li>
        </ul>
    </div>
</body>
</html>