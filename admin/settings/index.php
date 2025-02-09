<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 檢查管理員權限
checkAdminRole();

// 初始化設定陣列
$settings = [];
$contact_settings = [];
$social_settings = [];
$other_settings = [];

try {
    // 獲取所有設定
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
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

    // 獲取其他設定
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key NOT LIKE 'contact_%' AND setting_key NOT LIKE 'social_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $other_settings[$row['setting_key']] = $row['setting_value'];
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
            $contact_settings ?: [],
            $social_settings ?: [],
            $other_settings ?: []
        );

        // 更新設定
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                              VALUES (:key, :value) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $all_settings)) {
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
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
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

            <form method="POST" class="needs-validation" novalidate>
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
                                <label for="contact_email" class="form-label">聯絡信箱</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_email'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="contact_address" class="form-label">地址</label>
                                <input type="text" class="form-control" id="contact_address" name="contact_address"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_address'] ?? ''); ?>">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="contact_hours" class="form-label">營業時間</label>
                                <input type="text" class="form-control" id="contact_hours" name="contact_hours"
                                       value="<?php echo htmlspecialchars($contact_settings['contact_hours'] ?? ''); ?>">
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
                                <label for="social_line" class="form-label">LINE ID</label>
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

                <!-- 其他設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">其他設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="site_description" class="form-label">網站描述</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"
                                ><?php echo htmlspecialchars($other_settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="site_keywords" class="form-label">網站關鍵字</label>
                                <input type="text" class="form-control" id="site_keywords" name="site_keywords"
                                       value="<?php echo htmlspecialchars($other_settings['site_keywords'] ?? ''); ?>">
                                <div class="form-text">多個關鍵字請用逗號分隔</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="google_analytics" class="form-label">Google Analytics 追蹤碼</label>
                                <input type="text" class="form-control" id="google_analytics" name="google_analytics"
                                       value="<?php echo htmlspecialchars($other_settings['google_analytics'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存設定
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
</script>

<?php require_once '../includes/footer.php'; ?> 
