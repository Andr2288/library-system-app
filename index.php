<?php
// Головний файл додатку
require_once 'config/database.php';
require_once 'controllers/HomeController.php';

// Простий роутер
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

switch ($controller) {
    case 'home':
        $homeController = new HomeController();
        if ($action === 'index') {
            $homeController->index();
        }
        break;
    case 'books':
        require_once 'controllers/BookController.php';
        $bookController = new BookController();
        if (method_exists($bookController, $action)) {
            $bookController->$action();
        }
        break;
    case 'readers':
        require_once 'controllers/ReaderController.php';
        $readerController = new ReaderController();
        if (method_exists($readerController, $action)) {
            $readerController->$action();
        }
        break;
    case 'loans':
        require_once 'controllers/LoanController.php';
        $loanController = new LoanController();
        if (method_exists($loanController, $action)) {
            $loanController->$action();
        }
        break;
    default:
        $homeController = new HomeController();
        $homeController->index();
        break;
}
?>
