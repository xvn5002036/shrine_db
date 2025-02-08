<?php
// 獲取資料庫配置
$dbConfig = require_once __DIR__ . '/../config/database.php';

// 建立資料庫連接
try {
    $conn = new mysqli(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['dbname']
    );

    // 檢查連接
    if ($conn->connect_error) {
        throw new Exception("連接失敗：" . $conn->connect_error);
    }

    // 設置字符集
    $conn->set_charset($dbConfig['charset']);

} catch (Exception $e) {
    error_log("資料庫連接錯誤：" . $e->getMessage());
    die("無法連接到資料庫，請稍後再試。");
} 