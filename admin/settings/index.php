<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 檢查管理員權限
checkAdminRole();

// 初始化設定陣列
$settings = [];
$basic_settings = [];
$contact_settings = [];
$social_settings = [];
$seo_settings = [];
$system_settings = [];

try {
    // 獲取所有設定
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // 獲取基本設定
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'site_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $basic_settings[$row['setting_key']] = $row['setting_value'];
    }

    // 獲取聯絡資訊設定
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'contact_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contact_settings[$row['setting_key']] = $row['setting_value'];
    }

    // 獲取社群媒體設定
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'social_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $social_settings[$row['setting_key']] = $row['setting_value'];
    }

    // 獲取SEO設定
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'seo_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $seo_settings[$row['setting_key']] = $row['setting_value'];
    }

    // 獲取系統設定
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'system_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $system_settings[$row['setting_key']] = $row['setting_value'];
    }

} catch (PDOException $e) {
    error_log('Error fetching settings: ' . $e->getMessage());
    $_SESSION['error'] = '無法載入設定';
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 開始事務
        $pdo->beginTransaction();

        // 合併所有設定
        $all_settings = array_merge(
            $basic_settings ?: [],
            $contact_settings ?: [],
            $social_settings ?: [],
            $seo_settings ?: [],
            $system_settings ?: []
        );

        // 更新設定
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                              VALUES (:key, :value) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $all_settings) || strpos($key, 'site_') === 0 || 
                strpos($key, 'contact_') === 0 || strpos($key, 'social_') === 0 || 
                strpos($key, 'seo_') === 0 || strpos($key, 'system_') === 0) {
                $stmt->execute([
                    ':key' => $key,
                    ':value' => $value
                ]);
            }
        }

        // 提交事務
        $pdo->commit();
        $_SESSION['success'] = '設定已更新';
        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        // 回滾事務
        $pdo->rollBack();
        error_log('Error updating settings: ' . $e->getMessage());
        $_SESSION['error'] = '更新設定時發生錯誤';
    }
}

$page_title = '網站設定';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" style="padding-top: 80px;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">網站設定</h1>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                <!-- 基本設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">基本設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_name" class="form-label">網站名稱</label>
                                <input type="text" class="form-control" id="site_name" name="site_name"
                                       value="<?php echo htmlspecialchars($basic_settings['site_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="site_url" class="form-label">網站網址</label>
                                <input type="url" class="form-control" id="site_url" name="site_url"
                                       value="<?php echo htmlspecialchars($basic_settings['site_url'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="site_logo" class="form-label">網站 Logo</label>
                                <input type="file" class="form-control" id="site_logo" name="site_logo" accept="image/*">
                                <?php if (!empty($basic_settings['site_logo'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($basic_settings['site_logo']); ?>" 
                                             alt="目前 Logo" style="max-height: 50px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="site_favicon" class="form-label">網站 Favicon</label>
                                <input type="file" class="form-control" id="site_favicon" name="site_favicon" 
                                       accept=".ico,.png">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 聯絡資訊設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">聯絡資訊設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">聯絡電話</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_fax" class="form-label">傳真</label>
                                <input type="text" class="form-control" id="contact_fax" name="contact_fax"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_fax'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">聯絡信箱</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_line" class="form-label">LINE ID</label>
                                <input type="text" class="form-control" id="contact_line" name="contact_line"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_line'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="contact_address" class="form-label">地址</label>
                                <input type="text" class="form-control" id="contact_address" name="contact_address"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_address'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_hours" class="form-label">營業時間</label>
                                <input type="text" class="form-control" id="contact_hours" name="contact_hours"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_hours'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_holiday" class="form-label">休息時間</label>
                                <input type="text" class="form-control" id="contact_holiday" name="contact_holiday"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_holiday'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 社群媒體設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">社群媒體設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="social_facebook" class="form-label">Facebook 連結</label>
                                <input type="url" class="form-control" id="social_facebook" name="social_facebook"
                                       value="<?php echo htmlspecialchars($social_settings['social_facebook'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="social_instagram" class="form-label">Instagram 連結</label>
                                <input type="url" class="form-control" id="social_instagram" name="social_instagram"
                                       value="<?php echo htmlspecialchars($social_settings['social_instagram'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="social_line" class="form-label">LINE 官方帳號</label>
                                <input type="text" class="form-control" id="social_line" name="social_line"
                                       value="<?php echo htmlspecialchars($social_settings['social_line'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="social_youtube" class="form-label">YouTube 頻道</label>
                                <input type="url" class="form-control" id="social_youtube" name="social_youtube"
                                       value="<?php echo htmlspecialchars($social_settings['social_youtube'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO 設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">SEO 設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="seo_title" class="form-label">預設網頁標題</label>
                                <input type="text" class="form-control" id="seo_title" name="seo_title"
                                       value="<?php echo htmlspecialchars($seo_settings['seo_title'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="seo_description" class="form-label">預設網頁描述</label>
                                <textarea class="form-control" id="seo_description" name="seo_description" rows="3"
                                ><?php echo htmlspecialchars($seo_settings['seo_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="seo_keywords" class="form-label">預設關鍵字</label>
                                <input type="text" class="form-control" id="seo_keywords" name="seo_keywords"
                                       value="<?php echo htmlspecialchars($seo_settings['seo_keywords'] ?? ''); ?>">
                                <div class="form-text">多個關鍵字請用逗號分隔</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="seo_google_verification" class="form-label">Google 網站驗證碼</label>
                                <input type="text" class="form-control" id="seo_google_verification" 
                                       name="seo_google_verification"
                                       value="<?php echo htmlspecialchars($seo_settings['seo_google_verification'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="seo_google_analytics" class="form-label">Google Analytics ID</label>
                                <input type="text" class="form-control" id="seo_google_analytics" 
                                       name="seo_google_analytics"
                                       value="<?php echo htmlspecialchars($seo_settings['seo_google_analytics'] ?? ''); ?>">
                            </div>
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
                            <div class="col-md-6 mb-3">
                                <label for="system_maintenance_mode" class="form-label">維護模式</label>
                                <select class="form-select" id="system_maintenance_mode" name="system_maintenance_mode">
                                    <option value="0" <?php echo ($system_settings['system_maintenance_mode'] ?? '') == '0' ? 'selected' : ''; ?>>關閉</option>
                                    <option value="1" <?php echo ($system_settings['system_maintenance_mode'] ?? '') == '1' ? 'selected' : ''; ?>>開啟</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="system_cache_time" class="form-label">快取時間 (秒)</label>
                                <input type="number" class="form-control" id="system_cache_time" name="system_cache_time"
                                       value="<?php echo htmlspecialchars($system_settings['system_cache_time'] ?? '3600'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="system_pagination_limit" class="form-label">每頁顯示筆數</label>
                                <input type="number" class="form-control" id="system_pagination_limit" 
                                       name="system_pagination_limit"
                                       value="<?php echo htmlspecialchars($system_settings['system_pagination_limit'] ?? '10'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="system_timezone" class="form-label">時區設定</label>
                                <select class="form-select" id="system_timezone" name="system_timezone">
                                    <option value="Asia/Taipei" <?php echo ($system_settings['system_timezone'] ?? '') == 'Asia/Taipei' ? 'selected' : ''; ?>>台北時間 (GMT+8)</option>
                                    <option value="UTC" <?php echo ($system_settings['system_timezone'] ?? '') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="system_backup_email" class="form-label">備份通知信箱</label>
                                <input type="email" class="form-control" id="system_backup_email" 
                                       name="system_backup_email"
                                       value="<?php echo htmlspecialchars($system_settings['system_backup_email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存設定
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> 重置
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// 表單驗證
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// 預覽圖片
function previewImage(input, previewElement) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            previewElement.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 監聽檔案上傳
document.getElementById('site_logo').addEventListener('change', function() {
    var preview = document.querySelector('img[alt="目前 Logo"]');
    if (!preview) {
        preview = document.createElement('img');
        preview.alt = '目前 Logo';
        preview.style.maxHeight = '50px';
        this.parentNode.appendChild(preview);
    }
    previewImage(this, preview);
});
</script>

<?php require_once '../includes/footer.php'; ?> 
