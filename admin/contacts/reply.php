<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取聯絡表單 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // 獲取聯絡表單詳情
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    $contact = $stmt->fetch();

    if (!$contact) {
        setFlashMessage('error', '找不到指定的聯絡表單記錄');
        header('Location: index.php');
        exit;
    }

    // 處理回覆表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reply_content = trim($_POST['reply_content'] ?? '');
        
        if (empty($reply_content)) {
            setFlashMessage('error', '請輸入回覆內容');
        } else {
            // 更新聯絡表單狀態和回覆內容
            $stmt = $pdo->prepare("
                UPDATE contacts 
                SET status = 'replied',
                    reply_content = ?,
                    replied_by = ?,
                    replied_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reply_content, $_SESSION['admin_id'], $id]);

            // 發送回覆郵件
            $to = $contact['email'];
            $subject = "Re: " . $contact['subject'];
            $message = $reply_content;
            $headers = "From: " . ADMIN_EMAIL . "\r\n";
            $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($to, $subject, $message, $headers)) {
                setFlashMessage('success', '回覆已發送');
            } else {
                setFlashMessage('warning', '回覆已儲存，但郵件發送失敗');
            }

            header('Location: view.php?id=' . $id);
            exit;
        }
    }

} catch (PDOException $e) {
    error_log('Error handling contact reply: ' . $e->getMessage());
    setFlashMessage('error', '處理回覆時發生錯誤');
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>回覆聯絡表單 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php 
        $admin_base = '../';  // 定義後台基礎路徑
        include '../includes/sidebar.php'; 
        ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h2>回覆聯絡表單</h2>
                    <a href="view.php?id=<?php echo $contact['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 返回詳情
                    </a>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <div class="content-body">
                    <div class="reply-form-container">
                        <!-- 原始訊息 -->
                        <div class="original-message">
                            <h3>原始訊息</h3>
                            <div class="message-details">
                                <div class="detail-group">
                                    <label>寄件者</label>
                                    <div><?php echo htmlspecialchars($contact['name']); ?> &lt;<?php echo htmlspecialchars($contact['email']); ?>&gt;</div>
                                </div>
                                <div class="detail-group">
                                    <label>主旨</label>
                                    <div><?php echo htmlspecialchars($contact['subject']); ?></div>
                                </div>
                                <div class="detail-group">
                                    <label>內容</label>
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 回覆表單 -->
                        <form method="post" class="reply-form">
                            <h3>回覆內容</h3>
                            <div class="form-group">
                                <label for="reply_content">回覆訊息</label>
                                <textarea id="reply_content" name="reply_content" class="form-control" rows="10" required></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> 發送回覆
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .reply-form-container {
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .original-message {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .original-message h3,
    .reply-form h3 {
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    .message-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
    }

    .detail-group {
        margin-bottom: 15px;
    }

    .detail-group:last-child {
        margin-bottom: 0;
    }

    .detail-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        color: #666;
    }

    .message-content {
        white-space: pre-wrap;
        padding: 10px;
        background: white;
        border-radius: 3px;
    }

    .reply-form {
        margin-top: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 200px;
    }

    .form-actions {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    </style>
</body>
</html> 