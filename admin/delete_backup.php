<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 檢查管理員權限
checkAdminRole();

if (isset($_GET['id'])) {
    try {
        // 開始事務
        $pdo->beginTransaction();

        // 獲取備份資訊
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $backup = $stmt->fetch();

        if ($backup) {
            // 刪除實體檔案
            $file_path = '../backups/' . $backup['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // 從資料庫中刪除記錄
            $stmt = $pdo->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            // 提交事務
            $pdo->commit();
            $_SESSION['success'] = '備份已刪除';
        } else {
            throw new Exception('找不到指定的備份');
        }
    } catch (Exception $e) {
        // 回滾事務
        $pdo->rollBack();
        $_SESSION['error'] = '刪除失敗：' . $e->getMessage();
    }
}

header('Location: backup.php');
exit; 