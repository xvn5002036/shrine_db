<?php
session_start();

// 檢查是否已經安裝
if (file_exists('../config/installed.php') && !isset($_GET['force'])) {
    die('系統已經安裝。如果需要重新安裝，請刪除 config/installed.php 檔案或在網址後加上 ?force=1');
}

// 安裝步驟
$steps = [
    1 => '系統需求檢查',
    2 => '資料庫設定',
    3 => '網站基本設定',
    4 => '完成安裝'
];

// 當前步驟
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// 檢查系統需求
function check_requirements() {
    $requirements = [
        'PHP 版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO 擴充模組' => extension_loaded('pdo'),
        'PDO MySQL 擴充模組' => extension_loaded('pdo_mysql'),
        'GD 擴充模組' => extension_loaded('gd'),
        'config 目錄可寫入' => is_writable('../config') || @mkdir('../config', 0755),
        'uploads 目錄可寫入' => is_writable('../uploads') || @mkdir('../uploads', 0755),
    ];
    return $requirements;
}

// 測試資料庫連線
function test_database_connection($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $db = new PDO($dsn, $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>網站安裝精靈</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding-top: 40px; }
        .install-container { max-width: 800px; margin: 0 auto; }
        .steps { margin-bottom: 30px; }
        .steps .step { padding: 10px; border: 1px solid #ddd; margin: 5px 0; }
        .steps .current { background-color: #007bff; color: white; }
        .steps .completed { background-color: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container install-container">
        <h1 class="text-center mb-4">網站安裝精靈</h1>
        
        <!-- 安裝步驟 -->
        <div class="steps">
            <?php foreach ($steps as $step => $name): ?>
                <div class="step <?php 
                    if ($step == $current_step) echo 'current';
                    elseif ($step < $current_step) echo 'completed';
                ?>">
                    <?php echo "步驟 $step: $name"; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($current_step == 1): ?>
            <!-- 步驟 1: 系統需求檢查 -->
            <div class="card">
                <div class="card-header">
                    <h2>系統需求檢查</h2>
                </div>
                <div class="card-body">
                    <?php
                    $requirements = check_requirements();
                    $can_proceed = true;
                    foreach ($requirements as $requirement => $satisfied):
                        $can_proceed = $can_proceed && $satisfied;
                    ?>
                        <div class="requirement-item">
                            <span class="requirement-name"><?php echo $requirement; ?></span>
                            <?php if ($satisfied): ?>
                                <span class="badge badge-success">符合</span>
                            <?php else: ?>
                                <span class="badge badge-danger">不符合</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($can_proceed): ?>
                        <a href="?step=2" class="btn btn-primary mt-3">下一步</a>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3">
                            請先解決上述問題後再繼續安裝。
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($current_step == 2): ?>
            <!-- 步驟 2: 資料庫設定 -->
            <div class="card">
                <div class="card-header">
                    <h2>資料庫設定</h2>
                </div>
                <div class="card-body">
                    <form action="install.php" method="post">
                        <input type="hidden" name="step" value="2">
                        
                        <div class="form-group">
                            <label>資料庫主機</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                        </div>

                        <div class="form-group">
                            <label>資料庫名稱</label>
                            <input type="text" name="db_name" class="form-control" value="shrine_db" required>
                        </div>

                        <div class="form-group">
                            <label>資料庫使用者</label>
                            <input type="text" name="db_user" class="form-control" value="root" required>
                        </div>

                        <div class="form-group">
                            <label>資料庫密碼</label>
                            <input type="password" name="db_pass" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">測試連線並繼續</button>
                    </form>
                </div>
            </div>

        <?php elseif ($current_step == 3): ?>
            <!-- 步驟 3: 網站基本設定 -->
            <div class="card">
                <div class="card-header">
                    <h2>網站基本設定</h2>
                </div>
                <div class="card-body">
                    <form action="install.php" method="post">
                        <input type="hidden" name="step" value="3">
                        
                        <div class="form-group">
                            <label>網站名稱</label>
                            <input type="text" name="site_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>管理員帳號</label>
                            <input type="text" name="admin_username" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>管理員密碼</label>
                            <input type="password" name="admin_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>管理員 Email</label>
                            <input type="email" name="admin_email" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">完成設定</button>
                    </form>
                </div>
            </div>

        <?php elseif ($current_step == 4): ?>
            <!-- 步驟 4: 完成安裝 -->
            <div class="card">
                <div class="card-header">
                    <h2>安裝完成</h2>
                </div>
                <div class="card-body">
                    <?php
                    // 創建安裝鎖定檔
                    $installed_file = '../config/installed.php';
                    $content = "<?php\n// 安裝完成時間：" . date('Y-m-d H:i:s') . "\ndefine('INSTALLED', true);\n?>";
                    file_put_contents($installed_file, $content);
                    ?>
                    <div class="alert alert-success">
                        恭喜！系統已經安裝完成。
                    </div>
                    <p>請注意以下事項：</p>
                    <ul>
                        <li>請刪除 install 目錄以確保安全</li>
                        <li>請妥善保管管理員帳號密碼</li>
                        <li>建議定期備份資料庫</li>
                    </ul>
                    <a href="../index.php" class="btn btn-primary">前往首頁</a>
                    <a href="../admin/" class="btn btn-secondary">前往管理後台</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 