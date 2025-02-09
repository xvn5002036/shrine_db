<?php
require_once 'config/config.php';
require_once 'includes/db_connect.php';

// 獲取祈福服務類型
try {
    $stmt = $pdo->query("
        SELECT bt.*, COUNT(b.id) as service_count 
        FROM blessing_types bt 
        LEFT JOIN blessings b ON bt.id = b.type_id 
        WHERE bt.status = 1 
        GROUP BY bt.id 
        ORDER BY bt.sort_order ASC
    ");
    $blessing_types = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching blessing types: ' . $e->getMessage());
    $blessing_types = [];
}

// 獲取熱門祈福服務
try {
    $stmt = $pdo->query("
        SELECT b.*, bt.name as type_name 
        FROM blessings b 
        LEFT JOIN blessing_types bt ON b.type_id = bt.id 
        WHERE b.status = 'active' 
        ORDER BY b.sort_order ASC 
        LIMIT 6
    ");
    $featured_services = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching featured services: ' . $e->getMessage());
    $featured_services = [];
}

$page_title = '線上祈福';
$current_page = 'prayer';
require_once 'templates/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>線上祈福</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
                <li class="breadcrumb-item active" aria-current="page">線上祈福</li>
            </ol>
        </nav>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <!-- 祈福服務類型 -->
        <section class="blessing-types mb-5">
            <h2 class="section-title">祈福服務類型</h2>
            <div class="row">
                <?php foreach ($blessing_types as $type): ?>
                    <div class="col-md-4 mb-4">
                        <div class="blessing-type-card">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($type['name']); ?></h3>
                                    <p class="card-text"><?php echo htmlspecialchars($type['description']); ?></p>
                                    <a href="blessings.php?type=<?php echo $type['id']; ?>" class="btn btn-primary">
                                        查看服務
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 熱門祈福服務 -->
        <section class="featured-services mb-5">
            <h2 class="section-title">熱門祈福服務</h2>
            <div class="row">
                <?php foreach ($featured_services as $service): ?>
                    <div class="col-md-4 mb-4">
                        <div class="service-card">
                            <div class="card">
                                <?php if (!empty($service['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="service-type"><?php echo htmlspecialchars($service['type_name']); ?></p>
                                    <p class="service-price">NT$ <?php echo number_format($service['price']); ?></p>
                                    <p class="service-duration">
                                        <i class="far fa-clock"></i> <?php echo htmlspecialchars($service['duration']); ?>
                                    </p>
                                    <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                                    <a href="booking.php?service=<?php echo $service['id']; ?>" class="btn btn-primary">
                                        立即預約
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 線上祈福表單 -->
        <section class="prayer-form mb-5">
            <h2 class="section-title">線上祈福預約</h2>
            <div class="card">
                <div class="card-body">
                    <form action="booking_process.php" method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">姓名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">請輸入姓名</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">聯絡電話 <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">請輸入聯絡電話</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">電子信箱 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">請輸入有效的電子信箱</div>
                        </div>

                        <div class="mb-3">
                            <label for="service_type" class="form-label">祈福服務類型 <span class="text-danger">*</span></label>
                            <select class="form-select" id="service_type" name="service_type" required>
                                <option value="">請選擇服務類型</option>
                                <?php foreach ($blessing_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">請選擇服務類型</div>
                        </div>

                        <div class="mb-3">
                            <label for="service" class="form-label">祈福項目 <span class="text-danger">*</span></label>
                            <select class="form-select" id="service" name="service" required>
                                <option value="">請先選擇服務類型</option>
                            </select>
                            <div class="invalid-feedback">請選擇祈福項目</div>
                        </div>

                        <div class="mb-3">
                            <label for="prayer_intention" class="form-label">祈福意向</label>
                            <textarea class="form-control" id="prayer_intention" name="prayer_intention" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="preferred_date" class="form-label">預約日期 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="preferred_date" name="preferred_date" required>
                            <div class="invalid-feedback">請選擇預約日期</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-pray"></i> 送出預約
                        </button>
                    </form>
                </div>
            </div>
        </section>
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

// 動態載入祈福項目
document.getElementById('service_type').addEventListener('change', function() {
    const typeId = this.value;
    const serviceSelect = document.getElementById('service');
    
    if (typeId) {
        fetch(`api/get_services.php?type_id=${typeId}`)
            .then(response => response.json())
            .then(data => {
                serviceSelect.innerHTML = '<option value="">請選擇祈福項目</option>';
                data.forEach(service => {
                    serviceSelect.innerHTML += `
                        <option value="${service.id}">${service.name} - NT$ ${service.price}</option>
                    `;
                });
            })
            .catch(error => console.error('Error:', error));
    } else {
        serviceSelect.innerHTML = '<option value="">請先選擇服務類型</option>';
    }
});
</script>

<?php require_once 'templates/footer.php'; ?> 