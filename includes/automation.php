<?php
/**
 * 自動化任務處理核心
 */

/**
 * 執行自動化任務
 */
function executeAutomationTask($type, $params, $taskId) {
    global $db;

    try {
        switch ($type) {
            case 'backup':
                executeBackupTask($params);
                break;
            case 'cleanup':
                executeCleanupTask($params);
                break;
            case 'report':
                executeReportTask($params);
                break;
            case 'notification':
                executeNotificationTask($params);
                break;
            default:
                throw new Exception('不支援的任務類型');
        }

        // 更新任務狀態
        $stmt = $db->prepare("UPDATE automation_tasks SET is_running = 0, last_success = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$taskId]);

        // 記錄成功日誌
        $message = sprintf('任務 #%d 執行完成', $taskId);
        $stmt = $db->prepare("INSERT INTO automation_logs (task_id, level, message, created_at) VALUES (?, 'success', ?, NOW())");
        $stmt->execute([$taskId, $message]);

    } catch (Exception $e) {
        // 更新任務狀態
        $stmt = $db->prepare("UPDATE automation_tasks SET is_running = 0, last_error = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$e->getMessage(), $taskId]);

        // 記錄錯誤日誌
        $message = sprintf('任務 #%d 執行失敗：%s', $taskId, $e->getMessage());
        $stmt = $db->prepare("INSERT INTO automation_logs (task_id, level, message, created_at) VALUES (?, 'error', ?, NOW())");
        $stmt->execute([$taskId, $message]);
    }
}

/**
 * 執行資料庫備份任務
 */
function executeBackupTask($params) {
    global $db;
    
    // 獲取資料庫配置
    $dbConfig = parse_ini_file(__DIR__ . '/../config/database.ini');
    
    // 設定備份檔案名稱
    $backupDir = $params['backup_dir'] ?? __DIR__ . '/../backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = sprintf(
        '%s/backup_%s.sql',
        rtrim($backupDir, '/'),
        date('Y-m-d_His')
    );
    
    // 執行備份命令
    $command = sprintf(
        'mysqldump -h %s -u %s -p%s %s > %s',
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database'],
        $filename
    );
    
    exec($command, $output, $returnVar);
    
    if ($returnVar !== 0) {
        throw new Exception('資料庫備份失敗');
    }
    
    // 壓縮備份檔案
    $zip = new ZipArchive();
    $zipFile = $filename . '.zip';
    
    if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
        $zip->addFile($filename, basename($filename));
        $zip->close();
        unlink($filename);
    } else {
        throw new Exception('備份檔案壓縮失敗');
    }
    
    // 清理舊備份
    $maxBackups = $params['max_backups'] ?? 10;
    cleanupOldBackups($backupDir, $maxBackups);
}

/**
 * 執行檔案清理任務
 */
function executeCleanupTask($params) {
    $targetDir = $params['target_dir'] ?? sys_get_temp_dir();
    $pattern = $params['pattern'] ?? '*';
    $maxAge = $params['max_age'] ?? 7;
    
    if (!file_exists($targetDir)) {
        throw new Exception('目標目錄不存在');
    }
    
    $files = glob($targetDir . '/' . $pattern);
    $now = time();
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileAge = floor(($now - filemtime($file)) / 86400);
            if ($fileAge >= $maxAge) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
    }
    
    if ($deleted === 0) {
        throw new Exception('沒有符合清理條件的檔案');
    }
}

/**
 * 執行報表生成任務
 */
function executeReportTask($params) {
    global $db;
    
    $reportType = $params['report_type'] ?? '';
    $format = $params['format'] ?? 'csv';
    $outputDir = $params['output_dir'] ?? __DIR__ . '/../reports';
    
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // 根據報表類型執行不同的 SQL 查詢
    switch ($reportType) {
        case 'daily_stats':
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count FROM table_name GROUP BY DATE(created_at)";
            break;
        case 'user_activity':
            $sql = "SELECT user_id, COUNT(*) as activity_count FROM activity_logs GROUP BY user_id";
            break;
        default:
            throw new Exception('不支援的報表類型');
    }
    
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        throw new Exception('沒有資料可供產生報表');
    }
    
    $filename = sprintf(
        '%s/%s_report_%s.%s',
        rtrim($outputDir, '/'),
        $reportType,
        date('Y-m-d_His'),
        $format
    );
    
    switch ($format) {
        case 'csv':
            exportToCsv($data, $filename);
            break;
        case 'json':
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
            break;
        default:
            throw new Exception('不支援的報表格式');
    }
}

/**
 * 執行通知發送任務
 */
function executeNotificationTask($params) {
    $type = $params['type'] ?? '';
    $recipients = $params['recipients'] ?? [];
    $message = $params['message'] ?? '';
    
    if (empty($type) || empty($recipients) || empty($message)) {
        throw new Exception('通知參數不完整');
    }
    
    switch ($type) {
        case 'email':
            foreach ($recipients as $recipient) {
                sendEmail($recipient, '系統通知', $message);
            }
            break;
        case 'slack':
            sendSlackNotification($params['webhook_url'], $message);
            break;
        default:
            throw new Exception('不支援的通知類型');
    }
}

/**
 * 清理舊備份檔案
 */
function cleanupOldBackups($backupDir, $maxBackups) {
    $files = glob($backupDir . '/backup_*.zip');
    
    if (count($files) <= $maxBackups) {
        return;
    }
    
    // 按修改時間排序
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // 刪除多餘的備份
    $filesToDelete = array_slice($files, $maxBackups);
    foreach ($filesToDelete as $file) {
        unlink($file);
    }
}

/**
 * 匯出資料為 CSV 格式
 */
function exportToCsv($data, $filename) {
    if (empty($data)) {
        throw new Exception('沒有資料可供匯出');
    }
    
    $fp = fopen($filename, 'w');
    
    // 寫入標題列
    fputcsv($fp, array_keys($data[0]));
    
    // 寫入資料列
    foreach ($data as $row) {
        fputcsv($fp, $row);
    }
    
    fclose($fp);
}

/**
 * 發送 Email
 */
function sendEmail($to, $subject, $message) {
    $headers = [
        'From' => SITE_NAME . ' <' . ADMIN_EMAIL . '>',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    if (!mail($to, $subject, $message, $headers)) {
        throw new Exception('郵件發送失敗');
    }
}

/**
 * 發送 Slack 通知
 */
function sendSlackNotification($webhookUrl, $message) {
    $data = json_encode(['text' => $message]);
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Slack 通知發送失敗');
    }
} 