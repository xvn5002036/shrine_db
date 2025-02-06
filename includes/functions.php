<?php
/**
 * 通用函數庫
 */

require_once __DIR__ . '/auth.php';

// 初始化資料庫連線
try {
    $dbConfig = require dirname(__DIR__) . '/config/database.php';
    
    // 先嘗試連接到 MySQL 伺服器
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 檢查資料庫是否存在
    $dbname = $dbConfig['dbname'];
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    
    if (!$result->fetch()) {
        // 創建資料庫
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    // 選擇資料庫
    $pdo->exec("USE `$dbname`");
    
    // 設置全域變數
    $GLOBALS['pdo'] = $pdo;
    
} catch (PDOException $e) {
    error_log('資料庫連線錯誤：' . $e->getMessage());
    die('資料庫連線錯誤，請檢查設定。');
}

/**
 * 獲取網站設定值
 * @param string $key 設定鍵名
 * @param string $default 預設值
 * @return string
 */
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : $default;
    } catch (Exception $e) {
        error_log('獲取設定值錯誤：' . $e->getMessage());
        return $default;
    }
}

/**
 * 更新網站設定
 */
function updateSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (`key`, `value`, `created_at`, `updated_at`)
            VALUES (?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            `value` = ?, `updated_at` = NOW()
        ");
        
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        error_log('無法更新網站設定：' . $e->getMessage());
        return false;
    }
}

/**
 * 獲取當前用戶ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * 安全過濾輸入
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * 產生隨機字串
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * 格式化日期時間
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($datetime));
}

/**
 * 檢查檔案是否為圖片
 */
function isImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowedTypes);
}

/**
 * 上傳檔案
 */
function uploadFile($file, $targetDir, $allowedTypes = [], $maxSize = 5242880) {
    // 檢查檔案大小
    if ($file['size'] > $maxSize) {
        throw new Exception('檔案大小超過限制');
    }

    // 檢查檔案類型
    if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
        throw new Exception('不支援的檔案類型');
    }

    // 確保目標目錄存在
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 產生唯一檔名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateRandomString() . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;

    // 移動檔案
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('檔案上傳失敗');
    }

    return $filename;
}

/**
 * 刪除檔案
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * 產生分頁資訊
 */
function generatePagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
        'offset' => ($currentPage - 1) * $perPage
    ];
}

/**
 * 記錄系統日誌
 */
function logSystemActivity($action, $level = 'info', $details = '') {
    global $pdo;
    
    $userId = getCurrentUserId();
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("
        INSERT INTO system_logs 
        (user_id, action, level, details, ip_address, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([$userId, $action, $level, $details, $ip]);
}

/**
 * 檢查權限
 */
function checkPermission($permission) {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    
    return in_array($permission, $_SESSION['user_permissions']);
}

/**
 * 發送系統通知
 */
function sendSystemNotification($userId, $title, $message, $type = 'info') {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, message, type, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([$userId, $title, $message, $type]);
}

/**
 * 格式化金額
 */
function formatAmount($amount, $decimals = 0) {
    return number_format($amount, $decimals, '.', ',');
}

/**
 * 產生 CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 驗證 CSRF Token
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 重定向並顯示訊息
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header('Location: ' . $url);
    exit;
}

/**
 * 獲取並清除快閃訊息
 */
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $message;
}

/**
 * 獲取最後備份時間
 * @return string|null 最後備份時間，如果沒有備份則返回 null
 */
function get_last_backup_time() {
    $backup_dir = dirname(__DIR__) . '/backups';
    
    // 如果備份目錄不存在
    if (!is_dir($backup_dir)) {
        return null;
    }
    
    // 獲取所有備份文件
    $files = glob($backup_dir . '/*.sql');
    
    if (empty($files)) {
        return null;
    }
    
    // 獲取最新的備份文件
    $latest_backup = max(array_map('filemtime', $files));
    
    return date('Y-m-d H:i:s', $latest_backup);
}

/**
 * 檢查管理員登入狀態
 */
function checkAdminLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_id'])) {
        // 記錄訪問嘗試
        error_log('未授權的後台訪問嘗試：' . $_SERVER['REQUEST_URI']);
        
        // 保存當前URL
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // 獲取目前腳本的目錄深度
        $current_path = $_SERVER['SCRIPT_FILENAME'];
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $relative_path = str_replace($document_root, '', $current_path);
        $depth = substr_count($relative_path, '/') - 1;
        
        // 根據深度構建返回路徑
        $path_to_admin = str_repeat('../', $depth) . 'admin/login.php';
        
        header('Location: ' . $path_to_admin);
        exit;
    }
    
    // 檢查管理員是否仍然有效
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, status FROM users WHERE id = ? AND status = 1 AND role = 'admin'");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            // 管理員不存在或已被停用
            session_destroy();
            $depth = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']), '/') - 1;
            $path_to_admin = str_repeat('../', $depth) . 'admin/login.php';
            header('Location: ' . $path_to_admin . '?error=account_inactive');
            exit;
        }
    } catch (PDOException $e) {
        error_log('檢查管理員狀態時發生錯誤：' . $e->getMessage());
        // 發生錯誤時，為安全起見，登出用戶
        session_destroy();
        $depth = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']), '/') - 1;
        $path_to_admin = str_repeat('../', $depth) . 'admin/login.php';
        header('Location: ' . $path_to_admin . '?error=system_error');
        exit;
    }
}

/**
 * 驗證管理員憑證
 */
function validateAdminCredentials($username, $password) {
    global $pdo;
    try {
        // 先檢查用戶是否存在
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("管理員登入失敗：找不到用戶名 {$username}");
            return false;
        }

        // 檢查角色和狀態
        if ($user['role'] !== 'admin') {
            error_log("管理員登入失敗：用戶 {$username} 不是管理員角色");
            return false;
        }

        if ($user['status'] != 1) {
            error_log("管理員登入失敗：用戶 {$username} 狀態不是啟用狀態");
            return false;
        }

        // 驗證密碼
        if (password_verify($password, $user['password'])) {
            // 設置登入狀態
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            
            // 更新最後登入時間
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            error_log("管理員 {$username} 登入成功");
            return true;
        } else {
            error_log("管理員登入失敗：{$username} 密碼錯誤");
            return false;
        }
    } catch (PDOException $e) {
        error_log('驗證管理員憑證時發生錯誤：' . $e->getMessage());
        return false;
    }
}

/**
 * 生成安全的密碼雜湊
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 獲取狀態標籤的HTML
 */
function getStatusBadge($status) {
    $badges = [
        'published' => '<span class="status-badge published">已發布</span>',
        'draft' => '<span class="status-badge draft">草稿</span>',
        'pending' => '<span class="status-badge pending">待審核</span>',
        'archived' => '<span class="status-badge archived">已歸檔</span>'
    ];
    
    return $badges[$status] ?? '<span class="status-badge">未知</span>';
}

/**
 * 截斷文本到指定長度
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * 設置提示消息
 * @param string $type 消息類型 (success, error, warning, info)
 * @param string $message 消息內容
 */
function setFlashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * 顯示並清除提示消息
 */
function displayFlashMessages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $msg) {
            $type = htmlspecialchars($msg['type']);
            $message = htmlspecialchars($msg['message']);
            echo "<div class='alert alert-{$type}'>{$message}</div>";
        }
        // 清除已顯示的消息
        $_SESSION['flash_messages'] = [];
    }
}

/**
 * 記錄管理員操作日誌
 */
function logAdminAction($action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, ip_address, created_at)
            VALUES (:admin_id, :action, :details, :ip_address, NOW())
        ");
        
        $stmt->execute([
            ':admin_id' => $_SESSION['admin_id'] ?? 0,
            ':action' => $action,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log('記錄管理員操作時發生錯誤：' . $e->getMessage());
    }
}

// 為了向後兼容，保留舊的函數名稱
function set_flash_message($type, $message) {
    setFlashMessage($type, $message);
}

function display_flash_messages() {
    displayFlashMessages();
}

/**
 * 將檔案大小轉換為易讀的格式
 * @param int $bytes 檔案大小（位元組）
 * @param int $decimals 小數點位數
 * @return string 格式化後的檔案大小
 */
function formatFileSize($bytes, $decimals = 2) {
    $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
} 
