<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 檢查是否登入
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 檢查是否有管理員權限
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $backup = $stmt->fetch();

        if ($backup) {
            $file_path = dirname(__DIR__) . '/backups/' . $backup['filename'];
            
            if (file_exists($file_path)) {
                // 清除任何已經輸出的內容
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // 設定適當的標頭
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($backup['filename']) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                
                // 讀取並輸出檔案
                readfile($file_path);
                exit;
            } else {
                $_SESSION['error'] = '備份檔案不存在：' . $backup['filename'];
            }
        } else {
            $_SESSION['error'] = '找不到指定的備份記錄';
        }
    } catch (Exception $e) {
        error_log('下載備份時發生錯誤：' . $e->getMessage());
        $_SESSION['error'] = '下載失敗：' . $e->getMessage();
    }
}

header('Location: backup.php');
exit; 