<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// 獲取所有啟用的祈福類型
$stmt = $pdo->prepare("
    SELECT * FROM blessing_types 
    WHERE status = 1 
    ORDER BY is_featured DESC, sort_order ASC, name ASC
");
$stmt->execute();
$blessing_types = $stmt->fetchAll();

// 頁面標題
$page_title = '祈福服務';
$current_page = 'blessings';
require_once '../templates/header.php';
?>

<div class="container py-5">
    <!-- 頁面標題 -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="fw-bold text-primary mb-3">祈福服務</h1>
            <p class="lead text-muted">誠心祈福，福慧雙修</p>
        </div>
    </div>

    <!-- 特色祈福項目 -->
    <?php
    $featured_types = array_filter($blessing_types, function($type) {
        return $type['is_featured'];
    });
    if (!empty($featured_types)):
    ?>
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="h3 mb-4">特色祈福</h2>
        </div>
        <?php foreach ($featured_types as $type): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($type['image_path']): ?>
                        <img src="<?php echo htmlspecialchars('../' . $type['image_path']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($type['name']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($type['name']); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo nl2br(htmlspecialchars($type['description'])); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-primary fw-bold">
                                NT$ <?php echo number_format($type['price']); ?>
                            </div>
                            <a href="booking.php?type=<?php echo $type['slug']; ?>" 
                               class="btn btn-primary">
                                立即預約
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 所有祈福項目 -->
    <div class="row">
        <div class="col-12">
            <h2 class="h3 mb-4">所有祈福項目</h2>
        </div>
        <?php foreach ($blessing_types as $type): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($type['image_path']): ?>
                        <img src="<?php echo htmlspecialchars('../' . $type['image_path']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($type['name']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($type['name']); ?>
                            <?php if ($type['is_featured']): ?>
                                <span class="badge bg-warning text-dark">特色</span>
                            <?php endif; ?>
                        </h5>
                        <p class="card-text text-muted">
                            <?php echo nl2br(htmlspecialchars($type['description'])); ?>
                        </p>
                        <ul class="list-unstyled mb-3">
                            <?php if ($type['duration']): ?>
                                <li>
                                    <i class="fas fa-clock text-muted me-2"></i>
                                    <?php echo htmlspecialchars($type['duration']); ?>
                                </li>
                            <?php endif; ?>
                            <?php if ($type['max_daily_slots']): ?>
                                <li>
                                    <i class="fas fa-users text-muted me-2"></i>
                                    每日限額：<?php echo $type['max_daily_slots']; ?> 名
                                </li>
                            <?php endif; ?>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-primary fw-bold">
                                NT$ <?php echo number_format($type['price']); ?>
                            </div>
                            <a href="booking.php?type=<?php echo $type['slug']; ?>" 
                               class="btn btn-primary">
                                立即預約
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 注意事項 -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="h4 mb-3">預約注意事項</h3>
                    <ul class="mb-0">
                        <li>請提前預約，以確保時段安排。</li>
                        <li>預約時請提供正確的聯絡資訊。</li>
                        <li>如需更改或取消預約，請提前通知。</li>
                        <li>特殊節日可能會調整服務時間，請留意公告。</li>
                        <li>如有任何疑問，歡迎聯繫我們。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 
