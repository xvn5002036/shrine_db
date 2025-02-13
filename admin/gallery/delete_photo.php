<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 檢查是否有管理員權限
checkAdminRole();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_id'])) {
    $photo_id = (int)$_POST['photo_id'];
    
    try {
        // 開始事務
        $pdo->beginTransaction();
        
        // 獲取圖片信息
        $stmt = $pdo->prepare("SELECT * FROM gallery_photos WHERE id = ?");
        $stmt->execute([$photo_id]);
        $photo = $stmt->fetch();
        
        if ($photo) {
            // 檢查是否為封面圖片
            $stmt = $pdo->prepare("SELECT cover_photo FROM gallery_albums WHERE id = ?");
            $stmt->execute([$photo['album_id']]);
            $album = $stmt->fetch();
            
            // 如果是封面圖片，清除封面設置
            if ($album['cover_photo'] === $photo['filename']) {
                $stmt = $pdo->prepare("UPDATE gallery_albums SET cover_photo = NULL WHERE id = ?");
                $stmt->execute([$photo['album_id']]);
            }
            
            // 刪除實際檔案
            $filepath = '../../uploads/gallery/' . $photo['album_id'] . '/' . $photo['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // 從資料庫中刪除記錄
            $stmt = $pdo->prepare("DELETE FROM gallery_photos WHERE id = ?");
            $stmt->execute([$photo_id]);
            
            // 提交事務
            $pdo->commit();
            
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('找不到圖片');
        }
    } catch (Exception $e) {
        // 回滾事務
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的請求']);
}