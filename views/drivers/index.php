<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Водії</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
        .nav a:hover { color: #007bff; }
        .delete-btn { background: none; border: none; color: #dc3545; cursor: pointer; text-decoration: underline; padding: 0; font-size: 14px; }
        .delete-btn:hover { color: #a71d2a; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
<div class="nav">
    <a href="index.php">Головна</a>
    <a href="index.php?controller=vehicles">Автомобілі</a>
    <a href="index.php?controller=drivers"><strong>Водії</strong></a>
    <a href="index.php?controller=trips">Рейси</a>
    <a href="search.php">Пошук</a>
</div>

<h1>Управління водіями</h1>

<a href="index.php?controller=drivers&action=create" class="btn">Додати водія</a>

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

<?php if (!empty($drivers)): ?>
    <table>
        <tr>
            <th>Ім'я</th>
            <th>Посвідчення</th>
            <th>Телефон</th>
            <th>Досвід (роки)</th>
            <th>Категорія</th>
            <th>Автомобіль</th>
            <th>Дії</th>
        </tr>
        <?php foreach ($drivers as $driver): ?>
            <tr>
                <td><?php echo htmlspecialchars($driver['name']); ?></td>
                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                <td><?php echo $driver['experience_years']; ?></td>
                <td><?php echo htmlspecialchars($driver['category']); ?></td>
                <td>
                    <?php if (isset($driver['license_plate']) && $driver['license_plate']): ?>
                        <?php echo htmlspecialchars($driver['license_plate'] . ' (' . $driver['brand'] . ' ' . $driver['model'] . ')'); ?>
                    <?php else: ?>
                        Не призначений
                    <?php endif; ?>
                </td>
                <td>
                    <a href="index.php?controller=drivers&action=edit&id=<?php echo $driver['id']; ?>">Редагувати</a>
                    |
                    <form method="POST" action="index.php?controller=drivers&action=delete" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $driver['id']; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <button type="submit" class="delete-btn" onclick="return confirm('Видалити водія <?php echo htmlspecialchars($driver['name']); ?>?')">
                            Видалити
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає водіїв для відображення</p>
<?php endif; ?>
</body>
</html>