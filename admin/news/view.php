<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取新聞 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', '無效的新聞 ID');
    header('Location: index.php');
    exit;
}

// 獲取新聞詳細資訊
try {
    $stmt = $pdo->prepare("
        SELECT n.*, 
               nc.name as category_name,
               a.username as created_by_name,
               a2.username as updated_by_name
        FROM news n
        LEFT JOIN news_categories nc ON n.category_id = nc.id
        LEFT JOIN admins a ON n.created_by = a.id
        LEFT JOIN admins a2 ON n.updated_by = a2.id
        WHERE n.id = :id
    ");
    
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();

    if (!$news) {
        setFlashMessage('error', '找不到指定的新聞');
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching news: ' . $e->getMessage());
    setFlashMessage('error', '獲取新聞資訊時發生錯誤');
    header('Location: index.php');
    exit;
}

// 獲取狀態對應的中文說明
$status_map = [
    'draft' => '草稿',
    'published' => '已發布',
    'archived' => '已封存'
];
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 查看新聞</title>
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
                    <h2>查看新聞</h2>
                    <div class="header-actions">
                        <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編輯
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="news-view">
                        <div class="news-header">
                            <h1 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                            <div class="news-meta">
                                <span class="news-category">
                                    <i class="fas fa-folder"></i>
                                    <?php echo htmlspecialchars($news['category_name'] ?? '未分類'); ?>
                                </span>
                                <span class="news-status status-<?php echo $news['status']; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo $status_map[$news['status']] ?? $news['status']; ?>
                                </span>
                                <span class="news-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    發布時間：<?php echo date('Y-m-d H:i', strtotime($news['publish_date'])); ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($news['image'])): ?>
                        <div class="news-image">
                            <img src="../../<?php echo htmlspecialchars($news['image']); ?>" alt="新聞圖片">
                        </div>
                        <?php endif; ?>

                        <div class="news-content">
                            <?php echo $news['content']; ?>
                        </div>

                        <div class="news-info">
                            <div class="info-group">
                                <label>建立者：</label>
                                <span><?php echo htmlspecialchars($news['created_by_name'] ?? '系統'); ?></span>
                            </div>
                            <div class="info-group">
                                <label>建立時間：</label>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($news['created_at'])); ?></span>
                            </div>
                            <?php if ($news['updated_by']): ?>
                            <div class="info-group">
                                <label>最後更新者：</label>
                                <span><?php echo htmlspecialchars($news['updated_by_name'] ?? '系統'); ?></span>
                            </div>
                            <div class="info-group">
                                <label>更新時間：</label>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($news['updated_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 