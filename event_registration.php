<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 建立資料庫連線
try {
    $dbConfig = require 'config/database.php';
    $db = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('資料庫連線錯誤：' . $e->getMessage());
    die('系統發生錯誤，請稍後再試。');
}

// 檢查是否有活動ID
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 如果沒有活動ID，導向活動列表頁
if (!$event_id) {
    header('Location: events.php');
    exit;
}

try {
    // 獲取活動詳情
    $stmt = $db->prepare("
        SELECT * FROM events 
        WHERE id = ? AND status = 1 
        AND registration_start_date <= NOW() 
        AND registration_end_date >= NOW()
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die('活動不存在或報名已截止');
    }

    $success = false;
    $error = '';

    // 處理報名表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $participants = (int)($_POST['participants'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');

        // 驗證
        if (empty($name) || empty($email) || empty($phone)) {
            $error = '請填寫必填欄位';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '請輸入有效的電子郵件地址';
        } elseif ($participants < 1) {
            $error = '參加人數必須大於0';
        } else {
            try {
                // 檢查是否還有名額
                if ($event['max_participants'] > 0) {
                    $stmt = $db->prepare("
                        SELECT SUM(participants) as total 
                        FROM event_registrations 
                        WHERE event_id = ? AND status = 1
                    ");
                    $stmt->execute([$event_id]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (($current['total'] + $participants) > $event['max_participants']) {
                        throw new Exception('報名人數已滿');
                    }
                }

                // 儲存報名資料
                $stmt = $db->prepare("
                    INSERT INTO event_registrations 
                    (event_id, name, email, phone, participants, notes, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$event_id, $name, $email, $phone, $participants, $notes]);

                $success = true;
                
                // 清空表單
                $name = $email = $phone = $notes = '';
                $participants = 1;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
} catch (Exception $e) {
    die('系統錯誤：' . $e->getMessage());
}

// 頁面標題
$page_title = "活動報名 - {$event['title']} | " . SITE_NAME;
require_once 'templates/header.php';
?>

<div class="event-registration-page">
    <div class="page-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($event['description']); ?></p>
        </div>
    </div>

    <div class="container py-5">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                報名成功！我們將盡快與您聯繫。
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- 活動資訊 -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">活動資訊</h5>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                活動時間：<?php echo date('Y/m/d H:i', strtotime($event['start_date'])); ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                活動地點：<?php echo htmlspecialchars($event['location']); ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-users text-primary me-2"></i>
                                名額限制：
                                <?php if ($event['max_participants'] > 0): ?>
                                    <?php echo $event['max_participants']; ?> 人
                                <?php else: ?>
                                    不限
                                <?php endif; ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-clock text-primary me-2"></i>
                                報名截止：<?php echo date('Y/m/d', strtotime($event['registration_end_date'])); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 報名表單 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">報名表單</h5>
                        <form method="post" action="event_registration.php?id=<?php echo $event_id; ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">姓名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">電話 <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required 
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="participants" class="form-label">參加人數</label>
                                <input type="number" class="form-control" id="participants" name="participants" 
                                       min="1" value="<?php echo $participants ?? 1; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">備註</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                          ><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>送出報名
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.event-registration-page {
    background-color: #f8f9fa;
    min-height: calc(100vh - 60px);
}

.page-header {
    background: linear-gradient(135deg, #4a90e2 0%, #8e44ad 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin-bottom: 1rem;
    font-weight: 300;
}

.lead {
    font-size: 1.1rem;
    opacity: 0.9;
}

.card {
    border: none;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

.card-title {
    color: #333;
    font-weight: 500;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.form-label {
    font-weight: 500;
    color: #555;
}

.btn-primary {
    padding: 0.8rem 2rem;
    font-weight: 500;
}

.alert {
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert i {
    font-size: 1.2rem;
}
</style>

<?php include 'includes/footer.php'; ?> 
