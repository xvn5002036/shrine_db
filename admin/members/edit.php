<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$errors = [];

// 獲取會員資料
try {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "資料庫錯誤：" . $e->getMessage();
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 驗證表單數據
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? ''); // 可選
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

    // 檢查Email是否已被其他會員使用
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "此Email已被其他會員使用";
        }
    }

    // 如果沒有錯誤，更新會員資料
    if (empty($errors)) {
        try {
            // 準備更新欄位
            $updateFields = [
                'name = :name',
                'email = :email',
                'phone = :phone',
                'status = :status',
                'updated_at = NOW()'
            ];
            $params = [
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':status' => $status,
                ':id' => $id
            ];

            // 如果有提供新密碼，加入密碼更新
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $errors[] = "密碼長度至少需要6個字元";
                } else {
                    $updateFields[] = 'password_hash = :password_hash';
                    $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
            }

            if (empty($errors)) {
                $sql = "UPDATE members SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // 記錄操作日誌
                logAdminAction('更新會員', "更新會員資料：{$name} ({$email})");

                // 設置成功消息
                setFlashMessage('success', '會員資料更新成功！');
                header('Location: index.php');
                exit;
            }
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
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 編輯會員</title>
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
                    <h2>編輯會員</h2>
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
                                   value="<?php echo htmlspecialchars($member['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($member['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">電話</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">新密碼</label>
                            <input type="password" id="password" name="password">
                            <small class="form-text">如果不修改密碼，請留空。新密碼長度至少需要6個字元</small>
                        </div>

                        <div class="form-group">
                            <label for="status">狀態</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo ($member['status'] == 'active') ? 'selected' : ''; ?>>正常</option>
                                <option value="inactive" <?php echo ($member['status'] == 'inactive') ? 'selected' : ''; ?>>未啟用</option>
                                <option value="blocked" <?php echo ($member['status'] == 'blocked') ? 'selected' : ''; ?>>已封鎖</option>
                            </select>
                        </div>

                        <div class="form-info">
                            <p>註冊時間：<?php echo date('Y-m-d H:i:s', strtotime($member['created_at'])); ?></p>
                            <?php if ($member['last_login']): ?>
                                <p>最後登入：<?php echo date('Y-m-d H:i:s', strtotime($member['last_login'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">更新會員</button>
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