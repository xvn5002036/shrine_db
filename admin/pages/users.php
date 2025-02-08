<?php
// 處理操作
$action = $_GET['action'] ?? 'list';
$message = '';

// 處理新增/編輯/刪除操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add':
        case 'edit':
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';
            $password = $_POST['password'] ?? '';
            
            try {
                if ($action === 'add') {
                    // 檢查郵箱是否已存在
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('該郵箱已被註冊');
                    }
                    
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role, status, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $email, $password_hash, $role, $status]);
                    $message = '新增用戶成功！';
                } else {
                    $id = $_POST['id'] ?? 0;
                    // 檢查郵箱是否已被其他用戶使用
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('該郵箱已被其他用戶使用');
                    }
                    
                    if ($password) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = ?, email = ?, password = ?, role = ?, status = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $email, $password_hash, $role, $status, $id]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = ?, email = ?, role = ?, status = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $email, $role, $status, $id]);
                    }
                    $message = '更新用戶成功！';
                }
            } catch (Exception $e) {
                $message = '操作失敗：' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            try {
                // 檢查是否為最後一個管理員
                if ($id == $admin['id']) {
                    throw new Exception('不能刪除當前登錄的管理員帳號');
                }
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM users 
                    WHERE role = 'admin' AND status = 'active' AND id != ?
                ");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('系統必須保留至少一個管理員帳號');
                }
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $message = '刪除用戶成功！';
            } catch (Exception $e) {
                $message = '刪除失敗：' . $e->getMessage();
            }
            break;
    }
}

// 獲取用戶列表
$page = $_GET['p'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // 獲取總數
    $total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // 獲取當前頁數據
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, status, created_at, last_login
        FROM users
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $users_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = '獲取數據失敗：' . $e->getMessage();
    $total = 0;
    $users_list = [];
}

// 計算總頁數
$total_pages = ceil($total / $per_page);

// 如果是編輯，獲取用戶信息
if ($action === 'edit') {
    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            $message = '用戶不存在';
            $action = 'list';
        }
    } catch (PDOException $e) {
        $message = '獲取用戶信息失敗：' . $e->getMessage();
        $action = 'list';
    }
}
?>

<div class="content-header">
    <h2 class="content-title">
        <?php echo $action === 'list' ? '用戶管理' : ($action === 'add' ? '新增用戶' : '編輯用戶'); ?>
    </h2>
    <?php if ($action === 'list'): ?>
    <a href="?page=users&action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> 新增用戶
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
                        <th>姓名</th>
                        <th>郵箱</th>
                        <th>角色</th>
                        <th>狀態</th>
                        <th>註冊時間</th>
                        <th>最後登錄</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                <?php echo $user['role'] === 'admin' ? '管理員' : '用戶'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo $user['status'] === 'active' ? '正常' : '禁用'; ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '從未登錄'; ?>
                        </td>
                        <td>
                            <a href="?page=users&action=edit&id=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $admin['id']): ?>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="deleteUser(<?php echo $user['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=users&p=<?php echo $i; ?>" 
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
        <form method="POST" action="?page=users&action=<?php echo $action; ?>" class="needs-validation" novalidate>
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">姓名</label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>">
                <div class="invalid-feedback">請輸入姓名</div>
            </div>
            
            <div class="form-group">
                <label for="email">郵箱</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
                <div class="invalid-feedback">請輸入有效的郵箱地址</div>
            </div>
            
            <div class="form-group">
                <label for="password">
                    密碼
                    <?php if ($action === 'edit'): ?>
                    <small class="text-muted">（留空表示不修改）</small>
                    <?php endif; ?>
                </label>
                <input type="password" id="password" name="password" class="form-control"
                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                <div class="invalid-feedback">
                    <?php echo $action === 'add' ? '請輸入密碼' : '如需修改密碼請輸入新密碼'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="role">角色</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user" <?php echo isset($user['role']) && $user['role'] === 'user' ? 'selected' : ''; ?>>
                        用戶
                    </option>
                    <option value="admin" <?php echo isset($user['role']) && $user['role'] === 'admin' ? 'selected' : ''; ?>>
                        管理員
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">狀態</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active" <?php echo isset($user['status']) && $user['status'] === 'active' ? 'selected' : ''; ?>>
                        正常
                    </option>
                    <option value="disabled" <?php echo isset($user['status']) && $user['status'] === 'disabled' ? 'selected' : ''; ?>>
                        禁用
                    </option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 儲存
                </button>
                <a href="?page=users" class="btn btn-secondary">
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

.badge-info {
    color: #fff;
    background-color: #17a2b8;
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

.form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 80%;
    color: #dc3545;
}

.was-validated .form-control:invalid,
.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.was-validated .form-control:invalid:focus,
.form-control.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.was-validated .form-control:invalid ~ .invalid-feedback,
.form-control.is-invalid ~ .invalid-feedback {
    display: block;
}

.text-muted {
    color: #6c757d !important;
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
// 表單驗證
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

function deleteUser(id) {
    if (confirm('確定要刪除這個用戶嗎？此操作無法復原。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=users&action=delete';
        
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