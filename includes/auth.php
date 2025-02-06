<?php
// 檢查用戶是否已登入
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 檢查是否為管理員
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// 檢查管理員權限
function checkAdminAuth() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// 檢查用戶登入狀態
function checkAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// 登入用戶
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['is_admin'] = $user['is_admin'] ?? false;
}

// 登出用戶
function logoutUser() {
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}
?> 
