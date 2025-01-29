<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取基本統計資料
try {
    $stats = [];
    
    // 檢查表是否存在並獲取統計數據
    $tables_to_check = [
        'users' => "SELECT COUNT(*) FROM users",
        'news' => "SELECT COUNT(*) FROM news WHERE status = 'published'",
        'events' => "SELECT COUNT(*) FROM events WHERE status = 'published'",
        'prayer_requests' => "SELECT COUNT(*) FROM prayer_requests WHERE status = 'pending'",
        'contact_messages' => "SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'",
        'gallery_images' => "SELECT COUNT(*) FROM gallery_images"
    ];

    foreach ($tables_to_check as $key => $query) {
        try {
            $stmt = $pdo->query($query);
            $stats['total_' . $key] = $stmt ? $stmt->fetchColumn() : 0;
        } catch (PDOException $e) {
            error_log("Error counting {$key}: " . $e->getMessage());
            $stats['total_' . $key] = 0;
        }
    }
} catch (PDOException $e) {
    error_log("Error getting stats: " . $e->getMessage());
    $stats = [
        'total_users' => 0,
        'total_news' => 0,
        'total_events' => 0,
        'total_prayer_requests' => 0,
        'total_contact_messages' => 0,
        'total_gallery_images' => 0
    ];
}

// 獲取最新消息
try {
    $latest_news = $pdo->query("
        SELECT n.*, nc.name as category_name, a.username as author_name 
        FROM news n 
        LEFT JOIN news_categories nc ON n.category_id = nc.id
        LEFT JOIN admins a ON n.created_by = a.id 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting latest news: " . $e->getMessage());
    $latest_news = [];
}

// 獲取最新活動
try {
    $latest_events = $pdo->query("
        SELECT e.*, et.name as event_type_name 
        FROM events e 
        LEFT JOIN event_types et ON e.event_type_id = et.id
        WHERE e.event_date >= CURRENT_DATE 
        ORDER BY e.event_date ASC 
        LIMIT 3
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting latest events: " . $e->getMessage());
    $latest_events = [];
}

// 獲取最新祈福請求
try {
    $latest_prayers = $pdo->query("
        SELECT pr.*, pt.name as prayer_type_name, u.name as user_name
        FROM prayer_requests pr 
        LEFT JOIN prayer_types pt ON pr.prayer_type_id = pt.id
        LEFT JOIN users u ON pr.user_id = u.id
        WHERE pr.status = 'pending' 
        ORDER BY pr.created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting prayer requests: " . $e->getMessage());
    $latest_prayers = [];
}

// 獲取最新聯絡訊息
try {
    $latest_contacts = $pdo->query("
        SELECT * FROM contact_messages 
        WHERE status = 'unread' 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting contact messages: " . $e->getMessage());
    $latest_contacts = [];
}

// 獲取系統資訊
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'os' => PHP_OS,
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_execution_time' => ini_get('max_execution_time')
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 後台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="dashboard">
                    <!-- 快速操作按鈕 -->
                    <div class="quick-actions">
                        <a href="news/add.php" class="quick-action-btn">
                            <i class="fas fa-plus"></i>
                            新增消息
                        </a>
                        <a href="events/add.php" class="quick-action-btn">
                            <i class="fas fa-calendar-plus"></i>
                            新增活動
                        </a>
                        <a href="gallery/upload.php" class="quick-action-btn">
                            <i class="fas fa-upload"></i>
                            上傳圖片
                        </a>
                        <a href="backup.php" class="quick-action-btn">
                            <i class="fas fa-database"></i>
                            系統備份
                        </a>
                    </div>

                    <!-- 統計卡片 -->
                    <div class="stat-cards">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3>用戶總數</h3>
                                <p><?php echo number_format($stats['total_users']); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="stat-info">
                                <h3>已發布消息</h3>
                                <p><?php echo number_format($stats['total_news']); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3>進行中活動</h3>
                                <p><?php echo number_format($stats['total_events']); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-pray"></i>
                            </div>
                            <div class="stat-info">
                                <h3>待處理祈福</h3>
                                <p><?php echo number_format($stats['total_prayer_requests']); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-info">
                                <h3>未讀訊息</h3>
                                <p><?php echo number_format($stats['total_contact_messages']); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-images"></i>
                            </div>
                            <div class="stat-info">
                                <h3>圖片總數</h3>
                                <p><?php echo number_format($stats['total_gallery_images']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- 最新動態 -->
                    <div class="dashboard-sections">
                        <!-- 最新消息 -->
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h2>最新消息</h2>
                                <a href="news/" class="btn btn-sm btn-primary">查看全部</a>
                            </div>
                            <div class="section-content">
                                <?php if (empty($latest_news)): ?>
                                    <p class="no-data">目前沒有最新消息</p>
                                <?php else: ?>
                                    <div class="news-list">
                                        <?php foreach ($latest_news as $news): ?>
                                            <div class="news-item">
                                                <div class="news-meta">
                                                    <span class="news-category"><?php echo htmlspecialchars($news['category_name'] ?? '未分類'); ?></span>
                                                    <span class="news-date"><?php echo date('Y-m-d', strtotime($news['created_at'])); ?></span>
                                                </div>
                                                <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                                                <div class="news-author">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($news['author_name'] ?? '系統'); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 近期活動 -->
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h2>近期活動</h2>
                                <a href="events/" class="btn btn-sm btn-primary">查看全部</a>
                            </div>
                            <div class="section-content">
                                <?php if (empty($latest_events)): ?>
                                    <p class="no-data">目前沒有近期活動</p>
                                <?php else: ?>
                                    <div class="events-grid">
                                        <?php foreach ($latest_events as $event): ?>
                                            <div class="event-card">
                                                <div class="event-date">
                                                    <span class="date"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                                    <span class="month"><?php echo date('m月', strtotime($event['event_date'])); ?></span>
                                                </div>
                                                <div class="event-info">
                                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                                    <p class="event-type"><?php echo htmlspecialchars($event['event_type_name'] ?? '未分類'); ?></p>
                                                    <p class="event-time">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                                    </p>
                                                    <p class="event-location">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?php echo htmlspecialchars($event['location']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 待處理祈福 -->
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h2>待處理祈福</h2>
                                <a href="prayers/" class="btn btn-sm btn-primary">查看全部</a>
                            </div>
                            <div class="section-content">
                                <?php if (empty($latest_prayers)): ?>
                                    <p class="no-data">目前沒有待處理的祈福請求</p>
                                <?php else: ?>
                                    <div class="prayers-list">
                                        <?php foreach ($latest_prayers as $prayer): ?>
                                            <div class="prayer-item">
                                                <div class="prayer-info">
                                                    <span class="prayer-type"><?php echo htmlspecialchars($prayer['prayer_type_name']); ?></span>
                                                    <span class="prayer-date"><?php echo date('Y-m-d', strtotime($prayer['created_at'])); ?></span>
                                                </div>
                                                <div class="prayer-user">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($prayer['user_name']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 未讀訊息 -->
                        <div class="dashboard-section">
                            <div class="section-header">
                                <h2>未讀訊息</h2>
                                <a href="contacts/" class="btn btn-sm btn-primary">查看全部</a>
                            </div>
                            <div class="section-content">
                                <?php if (empty($latest_contacts)): ?>
                                    <p class="no-data">目前沒有未讀訊息</p>
                                <?php else: ?>
                                    <div class="messages-list">
                                        <?php foreach ($latest_contacts as $contact): ?>
                                            <div class="message-item">
                                                <div class="message-header">
                                                    <span class="message-from"><?php echo htmlspecialchars($contact['name']); ?></span>
                                                    <span class="message-date"><?php echo date('Y-m-d H:i', strtotime($contact['created_at'])); ?></span>
                                                </div>
                                                <div class="message-subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 系統資訊 -->
                        <div class="dashboard-section system-info">
                            <div class="section-header">
                                <h2>系統資訊</h2>
                            </div>
                            <div class="section-content">
                                <table class="info-table">
                                    <tr>
                                        <th>PHP 版本：</th>
                                        <td><?php echo htmlspecialchars($system_info['php_version']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>MySQL 版本：</th>
                                        <td><?php echo htmlspecialchars($system_info['mysql_version']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>作業系統：</th>
                                        <td><?php echo htmlspecialchars($system_info['os']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Web 伺服器：</th>
                                        <td><?php echo htmlspecialchars($system_info['server_software']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>記憶體限制：</th>
                                        <td><?php echo htmlspecialchars($system_info['memory_limit']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>上傳限制：</th>
                                        <td><?php echo htmlspecialchars($system_info['upload_max_filesize']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>執行時限：</th>
                                        <td><?php echo htmlspecialchars($system_info['max_execution_time']); ?> 秒</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html> 