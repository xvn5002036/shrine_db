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
        <!-- 分類過濾 -->
        <div class="category-filter mb-4">
            <div class="d-flex justify-content-center flex-wrap">
                <a href="gallery.php" class="filter-btn <?php echo empty($category) ? 'active' : ''; ?>">
                    全部相簿
                </a>
                <?php foreach ($categories as $key => $name): ?>
                    <a href="gallery.php?category=<?php echo $key; ?>" 
                       class="filter-btn <?php echo $category === $key ? 'active' : ''; ?>">
                        <?php echo $name; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            </div>

        <!-- 相簿列表 -->
        <div class="album-grid">
            <div class="row g-4">
                <?php if (empty($albums)): ?>
                    <div class="col-12 text-center">
                        <div class="empty-state">
                            <i class="fas fa-images"></i>
                            <h3>暫無相簿</h3>
                            <p>目前還沒有相關的活動相簿</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($albums as $album): ?>
                        <div class="col-md-6 col-lg-4">
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
                                <div class="album-overlay"></div>
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
                <?php endif; ?>
            </div>
        </div>

        <!-- 分頁 -->
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

<style>
/* 頁面橫幅 */
.page-banner {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    padding: 4rem 0;
    margin-bottom: 4rem;
    color: #fff;
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
    background: url('assets/images/pattern.png');
    opacity: 0.1;
    z-index: 1;
}

.page-banner .container {
    position: relative;
    z-index: 2;
}

.page-banner h1 {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

/* 分類過濾 */
.category-filter {
    padding: 1.5rem 0;
    margin-bottom: 3rem;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

.filter-btn {
    display: inline-block;
    padding: 1rem 2rem;
    margin: 0.5rem;
    border-radius: 50px;
    color: #2c3e50;
    background: #f8f9fa;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
}

.filter-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    color: #3498db;
    border-color: #3498db;
}

.filter-btn.active {
    background: #3498db;
    color: #fff;
    border-color: #3498db;
}

/* 相簿卡片 */
.album-grid {
    margin-bottom: 4rem;
}

.album-card {
    position: relative;
    height: 400px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    background: #fff;
}

.album-card:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
}

/* 相簿封面 */
.album-cover {
    position: relative;
    width: 100%;
    height: 100%;
    background: #f8fafc;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.album-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    display: block;
    max-width: 100%;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.album-card:hover .album-cover img {
    transform: scale(1.15);
}

/* 圖片載入動畫 */
.album-cover:not(.loaded)::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(52, 152, 219, 0.1), transparent);
    animation: loading 1.5s infinite;
    z-index: 1;
}

@keyframes loading {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.album-cover.loaded::before {
    display: none;
}

/* 無封面樣式 */
.no-cover {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
    padding: 2rem;
    text-align: center;
}

.no-cover-icon {
    margin-bottom: 1.5rem;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(52, 152, 219, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.no-cover-icon i {
    font-size: 3rem;
    color: #3498db;
}

.no-cover-text {
    font-size: 1.2rem;
    color: #2c3e50;
    font-weight: 600;
}

/* 相簿遮罩 */
.album-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0) 0%,
        rgba(0, 0, 0, 0.5) 50%,
        rgba(0, 0, 0, 0.85) 100%
    );
    opacity: 0;
    transition: opacity 0.5s ease;
}

.album-card:hover .album-overlay {
    opacity: 1;
}

/* 相簿詳細資訊 */
.album-details {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2.5rem;
    color: #fff;
    z-index: 2;
    transform: translateY(30%);
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.album-card:hover .album-details {
    transform: translateY(0);
}

.album-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 1.2rem;
    color: #fff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    line-height: 1.3;
}

.album-meta {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.2rem;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 500;
}

.meta-item i {
    margin-right: 0.8rem;
    font-size: 1.2rem;
}

.album-description {
    font-size: 1.1rem;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1.5rem;
}

/* 相簿操作按鈕 */
.view-album {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    backdrop-filter: blur(10px);
    transition: all 0.4s ease;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.view-album:hover {
    background: #fff;
    color: #2c3e50;
    transform: translateY(-3px);
    border-color: #fff;
}

.view-album i {
    font-size: 1rem;
    transition: transform 0.4s ease;
}

.view-album:hover i {
    transform: translateX(6px);
}

/* 相簿分類標籤 */
.album-category {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.8rem 1.5rem;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 50px;
    font-size: 1rem;
    color: #2c3e50;
    font-weight: 600;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    z-index: 3;
    transition: all 0.3s ease;
}

.album-category:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
}

.album-category i {
    color: #3498db;
    font-size: 1rem;
}

/* 空狀態 */
.empty-state {
    padding: 6rem 0;
    text-align: center;
    color: #7f8c8d;
    background: #f8f9fa;
    border-radius: 20px;
    margin: 2rem 0;
}

.empty-state i {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    color: #3498db;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #2c3e50;
    font-weight: 700;
}

/* 分頁樣式 */
.pagination-wrapper {
    margin-top: 4rem;
    margin-bottom: 3rem;
}

.pagination .page-link {
    border: none;
    padding: 1rem 1.5rem;
    margin: 0 0.4rem;
    color: #2c3e50;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 600;
}

.pagination .page-link:hover {
    background: #e9ecef;
    color: #3498db;
    transform: translateY(-2px);
}

.pagination .page-item.active .page-link {
    background: #3498db;
    color: #fff;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.pagination .page-item.disabled .page-link {
    background: none;
    color: #bdc3c7;
}

/* 響應式調整 */
@media (max-width: 768px) {
    .page-banner {
        padding: 3rem 0;
    }
    
    .page-banner h1 {
        font-size: 2.2rem;
    }
    
    .filter-btn {
        padding: 0.8rem 1.5rem;
        font-size: 0.95rem;
    }
    
    .album-card {
        height: 350px;
    }
    
    .album-details {
        padding: 1.8rem;
    }
    
    .album-title {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .album-meta {
        gap: 1.2rem;
        margin-bottom: 1rem;
    }
    
    .album-description {
        font-size: 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .view-album {
        padding: 0.8rem 1.5rem;
    }
    
    .album-category {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .album-card {
        height: 300px;
    }
    
    .album-title {
        font-size: 1.3rem;
    }
    
    .meta-item {
        font-size: 0.9rem;
    }
    
    .album-description {
        display: none;
    }
}
</style>

<?php require_once 'templates/footer.php'; ?> 