<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

// 獲取捐款類型列表
$types_stmt = $pdo->query("SELECT id, name FROM donation_types WHERE status = 1 ORDER BY sort_order");
$donation_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 驗證表單數據
    $donor_name = trim($_POST['donor_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $donation_type_id = $_POST['donation_type_id'] ?? '';
    $donation_date = $_POST['donation_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $receipt_number = trim($_POST['receipt_number'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = $_POST['status'] ?? 'pending';

    // 驗證必填欄位
    if (empty($donor_name)) {
        $errors[] = "請輸入捐款人姓名";
    }
    if (empty($contact)) {
        $errors[] = "請輸入聯絡方式";
    }
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "請輸入有效的捐款金額";
    }
    if (empty($donation_type_id)) {
        $errors[] = "請選擇捐款類型";
    }
    if (empty($donation_date)) {
        $errors[] = "請選擇捐款日期";
    }
    if (empty($payment_method)) {
        $errors[] = "請選擇付款方式";
    }

    // 如果沒有錯誤，保存捐款記錄
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO donations (
                donor_name, contact, amount, donation_type_id, donation_date,
                payment_method, receipt_number, purpose, status, notes, processed_by,
                created_at
            ) VALUES (
                :donor_name, :contact, :amount, :donation_type_id, :donation_date,
                :payment_method, :receipt_number, :purpose, :status, :notes, :processed_by,
                NOW()
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':donor_name' => $donor_name,
                ':contact' => $contact,
                ':amount' => $amount,
                ':donation_type_id' => $donation_type_id,
                ':donation_date' => $donation_date,
                ':payment_method' => $payment_method,
                ':receipt_number' => $receipt_number,
                ':purpose' => $purpose,
                ':status' => $status,
                ':notes' => $notes,
                ':processed_by' => $_SESSION['admin_id']
            ]);

            // 記錄操作日誌
            logAdminAction('新增捐款', "新增捐款記錄：{$donor_name} - {$amount}元");

            // 設置成功消息
            setFlashMessage('success', '捐款記錄新增成功！');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = '保存失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 新增捐款</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/zh-tw.js"></script>
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>新增捐款</h2>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="add.php" method="post" class="form">
                        <div class="form-group">
                            <label for="donor_name">捐款人姓名 <span class="text-danger">*</span></label>
                            <input type="text" id="donor_name" name="donor_name" 
                                   value="<?php echo htmlspecialchars($donor_name ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="contact">聯絡方式 <span class="text-danger">*</span></label>
                            <input type="text" id="contact" name="contact" 
                                   value="<?php echo htmlspecialchars($contact ?? ''); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="amount">捐款金額 <span class="text-danger">*</span></label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" 
                                       value="<?php echo htmlspecialchars($amount ?? ''); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="donation_type_id">捐款類型 <span class="text-danger">*</span></label>
                                <select id="donation_type_id" name="donation_type_id" required>
                                    <option value="">請選擇</option>
                                    <?php foreach ($donation_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"
                                            <?php echo (isset($donation_type_id) && $donation_type_id == $type['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="donation_date">捐款日期 <span class="text-danger">*</span></label>
                                <input type="text" id="donation_date" name="donation_date" 
                                       value="<?php echo htmlspecialchars($donation_date ?? date('Y-m-d')); ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="payment_method">付款方式 <span class="text-danger">*</span></label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="">請選擇</option>
                                    <option value="cash" <?php echo (isset($payment_method) && $payment_method == 'cash') ? 'selected' : ''; ?>>現金</option>
                                    <option value="credit_card" <?php echo (isset($payment_method) && $payment_method == 'credit_card') ? 'selected' : ''; ?>>信用卡</option>
                                    <option value="bank_transfer" <?php echo (isset($payment_method) && $payment_method == 'bank_transfer') ? 'selected' : ''; ?>>銀行轉帳</option>
                                    <option value="line_pay" <?php echo (isset($payment_method) && $payment_method == 'line_pay') ? 'selected' : ''; ?>>LINE Pay</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="receipt_number">收據編號</label>
                                <input type="text" id="receipt_number" name="receipt_number" 
                                       value="<?php echo htmlspecialchars($receipt_number ?? ''); ?>">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="status">狀態</label>
                                <select id="status" name="status">
                                    <option value="pending" <?php echo (isset($status) && $status == 'pending') ? 'selected' : ''; ?>>待處理</option>
                                    <option value="processing" <?php echo (isset($status) && $status == 'processing') ? 'selected' : ''; ?>>處理中</option>
                                    <option value="completed" <?php echo (isset($status) && $status == 'completed') ? 'selected' : ''; ?>>已完成</option>
                                    <option value="cancelled" <?php echo (isset($status) && $status == 'cancelled') ? 'selected' : ''; ?>>已取消</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="purpose">捐款用途</label>
                            <input type="text" id="purpose" name="purpose" 
                                   value="<?php echo htmlspecialchars($purpose ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="notes">備註</label>
                            <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">新增捐款</button>
                            <a href="index.php" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
    <script>
        // 初始化日期選擇器
        flatpickr("#donation_date", {
            dateFormat: "Y-m-d",
            locale: "zh-tw"
        });
    </script>
</body>
</html> 