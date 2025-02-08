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
$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

try {
    // 獲取活動類型列表
    $stmt = $pdo->query("SELECT id, name FROM event_types WHERE status = 'active' ORDER BY sort_order");
    $event_types = $stmt->fetchAll();

    // 構建查詢條件
    $where = [];
    $params = [];
    
    if ($search) {
        $where[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($type_id) {
        $where[] = "e.event_type_id = ?";
        $params[] = $type_id;
    }
    
    if ($status !== '') {
        $where[] = "e.status = ?";
        $params[] = $status;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 獲取總記錄數
    $countSql = "
        SELECT COUNT(*) 
        FROM events e 
        LEFT JOIN event_types t ON e.event_type_id = t.id 
        $whereClause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 計算總頁數
    $totalPages = ceil($total / $limit);
    if ($page > $totalPages) $page = $totalPages;
    if ($page < 1) $page = 1;
    
    // 獲取活動列表
    $sql = "
        SELECT e.*, t.name as type_name,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'confirmed') as confirmed_participants
        FROM events e 
        LEFT JOIN event_types t ON e.event_type_id = t.id 
        $whereClause 
        ORDER BY e.start_date DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Error: ' . $e->getMessage());
    $error = '系統錯誤：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* 主要布局 */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-main {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            background-color: #f4f6f9;
        }

        .content {
            padding: 20px;
            margin-top: 60px;
        }

        /* 搜尋和篩選區域 */
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

        .status-active {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-inactive {
            background-color: #f5c6cb;
            color: #721c24;
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

        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #4a90e2;
            text-decoration: none;
        }

        .page-link.active {
            background: #4a90e2;
            color: white;
            border-color: #4a90e2;
        }

        .page-info {
            padding: 8px 12px;
            color: #666;
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .content {
                padding: 10px;
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
                    <h2>活動管理</h2>
                    <nav class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <span>活動管理</span>
                    </nav>
                </div>

                <!-- 搜尋和篩選 -->
                <div class="filters">
                    <form method="get" class="search-form">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="搜尋活動標題、說明或地點..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="type_id" class="form-control">
                                <option value="">所有類型</option>
                                <?php foreach ($event_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" 
                                            <?php echo $type_id == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="status" class="form-control">
                                <option value="">所有狀態</option>
                                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>進行中</option>
                                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>已結束</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0 0 auto;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜尋
                            </button>
                            <a href="add.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> 新增活動
                            </a>
                        </div>
                    </form>
                </div>

                <!-- 活動列表 -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>活動標題</th>
                                <th>類型</th>
                                <th>時間</th>
                                <th>地點</th>
                                <th>報名狀況</th>
                                <th>狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">沒有找到任何活動</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars($event['type_name'] ?? '未分類'); ?></td>
                                        <td>
                                            <?php 
                                            echo date('Y/m/d H:i', strtotime($event['start_date']));
                                            if ($event['start_date'] != $event['end_date']) {
                                                echo '<br>至<br>' . date('Y/m/d H:i', strtotime($event['end_date']));
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td>
                                            <?php
                                            if ($event['max_participants']) {
                                                echo $event['confirmed_participants'] . ' / ' . $event['max_participants'];
                                            } else {
                                                echo $event['confirmed_participants'] . ' 人';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $event['status'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $event['status'] ? '進行中' : '已結束'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="edit.php?id=<?php echo $event['id']; ?>" 
                                                   class="btn btn-info" title="編輯">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="registrations.php?event_id=<?php echo $event['id']; ?>" 
                                                   class="btn btn-primary" title="報名管理">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $event['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('確定要刪除此活動？')"
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
                            <a href="?page=1<?php echo $search ? "&search=$search" : ''; ?><?php echo $type_id ? "&type_id=$type_id" : ''; ?><?php echo $status !== '' ? "&status=$status" : ''; ?>" 
                               class="page-link">&laquo; 第一頁</a>
                            <a href="?page=<?php echo $page-1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $type_id ? "&type_id=$type_id" : ''; ?><?php echo $status !== '' ? "&status=$status" : ''; ?>" 
                               class="page-link">&lsaquo; 上一頁</a>
                        <?php endif; ?>
                        
                        <span class="page-info">第 <?php echo $page; ?> 頁，共 <?php echo $totalPages; ?> 頁</span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $type_id ? "&type_id=$type_id" : ''; ?><?php echo $status !== '' ? "&status=$status" : ''; ?>" 
                               class="page-link">下一頁 &rsaquo;</a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $type_id ? "&type_id=$type_id" : ''; ?><?php echo $status !== '' ? "&status=$status" : ''; ?>" 
                               class="page-link">最後一頁 &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 