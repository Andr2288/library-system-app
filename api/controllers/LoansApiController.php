<?php
require_once 'BaseApiController.php';
require_once __DIR__ . '/../../models/LoanModel.php';
require_once __DIR__ . '/../../models/BookModel.php';
require_once __DIR__ . '/../../models/ReaderModel.php';
require_once __DIR__ . '/../../models/CategoryModel.php';

class LoansApiController extends BaseApiController {
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
            $this->success($loans);
        } catch (Exception $e) {
            $this->error('Failed to fetch loans: ' . $e->getMessage(), 500);
        }
    }

    public function show($id) {
        try {
            $loan = $this->loanModel->getById($id);
            if (!$loan) {
                $this->error('Loan not found', 404);
            }
            $this->success($loan);
        } catch (Exception $e) {
            $this->error('Failed to fetch loan: ' . $e->getMessage(), 500);
        }
    }

    public function active() {
        try {
            $loans = $this->loanModel->getActiveLoans();
            $this->success($loans);
        } catch (Exception $e) {
            $this->error('Failed to fetch active loans: ' . $e->getMessage(), 500);
        }
    }

    public function overdue() {
        try {
            $loans = $this->loanModel->getOverdueLoans();
            $this->success($loans);
        } catch (Exception $e) {
            $this->error('Failed to fetch overdue loans: ' . $e->getMessage(), 500);
        }
    }

    public function create() {
        try {
            $input = $this->getJsonInput();
            
            $errors = $this->validateRequired($input, ['book_id', 'reader_id', 'category_id']);
            
            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $bookId = (int)$input['book_id'];
            $readerId = (int)$input['reader_id'];
            $categoryId = (int)$input['category_id'];
            
            // Validate book availability
            $book = $this->bookModel->getById($bookId);
            if (!$book) {
                $this->error('Book not found', 404);
            }
            if ($book['copies_available'] <= 0) {
                $this->error('Book is not available for loan', 409);
            }
            
            // Validate reader exists
            $reader = $this->readerModel->getById($readerId);
            if (!$reader) {
                $this->error('Reader not found', 404);
            }
            
            // Validate category exists
            $category = $this->categoryModel->getById($categoryId);
            if (!$category) {
                $this->error('Category not found', 404);
            }

            $data = [
                'book_id' => $bookId,
                'reader_id' => $readerId,
                'category_id' => $categoryId,
                'loan_date' => isset($input['loan_date']) ? $input['loan_date'] : date('Y-m-d H:i:s'),
                'return_date' => isset($input['return_date']) ? $input['return_date'] : date('Y-m-d H:i:s', strtotime('+30 days')),
                'status' => isset($input['status']) ? $input['status'] : 'active'
            ];

            if (isset($input['actual_return_date'])) {
                $data['actual_return_date'] = $input['actual_return_date'];
            }
            if (isset($input['fine_amount'])) {
                $data['fine_amount'] = (float)$input['fine_amount'];
            }

            if ($this->loanModel->create($data)) {
                // Decrease available copies
                if ($data['status'] === 'active') {
                    $this->bookModel->update($bookId, [
                        'copies_available' => max(0, $book['copies_available'] - 1)
                    ]);
                }
                $this->success(null, 'Loan created successfully');
            } else {
                $this->error('Failed to create loan', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function update($id) {
        try {
            $loan = $this->loanModel->getById($id);
            if (!$loan) {
                $this->error('Loan not found', 404);
            }

            $input = $this->getJsonInput();
            $errors = [];

            if (isset($input['return_date']) && isset($input['loan_date'])) {
                $loanDate = new DateTime($input['loan_date']);
                $returnDate = new DateTime($input['return_date']);
                if ($returnDate <= $loanDate) {
                    $errors['return_date'] = 'Return date must be after loan date';
                }
            }

            if (isset($input['fine_amount'])) {
                $fineAmount = (float)$input['fine_amount'];
                if ($fineAmount < 0 || $fineAmount > 10000) {
                    $errors['fine_amount'] = 'Fine amount must be between 0 and 10000';
                }
            }

            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $updateData = [];
            $fields = ['book_id', 'reader_id', 'category_id', 'loan_date', 'return_date', 'actual_return_date', 'fine_amount', 'status'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }

            // Handle status change from active to returned
            if ($loan['status'] === 'active' && isset($input['status']) && $input['status'] === 'returned') {
                $book = $this->bookModel->getById($loan['book_id']);
                if ($book) {
                    $this->bookModel->update($loan['book_id'], [
                        'copies_available' => min($book['copies_total'], $book['copies_available'] + 1)
                    ]);
                }
            }

            if ($this->loanModel->update($id, $updateData)) {
                $this->success(null, 'Loan updated successfully');
            } else {
                $this->error('Failed to update loan', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id) {
        try {
            $loan = $this->loanModel->getById($id);
            if (!$loan) {
                $this->error('Loan not found', 404);
            }

            // Return book copy if loan was active
            if ($loan['status'] === 'active') {
                $book = $this->bookModel->getById($loan['book_id']);
                if ($book) {
                    $this->bookModel->update($loan['book_id'], [
                        'copies_available' => min($book['copies_total'], $book['copies_available'] + 1)
                    ]);
                }
            }

            if ($this->loanModel->delete($id)) {
                $this->success(null, 'Loan deleted successfully');
            } else {
                $this->error('Failed to delete loan', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
}
?>
