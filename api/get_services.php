<?php
require_once '../config/config.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// 檢查是否有提供類型ID
if (!isset($_GET['type_id']) || !is_numeric($_GET['type_id'])) {
    http_response_code(400);
    echo json_encode(['error' => '無效的類型ID']);
    exit();
}

$type_id = (int)$_GET['type_id'];

try {
    // 獲取指定類型的祈福服務
    $stmt = $pdo->prepare("
        SELECT id, name, price, duration 
        FROM blessings 
        WHERE type_id = ? AND status = 'active' 
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$type_id]);
    $services = $stmt->fetchAll();

    echo json_encode($services);

} catch (PDOException $e) {
    error_log('Error fetching services: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '獲取服務失敗']);
}
?> 