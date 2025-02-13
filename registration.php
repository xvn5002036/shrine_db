<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 設定輸出編碼
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// 檢查是否有活動ID
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    header('Location: events.php');
    exit;
}

$event_id = (int)$_GET['event_id'];
$error_message = '';
$success_message = '';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );

    // 取得活動資訊
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: events.php');
        exit;
    }

    // 檢查是否已超過報名截止日期
    if (strtotime($event['registration_deadline']) <= time()) {
        header('Location: events.php');
        exit;
    }

    // 處理表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 驗證表單資料
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $participants = (int)($_POST['participants'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors[] = '請填寫姓名';
        }

        if (empty($phone)) {
            $errors[] = '請填寫電話';
        }

        if (empty($email)) {
            $errors[] = '請填寫電子郵件';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '電子郵件格式不正確';
        }

        if ($participants < 1) {
            $errors[] = '參加人數必須大於 0';
        }

        if ($event['capacity'] > 0) {
            // 檢查剩餘名額
            $stmt = $pdo->prepare("SELECT SUM(participants) as total FROM registrations WHERE event_id = ?");
            $stmt->execute([$event_id]);
            $registered = $stmt->fetch();
            $remaining = $event['capacity'] - ($registered['total'] ?? 0);

            if ($participants > $remaining) {
                $errors[] = "超過剩餘名額（剩餘 {$remaining} 位）";
            }
        }

        if (empty($errors)) {
            // 新增報名資料
            $stmt = $pdo->prepare("
                INSERT INTO registrations (event_id, name, phone, email, participants, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$event_id, $name, $phone, $email, $participants, $notes])) {
                $success_message = '報名成功！';
            } else {
                $error_message = '報名失敗，請稍後再試。';
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }

} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}

// 引入頁首
include 'templates/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center mb-0">活動報名</h2>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    
                    <div class="event-info mb-4">
                        <p><i class="fas fa-calendar-alt"></i> 活動日期：<?php echo date('Y/m/d', strtotime($event['event_date'])); ?></p>
                        <p><i class="fas fa-clock"></i> 活動時間：<?php echo $event['event_time'] ? date('H:i', strtotime($event['event_time'])) : '待定'; ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> 地點：<?php echo htmlspecialchars($event['location']); ?></p>
                        <?php if ($event['capacity']): ?>
                        <p><i class="fas fa-users"></i> 人數限制：<?php echo number_format($event['capacity']); ?> 人</p>
                        <?php endif; ?>
                        <p><i class="fas fa-hourglass-end"></i> 報名截止：<?php echo date('Y/m/d', strtotime($event['registration_deadline'])); ?></p>
                    </div>

                    <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                        <div class="mt-3">
                            <a href="events.php" class="btn btn-primary">返回活動列表</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">電話 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" required
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">電子郵件 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="participants" class="form-label">參加人數 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="participants" name="participants" 
                                   min="1" value="<?php echo htmlspecialchars($_POST['participants'] ?? '1'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">備註</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">確認報名</button>
                            <a href="events.php" class="btn btn-secondary">返回活動列表</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<!-- 表單驗證 JavaScript -->
<script>
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
</body>
</html> 