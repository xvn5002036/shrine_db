<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 檢查是否有提供 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: news.php');
    exit;
}

$id = (int)$_GET['id'];

// 獲取新聞詳細資訊
try {
    $sql = "
        SELECT n.*, a.username as author_name 
        FROM news n 
        LEFT JOIN admins a ON n.created_by = a.id 
        WHERE n.id = :id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $news = $stmt->fetch();

    if (!$news) {
        header('Location: news.php');
        exit;
    }

    // 更新瀏覽次數
    $update_sql = "UPDATE news SET views = views + 1 WHERE id = :id";
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([':id' => $id]);

} catch (PDOException $e) {
    error_log('Error fetching news detail: ' . $e->getMessage());
    header('Location: news.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/news-detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($news['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($news['content']), 0, 100)); ?>">
    <?php if (!empty($news['image'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($news['image']); ?>">
    <?php endif; ?>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="content-header">
                <div class="breadcrumb">
                    <a href="index.php">首頁</a>
                    <i class="fas fa-angle-right"></i>
                    <a href="news.php">最新消息</a>
                    <i class="fas fa-angle-right"></i>
                    <span><?php echo htmlspecialchars($news['title']); ?></span>
                </div>
            </div>

            <div class="content-body">
                <article class="news-detail">
                    <header class="news-header">
                        <h1><?php echo htmlspecialchars($news['title']); ?></h1>
                        <div class="news-meta">
                            <span>
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('Y-m-d', strtotime($news['created_at'])); ?>
                            </span>
                            <?php if (!empty($news['category_name'])): ?>
                                <span>
                                    <i class="fas fa-folder"></i>
                                    <?php echo htmlspecialchars($news['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <span>
                                <i class="far fa-eye"></i>
                                <?php echo number_format($news['views']); ?> 次瀏覽
                            </span>
                        </div>
                    </header>

                    <?php if (!empty($news['image'])): ?>
                        <div class="news-image">
                            <img src="<?php echo htmlspecialchars($news['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($news['title']); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="news-content">
                        <?php echo $news['content']; ?>
                    </div>

                    <footer class="news-footer">
                        <div class="share-buttons">
                            <span>分享：</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" 
                               target="_blank" class="share-btn facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://social-plugins.line.me/lineit/share?url=<?php echo urlencode($current_url); ?>" 
                               target="_blank" class="share-btn line">
                                <i class="fab fa-line"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($news['title']); ?>" 
                               target="_blank" class="share-btn twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>

                        <?php if (!empty($prev_news) || !empty($next_news)): ?>
                            <div class="navigation-links">
                                <?php if (!empty($prev_news)): ?>
                                    <a href="news_detail.php?id=<?php echo $prev_news['id']; ?>" class="nav-link prev">
                                        <i class="fas fa-angle-left"></i>
                                        <?php echo htmlspecialchars($prev_news['title']); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($next_news)): ?>
                                    <a href="news_detail.php?id=<?php echo $next_news['id']; ?>" class="nav-link next">
                                        <?php echo htmlspecialchars($next_news['title']); ?>
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </footer>
                </article>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 