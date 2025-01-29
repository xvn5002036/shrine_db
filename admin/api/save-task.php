<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員登入狀態
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未授權的訪問']);
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit;
}

// 驗證必要欄位
$requiredFields = ['name', 'type', 'schedule'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => sprintf('缺少必要欄位：%s', $field)]);
        exit;
    }
}

// 驗證任務類型
$validTypes = ['backup', 'cleanup', 'report', 'notification'];
if (!in_array($_POST['type'], $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的任務類型']);
    exit;
}

// 驗證 Cron 表達式
if (!validateCronExpression($_POST['schedule'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的 Cron 表達式']);
    exit;
}

try {
    // 驗證參數格式
    $params = isset($_POST['params']) ? $_POST['params'] : '{}';
    if (!isValidJson($params)) {
        throw new Exception('無效的參數格式');
    }

    // 準備資料
    $data = [
        'name' => $_POST['name'],
        'type' => $_POST['type'],
        'schedule' => $_POST['schedule'],
        'params' => $params,
        'status' => 'active',
        'is_running' => 0
    ];

    // 儲存到資料庫
    $stmt = $db->prepare("
        INSERT INTO automation_tasks 
        (name, type, schedule, params, status, is_running, created_at, updated_at)
        VALUES 
        (:name, :type, :schedule, :params, :status, :is_running, NOW(), NOW())
    ");
    $stmt->execute($data);

    $taskId = $db->lastInsertId();

    // 記錄日誌
    $message = sprintf('新增自動化任務：%s (ID: %d)', $data['name'], $taskId);
    $stmt = $db->prepare("INSERT INTO automation_logs (task_id, level, message, created_at) VALUES (?, 'info', ?, NOW())");
    $stmt->execute([$taskId, $message]);

    echo json_encode(['success' => true, 'taskId' => $taskId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 驗證 Cron 表達式
 */
function validateCronExpression($expression) {
    $pattern = '/^(\*|([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])|\*\/([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])) (\*|([0-9]|1[0-9]|2[0-3])|\*\/([0-9]|1[0-9]|2[0-3])) (\*|([1-9]|1[0-9]|2[0-9]|3[0-1])|\*\/([1-9]|1[0-9]|2[0-9]|3[0-1])) (\*|([1-9]|1[0-2])|\*\/([1-9]|1[0-2])) (\*|([0-6])|\*\/([0-6]))$/';
    return preg_match($pattern, trim($expression));
}

/**
 * 驗證 JSON 格式
 */
function isValidJson($string) {
    if (empty($string)) return true;
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
} 