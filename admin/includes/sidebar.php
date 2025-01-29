<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h1>宮廟管理系統</h1>
        <p>後台管理</p>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/index.php') !== false ? 'active' : ''; ?>">
                <a href="/admin/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    儀表板
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/news') !== false ? 'active' : ''; ?>">
                <a href="/admin/news/index.php">
                    <i class="fas fa-newspaper"></i>
                    新聞管理
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/events') !== false ? 'active' : ''; ?>">
                <a href="/admin/events/index.php">
                    <i class="fas fa-calendar-alt"></i>
                    活動管理
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/prayers') !== false ? 'active' : ''; ?>">
                <a href="/admin/prayers/index.php">
                    <i class="fas fa-pray"></i>
                    祈福管理
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/donations') !== false ? 'active' : ''; ?>">
                <a href="/admin/donations/index.php">
                    <i class="fas fa-hand-holding-heart"></i>
                    捐獻管理
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/members') !== false ? 'active' : ''; ?>">
                <a href="/admin/members/index.php">
                    <i class="fas fa-users"></i>
                    信眾管理
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/settings') !== false ? 'active' : ''; ?>">
                <a href="/admin/settings/index.php">
                    <i class="fas fa-cog"></i>
                    系統設置
                </a>
            </li>
            
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/backup') !== false ? 'active' : ''; ?>">
                <a href="/admin/backup/index.php">
                    <i class="fas fa-database"></i>
                    數據備份
                </a>
            </li>
        </ul>
    </nav>
</aside> 