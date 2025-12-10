<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бібліотечна система</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .nav { background: #ecf0f1; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #2c3e50; }
        .nav a:hover { color: #3498db; }
        .counter { background: #e8f5e9; padding: 10px; margin: 20px 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
<div class="header">
    <h1>📚 Бібліотечна система</h1>
    <p>Система управління бібліотекою</p>
</div>

<div class="nav">
    <a href="index.php"><strong>Головна</strong></a>
    <a href="index.php?controller=books">Книги</a>
    <a href="index.php?controller=readers">Читачі</a>
    <a href="index.php?controller=loans">Видачі</a>
    <a href="search.php">Пошук</a>
</div>

<div class="counter">
    <strong>Кількість відвідувань: <?php echo isset($visits) ? $visits : 0; ?></strong>
</div>

<h2>Звіт бібліотеки</h2>

<?php if (isset($error)): ?>
    <p style="color: red;">Помилка: <?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if (!empty($reports)): ?>
    <table>
        <tr>
            <th>Назва книги</th>
            <th>Автор</th>
            <th>ISBN</th>
            <th>Читач</th>
            <th>Категорія</th>
            <th>Дата видачі</th>
            <th>Статус</th>
        </tr>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($report['title']); ?></strong></td>
                <td><?php echo htmlspecialchars($report['author']); ?></td>
                <td><?php echo htmlspecialchars($report['isbn']); ?></td>
                <td><?php echo htmlspecialchars($report['reader_name']); ?></td>
                <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                <td><?php echo htmlspecialchars($report['loan_date']); ?></td>
                <td><?php echo htmlspecialchars($report['loan_status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає даних для відображення</p>
<?php endif; ?>
</body>
</html>
