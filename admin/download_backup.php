<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 檢查管理員權限
checkAdminRole();

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $backup = $stmt->fetch();

        if ($backup) {
            $file_path = '../backups/' . $backup['filename'];
            
            if (file_exists($file_path)) {
                // 設定適當的標頭
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
                header('Content-Length: ' . filesize($file_path));
                
                // 讀取並輸出檔案
                readfile($file_path);
                exit;
            } else {
                throw new Exception('備份檔案不存在');
            }
        } else {
            throw new Exception('找不到指定的備份');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '下載失敗：' . $e->getMessage();
    }
}

header('Location: backup.php');
exit; 