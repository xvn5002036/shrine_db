<?php
require_once '../config/config.php';
require_once '../config/database.php';
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
        'blessing_registrations' => "SELECT COUNT(*) FROM blessing_registrations WHERE status = 'pending'",
        'contact_messages' => "SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'",
        'gallery_images' => "SELECT COUNT(*) FROM gallery_images"
    ];

    foreach ($tables_to_check as $key => $query) {
        try {
            $stmt = $pdo->query($query);
            $stats[$key] = $stmt ? $stmt->fetchColumn() : 0;
        } catch (PDOException $e) {
            error_log("Error counting {$key}: " . $e->getMessage());
            $stats[$key] = 0;
        }
    }
} catch (PDOException $e) {
    error_log("Error getting stats: " . $e->getMessage());
    $stats = [
        'users' => 0,
        'news' => 0,
        'events' => 0,
        'blessing_registrations' => 0,
        'contact_messages' => 0,
        'gallery_images' => 0
    ];
}

// 獲取最新消息
try {
    $latest_news = $pdo->query("
        SELECT n.*, nc.name as category_name, u.username as author_name 
        FROM news n 
        LEFT JOIN news_categories nc ON n.category_id = nc.id
        LEFT JOIN users u ON n.created_by = u.id 
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
        WHERE e.status = 1 
        AND e.end_date >= CURRENT_TIMESTAMP 
        ORDER BY e.start_date ASC 
        LIMIT 3
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting latest events: " . $e->getMessage());
    $latest_events = [];
}

// 獲取最新祈福請求
try {
    $latest_blessings = $pdo->query("
        SELECT br.*, b.name as blessing_name, b.price, bt.name as type_name
        FROM blessing_registrations br
        LEFT JOIN blessings b ON br.blessing_id = b.id
        LEFT JOIN blessing_types bt ON b.type_id = bt.id
        WHERE br.status = 'pending'
        ORDER BY br.created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting blessing requests: " . $e->getMessage());
    $latest_blessings = [];
}

// 獲取最新聯絡訊息
try {
    $stmt = $pdo->query("
        SELECT * FROM contact_messages 
        WHERE status = 'unread' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_contacts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting recent contacts: " . $e->getMessage());
    $recent_contacts = [];
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
            padding-left: 250px; /* 側邊欄寬度 */
        }

        .admin-main {
            flex: 1;
            padding: 80px 20px 20px 20px; /* 上方增加 padding 避免被頂部欄遮蓋 */
            min-height: 100vh;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            background-color: #f4f6f9;
        }

        .content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* 快速操作按鈕 */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #333;
            transition: transform 0.2s;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
        }

        /* 統計卡片 */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #f0f4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: #4a90e2;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 0.9em;
            color: #666;
        }

        .stat-info p {
            margin: 5px 0 0;
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .dashboard-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            height: fit-content;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.2em;
            color: #333;
            font-weight: 600;
        }

        .news-list, .events-grid, .prayers-list, .messages-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .news-item, .prayer-item, .message-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .news-meta, .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9em;
            color: #666;
        }

        .news-category, .message-from {
            color: #4a90e2;
            font-weight: 500;
        }

        .news-title, .message-subject {
            font-weight: 500;
            color: #333;
            margin: 8px 0;
        }

        .news-author, .message-preview {
            font-size: 0.9em;
            color: #666;
        }

        .event-card {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .event-date {
            text-align: center;
            min-width: 60px;
            padding: 8px;
            background: #4a90e2;
            color: white;
            border-radius: 6px;
        }

        .event-date .date {
            font-size: 1.5em;
            font-weight: bold;
            line-height: 1;
        }

        .event-date .month {
            font-size: 0.8em;
            opacity: 0.9;
        }

        .event-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.1em;
            color: #333;
        }

        .event-type {
            color: #4a90e2;
            font-size: 0.9em;
            margin: 0 0 5px 0;
        }

        .event-time, .event-location {
            font-size: 0.9em;
            color: #666;
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.9em;
            border-radius: 4px;
        }

        .system-info {
            grid-column: span 2;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table th, .info-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .info-table th {
            text-align: left;
            color: #666;
            width: 200px;
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.9em;
        }

        /* 響應式設計 */
        @media (max-width: 1200px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
            
            .system-info {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                padding-left: 0;
            }

            .admin-main {
                padding: 70px 15px 15px 15px;
            }

            .stat-cards {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 0 10px;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
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
                            <p><?php echo number_format($stats['users']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div class="stat-info">
                            <h3>已發布消息</h3>
                            <p><?php echo number_format($stats['news']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>進行中活動</h3>
                            <p><?php echo number_format($stats['events']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3>未讀訊息</h3>
                            <p><?php echo number_format($stats['contact_messages']); ?></p>
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
                                                <span class="date"><?php echo date('d', strtotime($event['start_date'])); ?></span>
                                                <span class="month"><?php echo date('m月', strtotime($event['start_date'])); ?></span>
                                            </div>
                                            <div class="event-info">
                                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                                <p class="event-type"><?php echo htmlspecialchars($event['event_type_name'] ?? '未分類'); ?></p>
                                                <p class="event-time">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo date('H:i', strtotime($event['start_date'])); ?>
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
                            <a href="blessings/" class="btn btn-sm btn-primary">查看全部</a>
                        </div>
                        <div class="section-content">
                            <?php if (empty($latest_blessings)): ?>
                                <p class="no-data">目前沒有待處理的祈福請求</p>
                            <?php else: ?>
                                <div class="prayers-list">
                                    <?php foreach ($latest_blessings as $blessing): ?>
                                        <div class="prayer-item">
                                            <div class="prayer-info">
                                                <span class="prayer-type"><?php echo htmlspecialchars($blessing['type_name']); ?></span>
                                                <span class="prayer-date"><?php echo date('Y-m-d', strtotime($blessing['created_at'])); ?></span>
                                            </div>
                                            <div class="prayer-user">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($blessing['name']); ?>
                                            </div>
                                            <div class="prayer-details">
                                                <div class="prayer-service">
                                                    <?php echo htmlspecialchars($blessing['blessing_name']); ?>
                                                </div>
                                                <div class="prayer-price">
                                                    NT$ <?php echo number_format($blessing['price']); ?>
                                                </div>
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
                            <a href="contact_messages/" class="btn btn-sm btn-primary">查看全部</a>
                        </div>
                        <div class="section-content">
                            <?php if (empty($recent_contacts)): ?>
                                <p class="no-data">目前沒有未讀訊息</p>
                            <?php else: ?>
                                <div class="messages-list">
                                    <?php foreach ($recent_contacts as $contact): ?>
                                        <div class="message-item">
                                            <div class="message-header">
                                                <span class="message-from"><?php echo htmlspecialchars($contact['name']); ?></span>
                                                <span class="message-date"><?php echo date('Y-m-d H:i', strtotime($contact['created_at'])); ?></span>
                                            </div>
                                            <div class="message-subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                                            <div class="message-preview"><?php echo mb_substr(htmlspecialchars($contact['message']), 0, 100) . '...'; ?></div>
                                            <div class="message-actions">
                                                <a href="contact_messages/view.php?id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> 查看
                                                </a>
                                                <a href="contact_messages/reply.php?id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-reply"></i> 回覆
                                                </a>
                                            </div>
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
        </main>
    </div>
    <script src="../assets/js/admin.js"></script> 
</body>
</html> 