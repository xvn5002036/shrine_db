<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 檢查 session 是否已經啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 如果已經登入，重定向到後台首頁
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

// 處理登入請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = '請輸入帳號和密碼';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare("SELECT id, password, status FROM admins WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $username;

                // 更新最後登入時間
                $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);

                // 檢查是否有需要重定向的頁面
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirect);
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = '帳號或密碼錯誤';
            }
        } catch (PDOException $e) {
            error_log('登入錯誤：' . $e->getMessage());
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
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">密碼</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">登入</button>
            </form>
            
            <div class="login-footer">
                <p><a href="../index.php">返回前台首頁</a></p>
            </div>
        </div>
    </div>
</body>
</html> 