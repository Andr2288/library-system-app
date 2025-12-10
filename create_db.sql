-- Створення бази даних
CREATE DATABASE IF NOT EXISTS transport_db;
USE transport_db;

-- Створення користувача з мінімальними правами
CREATE USER IF NOT EXISTS 'transport_user'@'localhost' IDENTIFIED BY 'password123';
GRANT SELECT, INSERT, UPDATE, DELETE ON transport_db.* TO 'transport_user'@'localhost';
FLUSH PRIVILEGES;

-- Таблиця водіїв
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    license_number VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(15),
    experience_years INT DEFAULT 0,
    category VARCHAR(10) NOT NULL
);

-- Таблиця автомобілів  
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_plate VARCHAR(15) UNIQUE NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT,
    capacity DECIMAL(5,2),
    driver_id INT,
    status ENUM('active', 'repair', 'inactive') DEFAULT 'active',
    photo VARCHAR(255),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
);

-- Таблиця маршрутів
CREATE TABLE routes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    start_point VARCHAR(100) NOT NULL,
    end_point VARCHAR(100) NOT NULL,
    distance_km DECIMAL(6,2),
    duration_hours DECIMAL(4,2)
);

-- Таблиця рейсів
CREATE TABLE trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    route_id INT NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    fuel_consumed DECIMAL(6,2),
    status ENUM('planned', 'active', 'completed') DEFAULT 'planned',
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (route_id) REFERENCES routes(id)
);

-- Подання з роботи 3
CREATE VIEW transport_report AS 
SELECT 
    v.license_plate,
    v.brand,
    v.model,
    d.name as driver_name,
    r.name as route_name,
    t.start_time,
    t.status as trip_status
FROM trips t
JOIN vehicles v ON t.vehicle_id = v.id
JOIN drivers d ON t.driver_id = d.id  
JOIN routes r ON t.route_id = r.id
ORDER BY t.start_time DESC;
