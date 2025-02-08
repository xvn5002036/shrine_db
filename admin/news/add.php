<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 初始化變數
$title = '';
$category_id = '';
$content = '';
$status = 'draft';
$error = '';
$success = false;

// 獲取新聞分類列表
try {
    $stmt = $pdo->query("SELECT id, name FROM news_categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching news categories: ' . $e->getMessage());
    $categories = [];
}

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
            // 生成 slug
            $base_slug = generateSlug($title);
            $slug = $base_slug;
            $counter = 1;
            
            // 檢查 slug 是否已存在，如果存在則加上數字
            while (true) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetchColumn() == 0) break;
                $slug = $base_slug . '-' . $counter++;
            }

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
                
                $upload_dir = '../../uploads/news/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $image_path = 'uploads/news/' . $filename;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    throw new Exception('圖片上傳失敗');
                }
            }
            
            // 儲存新聞
            $stmt = $pdo->prepare("
                INSERT INTO news (title, slug, category_id, content, image, status, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $title,
                $slug,
                $category_id ?: null,
                $content,
                $image_path,
                $status,
                $_SESSION['admin_id']
            ]);
            
            $success = true;
            $title = $category_id = $content = '';
            $status = 'draft';
            
        } catch (Exception $e) {
            error_log('Error adding news: ' . $e->getMessage());
            $error = '新增消息時發生錯誤：' . $e->getMessage();
            
            // 如果上傳失敗，刪除已上傳的圖片
            if (isset($image_path) && file_exists('../../' . $image_path)) {
                unlink('../../' . $image_path);
            }
        }
    }
}

// 在檔案開頭加入 generateSlug 函數
function generateSlug($text) {
    // 將文字轉換為小寫
    $text = mb_strtolower($text, 'UTF-8');
    
    // 將中文字轉換為拼音（如果有需要的話）
    // 這裡可以使用其他轉換方法
    
    // 移除特殊字符
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
    
    // 將空格替換為連字符
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // 移除開頭和結尾的連字符
    $text = trim($text, '-');
    
    // 如果是空的，給一個預設值
    if (empty($text)) {
        $text = 'news-' . time();
    }
    
    return $text;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增消息 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- 引入 CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/27.1.0/classic/ckeditor.js"></script>
    <style>
        /* 基本布局 */
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
            padding-left: 250px; /* 側邊欄寬度 */
        }

        .admin-main {
            flex: 1;
            padding: 80px 20px 20px 20px; /* 上方增加 padding 避免被頂部欄遮蓋 */
            min-height: 100vh;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            background-color: #f4f6f9;
            margin-left: 0; /* 確保不會有額外的左邊距 */
            z-index: 1; /* 確保主內容在適當的層級 */
        }

        .content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .content-header {
            margin-bottom: 20px;
        }

        .breadcrumb {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .breadcrumb a {
            color: #4a90e2;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* 表單樣式 */
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #4a90e2;
            outline: none;
        }

        select.form-control {
            height: 38px;
        }

        .image-preview {
            max-width: 300px;
            margin-top: 10px;
        }

        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .ck-editor__editable {
            min-height: 300px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-container {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
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

        .btn:hover {
            opacity: 0.9;
        }

        .required {
            color: #dc3545;
            margin-left: 3px;
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .admin-container {
                padding-left: 0;
            }

            .admin-main {
                padding-top: 60px;
                margin-left: 0;
            }

            .content {
                padding: 15px;
            }

            .form-container {
                padding: 15px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
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
                    <h2>新增消息</h2>
                    <nav class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <a href="index.php">消息管理</a> /
                        <span>新增消息</span>
                    </nav>
                </div>

                <div class="form-container">
                    <?php if ($success): ?>
                        <div class="message success">
                            <i class="fas fa-check-circle"></i> 消息新增成功！
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="message error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">標題 <span class="required">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category_id">分類</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">選擇分類</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="image">封面圖片</label>
                            <input type="file" id="image" name="image" class="form-control" 
                                   accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted">
                                支援 JPG、PNG、GIF 格式，檔案大小不超過 5MB
                            </small>
                            <div id="image-preview" class="image-preview"></div>
                        </div>

                        <div class="form-group">
                            <label for="content">內容 <span class="required">*</span></label>
                            <textarea id="content" name="content" class="form-control" required>
                                <?php echo htmlspecialchars($content); ?>
                            </textarea>
                        </div>

                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>發布</option>
                            </select>
                        </div>

                        <div class="btn-container">
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
        </main>
    </div>

    <script>
        // 初始化 CKEditor
        ClassicEditor
            .create(document.querySelector('#content'))
            .catch(error => {
                console.error(error);
            });

        // 圖片預覽
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html> 
