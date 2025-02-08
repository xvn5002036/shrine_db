<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取訊息 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // 檢查訊息是否存在
    $stmt = $pdo->prepare("SELECT id FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        setFlashMessage('error', '找不到指定的訊息');
        header('Location: index.php');
        exit;
    }

    // 刪除訊息
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);

    setFlashMessage('success', '訊息已成功刪除');

} catch (PDOException $e) {
    error_log('Error deleting contact message: ' . $e->getMessage());
    setFlashMessage('error', '刪除訊息時發生錯誤');
}

// 返回列表頁面
header('Location: index.php');
exit; 