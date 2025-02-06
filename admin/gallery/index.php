<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// 確保用戶已登入且為管理員
adminOnly();

// 處理刪除操作
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        // 獲取圖片資訊
        $stmt = $pdo->prepare("SELECT image_path FROM gallery_images WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $image = $stmt->fetch();

        if ($image) {
            // 刪除實體檔案
            $file_path = '../../' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // 從資料庫中刪除記錄
            $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = ?");
            $stmt->execute([$_POST['id']]);

            $_SESSION['success'] = '相片已成功刪除';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '刪除相片時發生錯誤：' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// 分頁設定
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 搜尋條件
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;

// 建立基本查詢
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($category_id > 0) {
    $where[] = "category_id = ?";
    $params[] = $category_id;
}

if ($status !== -1) {
    $where[] = "status = ?";
    $params[] = $status;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// 獲取總記錄數
$count_sql = "SELECT COUNT(*) FROM gallery_images {$where_clause}";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// 計算總頁數
$total_pages = ceil($total_records / $per_page);

// 獲取相片列表
$sql = "
    SELECT i.*, c.name as category_name 
    FROM gallery_images i 
    LEFT JOIN gallery_categories c ON i.category_id = c.id 
    {$where_clause} 
    ORDER BY i.created_at DESC 
    LIMIT {$per_page} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$images = $stmt->fetchAll();

// 獲取所有分類
$categories = $pdo->query("SELECT * FROM gallery_categories ORDER BY name")->fetchAll();

// 頁面標題
$page_title = '相片管理';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新增相片
                    </a>
                </div>
            </div>

            <?php include '../includes/message.php'; ?>

            <!-- 搜尋表單 -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="搜尋標題或描述..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="category_id">
                                <option value="0">所有分類</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="-1" <?php echo $status === -1 ? 'selected' : ''; ?>>所有狀態</option>
                                <option value="1" <?php echo $status === 1 ? 'selected' : ''; ?>>已發布</option>
                                <option value="0" <?php echo $status === 0 ? 'selected' : ''; ?>>未發布</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> 搜尋
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> 重置
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 相片列表 -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="100">預覽圖</th>
                            <th>標題</th>
                            <th>分類</th>
                            <th>狀態</th>
                            <th>建立時間</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($images)): ?>
                            <tr>
                                <td colspan="6" class="text-center">尚無相片</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($images as $image): ?>
                                <tr>
                                    <td>
                                        <img src="../../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($image['title']); ?>" 
                                             class="img-thumbnail" style="max-height: 50px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($image['title']); ?></td>
                                    <td><?php echo htmlspecialchars($image['category_name']); ?></td>
                                    <td>
                                        <?php if ($image['status']): ?>
                                            <span class="badge bg-success">已發布</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">未發布</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($image['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $image['id']; ?>" 
                                           class="btn btn-sm btn-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="post" class="d-inline" 
                                              onsubmit="return confirm('確定要刪除這張相片嗎？');">
                                            <input type="hidden" name="id" value="<?php echo $image['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 分頁 -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo $search ? "&search={$search}" : ''; ?><?php echo $category_id ? "&category_id={$category_id}" : ''; ?><?php echo $status !== -1 ? "&status={$status}" : ''; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? "&search={$search}" : ''; ?><?php echo $category_id ? "&category_id={$category_id}" : ''; ?><?php echo $status !== -1 ? "&status={$status}" : ''; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? "&search={$search}" : ''; ?><?php echo $category_id ? "&category_id={$category_id}" : ''; ?><?php echo $status !== -1 ? "&status={$status}" : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? "&search={$search}" : ''; ?><?php echo $category_id ? "&category_id={$category_id}" : ''; ?><?php echo $status !== -1 ? "&status={$status}" : ''; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $search ? "&search={$search}" : ''; ?><?php echo $category_id ? "&category_id={$category_id}" : ''; ?><?php echo $status !== -1 ? "&status={$status}" : ''; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 
