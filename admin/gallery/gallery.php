<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// 處理搜尋和篩選
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// 定義相簿分類
$categories = [
    '' => '全部相簿',
    'temple' => '宮廟建築',
    'ceremony' => '祭典活動',
    'collection' => '文物典藏',
    'festival' => '節慶活動'
];

try {
    // 構建查詢條件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_clause .= " AND (a.title LIKE :search OR a.description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($category)) {
        $where_clause .= " AND a.category = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($status)) {
        $where_clause .= " AND a.status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($date_from)) {
        $where_clause .= " AND a.event_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_clause .= " AND a.event_date <= :date_to";
        $params[':date_to'] = $date_to;
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM gallery_albums a $where_clause";
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取相簿列表
    $sql = "
        SELECT a.*, u.username as created_by_name,
               (SELECT file_name FROM gallery_photos WHERE album_id = a.id ORDER BY created_at ASC LIMIT 1) as cover_photo,
               (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id) as photo_count
        FROM gallery_albums a
        LEFT JOIN users u ON a.created_by = u.id
        $where_clause
        ORDER BY a.event_date DESC, a.created_at DESC
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $albums = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Error fetching albums: ' . $e->getMessage());
    $albums = [];
    $total_pages = 0;
}

$page_title = '相簿管理';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1><i class="fas fa-images"></i> 相簿管理</h1>
                <div class="btn-toolbar">
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新增相簿
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 搜尋和篩選表單 -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="搜尋相簿標題或描述" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="category">
                            <?php foreach ($categories as $key => $name): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo $category === $key ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="">所有狀態</option>
                            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已發布</option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" 
                               placeholder="開始日期" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" 
                               placeholder="結束日期" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 相簿統計 -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">總相簿數</h5>
                        <h2 class="mb-0"><?php echo $total_records; ?></h2>
                    </div>
                </div>
            </div>
            <?php
            // 獲取各分類的相簿數量
            foreach ($categories as $cat_key => $cat_name):
                if (empty($cat_key)) continue;
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM gallery_albums WHERE category = ?");
                $stmt->execute([$cat_key]);
                $count = $stmt->fetchColumn();
            ?>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $cat_name; ?></h5>
                        <h2 class="mb-0"><?php echo $count; ?></h2>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 相簿列表 -->
        <div class="row g-4">
            <?php if (empty($albums)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        尚無相簿，請點擊「新增相簿」按鈕建立新的相簿。
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($albums as $album): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 album-card">
                            <div class="card-img-top position-relative" style="height: 200px;">
                                <?php if ($album['cover_photo']): ?>
                                    <img src="/uploads/gallery/<?php echo $album['id']; ?>/<?php echo $album['cover_photo']; ?>" 
                                         class="w-100 h-100 object-fit-cover" 
                                         alt="<?php echo htmlspecialchars($album['title']); ?>">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                        <i class="fas fa-images fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="position-absolute top-0 end-0 p-2">
                                    <span class="badge <?php echo $album['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $album['status'] === 'published' ? '已發布' : '草稿'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($album['title']); ?>">
                                    <?php echo htmlspecialchars($album['title']); ?>
                                </h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo date('Y-m-d', strtotime($album['event_date'])); ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-folder"></i> 
                                        <?php echo $categories[$album['category']]; ?>
                                    </span>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-image"></i> <?php echo $album['photo_count']; ?> 張照片
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($album['created_by_name']); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="btn-group w-100">
                                    <a href="edit.php?id=<?php echo $album['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="編輯">
                                        <i class="fas fa-edit"></i> 編輯
                                    </a>
                                    <a href="delete.php?id=<?php echo $album['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('確定要刪除此相簿嗎？此操作將會永久刪除相簿及其所有照片，且無法復原。')"
                                       title="刪除">
                                        <i class="fas fa-trash"></i> 刪除
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 分頁 -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <?php echo $total_pages; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<style>
.album-card {
    transition: transform 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.album-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.card-img-top img {
    transition: transform 0.3s;
}

.album-card:hover .card-img-top img {
    transform: scale(1.05);
}

.btn-group .btn {
    transition: all 0.2s;
}

.btn-group .btn:hover {
    transform: translateY(-2px);
}
</style>

<?php require_once '../includes/footer.php'; ?> 