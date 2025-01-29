<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理搜索和篩選
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$event_type = $_GET['type'] ?? '';

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 構建查詢條件
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($status)) {
    $where_conditions[] = "e.status = :status";
    $params[':status'] = $status;
}

if (!empty($event_type)) {
    $where_conditions[] = "et.id = :event_type";
    $params[':event_type'] = $event_type;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 獲取總記錄數
$count_sql = "
    SELECT COUNT(*) 
    FROM events e 
    LEFT JOIN event_types et ON e.event_type_id = et.id 
    LEFT JOIN admins a1 ON e.created_by = a1.id
    LEFT JOIN admins a2 ON e.updated_by = a2.id
    {$where_clause}
";

$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 獲取活動列表
$sql = "
    SELECT 
        e.*, 
        et.name as event_type_name,
        a1.username as created_by_name,
        a2.username as updated_by_name
    FROM events e 
    LEFT JOIN event_types et ON e.event_type_id = et.id
    LEFT JOIN admins a1 ON e.created_by = a1.id
    LEFT JOIN admins a2 ON e.updated_by = a2.id
    {$where_clause}
    ORDER BY e.event_date DESC 
    LIMIT :offset, :limit
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$events = $stmt->fetchAll();

// 獲取活動類型列表（用於篩選）
$event_types = $pdo->query("SELECT * FROM event_types ORDER BY sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 活動管理</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>活動管理</h2>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新增活動
                    </a>
                </div>

                <div class="content-card">
                    <!-- 搜索和篩選 -->
                    <div class="filter-section">
                        <form action="" method="get" class="search-form">
                            <div class="form-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜索活動...">
                            </div>
                            <div class="form-group">
                                <select name="status">
                                    <option value="">所有狀態</option>
                                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已發布</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已結束</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="type">
                                    <option value="">所有類型</option>
                                    <?php foreach ($event_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $event_type == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">搜索</button>
                        </form>
                    </div>

                    <!-- 活動列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>標題</th>
                                    <th>類型</th>
                                    <th>日期</th>
                                    <th>地點</th>
                                    <th>狀態</th>
                                    <th>報名人數</th>
                                    <th>建立者</th>
                                    <th>更新時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">沒有找到活動</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo $event['id']; ?></td>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo htmlspecialchars($event['event_type_name'] ?? '未分類'); ?></td>
                                            <td>
                                                <?php echo date('Y-m-d', strtotime($event['event_date'])); ?><br>
                                                <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $event['status']; ?>">
                                                    <?php
                                                    $status_map = [
                                                        'draft' => '草稿',
                                                        'published' => '已發布',
                                                        'cancelled' => '已取消',
                                                        'completed' => '已結束'
                                                    ];
                                                    echo $status_map[$event['status']] ?? $event['status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $event['current_participants']; ?>/
                                                <?php echo $event['max_participants'] ? $event['max_participants'] : '∞'; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['created_by_name'] ?? '系統'); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($event['updated_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary" title="編輯">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info" title="查看">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(<?php echo $event['id']; ?>)" class="btn btn-sm btn-danger" title="刪除">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁 -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($event_type) ? '&type=' . urlencode($event_type) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($event_type) ? '&type=' . urlencode($event_type) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($event_type) ? '&type=' . urlencode($event_type) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($event_type) ? '&type=' . urlencode($event_type) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($event_type) ? '&type=' . urlencode($event_type) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('確定要刪除這個活動嗎？此操作無法復原。')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
    </script>
</body>
</html> 