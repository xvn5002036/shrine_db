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

<style>
.admin-sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background-color: #2c3e50;
    color: #fff;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar-header {
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.site-title {
    font-size: 1.2rem;
    margin: 0;
    color: var(--primary-color);
}

.sidebar-toggle {
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.5rem;
    display: none;
}

.admin-profile {
    padding: 1.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.profile-image {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.profile-info {
    flex: 1;
}

.admin-name {
    margin: 0;
    font-weight: 500;
    font-size: 0.9rem;
}

.admin-role {
    font-size: 0.8rem;
    opacity: 0.7;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 0.2rem 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-link i {
    width: 20px;
    margin-right: 10px;
    font-size: 1.1rem;
}

.nav-item:hover .nav-link,
.nav-item.active .nav-link {
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--primary-color);
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #fff;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--primary-color);
}

.nav-divider {
    height: 1px;
    background-color: rgba(255, 255, 255, 0.1);
    margin: 10px 0;
}

.nav-item a[target="_blank"] {
    color: #4a90e2;
}

.nav-item a[target="_blank"]:hover {
    background-color: rgba(74, 144, 226, 0.1);
}

@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }

    .admin-sidebar.show {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: block;
    }
}
</style> 
