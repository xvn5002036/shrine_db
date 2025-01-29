<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 初始化錯誤和成功訊息
$error = null;
$success = null;

// 分頁設定
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10; // 每頁顯示數量
$offset = ($page - 1) * $limit;

try {
    // 獲取服務類型列表
    $stmt = $db->query("SELECT * FROM service_types WHERE status = 1 ORDER BY sort_order, name");
    $service_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取服務類型篩選
    $type_id = isset($_GET['type']) ? (int)$_GET['type'] : null;

    // 構建查詢條件
    $where_conditions = ['s.status = 1'];
    $params = [];
    
    if ($type_id) {
        $where_conditions[] = 's.type_id = ?';
        $params[] = $type_id;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 獲取服務列表
    $stmt = $db->prepare("
        SELECT s.*, st.name as type_name 
        FROM services s 
        JOIN service_types st ON s.type_id = st.id 
        $where_clause 
        ORDER BY s.is_featured DESC, s.created_at DESC
    ");
    
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 處理表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
        // 驗證必填欄位
        $required_fields = ['name', 'phone', 'email', 'service_id', 'booking_date', 'booking_time'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            $error = '請填寫所有必填欄位';
        } else {
            // 準備資料
            $booking_data = [
                'service_id' => (int)$_POST['service_id'],
                'name' => trim($_POST['name']),
                'phone' => trim($_POST['phone']),
                'email' => trim($_POST['email']),
                'booking_date' => trim($_POST['booking_date']),
                'booking_time' => trim($_POST['booking_time']),
                'notes' => trim($_POST['notes'] ?? ''),
                'status' => 'pending'
            ];
            
            // 插入預約資料
            $stmt = $db->prepare("
                INSERT INTO service_bookings 
                (service_id, name, phone, email, booking_date, booking_time, notes, status, created_at) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([
                $booking_data['service_id'],
                $booking_data['name'],
                $booking_data['phone'],
                $booking_data['email'],
                $booking_data['booking_date'],
                $booking_data['booking_time'],
                $booking_data['notes'],
                $booking_data['status']
            ])) {
                $success = '預約申請已送出，我們會盡快與您聯絡確認';
            } else {
                $error = '系統錯誤，請稍後再試';
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = '系統發生錯誤，請稍後再試';
}

// 頁面標題
$page_title = '宮廟服務';
require_once 'templates/header.php';
?>

<main class="container">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="services-container">
        <!-- 服務類型導航 -->
        <div class="services-nav">
            <a href="services.php" class="service-type-btn <?php echo !$type_id ? 'active' : ''; ?>">
                全部服務
            </a>
            <?php foreach ($service_types as $type): ?>
                <a href="?type=<?php echo $type['id']; ?>" 
                   class="service-type-btn <?php echo $type_id == $type['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($type['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 服務列表 -->
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <?php if ($service['image']): ?>
                        <div class="service-image">
                            <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($service['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="service-content">
                        <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p class="service-type"><?php echo htmlspecialchars($service['type_name']); ?></p>
                        <?php if ($service['price']): ?>
                            <p class="service-price">NT$ <?php echo number_format($service['price']); ?></p>
                        <?php endif; ?>
                        <?php if ($service['duration']): ?>
                            <p class="service-duration"><?php echo htmlspecialchars($service['duration']); ?></p>
                        <?php endif; ?>
                        <div class="service-description">
                            <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                        </div>
                        <button class="btn btn-primary book-service" 
                                data-service-id="<?php echo $service['id']; ?>"
                                data-service-name="<?php echo htmlspecialchars($service['name']); ?>"
                                onclick="openBookingForm(this)">
                            立即預約
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 預約表單 Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>服務預約</h2>
            <form id="bookingForm" method="post" action="services.php">
                <input type="hidden" name="service_id" id="booking_service_id">
                
                <div class="form-group">
                    <label for="name">姓名 <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">聯絡電話 <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">電子郵件 <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="booking_date">預約日期 <span class="required">*</span></label>
                    <input type="date" id="booking_date" name="booking_date" 
                           class="form-control" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="booking_time">預約時間 <span class="required">*</span></label>
                    <input type="time" id="booking_time" name="booking_time" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="notes">備註</label>
                    <textarea id="notes" name="notes" class="form-control" rows="4"></textarea>
                </div>
                
                <button type="submit" name="submit_booking" class="btn btn-primary">送出預約</button>
            </form>
        </div>
    </div>
</main>

<?php require_once 'templates/footer.php'; ?>

<style>
.services-container {
    padding: 40px 0;
}

.services-nav {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 40px;
}

.service-type-btn {
    padding: 10px 20px;
    border: 2px solid #c19b77;
    border-radius: 30px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.service-type-btn:hover,
.service-type-btn.active {
    background-color: #c19b77;
    color: #fff;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
}

.service-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.service-card:hover {
    transform: translateY(-5px);
}

.service-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.service-content {
    padding: 20px;
}

.service-content h3 {
    margin: 0 0 10px;
    color: #333;
}

.service-type {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 10px;
}

.service-price {
    color: #c19b77;
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 10px;
}

.service-duration {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 15px;
}

.service-description {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

/* Modal 樣式 */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #c19b77;
}

@media (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
}
</style>

<script>
function openBookingForm(button) {
    const modal = document.getElementById('bookingModal');
    const serviceId = button.getAttribute('data-service-id');
    const serviceName = button.getAttribute('data-service-name');
    
    document.getElementById('booking_service_id').value = serviceId;
    modal.style.display = 'block';
}

// 關閉 Modal
document.querySelector('.close').onclick = function() {
    document.getElementById('bookingModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('bookingModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script> 
