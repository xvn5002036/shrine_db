<?php
$current_year = date('Y');
?>
<footer class="site-footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-sections">
                <!-- 宮廟資訊 -->
                <div class="footer-section">
                    <h3>關於本宮</h3>
                    <div class="footer-logo">
                        <img src="image/logo.png" alt="<?php echo SITE_NAME; ?>">
                    </div>
                    <p class="temple-description">
                        <?php echo SITE_NAME; ?> 致力於弘揚傳統文化，提供信眾優質的宗教服務，
                        並以現代化的管理方式，創造舒適的參拜環境。
                    </p>
                </div>

                <!-- 聯絡資訊 -->
                <div class="footer-section">
                    <h3>聯絡資訊</h3>
                    <ul class="contact-list">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>台北市中正區重慶南路一段2號</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>(02) 2345-6789</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>info@example.com</span>
                        </li>
                        <li>
                            <i class="far fa-clock"></i>
                            <span>平日：早上 6:00 - 晚上 21:00</span>
                        </li>
                        <li>
                            <i class="far fa-clock"></i>
                            <span>假日：早上 5:30 - 晚上 22:00</span>
                        </li>
                    </ul>
                </div>

                <!-- 快速連結 -->
                <div class="footer-section">
                    <h3>快速連結</h3>
                    <ul class="quick-links">
                        <li><a href="about.php">關於本宮</a></li>
                        <li><a href="news.php">最新消息</a></li>
                        <li><a href="events.php">活動資訊</a></li>
                        <li><a href="services.php">祈福服務</a></li>
                        <li><a href="gallery.php">活動花絮</a></li>
                        <li><a href="contact.php">聯絡我們</a></li>
                    </ul>
                </div>

                <!-- 社群連結 -->
                <div class="footer-section">
                    <h3>社群連結</h3>
                    <div class="social-links">
                        <a href="#" target="_blank" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" target="_blank" title="LINE">
                            <i class="fab fa-line"></i>
                        </a>
                    </div>
                    <div class="newsletter">
                        <h4>訂閱電子報</h4>
                        <form action="subscribe.php" method="post" class="newsletter-form">
                            <input type="email" name="email" placeholder="請輸入您的 Email" required>
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 版權資訊 -->
    <div class="footer-bottom">
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo $current_year; ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
            <div class="footer-links">
                <a href="privacy.php">隱私權政策</a>
                <a href="terms.php">使用條款</a>
                <a href="sitemap.php">網站地圖</a>
            </div>
        </div>
    </div>

    <!-- 回到頂部按鈕 -->
    <button id="back-to-top" class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>
</footer>

<!-- 引入 Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html> 
