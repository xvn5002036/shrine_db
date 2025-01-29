<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供活動 ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$event_id = (int)$_GET['id'];

try {
    // 開始事務
    $pdo->beginTransaction();

    // 獲取活動資訊（主要是為了獲取圖片路徑）
    $stmt = $pdo->prepare("SELECT image FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if ($event) {
        // 刪除相關的報名記錄
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ?");
        $stmt->execute([$event_id]);

        // 刪除活動
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);

        // 如果有圖片，刪除圖片文件
        if ($event['image'] && file_exists('../../' . $event['image'])) {
            unlink('../../' . $event['image']);
        }

        // 提交事務
        $pdo->commit();

        // 記錄操作日誌
        $admin_id = $_SESSION['admin_id'];
        $log_stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_table, target_id, created_at)
            VALUES (:admin_id, 'delete', 'events', :event_id, CURRENT_TIMESTAMP)
        ");
        $log_stmt->execute([
            ':admin_id' => $admin_id,
            ':event_id' => $event_id
        ]);

        header('Location: index.php?success=2');
    } else {
        // 活動不存在
        $pdo->rollBack();
        header('Location: index.php?error=1');
    }
} catch (PDOException $e) {
    // 發生錯誤，回滾事務
    $pdo->rollBack();
    error_log("Error deleting event: " . $e->getMessage());
    header('Location: index.php?error=2');
}

exit; 