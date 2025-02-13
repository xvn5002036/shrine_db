<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設定輸出編碼
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// 檢查必要的目錄是否存在
$upload_dir = 'uploads/gallery';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 使用全域PDO實例
$pdo = $GLOBALS['pdo'];

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// 處理分類過濾
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// 定義相簿分類
$categories = [
    'temple' => '宮廟建築',
    'ceremony' => '祭典活動',
    'collection' => '文物典藏',
    'festival' => '節慶活動'
];

try {
    // 構建查詢條件
    $where_clause = "WHERE a.status = 'published'";
    $params = [];
    
    if (!empty($category)) {
        $where_clause .= " AND a.category = :category";
        $params[':category'] = $category;
    }

    // 獲取總記錄數
    $count_sql = "
        SELECT COUNT(*) 
        FROM gallery_albums a 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取相簿列表
    $sql = "
        SELECT a.*, u.username as creator_name,
               (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) as photo_count
        FROM gallery_albums a 
        LEFT JOIN users u ON a.created_by = u.id 
        $where_clause 
        ORDER BY a.event_date DESC 
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $albums = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching albums: ' . $e->getMessage());
    $albums = [];
    $total_pages = 0;
}

$page_title = '活動花絮';
$current_page = 'gallery';
require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>活動花絮</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                <li class="breadcrumb-item active">活動花絮</li>
            </ol>
        </nav>
    </div>
        </div>

<div class="page-content">
    <div class="container">
        <!-- 內容包裝器 -->
        <div class="content-wrapper">
            <!-- 左側邊欄 -->
            <div class="sidebar">
                <div class="category-filter">
                    <h4 class="filter-title">活動分類</h4>
                    <div class="filter-list">
                        <a href="gallery.php" class="filter-btn <?php echo empty($category) ? 'active' : ''; ?>">
                            <i class="fas fa-border-all"></i>
                            <span>全部相簿</span>
                            <span class="count"><?php echo $total_records; ?></span>
                        </a>
                        <?php foreach ($categories as $key => $name): ?>
                            <a href="gallery.php?category=<?php echo $key; ?>" 
                               class="filter-btn <?php echo $category === $key ? 'active' : ''; ?>">
                                <i class="fas <?php
                                    switch($key) {
                                        case 'temple': echo 'fa-gopuram';
                                        break;
                                        case 'ceremony': echo 'fa-pray';
                                        break;
                                        case 'collection': echo 'fa-archive';
                                        break;
                                        case 'festival': echo 'fa-dragon';
                                        break;
                                        default: echo 'fa-folder';
                                    }
                                ?>"></i>
                                <span><?php echo $name; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 主要內容區 -->
            <div class="main-content">
                <!-- 相簿列表 -->
                <div class="album-grid">
                    <?php if (empty($albums)): ?>
                        <div class="empty-state">
                            <i class="fas fa-images"></i>
                            <h3>暫無相簿</h3>
                            <p>目前還沒有相關的活動相簿</p>
                        </div>
                    <?php else: ?>
                        <div class="masonry-grid">
                            <?php foreach ($albums as $album): ?>
                                <div class="masonry-item">
                                    <div class="album-card">
                                        <div class="album-cover">
                                            <?php if (!empty($album['cover_photo']) && file_exists('uploads/gallery/' . $album['id'] . '/' . $album['cover_photo'])): ?>
                                                <img src="uploads/gallery/<?php echo $album['id']; ?>/<?php echo htmlspecialchars($album['cover_photo']); ?>"
                                                     alt="<?php echo htmlspecialchars($album['title']); ?>"
                                                     loading="lazy"
                                                     onload="this.parentElement.classList.add('loaded')"
                                                     onerror="this.parentElement.innerHTML='<div class=\'no-cover\'><div class=\'no-cover-icon\'><i class=\'fas fa-images\'></i></div><div class=\'no-cover-text\'>圖片載入失敗</div></div>'">
                                            <?php else: ?>
                                                <div class="no-cover">
                                                    <div class="no-cover-icon">
                                                        <i class="fas fa-images"></i>
                                                    </div>
                                                    <div class="no-cover-text">
                                                        尚無封面
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="album-details">
                                            <div class="album-info">
                                                <h3 class="album-title">
                                                    <?php echo htmlspecialchars($album['title']); ?>
                                                </h3>
                                                <div class="album-meta">
                                                    <span class="meta-item">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <?php echo date('Y/m/d', strtotime($album['event_date'])); ?>
                                                    </span>
                                                    <span class="meta-item">
                                                        <i class="fas fa-images"></i>
                                                        <?php echo $album['photo_count']; ?> 張照片
                                                    </span>
                                                </div>
                                                <?php if (!empty($album['description'])): ?>
                                                    <p class="album-description">
                                                        <?php echo mb_strimwidth(htmlspecialchars($album['description']), 0, 100, '...'); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="album-actions">
                                                <a href="gallery_detail.php?id=<?php echo $album['id']; ?>" class="view-album">
                                                    <span>查看相簿</span>
                                                    <i class="fas fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="album-category">
                                            <i class="fas fa-folder"></i> 
                                            <span><?php echo $categories[$album['category']] ?? '未分類'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 分頁導航 -->
                <?php if ($total_pages > 1): ?>
                    <nav class="pagination-wrapper">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&category=<?php echo $category; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&category=' . $category . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '">';
                                echo '<a class="page-link" href="?page=' . $i . '&category=' . $category . '">' . $i . '</a>';
                                echo '</li>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&category=' . $category . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&category=<?php echo $category; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* 頁面橫幅 */
.page-banner {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    padding: 5rem 0;
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
}

.page-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239ba5ad' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
}

.page-banner h1 {
    font-size: 3.5rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: -1px;
}

/* 內容包裝器 */
.content-wrapper {
    display: flex;
    gap: 2.5rem;
    position: relative;
    margin-top: -60px;
    width: 100%;
}

/* 左側邊欄 */
.sidebar {
    width: 300px;
    flex-shrink: 0;
}

/* 分類過濾器 */
.category-filter {
    background: rgba(255, 255, 255, 0.98);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 2rem;
    backdrop-filter: blur(20px);
}

.filter-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 1.8rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(0, 0, 0, 0.06);
    letter-spacing: -0.5px;
}

.filter-btn {
    display: flex;
    align-items: center;
    padding: 1.2rem 1.5rem;
    margin-bottom: 0.8rem;
    border-radius: 12px;
    color: #444;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    background: #f8f9fa;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.filter-btn i {
    font-size: 1.2rem;
    margin-right: 1rem;
    color: #3498db;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: #fff;
    transform: translateX(5px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.filter-btn.active {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: #fff;
    border: none;
}

.filter-btn.active i {
    color: #fff;
}

/* 瀑布流網格樣式 */
.masonry-grid {
    column-count: 3;
    column-gap: 30px;
    padding: 0;
    width: 100%;
}

@media (max-width: 1400px) {
    .masonry-grid {
        column-count: 3;
    }
}

@media (max-width: 1200px) {
    .masonry-grid {
        column-count: 2;
        column-gap: 25px;
    }
    
    .content-wrapper {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        margin-bottom: 2rem;
    }
    
    .category-filter {
        position: relative;
        top: 0;
    }
    
    .filter-list {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .filter-btn {
        flex: 1;
        min-width: 200px;
        margin-bottom: 0;
    }
}

@media (max-width: 768px) {
    .masonry-grid {
        column-count: 1;
        column-gap: 20px;
    }
}

.masonry-item {
    break-inside: avoid;
    margin-bottom: 30px;
    display: inline-block;
    width: 100%;
}

.album-card {
    margin: 0;
    width: 100%;
    display: block;
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    transform: translateZ(0);
    backface-visibility: hidden;
}

.album-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.album-cover {
    position: relative;
    padding-top: 66%;
    overflow: hidden;
    background: #f8f9fa;
}

.album-cover img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.album-card:hover .album-cover img {
    transform: scale(1.05);
}

.album-details {
    padding: 1.5rem;
    background: #fff;
}

.album-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #2d3436;
    line-height: 1.4;
}

.album-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #636e72;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-item i {
    color: #00b894;
}

.album-description {
    font-size: 0.95rem;
    color: #636e72;
    margin-bottom: 1.5rem;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.album-actions {
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    text-align: right;
}

.view-album {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #00b894, #00cec9);
    color: #fff;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.view-album:hover {
    background: linear-gradient(135deg, #00a884, #00b8b8);
    transform: translateX(5px);
    color: #fff;
}

.album-category {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.5rem 1rem;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #00b894;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

/* 空狀態樣式 */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, #f6f8fb, #f0f3f7);
    border-radius: 15px;
    margin: 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #00b894;
    margin-bottom: 1.5rem;
    opacity: 0.8;
}

.empty-state h3 {
    font-size: 1.8rem;
    color: #2d3436;
    margin-bottom: 1rem;
    font-weight: 700;
}

.empty-state p {
    color: #636e72;
    font-size: 1.1rem;
    line-height: 1.6;
}

/* 動畫效果 */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.masonry-item {
    animation: fadeInUp 0.6s ease-out forwards;
    animation-delay: calc(var(--card-index) * 0.1s);
}

/* 主要內容區域寬度調整 */
.main-content {
    flex: 1;
    max-width: 100%;
    width: 100%;
}
</style>

<!-- 添加 Masonry 相關腳本 -->
<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
<script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化 Masonry
    var grid = document.querySelector('.masonry-grid');
    var masonry = new Masonry(grid, {
        itemSelector: '.masonry-item',
        columnWidth: '.masonry-item',
        percentPosition: true,
        transitionDuration: '0.3s'
    });

    // 當圖片載入完成後重新排列
    imagesLoaded(grid).on('progress', function() {
        masonry.layout();
    });

    // 為相簿卡片添加動畫延遲
    const cards = document.querySelectorAll('.masonry-item');
    cards.forEach((card, index) => {
        card.style.setProperty('--card-index', index);
    });
});
</script>

<?php include 'templates/footer.php'; ?> 