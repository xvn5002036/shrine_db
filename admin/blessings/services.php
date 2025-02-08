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
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // 構建查詢條件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_clause .= " AND (b.name LIKE :search OR b.description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($type_filter) {
        $where_clause .= " AND b.type_id = :type_id";
        $params[':type_id'] = $type_filter;
    }
    
    if ($status_filter) {
        $where_clause .= " AND b.status = :status";
        $params[':status'] = $status_filter;
    }

    // 獲取總記錄數
    $count_sql = "
        SELECT COUNT(*) 
        FROM blessings b 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取服務列表
    $sql = "
        SELECT b.*, bt.name as type_name
        FROM blessings b
        LEFT JOIN blessing_types bt ON b.type_id = bt.id
        $where_clause
        ORDER BY b.id DESC
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $services = $stmt->fetchAll();

    // 獲取所有祈福類型
    $stmt = $pdo->query("SELECT * FROM blessing_types WHERE status = 'active' ORDER BY sort_order ASC");
    $blessing_types = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = '查詢失敗：' . $e->getMessage();
    $services = [];
    $total_pages = 0;
}

$page_title = '祈福服務管理';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>祈福服務管理</h1>
                <div class="btn-toolbar">
                    <a href="add_service.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> 新增服務
                    </a>
                </div>
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
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" 
                               placeholder="搜尋服務名稱或說明" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="type">
                            <option value="">所有類型</option>
                            <?php foreach ($blessing_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" 
                                        <?php echo $type_filter === (int)$type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">所有狀態</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>啟用</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>停用</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">搜尋</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 服務列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>排序</th>
                                <th>服務名稱</th>
                                <th>類型</th>
                                <th>價格</th>
                                <th>服務期間</th>
                                <th>狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">沒有找到相關服務</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo $service['sort_order']; ?></td>
                                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['type_name']); ?></td>
                                        <td>NT$ <?php echo number_format($service['price']); ?></td>
                                        <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $service['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $service['status'] === 'active' ? '啟用' : '停用'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit_service.php?id=<?php echo $service['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="編輯">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_service.php?id=<?php echo $service['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('確定要刪除此服務嗎？此操作無法復原。')"
                                                   title="刪除">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>">
                                        上一頁
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>">
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

<?php require_once '../includes/footer.php'; ?> 
