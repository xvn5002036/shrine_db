<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取搜尋關鍵字
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// 預設每頁顯示數量
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

$results = [];
$total_results = 0;

if (!empty($keyword)) {
    try {
        // 準備基礎的搜尋條件
        $search_term = "%{$keyword}%";
        
        // 根據類別搜尋不同的內容
        if ($category == 'all' || $category == 'news') {
            // 搜尋最新消息
            $stmt = $db->prepare("
                SELECT 
                    'news' as type,
                    id,
                    title,
                    content,
                    published_at,
                    NULL as event_date,
                    NULL as event_time
                FROM news 
                WHERE (title LIKE ? OR content LIKE ?) 
                AND status = 'published'
            ");
            $stmt->execute([$search_term, $search_term]);
            $news_results = $stmt->fetchAll();
            $results = array_merge($results, $news_results);
        }

        if ($category == 'all' || $category == 'events') {
            // 搜尋活動
            $stmt = $db->prepare("
                SELECT 
                    'event' as type,
                    id,
                    title,
                    description as content,
                    created_at as published_at,
                    event_date,
                    event_time
                FROM events 
                WHERE (title LIKE ? OR description LIKE ?) 
                AND status = 'published'
            ");
            $stmt->execute([$search_term, $search_term]);
            $event_results = $stmt->fetchAll();
            $results = array_merge($results, $event_results);
        }

        if ($category == 'all' || $category == 'services') {
            // 搜尋祈福服務
            $stmt = $db->prepare("
                SELECT 
                    'service' as type,
                    id,
                    name as title,
                    description as content,
                    created_at as published_at,
                    NULL as event_date,
                    NULL as event_time
                FROM prayer_types 
                WHERE (name LIKE ? OR description LIKE ?) 
                AND status = 1
            ");
            $stmt->execute([$search_term, $search_term]);
            $service_results = $stmt->fetchAll();
            $results = array_merge($results, $service_results);
        }

        // 計算總結果數
        $total_results = count($results);

        // 排序結果（依日期降序）
        usort($results, function($a, $b) {
            return strtotime($b['published_at']) - strtotime($a['published_at']);
        });

        // 分頁處理
        $results = array_slice($results, $offset, $per_page);

    } catch (PDOException $e) {
        error_log("搜尋錯誤：" . $e->getMessage());
        $error = "搜尋時發生錯誤，請稍後再試。";
    }
}

// 計算總頁數
$total_pages = ceil($total_results / $per_page);

// 載入頁面
include 'templates/header.php';
?>

<div class="container">
    <div class="search-page">
        <h1 class="section-title">搜尋結果</h1>

        <!-- 搜尋表單 -->
        <div class="search-form">
            <form action="search.php" method="get" class="mb-4">
                <div class="search-input-group">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($keyword); ?>" 
                           placeholder="請輸入關鍵字..." class="form-control">
                    <select name="category" class="form-control">
                        <option value="all" <?php echo $category == 'all' ? 'selected' : ''; ?>>全部</option>
                        <option value="news" <?php echo $category == 'news' ? 'selected' : ''; ?>>最新消息</option>
                        <option value="events" <?php echo $category == 'events' ? 'selected' : ''; ?>>活動資訊</option>
                        <option value="services" <?php echo $category == 'services' ? 'selected' : ''; ?>>祈福服務</option>
                    </select>
                    <button type="submit" class="btn btn-primary">搜尋</button>
                </div>
            </form>
        </div>

        <?php if (!empty($keyword)): ?>
            <!-- 搜尋結果統計 -->
            <div class="search-stats">
                <p>找到 <?php echo $total_results; ?> 筆與 "<?php echo htmlspecialchars($keyword); ?>" 相關的結果</p>
            </div>

            <?php if (!empty($results)): ?>
                <!-- 搜尋結果列表 -->
                <div class="search-results">
                    <?php foreach ($results as $result): ?>
                        <div class="search-item">
                            <div class="search-item-type">
                                <?php
                                switch ($result['type']) {
                                    case 'news':
                                        echo '<span class="badge badge-primary">最新消息</span>';
                                        break;
                                    case 'event':
                                        echo '<span class="badge badge-success">活動資訊</span>';
                                        break;
                                    case 'service':
                                        echo '<span class="badge badge-info">祈福服務</span>';
                                        break;
                                }
                                ?>
                            </div>
                            <h3 class="search-item-title">
                                <?php
                                $link = '';
                                switch ($result['type']) {
                                    case 'news':
                                        $link = 'news.php?id=' . $result['id'];
                                        break;
                                    case 'event':
                                        $link = 'events.php?id=' . $result['id'];
                                        break;
                                    case 'service':
                                        $link = 'services.php?id=' . $result['id'];
                                        break;
                                }
                                ?>
                                <a href="<?php echo $link; ?>"><?php echo htmlspecialchars($result['title']); ?></a>
                            </h3>
                            <div class="search-item-meta">
                                <?php if ($result['event_date']): ?>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('Y/m/d', strtotime($result['event_date'])); ?></span>
                                    <?php if ($result['event_time']): ?>
                                        <span><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($result['event_time'])); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span><i class="fas fa-clock"></i> <?php echo date('Y/m/d', strtotime($result['published_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="search-item-content">
                                <?php echo mb_substr(strip_tags($result['content']), 0, 200) . '...'; ?>
                            </div>
                            <a href="<?php echo $link; ?>" class="btn btn-outline">閱讀更多</a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 分頁導航 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?q=<?php echo urlencode($keyword); ?>&category=<?php echo $category; ?>&page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-results">
                    <p>沒有找到相關結果，請嘗試其他關鍵字。</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>