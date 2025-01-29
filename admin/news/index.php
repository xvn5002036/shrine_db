<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// 處理搜尋
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE title LIKE :search OR content LIKE :search";
    $params[':search'] = "%{$search}%";
}

// 獲取總記錄數
try {
    $count_sql = "SELECT COUNT(*) FROM news " . $where_clause;
    $stmt = $pdo->prepare($count_sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $total_records = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Error counting news: ' . $e->getMessage());
    $total_records = 0;
}

$total_pages = ceil($total_records / $per_page);

// 獲取新聞列表
try {
    $sql = "
        SELECT n.*, a.username as author_name 
        FROM news n 
        LEFT JOIN admins a ON n.created_by = a.id 
        {$where_clause} 
        ORDER BY n.created_at DESC 
        LIMIT :offset, :per_page
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $news_list = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching news: ' . $e->getMessage());
    $news_list = [];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 新聞管理</title>
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
                    <div class="content-header-actions">
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 新增新聞
                        </a>
                    </div>
                </div>
                
                <div class="content-card">
                    <?php displayFlashMessages(); ?>

                    <!-- 搜尋表單 -->
                    <div class="search-form">
                        <form action="" method="get" class="form-inline">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="搜尋新聞..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">搜尋</button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-secondary">清除搜尋</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- 新聞列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>標題</th>
                                    <th>作者</th>
                                    <th>狀態</th>
                                    <th>發布時間</th>
                                    <th>建立時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($news_list)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">目前沒有新聞</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($news_list as $news): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($news['title']); ?></td>
                                            <td><?php echo htmlspecialchars($news['author_name'] ?? '未知'); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $news['status']; ?>">
                                                    <?php echo $news['status'] === 'published' ? '已發布' : '草稿'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $news['publish_date'] ? date('Y/m/d H:i', strtotime($news['publish_date'])) : '未設定'; ?></td>
                                            <td><?php echo date('Y/m/d H:i', strtotime($news['created_at'])); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary" title="編輯">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="preview.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-info" title="預覽" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete(<?php echo $news['id']; ?>)" title="刪除">
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
                                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
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