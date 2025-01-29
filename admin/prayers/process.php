<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取祈福請求 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', '無效的祈福請求 ID');
    header('Location: index.php');
    exit;
}

// 獲取祈福請求詳細資訊
try {
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               pt.name as prayer_type_name,
               u.name as user_name,
               u.email as user_email,
               u.phone as user_phone,
               a1.username as processed_by_name
        FROM prayer_requests pr 
        LEFT JOIN prayer_types pt ON pr.prayer_type_id = pt.id
        LEFT JOIN users u ON pr.user_id = u.id
        LEFT JOIN admins a1 ON pr.processed_by = a1.id
        WHERE pr.id = :id
    ");
    
    $stmt->execute([':id' => $id]);
    $prayer = $stmt->fetch();

    if (!$prayer) {
        setFlashMessage('error', '找不到指定的祈福請求');
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching prayer request: ' . $e->getMessage());
    setFlashMessage('error', '獲取祈福請求資訊時發生錯誤');
    header('Location: index.php');
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $errors = [];

    // 驗證輸入
    if (!in_array($status, ['processing', 'completed', 'cancelled'])) {
        $errors[] = '請選擇有效的狀態';
    }

    // 如果沒有錯誤，更新祈福請求
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE prayer_requests 
                SET status = :status,
                    notes = :notes,
                    processed_by = :processed_by,
                    processed_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                ':status' => $status,
                ':notes' => $notes,
                ':processed_by' => $_SESSION['admin_id'],
                ':id' => $id
            ]);

            // 記錄操作日誌
            logAdminAction('處理祈福請求', "處理祈福請求 ID: {$id}, 狀態: {$status}");

            setFlashMessage('success', '祈福請求已更新');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log('Error updating prayer request: ' . $e->getMessage());
            $errors[] = '更新祈福請求時發生錯誤';
        }
    }
}

// 狀態對應中文說明
$status_map = [
    'pending' => '待處理',
    'processing' => '處理中',
    'completed' => '已完成',
    'cancelled' => '已取消'
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 處理祈福請求</title>
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
                    <h2>處理祈福請求</h2>
                    <div class="header-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>

                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 祈福請求詳細資訊 -->
                    <div class="prayer-details">
                        <div class="detail-section">
                            <h3>申請人資訊</h3>
                            <div class="info-group">
                                <label>姓名：</label>
                                <span><?php echo htmlspecialchars($prayer['user_name']); ?></span>
                            </div>
                            <div class="info-group">
                                <label>Email：</label>
                                <span><?php echo htmlspecialchars($prayer['user_email']); ?></span>
                            </div>
                            <?php if ($prayer['user_phone']): ?>
                            <div class="info-group">
                                <label>電話：</label>
                                <span><?php echo htmlspecialchars($prayer['user_phone']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-section">
                            <h3>祈福資訊</h3>
                            <div class="info-group">
                                <label>類型：</label>
                                <span><?php echo htmlspecialchars($prayer['prayer_type_name']); ?></span>
                            </div>
                            <div class="info-group">
                                <label>申請時間：</label>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($prayer['created_at'])); ?></span>
                            </div>
                            <div class="info-group">
                                <label>目前狀態：</label>
                                <span class="status-badge status-<?php echo $prayer['status']; ?>">
                                    <?php echo $status_map[$prayer['status']] ?? $prayer['status']; ?>
                                </span>
                            </div>
                            <?php if ($prayer['processed_by_name']): ?>
                            <div class="info-group">
                                <label>處理者：</label>
                                <span><?php echo htmlspecialchars($prayer['processed_by_name']); ?></span>
                            </div>
                            <div class="info-group">
                                <label>處理時間：</label>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($prayer['processed_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-section">
                            <h3>祈福內容</h3>
                            <div class="prayer-content">
                                <?php echo nl2br(htmlspecialchars($prayer['content'])); ?>
                            </div>
                        </div>

                        <?php if (!empty($prayer['notes'])): ?>
                        <div class="detail-section">
                            <h3>處理備註</h3>
                            <div class="prayer-notes">
                                <?php echo nl2br(htmlspecialchars($prayer['notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 處理表單 -->
                    <form action="process.php?id=<?php echo $id; ?>" method="post" class="form">
                        <div class="form-group">
                            <label for="status">更新狀態</label>
                            <select id="status" name="status" required>
                                <option value="">請選擇狀態</option>
                                <option value="processing" <?php echo $prayer['status'] === 'processing' ? 'selected' : ''; ?>>處理中</option>
                                <option value="completed" <?php echo $prayer['status'] === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                <option value="cancelled" <?php echo $prayer['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">處理備註</label>
                            <textarea id="notes" name="notes" rows="5"><?php echo htmlspecialchars($prayer['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">更新狀態</button>
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