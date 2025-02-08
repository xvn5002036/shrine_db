<?php
// 檢查是否有更新設定的請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 更新網站設定
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_at = NOW()
        ");

        // 更新網站名稱
        if (isset($_POST['site_name'])) {
            $stmt->execute(['site_name', $_POST['site_name']]);
        }

        // 更新網站描述
        if (isset($_POST['site_description'])) {
            $stmt->execute(['site_description', $_POST['site_description']]);
        }

        // 更新管理員郵箱
        if (isset($_POST['admin_email'])) {
            $stmt->execute(['admin_email', $_POST['admin_email']]);
        }

        // 更新其他設定...
        setFlashMessage('success', '設定已成功更新');
        
    } catch (PDOException $e) {
        error_log('更新設定錯誤：' . $e->getMessage());
        setFlashMessage('error', '更新設定時發生錯誤');
    }
}

// 獲取當前設定
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log('獲取設定錯誤：' . $e->getMessage());
    $settings = [];
}
?>

<div class="content-header">
    <h2 class="content-title">
        <i class="fas fa-cog"></i> 系統設定
    </h2>
</div>

<div class="content-body">
    <?php displayFlashMessages(); ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="settings-section">
                    <h3>基本設定</h3>
                    
                    <div class="form-group">
                        <label for="site_name">網站名稱</label>
                        <input type="text" id="site_name" name="site_name" class="form-control"
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? SITE_NAME); ?>">
                    </div>

                    <div class="form-group">
                        <label for="site_description">網站描述</label>
                        <textarea id="site_description" name="site_description" class="form-control" rows="3"
                        ><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="admin_email">管理員郵箱</label>
                        <input type="email" id="admin_email" name="admin_email" class="form-control"
                               value="<?php echo htmlspecialchars($settings['admin_email'] ?? ADMIN_EMAIL); ?>">
                    </div>
                </div>

                <div class="settings-section">
                    <h3>系統功能設定</h3>
                    
                    <div class="form-group">
                        <label class="switch-label">
                            <input type="checkbox" name="enable_registration" value="1"
                                   <?php echo ($settings['enable_registration'] ?? ENABLE_REGISTRATION) ? 'checked' : ''; ?>>
                            開放會員註冊
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="switch-label">
                            <input type="checkbox" name="enable_comments" value="1"
                                   <?php echo ($settings['enable_comments'] ?? ENABLE_COMMENTS) ? 'checked' : ''; ?>>
                            開放評論功能
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>上傳設定</h3>
                    
                    <div class="form-group">
                        <label for="max_file_size">最大上傳檔案大小 (MB)</label>
                        <input type="number" id="max_file_size" name="max_file_size" class="form-control"
                               value="<?php echo htmlspecialchars($settings['max_file_size'] ?? '5'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="allowed_file_types">允許的檔案類型</label>
                        <input type="text" id="allowed_file_types" name="allowed_file_types" class="form-control"
                               value="<?php echo htmlspecialchars($settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif'); ?>"
                               placeholder="以逗號分隔的副檔名列表">
                    </div>
                </div>

                <div class="settings-section">
                    <h3>安全設定</h3>
                    
                    <div class="form-group">
                        <label for="password_min_length">最小密碼長度</label>
                        <input type="number" id="password_min_length" name="password_min_length" class="form-control"
                               value="<?php echo htmlspecialchars($settings['password_min_length'] ?? PASSWORD_MIN_LENGTH); ?>">
                    </div>

                    <div class="form-group">
                        <label for="login_attempts_limit">登入嘗試次數限制</label>
                        <input type="number" id="login_attempts_limit" name="login_attempts_limit" class="form-control"
                               value="<?php echo htmlspecialchars($settings['login_attempts_limit'] ?? LOGIN_ATTEMPTS_LIMIT); ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存設定
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.settings-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.settings-section:last-child {
    border-bottom: none;
}

.settings-section h3 {
    margin-bottom: 1.5rem;
    color: var(--primary-color);
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 1rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    outline: none;
}

.switch-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.switch-label input[type="checkbox"] {
    margin-right: 0.5rem;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}
</style> 