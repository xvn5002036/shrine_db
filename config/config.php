<?php
// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 設置默認字符集
mb_internal_encoding('UTF-8');

// 定義常量
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('BACKUP_PATH', BASE_PATH . '/backups');
define('CONFIG_PATH', __DIR__);
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('REPORTS_PATH', BASE_PATH . '/reports');

// 創建必要的目錄
$directories = [
    UPLOAD_PATH,
    UPLOAD_PATH . '/news',
    UPLOAD_PATH . '/events',
    UPLOAD_PATH . '/members',
    BACKUP_PATH,
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Session 設定 (必須在 session_start 之前)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true
]);

// 開啟會話
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 加載數據庫配置
$database_config = require_once __DIR__ . '/database.php';

// 初始化資料庫連接
try {
    $pdo = new PDO(
        "mysql:host={$database_config['host']};dbname={$database_config['dbname']};charset={$database_config['charset']}",
        $database_config['username'],
        $database_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$database_config['charset']}"
        ]
    );
    $GLOBALS['pdo'] = $pdo;
    
    // 同時建立 mysqli 連接
    $conn = new mysqli(
        $database_config['host'],
        $database_config['username'],
        $database_config['password'],
        $database_config['dbname']
    );
    $conn->set_charset($database_config['charset']);
    $GLOBALS['conn'] = $conn;
    
} catch (Exception $e) {
    die('資料庫連接失敗：' . $e->getMessage());
}

// 網站基本設置
$config = [
    'site_name' => '宮廟管理系統',
    'site_description' => '專業的宮廟管理系統',
    'admin_email' => 'admin@example.com',
    'upload_max_size' => 5 * 1024 * 1024, // 5MB
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif'],
    'pagination_per_page' => 10
];

// 網站基本設定
define('SITE_NAME', $config['site_name']);
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', $config['admin_email']);

// 資料庫設定
define('DB_HOST', $database_config['host']);
define('DB_NAME', $database_config['dbname']);
define('DB_USER', $database_config['username']);
define('DB_PASS', $database_config['password']);

// 網站功能設定
define('ENABLE_REGISTRATION', true);  // 是否開放註冊
define('ENABLE_COMMENTS', true);      // 是否開放評論
define('ITEMS_PER_PAGE', $config['pagination_per_page']);

// 上傳檔案設定
define('MAX_FILE_SIZE', $config['upload_max_size']);
define('ALLOWED_FILE_TYPES', $config['allowed_image_types']);

// 安全設定
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_ATTEMPTS_TIMEOUT', 1800); // 30分鐘

// 自訂函數
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// 錯誤處理函數
function handleError($error) {
    $_SESSION['error'] = $error;
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// 成功處理函數
function handleSuccess($message) {
    $_SESSION['success'] = $message;
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>



