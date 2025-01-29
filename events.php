<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 設定輸出編碼
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// 獲取活動類型篩選
$event_type = isset($_GET['type']) ? $_GET['type'] : '';

// 如果有指定 ID，顯示單一活動
if (isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND status = 'published'");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: events.php');
        exit;
    }
} else {
    // 構建查詢條件
    $where_clause = "WHERE status = 'published'";
    $params = [];
    
    if ($event_type) {
        $where_clause .= " AND event_type = :event_type";
        $params[':event_type'] = $event_type;
    }
    
    // 只顯示未結束的活動
    $where_clause .= " AND end_date >= CURRENT_DATE()";
    
    // 獲取總數用於分頁
    $stmt = $db->prepare("SELECT COUNT(*) FROM events " . $where_clause);
    if ($event_type) {
        $stmt->bindParam(':event_type', $event_type);
    }
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $limit);
    
    // 獲取活動列表
    $stmt = $db->prepare("SELECT * FROM events {$where_clause} ORDER BY start_date ASC LIMIT :limit OFFSET :offset");
    if ($event_type) {
        $stmt->bindParam(':event_type', $event_type);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $events_list = $stmt->fetchAll();
}

// 獲取活動類型列表
$stmt = $db->query("SELECT DISTINCT et.name as event_type FROM events e JOIN event_types et ON e.type_id = et.id WHERE e.status = 'published'");
$event_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
                        <span class="date">
                            <i class="fas fa-calendar"></i> 
                            <?php 
                            $start_date = date('Y/m/d', strtotime($event['start_date']));
                            $end_date = date('Y/m/d', strtotime($event['end_date']));
                            echo $start_date . ($start_date != $end_date ? ' ~ ' . $end_date : '');
                            ?>
                        </span>
                        <span class="time"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($event['time']); ?></span>
                        <span class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                        <?php if ($event['event_type']): ?>
                            <span class="type"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($event['event_type']); ?></span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($event['image_url']): ?>
                    <div class="event-image">
                        <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                <?php endif; ?>

                <div class="event-content">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </div>

                <?php if ($event['registration_url']): ?>
                    <div class="event-registration">
                        <a href="<?php echo htmlspecialchars($event['registration_url']); ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-sign-in-alt"></i> 立即報名
                        </a>
                    </div>
                <?php endif; ?>

                <footer class="event-footer">
                    <a href="events.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回列表</a>
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
                            <a href="?type=<?php echo urlencode($type); ?>" 
                               class="filter-btn <?php echo $event_type === $type ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($type); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="events-list">
                <?php if (empty($events_list)): ?>
                    <div class="no-results">
                        <p>目前沒有進行中的活動</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events_list as $item): ?>
                        <article class="event-item">
                            <?php if ($item['image_url']): ?>
                                <div class="event-image">
                                    <a href="events.php?id=<?php echo $item['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="event-content">
                                <h2>
                                    <a href="events.php?id=<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                </h2>
                                <div class="event-meta">
                                    <span class="date">
                                        <i class="fas fa-calendar"></i> 
                                        <?php 
                                        $start_date = date('Y/m/d', strtotime($item['start_date']));
                                        $end_date = date('Y/m/d', strtotime($item['end_date']));
                                        echo $start_date . ($start_date != $end_date ? ' ~ ' . $end_date : '');
                                        ?>
                                    </span>
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo htmlspecialchars($item['time']); ?>
                                    </span>
                                    <span class="location">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($item['location']); ?>
                                    </span>
                                </div>
                                <div class="event-excerpt">
                                    <?php 
                                    $excerpt = mb_substr(strip_tags($item['description']), 0, 150, 'UTF-8');
                                    echo htmlspecialchars($excerpt) . '...';
                                    ?>
                                </div>
                                <div class="event-actions">
                                    <a href="events.php?id=<?php echo $item['id']; ?>" class="btn btn-secondary">
                                        活動詳情 <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <?php if ($item['registration_url']): ?>
                                        <a href="<?php echo htmlspecialchars($item['registration_url']); ?>" 
                                           class="btn btn-primary" target="_blank">
                                            立即報名 <i class="fas fa-sign-in-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $event_type ? '&type=' . urlencode($event_type) : ''; ?>" class="prev">
                            <i class="fas fa-chevron-left"></i> 上一頁
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $event_type ? '&type=' . urlencode($event_type) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $event_type ? '&type=' . urlencode($event_type) : ''; ?>" class="next">
                            下一頁 <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'templates/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
