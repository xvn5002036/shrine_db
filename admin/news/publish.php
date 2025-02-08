<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取新聞 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // 更新新聞狀態為已發布
    $stmt = $pdo->prepare("
        UPDATE news 
        SET status = 'published', updated_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    
    // 設定成功訊息
    $_SESSION['success_message'] = '新聞已成功發布！';
    
} catch (PDOException $e) {
    error_log('Error publishing news: ' . $e->getMessage());
    $_SESSION['error_message'] = '發布新聞時發生錯誤：' . $e->getMessage();
}

// 重定向回新聞列表
header('Location: index.php');
exit; 