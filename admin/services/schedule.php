<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 確保用戶已登入且有權限
checkAdminAuth();

// 初始化變數
$service = null;
$schedules = [];
$error = null;
$success = null;

// 檢查是否有提供ID
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

// 獲取服務資料
try {
    $stmt = $pdo->prepare("SELECT s.*, t.name as type_name 
                          FROM services s 
                          JOIN service_types t ON s.type_id = t.id 
                          WHERE s.id = ? AND s.status = 1");
    if ($stmt->execute([$_GET['id']])) {
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$service || !$service['booking_required']) {
            header('Location: index.php');
            exit;
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '無法獲取服務資料';
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 開始事務
        $pdo->beginTransaction();

        // 刪除現有時段
        $stmt = $pdo->prepare("DELETE FROM service_schedules WHERE service_id = ?");
        $stmt->execute([$_GET['id']]);

        // 插入新時段
        if (!empty($_POST['schedules'])) {
            $stmt = $pdo->prepare("INSERT INTO service_schedules 
                                 (service_id, day_of_week, start_time, end_time, max_bookings, status) 
                                 VALUES (?, ?, ?, ?, ?, 1)");

            foreach ($_POST['schedules'] as $schedule) {
                if (empty($schedule['start_time']) || empty($schedule['end_time'])) {
                    continue;
                }
                $stmt->execute([
                    $_GET['id'],
                    $schedule['day_of_week'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                    !empty($schedule['max_bookings']) ? $schedule['max_bookings'] : null
                ]);
            }
        }

        // 提交事務
        $pdo->commit();
        $success = '時段設定已更新';
    } catch (Exception $e) {
        // 回滾事務
        $pdo->rollBack();
        error_log($e->getMessage());
        $error = '更新失敗，請稍後再試';
    }
}

// 獲取現有時段設定
try {
    $stmt = $pdo->prepare("SELECT * FROM service_schedules WHERE service_id = ? AND status = 1 ORDER BY day_of_week, start_time");
    if ($stmt->execute([$_GET['id']])) {
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '無法獲取時段設定';
}

// 頁面標題
$page_title = '時段設定 - ' . $service['name'];
require_once '../includes/header.php';

// 星期幾的選項
$weekdays = [
    0 => '星期日',
    1 => '星期一',
    2 => '星期二',
    3 => '星期三',
    4 => '星期四',
    5 => '星期五',
    6 => '星期六'
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" id="scheduleForm">
                <div class="schedule-container">
                    <?php foreach ($weekdays as $day_num => $day_name): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($day_name); ?></h5>
                                    <button type="button" class="btn btn-sm btn-outline-primary add-time-slot" 
                                            data-day="<?php echo $day_num; ?>">
                                        <i class="fas fa-plus"></i> 新增時段
                                    </button>
                                </div>
                            </div>
                            <div class="card-body time-slots" data-day="<?php echo $day_num; ?>">
                                <?php
                                $day_schedules = array_filter($schedules, function($schedule) use ($day_num) {
                                    return $schedule['day_of_week'] == $day_num;
                                });
                                if (!empty($day_schedules)):
                                    foreach ($day_schedules as $schedule):
                                ?>
                                    <div class="row mb-2 time-slot">
                                        <input type="hidden" name="schedules[][day_of_week]" 
                                               value="<?php echo $day_num; ?>">
                                        <div class="col-md-4">
                                            <label class="form-label">開始時間</label>
                                            <input type="time" class="form-control" 
                                                   name="schedules[][start_time]"
                                                   value="<?php echo $schedule['start_time']; ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">結束時間</label>
                                            <input type="time" class="form-control" 
                                                   name="schedules[][end_time]"
                                                   value="<?php echo $schedule['end_time']; ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">最大預約數</label>
                                            <input type="number" class="form-control" 
                                                   name="schedules[][max_bookings]"
                                                   value="<?php echo $schedule['max_bookings']; ?>" 
                                                   min="1">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger remove-time-slot">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存設定
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 時段模板 -->
<template id="timeSlotTemplate">
    <div class="row mb-2 time-slot">
        <input type="hidden" name="schedules[][day_of_week]" value="">
        <div class="col-md-4">
            <label class="form-label">開始時間</label>
            <input type="time" class="form-control" name="schedules[][start_time]" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">結束時間</label>
            <input type="time" class="form-control" name="schedules[][end_time]" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">最大預約數</label>
            <input type="number" class="form-control" name="schedules[][max_bookings]" min="1">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger remove-time-slot">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const template = document.getElementById('timeSlotTemplate');
    
    // 新增時段
    document.querySelectorAll('.add-time-slot').forEach(button => {
        button.addEventListener('click', function() {
            const day = this.dataset.day;
            const container = document.querySelector(`.time-slots[data-day="${day}"]`);
            const clone = template.content.cloneNode(true);
            
            // 設定星期幾
            clone.querySelector('input[name="schedules[][day_of_week]"]').value = day;
            
            // 綁定刪除按鈕事件
            clone.querySelector('.remove-time-slot').addEventListener('click', function() {
                this.closest('.time-slot').remove();
            });
            
            container.appendChild(clone);
        });
    });
    
    // 刪除時段
    document.querySelectorAll('.remove-time-slot').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.time-slot').remove();
        });
    });
    
    // 表單驗證
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        const timeSlots = document.querySelectorAll('.time-slot');
        timeSlots.forEach(slot => {
            const startTime = slot.querySelector('input[name="schedules[][start_time]"]').value;
            const endTime = slot.querySelector('input[name="schedules[][end_time]"]').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('結束時間必須晚於開始時間');
                return;
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 