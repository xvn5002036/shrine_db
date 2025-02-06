<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 檢查並建立資料表
try {
    // 建立相簿分類表
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 檢查是否已有分類資料
    $stmt = $pdo->query("SELECT COUNT(*) FROM gallery_categories");
    if ($stmt->fetchColumn() == 0) {
        // 插入預設相簿分類
        $pdo->exec("INSERT INTO gallery_categories (name, description, status) VALUES
            ('法會活動', '各式法會活動花絮', 1),
            ('節慶慶典', '重要節慶與慶典紀錄', 1),
            ('建築風貌', '宮廟建築與環境', 1),
            ('文化展演', '文化活動與展覽', 1)");
    }
    
    // 建立相片表
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_path VARCHAR(255) NOT NULL,
        thumbnail_path VARCHAR(255),
        status TINYINT(1) DEFAULT 1,
        view_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES gallery_categories(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 檢查是否已有圖片資料
    $stmt = $pdo->query("SELECT COUNT(*) FROM gallery_images");
    if ($stmt->fetchColumn() == 0) {
        // 插入範例相片
        $pdo->exec("INSERT INTO gallery_images (category_id, title, description, image_path, status) VALUES
            (1, '浴佛法會', '浴佛節法會活動現場', 'uploads/gallery/sample1.jpg', 1),
            (1, '祈福法會', '年度祈福法會盛況', 'uploads/gallery/sample2.jpg', 1),
            (2, '新春祭祀', '農曆新年祭祀儀式', 'uploads/gallery/sample3.jpg', 1),
            (3, '宮廟外觀', '宮廟建築之美', 'uploads/gallery/sample4.jpg', 1)");
    }
} catch (PDOException $e) {
    error_log("資料表建立錯誤：" . $e->getMessage());
}

// 獲取分類
$stmt = $pdo->query("SELECT * FROM gallery_categories WHERE status = 1");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取當前分類
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // 每頁顯示12張圖片
$offset = ($page - 1) * $limit;

// 構建查詢條件
$where = "WHERE gi.status = 1";
$params = array();
if ($category_id) {
    $where .= " AND gi.category_id = " . $category_id;
}

// 獲取總數
$sql = "SELECT COUNT(*) FROM gallery_images gi " . $where;
$total = $pdo->query($sql)->fetchColumn();
$total_pages = ceil($total / $limit);

// 獲取圖片
$sql = "SELECT gi.*, ga.title as album_title, gc.name as category_name 
        FROM gallery_images gi 
        LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
        LEFT JOIN gallery_categories gc ON ga.category_id = gc.id 
        {$where} 
        ORDER BY gi.created_at DESC 
        LIMIT {$offset}, {$limit}";
$images = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 頁面標題
$page_title = "活動花絮 | " . SITE_NAME;
require_once 'templates/header.php';
?>

<!-- 頁面橫幅 -->
<!-- <div class="hero-section" style="background-image: url('assets/images/bg-gallery.jpg');">
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
</div> -->

<main>
    <section class="gallery-section">
        <div class="container">
            <!-- 分類選單 -->
            <div class="categories mb-4">
                <a href="gallery.php" class="btn <?php echo !$category_id ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                    全部
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="gallery.php?category=<?php echo $cat['id']; ?>" 
                       class="btn <?php echo $category_id == $cat['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($images)): ?>
                <div class="alert alert-info">
                    目前沒有相片
                </div>
            <?php else: ?>
                <!-- 相片網格 -->
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($images as $image): ?>
                        <div class="col">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($image['title']); ?>"
                                     style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                    <p class="card-text small text-muted">
                                        <?php echo htmlspecialchars($image['category_name']); ?> | 
                                        <?php echo date('Y/m/d', strtotime($image['created_at'])); ?>
                                    </p>
                                    <?php if ($image['description']): ?>
                                        <p class="card-text">
                                            <?php echo nl2br(htmlspecialchars($image['description'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        <i class="fas fa-eye"></i> <?php echo number_format($image['view_count']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 分頁 -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> 上一頁
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>">
                                        下一頁 <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

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