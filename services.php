<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 初始化變數
$services = [];
$types = [];
$error = null;
$selected_type = isset($_GET['type']) ? $_GET['type'] : null;

try {
    // 獲取服務類型
    $stmt = $pdo->query("SELECT * FROM service_types WHERE status = 1 ORDER BY sort_order");
    if ($stmt) {
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 獲取服務列表
    $query = "SELECT s.*, t.name as type_name 
              FROM services s 
              JOIN service_types t ON s.type_id = t.id 
              WHERE s.status = 1";
    
    if ($selected_type) {
        $query .= " AND t.slug = " . $pdo->quote($selected_type);
    }
    
    $query .= " ORDER BY t.sort_order, s.sort_order, s.name";
    
    $stmt = $pdo->query($query);
    if ($stmt) {
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new PDOException("無法獲取服務資料");
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '系統發生錯誤，請稍後再試';
}

// 頁面標題
$page_title = '宮廟服務項目';
require_once 'templates/header.php';
?>

<main class="container">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 服務類型選單 -->
    <div class="service-types-nav">
        <a href="services.php" class="btn <?php echo !$selected_type ? 'btn-primary' : 'btn-outline-primary'; ?>">
            全部服務
        </a>
        <?php foreach ($types as $type): ?>
            <a href="services.php?type=<?php echo htmlspecialchars($type['slug']); ?>" 
               class="btn <?php echo $selected_type === $type['slug'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo htmlspecialchars($type['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="services-container">
        <div class="services-grid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <?php if (!empty($service['image'])): ?>
                            <div class="service-image">
                                <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($service['name']); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="service-content">
                            <div class="service-type-badge">
                                <?php echo htmlspecialchars($service['type_name']); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <?php if ($service['price']): ?>
                                <p class="service-price">NT$ <?php echo number_format($service['price']); ?></p>
                            <?php endif; ?>
                            <div class="service-description">
                                <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                            </div>
                            <?php if (!empty($service['duration'])): ?>
                                <p class="service-duration">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo htmlspecialchars($service['duration']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($service['notice'])): ?>
                                <div class="service-notice">
                                    <h4>注意事項：</h4>
                                    <?php echo nl2br(htmlspecialchars($service['notice'])); ?>
                                </div>
                            <?php endif; ?>
                            <div class="service-actions">
                                <?php if ($service['booking_required']): ?>
                                    <a href="booking.php?service_id=<?php echo $service['id']; ?>" 
                                       class="btn btn-primary">立即預約</a>
                                <?php else: ?>
                                    <a href="contact.php" class="btn btn-outline-primary">聯絡我們</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">目前沒有可用的服務項目</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>

<style>
.services-container {
    padding: 40px 0;
}

.service-types-nav {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin: 0 auto;
    max-width: 1200px;
}

.service-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-5px);
}

.service-image {
    position: relative;
    padding-top: 60%;
    overflow: hidden;
}

.service-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-content {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.service-type-badge {
    display: inline-block;
    padding: 4px 12px;
    background-color: #f8f9fa;
    color: #666;
    border-radius: 20px;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.service-content h3 {
    margin: 0 0 10px;
    color: #333;
    font-size: 1.5rem;
}

.service-price {
    color: #c19b77;
    font-size: 1.4em;
    font-weight: bold;
    margin: 10px 0;
}

.service-description {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
    flex-grow: 1;
}

.service-duration {
    color: #888;
    font-size: 0.9em;
    margin: 10px 0;
}

.service-duration i {
    margin-right: 5px;
}

.service-notice {
    background-color: #fff8e1;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
    font-size: 0.9em;
}

.service-notice h4 {
    color: #856404;
    margin: 0 0 10px;
    font-size: 1em;
}

.service-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.service-actions .btn {
    flex: 1;
    text-align: center;
}

@media (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
        padding: 0 15px;
    }
    
    .service-card {
        margin-bottom: 20px;
    }

    .service-types-nav {
        padding: 0 15px;
    }
}
</style> 