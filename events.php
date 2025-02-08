<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 設定輸出編碼
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// 獲取活動類型篩選
$event_type = isset($_GET['type']) ? (int)$_GET['type'] : 0;
// 獲取時間篩選（upcoming: 即將到來, past: 歷史活動）
$time_filter = isset($_GET['time']) ? $_GET['time'] : 'upcoming';

// 初始化分頁變數
$total_pages = 1;

// 構建查詢字符串
$query_string = '';
if ($event_type) {
    $query_string .= '&type=' . urlencode($event_type);
}
if ($time_filter) {
    $query_string .= '&time=' . urlencode($time_filter);
}

// 獲取活動列表
try {
    // 構建基本的 WHERE 子句
    $where_clause = "WHERE e.status = 1";
    
    // 根據時間篩選添加條件
    if ($time_filter === 'upcoming') {
        $where_clause .= " AND e.end_date >= CURRENT_TIMESTAMP";
    } elseif ($time_filter === 'past') {
        $where_clause .= " AND e.end_date < CURRENT_TIMESTAMP";
    }

    // 如果有活動類型篩選，添加條件
    if ($event_type) {
        $where_clause .= " AND e.event_type_id = :event_type";
    }

    // 先計算總記錄數
    $count_sql = "
        SELECT COUNT(*) 
        FROM events e
        LEFT JOIN event_types et ON e.event_type_id = et.id
        $where_clause
    ";
    
    if ($event_type) {
        $stmt = $pdo->prepare($count_sql);
        $stmt->bindValue(':event_type', $event_type, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->query($count_sql);
    }
    
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取活動列表
    $sql = "
        SELECT e.*, et.name as type_name,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'confirmed') as confirmed_count
        FROM events e
        LEFT JOIN event_types et ON e.event_type_id = et.id
        $where_clause
        ORDER BY " . ($time_filter === 'past' ? 'e.end_date DESC' : 'e.start_date ASC') . "
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($event_type) {
        $stmt->bindValue(':event_type', $event_type, PDO::PARAM_INT);
    }
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching events: ' . $e->getMessage());
    $events = [];
}

// 獲取活動類型列表
try {
    $stmt = $pdo->query("
        SELECT id, name, description 
        FROM event_types 
        WHERE status = 'active' 
        ORDER BY sort_order
    ");
    $event_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('獲取活動類型錯誤：' . $e->getMessage());
    $event_types = [];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo isset($event) ? htmlspecialchars($event['title']) : '活動資訊'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/events.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* 活動列表特定樣式 */
        .events-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .event-card {
            transition: transform 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
        }

        .event-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-title {
            font-size: 1.2em;
            margin: 0 0 10px 0;
            color: #333;
        }

        .registration-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .registration-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9em;
        }

        .deadline-warning {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .filter-options {
            margin: 20px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .event-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include 'templates/header.php'; ?>

    <main class="container">
        <?php if (isset($event)): ?>
            <!-- 單一活動內容 -->
            <article class="event-detail">
                <header class="event-header">
                    <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                    <div class="event-meta">
                        <span class="type">
                            <i class="fas fa-tag"></i> 
                            <?php echo htmlspecialchars($event['type_name'] ?? '未分類'); ?>
                        </span>
                        <span class="date">
                            <i class="fas fa-calendar"></i> 
                            <?php 
                            $start_date = new DateTime($event['start_date']);
                            $end_date = new DateTime($event['end_date']);
                            echo $start_date->format('Y/m/d');
                            if ($start_date->format('Y-m-d') !== $end_date->format('Y-m-d')) {
                                echo ' ~ ' . $end_date->format('Y/m/d');
                            }
                            ?>
                        </span>
                        <span class="time">
                            <i class="fas fa-clock"></i> 
                            <?php 
                            echo $start_date->format('H:i') . ' ~ ' . $end_date->format('H:i');
                            ?>
                        </span>
                        <span class="location">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($event['location']); ?>
                        </span>
                        <?php if ($event['max_participants'] > 0): ?>
                            <span class="participants">
                                <i class="fas fa-users"></i>
                                報名人數：<?php echo $event['current_participants']; ?> / <?php echo $event['max_participants']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($event['image']): ?>
                    <div class="event-image">
                        <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                <?php endif; ?>

                <div class="event-content">
                    <div class="event-description">
                        <h2>活動說明</h2>
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>

                    <?php if ($event['max_participants'] > 0): ?>
                        <div class="event-registration-info">
                            <h2>報名資訊</h2>
                            <ul>
                                <li>
                                    <strong>報名截止日期：</strong>
                                    <?php 
                                    $deadline = new DateTime($event['registration_deadline']);
                                    echo $deadline->format('Y/m/d H:i'); 
                                    ?>
                                </li>
                                <li>
                                    <strong>報名狀態：</strong>
                                    <?php
                                    $now = new DateTime();
                                    $start_date = new DateTime($event['start_date']);
                                    
                                    if ($now > $start_date) {
                                        echo '<span class="status-ended">活動已結束</span>';
                                    } elseif ($now > $deadline) {
                                        echo '<span class="status-closed">報名已截止</span>';
                                    } elseif ($event['current_participants'] >= $event['max_participants']) {
                                        echo '<span class="status-full">名額已滿</span>';
                                    } else {
                                        echo '<span class="status-open">開放報名中</span>';
                                    }
                                    ?>
                                </li>
                                <li>
                                    <strong>剩餘名額：</strong>
                                    <?php echo max(0, $event['max_participants'] - $event['current_participants']); ?> 個
                                </li>
                            </ul>

                            <?php if ($now <= $deadline && $now <= $start_date && $event['current_participants'] < $event['max_participants']): ?>
                                <div class="registration-actions">
                                    <a href="event_registration.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> 立即報名
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['notes'])): ?>
                        <div class="event-notes">
                            <h2>注意事項</h2>
                            <?php echo nl2br(htmlspecialchars($event['notes'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <footer class="event-footer">
                    <div class="event-actions">
                        <a href="events.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="event_registration_history.php" class="btn btn-info">
                                <i class="fas fa-history"></i> 我的報名記錄
                            </a>
                        <?php endif; ?>
                    </div>
                </footer>
            </article>
        <?php else: ?>
            <!-- 活動列表 -->
            <div class="page-header">
                <h1>活動資訊</h1>
                <?php if (!empty($event_types)): ?>
                    <div class="event-filters">
                        <a href="events.php" class="filter-btn <?php echo !$event_type ? 'active' : ''; ?>">全部</a>
                        <?php foreach ($event_types as $type): ?>
                            <a href="?type=<?php echo $type['id']; ?>" 
                               class="filter-btn <?php echo $event_type === (int)$type['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($type['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="filter-options">
                <a href="?time=upcoming" class="filter-btn <?php echo $time_filter === 'upcoming' ? 'active' : ''; ?>">
                    即將舉辦的活動
                </a>
                <a href="?time=past" class="filter-btn <?php echo $time_filter === 'past' ? 'active' : ''; ?>">
                    歷史活動
                </a>
            </div>

            <div class="events-container">
                <?php if (empty($events)): ?>
                    <div class="no-events">
                        <p>目前沒有進行中的活動</p>
                    </div>
                <?php else: ?>
                    <div class="event-grid">
                        <?php foreach ($events as $event): ?>
                            <div class="event-card">
                                <?php if ($event['image']): ?>
                                    <div class="event-image">
                                        <img src="<?php echo htmlspecialchars($event['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="event-content">
                                    <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                                    
                                    <div class="event-meta">
                                        <span>
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('Y/m/d H:i', strtotime($event['start_date'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </span>
                                        <?php if ($event['type_name']): ?>
                                            <span>
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($event['type_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="event-description">
                                        <?php echo nl2br(htmlspecialchars(mb_substr($event['description'], 0, 100))); ?>...
                                    </div>

                                    <div class="registration-info">
                                        <div class="registration-status">
                                            <span>
                                                <i class="fas fa-users"></i>
                                                已報名：<?php echo $event['confirmed_count']; ?> 人
                                                <?php if ($event['max_participants']): ?>
                                                    / 上限 <?php echo $event['max_participants']; ?> 人
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <?php
                                        $can_register = true;
                                        $disabled_reason = '';

                                        // 檢查是否已達人數上限
                                        if ($event['max_participants'] && $event['confirmed_count'] >= $event['max_participants']) {
                                            $can_register = false;
                                            $disabled_reason = '報名人數已滿';
                                        }
                                        // 檢查報名截止時間
                                        elseif ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
                                            $can_register = false;
                                            $disabled_reason = '報名已截止';
                                        }
                                        ?>

                                        <?php if ($can_register): ?>
                                            <a href="event_registration.php?id=<?php echo $event['id']; ?>" 
                                               class="btn-register">
                                                立即報名
                                            </a>
                                            <?php if ($event['registration_deadline']): ?>
                                                <div class="deadline-warning">
                                                    報名截止時間：<?php echo date('Y/m/d H:i', strtotime($event['registration_deadline'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="btn-register disabled">
                                                <?php echo $disabled_reason; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $query_string; ?>" class="page-btn first">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="page-btn prev">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" 
                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="page-btn next">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>" class="page-btn last">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>

<style>
/* 活動詳情頁面樣式 */
.event-detail {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.event-header {
    margin-bottom: 30px;
}

.event-header h1 {
    font-size: 2em;
    color: #333;
    margin: 0 0 20px 0;
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.event-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 0.95em;
}

.event-meta i {
    color: #4a90e2;
}

.event-image {
    margin-bottom: 30px;
    border-radius: 8px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: auto;
    display: block;
}

.event-content {
    line-height: 1.6;
}

.event-description,
.event-registration-info,
.event-notes {
    margin-bottom: 30px;
}

.event-content h2 {
    font-size: 1.5em;
    color: #333;
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #eee;
}

.event-registration-info ul {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.event-registration-info li {
    margin-bottom: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.event-registration-info strong {
    color: #333;
    margin-right: 10px;
}

.status-open { color: #28a745; }
.status-closed { color: #dc3545; }
.status-full { color: #ffc107; }
.status-ended { color: #6c757d; }

.registration-actions {
    margin-top: 20px;
}

.event-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.event-actions {
    display: flex;
    gap: 15px;
}

.btn {
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4a90e2;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .event-detail {
        padding: 20px;
    }

    .event-meta {
        flex-direction: column;
        gap: 10px;
    }

    .event-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* 活動篩選按鈕樣式 */
.event-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 20px;
    background: #f8f9fa;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid #ddd;
}

.filter-btn:hover {
    background: #e9ecef;
    color: #333;
}

.filter-btn.active {
    background: #4a90e2;
    color: white;
    border-color: #4a90e2;
}

/* 活動列表樣式 */
.events-list {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.event-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.event-item .event-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.event-item .event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-item .event-content {
    padding: 20px;
    flex: 1;
}

.event-item h2 {
    margin: 0 0 15px 0;
    font-size: 1.5em;
}

.event-item h2 a {
    color: #333;
    text-decoration: none;
}

.event-item h2 a:hover {
    color: #4a90e2;
}

.event-item .event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    color: #666;
    font-size: 0.9em;
}

.event-item .event-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.event-item .event-meta i {
    color: #4a90e2;
}

.event-item .event-excerpt {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

.event-item .event-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
}

/* 無資料提示樣式 */
.no-results {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.no-results p {
    color: #666;
    font-size: 1.1em;
    margin: 0;
}

@media (max-width: 768px) {
    .event-filters {
        justify-content: center;
    }
    
    .event-item {
        flex-direction: column;
    }
    
    .event-item .event-image {
        width: 100%;
        height: 180px;
    }
    
    .event-item .event-actions {
        flex-direction: column;
    }
    
    .event-item .event-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

.filter-options {
    margin: 20px 0;
    text-align: center;
}

.filter-btn {
    display: inline-block;
    padding: 8px 16px;
    margin: 0 5px;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.filter-btn.active {
    background-color: #4a90e2;
    border-color: #4a90e2;
    color: white;
}

.filter-btn:hover {
    background-color: #e9ecef;
}

.filter-btn.active:hover {
    background-color: #357abd;
}
</style>
