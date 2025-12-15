<?php
require_once 'BaseApiController.php';
require_once __DIR__ . '/../../models/BookModel.php';
require_once __DIR__ . '/../../models/CategoryModel.php';

class BooksApiController extends BaseApiController {
    private $bookModel;
    private $categoryModel;

    public function __construct() {
        $this->bookModel = new BookModel();
        $this->categoryModel = new CategoryModel();
    }

    public function index() {
        try {
            $books = $this->bookModel->getBooksWithCategories();
            $this->success($books);
        } catch (Exception $e) {
            $this->error('Failed to fetch books: ' . $e->getMessage(), 500);
        }
    }

    public function show($id) {
        try {
            $book = $this->bookModel->getById($id);
            if (!$book) {
                $this->error('Book not found', 404);
            }
            $this->success($book);
        } catch (Exception $e) {
            $this->error('Failed to fetch book: ' . $e->getMessage(), 500);
        }
    }

    public function create() {
        try {
            $input = $this->getJsonInput();
            
            // Validate required fields
            $errors = $this->validateRequired($input, ['title', 'author', 'isbn', 'year']);
            
            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            // Additional validations
            $title = $this->validateInput($input['title']);
            $author = $this->validateInput($input['author']);
            $isbn = $this->validateInput($input['isbn']);
            $year = (int)$input['year'];
            
            if (strlen($title) < 2) {
                $errors['title'] = 'Title must be at least 2 characters';
            }
            
            if (!preg_match('/^978-\d{3}-\d{2}-\d{4}-\d$/i', $isbn)) {
                $errors['isbn'] = 'Invalid ISBN format (978-XXX-XX-XXXX-X)';
            }
            
            $currentYear = date('Y');
            if ($year < 1000 || $year > $currentYear) {
                $errors['year'] = "Year must be between 1000 and $currentYear";
            }
            
            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            // Check for duplicate ISBN
            $existing = $this->bookModel->getByISBN($isbn);
            if ($existing) {
                $this->error('Book with this ISBN already exists', 409);
            }

            $data = [
                'title' => $title,
                'author' => $author,
                'isbn' => $isbn,
                'year' => $year,
                'copies_total' => isset($input['copies_total']) ? (int)$input['copies_total'] : 1,
                'copies_available' => isset($input['copies_available']) ? (int)$input['copies_available'] : 1,
                'category_id' => isset($input['category_id']) ? (int)$input['category_id'] : null,
                'status' => isset($input['status']) ? $input['status'] : 'available'
            ];

            if ($this->bookModel->create($data)) {
                $this->success(null, 'Book created successfully');
            } else {
                $this->error('Failed to create book', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function update($id) {
        try {
            $book = $this->bookModel->getById($id);
            if (!$book) {
                $this->error('Book not found', 404);
            }

            $input = $this->getJsonInput();
            $errors = [];

            if (isset($input['title'])) {
                $title = $this->validateInput($input['title']);
                if (strlen($title) < 2) {
                    $errors['title'] = 'Title must be at least 2 characters';
                }
            }

            if (isset($input['isbn'])) {
                $isbn = $this->validateInput($input['isbn']);
                if (!preg_match('/^978-\d{3}-\d{2}-\d{4}-\d$/i', $isbn)) {
                    $errors['isbn'] = 'Invalid ISBN format';
                } else {
                    $existing = $this->bookModel->getByISBN($isbn);
                    if ($existing && $existing['id'] != $id) {
                        $errors['isbn'] = 'Book with this ISBN already exists';
                    }
                }
            }

            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $updateData = [];
            $fields = ['title', 'author', 'isbn', 'year', 'copies_total', 'copies_available', 'category_id', 'status'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $field === 'title' || $field === 'author' || $field === 'isbn' 
                        ? $this->validateInput($input[$field]) 
                        : $input[$field];
                }
            }

            if ($this->bookModel->update($id, $updateData)) {
                $this->success(null, 'Book updated successfully');
            } else {
                $this->error('Failed to update book', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id) {
        try {
            $book = $this->bookModel->getById($id);
            if (!$book) {
                $this->error('Book not found', 404);
            }

            if ($this->bookModel->delete($id)) {
                $this->success(null, 'Book deleted successfully');
            } else {
                $this->error('Failed to delete book', 500);
            }

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $this->error('Cannot delete book: it is used in loans', 409);
            } else {
                $this->error('Database error: ' . $e->getMessage(), 500);
            }
        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
}
?>
