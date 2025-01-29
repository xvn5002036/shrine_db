<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $max_participants = (int)($_POST['max_participants'] ?? 0);
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
    $image_path = '';
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

            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $image_path = 'uploads/events/' . $filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $errors[] = '圖片上傳失敗';
            }
        }
    }

    // 如果沒有錯誤，保存活動
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO events (
                    title, description, event_date, event_time, location, 
                    max_participants, image, status, created_by, created_at
                ) VALUES (
                    :title, :description, :event_date, :event_time, :location,
                    :max_participants, :image, :status, :created_by, NOW()
                )
            ");

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':event_date' => $event_date,
                ':event_time' => $event_time,
                ':location' => $location,
                ':max_participants' => $max_participants,
                ':image' => $image_path,
                ':status' => $status,
                ':created_by' => $_SESSION['admin_id']
            ]);

            // 記錄操作日誌
            logAdminAction('新增活動', "新增活動：{$title}");

            // 設置成功消息
            setFlashMessage('success', '活動新增成功！');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = '保存失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 新增活動</title>
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
                    <h2>新增活動</h2>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="add.php" method="post" enctype="multipart/form-data" class="form">
                        <div class="form-group">
                            <label for="title">活動名稱</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">活動描述</label>
                            <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="event_date">活動日期</label>
                                <input type="text" id="event_date" name="event_date" value="<?php echo htmlspecialchars($event_date ?? ''); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="event_time">活動時間</label>
                                <input type="text" id="event_time" name="event_time" value="<?php echo htmlspecialchars($event_time ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location">活動地點</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="max_participants">參加人數上限</label>
                            <input type="number" id="max_participants" name="max_participants" 
                                   value="<?php echo htmlspecialchars($max_participants ?? '0'); ?>" min="0">
                            <small class="form-text">設為 0 表示不限制人數</small>
                        </div>

                        <div class="form-group">
                            <label for="image">活動圖片</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <small class="form-text">支援 JPG、PNG、GIF 格式，最大 5MB</small>
                        </div>

                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo ($status ?? '') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo ($status ?? '') === 'published' ? 'selected' : ''; ?>>發布</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">新增活動</button>
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