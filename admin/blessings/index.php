<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 處理狀態更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $registration_id = (int)$_POST['registration_id'];
        
        if ($_POST['action'] === 'update_status') {
            $new_status = $_POST['status'];
            $stmt = $pdo->prepare("
                UPDATE blessing_registrations 
                SET status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $registration_id]);
            $_SESSION['success'] = '預約狀態已更新';
        } elseif ($_POST['action'] === 'delete') {
            // 檢查預約狀態是否為已取消
            $stmt = $pdo->prepare("SELECT status FROM blessing_registrations WHERE id = ?");
            $stmt->execute([$registration_id]);
            $registration = $stmt->fetch();
            
            if ($registration && $registration['status'] === 'cancelled') {
                // 執行刪除操作
                $stmt = $pdo->prepare("DELETE FROM blessing_registrations WHERE id = ?");
                $stmt->execute([$registration_id]);
                $_SESSION['success'] = '預約記錄已成功刪除';
            } else {
                throw new Exception('只能刪除已取消的預約記錄');
            }
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = '操作失敗：' . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 處理搜尋和篩選
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

try {
    // 構建查詢條件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_clause .= " AND (br.name LIKE :search OR br.email LIKE :search OR br.phone LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_clause .= " AND br.status = :status";
        $params[':status'] = $status_filter;
    }
    
    if (!empty($date_from)) {
        $where_clause .= " AND DATE(br.created_at) >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_clause .= " AND DATE(br.created_at) <= :date_to";
        $params[':date_to'] = $date_to;
    }

    // 獲取總記錄數
    $count_sql = "
        SELECT COUNT(*) 
        FROM blessing_registrations br 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取預約列表
    $sql = "
        SELECT br.*, 
               b.name as blessing_name, 
               b.price as price,
               bt.name as type_name
        FROM blessing_registrations br
        LEFT JOIN blessings b ON br.blessing_id = b.id
        LEFT JOIN blessing_types bt ON b.type_id = bt.id
        $where_clause
        ORDER BY br.created_at DESC
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $registrations = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = '查詢失敗：' . $e->getMessage();
    $registrations = [];
    $total_pages = 0;
}

$page_title = '祈福預約管理';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>祈福預約管理</h1>
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
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="搜尋姓名/信箱/電話" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="">所有狀態</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>待處理</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>已確認</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>已完成</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" 
                               value="<?php echo $date_from; ?>" placeholder="開始日期">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" 
                               value="<?php echo $date_to; ?>" placeholder="結束日期">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">搜尋</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 預約列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>申請人</th>
                                <th>祈福項目</th>
                                <th>類型</th>
                                <th>聯絡方式</th>
                                <th>金額</th>
                                <th>申請日期</th>
                                <th>狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrations)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">沒有找到相關記錄</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrations as $reg): ?>
                                    <tr>
                                        <td><?php echo $reg['id']; ?></td>
                                        <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['blessing_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['type_name']); ?></td>
                                        <td>
                                            <div>電話：<?php echo htmlspecialchars($reg['phone']); ?></div>
                                            <div>信箱：<?php echo htmlspecialchars($reg['email']); ?></div>
                                        </td>
                                        <td>NT$ <?php echo number_format($reg['price']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($reg['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo match($reg['status']) {
                                                    'pending' => 'bg-warning',
                                                    'confirmed' => 'bg-info',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            ?>">
                                                <?php 
                                                echo match($reg['status']) {
                                                    'pending' => '待處理',
                                                    'confirmed' => '已確認',
                                                    'completed' => '已完成',
                                                    'cancelled' => '已取消',
                                                    default => '未知'
                                                };
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    更新狀態
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" class="dropdown-item">確認</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                            <input type="hidden" name="status" value="completed">
                                                            <button type="submit" class="dropdown-item">完成</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit" class="dropdown-item">取消</button>
                                                        </form>
                                                    </li>
                                                    <?php if ($reg['status'] === 'cancelled'): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('確定要刪除此預約記錄嗎？此操作無法復原。');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash"></i> 刪除
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <a href="view.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        上一頁
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
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
