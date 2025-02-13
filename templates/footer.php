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
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'temple_description'");
                            $temple_description = $stmt->fetchColumn();
                            echo htmlspecialchars($temple_description ?: SITE_NAME . ' 致力於弘揚傳統文化，提供信眾優質的宗教服務，並以現代化的管理方式，創造舒適的參拜環境。');
                        } catch (PDOException $e) {
                            error_log("Error fetching temple description: " . $e->getMessage());
                            echo SITE_NAME . ' 致力於弘揚傳統文化，提供信眾優質的宗教服務，並以現代化的管理方式，創造舒適的參拜環境。';
                        }
                        ?>
                    </p>
                </div>

                <!-- 聯絡資訊 -->
                <div class="footer-section">
                    <h3>聯絡資訊</h3>
                    <div class="contact-list">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM contact_info WHERE status = 1 ORDER BY sort_order ASC");
                            while ($info = $stmt->fetch()) {
                                echo '<div class="contact-item">';
                                echo '<i class="' . htmlspecialchars($info['icon']) . '"></i>';
                                echo '<span>' . htmlspecialchars($info['value']) . '</span>';
                                echo '</div>';
                            }
                        } catch (PDOException $e) {
                            error_log("Error fetching contact info: " . $e->getMessage());
                            // 如果發生錯誤，顯示預設的聯絡資訊
                            ?>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>台北市中正區重慶南路一段2號</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>(02) 2345-6789</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>info@example.com</span>
                            </div>
                            <div class="contact-item">
                                <i class="far fa-clock"></i>
                                <span>平日：早上 6:00 - 晚上 21:00</span>
                            </div>
                            <div class="contact-item">
                                <i class="far fa-clock"></i>
                                <span>假日：早上 5:30 - 晚上 22:00</span>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
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
                        <?php
                        try {
                            // 獲取啟用的社群連結
                            $stmt = $pdo->query("SELECT * FROM social_links WHERE status = 1 ORDER BY sort_order ASC");
                            while ($link = $stmt->fetch()):
                            ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" title="<?php echo htmlspecialchars($link['platform']); ?>">
                                <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                            </a>
                            <?php endwhile;
                        } catch (PDOException $e) {
                            error_log("Error fetching social links: " . $e->getMessage());
                            // 如果發生錯誤，顯示預設的社群連結
                            ?>
                            <a href="#" target="_blank" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" target="_blank" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" target="_blank" title="LINE">
                                <i class="fab fa-line"></i>
                            </a>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="newsletter">
                        <h4>訂閱電子報</h4>
                        <form id="newsletter-form" class="newsletter-form">
                            <input type="email" name="email" placeholder="請輸入您的 Email" required>
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
                        <div id="newsletter-message" class="newsletter-message"></div>
                        <div class="newsletter-unsubscribe">
                            <a href="unsubscribe.php" class="unsubscribe-link">
                                <i class="fas fa-times-circle"></i> 取消訂閱電子報
                            </a>
                        </div>
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

<style>
.newsletter-unsubscribe {
    margin-top: 10px;
    text-align: center;
}

.unsubscribe-link {
    color: #666;
    font-size: 0.9em;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.unsubscribe-link:hover {
    color: #dc3545;
}

.newsletter-message {
    margin-top: 10px;
    font-size: 0.9em;
}

.newsletter-message.success {
    color: #28a745;
}

.newsletter-message.error {
    color: #dc3545;
}
</style>

<script>
document.getElementById('newsletter-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const messageDiv = document.getElementById('newsletter-message');
    const email = form.email.value;
    
    // 清除之前的訊息
    messageDiv.textContent = '';
    messageDiv.className = 'newsletter-message';
    
    // 發送 AJAX 請求
    fetch('subscribe.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        messageDiv.textContent = data.message;
        messageDiv.className = 'newsletter-message ' + (data.success ? 'success' : 'error');
        
        if (data.success) {
            form.reset();
        }
    })
    .catch(error => {
        messageDiv.textContent = '訂閱處理時發生錯誤，請稍後再試';
        messageDiv.className = 'newsletter-message error';
        console.error('Error:', error);
    });
});
</script>
</body>
</html> 
