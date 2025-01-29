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
        // 先獲取捐款資訊，用於記錄日誌
        $stmt = $pdo->prepare("SELECT donor_name, amount FROM donations WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($donation) {
            // 執行刪除
            $stmt = $pdo->prepare("DELETE FROM donations WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            // 記錄操作日誌
            logAdminAction('刪除捐款', "刪除捐款記錄：{$donation['donor_name']} - {$donation['amount']}元");
            
            // 設置成功消息
            setFlashMessage('success', '捐款記錄已成功刪除！');
        }
        
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = '刪除失敗：' . $e->getMessage();
    }
}

// 定義變數
$errors = [];
$success = '';
$donations = [];
$donation_types = [];
$total_pages = 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

try {
    // 獲取捐款類型列表
    $types_stmt = $pdo->query("SELECT id, name FROM donation_types WHERE status = 1 ORDER BY sort_order");
    $donation_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 構建搜尋條件
    $where = "1=1";
    $params = array();

    if (isset($_GET['donor_name']) && !empty($_GET['donor_name'])) {
        $where .= " AND donor_name LIKE :donor_name";
        $params[':donor_name'] = "%" . $_GET['donor_name'] . "%";
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }

    if (isset($_GET['donation_type_id']) && !empty($_GET['donation_type_id'])) {
        $where .= " AND donation_type_id = :donation_type_id";
        $params[':donation_type_id'] = $_GET['donation_type_id'];
    }

    // 計算總記錄數和分頁
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE $where");
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;

    // 獲取捐款記錄
    $sql = "SELECT d.*, dt.name as type_name, a.username as processor_name 
            FROM donations d 
            LEFT JOIN donation_types dt ON d.donation_type_id = dt.id 
            LEFT JOIN admins a ON d.processed_by = a.id 
            WHERE $where 
            ORDER BY d.donation_date DESC, d.id DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "資料庫錯誤：" . $e->getMessage();
}

// 定義狀態文字和樣式
$status_class = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];

$status_text = [
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
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 捐款管理</title>
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
                    <h2>捐款管理</h2>
                    <div class="content-header-actions">
                        <a href="add.php" class="btn btn-primary">新增捐款</a>
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
                            <div class="form-group col-md-3">
                                <input type="text" name="donor_name" class="form-control" placeholder="捐款人姓名" 
                                       value="<?php echo isset($_GET['donor_name']) ? htmlspecialchars($_GET['donor_name']) : ''; ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <select name="donation_type_id" class="form-select">
                                    <option value="">所有類型</option>
                                    <?php foreach ($donation_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" 
                                            <?php echo (isset($_GET['donation_type_id']) && $_GET['donation_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">所有狀態</option>
                                    <?php foreach ($status_text as $key => $text): ?>
                                        <option value="<?php echo $key; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == $key) ? 'selected' : ''; ?>>
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

                    <!-- 捐款列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>收據編號</th>
                                    <th>捐款人</th>
                                    <th>金額</th>
                                    <th>類型</th>
                                    <th>捐款日期</th>
                                    <th>狀態</th>
                                    <th>處理人員</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($donations)): ?>
                                    <?php foreach ($donations as $donation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($donation['receipt_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                                        <td><?php echo number_format($donation['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($donation['type_name']); ?></td>
                                        <td><?php echo $donation['donation_date']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class[$donation['status']]; ?>">
                                                <?php echo $status_text[$donation['status']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($donation['processor_name'] ?? '-'); ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $donation['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                            <a href="?delete=<?php echo $donation['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('確定要刪除此捐款記錄嗎？');">刪除</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">沒有找到相關記錄</td>
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
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['donor_name']) ? '&donor_name='.urlencode($_GET['donor_name']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['donation_type_id']) ? '&donation_type_id='.$_GET['donation_type_id'] : ''; ?>">
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
