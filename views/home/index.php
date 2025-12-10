<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автотранспортне підприємство</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { background: #333; color: white; padding: 20px; text-align: center; }
        .nav { background: #f4f4f4; padding: 10px; margin: 20px 0; }
        .nav a { margin: 0 15px; text-decoration: none; color: #333; }
        .nav a:hover { color: #007bff; }
        .counter { background: #e9ecef; padding: 10px; margin: 20px 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class="header">
    <h1>Автотранспортне підприємство</h1>
    <p>Система управління транспортом</p>
</div>

<div class="nav">
    <a href="index.php"><strong>Головна</strong></a>
    <a href="index.php?controller=vehicles">Автомобілі</a>
    <a href="index.php?controller=drivers">Водії</a>
    <a href="index.php?controller=trips">Рейси</a>
    <a href="search.php">Пошук</a>
</div>

<div class="counter">
    <strong>Кількість відвідувань: <?php echo isset($visits) ? $visits : 0; ?></strong>
</div>

<h2>Звіт транспортного підприємства</h2>

<?php if (isset($error)): ?>
    <p style="color: red;">Помилка: <?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if (!empty($reports)): ?>
    <table>
        <tr>
            <th>Номер</th>
            <th>Марка</th>
            <th>Модель</th>
            <th>Водій</th>
            <th>Маршрут</th>
            <th>Час початку</th>
            <th>Статус</th>
        </tr>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><?php echo htmlspecialchars($report['license_plate']); ?></td>
                <td><?php echo htmlspecialchars($report['brand']); ?></td>
                <td><?php echo htmlspecialchars($report['model']); ?></td>
                <td><?php echo htmlspecialchars($report['driver_name']); ?></td>
                <td><?php echo htmlspecialchars($report['route_name']); ?></td>
                <td><?php echo htmlspecialchars($report['start_time']); ?></td>
                <td><?php echo htmlspecialchars($report['trip_status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Немає даних для відображення</p>
<?php endif; ?>
</body>
</html>