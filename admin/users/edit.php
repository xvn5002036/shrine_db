<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的用戶ID';
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證並處理表單數據
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $role = $_POST['role'];
        $status = (int)$_POST['status'];
        $new_password = trim($_POST['password']);

        // 驗證必填欄位
        if (empty($username) || empty($email)) {
            throw new Exception('請填寫所有必填欄位');
        }

        // 驗證用戶名是否已被其他用戶使用
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM addusers WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('用戶名已被使用');
        }

        // 驗證電子郵件是否已被其他用戶使用
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM addusers WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('電子郵件已被使用');
        }

        // 準備更新語句
        $sql = "UPDATE addusers SET 
                username = ?, 
                email = ?, 
                phone = ?, 
                first_name = ?, 
                last_name = ?, 
                role = ?, 
                status = ?";
        $params = [
            $username,
            $email,
            $phone,
            $first_name,
            $last_name,
            $role,
            $status
        ];

        // 如果有提供新密碼，則更新密碼
        if (!empty($new_password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        // 執行更新
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['success'] = '用戶資料已成功更新';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = '更新失敗：' . $e->getMessage();
    }
}

try {
    // 獲取用戶資料
    $stmt = $pdo->prepare("SELECT * FROM addusers WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = '找不到指定的用戶';
        header('Location: index.php');
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = '資料庫錯誤：' . $e->getMessage();
    header('Location: index.php');
    exit();
}

$page_title = '編輯用戶';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>編輯用戶</h1>
                <div class="btn-toolbar">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">用戶名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            <div class="invalid-feedback">請輸入用戶名</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">電子郵件 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <div class="invalid-feedback">請輸入有效的電子郵件地址</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">新密碼</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">如果不修改密碼，請留空</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">電話</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">名字</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">姓氏</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">角色</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>一般用戶</option>
                                <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理員</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">狀態</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1" <?php echo $user['status'] ? 'selected' : ''; ?>>啟用</option>
                                <option value="0" <?php echo !$user['status'] ? 'selected' : ''; ?>>停用</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 更新用戶
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 表單驗證
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?> 