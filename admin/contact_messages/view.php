<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取訊息 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // 獲取訊息詳情
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as replied_by_name 
        FROM contact_messages m 
        LEFT JOIN users u ON m.replied_by = u.id 
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $message = $stmt->fetch();

    if (!$message) {
        setFlashMessage('error', '找不到指定的訊息');
        header('Location: index.php');
        exit;
    }

    // 如果訊息是未讀狀態，更新為已讀
    if ($message['status'] === 'unread') {
        $stmt = $pdo->prepare("
            UPDATE contact_messages 
            SET status = 'read', 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $message['status'] = 'read';
    }

} catch (PDOException $e) {
    error_log('Error viewing contact message: ' . $e->getMessage());
    setFlashMessage('error', '查看訊息時發生錯誤');
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看訊息 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php 
        $admin_base = '../';
        include '../includes/sidebar.php'; 
        ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h2>查看訊息</h2>
                    <div class="header-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                        <a href="reply.php?id=<?php echo $message['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-reply"></i> 回覆訊息
                        </a>
                    </div>
                </div>
                
                <?php displayFlashMessages(); ?>
                
                <div class="content-body">
                    <div class="message-detail">
                        <div class="message-header">
                            <div class="message-meta">
                                <div class="meta-item">
                                    <label>寄件者：</label>
                                    <span><?php echo htmlspecialchars($message['name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>電子郵件：</label>
                                    <span><?php echo htmlspecialchars($message['email']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>主旨：</label>
                                    <span><?php echo htmlspecialchars($message['subject']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>建立時間：</label>
                                    <span><?php echo date('Y-m-d H:i:s', strtotime($message['created_at'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>狀態：</label>
                                    <span class="status-badge status-<?php echo $message['status']; ?>">
                                        <?php
                                        $status_text = [
                                            'unread' => '未讀',
                                            'read' => '已讀',
                                            'replied' => '已回覆',
                                            'archived' => '已封存'
                                        ];
                                        echo $status_text[$message['status']] ?? $message['status'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-content">
                            <h3>訊息內容</h3>
                            <div class="content-text">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($message['reply_content']): ?>
                        <div class="message-reply">
                            <h3>回覆內容</h3>
                            <div class="reply-meta">
                                <span>回覆者：<?php echo htmlspecialchars($message['replied_by_name']); ?></span>
                                <span>回覆時間：<?php echo date('Y-m-d H:i:s', strtotime($message['replied_at'])); ?></span>
                            </div>
                            <div class="reply-content">
                                <?php echo nl2br(htmlspecialchars($message['reply_content'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .message-detail {
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .message-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .meta-item label {
        font-weight: bold;
        color: #666;
        margin-right: 10px;
    }

    .message-content,
    .message-reply {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .message-content h3,
    .message-reply h3 {
        margin-bottom: 15px;
        color: var(--primary-color);
    }

    .content-text,
    .reply-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        white-space: pre-wrap;
    }

    .reply-meta {
        margin-bottom: 10px;
        color: #666;
    }

    .reply-meta span {
        margin-right: 20px;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.9em;
    }

    .status-unread {
        background: #ffc107;
        color: #000;
    }

    .status-read {
        background: #17a2b8;
        color: #fff;
    }

    .status-replied {
        background: #28a745;
        color: #fff;
    }

    .status-archived {
        background: #6c757d;
        color: #fff;
    }
    </style>
</body>
</html> 