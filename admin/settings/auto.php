<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

$errors = [];
$success = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 自動化設定
        $auto_backup_enabled = isset($_POST['auto_backup_enabled']) ? 1 : 0;
        $auto_backup_time = trim($_POST['auto_backup_time'] ?? '');
        $auto_backup_keep_days = (int)($_POST['auto_backup_keep_days'] ?? 30);
        $auto_report_enabled = isset($_POST['auto_report_enabled']) ? 1 : 0;
        $auto_report_email = trim($_POST['auto_report_email'] ?? '');
        $auto_report_time = trim($_POST['auto_report_time'] ?? '');
        
        // 驗證設定
        if ($auto_backup_enabled && empty($auto_backup_time)) {
            $errors[] = "請選擇自動備份時間";
        }
        if ($auto_backup_keep_days < 1) {
            $errors[] = "備份保留天數必須大於0";
        }
        if ($auto_report_enabled) {
            if (empty($auto_report_email)) {
                $errors[] = "請輸入報表接收Email";
            } elseif (!filter_var($auto_report_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "請輸入有效的Email格式";
            }
            if (empty($auto_report_time)) {
                $errors[] = "請選擇自動報表時間";
            }
        }

        if (empty($errors)) {
            // 更新設定
            $settings = [
                'auto_backup_enabled' => $auto_backup_enabled,
                'auto_backup_time' => $auto_backup_time,
                'auto_backup_keep_days' => $auto_backup_keep_days,
                'auto_report_enabled' => $auto_report_enabled,
                'auto_report_email' => $auto_report_email,
                'auto_report_time' => $auto_report_time
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) 
                                     VALUES (:key, :value, NOW()) 
                                     ON DUPLICATE KEY UPDATE 
                                     setting_value = :value, updated_at = NOW()");
                $stmt->execute([
                    ':key' => $key,
                    ':value' => $value
                ]);
            }

            // 記錄操作日誌
            logAdminAction('更新設定', "更新自動化設定");

            // 設置成功消息
            setFlashMessage('success', '自動化設定已成功更新！');
            header('Location: auto.php');
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = '保存失敗：' . $e->getMessage();
    }
}

// 獲取當前設定
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'auto_%'");
    $current_settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $errors[] = "讀取設定失敗：" . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 自動化設定</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>自動化設定</h2>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="form">
                        <div class="form-section">
                            <h3>自動備份設定</h3>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="auto_backup_enabled" value="1" 
                                           <?php echo (!empty($current_settings['auto_backup_enabled'])) ? 'checked' : ''; ?>>
                                    啟用自動備份
                                </label>
                            </div>

                            <div class="form-group">
                                <label for="auto_backup_time">備份時間</label>
                                <input type="time" id="auto_backup_time" name="auto_backup_time" 
                                       value="<?php echo htmlspecialchars($current_settings['auto_backup_time'] ?? ''); ?>">
                                <small class="form-text">選擇每天執行備份的時間</small>
                            </div>

                            <div class="form-group">
                                <label for="auto_backup_keep_days">備份保留天數</label>
                                <input type="number" id="auto_backup_keep_days" name="auto_backup_keep_days" min="1" 
                                       value="<?php echo htmlspecialchars($current_settings['auto_backup_keep_days'] ?? '30'); ?>">
                                <small class="form-text">超過指定天數的備份檔案將自動刪除</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>自動報表設定</h3>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="auto_report_enabled" value="1" 
                                           <?php echo (!empty($current_settings['auto_report_enabled'])) ? 'checked' : ''; ?>>
                                    啟用自動報表
                                </label>
                            </div>

                            <div class="form-group">
                                <label for="auto_report_email">報表接收Email</label>
                                <input type="email" id="auto_report_email" name="auto_report_email" 
                                       value="<?php echo htmlspecialchars($current_settings['auto_report_email'] ?? ''); ?>">
                                <small class="form-text">自動產生的報表將發送到此Email</small>
                            </div>

                            <div class="form-group">
                                <label for="auto_report_time">報表產生時間</label>
                                <input type="time" id="auto_report_time" name="auto_report_time" 
                                       value="<?php echo htmlspecialchars($current_settings['auto_report_time'] ?? ''); ?>">
                                <small class="form-text">選擇每天產生報表的時間</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">保存設定</button>
                            <button type="reset" class="btn btn-secondary">重置</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 