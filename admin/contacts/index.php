<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取分頁參數
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 獲取篩選參數
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
    }
    
    $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM contacts $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    
    // 計算總頁數
    $total_pages = ceil($total_records / $per_page);
    
    // 獲取聯絡表單列表
    $sql = "SELECT c.*, u.username as replied_by_name 
            FROM contacts c 
            LEFT JOIN users u ON c.replied_by = u.id 
            $where_clause 
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $contacts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Error fetching contacts: ' . $e->getMessage());
    setFlashMessage('error', '獲取聯絡表單列表時發生錯誤');
    $contacts = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聯絡表單管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php 
        $admin_base = '../';  // 定義後台基礎路徑
        include '../includes/sidebar.php'; 
        ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h2>聯絡表單管理</h2>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <div class="content-body">
                    <!-- 搜尋和篩選 -->
                    <div class="filter-section">
                        <form method="get" class="filter-form">
                            <div class="form-group">
                                <input type="text" name="search" placeholder="搜尋..." 
                                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
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

                    <!-- 聯絡表單列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>姓名</th>
                                    <th>電子郵件</th>
                                    <th>主旨</th>
                                    <th>狀態</th>
                                    <th>建立時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?php echo $contact['id']; ?></td>
                                    <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $contact['status']; ?>">
                                            <?php
                                            $status_text = [
                                                'unread' => '未讀',
                                                'read' => '已讀',
                                                'replied' => '已回覆',
                                                'archived' => '已封存'
                                            ];
                                            echo $status_text[$contact['status']] ?? $contact['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($contact['created_at'])); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="reply.php?id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="deleteContact(<?php echo $contact['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($contacts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">沒有找到聯絡表單記錄</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁 -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>" 
                               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    function deleteContact(id) {
        if (confirm('確定要刪除這個聯絡表單記錄嗎？此操作無法復原。')) {
            window.location.href = 'delete.php?id=' + id;
        }
    }
    </script>
</body>
</html> 