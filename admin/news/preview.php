<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', '未指定要預覽的新聞');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // 獲取新聞詳細資訊
    $stmt = $pdo->prepare("
        SELECT n.*, a.username as author_name 
        FROM news n 
        LEFT JOIN admins a ON n.created_by = a.id 
        WHERE n.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();

    if (!$news) {
        throw new Exception('找不到指定的新聞');
    }

} catch (Exception $e) {
    setFlashMessage('error', '載入新聞時發生錯誤：' . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .news-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .news-meta {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .news-meta span {
            margin-right: 20px;
        }
        .news-content {
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .news-image {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .preview-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .status-label {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .status-draft {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .status-published {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>新聞預覽</h2>
                    <div class="content-header-actions">
                        <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編輯新聞
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="preview-container">
                        <div class="news-header">
                            <h1><?php echo htmlspecialchars($news['title']); ?>
                                <span class="status-label status-<?php echo $news['status']; ?>">
                                    <?php echo $news['status'] === 'published' ? '已發布' : '草稿'; ?>
                                </span>
                            </h1>
                            <div class="news-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($news['author_name'] ?? '未知'); ?></span>
                                <span><i class="fas fa-clock"></i> 發布時間：<?php echo date('Y/m/d H:i', strtotime($news['publish_date'])); ?></span>
                                <span><i class="fas fa-calendar-plus"></i> 建立時間：<?php echo date('Y/m/d H:i', strtotime($news['created_at'])); ?></span>
                                <?php if ($news['updated_at'] !== $news['created_at']): ?>
                                    <span><i class="fas fa-edit"></i> 最後更新：<?php echo date('Y/m/d H:i', strtotime($news['updated_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($news['image'])): ?>
                            <div class="news-image-container">
                                <img src="../../<?php echo htmlspecialchars($news['image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-image">
                            </div>
                        <?php endif; ?>

                        <div class="news-content">
                            <?php echo $news['content']; ?>
                        </div>

                        <div class="preview-actions">
                            <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> 編輯
                            </a>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $news['id']; ?>)">
                                <i class="fas fa-trash"></i> 刪除
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> 返回列表
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('確定要刪除這則新聞嗎？此操作無法復原。')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', '未指定要預覽的新聞');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // 獲取新聞詳細資訊
    $stmt = $pdo->prepare("
        SELECT n.*, a.username as author_name 
        FROM news n 
        LEFT JOIN admins a ON n.created_by = a.id 
        WHERE n.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();

    if (!$news) {
        throw new Exception('找不到指定的新聞');
    }

} catch (Exception $e) {
    setFlashMessage('error', '載入新聞時發生錯誤：' . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .news-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .news-meta {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .news-meta span {
            margin-right: 20px;
        }
        .news-content {
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .news-image {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .preview-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .status-label {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .status-draft {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .status-published {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>新聞預覽</h2>
                    <div class="content-header-actions">
                        <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編輯新聞
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="preview-container">
                        <div class="news-header">
                            <h1><?php echo htmlspecialchars($news['title']); ?>
                                <span class="status-label status-<?php echo $news['status']; ?>">
                                    <?php echo $news['status'] === 'published' ? '已發布' : '草稿'; ?>
                                </span>
                            </h1>
                            <div class="news-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($news['author_name'] ?? '未知'); ?></span>
                                <span><i class="fas fa-clock"></i> 發布時間：<?php echo date('Y/m/d H:i', strtotime($news['publish_date'])); ?></span>
                                <span><i class="fas fa-calendar-plus"></i> 建立時間：<?php echo date('Y/m/d H:i', strtotime($news['created_at'])); ?></span>
                                <?php if ($news['updated_at'] !== $news['created_at']): ?>
                                    <span><i class="fas fa-edit"></i> 最後更新：<?php echo date('Y/m/d H:i', strtotime($news['updated_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($news['image'])): ?>
                            <div class="news-image-container">
                                <img src="../../<?php echo htmlspecialchars($news['image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-image">
                            </div>
                        <?php endif; ?>

                        <div class="news-content">
                            <?php echo $news['content']; ?>
                        </div>

                        <div class="preview-actions">
                            <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> 編輯
                            </a>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $news['id']; ?>)">
                                <i class="fas fa-trash"></i> 刪除
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> 返回列表
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('確定要刪除這則新聞嗎？此操作無法復原。')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
    </script>
</body>
</html>