<?php
session_start();

// 檢查是否已經安裝
if (file_exists('../config/installed.php') && !isset($_GET['force'])) {
    die('系統已經安裝。如果需要重新安裝，請刪除 config/installed.php 檔案');
}

// 處理資料庫設定
if ($_POST['step'] == '2') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    try {
        // 測試資料庫連線
        $dsn = "mysql:host=$db_host;charset=utf8mb4";
        $db = new PDO($dsn, $db_user, $db_pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 如果是重新安裝，先刪除舊的資料庫
        if (isset($_GET['force'])) {
            $db->exec("DROP DATABASE IF EXISTS `$db_name`");
        }

        // 創建資料庫
        $db->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->exec("USE `$db_name`");

        // 創建導入記錄表
        $db->exec("CREATE TABLE IF NOT EXISTS `sql_import_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `filename` varchar(255) NOT NULL,
            `imported_at` datetime NOT NULL,
            `checksum` varchar(32) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `filename` (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 檢查 main.sql 是否存在
        if (!file_exists('../sql/main.sql')) {
            die('錯誤：找不到必要的 main.sql 檔案');
        }

        // 優先導入 main.sql
        $main_sql = file_get_contents('../sql/main.sql');
        $main_checksum = md5($main_sql);
        
        // 檢查 main.sql 是否需要重新導入
        $stmt = $db->prepare("SELECT checksum FROM sql_import_logs WHERE filename = 'main.sql'");
        $stmt->execute();
        $stored_checksum = $stmt->fetchColumn();
        
        if ($stored_checksum !== $main_checksum || isset($_GET['force'])) {
            // 執行 main.sql
            $db->exec($main_sql);
            
            // 更新導入記錄
            $stmt = $db->prepare("INSERT INTO sql_import_logs (filename, imported_at, checksum) 
                                VALUES ('main.sql', NOW(), ?) 
                                ON DUPLICATE KEY UPDATE imported_at = NOW(), checksum = ?");
            $stmt->execute([$main_checksum, $main_checksum]);
        }

        // 獲取所有其他 SQL 檔案
        $sql_files = array_filter(glob('../sql/*.sql'), function($file) {
            return basename($file) !== 'main.sql';
        });

        // 導入其他 SQL 檔案
        foreach ($sql_files as $sql_file) {
            $filename = basename($sql_file);
            $sql_content = file_get_contents($sql_file);
            $checksum = md5($sql_content);
            
            // 檢查檔案是否需要導入
            $stmt = $db->prepare("SELECT checksum FROM sql_import_logs WHERE filename = ?");
            $stmt->execute([$filename]);
            $stored_checksum = $stmt->fetchColumn();
            
            if ($stored_checksum !== $checksum || isset($_GET['force'])) {
                try {
                    // 執行 SQL
                    $db->exec($sql_content);
                    
                    // 更新導入記錄
                    $stmt = $db->prepare("INSERT INTO sql_import_logs (filename, imported_at, checksum) 
                                        VALUES (?, NOW(), ?) 
                                        ON DUPLICATE KEY UPDATE imported_at = NOW(), checksum = ?");
                    $stmt->execute([$filename, $checksum, $checksum]);
                    
                    error_log("成功導入 SQL 檔案：" . $filename);
                } catch (Exception $e) {
                    error_log("導入 SQL 檔案失敗：" . $filename . " - " . $e->getMessage());
                }
            }
        }

        // 儲存資料庫設定
        $config = "<?php
return [
    'host' => '$db_host',
    'dbname' => '$db_name',
    'username' => '$db_user',
    'password' => '$db_pass',
    'charset' => 'utf8mb4'
];";
        
        if (!is_dir('../config')) {
            mkdir('../config', 0755, true);
        }
        file_put_contents('../config/database.php', $config);
        
        // 重定向到下一步
        header('Location: index.php?step=3');
        exit;
    } catch (PDOException $e) {
        die('資料庫連線錯誤：' . $e->getMessage());
    }
}

// 處理網站基本設定
if ($_POST['step'] == '3') {
    try {
        // 載入資料庫設定
        $dbConfig = require('../config/database.php');
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $db = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 儲存網站設定
        $site_name = $_POST['site_name'];
        $admin_username = $_POST['admin_username'];
        $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        $admin_email = $_POST['admin_email'];

        try {
            // 更新網站設定
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_name'");
            $stmt->execute([$site_name]);

            // 檢查users表格是否存在
            $stmt = $db->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() == 0) {
                throw new Exception('users表格不存在');
            }

            // 刪除現有的管理員帳號
            $stmt = $db->prepare("DELETE FROM users WHERE role = 'admin'");
            $stmt->execute();

            // 創建管理員帳號
            $stmt = $db->prepare("INSERT INTO users (username, password, email, role, status, created_at, updated_at) VALUES (?, ?, ?, 'admin', 1, NOW(), NOW())");
            if (!$stmt->execute([$admin_username, $admin_password, $admin_email])) {
                throw new Exception('創建管理員帳號失敗：' . implode(', ', $stmt->errorInfo()));
            }

            // 驗證管理員帳號是否創建成功
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
            $stmt->execute([$admin_username]);
            $admin = $stmt->fetch();
            if (!$admin) {
                throw new Exception('管理員帳號創建後無法驗證');
            }

            error_log("管理員帳號創建成功：{$admin_username}");

            // 標記安裝完成
            file_put_contents('../config/installed.php', '<?php return true;');

            // 重定向到完成頁面
            header('Location: index.php?step=4');
            exit;
        } catch (Exception $e) {
            die('設定錯誤：' . $e->getMessage());
        }
    } catch (Exception $e) {
        die('設定錯誤：' . $e->getMessage());
    }
}

// 如果沒有 POST 資料，重定向回首頁
header('Location: index.php');
exit; 