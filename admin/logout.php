<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// 開始 session（如果尚未開始）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // 如果用戶已登入，記錄登出日誌
    if (isset($_SESSION['admin_id'])) {
        logAdminAction('logout', "管理員 {$_SESSION['admin_username']} 登出系統");
    }

    // 清除所有 session 變數
    $_SESSION = array();

    // 刪除 session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // 銷毀 session
    session_destroy();

    // 設置成功訊息
    setFlashMessage('success', '您已成功登出系統');

} catch (Exception $e) {
    error_log('登出時發生錯誤：' . $e->getMessage());
    setFlashMessage('error', '登出時發生錯誤');
}

// 重定向到登入頁面
header('Location: login.php');
exit; 