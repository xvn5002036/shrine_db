<?php
// 處理操作
$action = $_GET['action'] ?? 'list';
$message = '';

// 處理新增/編輯/刪除操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
        case 'edit':
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $status = $_POST['status'] ?? 'draft';
            
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO news (title, content, status, created_by, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$title, $content, $status, $admin['id']]);
                    $message = '新增消息成功！';
                } else {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("
                        UPDATE news 
                        SET title = ?, content = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $content, $status, $id]);
                    $message = '更新消息成功！';
                }
            } catch (PDOException $e) {
                $message = '操作失敗：' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            try {
                $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
                $stmt->execute([$id]);
                $message = '刪除消息成功！';
            } catch (PDOException $e) {
                $message = '刪除失敗：' . $e->getMessage();
            }
            break;
    }
}

// 獲取消息列表
$page = $_GET['p'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // 獲取總數
    $total = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
    
    // 獲取當前頁數據
    $stmt = $pdo->prepare("
        SELECT n.*, u.name as author
        FROM news n
        LEFT JOIN users u ON n.created_by = u.id
        ORDER BY n.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $news_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = '獲取數據失敗：' . $e->getMessage();
    $total = 0;
    $news_list = [];
}

// 計算總頁數
$total_pages = ceil($total / $per_page);
?>

<div class="content-header">
    <h2 class="content-title">
        <?php echo $action === 'list' ? '最新消息管理' : ($action === 'add' ? '新增消息' : '編輯消息'); ?>
    </h2>
    <?php if ($action === 'list'): ?>
    <a href="?page=news&action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> 新增消息
    </a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
<div class="alert alert-info">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- 列表視圖 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>標題</th>
                        <th>作者</th>
                        <th>狀態</th>
                        <th>發布時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news_list as $news): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($news['title']); ?></td>
                        <td><?php echo htmlspecialchars($news['author']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $news['status'] === 'published' ? 'success' : 'warning'; ?>">
                                <?php echo $news['status'] === 'published' ? '已發布' : '草稿'; ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($news['created_at'])); ?></td>
                        <td>
                            <a href="?page=news&action=edit&id=<?php echo $news['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="deleteNews(<?php echo $news['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=news&p=<?php echo $i; ?>" 
               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- 表單視圖 -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="?page=news&action=<?php echo $action; ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">標題</label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo isset($news['title']) ? htmlspecialchars($news['title']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="content">內容</label>
                <textarea id="content" name="content" class="form-control" rows="10" required><?php 
                    echo isset($news['content']) ? htmlspecialchars($news['content']) : ''; 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">狀態</label>
                <select id="status" name="status" class="form-control">
                    <option value="draft" <?php echo isset($news['status']) && $news['status'] === 'draft' ? 'selected' : ''; ?>>
                        草稿
                    </option>
                    <option value="published" <?php echo isset($news['status']) && $news['status'] === 'published' ? 'selected' : ''; ?>>
                        發布
                    </option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 儲存
                </button>
                <a href="?page=news" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 取消
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 0.25em 0.4em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

.badge-success {
    color: #fff;
    background-color: #28a745;
}

.badge-warning {
    color: #212529;
    background-color: #ffc107;
}

.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    text-decoration: none;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border: 1px solid #007bff;
}

.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border: 1px solid #6c757d;
}

.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border: 1px solid #dc3545;
}

.form-group {
    margin-bottom: 1rem;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.pagination {
    display: flex;
    padding-left: 0;
    list-style: none;
    border-radius: 0.25rem;
    margin-top: 20px;
    justify-content: center;
}

.page-link {
    position: relative;
    display: block;
    padding: 0.5rem 0.75rem;
    margin-left: -1px;
    line-height: 1.25;
    color: #007bff;
    background-color: #fff;
    border: 1px solid #dee2e6;
    text-decoration: none;
}

.page-link.active {
    z-index: 1;
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
</style>

<script>
function deleteNews(id) {
    if (confirm('確定要刪除這則消息嗎？此操作無法復原。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=news&action=delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 