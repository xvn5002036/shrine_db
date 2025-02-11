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
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * 檢查使用者是否為管理員
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && 
           ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff');
}

/**
 * 檢查管理員權限
 * 如果不是管理員則重定向到登入頁面
 */
function checkAdminRole() {
    // 檢查是否在登入頁面
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    if ($current_script === 'login.php') {
        return;
    }

    // 檢查是否已登入且是管理員
    if (!isLoggedIn()) {
        $_SESSION['error'] = '請先登入';
        header('Location: /admin/login.php');
        exit;
    }

    if (!isAdmin()) {
        $_SESSION['error'] = '您沒有管理員權限';
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
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
}

/**
 * 登出使用者
 */
function logout() {
    // 清除所有 session 變數
    $_SESSION = array();
    
    // 如果有設置 session cookie，也要清除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // 銷毀 session
    session_destroy();
    
    // 重定向到登入頁面
    header('Location: /admin/login.php');
    exit;
}
?> 
