<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo isset($current_page) ? '../' : ''; ?>assets/css/style.css">
</head>
<body>
    <?php
    // 確保資料庫連線
    if (!isset($pdo)) {
        require_once(isset($current_page) ? '../' : '') . 'includes/db_connect.php';
    }

    // 獲取設定值
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log('Error fetching settings: ' . $e->getMessage());
        $settings = [];
    }
    ?>
    <header class="site-header">
        <div class="header-top">
            <div class="container">
                <div class="contact-info">
                    <?php if (!empty($settings['contact_phone'])): ?>
                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['contact_phone']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($settings['contact_hours'])): ?>
                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($settings['contact_hours']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="admin-link">
                    <a href="<?php echo isset($current_page) ? '../' : ''; ?>admin/login.php">
                        <i class="fas fa-user-lock"></i> 後台管理
                    </a>
                </div>
                <div class="social-links">
                    <?php if (!empty($settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank">
                            <i class="fab fa-facebook"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_instagram']); ?>" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_line'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_line']); ?>" target="_blank">
                            <i class="fab fa-line"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_youtube']); ?>" target="_blank">
                            <i class="fab fa-youtube"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="header-main">
            <div class="container">
                <div class="logo">
                    <a href="<?php echo isset($current_page) ? '../' : ''; ?>index.php">
                        <img src="<?php echo isset($current_page) ? '../' : ''; ?>image/logo.png" 
                             alt="<?php echo SITE_NAME; ?>">
                    </a>
                </div>
                <div class="header-search">
                    <form action="<?php echo isset($current_page) ? '../' : ''; ?>search.php" method="get">
                        <input type="text" name="q" placeholder="搜尋...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="header-actions">
                    <a href="<?php echo isset($current_page) ? '../' : ''; ?>prayer.php" class="action-btn prayer-btn">
                        <i class="fas fa-pray"></i> 線上祈福
                    </a>
                    <a href="<?php echo isset($current_page) ? '../' : ''; ?>contact.php" class="action-btn contact-btn">
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
                    <li><a href="<?php echo isset($current_page) ? '../' : ''; ?>index.php">首頁</a></li>
                    <li><a href="<?php echo isset($current_page) ? '../' : ''; ?>about.php">關於我們</a></li>
                    <li><a href="<?php echo isset($current_page) ? '../' : ''; ?>news.php">最新消息</a></li>
                    <li><a href="<?php echo isset($current_page) ? '../' : ''; ?>events.php">活動資訊</a></li>
                    <li<?php echo isset($current_page) && $current_page === 'blessings' ? ' class="active"' : ''; ?>>
                        <a href="<?php echo isset($current_page) ? '../' : ''; ?>blessings.php">祈福服務</a>
                    </li>
                    <li><a href="<?php echo isset($current_page) ? '../' : ''; ?>gallery.php">活動花絮</a></li>
                    <li><a href="<?php echo isset($current_page) ? '../' : ''; ?>contact.php">聯絡我們</a></li>
                </ul>
            </div>
        </nav>
    </header>
    <main> 