<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// 確保用戶已登入且為管理員
adminOnly();

// 處理新增分類
if (isset($_POST['add'])) {
    try {
        if (empty($_POST['name'])) {
            throw new Exception('請輸入分類名稱');
        }

        $stmt = $pdo->prepare("
            INSERT INTO gallery_categories (name, description, status, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'] ?? '',
            isset($_POST['status']) ? 1 : 0
        ]);

        $_SESSION['success'] = '分類已成功新增';
        header('Location: categories.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '新增分類失敗：' . $e->getMessage();
    }
}

// 處理編輯分類
if (isset($_POST['edit'])) {
    try {
        if (empty($_POST['name'])) {
            throw new Exception('請輸入分類名稱');
        }

        $stmt = $pdo->prepare("
            UPDATE gallery_categories 
            SET name = ?, description = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'] ?? '',
            isset($_POST['status']) ? 1 : 0,
            $_POST['id']
        ]);

        $_SESSION['success'] = '分類已成功更新';
        header('Location: categories.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '更新分類失敗：' . $e->getMessage();
    }
}

// 處理刪除分類
if (isset($_POST['delete'])) {
    try {
        // 檢查是否有相片使用此分類
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM gallery_images WHERE category_id = ?");
        $stmt->execute([$_POST['id']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('此分類下還有相片，無法刪除');
        }

        $stmt = $pdo->prepare("DELETE FROM gallery_categories WHERE id = ?");
        $stmt->execute([$_POST['id']]);

        $_SESSION['success'] = '分類已成功刪除';
        header('Location: categories.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '刪除分類失敗：' . $e->getMessage();
    }
}

// 獲取分類列表
$categories = $pdo->query("
    SELECT c.*, COUNT(i.id) as image_count 
    FROM gallery_categories c 
    LEFT JOIN gallery_images i ON c.id = i.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();

// 頁面標題
$page_title = '相簿分類管理';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> 新增分類
                    </button>
                </div>
            </div>

            <?php include '../includes/message.php'; ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>名稱</th>
                            <th>描述</th>
                            <th>狀態</th>
                            <th>相片數量</th>
                            <th>建立時間</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="text-center">尚無分類</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td>
                                        <?php if ($category['status']): ?>
                                            <span class="badge bg-success">啟用</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">停用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($category['image_count']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editModal<?php echo $category['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" class="d-inline" 
                                              onsubmit="return confirm('確定要刪除這個分類嗎？');">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger" 
                                                    <?php echo $category['image_count'] > 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- 編輯分類 Modal -->
                                <div class="modal fade" id="editModal<?php echo $category['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">編輯分類</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="edit_name<?php echo $category['id']; ?>" class="form-label">
                                                            名稱 <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" class="form-control" 
                                                               id="edit_name<?php echo $category['id']; ?>" 
                                                               name="name" required 
                                                               value="<?php echo htmlspecialchars($category['name']); ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="edit_description<?php echo $category['id']; ?>" class="form-label">
                                                            描述
                                                        </label>
                                                        <textarea class="form-control" 
                                                                  id="edit_description<?php echo $category['id']; ?>" 
                                                                  name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   id="edit_status<?php echo $category['id']; ?>" 
                                                                   name="status" value="1" 
                                                                   <?php echo $category['status'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" 
                                                                   for="edit_status<?php echo $category['id']; ?>">
                                                                啟用
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        取消
                                                    </button>
                                                    <button type="submit" name="edit" class="btn btn-primary">
                                                        儲存
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- 新增分類 Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">新增分類</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">描述</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_status" name="status" value="1" checked>
                            <label class="form-check-label" for="add_status">
                                啟用
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" name="add" class="btn btn-primary">儲存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 