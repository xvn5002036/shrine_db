<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 初始化變數
$error = '';
$success = false;

try {
    // 獲取活動類型列表
    $stmt = $pdo->query("SELECT id, name FROM event_types WHERE status = 'active' ORDER BY sort_order");
    $event_types = $stmt->fetchAll();

    // 處理表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $event_type_id = trim($_POST['event_type_id'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $registration_deadline_date = trim($_POST['registration_deadline_date'] ?? '');
        $registration_deadline_time = trim($_POST['registration_deadline_time'] ?? '');
        $max_participants = trim($_POST['max_participants'] ?? '');
        
        // 驗證
        if (empty($title)) {
            $error = '請輸入活動標題';
        } elseif (empty($event_type_id)) {
            $error = '請選擇活動類型';
        } elseif (empty($description)) {
            $error = '請輸入活動說明';
        } elseif (empty($location)) {
            $error = '請輸入活動地點';
        } elseif (empty($start_date) || empty($start_time)) {
            $error = '請輸入開始日期和時間';
        } elseif (empty($end_date) || empty($end_time)) {
            $error = '請輸入結束日期和時間';
        } else {
            try {
                // 處理圖片上傳
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($_FILES['image']['type'], $allowed_types)) {
                        throw new Exception('只允許上傳 JPG、PNG 或 GIF 圖片');
                    }
                    
                    if ($_FILES['image']['size'] > $max_size) {
                        throw new Exception('圖片大小不能超過 5MB');
                    }
                    
                    $upload_dir = '../../uploads/events/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $image_path = 'uploads/events/' . $filename;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                        throw new Exception('圖片上傳失敗');
                    }
                }
                
                // 組合日期時間
                $start_datetime = $start_date . ' ' . $start_time;
                $end_datetime = $end_date . ' ' . $end_time;
                $registration_deadline = $registration_deadline_date . ' ' . $registration_deadline_time;
                
                // 新增活動
                $stmt = $pdo->prepare("
                    INSERT INTO events (
                        event_type_id, title, description, image, location,
                        start_date, end_date, registration_deadline,
                        max_participants, created_by
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                
                $stmt->execute([
                    $event_type_id,
                    $title,
                    $description,
                    $image_path,
                    $location,
                    $start_datetime,
                    $end_datetime,
                    $registration_deadline,
                    $max_participants ?: null,
                    $_SESSION['admin_id']
                ]);
                
                $success = true;
                
            } catch (Exception $e) {
                error_log('Error adding event: ' . $e->getMessage());
                $error = '新增活動時發生錯誤：' . $e->getMessage();
                
                // 如果上傳失敗，刪除已上傳的圖片
                if (isset($image_path) && file_exists('../../' . $image_path)) {
                    unlink('../../' . $image_path);
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('Error: ' . $e->getMessage());
    $error = '系統錯誤：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增活動 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f4f6f9;
        }

        .page-header {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            margin-bottom: 0;
            padding: 0;
        }

        .btn-toolbar {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="toolbar">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">首頁</a></li>
                        <li class="breadcrumb-item"><a href="index.php">活動管理</a></li>
                        <li class="breadcrumb-item active">新增活動</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="add.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="title" class="form-label">活動標題 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="event_type_id" class="form-label">活動類型 <span class="text-danger">*</span></label>
                            <select class="form-select" id="event_type_id" name="event_type_id" required>
                                <option value="">請選擇活動類型</option>
                                <?php foreach ($event_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date" class="form-label">開始時間 <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date" class="form-label">結束時間 <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="location" class="form-label">活動地點 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" required>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_participants" class="form-label">人數上限</label>
                            <input type="number" class="form-control" id="max_participants" name="max_participants" min="0">
                            <div class="form-text">若不設定則表示不限人數</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status" class="form-label">活動狀態 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="進行中">進行中</option>
                                <option value="已結束">已結束</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">活動說明 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label for="image" class="form-label">活動圖片</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>

                <hr>

                <div class="btn-toolbar justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存活動
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // 表單驗證
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // 日期時間驗證
    document.getElementById('end_date').addEventListener('change', function() {
        var startDate = document.getElementById('start_date').value;
        var endDate = this.value;
        
        if (startDate && endDate && startDate > endDate) {
            alert('結束時間不能早於開始時間');
            this.value = '';
        }
    });
    </script>
</body>
</html> 
