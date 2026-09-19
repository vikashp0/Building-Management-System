<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /Building/assets/login/login.php");
        exit();
    }
}

function require_role($required_role) {
    require_login();
    
    $user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    
    if (strtolower($user_role) !== strtolower($required_role)) {
        header("Location: /Building/assets/index.php");
        exit();
    }
}
?>