<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取相片分類
$stmt = $db->prepare("SELECT * FROM gallery_categories WHERE status = 1 ORDER BY sort_order, name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取選定分類
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;

// 準備查詢
if ($selected_category) {
    $sql = "
        SELECT gi.*, ga.title as album_title, gc.name as category_name 
        FROM gallery_images gi 
        JOIN gallery_albums ga ON gi.album_id = ga.id 
        JOIN gallery_categories gc ON ga.category_id = gc.id 
        WHERE ga.category_id = ? AND gi.status = 1 
        ORDER BY gi.sort_order, gi.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$selected_category]);
} else {
    $sql = "
        SELECT gi.*, ga.title as album_title, gc.name as category_name 
        FROM gallery_images gi 
        JOIN gallery_albums ga ON gi.album_id = ga.id 
        JOIN gallery_categories gc ON ga.category_id = gc.id 
        WHERE gi.status = 1 
        ORDER BY gi.sort_order, gi.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
}
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 頁面標題
$page_title = "活動花絮 | " . SITE_NAME;
require_once 'templates/header.php';
?>

<!-- 頁面橫幅 -->
<div class="hero-section" style="background-image: url('assets/images/bg-gallery.jpg');">
    <div class="hero-content">
        <div class="container">
            <h1 data-aos="fade-up">活動花絮</h1>
            <p data-aos="fade-up" data-aos-delay="200">記錄每個珍貴時刻，分享宮廟活動的精彩瞬間</p>
        </div>
    </div>
    <div class="wave-decoration">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
            <path fill="#ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>
</div>

<main>
    <section class="gallery-section">
        <div class="container">
            <!-- 分類選單 -->
            <div class="category-menu" data-aos="fade-up">
                <a href="gallery.php" class="category-btn <?php echo $selected_category ? '' : 'active'; ?>">全部</a>
                <?php foreach ($categories as $category): ?>
                    <a href="?category=<?php echo urlencode($category['id']); ?>" 
                       class="category-btn <?php echo $selected_category === $category['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- 相片網格 -->
            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-card" data-aos="fade-up">
                        <a href="<?php echo htmlspecialchars($photo['image_url']); ?>" 
                           data-fancybox="gallery" 
                           data-caption="<?php echo htmlspecialchars($photo['title']); ?>">
                            <img src="<?php echo htmlspecialchars($photo['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($photo['title']); ?>">
                        </a>
                        <div class="photo-info">
                            <h3><?php echo htmlspecialchars($photo['title']); ?></h3>
                            <?php if ($photo['description']): ?>
                                <p><?php echo htmlspecialchars($photo['description']); ?></p>
                            <?php endif; ?>
                            <span class="category-tag"><?php echo htmlspecialchars($photo['category_name']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once 'templates/footer.php'; ?>

<!-- Fancybox -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

<!-- AOS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    // 初始化 AOS
    AOS.init({
        duration: 800,
        once: true
    });

    // 初始化 Fancybox
    Fancybox.bind("[data-fancybox]", {
        // 設定選項
    });
</script>

<style>
.hero-section {
    position: relative;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding: 120px 0 160px;
    color: #fff;
    text-align: center;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-content h1 {
    font-size: 3em;
    margin-bottom: 20px;
}

.hero-content p {
    font-size: 1.2em;
    max-width: 600px;
    margin: 0 auto;
}

.wave-decoration {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
}

.gallery-section {
    padding: 80px 0;
    background-color: #fff;
}

.category-menu {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 40px;
}

.category-btn {
    padding: 10px 20px;
    border: 2px solid #c19b77;
    border-radius: 30px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.category-btn:hover,
.category-btn.active {
    background-color: #c19b77;
    color: #fff;
}

.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    padding: 20px 0;
}

.photo-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.photo-card:hover {
    transform: translateY(-5px);
}

.photo-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}

.photo-info {
    padding: 20px;
}

.photo-info h3 {
    margin: 0 0 10px;
    font-size: 1.2em;
    color: #333;
}

.photo-info p {
    margin: 0 0 15px;
    color: #666;
    font-size: 0.9em;
}

.category-tag {
    display: inline-block;
    padding: 5px 10px;
    background-color: #f0f0f0;
    border-radius: 15px;
    font-size: 0.8em;
    color: #666;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 80px 0 120px;
    }

    .hero-content h1 {
        font-size: 2.5em;
    }

    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
}
</style> 