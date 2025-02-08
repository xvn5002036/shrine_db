<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的消息ID';
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // 開始事務處理
    $pdo->beginTransaction();

    // 檢查消息是否存在
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();

    if (!$news) {
        throw new Exception('找不到指定的消息');
    }

    // 如果有圖片，先刪除圖片文件
    if (!empty($news['image'])) {
        $image_path = $root_path . $news['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // 刪除消息記錄
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$id]);
    
    // 提交事務
    $pdo->commit();
    
    $_SESSION['success'] = '消息已成功刪除';

} catch (Exception $e) {
    // 發生錯誤時回滾事務
    $pdo->rollBack();
    $_SESSION['error'] = '刪除失敗：' . $e->getMessage();
}

// 重定向回列表頁面
header('Location: index.php');
exit(); 