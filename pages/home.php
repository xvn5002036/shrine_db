<link rel="stylesheet" href="assets/css/home.css">

<div class="container">
    <!-- 輪播圖區域 -->
    <section class="slider-section">
        <div class="slider">
            <div class="slide">
                <img src="assets/images/slide1.jpg" alt="宮廟外觀">
                <div class="slide-content">
                    <h2>歡迎蒞臨<?php echo SITE_NAME; ?></h2>
                    <p>傳承百年文化，守護眾生平安</p>
                </div>
            </div>
            <div class="slide">
                <img src="assets/images/slide2.jpg" alt="祈福儀式">
                <div class="slide-content">
                    <h2>誠心祈福</h2>
                    <p>專業法師帶領，虔誠祈求平安福祉</p>
                </div>
            </div>
            <div class="slide">
                <img src="assets/images/slide3.jpg" alt="節慶活動">
                <div class="slide-content">
                    <h2>傳統節慶</h2>
                    <p>體驗傳統文化，傳承千年智慧</p>
                </div>
            </div>
            <button class="slider-prev"><i class="fas fa-chevron-left"></i></button>
            <button class="slider-next"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots"></div>
        </div>
    </section>

    <!-- 宮廟介紹區域 -->
    <section class="temple-intro">
        <h2 class="section-title">宮廟簡介</h2>
        <div class="intro-content">
            <div class="intro-text">
                <p>
                    <?php echo SITE_NAME; ?>座落於台北市中心，為北台灣最具代表性的古剎之一。
                    創建於清朝年間，至今已有超過百年歷史，不僅是重要的宗教信仰中心，
                    更是珍貴的文化資產。本宮主祀觀世音菩薩，同時供奉諸多神明，
                    香火鼎盛，信眾眾多。
                </p>
                <p>
                    本宮秉持「慈悲濟世」的理念，除了提供各項祈福服務外，
                    更致力於推廣傳統文化、關懷弱勢、舉辦慈善活動，
                    期望能為社會帶來更多正面的影響力。
                </p>
                <a href="about.php" class="btn btn-primary">了解更多</a>
            </div>
            <div class="intro-image">
                <img src="assets/images/temple.jpg" alt="宮廟外觀" class="temple-image">
            </div>
        </div>
    </section>

    <!-- 最新消息區域 -->
    <section class="news-section">
        <h2 class="section-title">最新消息</h2>
        <div class="news-list">
            <?php
            // 從資料庫獲取最新的兩則新聞
            $stmt = $pdo->prepare("SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 2");
            $stmt->execute();
            $latest_news = $stmt->fetchAll();

            if (!empty($latest_news)):
                foreach ($latest_news as $news):
            ?>
                <div class="news-item">
                    <?php if ($news['image']): ?>
                    <div class="news-image">
                        <img src="<?php echo htmlspecialchars($news['image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                    </div>
                    <?php endif; ?>
                    <div class="news-content">
                        <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                        <div class="news-meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('Y/m/d', strtotime($news['created_at'])); ?></span>
                        </div>
                        <p><?php echo mb_substr(strip_tags($news['content']), 0, 100, 'UTF-8') . '...'; ?></p>
                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn btn-outline">閱讀更多</a>
                    </div>
                </div>
            <?php 
                endforeach;
            else:
            ?>
                <div class="no-news">
                    <p>目前沒有最新消息</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center">
            <a href="news.php" class="btn btn-primary">查看更多消息</a>
        </div>
    </section>

    <!-- 祈福服務區域 -->
    <section class="services-section">
        <h2 class="section-title">祈福服務</h2>
        <div class="services-grid">
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-pray"></i>
                </div>
                <h3>安太歲</h3>
                <p>為信眾安奉太歲，祈求平安順遂</p>
                <div class="service-meta">
                    <span><i class="fas fa-dollar-sign"></i> NT$600起</span>
                </div>
                <a href="blessings/booking.php?type=taisui" class="btn btn-outline">立即預約</a>
            </div>
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-fire-alt"></i>
                </div>
                <h3>光明燈</h3>
                <p>為信眾點燈祈福，照亮前程</p>
                <div class="service-meta">
                    <span><i class="fas fa-dollar-sign"></i> NT$1,200起</span>
                </div>
                <a href="blessings/booking.php?type=light" class="btn btn-outline">立即預約</a>
            </div>
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3>平安祈福</h3>
                <p>為信眾消災解厄，祈求平安</p>
                <div class="service-meta">
                    <span><i class="fas fa-dollar-sign"></i> NT$800起</span>
                </div>
                <a href="blessings/booking.php?type=peace" class="btn btn-outline">立即預約</a>
            </div>
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>姻緣祈福</h3>
                <p>為信眾祈求良緣，圓滿姻緣</p>
                <div class="service-meta">
                    <span><i class="fas fa-dollar-sign"></i> NT$1,000起</span>
                </div>
                <a href="blessings/booking.php?type=marriage" class="btn btn-outline">立即預約</a>
            </div>
        </div>
        <div class="text-center" style="margin-top: 2rem;">
            <a href="blessings/" class="btn btn-primary">查看更多服務</a>
        </div>
    </section>

    <!-- 活動資訊區域 -->
    <section class="events-section">
        <h2 class="section-title">近期活動</h2>
        <div class="events-grid">
            <?php
            // 從資料庫獲取最新的三個活動
            $stmt = $pdo->prepare("
                SELECT *, 
                    DATE_FORMAT(start_date, '%Y-%m-%d') as formatted_date,
                    TIME_FORMAT(start_date, '%H:%i') as formatted_time
                FROM events 
                WHERE status = 1 
                AND start_date >= CURDATE() 
                ORDER BY start_date ASC 
                LIMIT 3
            ");
            $stmt->execute();
            $upcoming_events = $stmt->fetchAll();

            if (!empty($upcoming_events)):
                foreach ($upcoming_events as $event):
                    $event_date = new DateTime($event['start_date']);
            ?>
                <div class="event-item">
                    <div class="event-image">
                        <img src="<?php echo !empty($event['image']) ? htmlspecialchars($event['image']) : 'assets/images/default-event.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                    <div class="event-content">
                        <div class="event-date">
                            <span class="day"><?php echo $event_date->format('d'); ?></span>
                            <span class="month"><?php echo $event_date->format('m'); ?>月</span>
                        </div>
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p><?php echo mb_substr(strip_tags($event['description']), 0, 50, 'UTF-8') . '...'; ?></p>
                        <div class="event-meta">
                            <span><i class="fas fa-clock"></i> <?php echo $event['formatted_time']; ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        <a href="events.php?id=<?php echo $event['id']; ?>" class="btn btn-outline">活動詳情</a>
                    </div>
                </div>
            <?php 
                endforeach;
            else:
            ?>
                <div class="no-events">
                    <p>目前沒有近期活動</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center" style="margin-top: 2rem;">
            <a href="events.php" class="btn btn-primary">查看更多活動</a>
        </div>
    </section>
</div>

