<?php
// 獲取資料庫配置
$root_path = $_SERVER['DOCUMENT_ROOT'];
$configPath = $root_path . '/config/database.php';

if (!file_exists($configPath)) {
    die("錯誤：找不到資料庫配置文件 ({$configPath})");
}

$dbConfig = require $configPath;

if (!is_array($dbConfig)) {
    die("錯誤：資料庫配置文件格式不正確");
}

// 檢查必要的配置項
$required = ['host', 'username', 'password', 'dbname', 'charset'];
foreach ($required as $field) {
    if (!isset($dbConfig[$field])) {
        die("錯誤：資料庫配置缺少 {$field} 設定");
    }
}

// 建立資料庫連接
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);

} catch (PDOException $e) {
    error_log("資料庫連接錯誤：" . $e->getMessage());
    die("無法連接到資料庫，請稍後再試。");
}
?> 