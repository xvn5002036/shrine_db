<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的預約ID';
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // 獲取預約詳細資訊
    $stmt = $pdo->prepare("
        SELECT br.*, 
               b.name as blessing_name, 
               b.duration,
               b.price,
               bt.name as type_name
        FROM blessing_registrations br
        LEFT JOIN blessings b ON br.blessing_id = b.id
        LEFT JOIN blessing_types bt ON b.type_id = bt.id
        WHERE br.id = ?
    ");
    $stmt->execute([$id]);
    $registration = $stmt->fetch();

    if (!$registration) {
        $_SESSION['error'] = '找不到指定的預約記錄';
        header('Location: index.php');
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = '資料庫錯誤：' . $e->getMessage();
    header('Location: index.php');
    exit();
}

$page_title = '查看祈福預約';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>查看祈福預約</h1>
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

        <div class="row">
            <div class="col-md-8">
                <!-- 基本資訊 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">基本資訊</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">預約編號</label>
                                <p><?php echo $registration['id']; ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">申請日期</label>
                                <p><?php echo date('Y-m-d H:i', strtotime($registration['created_at'])); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">狀態</label>
                                <p>
                                    <span class="badge <?php 
                                        echo match($registration['status']) {
                                            'pending' => 'bg-warning',
                                            'confirmed' => 'bg-info',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php 
                                        echo match($registration['status']) {
                                            'pending' => '待處理',
                                            'confirmed' => '已確認',
                                            'completed' => '已完成',
                                            'cancelled' => '已取消',
                                            default => '未知'
                                        };
                                        ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">祈福項目</label>
                                <p><?php echo htmlspecialchars($registration['blessing_name']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">類型</label>
                                <p><?php echo htmlspecialchars($registration['type_name']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">服務期間</label>
                                <p><?php echo htmlspecialchars($registration['duration']); ?></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">金額</label>
                                <p>NT$ <?php echo number_format($registration['price']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">付款狀態</label>
                                <p>
                                    <span class="badge <?php echo $registration['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $registration['payment_status'] === 'paid' ? '已付款' : '未付款'; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">付款方式</label>
                                <p><?php echo htmlspecialchars($registration['payment_method'] ?? '尚未選擇'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 申請人資訊 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">申請人資訊</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">姓名</label>
                                <p><?php echo htmlspecialchars($registration['name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">性別</label>
                                <p><?php echo isset($registration['gender']) ? ($registration['gender'] === 'M' ? '男' : '女') : '未指定'; ?></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">電話</label>
                                <p><?php echo htmlspecialchars($registration['phone'] ?? '未提供'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <p><?php echo htmlspecialchars($registration['email'] ?? '未提供'); ?></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">地址</label>
                                <p><?php echo htmlspecialchars($registration['address'] ?? '未提供'); ?></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-bold">備註</label>
                                <p><?php echo nl2br(htmlspecialchars($registration['notes'] ?? '無')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- 操作記錄 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">狀態更新</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">更新狀態</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo $registration['status'] === 'pending' ? 'selected' : ''; ?>>待處理</option>
                                    <option value="confirmed" <?php echo $registration['status'] === 'confirmed' ? 'selected' : ''; ?>>已確認</option>
                                    <option value="completed" <?php echo $registration['status'] === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                    <option value="cancelled" <?php echo $registration['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 更新狀態
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 系統資訊 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">系統資訊</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">建立時間</label>
                            <p><?php echo date('Y-m-d H:i:s', strtotime($registration['created_at'])); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">最後更新</label>
                            <p><?php echo date('Y-m-d H:i:s', strtotime($registration['updated_at'])); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">建立者</label>
                            <p><?php echo htmlspecialchars($registration['created_by_name'] ?? '系統'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 
