<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// 確保用戶已登入且為管理員
adminOnly();

// 檢查是否有提供ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = '未指定要編輯的相片';
    header('Location: index.php');
    exit;
}

// 獲取相片資料
$stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE id = ?");
$stmt->execute([$_GET['id']]);
$image = $stmt->fetch();

if (!$image) {
    $_SESSION['error'] = '找不到指定的相片';
    header('Location: index.php');
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證必填欄位
        if (empty($_POST['title']) || empty($_POST['category_id'])) {
            throw new Exception('請填寫所有必填欄位');
        }

        $image_path = $image['image_path'];

        // 處理圖片上傳
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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

            // 刪除舊圖片
            if (file_exists('../../' . $image['image_path'])) {
                unlink('../../' . $image['image_path']);
            }

            $image_path = 'uploads/gallery/' . $filename;
        }

        // 更新資料庫
        $stmt = $pdo->prepare("
            UPDATE gallery_images SET 
                category_id = ?,
                title = ?,
                description = ?,
                image_path = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['category_id'],
            $_POST['title'],
            $_POST['description'],
            $image_path,
            isset($_POST['status']) ? 1 : 0,
            $image['id']
        ]);

        $_SESSION['success'] = '相片已成功更新';
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// 獲取分類列表
$categories = $pdo->query("SELECT * FROM gallery_categories WHERE status = 1 ORDER BY name")->fetchAll();

// 頁面標題
$page_title = '編輯相片';
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
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($image['title']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">分類 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">請選擇分類</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $category['id'] == $image['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">描述</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($image['description']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="image" class="form-label">圖片</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    <div class="form-text">支援 JPG、PNG、GIF 格式，建議尺寸 1200x800 像素</div>
                                    <?php if ($image['image_path']): ?>
                                        <div class="mt-2">
                                            <img src="../../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 alt="目前圖片" class="img-thumbnail" style="max-height: 200px;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1"
                                               <?php echo $image['status'] ? 'checked' : ''; ?>>
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