<?php
// 處理操作
$action = $_GET['action'] ?? 'list';
$message = '';

// 處理審核/刪除操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'approve':
            $id = $_POST['id'] ?? 0;
            try {
                $stmt = $pdo->prepare("
                    UPDATE blessings 
                    SET status = 'approved', approved_by = ?, approved_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$admin['id'], $id]);
                $message = '祝福已審核通過！';
            } catch (PDOException $e) {
                $message = '操作失敗：' . $e->getMessage();
            }
            break;
            
        case 'reject':
            $id = $_POST['id'] ?? 0;
            $reject_reason = $_POST['reject_reason'] ?? '';
            try {
                $stmt = $pdo->prepare("
                    UPDATE blessings 
                    SET status = 'rejected', reject_reason = ?, rejected_by = ?, rejected_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$reject_reason, $admin['id'], $id]);
                $message = '祝福已被拒絕！';
            } catch (PDOException $e) {
                $message = '操作失敗：' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            try {
                $stmt = $pdo->prepare("DELETE FROM blessings WHERE id = ?");
                $stmt->execute([$id]);
                $message = '祝福已刪除！';
            } catch (PDOException $e) {
                $message = '刪除失敗：' . $e->getMessage();
            }
            break;
    }
}

// 獲取祝福列表
$page = $_GET['p'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 過濾條件
$status_filter = $_GET['status'] ?? 'pending';

try {
    // 獲取總數
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blessings WHERE status = ?");
    $stmt->execute([$status_filter]);
    $total = $stmt->fetchColumn();
    
    // 獲取當前頁數據
    $stmt = $pdo->prepare("
        SELECT b.*, 
               u1.name as author_name,
               u2.name as approved_by_name,
               u3.name as rejected_by_name
        FROM blessings b
        LEFT JOIN users u1 ON b.created_by = u1.id
        LEFT JOIN users u2 ON b.approved_by = u2.id
        LEFT JOIN users u3 ON b.rejected_by = u3.id
        WHERE b.status = ?
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$status_filter, $per_page, $offset]);
    $blessings_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = '獲取數據失敗：' . $e->getMessage();
    $total = 0;
    $blessings_list = [];
}

// 計算總頁數
$total_pages = ceil($total / $per_page);
?>

<div class="content-header">
    <h2 class="content-title">祝福管理</h2>
    <div class="filter-buttons">
        <a href="?page=blessings&status=pending" 
           class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-light'; ?>">
            待審核
        </a>
        <a href="?page=blessings&status=approved" 
           class="btn <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-light'; ?>">
            已通過
        </a>
        <a href="?page=blessings&status=rejected" 
           class="btn <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-light'; ?>">
            已拒絕
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-info">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>發布者</th>
                        <th>內容</th>
                        <th>發布時間</th>
                        <?php if ($status_filter === 'approved'): ?>
                        <th>審核者</th>
                        <th>審核時間</th>
                        <?php elseif ($status_filter === 'rejected'): ?>
                        <th>拒絕原因</th>
                        <th>處理者</th>
                        <th>處理時間</th>
                        <?php endif; ?>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blessings_list as $blessing): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($blessing['author_name']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($blessing['content'])); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($blessing['created_at'])); ?></td>
                        <?php if ($status_filter === 'approved'): ?>
                        <td><?php echo htmlspecialchars($blessing['approved_by_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($blessing['approved_at'])); ?></td>
                        <?php elseif ($status_filter === 'rejected'): ?>
                        <td><?php echo htmlspecialchars($blessing['reject_reason']); ?></td>
                        <td><?php echo htmlspecialchars($blessing['rejected_by_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($blessing['rejected_at'])); ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($status_filter === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-success"
                                    onclick="approveBlessing(<?php echo $blessing['id']; ?>)">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning"
                                    onclick="showRejectDialog(<?php echo $blessing['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="deleteBlessing(<?php echo $blessing['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=blessings&status=<?php echo $status_filter; ?>&p=<?php echo $i; ?>" 
               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 拒絕原因對話框 -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">拒絕原因</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectForm" method="POST">
                    <input type="hidden" name="id" id="rejectBlessingId">
                    <div class="form-group">
                        <label for="reject_reason">請輸入拒絕原因：</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" 
                                rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitReject()">確定</button>
            </div>
        </div>
    </div>
</div>

<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
}

.btn-light {
    color: #212529;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    text-decoration: none;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border: 1px solid #007bff;
}

.btn-success {
    color: #fff;
    background-color: #28a745;
    border: 1px solid #28a745;
}

.btn-warning {
    color: #212529;
    background-color: #ffc107;
    border: 1px solid #ffc107;
}

.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border: 1px solid #dc3545;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1050;
}

.modal.fade {
    opacity: 0;
    transition: opacity 0.15s linear;
}

.modal.show {
    opacity: 1;
    display: block;
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 0.5rem;
    pointer-events: none;
    transform: translate(0, -50px);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: none;
}

.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 0.3rem;
    outline: 0;
}

.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 0.3rem;
    border-top-right-radius: 0.3rem;
}

.modal-body {
    position: relative;
    flex: 1 1 auto;
    padding: 1rem;
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 1rem;
    border-top: 1px solid #dee2e6;
}

.close {
    float: right;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    opacity: .5;
    padding: 0;
    background-color: transparent;
    border: 0;
    appearance: none;
}

.form-group {
    margin-bottom: 1rem;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.pagination {
    display: flex;
    padding-left: 0;
    list-style: none;
    border-radius: 0.25rem;
    margin-top: 20px;
    justify-content: center;
}

.page-link {
    position: relative;
    display: block;
    padding: 0.5rem 0.75rem;
    margin-left: -1px;
    line-height: 1.25;
    color: #007bff;
    background-color: #fff;
    border: 1px solid #dee2e6;
    text-decoration: none;
}

.page-link.active {
    z-index: 1;
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
</style>

<script>
function approveBlessing(id) {
    if (confirm('確定要通過這條祝福嗎？')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=blessings&action=approve';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectDialog(id) {
    document.getElementById('rejectBlessingId').value = id;
    const modal = document.getElementById('rejectModal');
    modal.classList.add('show');
}

function submitReject() {
    const form = document.getElementById('rejectForm');
    form.action = '?page=blessings&action=reject';
    form.submit();
}

function closeModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.remove('show');
}

function deleteBlessing(id) {
    if (confirm('確定要刪除這條祝福嗎？此操作無法復原。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=blessings&action=delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// 關閉按鈕和遮罩層點擊事件
document.querySelector('.close').addEventListener('click', closeModal);
document.querySelector('.modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script> 