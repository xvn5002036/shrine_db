<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 確保用戶已登入且有權限
checkAdminAuth();

// 初始化變數
$services = [];
$types = [];
$error = null;
$success = null;

// 處理刪除請求
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE services SET status = 0 WHERE id = ?");
        if ($stmt->execute([$_POST['id']])) {
            $success = '服務項目已成功刪除';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = '刪除失敗，請稍後再試';
    }
}

try {
    // 獲取服務類型
    $stmt = $pdo->query("SELECT * FROM service_types WHERE status = 1 ORDER BY sort_order");
    if ($stmt) {
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 獲取服務列表
    $query = "SELECT s.*, t.name as type_name, u.username as created_by_name 
              FROM services s 
              JOIN service_types t ON s.type_id = t.id 
              LEFT JOIN users u ON s.created_by = u.id 
              WHERE s.status = 1 
              ORDER BY t.sort_order, s.sort_order, s.name";
    
    $stmt = $pdo->query($query);
    if ($stmt) {
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '系統發生錯誤，請稍後再試';
}

// 頁面標題
$page_title = '服務管理';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新增服務
        </a>
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
                            <th>類型</th>
                            <th>名稱</th>
                            <th>價格</th>
                            <th>排序</th>
                            <th>需預約</th>
                            <th>建立者</th>
                            <th>建立時間</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td>
                                        <?php if ($service['price']): ?>
                                            NT$ <?php echo number_format($service['price']); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['sort_order']); ?></td>
                                    <td>
                                        <?php if ($service['booking_required']): ?>
                                            <span class="badge bg-success">是</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">否</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['created_by_name']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($service['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit.php?id=<?php echo $service['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($service['booking_required']): ?>
                                                <a href="schedule.php?id=<?php echo $service['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="時段設定">
                                                    <i class="fas fa-clock"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete(<?php echo $service['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">尚無服務項目</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
                確定要刪除這個服務項目嗎？
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" name="delete" class="btn btn-danger">確定刪除</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?> 