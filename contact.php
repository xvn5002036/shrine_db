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
        try {
            // 儲存到資料庫
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            // 發送通知郵件
            $to = "xvn5002036@gmail.com"; // 請更換為您的 Email
            $mail_subject = "新的聯絡表單訊息: " . $subject;
            $mail_content = "姓名: $name\n";
            $mail_content .= "Email: $email\n";
            $mail_content .= "電話: $phone\n";
            $mail_content .= "主旨: $subject\n\n";
            $mail_content .= "訊息內容:\n$message";
            
            @mail($to, $mail_subject, $mail_content);
            
            $success = true;
            $name = $email = $phone = $subject = $message = '';
        } catch (Exception $e) {
            error_log('儲存聯絡表單資料錯誤：' . $e->getMessage());
            $error = '發生錯誤，請稍後再試';
        }
    }
}

// 頁面標題
$page_title = "聯絡我們 | " . SITE_NAME;
require_once 'templates/header.php';
?>

<div class="contact-page">
    <div class="contact-header">
        <div class="container">
            <h1>聯絡我們</h1>
            <p>歡迎與我們聯繫，我們將盡快回覆您的訊息</p>
        </div>
    </div>

    <div class="contact-content">
        <div class="container">
            <?php if ($success): ?>
                <div class="message-box success">
                    <i class="fas fa-check-circle"></i>
                    <p>感謝您的來信，我們會盡快回覆您。</p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message-box error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="contact-grid">
                <div class="contact-info">
                    <div class="info-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>地址</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_address')); ?></p>
                    </div>

                    <div class="info-card">
                        <i class="fas fa-phone"></i>
                        <h3>電話</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_phone')); ?></p>
                    </div>

                    <div class="info-card">
                        <i class="fas fa-envelope"></i>
                        <h3>Email</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_email')); ?></p>
                    </div>

                    <div class="info-card">
                        <i class="fas fa-clock"></i>
                        <h3>開放時間</h3>
                        <div class="hours">
                            <p>平日：<?php echo htmlspecialchars(getSetting('opening_hours_weekday')); ?></p>
                            <p>假日：<?php echo htmlspecialchars(getSetting('opening_hours_weekend')); ?></p>
                            <p><?php echo htmlspecialchars(getSetting('opening_hours_special')); ?></p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <form method="post" action="contact.php">
                        <div class="form-group">
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                            <label for="name">姓名 <span class="required">*</span></label>
                        </div>

                        <div class="form-group">
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            <label for="email">Email <span class="required">*</span></label>
                        </div>

                        <div class="form-group">
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            <label for="phone">電話</label>
                        </div>

                        <div class="form-group">
                            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                            <label for="subject">主旨</label>
                        </div>

                        <div class="form-group">
                            <textarea id="message" name="message" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            <label for="message">訊息內容 <span class="required">*</span></label>
                        </div>

                        <button type="submit">
                            <span>送出訊息</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- 地圖區塊 -->
            <div class="map-section">
                <div class="map-wrapper">
                    <div class="map-info">
                        <h3><i class="fas fa-map-marked-alt"></i> 宮廟位置</h3>
                        <p><?php echo htmlspecialchars(getSetting('contact_address')); ?></p>
                        <div class="map-actions">
                            <a href="https://www.google.com/maps/dir//242新北市新莊區福營路500號" target="_blank" class="map-button">
                                <i class="fas fa-directions"></i> Google Maps
                            </a>
                            <a href="https://maps.apple.com/?daddr=25.02345,121.41949" target="_blank" class="map-button">
                                <i class="fas fa-map"></i> Apple Maps
                            </a>
                        </div>
                    </div>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3615.3104278685173!2d121.41696057506164!3d25.023537138713348!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3442a75aadeeb6e5%3A0x1422dba0d2ca5c74!2zMjQy5paw5YyX5biC5paw6I6K5Y2A56aP54ef6LevNTAw6Jmf!5e0!3m2!1szh-TW!2stw!4v1738858139167!5m2!1szh-TW!2stw"
                            width="100%" 
                            height="100%" 
                            frameborder="0"
                            style="border:0; position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                            allowfullscreen="" 
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.contact-page {
    background-color: #f8f9fa;
    min-height: calc(100vh - 60px);
    padding-bottom: 40px;
}

.contact-header {
    background: linear-gradient(135deg, #4a90e2 0%, #8e44ad 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    margin-bottom: 40px;
}

.contact-header h1 {
    font-size: 2.5em;
    margin-bottom: 20px;
    font-weight: 300;
}

.contact-header p {
    font-size: 1.1em;
    opacity: 0.9;
    margin: 0;
}

.contact-content {
    position: relative;
    z-index: 1;
}

.message-box {
    max-width: 600px;
    margin: 0 auto 30px;
    padding: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.message-box.success {
    background-color: #d4edda;
    color: #155724;
}

.message-box.error {
    background-color: #f8d7da;
    color: #721c24;
}

.message-box i {
    font-size: 24px;
}

.message-box p {
    margin: 0;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.contact-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    align-content: start;
}

.info-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    text-align: center;
    transition: transform 0.3s ease;
}

.info-card:hover {
    transform: translateY(-5px);
}

.info-card i {
    font-size: 2em;
    color: #4a90e2;
    margin-bottom: 15px;
}

.info-card h3 {
    font-size: 1.2em;
    margin-bottom: 15px;
    color: #333;
}

.info-card p {
    color: #666;
    margin: 0;
    line-height: 1.6;
}

.contact-form {
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    align-self: start;
}

.form-group {
    position: relative;
    margin-bottom: 25px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 0;
    font-size: 16px;
    border: none;
    border-bottom: 2px solid #ddd;
    background: transparent;
    transition: border-color 0.3s ease;
}

.form-group label {
    position: absolute;
    top: 12px;
    left: 0;
    font-size: 16px;
    color: #999;
    transition: all 0.3s ease;
    pointer-events: none;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4a90e2;
}

.form-group input:focus + label,
.form-group textarea:focus + label,
.form-group input:not(:placeholder-shown) + label,
.form-group textarea:not(:placeholder-shown) + label {
    top: -20px;
    font-size: 12px;
    color: #4a90e2;
}

.form-group textarea {
    height: 100px;
    resize: none;
}

.required {
    color: #e74c3c;
}

button {
    background: linear-gradient(135deg, #4a90e2 0%, #8e44ad 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 30px;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: transform 0.3s ease;
}

button:hover {
    transform: translateY(-2px);
}

@media (max-width: 992px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-info {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .contact-header {
        padding: 60px 0;
        margin-bottom: 30px;
    }
    
    .contact-info {
        grid-template-columns: 1fr;
    }
    
    .contact-form {
        padding: 30px;
    }
}

/* 地圖區塊樣式 */
.map-section {
    margin-top: 60px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

.map-wrapper {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    overflow: hidden;
    display: grid;
    grid-template-columns: 300px 1fr;
    height: 500px;
}

.map-info {
    padding: 30px;
    background: linear-gradient(135deg, #4a90e2 0%, #8e44ad 100%);
    color: white;
}

.map-info h3 {
    font-size: 1.3em;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.map-info h3 i {
    font-size: 1.2em;
}

.map-info p {
    margin-bottom: 20px;
    line-height: 1.6;
    opacity: 0.9;
}

.map-actions {
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.map-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 25px;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 0.95em;
}

.map-button:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    transform: translateY(-2px);
}

.map-button i {
    font-size: 1.2em;
}

@media (max-width: 992px) {
    .map-actions {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }

    .map-button {
        width: auto;
        min-width: 150px;
    }
}

.map-container {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 500px;
}

.map-container iframe {
    display: block;
}

@media (max-width: 992px) {
    .map-wrapper {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .map-info {
        padding: 20px;
    }
    
    .map-container {
        height: 400px;
        min-height: 400px;
    }
}

@media (max-width: 768px) {
    .map-section {
        margin-top: 40px;
    }
    
    .map-container {
        height: 300px;
        min-height: 300px;
    }
}
</style>

<script>
// 表單驗證
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php include 'templates/footer.php'; ?> 