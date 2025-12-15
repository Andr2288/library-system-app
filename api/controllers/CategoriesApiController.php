<?php
require_once 'BaseApiController.php';
require_once __DIR__ . '/../../models/CategoryModel.php';

class CategoriesApiController extends BaseApiController {
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = new CategoryModel();
    }

    public function index() {
        try {
            $categories = $this->categoryModel->getAllWithStats();
            $this->success($categories);
        } catch (Exception $e) {
            $this->error('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }

    public function show($id) {
        try {
            $category = $this->categoryModel->getById($id);
            if (!$category) {
                $this->error('Category not found', 404);
            }
            $this->success($category);
        } catch (Exception $e) {
            $this->error('Failed to fetch category: ' . $e->getMessage(), 500);
        }
    }

    public function popular() {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $categories = $this->categoryModel->getPopularCategories($limit);
            $this->success($categories);
        } catch (Exception $e) {
            $this->error('Failed to fetch popular categories: ' . $e->getMessage(), 500);
        }
    }

    public function create() {
        try {
            $input = $this->getJsonInput();
            
            $errors = $this->validateRequired($input, ['name']);
            
            if (!empty($errors)) {
                $this->error('Validation failed', 400, $errors);
            }

            $name = $this->validateInput($input['name']);
            
            if (strlen($name) < 2) {
                $this->error('Name must be at least 2 characters', 400);
            }

            $data = [
                'name' => $name,
                'description' => isset($input['description']) ? $this->validateInput($input['description']) : null,
                'floor_location' => isset($input['floor_location']) ? $this->validateInput($input['floor_location']) : null
            ];

            if ($this->categoryModel->create($data)) {
                $this->success(null, 'Category created successfully');
            } else {
                $this->error('Failed to create category', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function update($id) {
        try {
            $category = $this->categoryModel->getById($id);
            if (!$category) {
                $this->error('Category not found', 404);
            }

            $input = $this->getJsonInput();

            if (isset($input['name'])) {
                $name = $this->validateInput($input['name']);
                if (strlen($name) < 2) {
                    $this->error('Name must be at least 2 characters', 400);
                }
            }

            $updateData = [];
            $fields = ['name', 'description', 'floor_location'];
            
            foreach ($fields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $this->validateInput($input[$field]);
                }
            }

            if ($this->categoryModel->update($id, $updateData)) {
                $this->success(null, 'Category updated successfully');
            } else {
                $this->error('Failed to update category', 500);
            }

        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }

    public function delete($id) {
        try {
            $category = $this->categoryModel->getById($id);
            if (!$category) {
                $this->error('Category not found', 404);
            }

            if ($this->categoryModel->delete($id)) {
                $this->success(null, 'Category deleted successfully');
            } else {
                $this->error('Failed to delete category', 500);
            }

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $this->error('Cannot delete category: it is used by books or loans', 409);
            } else {
                $this->error('Database error: ' . $e->getMessage(), 500);
            }
        } catch (Exception $e) {
            $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
}
?>
