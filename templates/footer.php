    </main>
    <footer class="site-footer">
        <div class="footer-top">
            <div class="container">
                <div class="footer-sections">
                    <div class="footer-section">
                        <div class="footer-logo">
                            <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                        </div>
                        <p class="temple-description">
                            <?php echo SITE_NAME; ?> 致力於弘揚傳統文化，提供信眾優質的宗教服務，
                            並以現代化的管理方式，創造舒適的參拜環境。
                        </p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-line"></i></a>
                        </div>
                    </div>
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
                                <i class="fas fa-clock"></i>
                                <span>週一至週日 06:00-21:00</span>
                            </li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>快速連結</h3>
                        <ul class="quick-links">
                            <li><a href="about.php">關於我們</a></li>
                            <li><a href="news.php">最新消息</a></li>
                            <li><a href="events.php">活動資訊</a></li>
                            <li><a href="services.php">祈福服務</a></li>
                            <li><a href="gallery.php">相簿集錦</a></li>
                            <li><a href="contact.php">聯絡我們</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>訂閱電子報</h3>
                        <p>訂閱我們的電子報，獲取最新活動和消息通知</p>
                        <div class="newsletter">
                            <form action="subscribe.php" method="post" class="newsletter-form">
                                <input type="email" name="email" placeholder="請輸入您的 Email" required>
                                <button type="submit">訂閱</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
                <div class="footer-links">
                    <a href="privacy.php">隱私權政策</a>
                    <a href="terms.php">使用條款</a>
                    <a href="sitemap.php">網站地圖</a>
                </div>
            </div>
        </div>
    </footer>

    <button class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="assets/js/menu.js"></script>
    <script src="assets/js/slider.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 