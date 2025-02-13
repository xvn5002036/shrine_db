<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證並處理表單數據
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $role = $_POST['role'];
        $status = (int)$_POST['status'];

        // 驗證必填欄位
        if (empty($username) || empty($password) || empty($email)) {
            throw new Exception('請填寫所有必填欄位');
        }

        // 驗證用戶名是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM addusers WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('用戶名已被使用');
        }

        // 驗證電子郵件是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM addusers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('電子郵件已被使用');
        }

        // 密碼加密
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 插入資料庫
        $stmt = $pdo->prepare("
            INSERT INTO addusers (username, password, email, phone, first_name, last_name, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $username,
            $hashed_password,
            $email,
            $phone,
            $first_name,
            $last_name,
            $role,
            $status
        ]);

        $_SESSION['success'] = '用戶已成功新增';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = '新增失敗：' . $e->getMessage();
    }
}

$page_title = '新增用戶';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>新增用戶</h1>
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
                            <input type="text" class="form-control" id="username" name="username" required>
                            <div class="invalid-feedback">請輸入用戶名</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">電子郵件 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">請輸入有效的電子郵件地址</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">密碼 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback">請輸入密碼</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">電話</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">名字</label>
                            <input type="text" class="form-control" id="first_name" name="first_name">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">姓氏</label>
                            <input type="text" class="form-control" id="last_name" name="last_name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">角色</label>
                            <select class="form-select" id="role" name="role">
                                <option value="user">一般用戶</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">狀態</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1">啟用</option>
                                <option value="0">停用</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 新增用戶
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

<?php require_once '../templates/footer.php'; ?> 