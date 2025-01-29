<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供活動 ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$event_id = (int)$_GET['id'];

// 獲取活動資訊
try {
    $stmt = $pdo->prepare("
        SELECT e.*, et.name as event_type_name 
        FROM events e 
        LEFT JOIN event_types et ON e.event_type_id = et.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching event: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $max_participants = (int)($_POST['max_participants'] ?? 0);
    $event_type_id = (int)($_POST['event_type_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $errors = [];

    // 驗證表單
    if (empty($title)) {
        $errors[] = '活動名稱不能為空';
    }
    if (empty($description)) {
        $errors[] = '活動描述不能為空';
    }
    if (empty($event_date)) {
        $errors[] = '活動日期不能為空';
    }
    if (empty($event_time)) {
        $errors[] = '活動時間不能為空';
    }
    if (empty($location)) {
        $errors[] = '活動地點不能為空';
    }

    // 處理圖片上傳
    $image_path = $event['image']; // 保留原有圖片路徑
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = '只允許上傳 JPG、PNG 或 GIF 格式的圖片';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = '圖片大小不能超過 5MB';
        } else {
            $upload_dir = '../../uploads/events/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // 刪除舊圖片
            if ($image_path && file_exists('../../' . $image_path)) {
                unlink('../../' . $image_path);
            }

            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $image_path = 'uploads/events/' . $filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $errors[] = '圖片上傳失敗';
            }
        }
    }

    // 如果沒有錯誤，更新活動
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE events SET 
                    title = :title,
                    description = :description,
                    event_date = :event_date,
                    event_time = :event_time,
                    location = :location,
                    max_participants = :max_participants,
                    event_type_id = :event_type_id,
                    image = :image,
                    status = :status,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':event_date' => $event_date,
                ':event_time' => $event_time,
                ':location' => $location,
                ':max_participants' => $max_participants,
                ':event_type_id' => $event_type_id ?: null,
                ':image' => $image_path,
                ':status' => $status,
                ':updated_by' => $_SESSION['admin_id'],
                ':id' => $event_id
            ]);

            // 更新成功，重定向到列表頁
            header('Location: index.php?success=1');
            exit;
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            $errors[] = '更新活動時發生錯誤';
        }
    }
}

// 獲取活動類型列表
try {
    $event_types = $pdo->query("SELECT * FROM event_types ORDER BY sort_order")->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching event types: " . $e->getMessage());
    $event_types = [];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 編輯活動</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/zh-tw.js"></script>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>編輯活動</h2>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="edit.php?id=<?php echo $event_id; ?>" method="post" enctype="multipart/form-data" class="form">
                        <div class="form-group">
                            <label for="title">活動名稱</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">活動描述</label>
                            <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="event_date">活動日期</label>
                                <input type="text" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="event_time">活動時間</label>
                                <input type="text" id="event_time" name="event_time" value="<?php echo $event['event_time']; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">活動地點</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="event_type_id">活動類型</label>
                            <select id="event_type_id" name="event_type_id">
                                <option value="">請選擇活動類型</option>
                                <?php foreach ($event_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $event['event_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="max_participants">參加人數上限</label>
                            <input type="number" id="max_participants" name="max_participants" 
                                   value="<?php echo $event['max_participants']; ?>" min="0">
                            <small class="form-text">設為 0 表示不限制人數</small>
                        </div>

                        <div class="form-group">
                            <label for="image">活動圖片</label>
                            <?php if ($event['image']): ?>
                                <div class="current-image">
                                    <img src="../../<?php echo htmlspecialchars($event['image']); ?>" alt="當前圖片" style="max-width: 200px;">
                                    <p>當前圖片</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="image" name="image" accept="image/*">
                            <small class="form-text">支援 JPG、PNG、GIF 格式，最大 5MB。不上傳則保留原圖</small>
                        </div>

                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo $event['status'] === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo $event['status'] === 'published' ? 'selected' : ''; ?>>發布</option>
                                <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>取消</option>
                                <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>已結束</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">更新活動</button>
                            <a href="index.php" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        // 初始化日期選擇器
        flatpickr("#event_date", {
            dateFormat: "Y-m-d",
            locale: "zh-tw"
        });

        // 初始化時間選擇器
        flatpickr("#event_time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            locale: "zh-tw"
        });
    </script>
</body>
</html> 