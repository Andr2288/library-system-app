<?php
require_once 'config/database.php';
require_once 'models/BookModel.php';
require_once 'models/ReaderModel.php';
require_once 'models/LoanModel.php';

$database = new Database();
$pdo = $database->getConnection();

$bookModel = new BookModel();
$readerModel = new ReaderModel();
$loanModel = new LoanModel();

$results = [];
$searchType = '';
$searchQuery = '';
$errors = [];

function validateInput($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

if ($_POST) {
    $searchType = validateInput($_POST['search_type']);
    $searchQuery = validateInput($_POST['search_query']);

    if (empty($searchType)) {
        $errors[] = "Оберіть тип пошуку";
    }

    if (empty($searchQuery) || strlen(trim($searchQuery)) === 0) {
        $errors[] = "Введіть пошуковий запит";
    } elseif (strlen($searchQuery) < 2) {
        $errors[] = "Пошуковий запит має містити мінімум 2 символи";
    }

    if (empty($errors)) {
        try {
            switch ($searchType) {
                case 'book_title':
                    $stmt = $pdo->prepare("
                        SELECT b.*, c.name as category_name 
                        FROM books b 
                        LEFT JOIN categories c ON b.category_id = c.id 
                        WHERE b.title LIKE ?
                    ");
                    $stmt->execute(["%$searchQuery%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'book_author':
                    $stmt = $pdo->prepare("
                        SELECT b.*, c.name as category_name 
                        FROM books b 
                        LEFT JOIN categories c ON b.category_id = c.id 
                        WHERE b.author LIKE ?
                    ");
                    $stmt->execute(["%$searchQuery%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'book_isbn':
                    if (!preg_match('/^978-\d{3}-\d{2}-\d{4}-\d$/', $searchQuery)) {
                        $errors[] = "Невірний формат ISBN. Формат: 978-XXX-XX-XXXX-X";
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT b.*, c.name as category_name 
                            FROM books b 
                            LEFT JOIN categories c ON b.category_id = c.id 
                            WHERE b.isbn LIKE ?
                        ");
                        $stmt->execute(["%$searchQuery%"]);
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                    break;

                case 'reader_name':
                    $stmt = $pdo->prepare("
                        SELECT r.*, COUNT(l.id) as active_loans
                        FROM readers r 
                        LEFT JOIN loans l ON r.id = l.reader_id AND l.status = 'active'
                        WHERE r.name LIKE ?
                        GROUP BY r.id
                    ");
                    $stmt->execute(["%$searchQuery%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'reader_card':
                    $stmt = $pdo->prepare("
                        SELECT r.*, COUNT(l.id) as active_loans
                        FROM readers r 
                        LEFT JOIN loans l ON r.id = l.reader_id AND l.status = 'active'
                        WHERE r.card_number LIKE ?
                        GROUP BY r.id
                    ");
                    $stmt->execute(["%$searchQuery%"]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;

                case 'loan_status':
                    $validStatuses = ['active', 'returned', 'overdue'];
                    if (!in_array(strtolower($searchQuery), $validStatuses)) {
                        $errors[] = "Невірний статус. Доступні: active, returned, overdue";
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT l.*, b.title, b.author, b.isbn,
                                   r.name as reader_name, r.card_number,
                                   c.name as category_name
                            FROM loans l
                            JOIN books b ON l.book_id = b.id
                            JOIN readers r ON l.reader_id = r.id  
                            JOIN categories c ON l.category_id = c.id
                            WHERE l.status LIKE ?
                            ORDER BY l.loan_date DESC
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
    <title>Пошук - Бібліотечна система</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; border-radius: 4px; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .nav a:hover, .nav a.active { color: #3498db; font-weight: bold; }

        .search-form { background: #e8f5e9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-row { display: flex; gap: 15px; align-items: end; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group select:invalid, .form-group input:invalid { border-color: #e74c3c; }

        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #2980b9; }

        .error-list { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error-list ul { margin: 5px 0 0 20px; }

        .results { margin-top: 20px; }
        .results-header { background: #3498db; color: white; padding: 10px; border-radius: 4px 4px 0 0; }
        .no-results { text-align: center; padding: 40px; color: #7f8c8d; }

        table { width: 100%; border-collapse: collapse; border: 1px solid #ddd; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #34495e; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        .required { color: #e74c3c; }
        .status-active { color: #27ae60; font-weight: bold; }
        .status-returned { color: #95a5a6; }
        .status-overdue { color: #e74c3c; font-weight: bold; }

        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .container { margin: 10px; padding: 15px; }
        }
    </style>
    <script>
        function validateSearchForm() {
            const errorDiv = document.getElementById('client-errors');
            errorDiv.innerHTML = '';

            const searchType = document.getElementById('search_type').value.trim();
            const searchQuery = document.getElementById('search_query').value.trim();
            let errors = [];

            if (!searchType) {
                errors.push('Оберіть тип пошуку');
            }

            if (!searchQuery) {
                errors.push('Введіть пошуковий запит');
            } else if (searchQuery.length < 2) {
                errors.push('Пошуковий запит має містити мінімум 2 символи');
            }

            if (searchType === 'book_isbn' && searchQuery) {
                if (!/^978-\d{3}-\d{2}-\d{4}-\d$/.test(searchQuery)) {
                    errors.push('ISBN має формат: 978-XXX-XX-XXXX-X');
                }
            }

            if (searchType === 'loan_status' && searchQuery) {
                const validStatuses = ['active', 'returned', 'overdue'];
                if (!validStatuses.includes(searchQuery.toLowerCase())) {
                    errors.push('Статус має бути: active, returned або overdue');
                }
            }

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
                'book_title': 'Кобзар',
                'book_author': 'Тарас Шевченко',
                'book_isbn': '978-966-03-5128-8',
                'reader_name': 'Олена Петренко',
                'reader_card': 'RD123456',
                'loan_status': 'active, returned, overdue'
            };

            searchInput.placeholder = placeholders[searchType] || 'Введіть пошуковий запит';
        }
    </script>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="index.php">Головна</a>
        <a href="index.php?controller=books">Книги</a>
        <a href="index.php?controller=readers">Читачі</a>
        <a href="index.php?controller=loans">Видачі</a>
        <a href="search.php" class="active">Пошук</a>
    </div>

    <h1>🔍 Пошук по базі даних</h1>

    <form method="post" onsubmit="return validateSearchForm()" class="search-form">
        <div class="form-row">
            <div class="form-group">
                <label for="search_type">Тип пошуку <span class="required">*</span></label>
                <select id="search_type" name="search_type" onchange="updateSearchPlaceholder()" required>
                    <option value="">Оберіть тип пошуку</option>
                    <option value="book_title" <?php echo ($searchType == 'book_title') ? 'selected' : ''; ?>>
                        📚 Книга за назвою
                    </option>
                    <option value="book_author" <?php echo ($searchType == 'book_author') ? 'selected' : ''; ?>>
                        ✍️ Книга за автором
                    </option>
                    <option value="book_isbn" <?php echo ($searchType == 'book_isbn') ? 'selected' : ''; ?>>
                        🔢 Книга за ISBN
                    </option>
                    <option value="reader_name" <?php echo ($searchType == 'reader_name') ? 'selected' : ''; ?>>
                        👤 Читач за іменем
                    </option>
                    <option value="reader_card" <?php echo ($searchType == 'reader_card') ? 'selected' : ''; ?>>
                        🎫 Читач за номером квитка
                    </option>
                    <option value="loan_status" <?php echo ($searchType == 'loan_status') ? 'selected' : ''; ?>>
                        📋 Видачі за статусом
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
                <button type="submit" class="btn">🔍 Шукати</button>
            </div>
        </div>
    </form>

    <div id="client-errors"></div>

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
                <?php if ($searchType == 'book_title' || $searchType == 'book_author' || $searchType == 'book_isbn'): ?>
                    <table>
                        <tr>
                            <th>Назва</th>
                            <th>Автор</th>
                            <th>ISBN</th>
                            <th>Рік</th>
                            <th>Примірників</th>
                            <th>Доступно</th>
                            <th>Категорія</th>
                            <th>Статус</th>
                        </tr>
                        <?php foreach ($results as $book): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td><?php echo $book['year']; ?></td>
                                <td><?php echo $book['copies_total']; ?></td>
                                <td><?php echo $book['copies_available']; ?></td>
                                <td><?php echo $book['category_name'] ? htmlspecialchars($book['category_name']) : 'Не вказана'; ?></td>
                                <td><?php echo htmlspecialchars($book['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($searchType == 'reader_name' || $searchType == 'reader_card'): ?>
                    <table>
                        <tr>
                            <th>Ім'я</th>
                            <th>Номер квитка</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Дата реєстрації</th>
                            <th>Активних видач</th>
                        </tr>
                        <?php foreach ($results as $reader): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($reader['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($reader['card_number']); ?></td>
                                <td><?php echo htmlspecialchars($reader['phone']); ?></td>
                                <td><?php echo htmlspecialchars($reader['email']); ?></td>
                                <td><?php echo htmlspecialchars($reader['registration_date']); ?></td>
                                <td><?php echo isset($reader['active_loans']) ? $reader['active_loans'] : 0; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                <?php elseif ($searchType == 'loan_status'): ?>
                    <table>
                        <tr>
                            <th>Книга</th>
                            <th>Читач</th>
                            <th>Категорія</th>
                            <th>Дата видачі</th>
                            <th>Дата повернення</th>
                            <th>Штраф</th>
                            <th>Статус</th>
                        </tr>
                        <?php foreach ($results as $loan): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($loan['title']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($loan['author']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($loan['reader_name']); ?><br>
                                    <small><?php echo htmlspecialchars($loan['card_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($loan['category_name']); ?></td>
                                <td><?php echo $loan['loan_date'] ? date('d.m.Y H:i', strtotime($loan['loan_date'])) : '-'; ?></td>
                                <td><?php echo $loan['return_date'] ? date('d.m.Y H:i', strtotime($loan['return_date'])) : '-'; ?></td>
                                <td><?php echo $loan['fine_amount'] ? number_format($loan['fine_amount'], 2) . ' грн' : '-'; ?></td>
                                <td>
                                    <span class="status-<?php echo $loan['status']; ?>">
                                        <?php
                                        $statuses = [
                                                'active' => 'Активна',
                                                'returned' => 'Повернена',
                                                'overdue' => 'Прострочена'
                                        ];
                                        echo isset($statuses[$loan['status']]) ? $statuses[$loan['status']] : $loan['status'];
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
