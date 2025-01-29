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
$prayer_type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 構建查詢條件
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(pr.name LIKE :search OR pr.content LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($status)) {
    $where_conditions[] = "pr.status = :status";
    $params[':status'] = $status;
}

if (!empty($prayer_type)) {
    $where_conditions[] = "pt.id = :prayer_type";
    $params[':prayer_type'] = $prayer_type;
}

if (!empty($start_date)) {
    $where_conditions[] = "pr.created_at >= :start_date";
    $params[':start_date'] = $start_date . ' 00:00:00';
}

if (!empty($end_date)) {
    $where_conditions[] = "pr.created_at <= :end_date";
    $params[':end_date'] = $end_date . ' 23:59:59';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 獲取總記錄數
$count_sql = "
    SELECT COUNT(*) 
    FROM prayer_requests pr 
    LEFT JOIN prayer_types pt ON pr.type_id = pt.id
    {$where_clause}
";

$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 獲取祈福請求列表
$sql = "
    SELECT 
        pr.*,
        pt.name as prayer_type_name,
        a1.username as processed_by_name
    FROM prayer_requests pr 
    LEFT JOIN prayer_types pt ON pr.type_id = pt.id
    LEFT JOIN admins a1 ON pr.processed_by = a1.id
    {$where_clause}
    ORDER BY 
        CASE pr.status 
            WHEN 'pending' THEN 1
            WHEN 'processing' THEN 2
            WHEN 'completed' THEN 3
            ELSE 4
        END,
        pr.created_at DESC
    LIMIT :offset, :limit
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$prayers = $stmt->fetchAll();

// 獲取祈福類型列表（用於篩選）
$prayer_types = $pdo->query("SELECT * FROM prayer_types ORDER BY sort_order")->fetchAll();

// 狀態對應中文說明
$status_map = [
    'pending' => '待處理',
    'processing' => '處理中',
    'completed' => '已完成',
    'cancelled' => '已取消'
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 祈福管理</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/zh-tw.js"></script>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>祈福請求管理</h2>
                    <div class="header-actions">
                        <a href="export.php" class="btn btn-success">
                            <i class="fas fa-file-export"></i> 匯出資料
                        </a>
                    </div>
                </div>

                <div class="content-card">
                    <!-- 搜索和篩選 -->
                    <div class="filter-section">
                        <form action="" method="get" class="search-form">
                            <div class="form-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜索祈福請求...">
                            </div>
                            <div class="form-group">
                                <select name="status">
                                    <option value="">所有狀態</option>
                                    <?php foreach ($status_map as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="type">
                                    <option value="">所有類型</option>
                                    <?php foreach ($prayer_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $prayer_type == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" placeholder="開始日期">
                            </div>
                            <div class="form-group">
                                <input type="text" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" placeholder="結束日期">
                            </div>
                            <button type="submit" class="btn btn-primary">搜索</button>
                            <a href="index.php" class="btn btn-secondary">重置</a>
                        </form>
                    </div>

                    <!-- 祈福請求列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>申請人</th>
                                    <th>類型</th>
                                    <th>內容</th>
                                    <th>狀態</th>
                                    <th>申請時間</th>
                                    <th>處理者</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($prayers)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">沒有找到祈福請求</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($prayers as $prayer): ?>
                                        <tr>
                                            <td><?php echo $prayer['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($prayer['user_name']); ?><br>
                                                <small><?php echo htmlspecialchars($prayer['user_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($prayer['prayer_type_name'] ?? '未分類'); ?></td>
                                            <td>
                                                <?php 
                                                $content = mb_substr(strip_tags($prayer['content']), 0, 50, 'UTF-8');
                                                echo htmlspecialchars($content) . (mb_strlen($prayer['content']) > 50 ? '...' : '');
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $prayer['status']; ?>">
                                                    <?php echo $status_map[$prayer['status']] ?? $prayer['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($prayer['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($prayer['processed_by_name'] ?? '-'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="process.php?id=<?php echo $prayer['id']; ?>" class="btn btn-sm btn-primary" title="處理">
                                                        <i class="fas fa-tasks"></i>
                                                    </a>
                                                    <a href="view.php?id=<?php echo $prayer['id']; ?>" class="btn btn-sm btn-info" title="查看">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($prayer['status'] === 'pending'): ?>
                                                        <button onclick="confirmCancel(<?php echo $prayer['id']; ?>)" class="btn btn-sm btn-warning" title="取消">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>
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
                                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($prayer_type) ? '&type=' . urlencode($prayer_type) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($prayer_type) ? '&type=' . urlencode($prayer_type) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($prayer_type) ? '&type=' . urlencode($prayer_type) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($prayer_type) ? '&type=' . urlencode($prayer_type) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>" class="page-link">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?><?php echo !empty($prayer_type) ? '&type=' . urlencode($prayer_type) : ''; ?><?php echo !empty($start_date) ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo !empty($end_date) ? '&end_date=' . urlencode($end_date) : ''; ?>" class="page-link">
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
        // 初始化日期選擇器
        flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            locale: "zh-tw"
        });
        
        flatpickr("#end_date", {
            dateFormat: "Y-m-d",
            locale: "zh-tw"
        });

        // 確認取消祈福請求
        function confirmCancel(id) {
            if (confirm('確定要取消這個祈福請求嗎？')) {
                window.location.href = 'cancel.php?id=' + id;
            }
        }
    </script>
</body>
</html> 
