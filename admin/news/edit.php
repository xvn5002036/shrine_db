<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取新聞 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// 初始化變數
$error = '';
$success = false;

try {
    // 獲取新聞資料
    $stmt = $pdo->prepare("
        SELECT n.*, nc.name as category_name 
        FROM news n 
        LEFT JOIN news_categories nc ON n.category_id = nc.id 
        WHERE n.id = ?
    ");
    $stmt->execute([$id]);
    $news = $stmt->fetch();

    if (!$news) {
        header('Location: index.php');
        exit;
    }

    // 獲取新聞分類列表
    $stmt = $pdo->query("SELECT id, name FROM news_categories ORDER BY name");
    $categories = $stmt->fetchAll();

    // 處理表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $category_id = trim($_POST['category_id'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = trim($_POST['status'] ?? 'draft');
        
        // 驗證
        if (empty($title)) {
            $error = '請輸入標題';
        } elseif (empty($content)) {
            $error = '請輸入內容';
        } else {
            try {
                // 處理圖片上傳
                $image_path = $news['image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($_FILES['image']['type'], $allowed_types)) {
                        throw new Exception('只允許上傳 JPG、PNG 或 GIF 圖片');
                    }
                    
                    if ($_FILES['image']['size'] > $max_size) {
                        throw new Exception('圖片大小不能超過 5MB');
                    }
                    
                    $upload_dir = '../../uploads/news/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $new_image_path = 'uploads/news/' . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                        // 如果上傳成功，刪除舊圖片
                        if ($image_path && file_exists('../../' . $image_path)) {
                            unlink('../../' . $image_path);
                        }
                        $image_path = $new_image_path;
                    }
                }
                
                // 更新新聞
                $stmt = $pdo->prepare("
                    UPDATE news 
                    SET title = ?, category_id = ?, content = ?, image = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $title,
                    $category_id ?: null,
                    $content,
                    $image_path,
                    $status,
                    $id
                ]);
                
                $success = true;
                
                // 重新獲取新聞資料
                $stmt = $pdo->prepare("
                    SELECT n.*, nc.name as category_name 
                    FROM news n 
                    LEFT JOIN news_categories nc ON n.category_id = nc.id 
                    WHERE n.id = ?
                ");
                $stmt->execute([$id]);
                $news = $stmt->fetch();
                
            } catch (Exception $e) {
                error_log('Error updating news: ' . $e->getMessage());
                $error = '更新消息時發生錯誤：' . $e->getMessage();
                
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
    <title>編輯消息 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- 引入 CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/27.1.0/classic/ckeditor.js"></script>
    <style>
        /* 修正版面配置 */
        .admin-container {
            display: flex;
            min-height: 100vh;
            padding-left: 250px; /* 側邊欄寬度 */
            position: relative;
        }

        .admin-main {
            flex: 1;
            padding: 20px;
            margin-left: 0;
            width: calc(100% - 250px); /* 扣除側邊欄寬度 */
            box-sizing: border-box;
        }

        .content {
            padding: 20px;
            margin-top: 60px; /* 為頂部導航預留空間 */
        }

        /* 表單樣式 */
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

        .form-control:focus {
            border-color: #4a90e2;
            outline: none;
        }

        /* CKEditor 樣式修正 */
        .ck-editor__editable {
            min-height: 300px;
        }

        /* 圖片預覽 */
        .image-preview {
            margin-top: 10px;
            max-width: 300px;
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        /* 按鈕樣式 */
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
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #4a90e2;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
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

        /* 響應式設計 */
        @media (max-width: 768px) {
            .admin-container {
                padding-left: 0;
            }

            .admin-main {
                width: 100%;
                margin-left: 0;
            }

            .content {
                padding: 10px;
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
                    <h2>編輯消息</h2>
                    <nav class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <a href="index.php">新聞管理</a> /
                        <span>編輯消息</span>
                    </nav>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">消息更新成功！</div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">標題</label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($news['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">分類</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">選擇分類</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $news['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">內容</label>
                            <textarea id="content" name="content" class="form-control" required>
                                <?php echo htmlspecialchars($news['content']); ?>
                            </textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">圖片</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <?php if ($news['image']): ?>
                                <div class="image-preview">
                                    <img src="../../<?php echo htmlspecialchars($news['image']); ?>" alt="新聞圖片">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft" <?php echo $news['status'] === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo $news['status'] === 'published' ? 'selected' : ''; ?>>已發布</option>
                                <option value="archived" <?php echo $news['status'] === 'archived' ? 'selected' : ''; ?>>已封存</option>
                            </select>
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
        // 初始化 CKEditor
        ClassicEditor
            .create(document.querySelector('#content'))
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html> 