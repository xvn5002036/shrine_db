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
$category = $_GET['category'] ?? '';

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 構建查詢條件
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(n.title LIKE :search OR n.content LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($status)) {
    $where_conditions[] = "n.status = :status";
    $params[':status'] = $status;
}

if (!empty($category)) {
    $where_conditions[] = "n.category_id = :category";
    $params[':category'] = $category;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 獲取總記錄數
$count_sql = "
    SELECT COUNT(*) 
    FROM news n 
    LEFT JOIN news_categories nc ON n.category_id = nc.id 
    LEFT JOIN admins a1 ON n.created_by = a1.id
    LEFT JOIN admins a2 ON n.updated_by = a2.id
    {$where_clause}
";

$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 獲取新聞列表
$sql = "
    SELECT 
        n.*, 
        nc.name as category_name,
        a1.username as created_by_name,
        a2.username as updated_by_name
    FROM news n 
    LEFT JOIN news_categories nc ON n.category_id = nc.id
    LEFT JOIN admins a1 ON n.created_by = a1.id
    LEFT JOIN admins a2 ON n.updated_by = a2.id
    {$where_clause}
    ORDER BY n.created_at DESC 
    LIMIT :offset, :limit
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$news_list = $stmt->fetchAll();

// 獲取新聞分類列表（用於篩選）
$categories = $pdo->query("SELECT * FROM news_categories ORDER BY sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 新聞管理</title>
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
                    <h2>新聞管理</h2>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新增新聞
                    </a>
                </div>

                <div class="content-card">
                    <!-- 搜索和篩選 -->
                    <div class="filter-section">
                        <form action="" method="get" class="search-form">
                            <div class="form-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜索新聞...">
                            </div>
                            <div class="form-group">
                                <select name="status">
                                    <option value="">所有狀態</option>
                                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已發布</option>
                                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>已封存</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="category">
                                    <option value="">所有分類</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">搜索</button>
                        </form>
                    </div>

                    <!-- 新聞列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>標題</th>
                                    <th>分類</th>
                                    <th>狀態</th>
                                    <th>發布日期</th>
                                    <th>建立者</th>
                                    <th>更新時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($news_list)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">沒有找到新聞</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($news_list as $news): ?>
                                        <tr>
                                            <td><?php echo $news['id']; ?></td>
                                            <td><?php echo htmlspecialchars($news['title']); ?></td>
                                            <td><?php echo htmlspecialchars($news['category_name'] ?? '未分類'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $news['status']; ?>">
                                                    <?php
                                                    $status_map = [
                                                        'draft' => '草稿',
                                                        'published' => '已發布',
                                                        'archived' => '已封存'
                                                    ];
                                                    echo $status_map[$news['status']] ?? $news['status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($news['publish_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($news['created_by_name'] ?? '系統'); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($news['updated_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary" title="編輯">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-info" title="查看">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(<?php echo $news['id']; ?>)" class="btn btn-sm btn-danger" title="刪除">
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
                                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?>" class="page-link">
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
            if (confirm('確定要刪除這則新聞嗎？此操作無法復原。')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
    </script>
</body>
</html> 