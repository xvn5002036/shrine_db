<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// 獲取祈福服務ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // 獲取祈福服務詳情
    $stmt = $pdo->prepare("
        SELECT b.*, bt.name as type_name 
        FROM blessings b 
        LEFT JOIN blessing_types bt ON b.type_id = bt.id 
        WHERE b.id = ? AND b.status = 'active'
    ");
    $stmt->execute([$id]);
    $blessing = $stmt->fetch();

    if (!$blessing) {
        die('找不到該祈福服務或服務已停用');
    }

    $success = false;
    $error = '';

    // 處理表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $participants = (int)($_POST['participants'] ?? 1);
        $special_requests = trim($_POST['special_requests'] ?? '');

        // 驗證
        if (empty($name) || empty($email) || empty($phone)) {
            $error = '請填寫必填欄位';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '請輸入有效的電子郵件地址';
        } elseif ($participants < 1) {
            $error = '參與人數必須大於0';
        } elseif ($blessing['max_participants'] && $participants > $blessing['max_participants']) {
            $error = '超過最大參與人數限制';
        } else {
            try {
                // 新增預約記錄
                $stmt = $pdo->prepare("
                    INSERT INTO blessing_registrations 
                    (blessing_id, name, email, phone, participants, special_requests) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $id,
                    $name,
                    $email,
                    $phone,
                    $participants,
                    $special_requests
                ]);

                $success = true;
                
                // 清空表單
                $name = $email = $phone = $special_requests = '';
                $participants = 1;

            } catch (Exception $e) {
                error_log('Error creating blessing registration: ' . $e->getMessage());
                $error = '預約失敗，請稍後再試';
            }
        }
    }
} catch (Exception $e) {
    die('系統錯誤：' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>預約<?php echo htmlspecialchars($blessing['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .booking-page {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 40px 0;
        }

        .page-header {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../assets/images/temple-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            text-align: center;
        }

        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .service-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .service-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .service-details h2 {
            color: #333;
            margin-bottom: 15px;
        }

        .service-meta {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .service-meta li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #666;
        }

        .service-meta i {
            color: #8b0000;
            width: 20px;
        }

        .booking-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #8b0000;
            outline: none;
        }

        .btn-submit {
            background: #8b0000;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background: #660000;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 40px 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>


    <div class="booking-page">
        <div class="page-header">
            <div class="container">
                <h1>預約祈福服務</h1>
                <p>虔誠祈福，保佑平安</p>
            </div>
        </div>

        <div class="booking-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    預約成功！我們將盡快處理您的預約申請，並透過電話或電子郵件與您聯繫。
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="booking-grid">
                <!-- 服務資訊 -->
                <div class="service-info">
                    <div class="service-image" style="background-image: url('<?php echo !empty($blessing['image']) ? '../' . $blessing['image'] : '../assets/images/default-blessing.jpg'; ?>')"></div>
                    <div class="service-details">
                        <h2><?php echo htmlspecialchars($blessing['name']); ?></h2>
                        <p><?php echo nl2br(htmlspecialchars($blessing['description'])); ?></p>
                        <ul class="service-meta">
                            <li>
                                <i class="fas fa-tag"></i>
                                類型：<?php echo htmlspecialchars($blessing['type_name']); ?>
                            </li>
                            <li>
                                <i class="fas fa-dollar-sign"></i>
                                價格：NT$ <?php echo number_format($blessing['price']); ?>
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                期間：<?php echo htmlspecialchars($blessing['duration']); ?>
                            </li>
                            <?php if ($blessing['max_participants']): ?>
                            <li>
                                <i class="fas fa-users"></i>
                                人數上限：<?php echo $blessing['max_participants']; ?> 人
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- 預約表單 -->
                <div class="booking-form">
                    <h3>填寫預約資料</h3>
                    <form method="post">
                        <div class="form-group">
                            <label for="name">姓名 <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">電子郵件 <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">聯絡電話 <span class="text-danger">*</span></label>
                            <input type="tel" id="phone" name="phone" class="form-control" required 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="participants">參與人數</label>
                            <input type="number" id="participants" name="participants" class="form-control" 
                                   min="1" <?php echo $blessing['max_participants'] ? 'max="'.$blessing['max_participants'].'"' : ''; ?> 
                                   value="<?php echo $participants ?? 1; ?>">
                        </div>

                        <div class="form-group">
                            <label for="special_requests">特殊需求或備註</label>
                            <textarea id="special_requests" name="special_requests" class="form-control" 
                                      rows="4"><?php echo htmlspecialchars($special_requests ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> 確認預約
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>

    <script>
        // 表單驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const participants = parseInt(document.getElementById('participants').value);

            if (!name || !email || !phone) {
                e.preventDefault();
                alert('請填寫所有必填欄位');
            } else if (participants < 1) {
                e.preventDefault();
                alert('參與人數必須大於0');
            }
        });
    </script>
</body>
</html> 
