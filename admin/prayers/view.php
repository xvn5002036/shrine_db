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
        LEFT JOIN prayer_types pt ON pr.type_id = pt.id
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
    <title><?php echo SITE_NAME; ?> - 查看祈福請求</title>
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
                    <h2>查看祈福請求</h2>
                    <div class="header-actions">
                        <?php if ($prayer['status'] === 'pending'): ?>
                            <a href="process.php?id=<?php echo $prayer['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-tasks"></i> 處理請求
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>

                <div class="content-card">
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
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 
