<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查是否登入
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 檢查是否有管理員權限
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// 確保 settings 資料表存在
try {
    $pdo->query("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(50) NOT NULL,
            `setting_value` text,
            `setting_group` varchar(20) NOT NULL DEFAULT 'general',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 插入預設值（如果不存在）
    $default_settings = [
        ['site_name', '宮廟管理系統', 'site'],
        ['site_description', '專業的宮廟管理系統', 'site'],
        ['site_email', 'admin@example.com', 'site'],
        ['site_phone', '02-1234-5678', 'site'],
        ['site_address', '台北市中正區範例路123號', 'site'],
        ['business_hours', '週一至週日 09:00-17:00', 'site'],
        ['maintenance_mode', '0', 'system'],
        ['registration_enabled', '1', 'system'],
        ['email_notification', '1', 'system'],
        ['items_per_page', '10', 'system']
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO settings (setting_key, setting_value, setting_group) 
        VALUES (?, ?, ?)
    ");

    foreach ($default_settings as $setting) {
        $stmt->execute($setting);
    }

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    die('資料庫錯誤，請聯繫管理員');
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 網站基本設定
        $site_settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'site_email' => $_POST['site_email'] ?? '',
            'site_phone' => $_POST['site_phone'] ?? '',
            'site_address' => $_POST['site_address'] ?? '',
            'business_hours' => $_POST['business_hours'] ?? ''
        ];
        
        // 系統設定
        $system_settings = [
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
            'email_notification' => isset($_POST['email_notification']) ? '1' : '0',
            'items_per_page' => $_POST['items_per_page'] ?? '10'
        ];
        
        $stmt = $pdo->prepare("
            UPDATE settings 
            SET setting_value = ? 
            WHERE setting_key = ?
        ");
        
        foreach ($site_settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        
        foreach ($system_settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = '設定已成功更新';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = '更新設定時發生錯誤：' . $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 獲取當前設定
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log('Error fetching settings: ' . $e->getMessage());
    $settings = [];
}

$page_title = '系統設定';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>系統設定</h1>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" class="settings-form">
            <!-- 網站基本設定 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">網站基本設定</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">網站名稱</label>
                                <input type="text" class="form-control" id="site_name" name="site_name"
                                       value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_email" class="form-label">網站信箱</label>
                                <input type="email" class="form-control" id="site_email" name="site_email"
                                       value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">網站描述</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_phone" class="form-label">聯絡電話</label>
                                <input type="text" class="form-control" id="site_phone" name="site_phone"
                                       value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="business_hours" class="form-label">營業時間</label>
                                <input type="text" class="form-control" id="business_hours" name="business_hours"
                                       value="<?php echo htmlspecialchars($settings['business_hours'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_address" class="form-label">地址</label>
                        <input type="text" class="form-control" id="site_address" name="site_address"
                               value="<?php echo htmlspecialchars($settings['site_address'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- 系統設定 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">系統設定</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                           name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_mode">維護模式</label>
                                </div>
                                <small class="text-muted">啟用後，只有管理員可以訪問網站</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="registration_enabled" 
                                           name="registration_enabled" <?php echo ($settings['registration_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="registration_enabled">開放註冊</label>
                                </div>
                                <small class="text-muted">允許新用戶註冊</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="email_notification" 
                                           name="email_notification" <?php echo ($settings['email_notification'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notification">電子郵件通知</label>
                                </div>
                                <small class="text-muted">啟用系統電子郵件通知功能</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="items_per_page" class="form-label">每頁顯示數量</label>
                                <input type="number" class="form-control" id="items_per_page" name="items_per_page"
                                       value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>"
                                       min="5" max="100">
                                <small class="text-muted">設定列表頁面每頁顯示的項目數量</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 儲存設定
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-form .card {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.settings-form .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.settings-form .form-switch {
    padding-left: 2.5em;
}

.settings-form .form-check-input {
    width: 3em;
}

.settings-form .text-muted {
    font-size: 0.875em;
}

@media (max-width: 768px) {
    .settings-form .card {
        margin-bottom: 1rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 
