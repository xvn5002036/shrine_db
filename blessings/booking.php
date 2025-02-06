<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// 檢查是否已登入
if (!isLoggedIn()) {
    $_SESSION['error'] = '請先登入後再進行預約';
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../login.php');
    exit;
}

// 獲取祈福類型
$type_slug = $_GET['type'] ?? '';
if (empty($type_slug)) {
    $_SESSION['error'] = '請選擇祈福項目';
    header('Location: index.php');
    exit;
}

// 查詢祈福類型資訊
$stmt = $pdo->prepare("SELECT * FROM blessing_types WHERE slug = ? AND status = 1");
$stmt->execute([$type_slug]);
$type = $stmt->fetch();

if (!$type) {
    $_SESSION['error'] = '找不到指定的祈福項目';
    header('Location: index.php');
    exit;
}

// 頁面標題
$page_title = '預約' . $type['name'];
$current_page = 'blessings';
require_once '../templates/header.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證輸入
        if (empty($_POST['recipient_name']) || empty($_POST['blessing_date'])) {
            throw new Exception('請填寫必要欄位');
        }

        // 檢查日期是否有效
        $blessing_date = new DateTime($_POST['blessing_date']);
        $today = new DateTime();
        if ($blessing_date < $today) {
            throw new Exception('預約日期不能早於今天');
        }

        // 檢查是否為特殊日期
        $stmt = $pdo->prepare("
            SELECT * FROM blessing_special_dates 
            WHERE date = ? AND (type_id IS NULL OR type_id = ?)
        ");
        $stmt->execute([$_POST['blessing_date'], $type['id']]);
        $special_date = $stmt->fetch();

        if ($special_date && $special_date['is_closed']) {
            throw new Exception('該日期不開放預約');
        }

        // 檢查當日預約數量
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM blessings 
            WHERE type_id = ? AND blessing_date = ? AND blessing_status != 'cancelled'
        ");
        $stmt->execute([$type['id'], $_POST['blessing_date']]);
        $daily_bookings = $stmt->fetchColumn();

        if ($daily_bookings >= $type['max_daily_slots']) {
            throw new Exception('該日期預約已滿');
        }

        // 開始交易
        $pdo->beginTransaction();

        // 建立祈福預約
        $stmt = $pdo->prepare("
            INSERT INTO blessings (
                type_id, user_id, recipient_name, recipient_birthdate,
                recipient_gender, recipient_phone, recipient_address,
                recipient_email, blessing_date, special_requests, 
                emergency_contact, emergency_phone,
                amount, payment_status, blessing_status,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, 'pending', 'pending',
                NOW(), NOW()
            )
        ");

        $stmt->execute([
            $type['id'],
            $_SESSION['user_id'],
            $_POST['recipient_name'],
            $_POST['recipient_birthdate'] ?: null,
            $_POST['recipient_gender'] ?: null,
            $_POST['recipient_phone'] ?: null,
            $_POST['recipient_address'] ?: null,
            $_POST['recipient_email'] ?: null,
            $_POST['blessing_date'],
            $_POST['special_requests'] ?: null,
            $_POST['emergency_contact'] ?: null,
            $_POST['emergency_phone'] ?: null,
            $type['price']
        ]);

        $blessing_id = $pdo->lastInsertId();

        // 提交交易
        $pdo->commit();

        $_SESSION['success'] = '預約已成功送出，請等待確認';
        header('Location: my_blessings.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

// 獲取可用時段
$day_of_week = date('N'); // 1 (星期一) 到 7 (星期日)
$stmt = $pdo->prepare("
    SELECT * FROM blessing_time_slots 
    WHERE type_id = ? AND day_of_week = ? AND is_available = 1 
    ORDER BY start_time
");
$stmt->execute([$type['id'], $day_of_week]);
$time_slots = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- 頁面標題 -->
            <h1 class="h2 mb-4"><?php echo htmlspecialchars($page_title); ?></h1>

            <?php include '../includes/message.php'; ?>

            <!-- 預約表單 -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <!-- 祈福項目資訊 -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($type['name']); ?></h5>
                                    <p class="text-muted small mb-2">
                                        <?php echo nl2br(htmlspecialchars($type['description'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="h5 text-primary mb-2">
                                        NT$ <?php echo number_format($type['price']); ?>
                                    </div>
                                    <?php if ($type['duration']): ?>
                                        <div class="small text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo htmlspecialchars($type['duration']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr>
                        </div>

                        <!-- 收件人資訊 -->
                        <h5 class="mb-3">收件人資訊</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="recipient_name" class="form-label">姓名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_birthdate" class="form-label">出生日期</label>
                                <input type="date" class="form-control" id="recipient_birthdate" name="recipient_birthdate">
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_gender" class="form-label">性別</label>
                                <select class="form-select" id="recipient_gender" name="recipient_gender">
                                    <option value="">請選擇</option>
                                    <option value="M">男</option>
                                    <option value="F">女</option>
                                    <option value="O">其他</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_phone" class="form-label">聯絡電話</label>
                                <input type="tel" class="form-control" id="recipient_phone" name="recipient_phone">
                            </div>
                            <div class="col-12">
                                <label for="recipient_address" class="form-label">地址</label>
                                <input type="text" class="form-control" id="recipient_address" name="recipient_address">
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_email" class="form-label">電子郵件</label>
                                <input type="email" class="form-control" id="recipient_email" name="recipient_email">
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- 預約資訊 -->
                        <h5 class="mb-3">預約資訊</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="blessing_date" class="form-label">預約日期 <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="blessing_date" name="blessing_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="special_requests" class="form-label">特殊需求</label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- 緊急聯絡人 -->
                        <h5 class="mb-3">緊急聯絡人</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="emergency_contact" class="form-label">姓名</label>
                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_phone" class="form-label">聯絡電話</label>
                                <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone">
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- 送出按鈕 -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">確認預約</button>
                            <a href="index.php" class="btn btn-outline-secondary">返回列表</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 表單驗證
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// 日期選擇器的動態更新
document.getElementById('blessing_date').addEventListener('change', function() {
    // 這裡可以加入 AJAX 請求來檢查選擇日期的可用時段
});
</script>

<?php require_once '../includes/footer.php'; ?> 
