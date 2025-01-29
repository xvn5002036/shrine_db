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
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 關於本宮</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>關於本宮</h1>
            <nav class="breadcrumb">
                <a href="index.php">首頁</a> &gt; 關於本宮
            </nav>
        </div>

        <div class="about-nav">
            <a href="?section=history" class="<?php echo $section == 'history' ? 'active' : ''; ?>">宮廟歷史</a>
            <a href="?section=architecture" class="<?php echo $section == 'architecture' ? 'active' : ''; ?>">建築特色</a>
            <a href="?section=traffic" class="<?php echo $section == 'traffic' ? 'active' : ''; ?>">交通指引</a>
        </div>

        <div class="about-content">
            <?php if ($section == 'history'): ?>
                <section class="history-section">
                    <h2>宮廟歷史</h2>
                    <div class="history-content">
                        <img src="assets/images/temple/history.jpg" alt="宮廟歷史照片" class="history-image">
                        <div class="text-content">
                            <?php echo nl2br(htmlspecialchars($about_content)); ?>
                            
                            <div class="timeline">
                                <h3>重要里程碑</h3>
                                <ul>
                                    <li>
                                        <span class="year">1830年</span>
                                        <span class="event">創建完工，初期為簡單的土角厝建築</span>
                                    </li>
                                    <li>
                                        <span class="year">1875年</span>
                                        <span class="event">第一次擴建，增建三川殿</span>
                                    </li>
                                    <li>
                                        <span class="year">1961年</span>
                                        <span class="event">重建主殿，奠定現今規模</span>
                                    </li>
                                    <li>
                                        <span class="year">1991年</span>
                                        <span class="event">整體翻修，修復古建築特色</span>
                                    </li>
                                    <li>
                                        <span class="year">2011年</span>
                                        <span class="event">增建文化館，推廣宗教文化教育</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            <?php elseif ($section == 'architecture'): ?>
                <section class="architecture-section">
                    <h2>建築特色</h2>
                    <div class="architecture-content">
                        <div class="architecture-gallery">
                            <div class="gallery-grid">
                                <div class="gallery-item">
                                    <img src="assets/images/temple/architecture1.jpg" alt="三川殿外觀" class="gallery-image">
                                    <div class="gallery-caption">三川殿莊嚴外觀</div>
                                </div>
                                <div class="gallery-item">
                                    <img src="assets/images/temple/architecture2.jpg" alt="龍柱雕刻" class="gallery-image">
                                    <div class="gallery-caption">精緻龍柱雕刻</div>
                                </div>
                                <div class="gallery-item">
                                    <img src="assets/images/temple/architecture3.jpg" alt="剪黏藝術" class="gallery-image">
                                    <div class="gallery-caption">屋頂剪黏藝術</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-content">
                            <h3>建築風格</h3>
                            <p>本宮建築採傳統閩南式建築風格，三川殿、正殿、後殿呈三進配置，具有豐富的歷史文化價值。整體建築充分展現了傳統匠師的精湛工藝，是北台灣重要的古建築代表。</p>
                            
                            <h3>建築特色</h3>
                            <ul class="feature-list">
                                <li>
                                    <i class="fas fa-columns"></i>
                                    <strong>三川殿：</strong>雙龍盤柱，氣勢磅礴，門楣上方有精緻的交趾陶裝飾
                                </li>
                                <li>
                                    <i class="fas fa-chess-rook"></i>
                                    <strong>正殿：</strong>精緻剪黏，藝術價值極高，屋頂脊飾以龍鳳為主題
                                </li>
                                <li>
                                    <i class="fas fa-monument"></i>
                                    <strong>後殿：</strong>泥塑工藝，栩栩如生，壁畫描繪民間傳說故事
                                </li>
                                <li>
                                    <i class="fas fa-dragon"></i>
                                    <strong>龍柱：</strong>純手工雕刻，工藝精湛，展現傳統石雕之美
                                </li>
                            </ul>
                            
                            <h3>文物保存</h3>
                            <p>本宮內收藏多件珍貴文物，包括清代匾額、古香爐等，皆經專業修復維護，定期舉辦文物展覽，讓民眾了解傳統工藝之美。</p>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="traffic-section">
                    <h2>交通指引</h2>
                    <div class="traffic-content">
                        <div class="map-container">
                            <?php
                            // 從設定中獲取 Google 地圖嵌入碼
                            try {
                                $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'google_map_embed'");
                                $stmt->execute();
                                $map_embed = $stmt->fetchColumn();
                                echo $map_embed;
                            } catch (PDOException $e) {
                                error_log('Error fetching map embed code: ' . $e->getMessage());
                            }
                            ?>
                        </div>
                        <div class="traffic-info">
                            <div class="info-block">
                                <h3><i class="fas fa-bus"></i> 大眾運輸</h3>
                                <ul>
                                    <li><strong>公車：</strong>
                                        <ul>
                                            <li>255路線：在「某某站」下車，步行約3分鐘</li>
                                            <li>307路線：在「某某站」下車，步行約5分鐘</li>
                                        </ul>
                                    </li>
                                    <li><strong>捷運：</strong>
                                        <ul>
                                            <li>某某線：至「某某站」2號出口，步行約10分鐘</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="info-block">
                                <h3><i class="fas fa-car"></i> 開車前往</h3>
                                <ul>
                                    <li><strong>國道一號：</strong>
                                        <ul>
                                            <li>某某交流道下，往某某方向約10分鐘</li>
                                        </ul>
                                    </li>
                                    <li><strong>停車資訊：</strong>
                                        <ul>
                                            <li>本宮備有免費信徒停車場，約可停放50輛車</li>
                                            <li>假日尖峰時段建議使用大眾運輸</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="info-block">
                                <h3><i class="fas fa-map-marker-alt"></i> 聯絡資訊</h3>
                                <ul>
                                    <li><strong>地址：</strong><?php echo htmlspecialchars(getSetting('site_address')); ?></li>
                                    <li><strong>電話：</strong><?php echo htmlspecialchars(getSetting('site_phone')); ?></li>
                                    <li><strong>Email：</strong><?php echo htmlspecialchars(getSetting('site_email')); ?></li>
                                </ul>
                            </div>
                            
                            <div class="info-block">
                                <h3><i class="fas fa-clock"></i> 開放時間</h3>
                                <ul>
                                    <li><strong>平日：</strong><?php echo htmlspecialchars(getSetting('weekday_hours')); ?></li>
                                    <li><strong>假日：</strong><?php echo htmlspecialchars(getSetting('weekend_hours')); ?></li>
                                    <li><strong>國定假日：</strong><?php echo htmlspecialchars(getSetting('holiday_hours')); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // 為圖片庫添加燈箱效果
        document.querySelectorAll('.gallery-image').forEach(image => {
            image.addEventListener('click', function() {
                // 在這裡實現燈箱效果
            });
        });
    </script>
</body>
</html> 