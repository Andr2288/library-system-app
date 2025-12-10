<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($book) ? 'Редагувати' : 'Додати'; ?> книгу</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
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
        .current-cover { margin: 10px 0; }
        .current-cover img { max-width: 200px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
    <script>
        function validateForm() {
            let isValid = true;
            
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            
            const title = document.getElementById('title').value.trim();
            if (!title) {
                document.getElementById('title_error').textContent = 'Назва обов\'язкова';
                isValid = false;
            } else if (title.length < 2) {
                document.getElementById('title_error').textContent = 'Назва має містити мінімум 2 символи';
                isValid = false;
            }
            
            const author = document.getElementById('author').value.trim();
            if (!author) {
                document.getElementById('author_error').textContent = 'Автор обов\'язковий';
                isValid = false;
            }
            
            const isbn = document.getElementById('isbn').value.trim();
            if (!isbn) {
                document.getElementById('isbn_error').textContent = 'ISBN обов\'язковий';
                isValid = false;
            } else if (!/^978-\d{3}-\d{2}-\d{4}-\d$/i.test(isbn)) {
                document.getElementById('isbn_error').textContent = 'Формат: 978-XXX-XX-XXXX-X';
                isValid = false;
            }
            
            const year = document.getElementById('year').value;
            const currentYear = new Date().getFullYear();
            if (!year) {
                document.getElementById('year_error').textContent = 'Рік обов\'язковий';
                isValid = false;
            } else if (year < 1000 || year > currentYear) {
                document.getElementById('year_error').textContent = `Рік має бути між 1000 та ${currentYear}`;
                isValid = false;
            }
            
            const copiesTotal = document.getElementById('copies_total').value;
            if (!copiesTotal || copiesTotal <= 0 || copiesTotal > 100) {
                document.getElementById('copies_total_error').textContent = 'Кількість від 1 до 100';
                isValid = false;
            }
            
            const copiesAvailable = document.getElementById('copies_available').value;
            if (copiesAvailable < 0 || copiesAvailable > copiesTotal) {
                document.getElementById('copies_available_error').textContent = 'Доступних не більше загальної кількості';
                isValid = false;
            }
            
            const coverImage = document.getElementById('cover_image').files[0];
            if (coverImage) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(coverImage.type)) {
                    document.getElementById('cover_error').textContent = 'Дозволені формати: JPG, PNG, GIF';
                    isValid = false;
                } else if (coverImage.size > 5 * 1024 * 1024) {
                    document.getElementById('cover_error').textContent = 'Максимальний розмір: 5MB';
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
        <a href="index.php?controller=books">Книги</a>
        <a href="index.php?controller=readers">Читачі</a>
        <a href="index.php?controller=loans">Видачі</a>
        <a href="search.php">Пошук</a>
    </div>

    <div class="form-container">
        <h1><?php echo isset($book) ? 'Редагувати книгу' : 'Додати нову книгу'; ?></h1>
        
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
            <?php if (isset($book)): ?>
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">Назва книги <span class="required">*</span></label>
                <input type="text" id="title" name="title" 
                       minlength="2" maxlength="200"
                       placeholder="Кобзар"
                       value="<?php echo isset($book) ? htmlspecialchars($book['title']) : (isset($formData['title']) ? htmlspecialchars($formData['title']) : ''); ?>"
                       required>
                <div class="error" id="title_error"></div>
            </div>

            <div class="form-group">
                <label for="author">Автор <span class="required">*</span></label>
                <input type="text" id="author" name="author" 
                       minlength="2" maxlength="100"
                       placeholder="Тарас Шевченко"
                       value="<?php echo isset($book) ? htmlspecialchars($book['author']) : (isset($formData['author']) ? htmlspecialchars($formData['author']) : ''); ?>"
                       required>
                <div class="error" id="author_error"></div>
            </div>

            <div class="form-group">
                <label for="isbn">ISBN <span class="required">*</span></label>
                <input type="text" id="isbn" name="isbn" 
                       pattern="978-\d{3}-\d{2}-\d{4}-\d"
                       placeholder="978-966-03-5128-8"
                       value="<?php echo isset($book) ? htmlspecialchars($book['isbn']) : (isset($formData['isbn']) ? htmlspecialchars($formData['isbn']) : ''); ?>"
                       required>
                <div class="error" id="isbn_error"></div>
                <small>Формат: 978-XXX-XX-XXXX-X</small>
            </div>

            <div class="form-group">
                <label for="year">Рік видання <span class="required">*</span></label>
                <input type="number" id="year" name="year" 
                       min="1000" max="<?php echo date('Y'); ?>"
                       value="<?php echo isset($book) ? $book['year'] : (isset($formData['year']) ? $formData['year'] : ''); ?>"
                       required>
                <div class="error" id="year_error"></div>
            </div>

            <div class="form-group">
                <label for="copies_total">Загальна кількість примірників <span class="required">*</span></label>
                <input type="number" id="copies_total" name="copies_total" 
                       min="1" max="100" step="1"
                       value="<?php echo isset($book) ? $book['copies_total'] : (isset($formData['copies_total']) ? $formData['copies_total'] : '1'); ?>"
                       required>
                <div class="error" id="copies_total_error"></div>
            </div>

            <div class="form-group">
                <label for="copies_available">Доступно примірників <span class="required">*</span></label>
                <input type="number" id="copies_available" name="copies_available" 
                       min="0" max="100" step="1"
                       value="<?php echo isset($book) ? $book['copies_available'] : (isset($formData['copies_available']) ? $formData['copies_available'] : '1'); ?>"
                       required>
                <div class="error" id="copies_available_error"></div>
            </div>

            <div class="form-group">
                <label for="category_id">Категорія</label>
                <select id="category_id" name="category_id">
                    <option value="">Оберіть категорію</option>
                    <?php if (isset($categories) && !empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php 
                                    $selected = false;
                                    if (isset($book) && $book['category_id'] == $category['id']) {
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
            </div>

            <div class="form-group">
                <label for="status">Статус</label>
                <select id="status" name="status">
                    <?php 
                    $currentStatus = isset($book) ? $book['status'] : (isset($formData['status']) ? $formData['status'] : 'available');
                    ?>
                    <option value="available" <?php echo ($currentStatus == 'available') ? 'selected' : ''; ?>>Доступна</option>
                    <option value="damaged" <?php echo ($currentStatus == 'damaged') ? 'selected' : ''; ?>>Пошкоджена</option>
                    <option value="lost" <?php echo ($currentStatus == 'lost') ? 'selected' : ''; ?>>Втрачена</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cover_image">Обкладинка книги</label>
                
                <?php if (isset($book) && $book['cover_image'] && file_exists($book['cover_image'])): ?>
                    <div class="current-cover">
                        <p><strong>Поточна обкладинка:</strong></p>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Обкладинка книги">
                        <p><small>Оберіть новий файл, щоб замінити поточну обкладинку</small></p>
                    </div>
                <?php endif; ?>
                
                <input type="file" id="cover_image" name="cover_image" accept="image/*">
                <div class="error" id="cover_error"></div>
                <small>Дозволені формати: JPG, PNG, GIF. Максимальний розмір: 5MB</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">
                    <?php echo isset($book) ? 'Оновити книгу' : 'Додати книгу'; ?>
                </button>
                <a href="index.php?controller=books" class="btn btn-secondary">Скасувати</a>
            </div>
        </form>
    </div>
</body>
</html>
