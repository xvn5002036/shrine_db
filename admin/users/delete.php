<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的用戶ID';
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // 檢查是否為當前登入的用戶
    if ($id === $_SESSION['admin_id']) {
        throw new Exception('無法刪除當前登入的用戶');
    }

    // 開始事務處理
    $pdo->beginTransaction();

    // 檢查用戶是否存在
    $stmt = $pdo->prepare("SELECT * FROM addusers WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('找不到指定的用戶');
    }

    // 檢查是否有相關的資料（例如：預約記錄、活動報名等）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blessing_registrations WHERE user_id = ?");
    $stmt->execute([$id]);
    $has_blessings = $stmt->fetchColumn() > 0;

    if ($has_blessings) {
        // 如果有相關資料，將用戶狀態改為停用而不是實際刪除
        $stmt = $pdo->prepare("UPDATE addusers SET status = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '該用戶有相關資料，已將狀態改為停用';
    } else {
        // 如果沒有相關資料，可以實際刪除用戶
        $stmt = $pdo->prepare("DELETE FROM addusers WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '用戶已成功刪除';
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