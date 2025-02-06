<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 確保用戶已登入且有權限
checkAdminAuth();

// 初始化變數
$types = [];
$error = null;
$success = null;

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // 驗證必填欄位
                    if (empty($_POST['name'])) {
                        throw new Exception('請填寫類型名稱');
                    }

                    // 生成 slug
                    $slug = generateSlug($_POST['name']);

                    // 檢查 slug 是否已存在
                    $stmt = $pdo->prepare("SELECT id FROM service_types WHERE slug = ?");
                    $stmt->execute([$slug]);
                    if ($stmt->fetch()) {
                        throw new Exception('此類型名稱已存在');
                    }

                    // 插入數據
                    $stmt = $pdo->prepare("INSERT INTO service_types (name, slug, description, sort_order) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([
                        $_POST['name'],
                        $slug,
                        $_POST['description'],
                        !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0
                    ])) {
                        $success = '服務類型新增成功';
                    } else {
                        throw new Exception('服務類型新增失敗');
                    }
                    break;

                case 'edit':
                    // 驗證必填欄位
                    if (empty($_POST['id']) || empty($_POST['name'])) {
                        throw new Exception('缺少必要參數');
                    }

                    // 生成新的 slug
                    $slug = generateSlug($_POST['name']);

                    // 檢查 slug 是否已被其他記錄使用
                    $stmt = $pdo->prepare("SELECT id FROM service_types WHERE slug = ? AND id != ?");
                    $stmt->execute([$slug, $_POST['id']]);
                    if ($stmt->fetch()) {
                        throw new Exception('此類型名稱已存在');
                    }

                    // 更新數據
                    $stmt = $pdo->prepare("UPDATE service_types SET name = ?, slug = ?, description = ?, sort_order = ? WHERE id = ?");
                    if ($stmt->execute([
                        $_POST['name'],
                        $slug,
                        $_POST['description'],
                        !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0,
                        $_POST['id']
                    ])) {
                        $success = '服務類型更新成功';
                    } else {
                        throw new Exception('服務類型更新失敗');
                    }
                    break;

                case 'delete':
                    if (empty($_POST['id'])) {
                        throw new Exception('缺少必要參數');
                    }

                    // 檢查是否有關聯的服務
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE type_id = ?");
                    $stmt->execute([$_POST['id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('此類型下有關聯的服務項目，無法刪除');
                    }

                    // 刪除類型
                    $stmt = $pdo->prepare("DELETE FROM service_types WHERE id = ?");
                    if ($stmt->execute([$_POST['id']])) {
                        $success = '服務類型已刪除';
                    } else {
                        throw new Exception('服務類型刪除失敗');
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 獲取服務類型列表
try {
    $stmt = $pdo->query("SELECT t.*, 
                                (SELECT COUNT(*) FROM services s WHERE s.type_id = t.id) as service_count 
                         FROM service_types t 
                         ORDER BY t.sort_order, t.name");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '系統發生錯誤，請稍後再試';
}

// 頁面標題
$page_title = '服務類型管理';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> 新增類型
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>排序</th>
                            <th>名稱</th>
                            <th>代碼</th>
                            <th>說明</th>
                            <th>服務數量</th>
                            <th>建立時間</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($types)): ?>
                            <?php foreach ($types as $type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['sort_order']); ?></td>
                                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                                    <td><?php echo htmlspecialchars($type['slug']); ?></td>
                                    <td><?php echo htmlspecialchars($type['description']); ?></td>
                                    <td><?php echo $type['service_count']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($type['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary"
                                                    onclick="editType(<?php echo htmlspecialchars(json_encode($type)); ?>)"
                                                    title="編輯">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($type['service_count'] == 0): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmDelete(<?php echo $type['id']; ?>)"
                                                        title="刪除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">尚無服務類型</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 新增類型對話框 -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">新增服務類型</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">類型名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">說明</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="add_sort_order" class="form-label">排序</label>
                        <input type="number" class="form-control" id="add_sort_order" name="sort_order" value="0">
                        <div class="form-text">數字越小越靠前</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">新增</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 編輯類型對話框 -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">編輯服務類型</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">類型名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">說明</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">排序</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order">
                        <div class="form-text">數字越小越靠前</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">儲存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 刪除確認對話框 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">確認刪除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                確定要刪除這個服務類型嗎？
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-danger">確定刪除</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editType(type) {
    document.getElementById('edit_id').value = type.id;
    document.getElementById('edit_name').value = type.name;
    document.getElementById('edit_description').value = type.description;
    document.getElementById('edit_sort_order').value = type.sort_order;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?> 