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
            $description = $_POST['description'] ?? '';
            $event_date = $_POST['event_date'] ?? '';
            $location = $_POST['location'] ?? '';
            $status = $_POST['status'] ?? 'upcoming';
            
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, event_date, location, status, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$title, $description, $event_date, $location, $status, $admin['id']]);
                    $message = '新增活動成功！';
                } else {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("
                        UPDATE events 
                        SET title = ?, description = ?, event_date = ?, location = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $event_date, $location, $status, $id]);
                    $message = '更新活動成功！';
                }
            } catch (PDOException $e) {
                $message = '操作失敗：' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            try {
                $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$id]);
                $message = '刪除活動成功！';
            } catch (PDOException $e) {
                $message = '刪除失敗：' . $e->getMessage();
            }
            break;
    }
}

// 獲取活動列表
$page = $_GET['p'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // 獲取總數
    $total = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    
    // 獲取當前頁數據
    $stmt = $pdo->prepare("
        SELECT e.*, u.name as organizer
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        ORDER BY e.event_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $events_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = '獲取數據失敗：' . $e->getMessage();
    $total = 0;
    $events_list = [];
}

// 計算總頁數
$total_pages = ceil($total / $per_page);
?>

<div class="content-header">
    <h2 class="content-title">
        <?php echo $action === 'list' ? '活動管理' : ($action === 'add' ? '新增活動' : '編輯活動'); ?>
    </h2>
    <?php if ($action === 'list'): ?>
    <a href="?page=events&action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> 新增活動
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
                        <th>活動日期</th>
                        <th>地點</th>
                        <th>狀態</th>
                        <th>主辦人</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events_list as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($event['event_date'])); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $event['status'] === 'completed' ? 'success' : 
                                    ($event['status'] === 'cancelled' ? 'danger' : 'primary'); 
                            ?>">
                                <?php 
                                echo $event['status'] === 'completed' ? '已結束' : 
                                    ($event['status'] === 'cancelled' ? '已取消' : '即將舉行'); 
                                ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($event['organizer']); ?></td>
                        <td>
                            <a href="?page=events&action=edit&id=<?php echo $event['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="deleteEvent(<?php echo $event['id']; ?>)">
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
            <a href="?page=events&p=<?php echo $i; ?>" 
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
        <form method="POST" action="?page=events&action=<?php echo $action; ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">活動名稱</label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo isset($event['title']) ? htmlspecialchars($event['title']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="description">活動描述</label>
                <textarea id="description" name="description" class="form-control" rows="10" required><?php 
                    echo isset($event['description']) ? htmlspecialchars($event['description']) : ''; 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="event_date">活動日期時間</label>
                <input type="datetime-local" id="event_date" name="event_date" class="form-control" required
                       value="<?php echo isset($event['event_date']) ? date('Y-m-d\TH:i', strtotime($event['event_date'])) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="location">活動地點</label>
                <input type="text" id="location" name="location" class="form-control" required
                       value="<?php echo isset($event['location']) ? htmlspecialchars($event['location']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="status">活動狀態</label>
                <select id="status" name="status" class="form-control">
                    <option value="upcoming" <?php echo isset($event['status']) && $event['status'] === 'upcoming' ? 'selected' : ''; ?>>
                        即將舉行
                    </option>
                    <option value="completed" <?php echo isset($event['status']) && $event['status'] === 'completed' ? 'selected' : ''; ?>>
                        已結束
                    </option>
                    <option value="cancelled" <?php echo isset($event['status']) && $event['status'] === 'cancelled' ? 'selected' : ''; ?>>
                        已取消
                    </option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 儲存
                </button>
                <a href="?page=events" class="btn btn-secondary">
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

.badge-primary {
    color: #fff;
    background-color: #007bff;
}

.badge-success {
    color: #fff;
    background-color: #28a745;
}

.badge-danger {
    color: #fff;
    background-color: #dc3545;
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
function deleteEvent(id) {
    if (confirm('確定要刪除這個活動嗎？此操作無法復原。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=events&action=delete';
        
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