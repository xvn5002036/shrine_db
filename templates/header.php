<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <div class="container">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> (02) 2345-6789</span>
                    <span><i class="fas fa-envelope"></i> 週一至週日 06:00-21:00</span>
                </div>
                <div class="admin-link">
                    <a href="admin/login.php"><i class="fas fa-user-lock"></i> 後台管理</a>
                </div>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-line"></i></a>
                </div>
            </div>
        </div>
        <div class="header-main">
            <div class="container">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                    </a>
                </div>
                <div class="header-search">
                    <form action="search.php" method="get">
                        <input type="text" name="q" placeholder="搜尋...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="header-actions">
                    <a href="prayer.php" class="action-btn prayer-btn">
                        <i class="fas fa-pray"></i> 線上祈福
                    </a>
                    <a href="contact.php" class="action-btn contact-btn">
                        <i class="fas fa-envelope"></i> 聯絡我們
                    </a>
                </div>
            </div>
        </div>
        <nav class="main-nav">
            <div class="container">
                <button class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <ul class="nav-menu">
                    <li><a href="index.php">首頁</a></li>
                    <li><a href="about.php">關於我們</a></li>
                    <li><a href="news.php">最新消息</a></li>
                    <li><a href="events.php">活動資訊</a></li>
                    <li><a href="services.php">祈福服務</a></li>
                    <li><a href="gallery.php">活動花絮</a></li>
                    <li><a href="contact.php">聯絡我們</a></li>
                </ul>
            </div>
        </nav>
    </header>
    <main> 