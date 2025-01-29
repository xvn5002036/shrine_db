<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', '未指定要刪除的新聞');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // 開始交易
    $pdo->beginTransaction();

    // 先獲取新聞資訊，特別是圖片路徑
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();

    if (!$news) {
        throw new Exception('找不到指定的新聞');
    }

    // 刪除相關的圖片文件
    if (!empty($news['image'])) {
        $image_path = '../../' . $news['image'];
        if (file_exists($image_path)) {
            if (!unlink($image_path)) {
                error_log("無法刪除圖片文件：{$image_path}");
            }
        }
    }

    // 記錄操作日誌
    logAdminAction('刪除新聞', "刪除新聞：{$news['title']} (ID: {$id})");

    // 刪除新聞記錄
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // 提交交易
    $pdo->commit();

    // 設置成功消息
    setFlashMessage('success', '新聞已成功刪除');

} catch (Exception $e) {
    // 回滾交易
    $pdo->rollBack();
    
    // 記錄錯誤
    error_log('刪除新聞時發生錯誤：' . $e->getMessage());
    
    // 設置錯誤消息
    setFlashMessage('error', '刪除新聞時發生錯誤：' . $e->getMessage());
}

// 重定向回列表頁面
header('Location: index.php');
exit; 