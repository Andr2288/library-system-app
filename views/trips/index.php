<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Рейси</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin: 5px; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
        .nav a:hover { color: #007bff; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-completed { color: #6c757d; }
        .status-planned { color: #ffc107; }
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
    <a href="index.php?controller=drivers">Водії</a>
    <a href="index.php?controller=trips"><strong>Рейси</strong></a>
    <a href="search.php">Пошук</a>
</div>

<h1>Управління рейсами</h1>

<a href="index.php?controller=trips&action=create" class="btn">Додати рейс</a>

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

<?php if (!empty($trips)): ?>
    <table>
        <tr>
            <th>Автомобіль</th>
            <th>Водій</th>
            <th>Маршрут</th>
            <th>Відстань</th>
            <th>Час початку</th>
            <th>Час закінчення</th>
            <th>Паливо</th>
            <th>Статус</th>
            <th>Дії</th>
        </tr>
        <?php foreach ($trips as $trip): ?>
            <tr>
                <td><?php echo htmlspecialchars($trip['license_plate'] . ' (' . $trip['brand'] . ' ' . $trip['model'] . ')'); ?></td>
                <td><?php echo htmlspecialchars($trip['driver_name']); ?></td>
                <td>
                    <?php echo htmlspecialchars($trip['route_name']); ?><br>
                    <small><?php echo htmlspecialchars($trip['start_point'] . ' → ' . $trip['end_point']); ?></small>
                </td>
                <td><?php echo $trip['distance_km']; ?> км</td>
                <td><?php echo $trip['start_time'] ? date('d.m.Y H:i', strtotime($trip['start_time'])) : '-'; ?></td>
                <td><?php echo $trip['end_time'] ? date('d.m.Y H:i', strtotime($trip['end_time'])) : '-'; ?></td>
                <td><?php echo $trip['fuel_consumed'] ? $trip['fuel_consumed'] . ' л' : '-'; ?></td>
                <td>
                    <span class="status-<?php echo $trip['status']; ?>">
                        <?php
                        $statuses = [
                                'planned' => 'Запланований',
                                'active' => 'Активний',
                                'completed' => 'Завершений'
                        ];
                        echo isset($statuses[$trip['status']]) ? $statuses[$trip['status']] : $trip['status'];
                        ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?controller=trips&action=edit&id=<?php echo $trip['id']; ?>">Редагувати</a>
                    |
                    <form method="POST" action="index.php?controller=trips&action=delete" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $trip['id']; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <button type="submit" class="delete-btn" onclick="return confirm('Видалити рейс?')">
                            Видалити
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає рейсів для відображення</p>
<?php endif; ?>
</body>
</html>