<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證並處理表單數據
        $name = trim($_POST['name']);
        $type_id = (int)$_POST['type_id'];
        $price = (float)$_POST['price'];
        $duration = trim($_POST['duration']);
        $description = trim($_POST['description']);
        $sort_order = (int)$_POST['sort_order'];
        $status = $_POST['status'];

        if (empty($name) || empty($duration)) {
            throw new Exception('請填寫所有必填欄位');
        }

        // 插入資料庫
        $stmt = $pdo->prepare("
            INSERT INTO blessings (name, type_id, price, duration, description, 
                                 sort_order, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $name, $type_id, $price, $duration, 
            $description, $sort_order, $status
        ]);

        $_SESSION['success'] = '祈福服務已成功新增';
        header('Location: services.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = '新增失敗：' . $e->getMessage();
    }
}

try {
    // 獲取所有祈福類型
    $stmt = $pdo->query("SELECT * FROM blessing_types WHERE status = 1 ORDER BY sort_order ASC");
    $blessing_types = $stmt->fetchAll();

    // 獲取目前最大的排序值
    $stmt = $pdo->query("SELECT MAX(sort_order) FROM blessings");
    $max_sort_order = $stmt->fetchColumn();
    $next_sort_order = $max_sort_order ? $max_sort_order + 10 : 10;

} catch (PDOException $e) {
    $_SESSION['error'] = '資料庫錯誤：' . $e->getMessage();
    header('Location: services.php');
    exit();
}

$page_title = '新增祈福服務';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>新增祈福服務</h1>
                <div class="btn-toolbar">
                    <a href="services.php" class="btn btn-secondary">
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
                    <div class="mb-3">
                        <label for="name" class="form-label">服務名稱 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">請輸入服務名稱</div>
                    </div>

                    <div class="mb-3">
                        <label for="type_id" class="form-label">服務類型 <span class="text-danger">*</span></label>
                        <select class="form-select" id="type_id" name="type_id" required>
                            <option value="">請選擇類型</option>
                            <?php foreach ($blessing_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">請選擇服務類型</div>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">價格 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">NT$</span>
                            <input type="number" class="form-control" id="price" name="price" 
                                   min="0" step="1" required>
                            <div class="invalid-feedback">請輸入有效的價格</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="duration" class="form-label">服務期間 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="duration" name="duration" required>
                        <div class="invalid-feedback">請輸入服務期間</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">服務說明</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="sort_order" class="form-label">排序</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="<?php echo $next_sort_order; ?>" min="0" step="10">
                        <div class="form-text">數字越小越靠前顯示</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">狀態</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">啟用</option>
                            <option value="inactive">停用</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 新增服務
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
