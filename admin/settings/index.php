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

    // 確保 social_links 資料表存在
    $pdo->query("
        CREATE TABLE IF NOT EXISTS `social_links` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `platform` varchar(50) NOT NULL,
            `url` varchar(255) NOT NULL,
            `icon` varchar(50) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `sort_order` int(11) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 確保 contact_info 資料表存在
    $pdo->query("
        CREATE TABLE IF NOT EXISTS `contact_info` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `type` varchar(50) NOT NULL,
            `value` text NOT NULL,
            `icon` varchar(50) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `sort_order` int(11) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 檢查是否需要插入預設的社群連結
    $stmt = $pdo->query("SELECT COUNT(*) FROM social_links");
    if ($stmt->fetchColumn() == 0) {
        $pdo->query("
            INSERT INTO `social_links` (`platform`, `url`, `icon`, `status`, `sort_order`) VALUES
            ('Facebook', '#', 'fab fa-facebook-f', 1, 1),
            ('Instagram', '#', 'fab fa-instagram', 1, 2),
            ('LINE', '#', 'fab fa-line', 1, 3)
        ");
    }

    // 檢查是否需要插入預設的聯絡資訊
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_info");
    if ($stmt->fetchColumn() == 0) {
        $pdo->query("
            INSERT INTO `contact_info` (`type`, `value`, `icon`, `status`, `sort_order`) VALUES
            ('address', '台北市中正區重慶南路一段2號', 'fas fa-map-marker-alt', 1, 1),
            ('phone', '(02) 2345-6789', 'fas fa-phone', 1, 2),
            ('email', 'info@example.com', 'fas fa-envelope', 1, 3),
            ('opening_hours_weekday', '平日：早上 6:00 - 晚上 21:00', 'far fa-clock', 1, 4),
            ('opening_hours_weekend', '假日：早上 5:30 - 晚上 22:00', 'far fa-clock', 1, 5)
        ");
    }

    // 插入預設值（如果不存在）
    $default_settings = [
        ['site_name', '宮廟管理系統', 'site'],
        ['site_email', 'admin@example.com', 'site'],
        ['temple_description', '本宮創立於民國元年，為台灣最具歷史的宮廟之一。我們致力於保存傳統文化，弘揚道德教化，服務社會大眾。', 'site'],
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
            'site_email' => $_POST['site_email'] ?? '',
            'temple_description' => $_POST['temple_description'] ?? ''
        ];

        // 系統設定
        $system_settings = [
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
            'email_notification' => isset($_POST['email_notification']) ? '1' : '0',
            'items_per_page' => $_POST['items_per_page'] ?? '10'
        ];

        // 更新一般設定
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($site_settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        foreach ($system_settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }

        // 處理社群連結
        if (isset($_POST['social_links'])) {
            foreach ($_POST['social_links'] as $id => $link) {
                if (strpos($id, 'new_') === 0) {
                    // 新增連結
                    $stmt = $pdo->prepare("
                        INSERT INTO social_links (platform, url, icon, status, sort_order)
                        VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 10 FROM social_links s))
                    ");
                    $stmt->execute([
                        $link['platform'],
                        $link['url'],
                        $link['icon'],
                        $link['status']
                    ]);
                } else {
                    // 更新既有連結
                    $stmt = $pdo->prepare("
                        UPDATE social_links 
                        SET platform = ?, url = ?, icon = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $link['platform'],
                        $link['url'],
                        $link['icon'],
                        $link['status'],
                        $id
                    ]);
                }
            }
        }

        // 處理刪除的連結
        if (isset($_POST['delete_social_links'])) {
            $stmt = $pdo->prepare("DELETE FROM social_links WHERE id = ?");
            foreach ($_POST['delete_social_links'] as $id) {
                $stmt->execute([$id]);
            }
        }

        // 處理聯絡資訊
        if (isset($_POST['contact_info'])) {
            foreach ($_POST['contact_info'] as $id => $info) {
                if (strpos($id, 'new_') === 0) {
                    // 新增聯絡資訊
                    $stmt = $pdo->prepare("
                        INSERT INTO contact_info (type, value, icon, status, sort_order)
                        VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 10 FROM contact_info c))
                    ");
                    $stmt->execute([
                        $info['type'],
                        $info['value'],
                        $info['icon'],
                        $info['status']
                    ]);
                } else {
                    // 更新既有聯絡資訊
                    $stmt = $pdo->prepare("
                        UPDATE contact_info 
                        SET type = ?, value = ?, icon = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $info['type'],
                        $info['value'],
                        $info['icon'],
                        $info['status'],
                        $id
                    ]);
                }
            }
        }

        // 處理刪除的聯絡資訊
        if (isset($_POST['delete_contact_info'])) {
            $stmt = $pdo->prepare("DELETE FROM contact_info WHERE id = ?");
            foreach ($_POST['delete_contact_info'] as $id) {
                $stmt->execute([$id]);
            }
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
                        <label for="temple_description" class="form-label">宮廟簡介</label>
                        <textarea class="form-control" id="temple_description" name="temple_description" rows="4"><?php echo htmlspecialchars($settings['temple_description'] ?? ''); ?></textarea>
                        <div class="form-text">此描述將顯示在網站頁尾的「關於本宮」區塊</div>
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

            <!-- 社群連結設定 -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">社群連結設定</h5>
                    <button type="button" class="btn btn-success btn-sm" onclick="addSocialLink()">
                        <i class="fas fa-plus"></i> 新增連結
                    </button>
                </div>
                <div class="card-body">
                    <div id="socialLinksContainer">
                        <?php
                        try {
                            // 獲取社群連結
                            $stmt = $pdo->query("SELECT * FROM social_links ORDER BY sort_order ASC");
                            $socialLinks = $stmt->fetchAll();
                            foreach ($socialLinks as $link):
                            ?>
                            <div class="social-link-item mb-3 border-bottom pb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-2">
                                            <label class="form-label">平台名稱</label>
                                            <input type="text" class="form-control" name="social_links[<?php echo $link['id']; ?>][platform]" 
                                                   value="<?php echo htmlspecialchars($link['platform']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label">連結網址</label>
                                            <input type="url" class="form-control" name="social_links[<?php echo $link['id']; ?>][url]" 
                                                   value="<?php echo htmlspecialchars($link['url']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-2">
                                            <label class="form-label">圖示代碼</label>
                                            <input type="text" class="form-control" name="social_links[<?php echo $link['id']; ?>][icon]" 
                                                   value="<?php echo htmlspecialchars($link['icon']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-2">
                                            <label class="form-label">狀態</label>
                                            <select class="form-select" name="social_links[<?php echo $link['id']; ?>][status]">
                                                <option value="1" <?php echo $link['status'] ? 'selected' : ''; ?>>啟用</option>
                                                <option value="0" <?php echo !$link['status'] ? 'selected' : ''; ?>>停用</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteSocialLink(this, <?php echo $link['id']; ?>)">
                                            <i class="fas fa-trash"></i> 刪除
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach;
                        } catch (PDOException $e) {
                            error_log("Error fetching social links: " . $e->getMessage());
                            echo '<div class="alert alert-warning">載入社群連結時發生錯誤，請重新整理頁面試試。</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- 聯絡資訊設定 -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">聯絡資訊設定</h5>
                    <button type="button" class="btn btn-success btn-sm" onclick="addContactInfo()">
                        <i class="fas fa-plus"></i> 新增聯絡資訊
                    </button>
                </div>
                <div class="card-body">
                    <div id="contactInfoContainer">
                        <?php
                        try {
                            // 獲取聯絡資訊
                            $stmt = $pdo->query("SELECT * FROM contact_info ORDER BY sort_order ASC");
                            $contactInfo = $stmt->fetchAll();
                            foreach ($contactInfo as $info):
                            ?>
                            <div class="contact-info-item mb-3 border-bottom pb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-2">
                                            <label class="form-label">類型</label>
                                            <input type="text" class="form-control" name="contact_info[<?php echo $info['id']; ?>][type]" 
                                                   value="<?php echo htmlspecialchars($info['type']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label">內容</label>
                                            <input type="text" class="form-control" name="contact_info[<?php echo $info['id']; ?>][value]" 
                                                   value="<?php echo htmlspecialchars($info['value']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-2">
                                            <label class="form-label">圖示代碼</label>
                                            <input type="text" class="form-control" name="contact_info[<?php echo $info['id']; ?>][icon]" 
                                                   value="<?php echo htmlspecialchars($info['icon']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-2">
                                            <label class="form-label">狀態</label>
                                            <select class="form-select" name="contact_info[<?php echo $info['id']; ?>][status]">
                                                <option value="1" <?php echo $info['status'] ? 'selected' : ''; ?>>啟用</option>
                                                <option value="0" <?php echo !$info['status'] ? 'selected' : ''; ?>>停用</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteContactInfo(this, <?php echo $info['id']; ?>)">
                                            <i class="fas fa-trash"></i> 刪除
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach;
                        } catch (PDOException $e) {
                            error_log("Error fetching contact info: " . $e->getMessage());
                            echo '<div class="alert alert-warning">載入聯絡資訊時發生錯誤，請重新整理頁面試試。</div>';
                        }
                        ?>
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

<script>
function addSocialLink() {
    const container = document.getElementById('socialLinksContainer');
    const newId = 'new_' + Date.now();
    const template = `
        <div class="social-link-item mb-3 border-bottom pb-3">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-2">
                        <label class="form-label">平台名稱</label>
                        <input type="text" class="form-control" name="social_links[${newId}][platform]" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-2">
                        <label class="form-label">連結網址</label>
                        <input type="url" class="form-control" name="social_links[${newId}][url]" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <label class="form-label">圖示代碼</label>
                        <input type="text" class="form-control" name="social_links[${newId}][icon]" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label">狀態</label>
                        <select class="form-select" name="social_links[${newId}][status]">
                            <option value="1">啟用</option>
                            <option value="0">停用</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteSocialLink(this)">
                        <i class="fas fa-trash"></i> 刪除
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
}

function deleteSocialLink(button, id) {
    if (confirm('確定要刪除此社群連結嗎？')) {
        const item = button.closest('.social-link-item');
        item.remove();
        
        if (id) {
            // 如果是既有的連結，加入刪除標記
            const form = document.querySelector('form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_social_links[]';
            input.value = id;
            form.appendChild(input);
        }
    }
}

function addContactInfo() {
    const container = document.getElementById('contactInfoContainer');
    const newId = 'new_' + Date.now();
    const template = `
        <div class="contact-info-item mb-3 border-bottom pb-3">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-2">
                        <label class="form-label">類型</label>
                        <input type="text" class="form-control" name="contact_info[${newId}][type]" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-2">
                        <label class="form-label">內容</label>
                        <input type="text" class="form-control" name="contact_info[${newId}][value]" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <label class="form-label">圖示代碼</label>
                        <input type="text" class="form-control" name="contact_info[${newId}][icon]" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-2">
                        <label class="form-label">狀態</label>
                        <select class="form-select" name="contact_info[${newId}][status]">
                            <option value="1">啟用</option>
                            <option value="0">停用</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteContactInfo(this)">
                        <i class="fas fa-trash"></i> 刪除
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
}

function deleteContactInfo(button, id) {
    if (confirm('確定要刪除此聯絡資訊嗎？')) {
        const item = button.closest('.contact-info-item');
        item.remove();
        
        if (id) {
            // 如果是既有的連結，加入刪除標記
            const form = document.querySelector('form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_contact_info[]';
            input.value = id;
            form.appendChild(input);
        }
    }
}
</script>

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
