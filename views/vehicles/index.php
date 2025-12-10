<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Автомобілі</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
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
    <a href="index.php?controller=vehicles"><strong>Автомобілі</strong></a>
    <a href="index.php?controller=drivers">Водії</a>
    <a href="index.php?controller=trips">Рейси</a>
    <a href="search.php">Пошук</a>
</div>

<h1>Управління автомобілями</h1>

<a href="index.php?controller=vehicles&action=create" class="btn">Додати автомобіль</a>

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

<?php if (!empty($vehicles)): ?>
    <table>
        <tr>
            <th>Номер</th>
            <th>Марка</th>
            <th>Модель</th>
            <th>Рік</th>
            <th>Вантажність</th>
            <th>Водій</th>
            <th>Статус</th>
            <th>Дії</th>
        </tr>
        <?php foreach ($vehicles as $vehicle): ?>
            <tr>
                <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                <td><?php echo $vehicle['year']; ?></td>
                <td><?php echo $vehicle['capacity']; ?> т</td>
                <td><?php echo htmlspecialchars(isset($vehicle['driver_name']) ? $vehicle['driver_name'] : 'Не призначений'); ?></td>
                <td><?php echo htmlspecialchars($vehicle['status']); ?></td>
                <td>
                    <a href="index.php?controller=vehicles&action=edit&id=<?php echo $vehicle['id']; ?>">Редагувати</a>
                    |
                    <form method="POST" action="index.php?controller=vehicles&action=delete" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $vehicle['id']; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <button type="submit" class="delete-btn" onclick="return confirm('Видалити автомобіль <?php echo htmlspecialchars($vehicle['license_plate']); ?>?')">
                            Видалити
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає автомобілів для відображення</p>
<?php endif; ?>
</body>
</html>