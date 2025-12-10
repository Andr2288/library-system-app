<?php
require_once 'BaseController.php';
require_once 'models/ReaderModel.php';

class ReaderController extends BaseController {
    private $readerModel;

    public function __construct() {
        $this->readerModel = new ReaderModel();
    }

    public function index() {
        try {
            $readers = $this->readerModel->getReadersWithBooks();

            $message = isset($_GET['message']) ? $_GET['message'] : null;
            $messageType = isset($_GET['type']) ? $_GET['type'] : 'error';

            $data = ['readers' => $readers];

            if ($message) {
                $data['message'] = $message;
                $data['messageType'] = $messageType;
            }

            $this->renderView('readers/index.php', $data);
        } catch (Exception $e) {
            $this->renderView('readers/index.php', [
                'readers' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create() {
        $errors = [];

        if ($_POST) {
            $name = $this->validateInput($_POST['name']);
            $cardNumber = $this->validateInput($_POST['card_number']);
            $phone = $this->validateInput($_POST['phone']);
            $email = $this->validateInput($_POST['email']);

            // Перевірка імені
            if (empty($name)) {
                $errors['name'] = 'Ім\'я обов\'язкове';
            } elseif (strlen($name) < 2) {
                $errors['name'] = 'Ім\'я має містити мінімум 2 символи';
            } elseif (!preg_match('/^[а-яА-ЯіІїЇєЄ\s]+$/u', $name)) {
                $errors['name'] = 'Ім\'я може містити лише українські літери та пробіли';
            }

            // Перевірка номера квитка
            if (empty($cardNumber)) {
                $errors['card_number'] = 'Номер читацького квитка обов\'язковий';
            } elseif (!preg_match('/^RD\d{6}$/i', $cardNumber)) {
                $errors['card_number'] = 'Невірний формат номера квитка (RD123456)';
            } else {
                try {
                    $existing = $this->readerModel->getByCardNumber(strtoupper($cardNumber));
                    if ($existing) {
                        $errors['card_number'] = 'Читач з таким номером квитка вже існує';
                    }
                } catch (Exception $e) {
                    // Ігноруємо помилки перевірки
                }
            }

            // Перевірка телефону
            if (empty($phone)) {
                $errors['phone'] = 'Номер телефону обов\'язковий';
            } elseif (!preg_match('/^\+380\d{9}$/', $phone)) {
                $errors['phone'] = 'Невірний формат телефону (+380XXXXXXXXX)';
            }

            // Перевірка email
            if (empty($email)) {
                $errors['email'] = 'Email обов\'язковий';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Невірний формат email';
            }

            if (empty($errors)) {
                $data = [
                    'name' => $name,
                    'card_number' => strtoupper($cardNumber),
                    'phone' => $phone,
                    'email' => $email,
                    'registration_date' => date('Y-m-d')
                ];

                try {
                    if ($this->readerModel->create($data)) {
                        $this->redirect('index.php?controller=readers');
                    } else {
                        $errors['general'] = 'Помилка створення запису';
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $errors['card_number'] = 'Читач з таким номером квитка вже існує';
                    } else {
                        $errors['general'] = 'Помилка бази даних: ' . $e->getMessage();
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        $this->renderView('readers/form.php', [
            'errors' => $errors,
            'formData' => $_POST
        ]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];

        if (!$id) {
            $this->redirect('index.php?controller=readers');
        }

        $reader = $this->readerModel->getById($id);
        if (!$reader) {
            $this->redirect('index.php?controller=readers');
        }

        if ($_POST) {
            $postedId = isset($_POST['reader_id']) ? (int)$_POST['reader_id'] : 0;
            if ($postedId !== $id) {
                $this->redirect('index.php?controller=readers');
            }

            $name = $this->validateInput($_POST['name']);
            $cardNumber = $this->validateInput($_POST['card_number']);
            $phone = $this->validateInput($_POST['phone']);
            $email = $this->validateInput($_POST['email']);

            if (empty($name)) {
                $errors['name'] = 'Ім\'я обов\'язкове';
            } elseif (strlen($name) < 2) {
                $errors['name'] = 'Ім\'я має містити мінімум 2 символи';
            } elseif (!preg_match('/^[а-яА-ЯіІїЇєЄ\s]+$/u', $name)) {
                $errors['name'] = 'Ім\'я може містити лише українські літери та пробіли';
            }

            if (empty($cardNumber)) {
                $errors['card_number'] = 'Номер читацького квитка обов\'язковий';
            } elseif (!preg_match('/^RD\d{6}$/i', $cardNumber)) {
                $errors['card_number'] = 'Невірний формат номера квитка (RD123456)';
            } else {
                $existing = $this->readerModel->getByCardNumber(strtoupper($cardNumber));
                if ($existing && $existing['id'] != $id) {
                    $errors['card_number'] = 'Читач з таким номером квитка вже існує';
                }
            }

            if (empty($phone)) {
                $errors['phone'] = 'Номер телефону обов\'язковий';
            } elseif (!preg_match('/^\+380\d{9}$/', $phone)) {
                $errors['phone'] = 'Невірний формат телефону (+380XXXXXXXXX)';
            }

            if (empty($email)) {
                $errors['email'] = 'Email обов\'язковий';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Невірний формат email';
            }

            if (empty($errors)) {
                $data = [
                    'name' => $name,
                    'card_number' => strtoupper($cardNumber),
                    'phone' => $phone,
                    'email' => $email
                ];

                try {
                    if ($this->readerModel->update($id, $data)) {
                        $this->redirect('index.php?controller=readers');
                    } else {
                        $errors['general'] = 'Помилка оновлення запису';
                    }
                } catch (Exception $e) {
                    $errors['general'] = 'Помилка: ' . $e->getMessage();
                }
            }
        }

        $this->renderView('readers/form.php', [
            'reader' => $reader,
            'errors' => $errors
        ]);
    }

    public function delete() {
        if ($_POST && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id) {
                try {
                    if ($this->readerModel->delete($id)) {
                        $this->redirect('index.php?controller=readers&message=' . urlencode('Читача успішно видалено') . '&type=success');
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'foreign key constraint') !== false ||
                        strpos($e->getMessage(), 'Cannot delete') !== false) {
                        $this->redirect('index.php?controller=readers&message=' . urlencode('Неможливо видалити читача: за ним закріплені книги. Спочатку поверніть всі книги.') . '&type=error');
                    } else {
                        $this->redirect('index.php?controller=readers&message=' . urlencode('Помилка видалення: ' . $e->getMessage()) . '&type=error');
                    }
                } catch (Exception $e) {
                    $this->redirect('index.php?controller=readers&message=' . urlencode('Помилка: ' . $e->getMessage()) . '&type=error');
                }
            }
        }

        $this->redirect('index.php?controller=readers');
    }
}
?>
