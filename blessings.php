<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // 每頁顯示9個項目，適合網格布局
$offset = ($page - 1) * $limit;

// 獲取選擇的類型
$selected_type = isset($_GET['type']) ? (int)$_GET['type'] : 0;

try {
    // 獲取所有啟用的祈福類型
    $stmt = $pdo->prepare("
        SELECT * FROM blessing_types 
        WHERE status = 1 
        ORDER BY is_featured DESC, sort_order ASC, name ASC
    ");
    $stmt->execute();
    $blessing_types = $stmt->fetchAll();

    // 構建查詢條件
    $where_clause = "WHERE b.status = 'active'";
    $params = [];
    
    if ($selected_type > 0) {
        $where_clause .= " AND b.type_id = :type_id";
        $params[':type_id'] = $selected_type;
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM blessings b $where_clause";
    $stmt = $pdo->prepare($count_sql);
    if ($selected_type > 0) {
        $stmt->bindValue(':type_id', $selected_type);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取祈福服務列表
    $sql = "SELECT b.*, bt.name as type_name
            FROM blessings b
            LEFT JOIN blessing_types bt ON b.type_id = bt.id
            $where_clause
            ORDER BY bt.sort_order ASC, b.created_at DESC
            LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($selected_type > 0) {
        $stmt->bindValue(':type_id', $selected_type);
    }
    $stmt->execute();
    $blessings = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error: ' . $e->getMessage());
    $error = '系統錯誤：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>祈福服務 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .blessings-page {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('/assets/images/temple-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .page-description {
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.1em;
            line-height: 1.6;
        }

        .blessing-types {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .type-link {
            padding: 8px 20px;
            border: 2px solid #ddd;
            border-radius: 20px;
            text-decoration: none;
            color: #666;
            transition: all 0.3s ease;
        }

        .type-link:hover,
        .type-link.active {
            background-color: #8b0000;
            border-color: #8b0000;
            color: white;
        }

        .blessings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .blessings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .blessing-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .blessing-card:hover {
            transform: translateY(-5px);
        }

        .blessing-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .blessing-type-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(139,0,0,0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .blessing-content {
            padding: 20px;
        }

        .blessing-title {
            font-size: 1.3em;
            margin-bottom: 10px;
            color: #333;
        }

        .blessing-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
            height: 4.5em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .blessing-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .blessing-price {
            font-size: 1.2em;
            color: #8b0000;
            font-weight: bold;
        }

        .btn-book {
            display: inline-block;
            padding: 8px 20px;
            background-color: #8b0000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn-book:hover {
            background-color: #660000;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .page-link {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .page-link:hover,
        .page-link.active {
            background-color: #8b0000;
            border-color: #8b0000;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-results i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .blessings-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .page-header {
                padding: 40px 0;
            }

            .page-header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>


    <div class="blessings-page">
        <div class="page-header">
            <div class="container">
                <h1>祈福服務</h1>
                <p class="page-description">
                    誠心祈福，虔誠祝禱，為您和摯愛的家人祈求平安、健康、福祉。
                    我們提供多樣化的祈福服務，協助您傳達對神明的虔誠與祝願。
                </p>
            </div>
        </div>

        <div class="blessings-container">
            <?php if (!empty($blessing_types)): ?>
                <div class="blessing-types">
                    <a href="blessings.php" class="type-link <?php echo $selected_type === 0 ? 'active' : ''; ?>">
                        全部服務
                    </a>
                    <?php foreach ($blessing_types as $type): ?>
                        <a href="blessings.php?type=<?php echo $type['id']; ?>" 
                           class="type-link <?php echo $selected_type === (int)$type['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($type['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($blessings)): ?>
                <div class="no-results">
                    <i class="fas fa-info-circle"></i>
                    <p>目前沒有可預約的祈福服務</p>
                </div>
            <?php else: ?>
                <div class="blessings-grid">
                    <?php foreach ($blessings as $blessing): ?>
                        <div class="blessing-card">
                            <div class="blessing-image" style="background-image: url('<?php echo !empty($blessing['image']) ? $blessing['image'] : 'assets/images/default-blessing.jpg'; ?>')">
                                <span class="blessing-type-tag">
                                    <?php echo htmlspecialchars($blessing['type_name']); ?>
                                </span>
                            </div>
                            <div class="blessing-content">
                                <h3 class="blessing-title">
                                    <?php echo htmlspecialchars($blessing['name']); ?>
                                </h3>
                                <p class="blessing-description">
                                    <?php echo htmlspecialchars($blessing['description']); ?>
                                </p>
                                <div class="blessing-info">
                                    <div class="blessing-price">
                                        NT$ <?php echo number_format($blessing['price']); ?>
                                    </div>
                                    <a href="booking.php?id=<?php echo $blessing['id']; ?>" class="btn-book">
                                        立即預約
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php 
                        // 構建查詢字串
                        $query_string = $selected_type ? "type={$selected_type}&" : '';
                        ?>
                        <?php if ($page > 1): ?>
                            <a href="blessings.php?<?php echo $query_string; ?>page=1" class="page-link">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="blessings.php?<?php echo $query_string; ?>page=<?php echo $page - 1; ?>" class="page-link">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="blessings.php?<?php echo $query_string; ?>page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="blessings.php?<?php echo $query_string; ?>page=<?php echo $page + 1; ?>" class="page-link">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="blessings.php?<?php echo $query_string; ?>page=<?php echo $total_pages; ?>" class="page-link">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html> 
