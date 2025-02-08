<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的服務ID';
    header('Location: services.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // 開始事務處理
    $pdo->beginTransaction();

    // 檢查服務是否存在
    $stmt = $pdo->prepare("SELECT * FROM blessings WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if (!$service) {
        throw new Exception('找不到指定的服務');
    }

    // 檢查是否有相關的預約記錄
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blessing_registrations WHERE blessing_id = ?");
    $stmt->execute([$id]);
    $registration_count = $stmt->fetchColumn();

    if ($registration_count > 0) {
        // 如果有預約記錄，將服務狀態改為停用而不是實際刪除
        $stmt = $pdo->prepare("UPDATE blessings SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '該服務已有預約記錄，已將狀態改為停用';
    } else {
        // 如果沒有預約記錄，可以實際刪除服務
        $stmt = $pdo->prepare("DELETE FROM blessings WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '服務已成功刪除';
    }

    // 提交事務
    $pdo->commit();

} catch (Exception $e) {
    // 發生錯誤時回滾事務
    $pdo->rollBack();
    $_SESSION['error'] = '刪除失敗：' . $e->getMessage();
}

// 重定向回列表頁面
header('Location: services.php');
exit(); 