<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取祈福請求 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', '無效的祈福請求 ID');
    header('Location: index.php');
    exit;
}

try {
    // 檢查祈福請求是否存在且狀態為待處理
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM prayer_requests 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $id]);
    $prayer = $stmt->fetch();

    if (!$prayer) {
        setFlashMessage('error', '找不到指定的祈福請求');
        header('Location: index.php');
        exit;
    }

    if ($prayer['status'] !== 'pending') {
        setFlashMessage('error', '只能取消待處理的祈福請求');
        header('Location: index.php');
        exit;
    }

    // 更新祈福請求狀態為已取消
    $stmt = $pdo->prepare("
        UPDATE prayer_requests 
        SET status = 'cancelled',
            processed_by = :processed_by,
            processed_at = NOW(),
            notes = CONCAT(COALESCE(notes, ''), '\n系統自動取消')
        WHERE id = :id
    ");

    $stmt->execute([
        ':processed_by' => $_SESSION['admin_id'],
        ':id' => $id
    ]);

    // 記錄操作日誌
    logAdminAction('取消祈福請求', "取消祈福請求 ID: {$id}");

    setFlashMessage('success', '祈福請求已取消');
} catch (PDOException $e) {
    error_log('Error cancelling prayer request: ' . $e->getMessage());
    setFlashMessage('error', '取消祈福請求時發生錯誤');
}

header('Location: index.php');
exit; 