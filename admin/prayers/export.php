<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 設定 CSV 檔案標頭
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=prayers_' . date('Ymd_His') . '.csv');

// 建立檔案指標
$output = fopen('php://output', 'w');

// 寫入 BOM (Byte Order Mark)，解決 Excel 中文亂碼問題
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 寫入 CSV 標題列
fputcsv($output, [
    'ID',
    '申請人',
    'Email',
    '電話',
    '祈福類型',
    '祈福內容',
    '狀態',
    '申請時間',
    '處理者',
    '處理時間',
    '處理備註'
]);

// 狀態對應中文說明
$status_map = [
    'pending' => '待處理',
    'processing' => '處理中',
    'completed' => '已完成',
    'cancelled' => '已取消'
];

try {
    // 獲取所有祈福請求
    $stmt = $pdo->query("
        SELECT 
            pr.*,
            pt.name as prayer_type_name,
            u.name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            a1.username as processed_by_name
        FROM prayer_requests pr 
        LEFT JOIN prayer_types pt ON pr.type_id = pt.id
        LEFT JOIN users u ON pr.user_id = u.id
        LEFT JOIN admins a1 ON pr.processed_by = a1.id
        ORDER BY pr.created_at DESC
    ");

    // 逐行寫入資料
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['user_name'],
            $row['user_email'],
            $row['user_phone'],
            $row['prayer_type_name'],
            $row['content'],
            $status_map[$row['status']] ?? $row['status'],
            $row['created_at'],
            $row['processed_by_name'] ?? '',
            $row['processed_at'] ?? '',
            $row['notes'] ?? ''
        ]);
    }

    // 記錄操作日誌
    logAdminAction('匯出祈福請求', '匯出所有祈福請求資料');
} catch (PDOException $e) {
    error_log('Error exporting prayer requests: ' . $e->getMessage());
    die('匯出資料時發生錯誤');
}

// 關閉檔案指標
fclose($output); 
