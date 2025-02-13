<?php
// 獲取當前時間
$current_time = date('Y年m月d日 H:i');
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Admin Style -->
    <link rel="stylesheet" href="/admin/assets/css/admin-style.css">
</head>
<body>
    <?php require_once 'sidebar.php'; ?>

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
                        $notification_sql = "SELECT COUNT(*) FROM notifications WHERE is_read = 0";
                        if (isset($_SESSION['admin_id'])) {
                            $notification_sql .= " AND (user_id = ? OR user_id IS NULL)";
                            $stmt = $pdo->prepare($notification_sql);
                            $stmt->execute([$_SESSION['admin_id']]);
                        } else {
                            $stmt = $pdo->prepare($notification_sql);
                            $stmt->execute();
                        }
                        $unread_count = $stmt->fetchColumn();
                        if ($unread_count > 0) {
                            echo "<span class='notification-badge'>$unread_count</span>";
                        }
                    } catch (PDOException $e) {
                        error_log("Error getting notification count: " . $e->getMessage());
                        // 不顯示錯誤給用戶看
                    }
                    ?>
                </a>
            </div>
            
            <div class="header-profile">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle fa-2x"></i>
                </div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理員'); ?></span>
                    <span class="profile-role"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? '管理員'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 