<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 處理搜尋和篩選
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($search) {
        $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $countSql = "SELECT COUNT(*) FROM contact_messages $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 計算總頁數
    $totalPages = ceil($total / $limit);
    
    // 確保當前頁碼有效
    if ($page < 1) $page = 1;
    if ($page > $totalPages) $page = $totalPages;
    
    // 獲取訊息列表
    $sql = "
        SELECT m.*, u.username as replied_by_name 
        FROM contact_messages m 
        LEFT JOIN users u ON m.replied_by = u.id 
        $whereClause 
        ORDER BY m.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Error fetching contact messages: ' . $e->getMessage());
    setFlashMessage('error', '獲取訊息列表時發生錯誤');
    $messages = [];
    $total = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聯絡訊息管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php 
        $admin_base = '../';
        include '../includes/sidebar.php'; 
        ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h2>聯絡訊息管理</h2>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <div class="content-body">
                    <!-- 搜尋和篩選 -->
                    <div class="filters">
                        <form method="get" class="search-form">
                            <div class="form-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="搜尋名稱、信箱或主旨..." class="form-control">
                            </div>
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="">所有狀態</option>
                                    <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>未讀</option>
                                    <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>已讀</option>
                                    <option value="replied" <?php echo $status === 'replied' ? 'selected' : ''; ?>>已回覆</option>
                                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>已封存</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜尋
                            </button>
                        </form>
                    </div>

                    <!-- 批量操作 -->
                    <form method="post" action="bulk-action.php" id="messages-form">
                        <div class="bulk-actions">
                            <select name="action" class="form-control">
                                <option value="">批量操作</option>
                                <option value="mark-read">標記為已讀</option>
                                <option value="mark-archived">封存</option>
                                <option value="delete">刪除</option>
                            </select>
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('確定要執行所選操作？')">
                                執行
                            </button>
                        </div>

                        <!-- 訊息列表 -->
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="select-all">
                                        </th>
                                        <th>寄件者</th>
                                        <th>主旨</th>
                                        <th>狀態</th>
                                        <th>建立時間</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">沒有找到任何訊息</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): ?>
                                        <tr class="<?php echo $message['status'] === 'unread' ? 'unread' : ''; ?>">
                                            <td>
                                                <input type="checkbox" name="ids[]" value="<?php echo $message['id']; ?>">
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($message['name']); ?><br>
                                                <small><?php echo htmlspecialchars($message['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $message['status']; ?>">
                                                    <?php
                                                    $status_text = [
                                                        'unread' => '未讀',
                                                        'read' => '已讀',
                                                        'replied' => '已回覆',
                                                        'archived' => '已封存'
                                                    ];
                                                    echo $status_text[$message['status']] ?? $message['status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a href="view.php?id=<?php echo $message['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="查看">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="reply.php?id=<?php echo $message['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="回覆">
                                                        <i class="fas fa-reply"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $message['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('確定要刪除此訊息？')" title="刪除">
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
                    </form>

                    <!-- 分頁 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-sm btn-secondary">&laquo; 第一頁</a>
                            <a href="?page=<?php echo $page-1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-sm btn-secondary">&lsaquo; 上一頁</a>
                        <?php endif; ?>
                        
                        <span class="page-info">第 <?php echo $page; ?> 頁，共 <?php echo $totalPages; ?> 頁</span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-sm btn-secondary">下一頁 &rsaquo;</a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="btn btn-sm btn-secondary">最後一頁 &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
    .filters {
        margin-bottom: 20px;
    }

    .search-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .bulk-actions {
        margin-bottom: 15px;
        display: flex;
        gap: 10px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
        background-color: white;
    }

    .table th,
    .table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 500;
    }

    .unread {
        background-color: #fff8e1;
    }

    .actions {
        display: flex;
        gap: 5px;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.9em;
    }

    .status-unread {
        background: #ffc107;
        color: #000;
    }

    .status-read {
        background: #17a2b8;
        color: #fff;
    }

    .status-replied {
        background: #28a745;
        color: #fff;
    }

    .status-archived {
        background: #6c757d;
        color: #fff;
    }

    .pagination {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }

    .page-info {
        padding: 5px 10px;
        background: #f8f9fa;
        border-radius: 3px;
    }
    </style>

    <script>
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });

    document.getElementById('messages-form').addEventListener('submit', function(e) {
        const action = document.querySelector('select[name="action"]').value;
        const checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
        
        if (!action) {
            e.preventDefault();
            alert('請選擇要執行的操作');
            return;
        }
        
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('請至少選擇一個訊息');
            return;
        }
    });
    </script>
</body>
</html> 