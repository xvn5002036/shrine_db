<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$success = false;
$error = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // 驗證
    if (empty($name) || empty($email) || empty($message)) {
        $error = '請填寫必填欄位';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '請輸入有效的電子郵件地址';
    } else {
        // 儲存到資料庫或發送郵件
        try {
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            // 發送通知郵件給管理員
            $admin_email = ADMIN_EMAIL;
            $mail_subject = "新的聯絡表單訊息: " . $subject;
            $mail_message = "姓名: $name\n";
            $mail_message .= "Email: $email\n";
            $mail_message .= "電話: $phone\n";
            $mail_message .= "主旨: $subject\n\n";
            $mail_message .= "訊息內容:\n$message";
            
            mail($admin_email, $mail_subject, $mail_message);
            
            $success = true;
            
            // 清空表單
            $name = $email = $phone = $subject = $message = '';
        } catch (Exception $e) {
            $error = '發生錯誤，請稍後再試';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 聯絡我們</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>聯絡我們</h1>
        </div>

        <div class="contact-container">
            <div class="contact-info">
                <h2>聯絡資訊</h2>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>地址</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_address')); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>電話</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_phone')); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_email')); ?></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>開放時間</h3>
                        <p>平日：<?php echo htmlspecialchars(getSetting('opening_hours_weekday')); ?></p>
                        <p>假日：<?php echo htmlspecialchars(getSetting('opening_hours_weekend')); ?></p>
                        <p><?php echo htmlspecialchars(getSetting('opening_hours_special')); ?></p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <h2>聯絡表單</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        感謝您的來信，我們會盡快回覆您。
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="contact.php">
                    <div class="form-group">
                        <label for="name">姓名 <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">電話</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject">主旨</label>
                        <input type="text" id="subject" name="subject" class="form-control" value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">訊息內容 <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">送出訊息</button>
                </form>
            </div>
        </div>

        <div class="map-container">
            <!-- 這裡可以嵌入 Google 地圖 -->
            <iframe src="about:blank" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html> 