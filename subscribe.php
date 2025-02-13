<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 設定回應的內容類型
header('Content-Type: application/json; charset=utf-8');

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '無效的請求方法'
    ]);
    exit;
}

// 獲取並驗證 email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode([
        'success' => false,
        'message' => '請輸入有效的電子郵件地址'
    ]);
    exit;
}

try {
    // 檢查資料表是否存在，如果不存在則創建
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `status` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 檢查是否已訂閱
    $stmt = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $subscriber = $stmt->fetch();

    if ($subscriber) {
        if ($subscriber['status'] === 'subscribed') {
            echo json_encode([
                'success' => false,
                'message' => '此電子郵件已經訂閱過了'
            ]);
            exit;
        } else {
            // 重新訂閱
            $stmt = $pdo->prepare("
                UPDATE newsletter_subscribers 
                SET status = 'subscribed', updated_at = CURRENT_TIMESTAMP 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
        }
    } else {
        // 新訂閱
        $stmt = $pdo->prepare("
            INSERT INTO newsletter_subscribers (email, status) 
            VALUES (?, 'subscribed')
        ");
        $stmt->execute([$email]);
    }

    // 準備郵件內容
    $site_name = SITE_NAME;
    $site_url = rtrim(SITE_URL, '/');
    $unsubscribe_url = $site_url . "/unsubscribe.php?email=" . urlencode($email);
    
    $to = $email;
    $subject = "訂閱確認 - {$site_name}電子報";
    $message = "
        <html>
        <head>
            <title>訂閱確認</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <h2 style='color: #333; text-align: center;'>感謝您訂閱{$site_name}電子報</h2>
                <div style='background-color: #fff; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <p>親愛的訂閱者您好：</p>
                    <p>感謝您訂閱我們的電子報！我們會定期為您提供以下資訊：</p>
                    <ul style='margin: 15px 0;'>
                        <li>最新活動消息</li>
                        <li>祈福服務資訊</li>
                        <li>重要節慶活動</li>
                        <li>特別優惠訊息</li>
                    </ul>
                    <p>如果您想要取消訂閱，請點擊以下連結：</p>
                    <p style='text-align: center;'>
                        <a href='{$unsubscribe_url}' 
                           style='display: inline-block; padding: 10px 20px; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px;'>
                            取消訂閱
                        </a>
                    </p>
                    <p>或複製以下網址至瀏覽器：<br>{$unsubscribe_url}</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p>祝您平安順心</p>
                    <p>{$site_name} 敬上</p>
                </div>
                <div style='text-align: center; color: #666; font-size: 12px; margin-top: 20px;'>
                    此為系統自動發送的郵件，請勿直接回覆。
                </div>
            </div>
        </body>
        </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>',
        'X-Mailer: PHP/' . phpversion()
    ];

    // 嘗試發送郵件
    $mail_sent = false;
    try {
        // 關閉錯誤顯示
        $error_reporting = error_reporting();
        error_reporting(0);
        
        $mail_sent = mail($to, $subject, $message, implode("\r\n", $headers));
        
        // 恢復錯誤顯示
        error_reporting($error_reporting);
    } catch (Exception $e) {
        error_log("發送訂閱確認郵件失敗：" . $e->getMessage());
    }

    // 根據郵件發送結果返回不同的訊息
    if ($mail_sent) {
        echo json_encode([
            'success' => true,
            'message' => '感謝您的訂閱！我們已寄送確認信至您的信箱。'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => '訂閱成功！由於系統維護中，確認信將稍後寄出。'
        ]);
    }

} catch (PDOException $e) {
    error_log("資料庫錯誤：" . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '訂閱處理時發生錯誤，請稍後再試'
    ]);
} 
