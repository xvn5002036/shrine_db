<?php
// 只在 session 未啟動時才啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 檢查使用者是否已登入
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 檢查使用者是否為管理員
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * 檢查管理員權限
 * 如果不是管理員則重定向到登入頁面
 */
function checkAdminRole() {
    if (!isLoggedIn() || !isAdmin()) {
        $_SESSION['error'] = '請先登入管理員帳號';
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * 檢查使用者權限
 * 如果未登入則重定向到登入頁面
 */
function checkUserRole() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = '請先登入';
        header('Location: /login.php');
        exit;
    }
}

/**
 * 登入使用者
 * @param array $user 使用者資料
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
}

/**
 * 登出使用者
 */
function logout() {
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}
?> 
