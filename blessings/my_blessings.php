<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// 檢查是否已登入
if (!isLoggedIn()) {
    $_SESSION['error'] = '請先登入';
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../login.php');
    exit;
}

// 處理取消預約
if (isset($_POST['cancel']) && isset($_POST['id'])) {
    try {
        // 檢查預約是否存在且屬於當前用戶
        $stmt = $pdo->prepare("
            SELECT * FROM blessings 
            WHERE id = ? AND user_id = ? AND blessing_status NOT IN ('completed', 'cancelled')
        ");
        $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
        $blessing = $stmt->fetch();

        if (!$blessing) {
            throw new Exception('找不到可取消的預約');
        }

        // 檢查是否可以取消（例如：距離預約日期還有一定時間）
        $blessing_date = new DateTime($blessing['blessing_date']);
        $today = new DateTime();
        $interval = $today->diff($blessing_date);
        
        if ($interval->days < 1) {
            throw new Exception('預約日期前24小時內不可取消');
        }

        // 更新預約狀態
        $stmt = $pdo->prepare("
            UPDATE blessings 
            SET blessing_status = 'cancelled', 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_POST['id']]);

        $_SESSION['success'] = '預約已成功取消';
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: my_blessings.php');
    exit;
}

// 分頁設定
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 獲取總記錄數
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM blessings WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$total_records = $stmt->fetchColumn();

// 計算總頁數
$total_pages = ceil($total_records / $per_page);

// 獲取預約列表
$stmt = $pdo->prepare("
    SELECT b.*, t.name as type_name, t.duration
    FROM blessings b
    JOIN blessing_types t ON b.type_id = t.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $per_page, $offset]);
$blessings = $stmt->fetchAll();

// 頁面標題
$page_title = '我的祈福';
$current_page = 'blessings';
require_once '../templates/header.php';
?>

<div class="container py-5">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><?php echo $page_title; ?></h1>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新增預約
        </a>
    </div>

    <?php include '../includes/message.php'; ?>

    <!-- 預約列表 -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>預約編號</th>
                        <th>祈福項目</th>
                        <th>收件人</th>
                        <th>預約日期</th>
                        <th>金額</th>
                        <th>付款狀態</th>
                        <th>預約狀態</th>
                        <th width="100">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blessings)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">尚無預約記錄</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blessings as $blessing): ?>
                            <tr>
                                <td><?php echo str_pad($blessing['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($blessing['type_name']); ?>
                                    <?php if ($blessing['duration']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo htmlspecialchars($blessing['duration']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($blessing['recipient_name']); ?>
                                    <?php if ($blessing['recipient_phone']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($blessing['recipient_phone']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d', strtotime($blessing['blessing_date'])); ?>
                                    <?php if ($blessing['preferred_time']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $blessing['preferred_time']; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    NT$ <?php echo number_format($blessing['amount']); ?>
                                </td>
                                <td>
                                    <?php
                                    $payment_status_class = [
                                        'pending' => 'bg-warning text-dark',
                                        'paid' => 'bg-success',
                                        'refunded' => 'bg-info',
                                        'cancelled' => 'bg-secondary'
                                    ];
                                    $payment_status_text = [
                                        'pending' => '待付款',
                                        'paid' => '已付款',
                                        'refunded' => '已退款',
                                        'cancelled' => '已取消'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $payment_status_class[$blessing['payment_status']]; ?>">
                                        <?php echo $payment_status_text[$blessing['payment_status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $blessing_status_class = [
                                        'pending' => 'bg-warning text-dark',
                                        'confirmed' => 'bg-info',
                                        'in_progress' => 'bg-primary',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-secondary'
                                    ];
                                    $blessing_status_text = [
                                        'pending' => '待確認',
                                        'confirmed' => '已確認',
                                        'in_progress' => '進行中',
                                        'completed' => '已完成',
                                        'cancelled' => '已取消'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $blessing_status_class[$blessing['blessing_status']]; ?>">
                                        <?php echo $blessing_status_text[$blessing['blessing_status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view.php?id=<?php echo $blessing['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="查看詳情">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($blessing['blessing_status'] === 'pending'): ?>
                                            <form method="post" class="d-inline" 
                                                  onsubmit="return confirm('確定要取消這個預約嗎？');">
                                                <input type="hidden" name="id" value="<?php echo $blessing['id']; ?>">
                                                <button type="submit" name="cancel" 
                                                        class="btn btn-sm btn-outline-danger" title="取消預約">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 分頁 -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 
