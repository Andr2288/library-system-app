<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($reader) ? 'Редагувати' : 'Додати'; ?> читача</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:invalid, select:invalid { border-color: #e74c3c; }
        input:valid, select:valid { border-color: #27ae60; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; }
        .error { color: #e74c3c; font-size: 14px; margin-top: 5px; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .required { color: #e74c3c; }
    </style>
    <script>
        function validateForm() {
            let isValid = true;
            
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            
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
            
            const cardNumber = document.getElementById('card_number').value.trim();
            if (!cardNumber) {
                document.getElementById('card_error').textContent = 'Номер квитка обов\'язковий';
                isValid = false;
            } else if (!/^RD\d{6}$/i.test(cardNumber)) {
                document.getElementById('card_error').textContent = 'Формат: RD123456';
                isValid = false;
            }
            
            const phone = document.getElementById('phone').value.trim();
            if (!phone) {
                document.getElementById('phone_error').textContent = 'Телефон обов\'язковий';
                isValid = false;
            } else if (!/^\+380\d{9}$/.test(phone)) {
                document.getElementById('phone_error').textContent = 'Формат: +380501234567';
                isValid = false;
            }
            
            const email = document.getElementById('email').value.trim();
            if (!email) {
                document.getElementById('email_error').textContent = 'Email обов\'язковий';
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('email_error').textContent = 'Невірний формат email';
                isValid = false;
            }
            
            return isValid;
        }
        
        function formatPhone() {
            const phoneInput = document.getElementById('phone');
            let value = phoneInput.value.replace(/\D/g, '');
            
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
        
        function formatCardNumber() {
            const input = document.getElementById('card_number');
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
        <a href="index.php?controller=books">Книги</a>
        <a href="index.php?controller=readers">Читачі</a>
        <a href="index.php?controller=loans">Видачі</a>
        <a href="search.php">Пошук</a>
    </div>

    <div class="form-container">
        <h1><?php echo isset($reader) ? 'Редагувати читача' : 'Додати нового читача'; ?></h1>
        
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
            <?php if (isset($reader)): ?>
                <input type="hidden" name="reader_id" value="<?php echo $reader['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Повне ім'я <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       minlength="2" maxlength="100"
                       pattern="[а-яА-ЯіІїЇєЄ\s]+"
                       placeholder="Олена Петренко"
                       value="<?php echo isset($reader) ? htmlspecialchars($reader['name']) : (isset($formData['name']) ? htmlspecialchars($formData['name']) : ''); ?>"
                       required>
                <div class="error" id="name_error"></div>
            </div>

            <div class="form-group">
                <label for="card_number">Номер читацького квитка <span class="required">*</span></label>
                <input type="text" id="card_number" name="card_number" 
                       pattern="[A-Za-z]{2}\d{6}"
                       placeholder="RD123456"
                       maxlength="8"
                       oninput="formatCardNumber()"
                       value="<?php echo isset($reader) ? htmlspecialchars($reader['card_number']) : (isset($formData['card_number']) ? htmlspecialchars($formData['card_number']) : ''); ?>"
                       required>
                <div class="error" id="card_error"></div>
                <small>Формат: RD123456 (2 літери + 6 цифр)</small>
            </div>

            <div class="form-group">
                <label for="phone">Номер телефону <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" 
                       pattern="\+380\d{9}"
                       placeholder="+380501234567"
                       maxlength="13"
                       oninput="formatPhone()"
                       value="<?php echo isset($reader) ? htmlspecialchars($reader['phone']) : (isset($formData['phone']) ? htmlspecialchars($formData['phone']) : ''); ?>"
                       required>
                <div class="error" id="phone_error"></div>
                <small>Формат: +380XXXXXXXXX</small>
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" 
                       placeholder="email@example.com"
                       value="<?php echo isset($reader) ? htmlspecialchars($reader['email']) : (isset($formData['email']) ? htmlspecialchars($formData['email']) : ''); ?>"
                       required>
                <div class="error" id="email_error"></div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">
                    <?php echo isset($reader) ? 'Оновити читача' : 'Додати читача'; ?>
                </button>
                <a href="index.php?controller=readers" class="btn btn-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</body>
</html>
