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
            <div class="row g-4">
                <?php foreach ($photos as $photo): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="photo-card">
                            <a href="/uploads/gallery/<?php echo $id; ?>/<?php echo $photo['file_name']; ?>" 
                               class="gallery-item" 
                               data-fancybox="gallery" 
                               data-caption="<?php echo htmlspecialchars($photo['description'] ?: $album['title']); ?>">
                                <img src="/uploads/gallery/<?php echo $id; ?>/<?php echo $photo['file_name']; ?>" 
                                     class="img-fluid" 
                                     alt="<?php echo htmlspecialchars($photo['description'] ?: $album['title']); ?>">
                            </a>
                            <?php if (!empty($photo['description'])): ?>
                                <div class="photo-caption">
                                    <?php echo htmlspecialchars($photo['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Fancybox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
<!-- Fancybox JS -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
    // 初始化 Fancybox
    Fancybox.bind("[data-fancybox]", {
        // 自定義選項
        loop: true,
        buttons: [
            "zoom",
            "slideShow",
            "fullScreen",
            "close"
        ],
        animationEffect: "fade",
        transitionEffect: "fade",
        // 顯示縮圖導航
        Thumbs: {
            autoStart: true,
            type: "classic"
        },
        // 調整圖片大小
        Image: {
            fit: "contain",
            ratio: 16/9,
            maxWidth: "80%",
            maxHeight: "80%"
        },
        // 調整燈箱效果
        Carousel: {
            transition: "slide",
            friction: 0.8
        }
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

/* 照片卡片樣式 */
.photo-gallery {
    padding: 1rem 0;
}

.photo-card {
    position: relative;
    margin-bottom: 2rem;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    aspect-ratio: 3/4;
    background: #fff;
}

.photo-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 30px rgba(0, 0, 0, 0.15);
}

.photo-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.photo-card:hover img {
    transform: scale(1.1);
}

.photo-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    font-size: 0.95rem;
    text-align: center;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.photo-card:hover .photo-caption {
    opacity: 1;
    transform: translateY(0);
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

/* Fancybox 自定義樣式 */
.fancybox__container {
    --fancybox-bg: rgba(0, 0, 0, 0.95);
}

.fancybox__toolbar {
    --fancybox-color: #fff;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
}

.fancybox__nav {
    --fancybox-color: #fff;
}

.fancybox__nav button {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
    border-radius: 50%;
    width: 50px;
    height: 50px;
}

.fancybox__thumbs {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    padding: 10px 0;
}

.fancybox__content {
    padding: 0;
    background: transparent;
    max-width: 85vw;
    max-height: 85vh;
    border-radius: 15px;
    overflow: hidden;
}

.fancybox__caption {
    background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0.5));
    padding: 1.5rem;
    font-size: 1.1rem;
    text-align: center;
    backdrop-filter: blur(10px);
}

/* 響應式調整 */
@media (max-width: 768px) {
    .page-banner {
        padding: 2rem 0;
    }
    
    .page-banner h1 {
        font-size: 2rem;
    }
    
    .album-info .card-body {
        padding: 1.5rem;
    }
    
    .album-info .card-title {
        font-size: 1.5rem;
    }
    
    .photo-card {
        aspect-ratio: 1;
    }
}
</style>

<?php require_once 'templates/footer.php'; ?> 
