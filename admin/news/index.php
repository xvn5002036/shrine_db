<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 處理搜尋和篩選
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? trim($_GET['category_id']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($search) {
        $where[] = "(n.title LIKE ? OR n.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category_id) {
        $where[] = "n.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($status) {
        $where[] = "n.status = ?";
        $params[] = $status;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $countSql = "SELECT COUNT(*) FROM news n $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 計算總頁數
    $totalPages = ceil($total / $limit);
    if ($totalPages < 1) $totalPages = 1;
    
    // 確保當前頁碼有效
    if ($page > $totalPages) $page = $totalPages;
    
    // 獲取新聞列表
    $sql = "
        SELECT n.*, nc.name as category_name, u.username as author_name
        FROM news n 
        LEFT JOIN news_categories nc ON n.category_id = nc.id 
        LEFT JOIN users u ON n.created_by = u.id
        $whereClause 
        ORDER BY n.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    // 除錯資訊
    echo "<!-- Debug Info:\n";
    echo "Current Page: $page\n";
    echo "Total Pages: $totalPages\n";
    echo "Total Records: $total\n";
    echo "Offset: $offset\n";
    echo "SQL: " . $sql . "\n";
    echo "Where Clause: " . $whereClause . "\n";
    echo "Params: " . print_r($params, true) . "\n";
    echo "-->";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $news_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 獲取分類列表（用於篩選）
    $categories = $pdo->query("SELECT id, name FROM news_categories ORDER BY name")->fetchAll();
    
} catch (PDOException $e) {
    error_log('Error fetching news: ' . $e->getMessage());
    echo '<div class="alert alert-danger">錯誤：' . $e->getMessage() . '</div>';
    $news_list = [];
    $total = 0;
    $totalPages = 0;
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新聞管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* 基本布局 */
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
            padding-left: 250px;
        }

        .admin-main {
            flex: 1;
            padding: 80px 20px 20px 20px;
            min-height: 100vh;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            background-color: #f4f6f9;
            margin-left: 0;
            z-index: 1;
        }

        .content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        /* 頁面標題和麵包屑 */
        .content-header {
            margin-bottom: 20px;
        }

        .content-header h2 {
            margin: 0;
            font-size: 1.8em;
            color: #333;
        }

        .breadcrumb {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .breadcrumb a {
            color: #4a90e2;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* 搜尋和篩選 */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }

        /* 表格樣式 */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #333;
        }

        .table tr:hover {
            background-color: #f5f5f5;
        }

        /* 狀態標籤 */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-draft {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-published {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-archived {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* 操作按鈕 */
        .actions {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: white;
        }

        .btn-primary {
            background-color: #4a90e2;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-info {
            background-color: #17a2b8;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* 分頁 */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .page-info {
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 4px;
            color: #666;
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .admin-container {
                padding-left: 0;
            }

            .admin-main {
                padding-top: 60px;
                margin-left: 0;
            }

            .content {
                padding: 15px;
            }

            .search-form {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h2>新聞管理</h2>
                    <nav class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <span>新聞管理</span>
                    </nav>
                </div>

                <!-- 搜尋和篩選 -->
                <div class="filters">
                    <form method="get" class="search-form">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="搜尋標題或內容..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="category_id" class="form-control">
                                <option value="">所有分類</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="status" class="form-control">
                                <option value="">所有狀態</option>
                                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已發布</option>
                                <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>已封存</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0 0 auto;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜尋
                            </button>
                            <a href="add.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> 新增消息
                            </a>
                        </div>
                    </form>
                </div>

                <!-- 新聞列表 -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>標題</th>
                                <th>分類</th>
                                <th>狀態</th>
                                <th>發布時間</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($news_list)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">沒有找到任何新聞</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($news_list as $news): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($news['title']); ?>
                                            <?php if ($news['status'] === 'draft'): ?>
                                                <span class="badge badge-warning">草稿</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($news['category_name'] ?? '未分類'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $news['status']; ?>">
                                                <?php
                                                $status_text = [
                                                    'draft' => '草稿',
                                                    'published' => '已發布',
                                                    'archived' => '已封存'
                                                ];
                                                echo $status_text[$news['status']] ?? $news['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($news['created_at'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="edit.php?id=<?php echo $news['id']; ?>" 
                                                   class="btn btn-info" title="編輯">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($news['status'] === 'draft'): ?>
                                                    <a href="publish.php?id=<?php echo $news['id']; ?>" 
                                                       class="btn btn-success" 
                                                       onclick="return confirm('確定要發布此消息？')"
                                                       title="發布">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete.php?id=<?php echo $news['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('確定要刪除此消息？')"
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

                <!-- 分頁 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo $search ? "&search=$search" : ''; ?><?php echo $category_id ? "&category_id=$category_id" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-secondary">&laquo; 第一頁</a>
                            <a href="?page=<?php echo $page-1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category_id ? "&category_id=$category_id" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-secondary">&lsaquo; 上一頁</a>
                        <?php endif; ?>
                        
                        <span class="page-info">第 <?php echo $page; ?> 頁，共 <?php echo $totalPages; ?> 頁</span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category_id ? "&category_id=$category_id" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-secondary">下一頁 &rsaquo;</a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category_id ? "&category_id=$category_id" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-secondary">最後一頁 &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 
