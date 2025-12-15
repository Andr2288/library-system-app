<?php
require_once 'BaseApiController.php';
require_once '../models/ReaderModel.php';

class ReadersApiController extends BaseApiController {
    private $readerModel;

    public function __construct() {
        $this->readerModel = new ReaderModel();
    }

    public function index() {
        try {
            $readers = $this->readerModel->getReadersWithBooks();
            $this->success($readers);
        } catch (Exception $e) {
            $this->error('Failed to fetch readers: ' . $e->getMessage(), 500);
        }
    }

    public function show($id) {
        try {
            $reader = $this->readerModel->getById($id);
            if (!$reader) {
                $this->error('Reader not found', 404);
            }
            $this->success($reader);
        } catch (Exception $e) {
            $this->error('Failed to fetch reader: ' . $e->getMessage(), 500);
        }
    }

    public function create() {
        try {
            $input = $this->getJsonInput();
            
            $errors = $this->validateRequired($input, ['name', 'card_number', 'phone', 'email']);
            
            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $name = $this->validateInput($input['name']);
            $cardNumber = $this->validateInput($input['card_number']);
            $phone = $this->validateInput($input['phone']);
            $email = $this->validateInput($input['email']);
            
            // Validate name
            if (strlen($name) < 2) {
                $errors['name'] = 'Name must be at least 2 characters';
            } elseif (!preg_match('/^[а-яА-ЯіІїЇєЄ\s]+$/u', $name)) {
                $errors['name'] = 'Name can only contain Ukrainian letters and spaces';
            }
            
            // Validate card number
            if (!preg_match('/^RD\d{6}$/i', $cardNumber)) {
                $errors['card_number'] = 'Invalid card number format (RD123456)';
            } else {
                $existing = $this->readerModel->getByCardNumber(strtoupper($cardNumber));
                if ($existing) {
                    $errors['card_number'] = 'Reader with this card number already exists';
                }
            }
            
            // Validate phone
            if (!preg_match('/^\+380\d{9}$/', $phone)) {
                $errors['phone'] = 'Invalid phone format (+380XXXXXXXXX)';
            }
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
            
            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $data = [
                'name' => $name,
                'card_number' => strtoupper($cardNumber),
                'phone' => $phone,
                'email' => $email,
                'registration_date' => date('Y-m-d')
            ];

            if ($this->readerModel->create($data)) {
                $this->success(null, 'Reader created successfully');
            } else {
                $this->error('Failed to create reader', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function update($id) {
        try {
            $reader = $this->readerModel->getById($id);
            if (!$reader) {
                $this->error('Reader not found', 404);
            }

            $input = $this->getJsonInput();
            $errors = [];

            if (isset($input['name'])) {
                $name = $this->validateInput($input['name']);
                if (strlen($name) < 2) {
                    $errors['name'] = 'Name must be at least 2 characters';
                } elseif (!preg_match('/^[а-яА-ЯіІїЇєЄ\s]+$/u', $name)) {
                    $errors['name'] = 'Name can only contain Ukrainian letters and spaces';
                }
            }

            if (isset($input['card_number'])) {
                $cardNumber = $this->validateInput($input['card_number']);
                if (!preg_match('/^RD\d{6}$/i', $cardNumber)) {
                    $errors['card_number'] = 'Invalid card number format';
                } else {
                    $existing = $this->readerModel->getByCardNumber(strtoupper($cardNumber));
                    if ($existing && $existing['id'] != $id) {
                        $errors['card_number'] = 'Reader with this card number already exists';
                    }
                }
            }

            if (isset($input['phone'])) {
                if (!preg_match('/^\+380\d{9}$/', $input['phone'])) {
                    $errors['phone'] = 'Invalid phone format';
                }
            }

            if (isset($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format';
                }
            }

            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $updateData = [];
            $fields = ['name', 'card_number', 'phone', 'email'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $field === 'card_number' 
                        ? strtoupper($this->validateInput($input[$field]))
                        : ($field === 'name' ? $this->validateInput($input[$field]) : $input[$field]);
                }
            }

            if ($this->readerModel->update($id, $updateData)) {
                $this->success(null, 'Reader updated successfully');
            } else {
                $this->error('Failed to update reader', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id) {
        try {
            $reader = $this->readerModel->getById($id);
            if (!$reader) {
                $this->error('Reader not found', 404);
            }

            if ($this->readerModel->delete($id)) {
                $this->success(null, 'Reader deleted successfully');
            } else {
                $this->error('Failed to delete reader', 500);
            }

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $this->error('Cannot delete reader: they have active loans', 409);
            } else {
                $this->error('Database error: ' . $e->getMessage(), 500);
            }
        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
}
?>
