<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Видачі книг</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin: 5px; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .nav a:hover { color: #3498db; }
        .status-active { color: #27ae60; font-weight: bold; }
        .status-returned { color: #95a5a6; }
        .status-overdue { color: #e74c3c; font-weight: bold; }
        .delete-btn { background: none; border: none; color: #e74c3c; cursor: pointer; text-decoration: underline; padding: 0; font-size: 14px; }
        .delete-btn:hover { color: #c0392b; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
<div class="nav">
    <a href="index.php">Головна</a>
    <a href="index.php?controller=books">Книги</a>
    <a href="index.php?controller=readers">Читачі</a>
    <a href="index.php?controller=loans"><strong>Видачі</strong></a>
    <a href="search.php">Пошук</a>
</div>

<h1>📖 Управління видачами</h1>

<a href="index.php?controller=loans&action=create" class="btn">Видати книгу</a>

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

<?php if (!empty($loans)): ?>
    <table>
        <tr>
            <th>Книга</th>
            <th>Читач</th>
            <th>Категорія</th>
            <th>Дата видачі</th>
            <th>Дата повернення</th>
            <th>Штраф (грн)</th>
            <th>Статус</th>
            <th>Дії</th>
        </tr>
        <?php foreach ($loans as $loan): ?>
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
                <td><?php echo $loan['fine_amount'] ? number_format($loan['fine_amount'], 2) : '-'; ?></td>
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
                <td>
                    <a href="index.php?controller=loans&action=edit&id=<?php echo $loan['id']; ?>">Редагувати</a>
                    |
                    <form method="POST" action="index.php?controller=loans&action=delete" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $loan['id']; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <button type="submit" class="delete-btn" onclick="return confirm('Видалити видачу?')">
                            Видалити
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає видач для відображення</p>
<?php endif; ?>
</body>
</html>
