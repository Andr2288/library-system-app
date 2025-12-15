<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($loan) ? 'Редагувати' : 'Створити'; ?> видачу</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 800px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .form-row { display: flex; gap: 15px; }
        .form-group { margin-bottom: 15px; flex: 1; }
        .form-group.full-width { flex: 100%; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:invalid, select:invalid { border-color: #e74c3c; }
        input:valid, select:valid { border-color: #27ae60; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; }
        .error { color: #e74c3c; font-size: 14px; margin-top: 5px; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .required { color: #e74c3c; }
        .info-box { background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 15px 0; }

        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
        }
    </style>
    <script>
        function validateForm() {
            let isValid = true;

            document.querySelectorAll('.error').forEach(el => el.textContent = '');

            const bookId = document.getElementById('book_id').value;
            if (!bookId) {
                document.getElementById('book_error').textContent = 'Оберіть книгу';
                isValid = false;
            }

            const readerId = document.getElementById('reader_id').value;
            if (!readerId) {
                document.getElementById('reader_error').textContent = 'Оберіть читача';
                isValid = false;
            }

            const categoryId = document.getElementById('category_id').value;
            if (!categoryId) {
                document.getElementById('category_error').textContent = 'Оберіть категорію';
                isValid = false;
            }

            const loanDate = document.getElementById('loan_date').value;
            if (!loanDate) {
                document.getElementById('loan_date_error').textContent = 'Вкажіть дату видачі';
                isValid = false;
            }

            const returnDate = document.getElementById('return_date').value;
            if (returnDate && loanDate) {
                const loanDateTime = new Date(loanDate);
                const returnDateTime = new Date(returnDate);
                if (returnDateTime <= loanDateTime) {
                    document.getElementById('return_date_error').textContent = 'Дата повернення має бути пізніше дати видачі';
                    isValid = false;
                }
            }

            const fineAmount = document.getElementById('fine_amount').value;
            if (fineAmount && (fineAmount < 0 || fineAmount > 10000)) {
                document.getElementById('fine_error').textContent = 'Штраф від 0 до 10000 грн';
                isValid = false;
            }

            return isValid;
        }

        function setCurrentDateTime() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('loan_date').value = now.toISOString().slice(0, 16);
        }

        function setReturnDate() {
            const loanDate = document.getElementById('loan_date').value;
            if (loanDate) {
                const date = new Date(loanDate);
                date.setDate(date.getDate() + 30); // +30 днів
                date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
                document.getElementById('return_date').value = date.toISOString().slice(0, 16);
            }
        }

        window.onload = function() {
            const loanDateInput = document.getElementById('loan_date');
            const isEditMode = document.querySelector('input[name="loan_id"]') !== null;

            if (!isEditMode && !loanDateInput.value) {
                setCurrentDateTime();
                setReturnDate();
            }
        };
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
    <h1><?php echo isset($loan) ? 'Редагувати видачу' : 'Видати книгу'; ?></h1>

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
        <?php if (isset($loan)): ?>
            <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="book_id">Книга <span class="required">*</span></label>
                <select id="book_id" name="book_id" required>
                    <option value="">Оберіть книгу</option>
                    <?php if (isset($books) && !empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                            <option value="<?php echo $book['id']; ?>"
                                    <?php
                                    $selected = false;
                                    if (isset($loan) && $loan['book_id'] == $book['id']) {
                                        $selected = true;
                                    } elseif (isset($formData['book_id']) && $formData['book_id'] == $book['id']) {
                                        $selected = true;
                                    }
                                    echo $selected ? 'selected' : '';
                                    ?>>
                                <?php echo htmlspecialchars($book['title'] . ' - ' . $book['author'] . ' (Доступно: ' . $book['copies_available'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="error" id="book_error"></div>
            </div>

            <div class="form-group">
                <label for="reader_id">Читач <span class="required">*</span></label>
                <select id="reader_id" name="reader_id" required>
                    <option value="">Оберіть читача</option>
                    <?php if (isset($readers) && !empty($readers)): ?>
                        <?php foreach ($readers as $reader): ?>
                            <option value="<?php echo $reader['id']; ?>"
                                    <?php
                                    $selected = false;
                                    if (isset($loan) && $loan['reader_id'] == $reader['id']) {
                                        $selected = true;
                                    } elseif (isset($formData['reader_id']) && $formData['reader_id'] == $reader['id']) {
                                        $selected = true;
                                    }
                                    echo $selected ? 'selected' : '';
                                    ?>>
                                <?php echo htmlspecialchars($reader['name'] . ' (' . $reader['card_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="error" id="reader_error"></div>
            </div>
        </div>

        <div class="form-group">
            <label for="category_id">Категорія <span class="required">*</span></label>
            <select id="category_id" name="category_id" required>
                <option value="">Оберіть категорію</option>
                <?php if (isset($categories) && !empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php
                                $selected = false;
                                if (isset($loan) && $loan['category_id'] == $category['id']) {
                                    $selected = true;
                                } elseif (isset($formData['category_id']) && $formData['category_id'] == $category['id']) {
                                    $selected = true;
                                }
                                echo $selected ? 'selected' : '';
                                ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="error" id="category_error"></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="loan_date">Дата видачі <span class="required">*</span></label>
                <input type="datetime-local" id="loan_date" name="loan_date"
                       onchange="setReturnDate()"
                       value="<?php echo isset($loan) && $loan['loan_date'] ? date('Y-m-d\TH:i', strtotime($loan['loan_date'])) : (isset($formData['loan_date']) ? $formData['loan_date'] : ''); ?>"
                       required>
                <div class="error" id="loan_date_error"></div>
            </div>

            <div class="form-group">
                <label for="return_date">Дата повернення (планована)</label>
                <input type="datetime-local" id="return_date" name="return_date"
                       value="<?php echo isset($loan) && $loan['return_date'] ? date('Y-m-d\TH:i', strtotime($loan['return_date'])) : (isset($formData['return_date']) ? $formData['return_date'] : ''); ?>">
                <div class="error" id="return_date_error"></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="actual_return_date">Фактична дата повернення</label>
                <input type="datetime-local" id="actual_return_date" name="actual_return_date"
                       value="<?php echo isset($loan) && $loan['actual_return_date'] ? date('Y-m-d\TH:i', strtotime($loan['actual_return_date'])) : (isset($formData['actual_return_date']) ? $formData['actual_return_date'] : ''); ?>">
            </div>

            <div class="form-group">
                <label for="fine_amount">Штраф (грн)</label>
                <input type="number" id="fine_amount" name="fine_amount"
                       min="0" max="10000" step="0.01"
                       placeholder="0.00"
                       value="<?php echo isset($loan) ? $loan['fine_amount'] : (isset($formData['fine_amount']) ? $formData['fine_amount'] : ''); ?>">
                <div class="error" id="fine_error"></div>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Статус видачі</label>
            <select id="status" name="status">
                <?php
                $currentStatus = isset($loan) ? $loan['status'] : (isset($formData['status']) ? $formData['status'] : 'active');
                ?>
                <option value="active" <?php echo ($currentStatus == 'active') ? 'selected' : ''; ?>>🟢 Активна</option>
                <option value="returned" <?php echo ($currentStatus == 'returned') ? 'selected' : ''; ?>>✅ Повернена</option>
                <option value="overdue" <?php echo ($currentStatus == 'overdue') ? 'selected' : ''; ?>>🔴 Прострочена</option>
            </select>
        </div>

        <?php if (isset($loan)): ?>
            <div class="info-box">
                <strong>Інформація про видачу:</strong><br>
                ID видачі: <?php echo $loan['id']; ?><br>
                Створено: <?php echo isset($loan['loan_date']) ? date('d.m.Y H:i', strtotime($loan['loan_date'])) : 'Невідомо'; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <button type="submit" class="btn">
                <?php echo isset($loan) ? '📝 Оновити видачу' : '📚 Видати книгу'; ?>
            </button>
            <a href="index.php?controller=loans" class="btn btn-secondary">❌ Скасувати</a>
        </div>
    </form>
</div>

<div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 4px; font-size: 14px; max-width: 800px; margin-left: auto; margin-right: auto;">
    <strong>Поради:</strong>
    <ul style="margin: 10px 0 0 20px;">
        <li>Оберіть книгу з доступними примірниками</li>
        <?php if (!isset($loan)): ?>
            <li>Дата видачі встановлюється автоматично на поточний час</li>
        <?php endif; ?>
        <li>Планована дата повернення автоматично встановлюється +30 днів</li>
        <li>Штраф встановлюється у разі прострочення повернення</li>
        <li>Статус "Повернена" автоматично поверне примірник книги</li>
    </ul>
</div>
</body>
</html>