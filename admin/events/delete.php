<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的活動ID';
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // 開始事務處理
    $pdo->beginTransaction();

    // 檢查活動是否存在
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        throw new Exception('找不到指定的活動');
    }

    // 檢查是否有相關的報名記錄
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ?");
    $stmt->execute([$id]);
    $registration_count = $stmt->fetchColumn();

    if ($registration_count > 0) {
        // 如果有報名記錄，將活動狀態改為已刪除而不是實際刪除
        $stmt = $pdo->prepare("UPDATE events SET status = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '活動已標記為已刪除';
    } else {
        // 如果沒有報名記錄，可以實際刪除活動
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '活動已成功刪除';
    }

    // 提交事務
    $pdo->commit();

} catch (Exception $e) {
    // 發生錯誤時回滾事務
    $pdo->rollBack();
    $_SESSION['error'] = '刪除失敗：' . $e->getMessage();
}

// 重定向回列表頁面
header('Location: index.php');
exit();
?> 
