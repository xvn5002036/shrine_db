<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 設置頁面標題
$page_title = '編輯活動';

// 獲取活動 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// 初始化變數
$error = '';
$success = false;

try {
    // 獲取活動資料
    $stmt = $pdo->prepare("
        SELECT e.*, t.name as type_name 
        FROM events e 
        LEFT JOIN event_types t ON e.event_type_id = t.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: index.php');
        exit;
    }

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
        $status = isset($_POST['status']) ? 'draft' : 'published';
        
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
                $image_path = $event['image'];
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
                    $new_image_path = 'uploads/events/' . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                        // 如果上傳成功，刪除舊圖片
                        if ($image_path && file_exists('../../' . $image_path)) {
                            unlink('../../' . $image_path);
                        }
                        $image_path = $new_image_path;
                    }
                }
                
                // 組合日期時間
                $start_datetime = $start_date . ' ' . $start_time;
                $end_datetime = $end_date . ' ' . $end_time;
                $registration_deadline = $registration_deadline_date . ' ' . $registration_deadline_time;
                
                // 更新活動
                $stmt = $pdo->prepare("
                    UPDATE events SET 
                        event_type_id = ?,
                        title = ?,
                        description = ?,
                        image = ?,
                        location = ?,
                        start_date = ?,
                        end_date = ?,
                        registration_deadline = ?,
                        max_participants = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
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
                    $status,
                    $id
                ]);
                
                $_SESSION['success'] = '活動更新成功！' . ($status === 'published' ? '目前狀態：開放報名' : '目前狀態：關閉報名');
                
                // 重新獲取活動資料
                $stmt = $pdo->prepare("
                    SELECT e.*, t.name as type_name 
                    FROM events e 
                    LEFT JOIN event_types t ON e.event_type_id = t.id 
                    WHERE e.id = ?
                ");
                $stmt->execute([$id]);
                $event = $stmt->fetch();
                
            } catch (Exception $e) {
                error_log('Error updating event: ' . $e->getMessage());
                $error = '更新活動時發生錯誤：' . $e->getMessage();
                
                // 如果上傳失敗，刪除已上傳的新圖片
                if (isset($new_image_path) && file_exists('../../' . $new_image_path)) {
                    unlink('../../' . $new_image_path);
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
    <title>編輯活動 - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Admin Style -->
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .image-preview {
            margin-top: 10px;
            max-width: 300px;
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .datetime-group {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .datetime-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php require_once '../includes/header.php'; ?>
            <div class="container-fluid">
                <div class="page-header">
                    <div class="toolbar">
                        <h1><i class="fas fa-edit"></i> 編輯活動</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">首頁</a></li>
                                <li class="breadcrumb-item"><a href="index.php">活動管理</a></li>
                                <li class="breadcrumb-item active">編輯活動</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="title" class="form-label">活動標題 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($event['title']); ?>" required>
                                    <div class="invalid-feedback">請輸入活動標題</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="event_type_id" class="form-label">活動類型 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="event_type_id" name="event_type_id" required>
                                        <option value="">選擇活動類型</option>
                                        <?php foreach ($event_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>" 
                                                    <?php echo $event['event_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">請選擇活動類型</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">活動說明 <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                                <div class="invalid-feedback">請輸入活動說明</div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">活動地點 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($event['location']); ?>" required>
                                <div class="invalid-feedback">請輸入活動地點</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">活動開始時間 <span class="text-danger">*</span></label>
                                    <div class="datetime-group">
                                        <input type="date" name="start_date" class="form-control" 
                                               value="<?php echo date('Y-m-d', strtotime($event['start_date'])); ?>" required>
                                        <input type="time" name="start_time" class="form-control" 
                                               value="<?php echo date('H:i', strtotime($event['start_date'])); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">活動結束時間 <span class="text-danger">*</span></label>
                                    <div class="datetime-group">
                                        <input type="date" name="end_date" class="form-control" 
                                               value="<?php echo date('Y-m-d', strtotime($event['end_date'])); ?>" required>
                                        <input type="time" name="end_time" class="form-control" 
                                               value="<?php echo date('H:i', strtotime($event['end_date'])); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">報名截止時間</label>
                                    <div class="datetime-group">
                                        <input type="date" name="registration_deadline_date" class="form-control" 
                                               value="<?php echo $event['registration_deadline'] ? date('Y-m-d', strtotime($event['registration_deadline'])) : ''; ?>">
                                        <input type="time" name="registration_deadline_time" class="form-control" 
                                               value="<?php echo $event['registration_deadline'] ? date('H:i', strtotime($event['registration_deadline'])) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="max_participants" class="form-label">活動名額</label>
                                    <input type="number" class="form-control" id="max_participants" name="max_participants" 
                                           min="0" step="1" placeholder="不限制請留空"
                                           value="<?php echo $event['max_participants']; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">活動圖片</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">支援 JPG、PNG、GIF 格式，檔案大小不超過 5MB</div>
                                <?php if ($event['image']): ?>
                                    <div class="image-preview mt-2">
                                        <img src="../../<?php echo htmlspecialchars($event['image']); ?>" alt="活動圖片" class="img-thumbnail">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" 
                                           value="draft" <?php echo $event['status'] === 'draft' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">關閉報名</label>
                                </div>
                                <div class="form-text">
                                    目前狀態：<?php echo $event['status'] === 'published' ? '<span class="text-success">開放報名中</span>' : '<span class="text-danger">已關閉報名</span>'; ?>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 取消
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 儲存變更
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap Bundle with Popper -->
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

        // 圖片預覽
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview mt-2';
                        document.getElementById('image').parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `<img src="${e.target.result}" alt="預覽圖片" class="img-thumbnail">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // 日期時間驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value + ' ' + document.querySelector('[name="start_time"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value + ' ' + document.querySelector('[name="end_time"]').value);
            
            if (endDate < startDate) {
                e.preventDefault();
                alert('結束時間不能早於開始時間');
            }
        });
    </script>
</body>
</html> 