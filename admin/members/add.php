<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

$errors = [];

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 驗證表單數據
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // 驗證必填欄位
    if (empty($name)) {
        $errors[] = "請輸入會員姓名";
    }
    if (empty($email)) {
        $errors[] = "請輸入Email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "請輸入有效的Email格式";
    }
    if (empty($password)) {
        $errors[] = "請輸入密碼";
    } elseif (strlen($password) < 6) {
        $errors[] = "密碼長度至少需要6個字元";
    }

    // 檢查Email是否已存在
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "此Email已被註冊";
        }
    }

    // 如果沒有錯誤，新增會員
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO members (
                name, email, phone, password_hash, status, created_at
            ) VALUES (
                :name, :email, :phone, :password_hash, :status, NOW()
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':status' => $status
            ]);

            // 記錄操作日誌
            logAdminAction('新增會員', "新增會員：{$name} ({$email})");

            // 設置成功消息
            setFlashMessage('success', '會員新增成功！');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = '保存失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 新增會員</title>
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
                    <h2>新增會員</h2>
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
                        <div class="form-group">
                            <label for="name">姓名 <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">電話</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">密碼 <span class="text-danger">*</span></label>
                            <input type="password" id="password" name="password" required>
                            <small class="form-text">密碼長度至少需要6個字元</small>
                        </div>

                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo (isset($status) && $status == 'active') ? 'selected' : ''; ?>>正常</option>
                                <option value="inactive" <?php echo (isset($status) && $status == 'inactive') ? 'selected' : ''; ?>>未啟用</option>
                                <option value="blocked" <?php echo (isset($status) && $status == 'blocked') ? 'selected' : ''; ?>>已封鎖</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">新增會員</button>
                            <a href="index.php" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 