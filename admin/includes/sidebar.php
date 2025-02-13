<?php
// 獲取當前頁面
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// 設定當前頁面變數
$page = '';
switch ($current_dir) {
    case 'admin':
        $page = 'home';
        break;
    case 'news':
        $page = 'news';
        break;
    case 'events':
        $page = 'events';
        break;
    case 'gallery':
        $page = 'gallery';
        break;
    case 'blessings':
        $page = 'blessings';
        break;
    case 'users':
        $page = 'users';
        break;
    case 'settings':
        $page = 'settings';
        break;
}

// 定義導航項目
$nav_items = [
    'home' => [
        'icon' => 'fas fa-home',
        'title' => '後臺首頁',
        'url' => 'index.php'
    ],
    'news' => [
        'icon' => 'fas fa-newspaper',
        'title' => '最新消息管理',
        'url' => 'news/index.php'
    ],
    'events' => [
        'icon' => 'fas fa-calendar-alt',
        'title' => '活動管理',
        'url' => 'events/index.php'
    ],
    'gallery' => [
        'icon' => 'fas fa-images',
        'title' => '相簿管理',
        'url' => 'gallery/gallery.php'
    ],
    'blessings' => [
        'icon' => 'fas fa-pray',
        'title' => '祈福管理',
        'url' => 'blessings/index.php'
    ],
    'users' => [
        'icon' => 'fas fa-users',
        'title' => '用戶管理',
        'url' => 'users/index.php'
    ],
    'newsletter' => [
        'icon' => 'fas fa-envelope',
        'title' => '電子報管理',
        'url' => 'newsletter/index.php'
    ],
    'settings' => [
        'icon' => 'fas fa-cog',
        'title' => '系統設定',
        'url' => 'settings/index.php'
    ]
];

// 判斷當前頁面是否為活動頁面
function isCurrentPage($nav_key) {
    global $current_dir, $current_page;
    
    switch ($nav_key) {
        case 'home':
            return $current_page === 'index.php' && $current_dir === 'admin';
        case 'news':
            return $current_dir === 'news';
        case 'events':
            return $current_dir === 'events';
        case 'gallery':
            return $current_dir === 'gallery';
        case 'blessings':
            return $current_dir === 'blessings';
        case 'users':
            return $current_dir === 'users';
        case 'newsletter':
            return $current_dir === 'newsletter';
        case 'settings':
            return $current_dir === 'settings';
        default:
            return false;
    }
}

// 輔助函數：獲取管理後台URL
function getAdminUrl($path) {
    $current_dir = dirname($_SERVER['PHP_SELF']);
    $admin_root = '/admin/';
    
    // 如果當前已經在子目錄中，需要返回上一層
    if (strpos($current_dir, '/admin/') !== false && $current_dir !== '/admin') {
        return "../$path";
    }
    
    return $path;
}
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
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-external-link-alt"></i>
                    <span>前台首頁</span>
                </a>
            </li>
            <li class="nav-divider"></li>
            <?php foreach ($nav_items as $key => $item): ?>
            <li class="nav-item <?php echo isCurrentPage($key) ? 'active' : ''; ?>">
                <a href="<?php echo getAdminUrl($item['url']); ?>" class="nav-link">
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['title']; ?></span>
                </a>
                <?php if ($key === 'blessings'): ?>
                <ul class="sub-menu">
                    <li class="sub-menu-item">
                        <a href="<?php echo getAdminUrl('blessings/services.php'); ?>" class="nav-link">
                            <i class="fas fa-hand-holding-heart"></i>
                            <span>祈福服務管理</span>
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo getAdminUrl('logout.php'); ?>" class="logout-btn">
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
