<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="site-header">
    <!-- 頂部資訊列 -->
    <div class="header-top">
        <div class="container">
            <div class="contact-info">
                <span><i class="fas fa-phone"></i> (02) 2345-6789</span>
                <span><i class="far fa-clock"></i> 營業時間：早上 6:00 - 晚上 21:00</span>
            </div>
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
        </div>
    </div>

    <!-- 主要標題區 -->
    <div class="header-main">
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                </a>
            </div>
            <div class="header-search">
                <form action="search.php" method="get">
                    <input type="text" name="q" placeholder="搜尋..." required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="header-actions">
                <a href="services.php" class="action-btn prayer-btn">
                    <i class="fas fa-pray"></i>
                    <span>線上祈福</span>
                </a>
                <a href="contact.php" class="action-btn contact-btn">
                    <i class="fas fa-envelope"></i>
                    <span>聯絡我們</span>
                </a>
            </div>
        </div>
    </div>
</header> 
