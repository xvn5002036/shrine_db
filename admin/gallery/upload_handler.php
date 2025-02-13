<?php
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查管理員權限
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

// 驗證必要欄位
if (!isset($_POST['title']) || !isset($_POST['category']) || !isset($_POST['event_date'])) {
    echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
    exit;
}

try {
    // 開始事務
    $pdo->beginTransaction();

    // 創建相簿
    $stmt = $pdo->prepare("
        INSERT INTO gallery_albums (
            title, description, event_date, category,
            status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['description'] ?? '',
        $_POST['event_date'],
        $_POST['category'],
        $_POST['status'] ?? 'draft',
        $_SESSION['admin_id']
    ]);

    $album_id = $pdo->lastInsertId();

    // 確保上傳目錄存在
    $upload_dir = '../../uploads/gallery/' . $album_id;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uploaded_files = [];
    $upload_errors = [];

    // 檢查是否有檔案上傳
    if (!isset($_FILES['photos'])) {
        throw new Exception('沒有收到任何檔案');
    }

    // 處理上傳的檔案
    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) {
            $upload_errors[] = "檔案 {$_FILES['photos']['name'][$key]} 上傳失敗";
            continue;
        }

        // 檢查檔案類型
        $file_type = $_FILES['photos']['type'][$key];
        if (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
            $upload_errors[] = "檔案 {$_FILES['photos']['name'][$key]} 類型不支援";
            continue;
        }

        // 檢查檔案大小
        $file_size = $_FILES['photos']['size'][$key];
        if ($file_size > 5 * 1024 * 1024) { // 5MB
            $upload_errors[] = "檔案 {$_FILES['photos']['name'][$key]} 超過大小限制";
            continue;
        }

        // 產生新檔名
        $extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $extension;
        $file_path = $upload_dir . '/' . $new_file_name;

        // 移動檔案
        if (move_uploaded_file($tmp_name, $file_path)) {
            // 插入資料庫
            $stmt = $pdo->prepare("
                INSERT INTO gallery_photos (
                    album_id, filename, original_name,
                    file_type, file_size, created_at
                ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $album_id,
                $new_file_name,
                $_FILES['photos']['name'][$key],
                $file_type,
                $file_size
            ]);

            $uploaded_files[] = [
                'id' => $pdo->lastInsertId(),
                'filename' => $new_file_name,
                'original_name' => $_FILES['photos']['name'][$key]
            ];

            // 設置第一張照片為封面
            if (count($uploaded_files) === 1) {
                $stmt = $pdo->prepare("UPDATE gallery_albums SET cover_photo = ? WHERE id = ?");
                $stmt->execute([$new_file_name, $album_id]);
            }
        } else {
            $upload_errors[] = "檔案 {$_FILES['photos']['name'][$key]} 儲存失敗";
        }
    }

    // 檢查是否有成功上傳的檔案
    if (empty($uploaded_files)) {
        throw new Exception('沒有成功上傳任何照片: ' . implode(', ', $upload_errors));
    }

    // 提交事務
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '相簿建立成功',
        'album_id' => $album_id,
        'files' => $uploaded_files,
        'errors' => $upload_errors // 回傳任何上傳錯誤
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => '上傳失敗：' . $e->getMessage()
    ]);
} 

