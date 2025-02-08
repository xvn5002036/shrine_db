<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// 設定輸出編碼
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// 處理分類和搜尋
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "WHERE n.status = 'published'";
$params = [];

if (!empty($search)) {
    $where_clause .= " AND (n.title LIKE :search OR n.content LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($category_id > 0) {
    $where_clause .= " AND n.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// 獲取所有分類
try {
    $categories_sql = "SELECT * FROM news_categories WHERE status = 'active' ORDER BY sort_order";
    $categories = $pdo->query($categories_sql)->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
    $categories = [];
}

// 獲取總記錄數
try {
    $count_sql = "SELECT COUNT(*) FROM news n " . $where_clause;
    $stmt = $pdo->prepare($count_sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $total_records = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Error counting news: ' . $e->getMessage());
    $total_records = 0;
}

$total_pages = ceil($total_records / $per_page);

// 獲取新聞列表
try {
    $sql = "
        SELECT n.*, u.username as author_name, c.name as category_name
        FROM news n 
        LEFT JOIN users u ON n.created_by = u.id 
        LEFT JOIN news_categories c ON n.category_id = c.id
        {$where_clause} 
        ORDER BY n.created_at DESC 
        LIMIT :offset, :per_page
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $news_list = $stmt->fetchAll();

    // 檢查是否有資料
    if (empty($news_list)) {
        // 檢查資料表是否存在資料
        $check_sql = "SELECT COUNT(*) FROM news WHERE status = 'published'";
        $total_news = $pdo->query($check_sql)->fetchColumn();
        error_log("Total published news in database: " . $total_news);
    }

} catch (PDOException $e) {
    error_log('Error fetching news: ' . $e->getMessage());
    $news_list = [];
}

// Debug 資訊
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    echo "<!-- Debug Info:\n";
    echo "Total Records: " . $total_records . "\n";
    echo "SQL: " . $sql . "\n";
    echo "Where Clause: " . $where_clause . "\n";
    echo "Params: " . print_r($params, true) . "\n";
    echo "Current Page: " . $page . "\n";
    echo "Offset: " . $offset . "\n";
    echo "-->";
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>最新消息 - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/news.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="content-header">
                <h1>最新消息</h1>
                <div class="breadcrumb">
                    <a href="index.php">首頁</a>
                    <i class="fas fa-angle-right"></i>
                    <span>最新消息</span>
                </div>
            </div>

            <div class="content-body">
                <!-- 分類選單 -->
                <div class="category-nav">
                    <a href="news.php" class="category-link <?php echo $category_id === 0 ? 'active' : ''; ?>">
                        全部
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="news.php?category=<?php echo $category['id']; ?>" 
                           class="category-link <?php echo $category_id === (int)$category['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- 搜尋區塊 -->
                <div class="search-block">
                    <form action="" method="get" class="search-form">
                        <?php if ($category_id > 0): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" name="search" 
                                   placeholder="請輸入關鍵字..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 新聞列表 -->
                <?php if (empty($news_list)): ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>目前沒有最新消息</p>
                    </div>
                <?php else: ?>
                    <div class="news-grid">
                        <?php foreach ($news_list as $news): ?>
                            <div class="news-card">
                                <?php if (!empty($news['image'])): ?>
                                    <div class="news-image">
                                        <a href="news_detail.php?id=<?php echo $news['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($news['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($news['title']); ?>">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="news-info">
                                    <h2 class="news-title">
                                        <a href="news_detail.php?id=<?php echo $news['id']; ?>">
                                            <?php echo htmlspecialchars($news['title']); ?>
                                        </a>
                                    </h2>
                                    <div class="news-meta">
                                        <span>
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo date('Y-m-d', strtotime($news['created_at'])); ?>
                                        </span>
                                        <?php if (!empty($news['category_name'])): ?>
                                            <span>
                                                <i class="fas fa-folder"></i>
                                                <?php echo htmlspecialchars($news['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="news-excerpt">
                                        <?php 
                                        $excerpt = strip_tags($news['content']);
                                        echo htmlspecialchars(mb_substr($excerpt, 0, 100)) . '...'; 
                                        ?>
                                    </div>
                                    <div class="news-actions">
                                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn-more">
                                            閱讀更多
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 分頁 -->
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
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 
