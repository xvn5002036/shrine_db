<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// 獲取所有啟用的祈福類型
$stmt = $pdo->prepare("
    SELECT * FROM blessing_types 
    WHERE status = 1 
    ORDER BY is_featured DESC, sort_order ASC, name ASC
");
$stmt->execute();
$blessing_types = $stmt->fetchAll();

// 頁面標題
$page_title = '祈福服務 | ' . SITE_NAME;
$current_page = 'blessings';
require_once '../templates/header.php';
?>



<!-- 服務說明 -->


<!-- 特色祈福 -->
<?php if (!empty($featured_types)): ?>
<section id="featured-blessings" class="featured-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>特色祈福</h2>
            <p>精選推薦的祈福服務，為您帶來最殊勝的祝福</p>
        </div>
        <div class="row">
            <?php foreach ($featured_types as $type): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blessing-card featured" data-aos="fade-up">
                    <div class="card-image">
                        <!-- 祈福項目圖片 -->
                        <div class="placeholder-image">
                            <i class="fas fa-image"></i>
                            <span><?php echo htmlspecialchars($type['name']); ?></span>
                        </div>
                        <div class="featured-badge">
                            <i class="fas fa-star"></i> 特色推薦
                        </div>
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($type['name']); ?></h3>
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($type['description'])); ?>
                        </div>
                        <div class="info-tags">
                            <?php if ($type['duration']): ?>
                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($type['duration']); ?></span>
                            <?php endif; ?>
                            <?php if ($type['max_daily_slots']): ?>
                            <span><i class="fas fa-users"></i> 每日限額：<?php echo $type['max_daily_slots']; ?> 名</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="price">
                                <span class="currency">NT$</span>
                                <span class="amount"><?php echo number_format($type['price']); ?></span>
                            </div>
                            <a href="booking.php?type=<?php echo $type['slug']; ?>" class="btn-book">
                                立即預約 <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 所有祈福項目 -->
<section id="all-blessings" class="all-blessings-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>所有祈福項目</h2>
            <p>多元化的祈福服務，滿足您不同的祈願需求</p>
        </div>
        <div class="row">
            <?php foreach ($blessing_types as $type): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blessing-card" data-aos="fade-up">
                    <div class="card-image">
                        <!-- 祈福項目圖片 -->
                        <div class="placeholder-image">
                            <i class="fas fa-image"></i>
                            <span><?php echo htmlspecialchars($type['name']); ?></span>
                        </div>
                        <?php if ($type['is_featured']): ?>
                        <div class="featured-tag">
                            <i class="fas fa-star"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($type['name']); ?></h3>
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($type['description'])); ?>
                        </div>
                        <div class="info-tags">
                            <?php if ($type['duration']): ?>
                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($type['duration']); ?></span>
                            <?php endif; ?>
                            <?php if ($type['max_daily_slots']): ?>
                            <span><i class="fas fa-users"></i> 每日限額：<?php echo $type['max_daily_slots']; ?> 名</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="price">
                                <span class="currency">NT$</span>
                                <span class="amount"><?php echo number_format($type['price']); ?></span>
                            </div>
                            <a href="booking.php?type=<?php echo $type['slug']; ?>" class="btn-book">
                                立即預約 <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 注意事項 -->
<section class="notice-section">
    <div class="container">
        <div class="notice-card" data-aos="fade-up">
            <div class="notice-header">
                <i class="fas fa-info-circle"></i>
                <h3>預約注意事項</h3>
            </div>
            <div class="notice-content">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="notice-list">
                            <li>請提前預約，以確保時段安排</li>
                            <li>預約時請提供正確的聯絡資訊</li>
                            <li>如需更改或取消預約，請提前通知</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="notice-list">
                            <li>特殊節日可能會調整服務時間，請留意公告</li>
                            <li>如有任何疑問，歡迎聯繫我們</li>
                            <li>祈福過程請保持虔誠與寧靜</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* 動畫效果 */
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

/* 頁面橫幅 */
.hero-banner {
    position: relative;
    height: 600px;
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 80px;
}

.hero-content {
    max-width: 800px;
    padding: 0 20px;
}

.hero-content h1 {
    font-size: 3.5em;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-content p {
    font-size: 1.5em;
    margin-bottom: 30px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.hero-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
}

.hero-buttons a {
    padding: 15px 30px;
    border-radius: 50px;
    font-size: 1.1em;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primary {
    background: #c1272d;
    color: #fff;
    border: 2px solid #c1272d;
}

.btn-primary:hover {
    background: #a01f24;
    color: #fff;
    transform: translateY(-3px);
}

.btn-secondary {
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    transform: translateY(-3px);
}

/* 介紹區塊 */
.intro-section {
    padding: 80px 0;
    background: #fff;
}

.intro-content {
    padding-right: 50px;
}

.intro-content h2 {
    font-size: 2.5em;
    color: #333;
    margin-bottom: 25px;
    font-weight: 700;
}

.intro-content p {
    font-size: 1.1em;
    color: #666;
    line-height: 1.8;
    margin-bottom: 30px;
}

.service-features {
    display: flex;
    gap: 30px;
    margin-top: 30px;
}

.feature {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #c1272d;
}

.feature i {
    font-size: 1.5em;
}

.intro-image {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.intro-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

/* 特色祈福區塊 */
.featured-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5em;
    color: #333;
    margin-bottom: 15px;
    font-weight: 700;
}

.section-header p {
    color: #666;
    font-size: 1.1em;
}

.blessing-card {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.blessing-card:hover {
    transform: translateY(-10px);
}

.blessing-card.featured {
    border: 2px solid #ffd700;
}

.card-image {
    position: relative;
    height: 250px;
    overflow: hidden;
    background: #f8f9fa;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.3s ease;
}

.blessing-card:hover .card-image img {
    transform: scale(1.1);
}

.featured-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #ffd700;
    color: #000;
    padding: 8px 15px;
    border-radius: 50px;
    font-size: 0.9em;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.featured-tag {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #ffd700;
    color: #000;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-content {
    padding: 25px;
}

.card-content h3 {
    font-size: 1.5em;
    color: #333;
    margin-bottom: 15px;
    font-weight: 600;
}

.description {
    color: #666;
    font-size: 0.95em;
    line-height: 1.6;
    margin-bottom: 20px;
    height: 80px;
    overflow: hidden;
}

.info-tags {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.info-tags span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f8f9fa;
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.9em;
    color: #666;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.price {
    color: #c1272d;
}

.price .currency {
    font-size: 0.9em;
}

.price .amount {
    font-size: 1.5em;
    font-weight: 700;
}

.btn-book {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #c1272d;
    color: #fff;
    padding: 10px 20px;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-book:hover {
    background: #a01f24;
    color: #fff;
    transform: translateX(5px);
}

/* 所有祈福項目區塊 */
.all-blessings-section {
    padding: 80px 0;
    background: #fff;
}

/* 注意事項區塊 */
.notice-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.notice-card {
    background: #fff;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.notice-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}

.notice-header i {
    font-size: 2em;
    color: #c1272d;
}

.notice-header h3 {
    font-size: 1.8em;
    color: #333;
    margin: 0;
    font-weight: 600;
}

.notice-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notice-list li {
    position: relative;
    padding-left: 30px;
    margin-bottom: 15px;
    color: #666;
    font-size: 1.1em;
}

.notice-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 8px;
    width: 8px;
    height: 8px;
    background: #c1272d;
    border-radius: 50%;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .hero-banner {
        height: 400px;
    }

    .hero-content h1 {
        font-size: 2.5em;
    }

    .hero-content p {
        font-size: 1.2em;
    }

    .hero-buttons {
        flex-direction: column;
    }

    .intro-content {
        padding-right: 0;
        margin-bottom: 40px;
    }

    .service-features {
        flex-direction: column;
        gap: 15px;
    }

    .notice-card {
        padding: 25px;
    }
}

/* 圖片預留位置樣式 */
.placeholder-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}

.placeholder-image i {
    font-size: 2em;
    margin-bottom: 10px;
}

.placeholder-image span {
    font-size: 0.9em;
    text-align: center;
    padding: 0 10px;
}

/* 修改介紹圖片容器樣式 */
.intro-image {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    height: 400px;
    background: #f8f9fa;
}
</style>

<!-- 動畫效果 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 1000,
        once: true
    });
</script>

<?php require_once '../includes/footer.php'; ?> 
