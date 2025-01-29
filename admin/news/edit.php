<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否登入
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    setFlashMessage('error', '請先登入');
    header('Location: /admin/login.php');
    exit;
}

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', '未指定新聞 ID');
    header('Location: /admin/news/index.php');
    exit;
}

$id = (int)$_GET['id'];

// Debug 資訊
error_log("Accessing edit.php with ID: " . $id);

// 獲取新聞資料
try {
    // 先檢查新聞是否存在
    $check_sql = "SELECT COUNT(*) FROM news WHERE id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $id]);
    
    if ($check_stmt->fetchColumn() == 0) {
        setFlashMessage('error', '找不到指定的新聞');
        header('Location: /admin/news/index.php');
        exit;
    }

    // 獲取新聞詳細資料
    $sql = "
        SELECT n.* 
        FROM news n 
        WHERE n.id = :id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug 資訊
    error_log("Found news: " . print_r($news, true));

    // 獲取新聞分類列表
    $categories_sql = "SELECT id, name FROM news_categories ORDER BY name";
    $categories = $pdo->query($categories_sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error in edit.php: ' . $e->getMessage());
    error_log('SQL State: ' . $e->errorInfo[0]);
    error_log('Error Code: ' . $e->errorInfo[1]);
    error_log('Error Message: ' . $e->errorInfo[2]);
    
    setFlashMessage('error', '系統錯誤，請稍後再試');
    header('Location: /admin/news/index.php');
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 開始交易
        $pdo->beginTransaction();

        // 準備更新數據
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = trim($_POST['status'] ?? 'draft');
        $publish_date = trim($_POST['publish_date'] ?? date('Y-m-d H:i:s'));
        $category_id = (int)($_POST['category_id'] ?? 0);

        // Debug 資訊
        error_log("POST data: " . print_r($_POST, true));

        // 驗證必填欄位
        if (empty($title) || empty($content)) {
            throw new Exception('標題和內容為必填項目');
        }

        // 處理圖片上傳
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/news/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('只允許上傳 JPG、JPEG、PNG 或 GIF 格式的圖片');
            }

            $new_filename = uniqid('news_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                throw new Exception('圖片上傳失敗');
            }

            $image_path = 'uploads/news/' . $new_filename;

            // 刪除舊圖片
            $stmt = $pdo->prepare("SELECT image FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $old_image = $stmt->fetchColumn();

            if ($old_image && file_exists('../../' . $old_image)) {
                unlink('../../' . $old_image);
            }
        }

        // 更新新聞
        $sql = "
            UPDATE news 
            SET title = :title,
                content = :content,
                status = :status,
                publish_date = :publish_date,
                category_id = :category_id,
                updated_at = NOW(),
                updated_by = :updated_by
        ";

        if ($image_path !== null) {
            $sql .= ", image = :image";
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':title' => $title,
            ':content' => $content,
            ':status' => $status,
            ':publish_date' => $publish_date,
            ':category_id' => $category_id,
            ':updated_by' => $_SESSION['admin_id'],
            ':id' => $id
        ];

        if ($image_path !== null) {
            $params[':image'] = $image_path;
        }

        $stmt->execute($params);

        // 記錄管理員操作
        logAdminAction('update_news', "更新新聞 ID: {$id}");

        // 提交交易
        $pdo->commit();

        setFlashMessage('success', '新聞更新成功');
        header('Location: /admin/news/index.php');
        exit;

    } catch (Exception $e) {
        // 回滾交易
        $pdo->rollBack();
        error_log('Error updating news: ' . $e->getMessage());
        setFlashMessage('error', '更新失敗：' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯新聞 - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic forecolor backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 16px; }',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-lang/langs/zh_TW.js',
            language: 'zh_TW'
        });
    </script>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>編輯新聞</h2>
                    <div class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <a href="index.php">新聞管理</a> /
                        <span>編輯新聞</span>
                    </div>
                </div>

                <div class="content-body">
                    <div class="card">
                        <div class="card-header">
                            <h3>編輯新聞內容</h3>
                        </div>
                        <div class="card-body">
                            <?php display_flash_messages(); ?>

                            <form action="edit.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" class="form">
                                <div class="form-group">
                                    <label for="title">標題 <span class="required">*</span></label>
                                    <input type="text" id="title" name="title" required
                                           value="<?php echo htmlspecialchars($news['title']); ?>"
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="category_id">分類</label>
                                    <select id="category_id" name="category_id" class="form-control">
                                        <option value="0">-- 請選擇分類 --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"
                                                    <?php echo $category['id'] == $news['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="content">內容 <span class="required">*</span></label>
                                    <textarea id="content" name="content" required
                                              class="form-control"><?php echo htmlspecialchars($news['content']); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="image">圖片</label>
                                    <?php if (!empty($news['image'])): ?>
                                        <div class="current-image">
                                            <img src="../../<?php echo htmlspecialchars($news['image']); ?>" 
                                                 alt="當前圖片" style="max-width: 200px;">
                                            <p>當前圖片</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="image" name="image" accept="image/*" class="form-control">
                                    <small class="form-text text-muted">
                                        支援的格式：JPG、JPEG、PNG、GIF。若不上傳新圖片則保留原圖。
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="status">狀態</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="draft" <?php echo $news['status'] === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                        <option value="published" <?php echo $news['status'] === 'published' ? 'selected' : ''; ?>>發布</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="publish_date">發布日期</label>
                                    <input type="datetime-local" id="publish_date" name="publish_date"
                                           value="<?php echo date('Y-m-d\TH:i', strtotime($news['publish_date'] ?? 'now')); ?>"
                                           class="form-control">
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 儲存
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> 取消
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 
