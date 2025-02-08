<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取聯絡表單 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // 檢查記錄是否存在
    $stmt = $pdo->prepare("SELECT id FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        setFlashMessage('error', '找不到指定的聯絡表單記錄');
        header('Location: index.php');
        exit;
    }

    // 刪除記錄
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$id]);

    setFlashMessage('success', '聯絡表單記錄已成功刪除');

} catch (PDOException $e) {
    error_log('Error deleting contact: ' . $e->getMessage());
    setFlashMessage('error', '刪除聯絡表單記錄時發生錯誤');
}

// 重定向回列表頁面
header('Location: index.php');
exit; 