<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取新聞 ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: news.php');
    exit;
}

try {
    // 獲取新聞詳細資訊
    $stmt = $pdo->prepare("
        SELECT n.*, u.username as author_name, c.name as category_name
        FROM news n 
        LEFT JOIN users u ON n.created_by = u.id 
        LEFT JOIN news_categories c ON n.category_id = c.id
        WHERE n.id = ? AND n.status = 'published'
    ");
    
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    
    if (!$news) {
        header('Location: news.php');
        exit;
    }
    
    // 更新瀏覽次數
    $stmt = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
    $stmt->execute([$id]);
    
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
    <link rel="stylesheet" href="assets/css/news.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .news-detail {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .news-header {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .news-title {
            font-size: 2em;
            color: #333;
            margin: 0 0 15px 0;
        }

        .news-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: #666;
            font-size: 0.9em;
        }

        .news-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .news-image {
            margin: 20px 0;
        }

        .news-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .news-content {
            line-height: 1.8;
            color: #444;
        }

        .news-content p {
            margin-bottom: 1em;
        }

        .news-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background: #4a90e2;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #357abd;
        }

        @media (max-width: 768px) {
            .news-detail {
                padding: 20px;
            }

            .news-title {
                font-size: 1.5em;
            }

            .news-meta {
                gap: 10px;
            }
        }
    </style>
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
                        <h1 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h1>
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
                            <?php if (!empty($news['author_name'])): ?>
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($news['author_name']); ?>
                                </span>
                            <?php endif; ?>
                            <span>
                                <i class="fas fa-eye"></i>
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

                    <div class="news-actions">
                        <a href="news.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i>
                            返回列表
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 