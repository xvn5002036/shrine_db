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
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? (int)$_GET['status'] : '';

try {
    // 構建查詢條件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_clause .= " AND (username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($role_filter)) {
        $where_clause .= " AND role = :role";
        $params[':role'] = $role_filter;
    }
    
    if ($status_filter !== '') {
        $where_clause .= " AND status = :status";
        $params[':status'] = $status_filter;
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM addusers $where_clause";
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取用戶列表
    $sql = "
        SELECT id, username, email, 
               CONCAT(first_name, ' ', last_name) as full_name,
               role, status, created_at, last_login 
        FROM addusers 
        $where_clause 
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
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = '查詢失敗：' . $e->getMessage();
    $users = [];
    $total_pages = 0;
}

$page_title = '用戶管理';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>用戶管理</h1>
                <div class="btn-toolbar">
                    <a href="add.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> 新增用戶
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
                               placeholder="搜尋用戶名/信箱/姓名" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">所有角色</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>管理員</option>
                            <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>一般用戶</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">所有狀態</option>
                            <option value="1" <?php echo $status_filter === 1 ? 'selected' : ''; ?>>啟用</option>
                            <option value="0" <?php echo $status_filter === 0 ? 'selected' : ''; ?>>停用</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">搜尋</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 用戶列表 -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用戶名</th>
                                <th>姓名</th>
                                <th>信箱</th>
                                <th>角色</th>
                                <th>狀態</th>
                                <th>註冊時間</th>
                                <th>最後登入</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">沒有找到相關用戶</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                                <?php echo $user['role'] === 'admin' ? '管理員' : '一般用戶'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['status'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $user['status'] ? '啟用' : '停用'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '從未登入'; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="編輯">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                                    <a href="delete.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('確定要刪除此用戶嗎？此操作無法復原。')"
                                                       title="刪除">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
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
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
                                        上一頁
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>">
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

<?php require_once '../templates/footer.php'; ?> 
