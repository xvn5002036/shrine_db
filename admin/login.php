<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 檢查 session 是否已經啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 確保 login_attempts 資料表存在
try {
    $pdo->query("
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(45) NOT NULL,
            `username` varchar(50) NOT NULL,
            `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ip_time` (`ip_address`, `attempt_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (PDOException $e) {
    error_log('創建 login_attempts 資料表時發生錯誤：' . $e->getMessage());
}

// 如果已經登入，重定向到後台首頁
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

// 處理登入請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '請輸入帳號和密碼';
    } else {
        try {
            // 檢查登入嘗試次數
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $stmt->execute([$ip]);
            $attempts = $stmt->fetchColumn();

            if ($attempts >= 5) {
                $error = '登入嘗試次數過多，請稍後再試';
            } else {
                // 驗證用戶憑證
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1 AND (role = 'admin' OR role = 'staff') LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // 登入成功
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_role'] = $user['role'];

                    // 更新最後登入時間
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // 清除登入嘗試記錄
                    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                    $stmt->execute([$ip]);

                    // 記錄登入日誌
                    logAdminAction('login', "管理員 {$user['username']} 登入成功");

                    // 重定向到後台首頁
                    header('Location: index.php');
                    exit;
                } else {
                    // 記錄失敗的登入嘗試
                    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())");
                    $stmt->execute([$ip, $username]);

                    $error = '帳號或密碼錯誤';
                }
            }
        } catch (PDOException $e) {
            error_log('登入時發生錯誤：' . $e->getMessage());
            $error = '系統錯誤，請稍後再試';
        }
    }
}

// 獲取錯誤訊息（如果有的話）
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'account_inactive':
            $error = '帳號已被停用';
            break;
        case 'system_error':
            $error = '系統錯誤，請稍後再試';
            break;
        case 'session_expired':
            $error = '登入階段已過期，請重新登入';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 後台管理登入</title>
    <link rel="stylesheet" href="../assets/css/admin-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        .login-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #666;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #4a90e2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74,144,226,0.2);
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            background-color: #4a90e2;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #357abd;
        }
        .alert {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            font-size: 14px;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
        .login-footer a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        .login-footer a:hover {
            color: #4a90e2;
        }
        @media (max-width: 480px) {
            .login-container {
                width: 100%;
                padding: 10px;
            }
            .login-box {
                padding: 20px;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>後台管理系統</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> 帳號
                    </label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> 密碼
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> 登入
                </button>
            </form>
            
            <div class="login-footer">
                <p><a href="../index.php"><i class="fas fa-home"></i> 返回前台首頁</a></p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 自動隱藏錯誤訊息
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 3000);
        }
    });
    </script>
</body>
</html> 
