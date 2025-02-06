<?php
// 檢查是否已安裝
if (!file_exists('config/installed.php')) {
    // 如果尚未安裝，跳轉到安裝頁面
    header('Location: install/');
    exit;
}

// 如果已安裝，載入必要的設定檔
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取頁面內容
$page = $_GET['page'] ?? 'home';
$validPages = ['home', 'about', 'news', 'events', 'contact'];

if (!in_array($page, $validPages)) {
    $page = 'home';
}

// 載入頁面
include 'templates/header.php';
include 'pages/' . $page . '.php';
include 'includes/footer.php'; 

