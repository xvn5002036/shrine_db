<?php
require_once 'config/config.php';
require_once 'includes/db_connect.php';

session_start();

// 驗證必填欄位
$required_fields = ['name', 'phone', 'email', 'service_type', 'service', 'preferred_date'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = '請填寫所有必填欄位';
        header('Location: prayer.php');
        exit();
    }
}

// 驗證電子信箱格式
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = '請輸入有效的電子信箱';
    header('Location: prayer.php');
    exit();
}

// 驗證日期格式
$preferred_date = date('Y-m-d', strtotime($_POST['preferred_date']));
if ($preferred_date < date('Y-m-d')) {
    $_SESSION['error'] = '預約日期不能早於今天';
    header('Location: prayer.php');
    exit();
}

try {
    // 開始交易
    $pdo->beginTransaction();

    // 插入預約記錄
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            name, phone, email, service_type_id, service_id, 
            prayer_intention, preferred_date, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
        )
    ");

    $stmt->execute([
        $_POST['name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['service_type'],
        $_POST['service'],
        $_POST['prayer_intention'] ?? '',
        $preferred_date
    ]);

    // 獲取預約編號
    $booking_id = $pdo->lastInsertId();

    // 提交交易
    $pdo->commit();

    // 設置成功訊息
    $_SESSION['success'] = '預約成功！您的預約編號為：' . $booking_id;

    // 發送確認郵件
    $to = $_POST['email'];
    $subject = '祈福預約確認通知 - ' . SITE_NAME;
    $message = "
        親愛的 {$_POST['name']} 您好，\n\n
        感謝您在" . SITE_NAME . "預約祈福服務。\n
        您的預約編號為：{$booking_id}\n
        預約日期：{$preferred_date}\n\n
        我們將盡快處理您的預約申請，並以電話或電子郵件方式與您聯繫確認。\n\n
        如有任何問題，請隨時與我們聯繫。\n\n
        順心安康\n
        " . SITE_NAME . "團隊
    ";
    $headers = 'From: ' . SITE_EMAIL . "\r\n" .
        'Reply-To: ' . SITE_EMAIL . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);

} catch (PDOException $e) {
    // 發生錯誤時回滾交易
    $pdo->rollBack();
    error_log('Booking error: ' . $e->getMessage());
    $_SESSION['error'] = '預約失敗，請稍後再試';
}

// 重定向回祈福頁面
header('Location: prayer.php');
exit();
?> 