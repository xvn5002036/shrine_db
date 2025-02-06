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

try {
    // 檢查並創建相簿資料表
    // 先關閉外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 刪除舊的表格（如果存在）
    $pdo->exec("DROP TABLE IF EXISTS `gallery_images`");
    $pdo->exec("DROP TABLE IF EXISTS `gallery_categories`");

    // 創建分類表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `gallery_categories` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            `description` TEXT,
            `status` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 創建圖片表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `gallery_images` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `category_id` INT,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `image_path` VARCHAR(255) NOT NULL,
            `thumbnail_path` VARCHAR(255),
            `status` TINYINT(1) DEFAULT 1,
            `view_count` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 重新開啟外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 檢查是否需要插入預設分類
    $stmt = $pdo->query("SELECT COUNT(*) FROM gallery_categories");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `gallery_categories` (`name`, `description`, `status`) VALUES
            ('宮廟建築', '本宮建築之美，展現傳統建築工藝', 1),
            ('祭典活動', '重要祭典與慶典活動紀錄', 1),
            ('文物典藏', '珍貴文物與歷史文獻', 1),
            ('節慶活動', '年度重要節慶活動花絮', 1)
        ");
    }

    // 檢查是否需要插入測試圖片
    $stmt = $pdo->query("SELECT COUNT(*) FROM gallery_images");
    if ($stmt->fetchColumn() == 0) {
        // 先確保有分類存在
        $stmt = $pdo->query("SELECT id FROM gallery_categories LIMIT 1");
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            $category_id = $category['id'];
            $pdo->exec("
                INSERT INTO `gallery_images` (`category_id`, `title`, `description`, `image_path`, `status`) VALUES
                ($category_id, '宮廟正面', '宮廟莊嚴的正面建築', 'uploads/gallery/temple-front.jpg', 1),
                ($category_id, '龍柱雕刻', '精美的龍柱雕刻藝術', 'uploads/gallery/dragon-pillar.jpg', 1)
            ");
        }
    }

    // 獲取分類列表
    $stmt = $pdo->query("SELECT * FROM gallery_categories WHERE status = 1 ORDER BY id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取當前分類
    $current_category = isset($_GET['category']) ? (int)$_GET['category'] : null;

    // 構建查詢條件
    $where_clause = "WHERE i.status = 1";
    $params = [];
    
    if ($current_category) {
        $where_clause .= " AND i.category_id = :category_id";
        $params[':category_id'] = $current_category;
    }

    // 獲取圖片列表
    $sql = "
        SELECT i.*, c.name as category_name 
        FROM gallery_images i 
        LEFT JOIN gallery_categories c ON i.category_id = c.id 
        {$where_clause} 
        ORDER BY i.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('資料庫錯誤：' . $e->getMessage());
    die('系統發生錯誤，請稍後再試。錯誤代碼：' . $e->getCode());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 相簿藝廊</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .gallery-container {
            padding: 2rem 0;
        }
        .gallery-filters {
            margin-bottom: 2rem;
            text-align: center;
        }
        .filter-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .filter-btn.active {
            background-color: #c1272d;
            color: white;
            border-color: #c1272d;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 0 1rem;
        }
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: translateY(-5px);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .gallery-item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 1rem;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        .gallery-item:hover .gallery-item-overlay {
            transform: translateY(0);
        }
        .gallery-item-title {
            font-size: 1.1rem;
            margin: 0 0 0.5rem;
        }
        .gallery-item-category {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>相簿藝廊</h1>
            <p class="subtitle">記錄宮廟重要時刻與珍貴回憶</p>
        </div>

        <div class="gallery-container">
            <!-- 分類過濾器 -->
            <div class="gallery-filters">
                <a href="gallery.php" class="filter-btn <?php echo !$current_category ? 'active' : ''; ?>">
                    全部相簿
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="?category=<?php echo $category['id']; ?>" 
                       class="filter-btn <?php echo $current_category === $category['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- 相片網格 -->
            <div class="gallery-grid">
                <?php if (empty($images)): ?>
                    <div class="no-results">
                        <p>目前沒有相片</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-item">
                            <a href="<?php echo htmlspecialchars($image['image_path']); ?>" 
                               data-lightbox="gallery" 
                               data-title="<?php echo htmlspecialchars($image['title']); ?>">
                                <img src="<?php echo htmlspecialchars($image['thumbnail_path'] ?: $image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['title']); ?>">
                                <div class="gallery-item-overlay">
                                    <h3 class="gallery-item-title">
                                        <?php echo htmlspecialchars($image['title']); ?>
                                    </h3>
                                    <div class="gallery-item-category">
                                        <i class="fas fa-folder"></i> 
                                        <?php echo htmlspecialchars($image['category_name']); ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': "圖片 %1 / %2"
        });
    </script>
</body>
</html> 