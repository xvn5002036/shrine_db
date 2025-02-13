<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 處理搜尋和篩選
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// 處理批次操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $selected_ids = isset($_POST['selected']) ? $_POST['selected'] : [];
    
    if (!empty($selected_ids)) {
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id IN (" . str_repeat('?,', count($selected_ids) - 1) . "?)");
                    $stmt->execute($selected_ids);
                    $_SESSION['success'] = '已成功刪除所選訂閱者';
                    break;
                    
                case 'unsubscribe':
                    $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed' WHERE id IN (" . str_repeat('?,', count($selected_ids) - 1) . "?)");
                    $stmt->execute($selected_ids);
                    $_SESSION['success'] = '已成功更新訂閱狀態';
                    break;
                    
                case 'resubscribe':
                    $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'subscribed' WHERE id IN (" . str_repeat('?,', count($selected_ids) - 1) . "?)");
                    $stmt->execute($selected_ids);
                    $_SESSION['success'] = '已成功更新訂閱狀態';
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = '操作失敗：' . $e->getMessage();
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

try {
    // 構建查詢條件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_clause .= " AND email LIKE :search";
        $params[':search'] = "%{$search}%";
    }
    
    if ($status_filter !== '') {
        $where_clause .= " AND status = :status";
        $params[':status'] = $status_filter;
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM newsletter_subscribers {$where_clause}";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取訂閱者列表
    $sql = "
        SELECT * FROM newsletter_subscribers 
        {$where_clause} 
        ORDER BY created_at DESC 
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $subscribers = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = '查詢失敗：' . $e->getMessage();
    $subscribers = [];
    $total_pages = 0;
}

$page_title = '電子報訂閱管理';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>電子報訂閱管理</h1>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 搜尋和篩選表單 -->
        <div class="card search-form">
            <div class="card-body">
                <form class="row g-3" method="GET">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" 
                               placeholder="搜尋電子郵件..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="status">
                            <option value="">所有狀態</option>
                            <option value="subscribed" <?php echo $status_filter === 'subscribed' ? 'selected' : ''; ?>>已訂閱</option>
                            <option value="unsubscribed" <?php echo $status_filter === 'unsubscribed' ? 'selected' : ''; ?>>已取消訂閱</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">搜尋</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 訂閱者列表 -->
        <div class="card">
            <div class="card-body">
                <form method="POST" id="subscribers-form">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="select-all">
                                    </th>
                                    <th>電子郵件</th>
                                    <th>狀態</th>
                                    <th>訂閱日期</th>
                                    <th>最後更新</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subscribers)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">沒有找到訂閱記錄</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subscribers as $subscriber): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected[]" value="<?php echo $subscriber['id']; ?>">
                                            </td>
                                            <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $subscriber['status'] === 'subscribed' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $subscriber['status'] === 'subscribed' ? '已訂閱' : '已取消訂閱'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($subscriber['created_at'])); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($subscriber['updated_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 批次操作 -->
                    <div class="bulk-actions mt-3">
                        <select name="action" class="form-select d-inline-block w-auto">
                            <option value="">批次操作</option>
                            <option value="delete">刪除所選</option>
                            <option value="unsubscribe">取消訂閱</option>
                            <option value="resubscribe">重新訂閱</option>
                        </select>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('確定要執行所選操作嗎？')">
                            執行
                        </button>
                    </div>
                </form>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                        上一頁
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">
                                        下一頁
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 全選/取消全選功能
document.querySelector('.select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

// 表單提交驗證
document.getElementById('subscribers-form').addEventListener('submit', function(e) {
    const action = this.querySelector('select[name="action"]').value;
    const selected = this.querySelectorAll('input[name="selected[]"]:checked');
    
    if (!action) {
        e.preventDefault();
        alert('請選擇要執行的操作');
        return;
    }
    
    if (selected.length === 0) {
        e.preventDefault();
        alert('請選擇要操作的訂閱者');
        return;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 