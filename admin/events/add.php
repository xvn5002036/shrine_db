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
        $end_date = trim($_POST['end_date'] ?? '');
        $max_participants = trim($_POST['max_participants'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        
        // 驗證
        if (empty($title)) {
            $error = '請輸入活動標題';
        } elseif (empty($event_type_id)) {
            $error = '請選擇活動類型';
        } elseif (empty($description)) {
            $error = '請輸入活動說明';
        } elseif (empty($location)) {
            $error = '請輸入活動地點';
        } elseif (empty($start_date)) {
            $error = '請輸入開始時間';
        } elseif (empty($end_date)) {
            $error = '請輸入結束時間';
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
                    
                    // 確保上傳目錄存在
                    $upload_dir = '../../uploads/events/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            throw new Exception('無法創建上傳目錄');
                        }
                    }
                    
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $image_path = 'uploads/events/' . $filename;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                        throw new Exception('圖片上傳失敗');
                    }
                }
                
                // 新增活動
                $stmt = $pdo->prepare("
                    INSERT INTO events (
                        event_type_id, 
                        title, 
                        description, 
                        image, 
                        location,
                        start_date, 
                        end_date, 
                        max_participants, 
                        status,
                        created_by,
                        created_at,
                        updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )
                ");
                
                $stmt->execute([
                    $event_type_id,
                    $title,
                    $description,
                    $image_path,
                    $location,
                    $start_date,
                    $end_date,
                    $max_participants ?: null,
                    $status,
                    $_SESSION['admin_id']
                ]);
                
                $_SESSION['success'] = '活動新增成功！';
                header('Location: index.php');
                exit;
                
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
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php require_once '../includes/header.php'; ?>
        
        <main class="admin-main">
            <div class="container-fluid">
                <div class="page-header">
                    <div class="toolbar">
                        <h1><i class="fas fa-calendar-plus"></i> 新增活動</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">首頁</a></li>
                                <li class="breadcrumb-item"><a href="index.php">活動管理</a></li>
                                <li class="breadcrumb-item active">新增活動</li>
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

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">活動標題 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                        <div class="invalid-feedback">請輸入活動標題</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="event_type_id" class="form-label">活動類型 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="event_type_id" name="event_type_id" required>
                                            <option value="">請選擇活動類型</option>
                                            <?php foreach ($event_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>">
                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">請選擇活動類型</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">活動說明 <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                <div class="invalid-feedback">請輸入活動說明</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">開始時間 <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                        <div class="invalid-feedback">請選擇開始時間</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">結束時間 <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                        <div class="invalid-feedback">請選擇結束時間</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">活動地點 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" required>
                                <div class="invalid-feedback">請輸入活動地點</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_participants" class="form-label">活動名額</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="0">
                                        <div class="form-text">不填寫表示不限制人數</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">活動狀態</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft">草稿</option>
                                            <option value="published">發布</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">活動圖片</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">支援 JPG、PNG、GIF 格式，檔案大小不超過 5MB</div>
                            </div>

                            <div class="text-end">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 取消
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 儲存活動
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

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

    // 圖片預覽
    document.getElementById('image').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const fileSize = file.size / 1024 / 1024; // Convert to MB
            
            if (fileSize > 5) {
                alert('圖片大小不能超過 5MB');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.createElement('div');
                preview.className = 'mt-2';
                preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">`;
                
                const existingPreview = document.querySelector('.img-thumbnail');
                if (existingPreview) {
                    existingPreview.parentElement.remove();
                }
                
                document.getElementById('image').parentElement.appendChild(preview);
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html> 
