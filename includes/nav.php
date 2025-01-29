<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="main-nav">
    <div class="container">
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
            <span>選單</span>
        </button>
        
        <ul class="nav-menu">
            <li class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                <a href="index.php">首頁</a>
            </li>
            <li class="<?php echo $current_page === 'about' ? 'active' : ''; ?> has-dropdown">
                <a href="about.php">關於本宮</a>
                <ul class="dropdown-menu">
                    <li><a href="about.php?section=history">宮廟歷史</a></li>
                    <li><a href="about.php?section=architecture">建築特色</a></li>
                    <li><a href="about.php?section=traffic">交通指引</a></li>
                </ul>
            </li>
            <li class="<?php echo $current_page === 'news' ? 'active' : ''; ?>">
                <a href="news.php">最新消息</a>
            </li>
            <li class="<?php echo $current_page === 'events' ? 'active' : ''; ?>">
                <a href="events.php">活動資訊</a>
            </li>
            <li class="<?php echo $current_page === 'services' ? 'active' : ''; ?> has-dropdown">
                <a href="services.php">祈福服務</a>
                <ul class="dropdown-menu">
                    <li><a href="services.php?type=prayer">祈福點燈</a></li>
                    <li><a href="services.php?type=fortune">求籤問卜</a></li>
                    <li><a href="services.php?type=wedding">神前婚禮</a></li>
                    <li><a href="services.php?type=blessing">安太歲</a></li>
                </ul>
            </li>
            <li class="<?php echo $current_page === 'gallery' ? 'active' : ''; ?>">
                <a href="gallery.php">活動花絮</a>
            </li>
            <li class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">
                <a href="contact.php">聯絡我們</a>
            </li>
        </ul>
    </div>
</nav>

<!-- 手機版選單遮罩 -->
<div class="mobile-menu-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 處理下拉選單
    const dropdowns = document.querySelectorAll('.has-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('mouseenter', function() {
            this.querySelector('.dropdown-menu').style.display = 'block';
        });
        dropdown.addEventListener('mouseleave', function() {
            this.querySelector('.dropdown-menu').style.display = 'none';
        });
    });
});
</script> 




