<?php
class BaseController {
    
    protected function validateInput($data) {
        return htmlspecialchars(trim(stripslashes($data)));
    }
    
    protected function redirect($url) {
        header("Location: $url");
        exit();
    }
    
    protected function renderView($viewFile, $data = []) {
        extract($data);
        require_once "views/$viewFile";
    }
}
?>
