<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取要顯示的部分
$section = isset($_GET['section']) ? $_GET['section'] : 'history';

// 從資料庫獲取關於我們的內容
try {
    $pdo = $GLOBALS['pdo'];
    $sql = "SELECT value FROM settings WHERE `key` = 'about_content'";
    $stmt = $pdo->query($sql);
    $about_content = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Error fetching about content: ' . $e->getMessage());
    $about_content = '暫無內容';
}

$page_title = "關於本宮 | " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AOS 動畫效果 -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <!-- 頁面橫幅 -->


    <!-- 導航標籤 -->
    <div class="section-nav">
        <div class="container">
            <div class="nav-wrapper" data-aos="fade-up">
                <a href="?section=history" class="nav-item <?php echo $section == 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>宮廟歷史</span>
                </a>
                <a href="?section=architecture" class="nav-item <?php echo $section == 'architecture' ? 'active' : ''; ?>">
                    <i class="fas fa-landmark"></i>
                    <span>建築特色</span>
                </a>
                <a href="?section=traffic" class="nav-item <?php echo $section == 'traffic' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>交通指引</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 主要內容區 -->
    <main class="main-content">
        <div class="container">
            <?php if ($section == 'history'): ?>
                <!-- 歷史沿革 -->
                <section class="content-section" data-aos="fade-up">
                    <div class="section-header">
                        <h2>宮廟歷史</h2>
                        <p>傳承百年，延續信仰</p>
                    </div>
                    
                    <div class="history-timeline">
                        <!-- 時間軸項目 -->
                        <div class="timeline-item" data-aos="fade-up">
                            <div class="year">1830</div>
                            <div class="content">
                                <h3>創建初期</h3>
                                <p>本宮始建於清道光年間，由地方仕紳集資興建，初期為簡單的土角厝建築。</p>
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>創建初期歷史照片</span>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item" data-aos="fade-up">
                            <div class="year">1875</div>
                            <div class="content">
                                <h3>首次擴建</h3>
                                <p>因信眾日增，進行首次擴建工程，增建三川殿，奠定基本格局。</p>
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>擴建工程照片</span>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item" data-aos="fade-up">
                            <div class="year">1961</div>
                            <div class="content">
                                <h3>全面重建</h3>
                                <p>進行全面重建工程，擴大規模，建立現今主體建築。</p>
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>重建工程照片</span>
                                </div>
                            </div>
                        </div>

                        <div class="timeline-item" data-aos="fade-up">
                            <div class="year">2011</div>
                            <div class="content">
                                <h3>文化傳承</h3>
                                <p>增建文化館，致力推廣宗教文化教育，保存珍貴文物。</p>
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>文化館照片</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            <?php elseif ($section == 'architecture'): ?>
                <!-- 建築特色 -->
                <section class="content-section" data-aos="fade-up">
                    <div class="section-header">
                        <h2>建築特色</h2>
                        <p>精湛工藝，文化瑰寶</p>
                    </div>

                    <div class="architecture-features">
                        <!-- 建築特色卡片 -->
                        <div class="feature-card" data-aos="fade-up">
                            <div class="card-header">
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>三川殿照片</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h3><i class="fas fa-torii-gate"></i> 三川殿</h3>
                                <p>採用傳統閩南建築風格，雙龍盤柱氣勢磅礴，門楣上方有精緻的交趾陶裝飾。</p>
                            </div>
                        </div>

                        <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                            <div class="card-header">
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>正殿照片</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h3><i class="fas fa-gopuram"></i> 正殿建築</h3>
                                <p>屋頂採用傳統燕尾脊，龍鳳呈祥，內部樑柱彩繪精美，展現傳統工藝之美。</p>
                            </div>
                        </div>

                        <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                            <div class="card-header">
                                <!-- 預留圖片位置 -->
                                <div class="placeholder-image">
                                    <i class="fas fa-image"></i>
                                    <span>石雕藝術照片</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h3><i class="fas fa-chess-rook"></i> 石雕藝術</h3>
                                <p>廟宇周圍石雕精美，龍柱、獅座等皆為名匠手工雕琢，工藝精湛。</p>
                            </div>
                        </div>
                    </div>
                </section>

            <?php else: ?>
                <!-- 交通指引 -->
                <section class="content-section" data-aos="fade-up">
                    <div class="section-header">
                        <h2>交通指引</h2>
                        <p>便捷抵達，輕鬆參訪</p>
                    </div>

                    <div class="traffic-info">
                        <div class="map-container" data-aos="fade-up">
                            <!-- 預留地圖位置 -->
                            <div class="placeholder-map">
                                <i class="fas fa-map-marked-alt"></i>
                                <span>Google 地圖</span>
                            </div>
                        </div>

                        <div class="info-cards">
                            <div class="info-card" data-aos="fade-up">
                                <div class="card-icon">
                                    <i class="fas fa-bus"></i>
                                </div>
                                <h3>大眾運輸</h3>
                                <ul>
                                    <li>公車：255、307路線至「某某站」下車</li>
                                    <li>捷運：某某線至「某某站」2號出口步行約10分鐘</li>
                                </ul>
                            </div>

                            <div class="info-card" data-aos="fade-up" data-aos-delay="100">
                                <div class="card-icon">
                                    <i class="fas fa-car"></i>
                                </div>
                                <h3>開車前往</h3>
                                <ul>
                                    <li>國道一號：某某交流道下，往某某方向約10分鐘</li>
                                    <li>備有免費信徒停車場，可停放約50輛車</li>
                                </ul>
                            </div>

                            <div class="info-card" data-aos="fade-up" data-aos-delay="200">
                                <div class="card-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3>開放時間</h3>
                                <ul>
                                    <li>平日：<?php echo htmlspecialchars(getSetting('weekday_hours')); ?></li>
                                    <li>假日：<?php echo htmlspecialchars(getSetting('weekend_hours')); ?></li>
                                    <li>國定假日：<?php echo htmlspecialchars(getSetting('holiday_hours')); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <style>
    /* 頁面橫幅 */
    .hero-banner {
        height: 400px;
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #fff;
        margin-bottom: 0;
    }

    .hero-content h1 {
        font-size: 3em;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .hero-content p {
        font-size: 1.2em;
        opacity: 0.9;
    }

    /* 導航標籤 */
    .section-nav {
        background: #fff;
        padding: 20px 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-wrapper {
        display: flex;
        justify-content: center;
        gap: 30px;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #666;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .nav-item i {
        font-size: 1.5em;
        margin-bottom: 5px;
    }

    .nav-item.active {
        background: #c1272d;
        color: #fff;
    }

    .nav-item:hover {
        transform: translateY(-3px);
        color: #c1272d;
    }

    .nav-item.active:hover {
        color: #fff;
    }

    /* 主要內容區 */
    .main-content {
        padding: 60px 0;
        background: #f8f9fa;
    }

    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-header h2 {
        font-size: 2.5em;
        color: #333;
        margin-bottom: 10px;
    }

    .section-header p {
        color: #666;
        font-size: 1.1em;
    }

    /* 歷史時間軸 */
    .history-timeline {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
        padding: 40px 0;
    }

    .history-timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 100%;
        background: #c1272d;
        top: 0;
    }

    .timeline-item {
        display: flex;
        justify-content: center;
        padding: 30px 0;
        position: relative;
    }

    .timeline-item:nth-child(odd) {
        flex-direction: row-reverse;
    }

    .year {
        min-width: 100px;
        padding: 10px;
        background: #c1272d;
        color: #fff;
        text-align: center;
        border-radius: 5px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .content {
        width: 45%;
        padding: 20px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin: 0 20px;
    }

    .content h3 {
        color: #333;
        margin-bottom: 10px;
    }

    /* 建築特色 */
    .architecture-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 20px;
    }

    .feature-card {
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-10px);
    }

    .card-header {
        height: 200px;
        overflow: hidden;
    }

    .card-body {
        padding: 20px;
    }

    .card-body h3 {
        color: #333;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-body h3 i {
        color: #c1272d;
    }

    /* 交通資訊 */
    .traffic-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .map-container {
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        height: 400px;
    }

    .info-cards {
        display: grid;
        gap: 20px;
    }

    .info-card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .card-icon {
        width: 50px;
        height: 50px;
        background: #c1272d;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5em;
        margin-bottom: 15px;
    }

    .info-card h3 {
        color: #333;
        margin-bottom: 10px;
    }

    .info-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-card li {
        color: #666;
        margin-bottom: 8px;
        padding-left: 20px;
        position: relative;
    }

    .info-card li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 8px;
        width: 6px;
        height: 6px;
        background: #c1272d;
        border-radius: 50%;
    }

    /* 圖片預留位置 */
    .placeholder-image {
        width: 100%;
        height: 100%;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        padding: 20px;
    }

    .placeholder-image i {
        font-size: 2em;
        margin-bottom: 10px;
    }

    .placeholder-image span {
        text-align: center;
        font-size: 0.9em;
    }

    .placeholder-map {
        width: 100%;
        height: 100%;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
    }

    .placeholder-map i {
        font-size: 3em;
        margin-bottom: 15px;
    }

    /* 響應式設計 */
    @media (max-width: 768px) {
        .hero-banner {
            height: 300px;
        }

        .hero-content h1 {
            font-size: 2em;
        }

        .nav-wrapper {
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav-item {
            padding: 8px 15px;
        }

        .traffic-info {
            grid-template-columns: 1fr;
        }

        .history-timeline::before {
            left: 20px;
        }

        .timeline-item {
            flex-direction: row !important;
            justify-content: flex-start;
        }

        .content {
            width: calc(100% - 140px);
            margin-left: 20px;
        }

        .year {
            min-width: 80px;
        }
    }
    </style>

    <!-- AOS 動畫效果 -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>

    <?php include 'templates/footer.php'; ?>
</body>
</html> 