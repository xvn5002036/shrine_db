<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取所有分類
try {
    $stmt = $pdo->query("SELECT * FROM news_categories WHERE status = 'active' ORDER BY sort_order");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
    $categories = [];
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $publish_date = $_POST['publish_date'] ?? date('Y-m-d H:i:s');
    $errors = [];

    // 驗證表單
    if (empty($title)) {
        $errors[] = '標題不能為空';
    }
    if (empty($content)) {
        $errors[] = '內容不能為空';
    }
    if ($category_id <= 0) {
        $errors[] = '請選擇分類';
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
            $upload_dir = '../../uploads/news/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $image_path = 'uploads/news/' . $filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $errors[] = '圖片上傳失敗';
            }
        }
    }

    // 如果沒有錯誤，保存新聞
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO news (title, content, image, status, category_id, publish_date, created_by, created_at)
                VALUES (:title, :content, :image, :status, :category_id, :publish_date, :created_by, NOW())
            ");

            $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':image' => $image_path,
                ':status' => $status,
                ':category_id' => $category_id,
                ':publish_date' => $publish_date,
                ':created_by' => $_SESSION['admin_id']
            ]);

            // 記錄操作日誌
            logAdminAction('新增新聞', "新增新聞：{$title}");

            // 設置成功消息
            setFlashMessage('success', '新聞新增成功！');
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
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 新增新聞</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/27.1.0/classic/ckeditor.js"></script>
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
                    <h2>新增新聞</h2>
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
                            <label for="title">標題</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="content">內容</label>
                            <textarea id="content" name="content"><?php echo htmlspecialchars($content ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="image">圖片</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <small class="form-text">支援 JPG、PNG、GIF 格式，最大 5MB</small>
                        </div>

                        <div class="form-group">
                            <label for="category_id">分類</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">請選擇分類</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($category_id ?? 0) == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo ($status ?? '') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo ($status ?? '') === 'published' ? 'selected' : ''; ?>>發布</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="publish_date">發布時間</label>
                            <input type="text" id="publish_date" name="publish_date" value="<?php echo htmlspecialchars($publish_date ?? date('Y-m-d H:i:s')); ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">新增新聞</button>
                            <a href="index.php" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        // 初始化 CKEditor
        ClassicEditor
            .create(document.querySelector('#content'), {
                language: 'zh'
            })
            .catch(error => {
                console.error(error);
            });

        // 初始化 Flatpickr
        flatpickr("#publish_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            locale: "zh-tw"
        });
    </script>
</body>
</html> 