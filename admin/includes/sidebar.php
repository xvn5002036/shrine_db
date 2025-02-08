<?php
// 獲取當前頁面
$current_page = basename($_SERVER['PHP_SELF']);
$page = $_GET['page'] ?? 'home';

// 定義導航項目
$nav_items = [
    'home' => [
        'icon' => 'fas fa-home',
        'title' => '儀表板',
        'url' => '/admin/index.php'
    ],
    'news' => [
        'icon' => 'fas fa-newspaper',
        'title' => '最新消息管理',
        'url' => '/admin/news/index.php'
    ],
    'events' => [
        'icon' => 'fas fa-calendar-alt',
        'title' => '活動管理',
        'url' => '/admin/events/index.php'
    ],
    'blessings' => [
        'icon' => 'fas fa-pray',
        'title' => '祈福管理',
        'url' => '/admin/blessings/index.php'
    ],
    'users' => [
        'icon' => 'fas fa-users',
        'title' => '用戶管理',
        'url' => '/admin/users/index.php'
    ],
    'settings' => [
        'icon' => 'fas fa-cog',
        'title' => '系統設定',
        'url' => '/admin/settings/index.php'
    ]
];
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h1 class="site-title"><?php echo SITE_NAME; ?></h1>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="admin-profile">
        <div class="profile-image">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="profile-info">
            <p class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理員'); ?></p>
            <span class="admin-role"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? '管理員'); ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="/" class="nav-link" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>前台首頁</span>
                </a>
                <a href="services.php" class="nav-link">
       <i class="fas fa-pray"></i>
       <span>祈福服務管理</span>
   </a>
            </li>
            <li class="nav-divider"></li>
            <?php foreach ($nav_items as $key => $item): ?>
            <li class="nav-item <?php echo $page === $key ? 'active' : ''; ?>">
                <a href="<?php echo $item['url']; ?>" class="nav-link">
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['title']; ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="/admin/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>登出</span>
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });
});
</script> 
