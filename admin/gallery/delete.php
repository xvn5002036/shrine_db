<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的相簿ID';
    header('Location: upload.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // 開始事務處理
    $pdo->beginTransaction();

    // 檢查相簿是否存在
    $stmt = $pdo->prepare("SELECT * FROM gallery_albums WHERE id = ?");
    $stmt->execute([$id]);
    $album = $stmt->fetch();

    if (!$album) {
        throw new Exception('找不到指定的相簿');
    }

    // 獲取相簿中的所有照片
    $stmt = $pdo->prepare("SELECT file_name FROM gallery_photos WHERE album_id = ?");
    $stmt->execute([$id]);
    $photos = $stmt->fetchAll();

    // 刪除所有照片檔案
    foreach ($photos as $photo) {
        $file_path = $root_path . '/uploads/gallery/' . $id . '/' . $photo['file_name'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // 刪除相簿目錄
    $album_dir = $root_path . '/uploads/gallery/' . $id;
    if (file_exists($album_dir)) {
        rmdir($album_dir);
    }

    // 刪除資料庫中的照片記錄
    $stmt = $pdo->prepare("DELETE FROM gallery_photos WHERE album_id = ?");
    $stmt->execute([$id]);

    // 刪除相簿記錄
    $stmt = $pdo->prepare("DELETE FROM gallery_albums WHERE id = ?");
    $stmt->execute([$id]);

    // 提交事務
    $pdo->commit();
    
    $_SESSION['success'] = '相簿已成功刪除';

} catch (Exception $e) {
    // 發生錯誤時回滾事務
    $pdo->rollBack();
    $_SESSION['error'] = '刪除失敗：' . $e->getMessage();
}

// 重定向回列表頁面
header('Location: upload.php');
exit(); 