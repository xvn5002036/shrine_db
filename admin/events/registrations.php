<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取活動 ID
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (!$event_id) {
    header('Location: index.php');
    exit;
}

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 處理搜尋和篩選
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

try {
    // 獲取活動資訊
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: index.php');
        exit;
    }

    // 構建查詢條件
    $where = ["r.event_id = ?"];
    $params = [$event_id];
    
    if ($search) {
        $where[] = "(r.name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status) {
        $where[] = "r.status = ?";
        $params[] = $status;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    
    // 獲取總記錄數
    $countSql = "
        SELECT COUNT(*) 
        FROM event_registrations r 
        $whereClause
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 計算總頁數
    $totalPages = ceil($total / $limit);
    if ($page > $totalPages) $page = $totalPages;
    if ($page < 1) $page = 1;
    
    // 獲取報名列表
    $sql = "
        SELECT r.*, u.username as user_name
        FROM event_registrations r 
        LEFT JOIN users u ON r.user_id = u.id 
        $whereClause 
        ORDER BY r.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registrations = $stmt->fetchAll();
    
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
    <title>報名管理 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-confirmed {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f5c6cb;
            color: #721c24;
        }

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

        .btn-danger {
            background-color: #dc3545;
        }

        .btn:hover {
            opacity: 0.9;
        }

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

        .event-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .event-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .event-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 0.9em;
        }

        .event-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

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
                    <h2>報名管理</h2>
                    <nav class="breadcrumb">
                        <a href="../index.php">首頁</a> /
                        <a href="index.php">活動管理</a> /
                        <span>報名管理</span>
                    </nav>
                </div>

                <!-- 活動資訊 -->
                <div class="event-info">
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <div class="event-meta">
                        <span>
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo date('Y/m/d H:i', strtotime($event['start_date'])); ?>
                        </span>
                        <span>
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($event['location']); ?>
                        </span>
                        <span>
                            <i class="fas fa-users"></i>
                            報名人數：<?php echo $total; ?> 人
                            <?php if ($event['max_participants']): ?>
                                / 上限 <?php echo $event['max_participants']; ?> 人
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- 搜尋和篩選 -->
                <div class="filters">
                    <form method="get" class="search-form">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="搜尋姓名、Email或電話..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="status" class="form-control">
                                <option value="">所有狀態</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待確認</option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>已確認</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0 0 auto;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜尋
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 報名列表 -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>姓名</th>
                                <th>Email</th>
                                <th>電話</th>
                                <th>報名時間</th>
                                <th>狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrations)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">沒有找到任何報名記錄</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrations as $registration): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($registration['name']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['phone']); ?></td>
                                        <td><?php echo date('Y/m/d H:i', strtotime($registration['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $registration['status']; ?>">
                                                <?php
                                                $status_text = [
                                                    'pending' => '待確認',
                                                    'confirmed' => '已確認',
                                                    'cancelled' => '已取消'
                                                ];
                                                echo $status_text[$registration['status']] ?? $registration['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <?php if ($registration['status'] === 'pending'): ?>
                                                    <a href="confirm_registration.php?id=<?php echo $registration['id']; ?>" 
                                                       class="btn btn-success" title="確認報名">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($registration['status'] !== 'cancelled'): ?>
                                                    <a href="cancel_registration.php?id=<?php echo $registration['id']; ?>" 
                                                       class="btn btn-danger" 
                                                       onclick="return confirm('確定要取消此報名？')"
                                                       title="取消報名">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete_registration.php?id=<?php echo $registration['id']; ?>" 
                                                   class="btn btn-danger" title="刪除報名"
                                                   onclick="return confirm('確定要刪除此報名記錄嗎？此操作無法復原！');">
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
                            <a href="?event_id=<?php echo $event_id; ?>&page=1<?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="page-link">&laquo; 第一頁</a>
                            <a href="?event_id=<?php echo $event_id; ?>&page=<?php echo $page-1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="page-link">&lsaquo; 上一頁</a>
                        <?php endif; ?>
                        
                        <span class="page-info">第 <?php echo $page; ?> 頁，共 <?php echo $totalPages; ?> 頁</span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?event_id=<?php echo $event_id; ?>&page=<?php echo $page+1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="page-link">下一頁 &rsaquo;</a>
                            <a href="?event_id=<?php echo $event_id; ?>&page=<?php echo $totalPages; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $status ? "&status=$status" : ''; ?>" 
                               class="page-link">最後一頁 &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 