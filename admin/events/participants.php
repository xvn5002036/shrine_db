<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 處理CSV下載
if (isset($_GET['download_csv'])) {
    // 設置header
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=event_participants_' . date('Y-m-d') . '.csv');
    
    // 創建輸出流
    $output = fopen('php://output', 'w');
    
    // 寫入BOM，解決中文亂碼
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // 寫入CSV標題
    fputcsv($output, array(
        'ID',
        '參與者姓名',
        '使用者帳號',
        '參與人數',
        '聯絡電話',
        '電子郵件',
        '報名時間',
        '狀態',
        '備註'
    ));
    
    // 獲取活動參與者資料
    $stmt = $pdo->prepare("
        SELECT r.*, u.username as user_name
        FROM event_registrations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.event_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$id]);
    
    // 寫入數據
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_text = match($row['status']) {
            'pending' => '待確認',
            'confirmed' => '已確認',
            'cancelled' => '已取消',
            default => '未知'
        };
        
        fputcsv($output, array(
            $row['id'],
            $row['name'],
            $row['user_name'],
            $row['participants'],
            $row['phone'],
            $row['email'],
            $row['created_at'],
            $status_text,
            $row['notes']
        ));
    }
    
    fclose($output);
    exit();
}

// 獲取活動 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // 獲取活動資訊
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: index.php');
        exit;
    }

    // 獲取報名統計資訊
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_registrations,
            SUM(CASE WHEN status = 'confirmed' THEN participants ELSE 0 END) as confirmed_participants,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM event_registrations 
        WHERE event_id = ?
    ");
    $stmt->execute([$id]);
    $stats = $stmt->fetch();

    // 獲取報名列表
    $stmt = $pdo->prepare("
        SELECT r.*, u.username as user_name
        FROM event_registrations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.event_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$id]);
    $registrations = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error: ' . $e->getMessage());
    die('系統錯誤，請稍後再試。');
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動參與者管理 - <?php echo htmlspecialchars($event['title']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Admin Style -->
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-main {
            flex: 1;
            padding: 20px;
            width: 100%;
            min-height: 100vh;
            background-color: #f4f6f9;
        }

        @media (max-width: 768px) {
            .admin-main {
                width: 100%;
            }
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-top: 0.5rem;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { 
            background: rgba(255, 215, 0, 0.2); 
            color: #856404;
        }
        .status-confirmed { 
            background: rgba(40, 167, 69, 0.2); 
            color: #155724;
        }
        .status-cancelled { 
            background: rgba(220, 53, 69, 0.2); 
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .action-buttons a {
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .action-buttons a:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .btn-confirm { 
            background: #28a745; 
        }
        .btn-confirm:hover { 
            background: #218838; 
        }
        .btn-cancel { 
            background: #dc3545; 
        }
        .btn-cancel:hover { 
            background: #c82333; 
        }
        .event-info {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .event-info p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        .event-info i {
            width: 20px;
            color: #4a90e2;
            margin-right: 0.5rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .table {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 1rem;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .card-body {
            padding: 1.5rem;
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php require_once '../includes/header.php'; ?>
            
            <div class="container-fluid">
                <div class="page-header">
                    <div class="toolbar">
                        <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($event['title']); ?> - 參與者管理</h1>
                        <div class="d-flex align-items-center">
                            <a href="?id=<?php echo $id; ?>&download_csv=1" class="btn btn-success me-2">
                                <i class="fas fa-download"></i> 下載CSV
                            </a>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="../index.php">首頁</a></li>
                                    <li class="breadcrumb-item"><a href="index.php">活動管理</a></li>
                                    <li class="breadcrumb-item active">參與者管理</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- 活動資訊 -->
                <div class="event-info mb-4">
                    <div class="row">
                        <div class="col">
                            <p><i class="fas fa-calendar-alt"></i> 活動時間：<?php echo date('Y/m/d H:i', strtotime($event['start_date'])); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> 活動地點：<?php echo htmlspecialchars($event['location']); ?></p>
                        </div>
                        <div class="col">
                            <p><i class="fas fa-users"></i> 報名上限：<?php echo $event['max_participants'] ? $event['max_participants'] . ' 人' : '不限'; ?></p>
                            <p><i class="fas fa-clock"></i> 報名截止：<?php echo $event['registration_deadline'] ? date('Y/m/d H:i', strtotime($event['registration_deadline'])) : '無'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- 統計資訊 -->
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>總報名人數</h3>
                        <div class="number"><?php echo $stats['total_registrations']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>確認參與人數</h3>
                        <div class="number"><?php echo $stats['confirmed_participants']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>待確認報名</h3>
                        <div class="number"><?php echo $stats['pending_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>已取消報名</h3>
                        <div class="number"><?php echo $stats['cancelled_count']; ?></div>
                    </div>
                </div>

                <!-- 參與者列表 -->
                <div class="card">
                    <div class="card-header">
                        <h2>參與者列表</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>報名時間</th>
                                        <th>姓名</th>
                                        <th>電話</th>
                                        <th>Email</th>
                                        <th>參加人數</th>
                                        <th>狀態</th>
                                        <th>備註</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($registrations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">尚無報名記錄</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($registrations as $reg): ?>
                                        <tr>
                                            <td><?php echo date('Y/m/d H:i', strtotime($reg['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                            <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                            <td><?php echo $reg['participants']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $reg['status']; ?>">
                                                    <?php
                                                    $status_text = [
                                                        'pending' => '待確認',
                                                        'confirmed' => '已確認',
                                                        'cancelled' => '已取消'
                                                    ];
                                                    echo $status_text[$reg['status']] ?? $reg['status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($reg['notes'] ?? ''); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($reg['status'] === 'pending'): ?>
                                                    <a href="confirm_registration.php?id=<?php echo $reg['id']; ?>" 
                                                       class="btn-confirm" 
                                                       onclick="return confirm('確定要確認此報名嗎？')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="delete_registration.php?id=<?php echo $reg['id']; ?>" 
                                                       class="btn-cancel"
                                                       onclick="return confirm('確定要刪除此報名記錄嗎？')">
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
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 
