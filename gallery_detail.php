<?php
require_once 'config/config.php';
require_once 'includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gallery.php');
    exit();
}

$id = (int)$_GET['id'];

// 定義相簿分類
$categories = [
    'temple' => '宮廟建築',
    'ceremony' => '祭典活動',
    'collection' => '文物典藏',
    'festival' => '節慶活動'
];

try {
    // 獲取相簿資訊
    $stmt = $pdo->prepare("
        SELECT a.*, u.username as creator_name 
        FROM gallery_albums a 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE a.id = ? AND a.status = 'published'
    ");
    $stmt->execute([$id]);
    $album = $stmt->fetch();

    if (!$album) {
        header('Location: gallery.php');
        exit();
    }

    // 獲取相簿中的所有照片
    $stmt = $pdo->prepare("
        SELECT * FROM gallery_photos 
        WHERE album_id = ? 
        ORDER BY sort_order ASC, created_at DESC
    ");
    $stmt->execute([$id]);
    $photos = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching album details: ' . $e->getMessage());
    header('Location: gallery.php');
    exit();
}

$page_title = $album['title'] . ' - 活動花絮';
$current_page = 'gallery';
require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><?php echo htmlspecialchars($album['title']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                <li class="breadcrumb-item"><a href="gallery.php">活動花絮</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($album['title']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <!-- 相簿資訊 -->
        <div class="album-info mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h2 class="card-title"><?php echo htmlspecialchars($album['title']); ?></h2>
                            <p class="text-muted">
                                <i class="fas fa-calendar-alt"></i> 活動日期：<?php echo date('Y年m月d日', strtotime($album['event_date'])); ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-folder"></i> 分類：<?php echo $categories[$album['category']] ?? '未分類'; ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-user"></i> 建立者：<?php echo htmlspecialchars($album['creator_name']); ?>
                            </p>
                            <?php if (!empty($album['description'])): ?>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($album['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="gallery.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 返回列表
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 照片展示 -->
        <div class="photo-gallery">
            <div class="masonry-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="masonry-item">
                        <div class="photo-card">
                            <div class="photo-card-inner">
                                <a href="/uploads/gallery/<?php echo $id; ?>/<?php echo $photo['filename']; ?>" 
                                   class="gallery-item" 
                                   data-fancybox="gallery" 
                                   data-caption="<?php echo htmlspecialchars($photo['description'] ?: $album['title']); ?>">
                                    <img src="/uploads/gallery/<?php echo $id; ?>/<?php echo $photo['filename']; ?>" 
                                         class="img-fluid" 
                                         alt="<?php echo htmlspecialchars($photo['description'] ?: $album['title']); ?>"
                                         loading="lazy">
                                    <?php if (!empty($photo['description'])): ?>
                                        <div class="photo-overlay">
                                            <div class="photo-description">
                                                <?php echo htmlspecialchars($photo['description']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Masonry JS -->
<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
<script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js"></script>

<!-- Fancybox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
<!-- Fancybox JS -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化 Masonry
        var grid = document.querySelector('.masonry-grid');
        var masonry = new Masonry(grid, {
            itemSelector: '.masonry-item',
            columnWidth: '.masonry-item',
            percentPosition: true,
            gutter: 20
        });

        // 處理圖片載入
        var images = document.querySelectorAll('.photo-card img');
        images.forEach(function(img) {
            // 設置初始透明度為 1
            img.style.opacity = '1';
            
            img.addEventListener('load', function() {
                this.classList.add('loaded');
                masonry.layout();
            });
        });

        // 當所有圖片載入完成後重新排列
        imagesLoaded(grid).on('progress', function() {
            masonry.layout();
        });

        // 初始化 Fancybox
        Fancybox.bind("[data-fancybox]", {
            loop: true,
            buttons: [
                "zoom",
                "slideShow",
                "fullScreen",
                "thumbs",
                "close"
            ],
            animationEffect: "fade",
            transitionEffect: "fade",
            Thumbs: {
                autoStart: true,
                type: "modern"
            },
            Image: {
                fit: "contain",
                ratio: 16/9,
                maxWidth: "90%",
                maxHeight: "90%"
            },
            Carousel: {
                transition: "slide",
                friction: 0.8
            }
        });
    });
</script>

<script>
// 當頁面載入完成後顯示網格
window.addEventListener('load', function() {
    document.querySelector('.masonry-grid').classList.add('loaded');
});
</script>

<style>
/* 頁面整體樣式 */
.page-banner {
    background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    padding: 3rem 0;
    margin-bottom: 3rem;
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
    background: rgba(0, 0, 0, 0.2);
    z-index: 1;
}

.page-banner .container {
    position: relative;
    z-index: 2;
}

.page-banner h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.breadcrumb {
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    backdrop-filter: blur(5px);
}

.breadcrumb-item a {
    color: #fff;
    text-decoration: none;
}

.breadcrumb-item.active {
    color: rgba(255, 255, 255, 0.8);
}

/* 相簿資訊卡片 */
.album-info .card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    background: #fff;
    margin-bottom: 2rem;
    overflow: hidden;
}

.album-info .card-body {
    padding: 2rem;
}

.album-info .card-title {
    color: #2d3436;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.album-info .text-muted {
    color: #636e72 !important;
    font-size: 1rem;
    line-height: 1.6;
}

.album-info .text-muted i {
    color: #00b894;
    margin-right: 8px;
}

/* 照片展示區域樣式 */
.photo-gallery {
    padding: 2rem 0;
}

.masonry-grid {
    width: 100%;
    margin: 0 auto;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.masonry-grid.loaded {
    opacity: 1;
}

.masonry-item {
    width: calc(33.333% - 20px);
    margin-bottom: 20px;
    break-inside: avoid;
    opacity: 1;
    animation: none;
}

@media (max-width: 992px) {
    .masonry-item {
        width: calc(50% - 20px);
    }
}

@media (max-width: 576px) {
    .masonry-item {
        width: 100%;
    }
}

.photo-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.photo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.photo-card-inner {
    position: relative;
    width: 100%;
    min-height: 100px;
    background: #f8f9fa;
}

.photo-card img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 12px;
    opacity: 1;
    transition: transform 0.3s ease;
}

.photo-card:hover img {
    transform: scale(1.05);
}

.photo-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
    padding: 20px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-card:hover .photo-overlay {
    opacity: 1;
}

.photo-description {
    color: #fff;
    font-size: 0.9rem;
    line-height: 1.4;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

/* Fancybox 客製化樣式 */
.fancybox__container {
    --fancybox-bg: rgba(0, 0, 0, 0.95);
}

.fancybox__toolbar {
    --fancybox-color: #fff;
    background: rgba(0, 0, 0, 0.3);
}

.fancybox__nav {
    --fancybox-color: #fff;
}

.fancybox__thumbs {
    background: rgba(0, 0, 0, 0.3);
}

/* 按鈕樣式 */
.btn-secondary {
    background: linear-gradient(135deg, #6c5ce7 0%, #a363d9 100%);
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    color: #fff;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(108, 92, 231, 0.2);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
    background: linear-gradient(135deg, #5d4adb 0%, #9349d3 100%);
}
</style>

<?php include 'includes/footer.php'; ?> 
