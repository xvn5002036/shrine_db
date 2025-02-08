<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取報名 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['error'] = '無效的報名記錄ID';
    header('Location: index.php');
    exit;
}

try {
    // 獲取報名資訊
    $stmt = $pdo->prepare("
        SELECT r.*, e.id as event_id, e.title as event_title 
        FROM event_registrations r 
        LEFT JOIN events e ON r.event_id = e.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $registration = $stmt->fetch();

    if (!$registration) {
        $_SESSION['error'] = '找不到該報名記錄';
        header('Location: index.php');
        exit;
    }

    // 刪除報名記錄
    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE id = ?");
    $stmt->execute([$id]);

    // 更新活動的目前參加人數
    $stmt = $pdo->prepare("
        UPDATE events e 
        SET current_participants = (
            SELECT COALESCE(SUM(participants), 0)
            FROM event_registrations 
            WHERE event_id = e.id 
            AND status = 'confirmed'
        )
        WHERE id = ?
    ");
    $stmt->execute([$registration['event_id']]);

    $_SESSION['success'] = '報名記錄已成功刪除';
    
} catch (PDOException $e) {
    error_log('Error deleting registration: ' . $e->getMessage());
    $_SESSION['error'] = '刪除報名記錄時發生錯誤';
}

// 返回報名列表頁面
header('Location: registrations.php?event_id=' . $registration['event_id']);
exit; 