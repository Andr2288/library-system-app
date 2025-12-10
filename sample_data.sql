USE transport_db;

-- Тестові дані для водіїв
INSERT INTO drivers (name, license_number, phone, experience_years, category) VALUES
('Іван Петренко', 'AA123456', '+380501234567', 5, 'C'),
('Марія Коваленко', 'BB789012', '+380502345678', 3, 'B'),
('Олександр Шевченко', 'CC345678', '+380503456789', 7, 'C+E');

-- Тестові дані для автомобілів
INSERT INTO vehicles (license_plate, brand, model, year, capacity, driver_id, status) VALUES
('AA1234BB', 'Mercedes', 'Sprinter', 2020, 3.5, 1, 'active'),
('BC5678DE', 'Ford', 'Transit', 2019, 2.0, 2, 'active'),
('KA9012MH', 'Volvo', 'FH16', 2021, 40.0, 3, 'active');

-- Тестові дані для маршрутів
INSERT INTO routes (name, start_point, end_point, distance_km, duration_hours) VALUES
('Київ-Львів', 'м. Київ', 'м. Львів', 540.5, 6.5),
('Харків-Одеса', 'м. Харків', 'м. Одеса', 580.2, 7.0),
('Дніпро-Запоріжжя', 'м. Дніпро', 'м. Запоріжжя', 85.3, 1.5);

-- Тестові дані для рейсів
INSERT INTO trips (vehicle_id, driver_id, route_id, start_time, fuel_consumed, status) VALUES
(1, 1, 1, '2024-11-26 08:00:00', 45.5, 'active'),
(2, 2, 3, '2024-11-26 10:30:00', 12.8, 'completed'),
(3, 3, 2, '2024-11-26 14:00:00', 78.2, 'planned');
