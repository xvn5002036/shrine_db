<?php
// 獲取當前時間
$current_time = date('Y年m月d日 H:i');
?>

<header class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="current-page">
            <h2><?php echo $nav_items[$page]['title'] ?? '儀表板'; ?></h2>
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-time">
            <i class="far fa-clock"></i>
            <span><?php echo $current_time; ?></span>
        </div>
        
        <div class="header-notifications">
            <a href="#" class="notification-icon">
                <i class="far fa-bell"></i>
                <?php
                // 獲取未讀通知數量
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
                    $unread_count = $stmt->fetchColumn();
                    if ($unread_count > 0) {
                        echo "<span class='notification-badge'>$unread_count</span>";
                    }
                } catch (PDOException $e) {
                    error_log("Error getting notification count: " . $e->getMessage());
                }
                ?>
            </a>
        </div>
        
        <div class="header-profile">
            <img src="../assets/images/default-avatar.png" alt="管理員頭像" class="profile-avatar">
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理員'); ?></span>
                <span class="profile-role"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? '管理員'); ?></span>
            </div>
        </div>
    </div>
</header>

<style>
.admin-header {
    height: 60px;
    background: white;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    position: fixed;
    top: 0;
    right: 0;
    left: 250px;
    z-index: 999;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sidebar-toggle {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #666;
    cursor: pointer;
    padding: 0.5rem;
    display: none;
}

.current-page h2 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--text-color);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.header-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.notification-icon {
    position: relative;
    color: #666;
    font-size: 1.2rem;
    text-decoration: none;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--error-color);
    color: white;
    font-size: 0.7rem;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 15px;
    text-align: center;
}

.header-profile {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.header-profile:hover {
    background-color: #f5f5f5;
}

.profile-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-info {
    display: flex;
    flex-direction: column;
}

.profile-name {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-color);
}

.profile-role {
    font-size: 0.8rem;
    color: #666;
}

@media (max-width: 768px) {
    .admin-header {
        left: 0;
    }
    
    .sidebar-toggle {
        display: block;
    }
    
    .header-time {
        display: none;
    }
    
    .profile-info {
        display: none;
    }
}
</style> 