<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取報名 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // 獲取報名資訊
    $stmt = $pdo->prepare("SELECT r.*, e.id as event_id FROM event_registrations r LEFT JOIN events e ON r.event_id = e.id WHERE r.id = ?");
    $stmt->execute([$id]);
    $registration = $stmt->fetch();

    if (!$registration) {
        $_SESSION['error'] = '找不到該報名記錄';
        header('Location: index.php');
        exit;
    }

    // 檢查報名狀態
    if ($registration['status'] !== 'pending') {
        $_SESSION['error'] = '該報名已經被處理過';
        header('Location: registrations.php?event_id=' . $registration['event_id']);
        exit;
    }

    // 更新報名狀態
    $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = '報名已確認';
    header('Location: registrations.php?event_id=' . $registration['event_id']);
    exit;

} catch (PDOException $e) {
    error_log('Error confirming registration: ' . $e->getMessage());
    $_SESSION['error'] = '確認報名時發生錯誤';
    header('Location: registrations.php?event_id=' . ($registration['event_id'] ?? ''));
    exit;
} 