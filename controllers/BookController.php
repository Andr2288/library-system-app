<?php
require_once 'BaseController.php';
require_once 'models/BookModel.php';

class BookController extends BaseController {
    private $bookModel;

    public function __construct() {
        $this->bookModel = new BookModel();
    }

    public function index() {
        try {
            $books = $this->bookModel->getBooksWithCategories();

            $message = isset($_GET['message']) ? $_GET['message'] : null;
            $messageType = isset($_GET['type']) ? $_GET['type'] : 'error';

            $data = ['books' => $books];

            if ($message) {
                $data['message'] = $message;
                $data['messageType'] = $messageType;
            }

            $this->renderView('books/index.php', $data);
        } catch (Exception $e) {
            $this->renderView('books/index.php', [
                'books' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create() {
        $errors = [];

        if ($_POST) {
            $title = $this->validateInput($_POST['title']);
            $author = $this->validateInput($_POST['author']);
            $isbn = $this->validateInput($_POST['isbn']);
            $year = (int)$_POST['year'];
            $copiesTotal = (int)$_POST['copies_total'];
            $copiesAvailable = (int)$_POST['copies_available'];

            // Перевірка назви
            if (empty($title)) {
                $errors['title'] = 'Назва книги обов\'язкова';
            } elseif (strlen($title) < 2) {
                $errors['title'] = 'Назва має містити мінімум 2 символи';
            }

            // Перевірка автора
            if (empty($author)) {
                $errors['author'] = 'Автор обов\'язковий';
            } elseif (strlen($author) < 2) {
                $errors['author'] = 'Ім\'я автора має містити мінімум 2 символи';
            }

            // Перевірка ISBN
            if (empty($isbn)) {
                $errors['isbn'] = 'ISBN обов\'язковий';
            } elseif (!preg_match('/^978-\d{3}-\d{2}-\d{4}-\d$/i', $isbn)) {
                $errors['isbn'] = 'Невірний формат ISBN (978-XXX-XX-XXXX-X)';
            } else {
                try {
                    $existing = $this->bookModel->getByISBN($isbn);
                    if ($existing) {
                        $errors['isbn'] = 'Книга з таким ISBN вже існує';
                    }
                } catch (Exception $e) {
                    // Ігноруємо помилки перевірки
                }
            }

            // Перевірка року
            $currentYear = date('Y');
            if ($year < 1000 || $year > $currentYear) {
                $errors['year'] = "Рік має бути між 1000 та " . $currentYear;
            }

            // Перевірка кількості
            if ($copiesTotal <= 0 || $copiesTotal > 100) {
                $errors['copies_total'] = 'Кількість примірників має бути від 1 до 100';
            }

            if ($copiesAvailable < 0 || $copiesAvailable > $copiesTotal) {
                $errors['copies_available'] = 'Доступних примірників не може бути більше загальної кількості';
            }

            if (empty($errors)) {
                $data = [
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $isbn,
                    'year' => $year,
                    'copies_total' => $copiesTotal,
                    'copies_available' => $copiesAvailable,
                    'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                    'status' => isset($_POST['status']) ? $_POST['status'] : 'available'
                ];

                // Обробка завантаження обкладинки
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($_FILES['cover_image']['type'], $allowedTypes)) {
                        if ($_FILES['cover_image']['size'] <= 5 * 1024 * 1024) {
                            $fileName = time() . '_' . basename($_FILES['cover_image']['name']);
                            $uploadPath = $uploadDir . $fileName;

                            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
                                $data['cover_image'] = $uploadPath;
                            } else {
                                $errors['cover_image'] = 'Помилка завантаження обкладинки';
                            }
                        } else {
                            $errors['cover_image'] = 'Розмір обкладинки перевищує 5MB';
                        }
                    } else {
                        $errors['cover_image'] = 'Дозволені формати: JPG, PNG, GIF';
                    }
                }

                if (empty($errors)) {
                    try {
                        if ($this->bookModel->create($data)) {
                            $this->redirect('index.php?controller=books');
                        } else {
                            $errors['general'] = 'Помилка створення запису';
                        }
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $errors['isbn'] = 'Книга з таким ISBN вже існує';
                        } else {
                            $errors['general'] = 'Помилка бази даних: ' . $e->getMessage();
                        }
                    } catch (Exception $e) {
                        $errors['general'] = 'Помилка: ' . $e->getMessage();
                    }
                }
            }
        }

        // Отримати список категорій для форми
        require_once 'models/CategoryModel.php';
        $categoryModel = new CategoryModel();
        try {
            $categories = $categoryModel->getAll();
        } catch (Exception $e) {
            $categories = [];
        }

        $this->renderView('books/form.php', [
            'categories' => $categories,
            'errors' => $errors,
            'formData' => $_POST
        ]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];

        if (!$id) {
            $this->redirect('index.php?controller=books');
        }

        $book = $this->bookModel->getById($id);
        if (!$book) {
            $this->redirect('index.php?controller=books');
        }

        if ($_POST) {
            $postedId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
            if ($postedId !== $id) {
                $this->redirect('index.php?controller=books');
            }

            $title = $this->validateInput($_POST['title']);
            $author = $this->validateInput($_POST['author']);
            $isbn = $this->validateInput($_POST['isbn']);
            $year = (int)$_POST['year'];
            $copiesTotal = (int)$_POST['copies_total'];
            $copiesAvailable = (int)$_POST['copies_available'];

            if (empty($title)) {
                $errors['title'] = 'Назва книги обов\'язкова';
            }
            if (empty($author)) {
                $errors['author'] = 'Автор обов\'язковий';
            }

            if (empty($isbn)) {
                $errors['isbn'] = 'ISBN обов\'язковий';
            } elseif (!preg_match('/^978-\d{3}-\d{2}-\d{4}-\d$/i', $isbn)) {
                $errors['isbn'] = 'Невірний формат ISBN';
            } else {
                $existing = $this->bookModel->getByISBN($isbn);
                if ($existing && $existing['id'] != $id) {
                    $errors['isbn'] = 'Книга з таким ISBN вже існує';
                }
            }

            $currentYear = date('Y');
            if ($year < 1000 || $year > $currentYear) {
                $errors['year'] = "Рік має бути між 1000 та " . $currentYear;
            }

            if ($copiesTotal <= 0 || $copiesTotal > 100) {
                $errors['copies_total'] = 'Кількість примірників має бути від 1 до 100';
            }

            if ($copiesAvailable < 0 || $copiesAvailable > $copiesTotal) {
                $errors['copies_available'] = 'Доступних примірників не може бути більше загальної кількості';
            }

            if (empty($errors)) {
                $data = [
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $isbn,
                    'year' => $year,
                    'copies_total' => $copiesTotal,
                    'copies_available' => $copiesAvailable,
                    'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                    'status' => isset($_POST['status']) ? $_POST['status'] : 'available'
                ];

                // Обробка нової обкладинки
                if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($_FILES['cover_image']['type'], $allowedTypes)) {
                        if ($_FILES['cover_image']['size'] <= 5 * 1024 * 1024) {
                            if ($book['cover_image'] && file_exists($book['cover_image'])) {
                                unlink($book['cover_image']);
                            }

                            $fileName = time() . '_' . basename($_FILES['cover_image']['name']);
                            $uploadPath = $uploadDir . $fileName;

                            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
                                $data['cover_image'] = $uploadPath;
                            }
                        }
                    }
                }

                try {
                    if ($this->bookModel->update($id, $data)) {
                        $this->redirect('index.php?controller=books');
                    } else {
                        $errors['general'] = 'Помилка оновлення запису';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        // Отримати список категорій
        require_once 'models/CategoryModel.php';
        $categoryModel = new CategoryModel();
        $categories = $categoryModel->getAll();

        $this->renderView('books/form.php', [
            'book' => $book,
            'categories' => $categories,
            'errors' => $errors
        ]);
    }

    public function delete() {
        if ($_POST && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id) {
                try {
                    $book = $this->bookModel->getById($id);

                    if ($this->bookModel->delete($id)) {
                        if ($book && $book['cover_image'] && file_exists($book['cover_image'])) {
                            unlink($book['cover_image']);
                        }

                        $this->redirect('index.php?controller=books&message=' . urlencode('Книгу успішно видалено') . '&type=success');
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'foreign key constraint') !== false ||
                        strpos($e->getMessage(), 'Cannot delete') !== false) {
                        $this->redirect('index.php?controller=books&message=' . urlencode('Неможливо видалити книгу: вона використовується у видачах. Спочатку видаліть всі видачі з цією книгою.') . '&type=error');
                    } else {
                        $this->redirect('index.php?controller=books&message=' . urlencode('Помилка видалення: ' . $e->getMessage()) . '&type=error');
                    }
                } catch (Exception $e) {
                    $this->redirect('index.php?controller=books&message=' . urlencode('Помилка: ' . $e->getMessage()) . '&type=error');
                }
            }
        }

        $this->redirect('index.php?controller=books');
    }
}
?>
