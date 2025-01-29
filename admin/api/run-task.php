<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/automation.php';

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

// 獲取請求數據
$data = json_decode(file_get_contents('php://input'), true);
$taskId = isset($data['taskId']) ? intval($data['taskId']) : 0;

if ($taskId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無效的任務ID']);
    exit;
}

try {
    // 獲取任務資訊
    $stmt = $db->prepare("SELECT * FROM automation_tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        throw new Exception('任務不存在');
    }

    // 檢查任務是否已在執行中
    if ($task['is_running']) {
        throw new Exception('任務正在執行中');
    }

    // 更新任務狀態
    $stmt = $db->prepare("UPDATE automation_tasks SET is_running = 1, last_run = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$taskId]);

    // 記錄開始執行日誌
    $message = sprintf('任務 #%d (%s) 開始執行', $taskId, $task['name']);
    $stmt = $db->prepare("INSERT INTO automation_logs (task_id, level, message, created_at) VALUES (?, 'info', ?, NOW())");
    $stmt->execute([$taskId, $message]);

    // 非同步執行任務
    $params = json_decode($task['params'], true) ?? [];
    executeAutomationTask($task['type'], $params, $taskId);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 