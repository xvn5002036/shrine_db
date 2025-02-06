<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// 確保用戶已登入且為管理員
adminOnly();

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證必填欄位
        if (empty($_POST['title']) || empty($_POST['category_id'])) {
            throw new Exception('請填寫所有必填欄位');
        }

        // 檢查是否有上傳圖片
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('請選擇要上傳的圖片');
        }

        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('只允許上傳 JPG、PNG 或 GIF 格式的圖片');
        }

        // 生成唯一的檔案名稱
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $upload_path = '../../uploads/gallery/' . $filename;

        // 移動上傳的檔案
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('圖片上傳失敗');
        }

        // 插入資料庫
        $stmt = $pdo->prepare("
            INSERT INTO gallery_images (
                category_id, title, description, image_path, 
                status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ");

        $stmt->execute([
            $_POST['category_id'],
            $_POST['title'],
            $_POST['description'],
            'uploads/gallery/' . $filename,
            isset($_POST['status']) ? 1 : 0
        ]);

        $_SESSION['success'] = '相片已成功新增';
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// 獲取分類列表
$categories = $pdo->query("SELECT * FROM gallery_categories WHERE status = 1 ORDER BY name")->fetchAll();

// 頁面標題
$page_title = '新增相片';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
            </div>

            <?php include '../includes/message.php'; ?>

            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">標題 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">分類 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">請選擇分類</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">描述</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">圖片 <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <div class="form-text">支援 JPG、PNG、GIF 格式，建議尺寸 1200x800 像素</div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1" checked>
                                        <label class="form-check-label" for="status">
                                            立即發布
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-save"></i> 儲存
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left"></i> 返回列表
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 
