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
        // 先獲取會員資訊，用於記錄日誌
        $stmt = $pdo->prepare("SELECT name, email FROM members WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            // 執行刪除
            $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            // 記錄操作日誌
            logAdminAction('刪除會員', "刪除會員：{$member['name']} ({$member['email']})");
            
            // 設置成功消息
            setFlashMessage('success', '會員已成功刪除！');
        }
        
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = '刪除失敗：' . $e->getMessage();
    }
}

// 定義變數
$errors = [];
$members = [];
$total_pages = 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

try {
    // 構建搜尋條件
    $where = "1=1";
    $params = array();

    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $where .= " AND (name LIKE :keyword OR email LIKE :keyword OR phone LIKE :keyword)";
        $params[':keyword'] = "%" . $_GET['keyword'] . "%";
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where .= " AND status = :status";
        $params[':status'] = $_GET['status'];
    }

    // 計算總記錄數和分頁
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE $where");
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;

    // 獲取會員記錄
    $sql = "SELECT * FROM members 
            WHERE $where 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = "資料庫錯誤：" . $e->getMessage();
}

// 定義狀態文字和樣式
$status_class = [
    'active' => 'success',
    'inactive' => 'warning',
    'blocked' => 'danger'
];

$status_text = [
    'active' => '正常',
    'inactive' => '未啟用',
    'blocked' => '已封鎖'
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 會員管理</title>
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
                    <h2>會員管理</h2>
                    <div class="content-header-actions">
                        <a href="add.php" class="btn btn-primary">新增會員</a>
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
                                       placeholder="搜尋會員姓名、Email或電話" 
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

                    <!-- 會員列表 -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>姓名</th>
                                    <th>Email</th>
                                    <th>電話</th>
                                    <th>狀態</th>
                                    <th>註冊時間</th>
                                    <th>最後登入</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['id']); ?></td>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class[$member['status']]; ?>">
                                                <?php echo $status_text[$member['status']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($member['created_at'])); ?></td>
                                        <td><?php echo $member['last_login'] ? date('Y-m-d H:i', strtotime($member['last_login'])) : '-'; ?></td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                            <a href="?delete=<?php echo $member['id']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('確定要刪除此會員嗎？此操作無法復原！');">刪除</a>
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