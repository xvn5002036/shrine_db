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

// 檢查是否有提供備份ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = '未提供有效的備份ID';
    header('Location: backup.php');
    exit;
}

try {
    // 開始事務
    $pdo->beginTransaction();

    // 獲取備份資訊
    $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $backup = $stmt->fetch();

    if ($backup) {
        // 刪除實體檔案
        $file_path = dirname(__DIR__) . '/backups/' . $backup['filename'];
        if (file_exists($file_path) && is_file($file_path)) {
            if (!unlink($file_path)) {
                throw new Exception('無法刪除檔案：' . $backup['filename']);
            }
        }

        // 從資料庫中刪除記錄
        $stmt = $pdo->prepare("DELETE FROM backups WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        // 提交事務
        $pdo->commit();
        $_SESSION['success'] = '備份已成功刪除';
    } else {
        throw new Exception('找不到指定的備份記錄');
    }
} catch (Exception $e) {
    // 回滾事務
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('刪除備份時發生錯誤：' . $e->getMessage());
    $_SESSION['error'] = '刪除失敗：' . $e->getMessage();
}

// 重定向回備份管理頁面
header('Location: backup.php');
exit; 