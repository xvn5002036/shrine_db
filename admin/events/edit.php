<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

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
        $status = isset($_POST['status']) ? 1 : 0;
        
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
                
                $success = true;
                
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
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-main {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            background-color: #f4f6f9;
        }

        .content {
            padding: 20px;
            margin-top: 60px;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .datetime-group {
            display: flex;
            gap: 10px;
        }

        .datetime-group .form-control {
            flex: 1;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: #4a90e2;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 300px;
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .content {
                padding: 10px;
            }

            .datetime-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h2>編輯活動</h2>
                    <nav class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <a href="index.php">活動管理</a> /
                        <span>編輯活動</span>
                    </nav>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">活動更新成功！</div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">活動標題</label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($event['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="event_type_id">活動類型</label>
                            <select id="event_type_id" name="event_type_id" class="form-control" required>
                                <option value="">選擇活動類型</option>
                                <?php foreach ($event_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" 
                                            <?php echo $event['event_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">活動說明</label>
                            <textarea id="description" name="description" class="form-control" 
                                      rows="6" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">活動圖片</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <?php if ($event['image']): ?>
                                <div class="image-preview">
                                    <img src="../../<?php echo htmlspecialchars($event['image']); ?>" alt="活動圖片">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">活動地點</label>
                            <input type="text" id="location" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>活動開始時間</label>
                            <div class="datetime-group">
                                <input type="date" name="start_date" class="form-control" 
                                       value="<?php echo date('Y-m-d', strtotime($event['start_date'])); ?>" required>
                                <input type="time" name="start_time" class="form-control" 
                                       value="<?php echo date('H:i', strtotime($event['start_date'])); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>活動結束時間</label>
                            <div class="datetime-group">
                                <input type="date" name="end_date" class="form-control" 
                                       value="<?php echo date('Y-m-d', strtotime($event['end_date'])); ?>" required>
                                <input type="time" name="end_time" class="form-control" 
                                       value="<?php echo date('H:i', strtotime($event['end_date'])); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>報名截止時間</label>
                            <div class="datetime-group">
                                <input type="date" name="registration_deadline_date" class="form-control" 
                                       value="<?php echo $event['registration_deadline'] ? date('Y-m-d', strtotime($event['registration_deadline'])) : ''; ?>">
                                <input type="time" name="registration_deadline_time" class="form-control" 
                                       value="<?php echo $event['registration_deadline'] ? date('H:i', strtotime($event['registration_deadline'])) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_participants">活動名額</label>
                            <input type="number" id="max_participants" name="max_participants" class="form-control" 
                                   min="0" step="1" placeholder="不限制請留空"
                                   value="<?php echo $event['max_participants']; ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" value="1" 
                                       <?php echo $event['status'] ? 'checked' : ''; ?>>
                                活動進行中
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 儲存變更
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> 取消
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // 圖片預覽
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        document.getElementById('image').parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `<img src="${e.target.result}" alt="預覽圖片">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // 表單驗證
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