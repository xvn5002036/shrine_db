<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 處理刪除請求
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // 先獲取祈福服務資訊，用於記錄日誌
        $stmt = $pdo->prepare("SELECT name FROM prayer_services WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($service) {
            // 執行刪除
            $stmt = $pdo->prepare("DELETE FROM prayer_services WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            // 記錄操作日誌
            logAdminAction('刪除祈福服務', "刪除祈福服務：{$service['name']}");
            
            // 設置成功消息
            setFlashMessage('success', '祈福服務已成功刪除！');
        }
        
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = '刪除失敗：' . $e->getMessage();
    }
}

// 定義變數
$errors = [];
$services = [];
$total_pages = 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

try {
    // 構建搜尋條件
    $where = "1=1";
    $params = array();

    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $where .= " AND (name LIKE :keyword OR description LIKE :keyword)";
        $params[':keyword'] = "%" . $_GET['keyword'] . "%";
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }

    // 計算總記錄數和分頁
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prayer_services WHERE $where");
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;

    // 獲取祈福服務記錄
    $sql = "SELECT * FROM prayer_services 
            WHERE $where 
            ORDER BY sort_order ASC, created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "資料庫錯誤：" . $e->getMessage();
}

// 定義狀態文字和樣式
$status_class = [
    'active' => 'success',
    'inactive' => 'warning'
];

$status_text = [
    'active' => '啟用',
    'inactive' => '停用'
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 祈福服務管理</title>
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
                    <h2>祈福服務管理</h2>
                    <div class="content-header-actions">
                        <a href="add.php" class="btn btn-primary">新增祈福服務</a>
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

                    <!-- 搜尋表單 -->
                    <form method="GET" class="search-form">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <input type="text" name="keyword" class="form-control" 
                                       placeholder="搜尋服務名稱或描述" 
                                       value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">所有狀態</option>
                                    <?php foreach ($status_text as $key => $text): ?>
                                        <option value="<?php echo $key; ?>" 
                                            <?php echo (isset($_GET['status']) && $_GET['status'] == $key) ? 'selected' : ''; ?>>
                                            <?php echo $text; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <button type="submit" class="btn btn-primary">搜尋</button>
                                <a href="index.php" class="btn btn-secondary">重置</a>
                            </div>
                        </div>
                    </form>

                    <!-- 服務列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>排序</th>
                                    <th>服務名稱</th>
                                    <th>價格</th>
                                    <th>狀態</th>
                                    <th>建立時間</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['sort_order']); ?></td>
                                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                                        <td>NT$ <?php echo number_format($service['price']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class[$service['status']]; ?>">
                                                <?php echo $status_text[$service['status']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($service['created_at'])); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                            <a href="?delete=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('確定要刪除此祈福服務嗎？');">刪除</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">沒有找到相關記錄</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁 -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="pagination-container">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['keyword']) ? '&keyword='.urlencode($_GET['keyword']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 
