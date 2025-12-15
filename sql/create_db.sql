-- Створення бази даних
CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Створення користувача з мінімальними правами
CREATE USER IF NOT EXISTS 'library_user'@'localhost' IDENTIFIED BY 'password123';
GRANT SELECT, INSERT, UPDATE, DELETE ON library_db.* TO 'library_user'@'localhost';
FLUSH PRIVILEGES;

-- Таблиця читачів
CREATE TABLE readers (
                         id INT PRIMARY KEY AUTO_INCREMENT,
                         name VARCHAR(100) NOT NULL,
                         card_number VARCHAR(20) UNIQUE NOT NULL,
                         phone VARCHAR(15),
                         email VARCHAR(100),
                         registration_date DATE DEFAULT (CURRENT_DATE)
);

-- Таблиця категорій книг
CREATE TABLE categories (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            name VARCHAR(100) NOT NULL,
                            description TEXT,
                            floor_location VARCHAR(50)
);

-- Таблиця книг
CREATE TABLE books (
                       id INT PRIMARY KEY AUTO_INCREMENT,
                       title VARCHAR(200) NOT NULL,
                       author VARCHAR(100) NOT NULL,
                       isbn VARCHAR(20) UNIQUE,
                       year INT,
                       copies_total INT DEFAULT 1,
                       copies_available INT DEFAULT 1,
                       category_id INT,
                       status ENUM('available', 'damaged', 'lost') DEFAULT 'available',
                       cover_image VARCHAR(255),
                       FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Таблиця видачі книг
CREATE TABLE loans (
                       id INT PRIMARY KEY AUTO_INCREMENT,
                       book_id INT NOT NULL,
                       reader_id INT NOT NULL,
                       category_id INT NOT NULL,
                       loan_date DATETIME,
                       return_date DATETIME,
                       actual_return_date DATETIME,
                       fine_amount DECIMAL(6,2),
                       status ENUM('active', 'returned', 'overdue') DEFAULT 'active',
                       FOREIGN KEY (book_id) REFERENCES books(id),
                       FOREIGN KEY (reader_id) REFERENCES readers(id),
                       FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Подання для звітів
CREATE VIEW library_report AS
SELECT
    b.title,
    b.author,
    b.isbn,
    r.name as reader_name,
    c.name as category_name,
    l.loan_date,
    l.status as loan_status
FROM loans l
         JOIN books b ON l.book_id = b.id
         JOIN readers r ON l.reader_id = r.id
         JOIN categories c ON l.category_id = c.id
ORDER BY l.loan_date DESC;