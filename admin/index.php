<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 檢查 session 是否已經啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 檢查是否已登入
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 獲取基本統計資料
try {
    $stats = [
        'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_news' => $db->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn(),
        'total_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn()
    ];

    // 檢查其他表格是否存在
    $tables_to_check = [
        'prayer_requests' => "SELECT COUNT(*) FROM prayer_requests",
        'contact_messages' => "SELECT COUNT(*) FROM contact_messages",
        'gallery' => "SELECT COUNT(*) FROM gallery"
    ];

    foreach ($tables_to_check as $key => $query) {
        try {
            $stats['total_' . $key] = $db->query($query)->fetchColumn();
        } catch (PDOException $e) {
            $stats['total_' . $key] = 0;
        }
    }
} catch (PDOException $e) {
    error_log("Error getting stats: " . $e->getMessage());
    $stats = [];
}

// 獲取最新消息
try {
    $latest_news = $db->query("
        SELECT n.*, u.username as author 
        FROM news n 
        LEFT JOIN users u ON n.created_by = u.id 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting latest news: " . $e->getMessage());
    $latest_news = [];
}

// 獲取最新祈福請求
try {
    $latest_prayers = $db->query("
        SELECT * FROM prayer_requests 
        WHERE status != 'completed' 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting prayer requests: " . $e->getMessage());
    $latest_prayers = [];
}

// 獲取最新活動
try {
    $latest_events = $db->query("
        SELECT * FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC 
        LIMIT 3
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting latest events: " . $e->getMessage());
    $latest_events = [];
}

// 獲取最新聯絡訊息
try {
    $latest_contacts = $db->query("
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
    'mysql_version' => $db->query('SELECT VERSION()')->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'last_backup' => get_last_backup_time()
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
        <!-- 側邊欄 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>後台管理系統</p>
            </div>
        
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>儀表板</span>
                        </a>
                    </li>
                    <li>
                        <a href="users/">
                            <i class="fas fa-users"></i>
                            <span>使用者管理</span>
                        </a>
                    </li>
                    <li>
                        <a href="news/">
                            <i class="fas fa-newspaper"></i>
                            <span>最新消息</span>
                        </a>
                    </li>
                    <li>
                        <a href="prayers/">
                            <i class="fas fa-pray"></i>
                            <span>祈福管理</span>
                        </a>
                    </li>
                    <li>
                        <a href="events/">
                            <i class="fas fa-calendar-alt"></i>
                            <span>活動管理</span>
                        </a>
                    </li>
                    <li>
                        <a href="gallery/">
                            <i class="fas fa-images"></i>
                            <span>圖片管理</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings/">
                            <i class="fas fa-cog"></i>
                            <span>網站設定</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- 主要內容區 -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="header-right">
                    <div class="admin-user">
                        <span class="welcome-text">歡迎，</span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理員'); ?></span>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            登出
                        </a>
                    </div>
                </div>
            </header>
            
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
                                <h3>使用者總數</h3>
                                <p><?php echo $stats['total_users']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="stat-info">
                                <h3>最新消息</h3>
                                <p><?php echo $stats['total_news']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-pray"></i>
                            </div>
                            <div class="stat-info">
                                <h3>祈福請求</h3>
                                <p><?php echo $stats['total_prayer_requests']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-info">
                                <h3>未讀訊息</h3>
                                <p><?php echo count($latest_contacts); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3>近期活動</h3>
                                <p><?php echo $stats['total_events']; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-images"></i>
                            </div>
                            <div class="stat-info">
                                <h3>圖片總數</h3>
                                <p><?php echo $stats['total_gallery']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- 系統資訊 -->
                    <div class="dashboard-section system-info">
                        <h2>系統資訊</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>PHP 版本</label>
                                <span><?php echo $system_info['php_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>MySQL 版本</label>
                                <span><?php echo $system_info['mysql_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>伺服器軟體</label>
                                <span><?php echo $system_info['server_software']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>最後備份時間</label>
                                <span><?php echo $system_info['last_backup']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 最新消息列表 -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>最新消息</h2>
                            <a href="news/" class="btn btn-sm btn-primary">查看全部</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>標題</th>
                                        <th>作者</th>
                                        <th>發布日期</th>
                                        <th>狀態</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latest_news as $news): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($news['title'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($news['author'] ?? ''); ?></td>
                                        <td><?php echo $news['publish_date'] ? date('Y/m/d', strtotime($news['publish_date'])) : ''; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars($news['status'] ?? ''); ?>">
                                                <?php echo ($news['status'] ?? '') === 'published' ? '已發布' : '草稿'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="news/edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                            <a href="news/preview.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-secondary">預覽</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 最新祈福請求 -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>最新祈福請求</h2>
                            <a href="prayers/" class="btn btn-sm btn-primary">查看全部</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>申請人</th>
                                        <th>請求類型</th>
                                        <th>申請日期</th>
                                        <th>狀態</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latest_prayers as $prayer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prayer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($prayer['request_type']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($prayer['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $prayer['status']; ?>">
                                                <?php echo get_prayer_status_text($prayer['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="prayers/view.php?id=<?php echo $prayer['id']; ?>" class="btn btn-sm btn-primary">處理</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 近期活動 -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>近期活動</h2>
                            <a href="events/" class="btn btn-sm btn-primary">查看全部</a>
                        </div>
                        <div class="events-grid">
                            <?php foreach ($latest_events as $event): ?>
                            <div class="event-card">
                                <div class="event-date">
                                    <span class="date"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                    <span class="month"><?php echo date('m月', strtotime($event['event_date'])); ?></span>
                                </div>
                                <div class="event-info">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p class="event-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('H:i', strtotime($event['event_time'])); ?>
                                    </p>
                                    <p class="event-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </p>
                                </div>
                                <div class="event-actions">
                                    <a href="events/edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 最新聯絡訊息 -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>未讀訊息</h2>
                            <a href="contacts/" class="btn btn-sm btn-primary">查看全部</a>
                        </div>
                        <div class="messages-list">
                            <?php foreach ($latest_contacts as $contact): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <div class="message-info">
                                        <span class="sender-name"><?php echo htmlspecialchars($contact['name']); ?></span>
                                        <span class="message-time"><?php echo date('Y/m/d H:i', strtotime($contact['created_at'])); ?></span>
                                    </div>
                                    <div class="message-actions">
                                        <a href="contacts/view.php?id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-primary">查看</a>
                                    </div>
                                </div>
                                <div class="message-subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                                <div class="message-preview"><?php echo mb_substr(htmlspecialchars($contact['message']), 0, 100); ?>...</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>




