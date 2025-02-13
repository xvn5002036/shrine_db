<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = '網站地圖';
include 'templates/header.php';
?>

<div class="content-wrapper">
    <div class="container">
        <div class="content-header">
            <h1>網站地圖</h1>
            <div class="breadcrumb">
                <a href="index.php">首頁</a>
                <i class="fas fa-angle-right"></i>
                <span>網站地圖</span>
            </div>
        </div>

        <div class="content-body">
            <div class="sitemap-content">
                <!-- 主要頁面 -->
                <section class="sitemap-section">
                    <h2><i class="fas fa-home"></i> 主要頁面</h2>
                    <ul class="sitemap-list">
                        <li><a href="index.php">首頁</a></li>
                        <li><a href="about.php">關於本宮</a></li>
                        <li><a href="news.php">最新消息</a></li>
                        <li><a href="events.php">活動資訊</a></li>
                        <li><a href="gallery.php">活動花絮</a></li>
                        <li><a href="contact.php">聯絡我們</a></li>
                    </ul>
                </section>

                <!-- 祈福服務 -->
                <section class="sitemap-section">
                    <h2><i class="fas fa-pray"></i> 祈福服務</h2>
                    <ul class="sitemap-list">
                        <li><a href="services.php">服務項目</a></li>
                        <li><a href="blessings.php">祈福服務</a></li>
                        <li><a href="booking.php">線上預約</a></li>
                    </ul>
                </section>

                <!-- 會員專區 -->
                <section class="sitemap-section">
                    <h2><i class="fas fa-user"></i> 會員專區</h2>
                    <ul class="sitemap-list">
                        <li><a href="login.php">會員登入</a></li>
                        <li><a href="register.php">註冊會員</a></li>
                        <li><a href="member/profile.php">會員資料</a></li>
                        <li><a href="member/bookings.php">預約紀錄</a></li>
                        <li><a href="member/events.php">活動報名紀錄</a></li>
                    </ul>
                </section>

                <!-- 其他資訊 -->
                <section class="sitemap-section">
                    <h2><i class="fas fa-info-circle"></i> 其他資訊</h2>
                    <ul class="sitemap-list">
                        <li><a href="privacy.php">隱私權政策</a></li>
                        <li><a href="terms.php">使用條款</a></li>
                        <li><a href="faq.php">常見問題</a></li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.sitemap-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.sitemap-section {
    margin-bottom: 30px;
}

.sitemap-section:last-child {
    margin-bottom: 0;
}

.sitemap-section h2 {
    color: #333;
    font-size: 1.5em;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sitemap-section h2 i {
    color: #c19b77;
}

.sitemap-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.sitemap-list li {
    padding: 5px 0;
}

.sitemap-list a {
    color: #666;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 8px 15px;
    background: #f8f9fa;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.sitemap-list a:hover {
    color: #c19b77;
    background: #f0f0f0;
    transform: translateX(5px);
}

@media (max-width: 768px) {
    .sitemap-content {
        padding: 20px;
    }

    .sitemap-section h2 {
        font-size: 1.3em;
    }

    .sitemap-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'templates/footer.php'; ?> 