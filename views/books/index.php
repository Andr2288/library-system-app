<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Книги</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .delete-btn { background: none; border: none; color: #e74c3c; cursor: pointer; text-decoration: underline; padding: 0; font-size: 14px; }
        .delete-btn:hover { color: #c0392b; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-available { color: #27ae60; font-weight: bold; }
        .status-damaged { color: #e67e22; }
        .status-lost { color: #e74c3c; }
    </style>
</head>
<body>
<div class="nav">
    <a href="index.php">Головна</a>
    <a href="index.php?controller=books"><strong>Книги</strong></a>
    <a href="index.php?controller=readers">Читачі</a>
    <a href="index.php?controller=loans">Видачі</a>
    <a href="search.php">Пошук</a>
</div>

<h1>📚 Управління книгами</h1>

<a href="index.php?controller=books&action=create" class="btn">Додати книгу</a>

<?php if (isset($message)): ?>
    <div class="alert alert-<?php echo isset($messageType) ? htmlspecialchars($messageType) : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        Помилка: <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($books)): ?>
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
            <th>Дії</th>
        </tr>
        <?php foreach ($books as $book): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                <td><?php echo htmlspecialchars($book['author']); ?></td>
                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                <td><?php echo $book['year']; ?></td>
                <td><?php echo $book['copies_total']; ?></td>
                <td><?php echo $book['copies_available']; ?></td>
                <td><?php echo htmlspecialchars(isset($book['category_name']) ? $book['category_name'] : 'Не вказана'); ?></td>
                <td>
                    <span class="status-<?php echo $book['status']; ?>">
                        <?php 
                        $statuses = ['available' => 'Доступна', 'damaged' => 'Пошкоджена', 'lost' => 'Втрачена'];
                        echo isset($statuses[$book['status']]) ? $statuses[$book['status']] : $book['status'];
                        ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?controller=books&action=edit&id=<?php echo $book['id']; ?>">Редагувати</a>
                    |
                    <form method="POST" action="index.php?controller=books&action=delete" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <button type="submit" class="delete-btn" onclick="return confirm('Видалити книгу <?php echo htmlspecialchars($book['title']); ?>?')">
                            Видалити
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає книг для відображення</p>
<?php endif; ?>
</body>
</html>
