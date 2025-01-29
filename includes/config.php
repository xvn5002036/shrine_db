<?php
// 網站基本設定
define('SITE_NAME', '財團法人台北市福德宮');
define('SITE_URL', 'http://localhost');

// 資料庫設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'temple_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// 時區設定
date_default_timezone_set('Asia/Taipei');

// 錯誤報告設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 網站路徑設定
define('BASE_PATH', dirname(__DIR__));
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');

// 其他常數設定
define('DEFAULT_LANG', 'zh_TW');
define('ADMIN_EMAIL', 'admin@example.com');
?> 