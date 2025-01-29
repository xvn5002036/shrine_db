<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 檢查管理員登入狀態
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 獲取自動化任務列表
$stmt = $db->query("SELECT * FROM automation_tasks ORDER BY last_run DESC");
$tasks = $stmt->fetchAll();

// 獲取最近的日誌記錄
$stmt = $db->query("SELECT * FROM automation_logs ORDER BY created_at DESC LIMIT 10");
$logs = $stmt->fetchAll();

// 獲取系統統計資料
$stmt = $db->query("SELECT COUNT(*) as total_tasks FROM automation_tasks");
$total_tasks = $stmt->fetch()['total_tasks'];

$stmt = $db->query("SELECT COUNT(*) as active_tasks FROM automation_tasks WHERE status = 'active'");
$active_tasks = $stmt->fetch()['active_tasks'];

$stmt = $db->query("SELECT COUNT(*) as failed_tasks FROM automation_logs WHERE level = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$failed_tasks = $stmt->fetch()['failed_tasks'];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 自動化管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-components.css">
    <link rel="stylesheet" href="../assets/css/admin-automation.css">
</head>
<body class="admin">
    <?php include 'includes/admin-header.php'; ?>
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h1>自動化管理</h1>
            <div class="header-actions">
                <button class="btn btn-primary" data-modal="add-task">新增任務</button>
                <button class="btn btn-secondary" onclick="runAllTasks()">執行所有任務</button>
            </div>
        </div>

        <!-- 統計資訊 -->
        <div class="automation-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_tasks; ?></div>
                <div class="stat-label">總任務數</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $active_tasks; ?></div>
                <div class="stat-label">執行中任務</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $failed_tasks; ?></div>
                <div class="stat-label">24小時內失敗任務</div>
            </div>
        </div>

        <!-- 任務列表 -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">排程任務列表</h2>
            </div>
            <div class="card-body">
                <div class="schedule-list">
                    <?php foreach ($tasks as $task): ?>
                        <div class="schedule-item">
                            <div class="schedule-info">
                                <div class="schedule-name"><?php echo htmlspecialchars($task['name']); ?></div>
                                <div class="schedule-time">
                                    上次執行：<?php echo $task['last_run'] ? date('Y-m-d H:i:s', strtotime($task['last_run'])) : '從未執行'; ?>
                                </div>
                            </div>
                            <div class="schedule-status <?php echo $task['status']; ?>">
                                <?php echo $task['status'] === 'active' ? '執行中' : '已停止'; ?>
                            </div>
                            <div class="schedule-actions">
                                <button class="btn btn-sm" onclick="toggleTask(<?php echo $task['id']; ?>)">
                                    <?php echo $task['status'] === 'active' ? '停止' : '啟動'; ?>
                                </button>
                                <button class="btn btn-sm" onclick="runTask(<?php echo $task['id']; ?>)">立即執行</button>
                                <button class="btn btn-sm" onclick="editTask(<?php echo $task['id']; ?>)">編輯</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 自動化日誌 -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">執行日誌</h2>
                <a href="automation-logs.php" class="btn btn-sm">查看全部</a>
            </div>
            <div class="card-body">
                <div class="automation-logs">
                    <?php foreach ($logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></div>
                            <span class="log-level <?php echo $log['level']; ?>"><?php echo strtoupper($log['level']); ?></span>
                            <div class="log-message"><?php echo htmlspecialchars($log['message']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- 新增任務模態框 -->
    <div class="modal" id="add-task-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">新增自動化任務</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="task-form" class="automation-form">
                    <div class="form-group">
                        <label for="task-name">任務名稱</label>
                        <input type="text" id="task-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="task-type">任務類型</label>
                        <select id="task-type" name="type" class="form-control" required>
                            <option value="backup">資料庫備份</option>
                            <option value="cleanup">清理暫存檔</option>
                            <option value="report">生成報表</option>
                            <option value="notification">發送通知</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="task-schedule">執行排程</label>
                        <input type="text" id="task-schedule" name="schedule" class="form-control" placeholder="*/5 * * * *" required>
                        <div class="setting-description">使用 Cron 表達式設定排程時間</div>
                    </div>
                    <div class="form-group">
                        <label for="task-params">參數設定</label>
                        <textarea id="task-params" name="params" class="form-control" rows="4" placeholder="{}">{}</textarea>
                        <div class="setting-description">使用 JSON 格式設定任務參數</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button class="btn btn-primary" onclick="saveTask()">儲存</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // 切換任務狀態
        function toggleTask(taskId) {
            fetch('api/toggle-task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ taskId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '操作失敗');
                }
            });
        }

        // 立即執行任務
        function runTask(taskId) {
            fetch('api/run-task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ taskId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('任務已開始執行');
                } else {
                    alert(data.message || '執行失敗');
                }
            });
        }

        // 執行所有任務
        function runAllTasks() {
            if (!confirm('確定要執行所有任務嗎？')) return;
            
            fetch('api/run-all-tasks.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('所有任務已開始執行');
                } else {
                    alert(data.message || '執行失敗');
                }
            });
        }

        // 儲存任務
        function saveTask() {
            const form = document.getElementById('task-form');
            const formData = new FormData(form);
            
            fetch('api/save-task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '儲存失敗');
                }
            });
        }
    </script>
</body>
</html> 