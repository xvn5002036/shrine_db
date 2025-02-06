<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 確保用戶已登入且有權限
checkAdminAuth();

// 初始化變數
$types = [];
$error = null;
$success = null;

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證必填欄位
        $required_fields = ['type_id', 'name', 'description'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception('請填寫所有必填欄位');
            }
        }

        // 準備數據
        $data = [
            'type_id' => $_POST['type_id'],
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => !empty($_POST['price']) ? $_POST['price'] : null,
            'duration' => $_POST['duration'],
            'sort_order' => !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0,
            'booking_required' => isset($_POST['booking_required']) ? 1 : 0,
            'max_participants' => !empty($_POST['max_participants']) ? $_POST['max_participants'] : null,
            'notice' => $_POST['notice'],
            'created_by' => $_SESSION['user_id'],
            'status' => 1
        ];

        // 處理圖片上傳
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = '../../uploads/services/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('只允許上傳 JPG、JPEG、PNG 或 GIF 格式的圖片');
            }

            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $data['image'] = 'uploads/services/' . $new_filename;
            } else {
                throw new Exception('圖片上傳失敗');
            }
        }

        // 生成 slug
        $data['slug'] = generateSlug($data['name']);

        // 插入數據
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = $pdo->prepare("INSERT INTO services ({$columns}) VALUES ({$values})");
        if ($stmt->execute(array_values($data))) {
            $success = '服務項目新增成功';
            // 清空表單
            $_POST = [];
        } else {
            throw new Exception('服務項目新增失敗');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 獲取服務類型
try {
    $stmt = $pdo->query("SELECT * FROM service_types WHERE status = 1 ORDER BY sort_order");
    if ($stmt) {
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '無法獲取服務類型';
}

// 頁面標題
$page_title = '新增服務';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="type_id" class="form-label">服務類型 <span class="text-danger">*</span></label>
                            <select name="type_id" id="type_id" class="form-select" required>
                                <option value="">請選擇服務類型</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"
                                            <?php echo isset($_POST['type_id']) && $_POST['type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">服務名稱 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">價格</label>
                            <div class="input-group">
                                <span class="input-group-text">NT$</span>
                                <input type="number" class="form-control" id="price" name="price" 
                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="duration" class="form-label">服務時長</label>
                            <input type="text" class="form-control" id="duration" name="duration" 
                                   value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : ''; ?>"
                                   placeholder="例如：30分鐘">
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : '0'; ?>">
                            <div class="form-text">數字越小越靠前</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image" class="form-label">服務圖片</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">建議尺寸：800x600 像素，檔案大小不超過 2MB</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="booking_required" 
                                       name="booking_required" value="1"
                                       <?php echo isset($_POST['booking_required']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="booking_required">
                                    需要預約
                                </label>
                            </div>
                        </div>

                        <div class="mb-3 booking-options" style="display: none;">
                            <label for="max_participants" class="form-label">最大參與人數</label>
                            <input type="number" class="form-control" id="max_participants" 
                                   name="max_participants" min="1"
                                   value="<?php echo isset($_POST['max_participants']) ? htmlspecialchars($_POST['max_participants']) : ''; ?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="description" class="form-label">服務說明 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="notice" class="form-label">注意事項</label>
                            <textarea class="form-control" id="notice" name="notice" 
                                      rows="3"><?php echo isset($_POST['notice']) ? htmlspecialchars($_POST['notice']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingRequired = document.getElementById('booking_required');
    const bookingOptions = document.querySelector('.booking-options');

    function toggleBookingOptions() {
        bookingOptions.style.display = bookingRequired.checked ? 'block' : 'none';
    }

    bookingRequired.addEventListener('change', toggleBookingOptions);
    toggleBookingOptions();
});
</script>

<?php require_once '../includes/footer.php'; ?> 