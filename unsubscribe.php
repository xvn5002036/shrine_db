<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$status = '';

// 檢查是否有提供電子郵件
if (isset($_GET['email'])) {
    $email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        try {
            // 檢查訂閱狀態
            $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch();

            if ($subscriber) {
                if ($subscriber['status'] === 'subscribed') {
                    // 處理退訂
                    if (isset($_POST['confirm'])) {
                        $stmt = $pdo->prepare("
                            UPDATE newsletter_subscribers 
                            SET status = 'unsubscribed', 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE email = ?
                        ");
                        $stmt->execute([$email]);
                        
                        $status = 'success';
                        $message = '您已成功退訂電子報。如果您改變主意，隨時可以重新訂閱。';
                    }
                } else {
                    $status = 'info';
                    $message = '此電子郵件已經退訂過了。';
                }
            } else {
                $status = 'warning';
                $message = '找不到此電子郵件的訂閱記錄。';
            }
        } catch (PDOException $e) {
            error_log("退訂處理錯誤：" . $e->getMessage());
            $status = 'error';
            $message = '處理您的請求時發生錯誤，請稍後再試。';
        }
    } else {
        $status = 'error';
        $message = '無效的電子郵件地址。';
    }
}

$page_title = '退訂電子報';
include 'templates/header.php';
?>

<div class="content-wrapper">
    <div class="container">
        <div class="content-header">
            <h1>退訂電子報</h1>
            <div class="breadcrumb">
                <a href="index.php">首頁</a>
                <i class="fas fa-angle-right"></i>
                <span>退訂電子報</span>
            </div>
        </div>

        <div class="content-body">
            <div class="unsubscribe-content">
                <?php if ($status === 'success'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $message; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">返回首頁</a>
                    </div>
                <?php elseif ($status === 'info' || $status === 'warning'): ?>
                    <div class="alert alert-<?php echo $status === 'info' ? 'info' : 'warning'; ?>">
                        <i class="fas fa-info-circle"></i>
                        <?php echo $message; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">返回首頁</a>
                    </div>
                <?php elseif ($status === 'error'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $message; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">返回首頁</a>
                    </div>
                <?php elseif (isset($_GET['email']) && !$status): ?>
                    <div class="confirmation-form">
                        <p class="mb-4">您確定要退訂以下電子郵件的電子報嗎？</p>
                        <p class="email-display mb-4"><?php echo htmlspecialchars($email); ?></p>
                        <form method="post" class="text-center">
                            <button type="submit" name="confirm" class="btn btn-danger">
                                <i class="fas fa-times-circle"></i> 確認退訂
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 返回首頁
                            </a>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="manual-unsubscribe">
                        <p class="mb-4">請輸入您要退訂的電子郵件地址：</p>
                        <form method="get" class="unsubscribe-form">
                            <div class="form-group mb-4">
                                <input type="email" name="email" class="form-control" required
                                       placeholder="請輸入您的電子郵件">
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 查詢訂閱狀態
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.unsubscribe-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto 40px;
}

.alert {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    margin-right: 10px;
}

.email-display {
    font-size: 1.2em;
    color: #666;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    text-align: center;
}

.unsubscribe-form {
    max-width: 400px;
    margin: 0 auto;
}

.btn {
    padding: 8px 20px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4a90e2;
    color: white;
    border: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    margin-left: 10px;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
}

.btn:hover {
    opacity: 0.9;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
}

.text-center {
    text-align: center;
}

.mt-4 {
    margin-top: 20px;
}

.mb-4 {
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .unsubscribe-content {
        padding: 20px;
    }
}
</style>

<?php include 'templates/footer.php'; ?> 