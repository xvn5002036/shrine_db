<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 獲取操作類型和訊息 ID 列表
$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];

// 驗證參數
if (empty($action) || empty($ids) || !is_array($ids)) {
    setFlashMessage('error', '無效的請求參數');
    header('Location: index.php');
    exit;
}

try {
    // 將 ID 轉換為整數並過濾無效值
    $ids = array_filter(array_map('intval', $ids));
    
    // 準備 SQL 參數佔位符
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    // 根據操作類型執行相應的操作
    switch ($action) {
        case 'mark-read':
            $stmt = $pdo->prepare("
                UPDATE contact_messages 
                SET status = 'read',
                    updated_at = NOW() 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($ids);
            setFlashMessage('success', '已將選中的訊息標記為已讀');
            break;
            
        case 'mark-archived':
            $stmt = $pdo->prepare("
                UPDATE contact_messages 
                SET status = 'archived',
                    updated_at = NOW() 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($ids);
            setFlashMessage('success', '已將選中的訊息封存');
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            setFlashMessage('success', '已刪除選中的訊息');
            break;
            
        default:
            setFlashMessage('error', '不支援的操作類型');
    }
    
} catch (PDOException $e) {
    error_log('Error executing bulk action: ' . $e->getMessage());
    setFlashMessage('error', '執行批量操作時發生錯誤');
}

// 返回列表頁面
header('Location: index.php');
exit; 