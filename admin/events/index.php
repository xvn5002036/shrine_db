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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f4f6f9;
        }

        .page-header {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-form {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .table-container {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .btn-toolbar {
            display: flex;
            gap: 10px;
        }

        .breadcrumb {
            margin-bottom: 0;
            padding: 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="toolbar">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">首頁</a></li>
                        <li class="breadcrumb-item active">活動管理</li>
                    </ol>
                </nav>
                <div class="btn-toolbar">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新增活動
                    </a>
                </div>
            </div>
        </div>

        <!-- 搜尋表單 -->
        <div class="search-form">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" 
                           placeholder="搜尋活動標題、說明或地點" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type_id">
                        <option value="">所有類型</option>
                        <?php foreach ($event_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                <?php echo $type_id == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">所有狀態</option>
                        <option value="進行中" <?php echo $status === '進行中' ? 'selected' : ''; ?>>進行中</option>
                        <option value="已結束" <?php echo $status === '已結束' ? 'selected' : ''; ?>>已結束</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">搜尋</button>
                </div>
            </form>
        </div>

        <!-- 活動列表 -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
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
                                <td colspan="7" class="text-center">沒有找到相關活動</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['type_name']); ?></td>
                                    <td>
                                        <?php 
                                        echo date('Y/m/d H:i', strtotime($event['start_date']));
                                        echo '<br>至<br>';
                                        echo date('Y/m/d H:i', strtotime($event['end_date']));
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td>
                                        <?php 
                                        echo $event['confirmed_participants'] . ' / ';
                                        echo $event['max_participants'] ?: '不限';
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $event['status'] === '進行中' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $event['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit.php?id=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-info" title="編輯">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="participants.php?id=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="報名管理">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('確定要刪除此活動嗎？')" 
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
        </div>

        <!-- 分頁 -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&type_id=<?php echo $type_id; ?>&status=<?php echo urlencode($status); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type_id=<?php echo $type_id; ?>&status=<?php echo urlencode($status); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&type_id=<?php echo $type_id; ?>&status=<?php echo urlencode($status); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php require_once '../templates/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 