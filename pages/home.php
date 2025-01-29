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
            $stmt = $db->prepare("SELECT * FROM news WHERE status = 'published' ORDER BY publish_date DESC LIMIT 2");
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
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('Y/m/d', strtotime($news['publish_date'])); ?></span>
                        </div>
                        <p><?php echo mb_substr(strip_tags($news['content']), 0, 100, 'UTF-8') . '...'; ?></p>
                        <a href="news.php?id=<?php echo $news['id']; ?>" class="btn btn-outline">閱讀更多</a>
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
        <div class="services-grid grid-4">
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-pray"></i>
                </div>
                <h3>安太歲</h3>
                <p>為信眾安奉太歲，祈求平安順遂</p>
                <a href="services.php?type=taisui" class="btn btn-outline">了解更多</a>
            </div>
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <h3>點光明燈</h3>
                <p>為信眾點燈祈福，照亮前程</p>
                <a href="services.php?type=light" class="btn btn-outline">了解更多</a>
            </div>
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>祈福法會</h3>
                <p>舉辦各類法會，消災解厄</p>
                <a href="services.php?type=ceremony" class="btn btn-outline">了解更多</a>
            </div>
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>婚姻締結</h3>
                <p>為新人舉行祝福儀式</p>
                <a href="services.php?type=wedding" class="btn btn-outline">了解更多</a>
            </div>
        </div>
    </section>

    <!-- 活動資訊區域 -->
    <section class="events-section">
        <h2 class="section-title">近期活動</h2>
        <div class="events-grid grid-3">
            <div class="event-item">
                <div class="event-image">
                    <img src="assets/images/event1.jpg" alt="浴佛節">
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <span class="day">15</span>
                        <span class="month">5月</span>
                    </div>
                    <h3>浴佛節慶典</h3>
                    <p>一年一度的浴佛節即將到來，歡迎大眾參加浴佛儀式...</p>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 09:00-17:00</span>
                        <span><i class="fas fa-map-marker-alt"></i> 大殿</span>
                    </div>
                    <a href="events.php?id=1" class="btn btn-outline">活動詳情</a>
                </div>
            </div>
            <div class="event-item">
                <div class="event-image">
                    <img src="assets/images/event2.jpg" alt="讀經班">
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <span class="day">20</span>
                        <span class="month">5月</span>
                    </div>
                    <h3>兒童讀經班開課</h3>
                    <p>每週日上午舉辦兒童讀經班，培養孩子良好品德...</p>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 10:00-12:00</span>
                        <span><i class="fas fa-map-marker-alt"></i> 文教室</span>
                    </div>
                    <a href="events.php?id=2" class="btn btn-outline">活動詳情</a>
                </div>
            </div>
            <div class="event-item">
                <div class="event-image">
                    <img src="assets/images/event3.jpg" alt="淨灘活動">
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <span class="day">25</span>
                        <span class="month">5月</span>
                    </div>
                    <h3>環保淨灘活動</h3>
                    <p>響應環保，舉辦淨灘活動，歡迎大眾共同參與...</p>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 08:00-12:00</span>
                        <span><i class="fas fa-map-marker-alt"></i> 海灘</span>
                    </div>
                    <a href="events.php?id=3" class="btn btn-outline">活動詳情</a>
                </div>
            </div>
        </div>
        <div class="text-center">
            <a href="events.php" class="btn btn-primary">查看更多活動</a>
        </div>
    </section>
</div> 