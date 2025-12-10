<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Читачі</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .nav a:hover { color: #3498db; }
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
    <a href="index.php?controller=readers"><strong>Читачі</strong></a>
    <a href="index.php?controller=loans">Видачі</a>
    <a href="search.php">Пошук</a>
</div>

<h1>📖 Управління читачами</h1>

<a href="index.php?controller=readers&action=create" class="btn">Додати читача</a>

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

<?php if (!empty($readers)): ?>
    <table>
        <tr>
            <th>Ім'я</th>
            <th>Номер квитка</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>Дата реєстрації</th>
            <th>Активних видач</th>
            <th>Дії</th>
        </tr>
        <?php foreach ($readers as $reader): ?>
            <tr>
                <td><?php echo htmlspecialchars($reader['name']); ?></td>
                <td><?php echo htmlspecialchars($reader['card_number']); ?></td>
                <td><?php echo htmlspecialchars($reader['phone']); ?></td>
                <td><?php echo htmlspecialchars($reader['email']); ?></td>
                <td><?php echo htmlspecialchars($reader['registration_date']); ?></td>
                <td><?php echo isset($reader['active_loans']) ? $reader['active_loans'] : 0; ?></td>
                <td>
                    <a href="index.php?controller=readers&action=edit&id=<?php echo $reader['id']; ?>">Редагувати</a>
                    |
                    <form method="POST" action="index.php?controller=readers&action=delete" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $reader['id']; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <button type="submit" class="delete-btn" onclick="return confirm('Видалити читача <?php echo htmlspecialchars($reader['name']); ?>?')">
                            Видалити
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає читачів для відображення</p>
<?php endif; ?>
</body>
</html>
