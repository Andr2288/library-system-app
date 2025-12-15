<?php
require_once 'BaseController.php';
require_once 'models/LoanModel.php';
require_once 'models/BookModel.php';
require_once 'models/ReaderModel.php';
require_once 'models/CategoryModel.php';

class LoanController extends BaseController {
    private $loanModel;
    private $bookModel;
    private $readerModel;
    private $categoryModel;

    public function __construct() {
        $this->loanModel = new LoanModel();
        $this->bookModel = new BookModel();
        $this->readerModel = new ReaderModel();
        $this->categoryModel = new CategoryModel();
    }

    public function index() {
        try {
            $loans = $this->loanModel->getLoansWithDetails();

            $message = isset($_GET['message']) ? $_GET['message'] : null;
            $messageType = isset($_GET['type']) ? $_GET['type'] : 'error';

            $data = ['loans' => $loans];

            if ($message) {
                $data['message'] = $message;
                $data['messageType'] = $messageType;
            }

            $this->renderView('loans/index.php', $data);
        } catch (Exception $e) {
            $this->renderView('loans/index.php', [
                'loans' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create() {
        $errors = [];

        if ($_POST) {
            $bookId = (int)$_POST['book_id'];
            $readerId = (int)$_POST['reader_id'];
            $categoryId = (int)$_POST['category_id'];
            $loanDate = $this->validateInput($_POST['loan_date']);
            $returnDate = !empty($_POST['return_date']) ? $this->validateInput($_POST['return_date']) : null;
            $actualReturnDate = !empty($_POST['actual_return_date']) ? $this->validateInput($_POST['actual_return_date']) : null;
            $fineAmount = !empty($_POST['fine_amount']) ? (float)$_POST['fine_amount'] : null;
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';

            // Перевірка обов'язкових полів
            if (!$bookId) {
                $errors['book_id'] = 'Оберіть книгу';
            } else {
                $book = $this->bookModel->getById($bookId);
                if (!$book || $book['copies_available'] <= 0) {
                    $errors['book_id'] = 'Обрана книга недоступна для видачі';
                }
            }

            if (!$readerId) {
                $errors['reader_id'] = 'Оберіть читача';
            }

            if (!$categoryId) {
                $errors['category_id'] = 'Оберіть категорію';
            }

            // Перевірка дати видачі - тільки чи не порожня
            if (empty($loanDate)) {
                $errors['loan_date'] = 'Вкажіть дату видачі';
            }

            // Перевірка дати повернення
            if ($returnDate && $loanDate) {
                try {
                    $loanDateTime = new DateTime($loanDate);
                    $returnDateTime = new DateTime($returnDate);
                    if ($returnDateTime <= $loanDateTime) {
                        $errors['return_date'] = 'Дата повернення має бути пізніше дати видачі';
                    }
                } catch (Exception $e) {
                    $errors['return_date'] = 'Невірний формат дати';
                }
            }

            // Перевірка штрафу
            if ($fineAmount !== null && ($fineAmount < 0 || $fineAmount > 10000)) {
                $errors['fine_amount'] = 'Сума штрафу має бути від 0 до 10000 грн';
            }

            // Перевірка статусу
            $validStatuses = ['active', 'returned', 'overdue'];
            if (!in_array($status, $validStatuses)) {
                $errors['status'] = 'Невірний статус видачі';
            }

            if (empty($errors)) {
                $data = [
                    'book_id' => $bookId,
                    'reader_id' => $readerId,
                    'category_id' => $categoryId,
                    'loan_date' => $loanDate,
                    'return_date' => $returnDate,
                    'actual_return_date' => $actualReturnDate,
                    'fine_amount' => $fineAmount,
                    'status' => $status
                ];

                try {
                    if ($this->loanModel->create($data)) {
                        // Зменшити кількість доступних примірників
                        $book = $this->bookModel->getById($bookId);
                        if ($book && $status === 'active') {
                            $this->bookModel->update($bookId, [
                                'copies_available' => max(0, $book['copies_available'] - 1)
                            ]);
                        }

                        $this->redirect('index.php?controller=loans');
                    } else {
                        $errors['general'] = 'Помилка створення видачі';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        // Отримуємо дані для форми
        try {
            $books = $this->bookModel->getAll();
            $readers = $this->readerModel->getAll();
            $categories = $this->categoryModel->getAll();
        } catch (Exception $e) {
            $books = [];
            $readers = [];
            $categories = [];
            $errors['general'] = 'Помилка завантаження даних: ' . $e->getMessage();
        }

        $this->renderView('loans/form.php', [
            'books' => $books,
            'readers' => $readers,
            'categories' => $categories,
            'errors' => $errors,
            'formData' => $_POST
        ]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];

        if (!$id) {
            $this->redirect('index.php?controller=loans');
        }

        $loan = $this->loanModel->getById($id);
        if (!$loan) {
            $this->redirect('index.php?controller=loans');
        }

        if ($_POST) {
            $postedId = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;
            if ($postedId !== $id) {
                $this->redirect('index.php?controller=loans');
            }

            $bookId = (int)$_POST['book_id'];
            $readerId = (int)$_POST['reader_id'];
            $categoryId = (int)$_POST['category_id'];
            $loanDate = $this->validateInput($_POST['loan_date']);
            $returnDate = !empty($_POST['return_date']) ? $this->validateInput($_POST['return_date']) : null;
            $actualReturnDate = !empty($_POST['actual_return_date']) ? $this->validateInput($_POST['actual_return_date']) : null;
            $fineAmount = !empty($_POST['fine_amount']) ? (float)$_POST['fine_amount'] : null;
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';

            if (!$bookId) {
                $errors['book_id'] = 'Оберіть книгу';
            }
            if (!$readerId) {
                $errors['reader_id'] = 'Оберіть читача';
            }
            if (!$categoryId) {
                $errors['category_id'] = 'Оберіть категорію';
            }
            if (empty($loanDate)) {
                $errors['loan_date'] = 'Вкажіть дату видачі';
            }

            if ($returnDate && $loanDate) {
                try {
                    $loanDateTime = new DateTime($loanDate);
                    $returnDateTime = new DateTime($returnDate);
                    if ($returnDateTime <= $loanDateTime) {
                        $errors['return_date'] = 'Дата повернення має бути пізніше дати видачі';
                    }
                } catch (Exception $e) {
                    $errors['return_date'] = 'Невірний формат дати';
                }
            }

            if ($fineAmount !== null && ($fineAmount < 0 || $fineAmount > 10000)) {
                $errors['fine_amount'] = 'Сума штрафу має бути від 0 до 10000 грн';
            }

            $validStatuses = ['active', 'returned', 'overdue'];
            if (!in_array($status, $validStatuses)) {
                $errors['status'] = 'Невірний статус видачі';
            }

            if (empty($errors)) {
                $data = [
                    'book_id' => $bookId,
                    'reader_id' => $readerId,
                    'category_id' => $categoryId,
                    'loan_date' => $loanDate,
                    'return_date' => $returnDate,
                    'actual_return_date' => $actualReturnDate,
                    'fine_amount' => $fineAmount,
                    'status' => $status
                ];

                try {
                    // Якщо статус змінився з active на returned, повернути примірник
                    if ($loan['status'] === 'active' && $status === 'returned') {
                        $book = $this->bookModel->getById($bookId);
                        if ($book) {
                            $this->bookModel->update($bookId, [
                                'copies_available' => min($book['copies_total'], $book['copies_available'] + 1)
                            ]);
                        }
                    }

                    if ($this->loanModel->update($id, $data)) {
                        $this->redirect('index.php?controller=loans');
                    } else {
                        $errors['general'] = 'Помилка оновлення видачі';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        // Отримуємо дані для форми
        try {
            $books = $this->bookModel->getAll();
            $readers = $this->readerModel->getAll();
            $categories = $this->categoryModel->getAll();
        } catch (Exception $e) {
            $books = [];
            $readers = [];
            $categories = [];
        }

        $this->renderView('loans/form.php', [
            'loan' => $loan,
            'books' => $books,
            'readers' => $readers,
            'categories' => $categories,
            'errors' => $errors
        ]);
    }

    public function delete() {
        if ($_POST && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id) {
                try {
                    $loan = $this->loanModel->getById($id);

                    // Якщо видача активна, повернути примірник
                    if ($loan && $loan['status'] === 'active') {
                        $book = $this->bookModel->getById($loan['book_id']);
                        if ($book) {
                            $this->bookModel->update($loan['book_id'], [
                                'copies_available' => min($book['copies_total'], $book['copies_available'] + 1)
                            ]);
                        }
                    }

                    $this->loanModel->delete($id);
                } catch (Exception $e) {
                    // Помилки ігноруємо для простоти
                }
            }
        }

        $this->redirect('index.php?controller=loans');
    }
}
?>