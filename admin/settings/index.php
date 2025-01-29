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
        // 基本設定
        $site_name = trim($_POST['site_name'] ?? '');
        $site_description = trim($_POST['site_description'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');
        $items_per_page = (int)($_POST['items_per_page'] ?? 20);
        
        // 驗證必填欄位
        if (empty($site_name)) {
            $errors[] = "請輸入網站名稱";
        }
        if (!empty($admin_email) && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "請輸入有效的管理員Email格式";
        }
        if ($items_per_page < 1) {
            $errors[] = "每頁顯示數量必須大於0";
        }

        if (empty($errors)) {
            // 更新設定
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'admin_email' => $admin_email,
                'items_per_page' => $items_per_page
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

            // 處理Logo上傳
            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($_FILES['site_logo']['type'], $allowed_types)) {
                    $errors[] = "Logo只允許上傳 JPG、PNG 或 GIF 格式的圖片";
                } elseif ($_FILES['site_logo']['size'] > $max_size) {
                    $errors[] = "Logo圖片大小不能超過 5MB";
                } else {
                    $upload_dir = '../../uploads/settings/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $filename = 'logo_' . time() . '_' . basename($_FILES['site_logo']['name']);
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $filepath)) {
                        // 更新資料庫中的 Logo 路徑
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) 
                                             VALUES ('site_logo', :value, NOW()) 
                                             ON DUPLICATE KEY UPDATE 
                                             setting_value = :value, updated_at = NOW()");
                        $stmt->execute([':value' => 'uploads/settings/' . $filename]);
                    } else {
                        $errors[] = "Logo上傳失敗";
                    }
                }
            }

            if (empty($errors)) {
                // 記錄操作日誌
                logAdminAction('更新設定', "更新系統設定");

                // 設置成功消息
                setFlashMessage('success', '設定已成功更新！');
                header('Location: index.php');
                exit;
            }
        }
    } catch (PDOException $e) {
        $errors[] = '保存失敗：' . $e->getMessage();
    }
}

// 獲取當前設定
try {
    $stmt = $pdo->query("SELECT * FROM settings");
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
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 系統設定</title>
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
                    <h2>系統設定</h2>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="form" enctype="multipart/form-data">
                        <div class="form-section">
                            <h3>基本設定</h3>
                            
                            <div class="form-group">
                                <label for="site_name">網站名稱 <span class="text-danger">*</span></label>
                                <input type="text" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($current_settings['site_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="site_description">網站描述</label>
                                <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($current_settings['site_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="admin_email">管理員Email</label>
                                <input type="email" id="admin_email" name="admin_email" 
                                       value="<?php echo htmlspecialchars($current_settings['admin_email'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="items_per_page">每頁顯示數量</label>
                                <input type="number" id="items_per_page" name="items_per_page" min="1" 
                                       value="<?php echo htmlspecialchars($current_settings['items_per_page'] ?? '20'); ?>">
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Logo設定</h3>
                            
                            <div class="form-group">
                                <label for="site_logo">網站Logo</label>
                                <?php if (!empty($current_settings['site_logo'])): ?>
                                    <div class="current-logo">
                                        <img src="../../<?php echo htmlspecialchars($current_settings['site_logo']); ?>" 
                                             alt="當前Logo" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="site_logo" name="site_logo" accept="image/*">
                                <small class="form-text">支援 JPG、PNG、GIF 格式，最大 5MB</small>
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