<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供活動 ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$event_id = (int)$_GET['id'];

// 獲取活動詳細資訊
try {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               et.name as event_type_name,
               a1.username as created_by_name,
               a2.username as updated_by_name
        FROM events e 
        LEFT JOIN event_types et ON e.event_type_id = et.id
        LEFT JOIN admins a1 ON e.created_by = a1.id
        LEFT JOIN admins a2 ON e.updated_by = a2.id
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: index.php');
        exit;
    }

    // 獲取報名者列表
    $stmt = $pdo->prepare("
        SELECT er.*, u.name as user_name, u.email, u.phone
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        WHERE er.event_id = ?
        ORDER BY er.registration_date DESC
    ");
    $stmt->execute([$event_id]);
    $registrations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching event details: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 活動詳情</title>
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
                    <h2>活動詳情</h2>
                    <div class="header-actions">
                        <a href="edit.php?id=<?php echo $event_id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編輯活動
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="event-details">
                        <div class="detail-section">
                            <h3>基本資訊</h3>
                            <table class="detail-table">
                                <tr>
                                    <th>活動名稱：</th>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                </tr>
                                <tr>
                                    <th>活動類型：</th>
                                    <td><?php echo htmlspecialchars($event['event_type_name'] ?? '未分類'); ?></td>
                                </tr>
                                <tr>
                                    <th>活動日期：</th>
                                    <td><?php echo date('Y-m-d', strtotime($event['event_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>活動時間：</th>
                                    <td><?php echo date('H:i', strtotime($event['event_time'])); ?></td>
                                </tr>
                                <tr>
                                    <th>活動地點：</th>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                </tr>
                                <tr>
                                    <th>參加人數：</th>
                                    <td>
                                        <?php echo $event['current_participants']; ?> / 
                                        <?php echo $event['max_participants'] ? $event['max_participants'] : '不限'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>活動狀態：</th>
                                    <td>
                                        <span class="status-badge status-<?php echo $event['status']; ?>">
                                            <?php
                                            $status_map = [
                                                'draft' => '草稿',
                                                'published' => '已發布',
                                                'cancelled' => '已取消',
                                                'completed' => '已結束'
                                            ];
                                            echo $status_map[$event['status']] ?? $event['status'];
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php if ($event['image']): ?>
                            <div class="detail-section">
                                <h3>活動圖片</h3>
                                <div class="event-image">
                                    <img src="../../<?php echo htmlspecialchars($event['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($event['title']); ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="detail-section">
                            <h3>活動描述</h3>
                            <div class="event-description">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h3>系統資訊</h3>
                            <table class="detail-table">
                                <tr>
                                    <th>建立者：</th>
                                    <td><?php echo htmlspecialchars($event['created_by_name'] ?? '系統'); ?></td>
                                </tr>
                                <tr>
                                    <th>建立時間：</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($event['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>最後更新者：</th>
                                    <td><?php echo htmlspecialchars($event['updated_by_name'] ?? '系統'); ?></td>
                                </tr>
                                <tr>
                                    <th>最後更新時間：</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($event['updated_at'])); ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="detail-section">
                            <h3>報名者列表</h3>
                            <?php if (empty($registrations)): ?>
                                <p class="no-data">目前還沒有人報名</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>報名者</th>
                                                <th>Email</th>
                                                <th>電話</th>
                                                <th>報名時間</th>
                                                <th>狀態</th>
                                                <th>備註</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($registrations as $registration): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($registration['user_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($registration['phone']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($registration['registration_date'])); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $registration['status']; ?>">
                                                            <?php
                                                            $reg_status_map = [
                                                                'pending' => '待確認',
                                                                'confirmed' => '已確認',
                                                                'cancelled' => '已取消'
                                                            ];
                                                            echo $reg_status_map[$registration['status']] ?? $registration['status'];
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($registration['notes'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 