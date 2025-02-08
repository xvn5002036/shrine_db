<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 檢查用戶是否已登入
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_role'])) {
    // 如果用戶未登入，將其重定向到登入頁面
    header('Location: /admin/login.php');
    exit();
}

// 檢查用戶角色是否為管理員
if ($_SESSION['admin_role'] !== 'admin') {
    // 如果用戶不是管理員，將其重定向到首頁
    header('Location: /');
    exit();
}

// 設定預設時區
date_default_timezone_set('Asia/Taipei');
?> 
