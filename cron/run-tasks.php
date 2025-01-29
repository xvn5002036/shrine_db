<?php
/**
 * 自動化任務排程執行器
 * 
 * 建議設定 crontab 每分鐘執行一次：
 * * * * * * php /path/to/your/project/cron/run-tasks.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/automation.php';

// 設定執行時區
date_default_timezone_set('Asia/Taipei');

// 檢查是否已經在執行中
$lockFile = __DIR__ . '/run-tasks.lock';
if (file_exists($lockFile)) {
    $pid = trim(file_get_contents($lockFile));
    if (posix_kill($pid, 0)) {
        exit("排程執行器已在執行中 (PID: $pid)\n");
    }
}

// 建立鎖定檔案
file_put_contents($lockFile, getmypid());

try {
    // 獲取所有啟用的任務
    $stmt = $db->prepare("
        SELECT * FROM automation_tasks 
        WHERE status = 'active' 
        AND is_running = 0
        AND (last_run IS NULL OR last_run <= DATE_SUB(NOW(), INTERVAL 1 MINUTE))
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll();

    foreach ($tasks as $task) {
        // 檢查是否符合執行時間
        if (!shouldRunTask($task['schedule'])) {
            continue;
        }

        try {
            // 更新任務狀態
            $stmt = $db->prepare("
                UPDATE automation_tasks 
                SET is_running = 1, last_run = NOW(), updated_at = NOW()
                WHERE id = ? AND is_running = 0
            ");
            $stmt->execute([$task['id']]);

            if ($stmt->rowCount() === 0) {
                continue; // 任務可能已被其他程序執行
            }

            // 執行任務
            $params = json_decode($task['params'], true) ?? [];
            executeAutomationTask($task['type'], $params, $task['id']);

        } catch (Exception $e) {
            // 記錄錯誤日誌
            $message = sprintf(
                '任務 #%d (%s) 執行失敗：%s',
                $task['id'],
                $task['name'],
                $e->getMessage()
            );
            
            $stmt = $db->prepare("INSERT INTO automation_logs (task_id, level, message, created_at) VALUES (?, 'error', ?, NOW())");
            $stmt->execute([$task['id'], $message]);

            // 更新任務狀態
            $stmt = $db->prepare("
                UPDATE automation_tasks 
                SET is_running = 0, last_error = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$e->getMessage(), $task['id']]);
        }
    }
} catch (Exception $e) {
    error_log('排程執行器錯誤：' . $e->getMessage());
} finally {
    // 清理鎖定檔案
    unlink($lockFile);
}

/**
 * 檢查是否符合執行時間
 */
function shouldRunTask($schedule) {
    $parts = explode(' ', trim($schedule));
    if (count($parts) !== 5) {
        return false;
    }

    list($minute, $hour, $day, $month, $weekday) = $parts;
    $now = time();
    $date = getdate($now);

    return matchCronPart($minute, $date['minutes']) &&
           matchCronPart($hour, $date['hours']) &&
           matchCronPart($day, $date['mday']) &&
           matchCronPart($month, $date['mon']) &&
           matchCronPart($weekday, $date['wday']);
}

/**
 * 檢查 Cron 表達式的單一部分是否符合
 */
function matchCronPart($pattern, $value) {
    // 處理星號
    if ($pattern === '*') {
        return true;
    }

    // 處理列表
    if (strpos($pattern, ',') !== false) {
        $parts = explode(',', $pattern);
        foreach ($parts as $part) {
            if (matchCronPart($part, $value)) {
                return true;
            }
        }
        return false;
    }

    // 處理範圍
    if (strpos($pattern, '-') !== false) {
        list($start, $end) = explode('-', $pattern);
        return $value >= $start && $value <= $end;
    }

    // 處理步進
    if (strpos($pattern, '/') !== false) {
        list($range, $step) = explode('/', $pattern);
        if ($range === '*') {
            return $value % $step === 0;
        }
        if (strpos($range, '-') !== false) {
            list($start, $end) = explode('-', $range);
            return $value >= $start && $value <= $end && ($value - $start) % $step === 0;
        }
    }

    // 處理固定值
    return (int)$pattern === (int)$value;
} 