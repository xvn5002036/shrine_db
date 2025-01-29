<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

$errors = [];
$success = '';

// 檢查是否需要執行自動備份
try {
    // 檢查是否啟用自動備份
    $stmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'auto_backup_enabled'");
    $auto_backup_enabled = $stmt->fetchColumn();

    if ($auto_backup_enabled) {
        // 獲取自動備份時間
        $stmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'auto_backup_time'");
        $backup_time = $stmt->fetchColumn();
        
        // 獲取最後一次自動備份的時間
        $stmt = $pdo->prepare("SELECT created_at FROM backups 
                              WHERE backup_type = 'auto' AND status = 'success'
                              ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $last_backup = $stmt->fetchColumn();

        // 如果沒有備份記錄，或最後備份時間超過24小時
        if (!$last_backup || strtotime($last_backup) < strtotime('today ' . $backup_time)) {
            // 當前時間是否已超過設定的備份時間
            if (date('H:i') >= $backup_time) {
                // 建立備份目錄
                $backup_dir = '../../backups/';
                if (!file_exists($backup_dir)) {
                    mkdir($backup_dir, 0777, true);
                }

                // 生成備份檔案名稱
                $timestamp = date('Y-m-d_H-i');
                $backup_file = $backup_dir . 'backup_' . $timestamp . '.sql';

                // 檢查 mysqldump 是否存在
                $mysqldump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
                if (!file_exists($mysqldump_path)) {
                    throw new Exception('找不到 mysqldump.exe，請確認 XAMPP 安裝路徑');
                }

                // 建立備份指令
                $cmd = sprintf(
                    '"%s" --host="%s" --user="%s" --password="%s" --add-drop-table --default-character-set=utf8mb4 --complete-insert --routines --triggers --single-transaction %s > "%s" 2>&1',
                    $mysqldump_path,
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME,
                    $backup_file
                );

                // 執行備份並捕獲輸出
                $output = [];
                $return_var = -1;
                exec($cmd, $output, $return_var);

                if ($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
                    // 檢查檔案內容
                    $file_content = file_get_contents($backup_file);
                    if (!empty($file_content) && strpos($file_content, 'CREATE TABLE') !== false) {
                        // 記錄備份資訊到資料庫
                        $stmt = $pdo->prepare("INSERT INTO backups (filename, file_size, backup_type, status, created_at) 
                                             VALUES (:filename, :file_size, :backup_type, :status, NOW())");
                        $stmt->execute([
                            ':filename' => 'backup_' . $timestamp . '.sql',
                            ':file_size' => filesize($backup_file),
                            ':backup_type' => 'auto',
                            ':status' => 'success'
                        ]);

                        // 清理過期備份
                        $stmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'auto_backup_keep_days'");
                        $keep_days = (int)$stmt->fetchColumn();

                        if ($keep_days > 0) {
                            $expired_date = date('Y-m-d H:i:s', strtotime("-{$keep_days} days"));
                            
                            // 獲取過期的備份記錄
                            $stmt = $pdo->prepare("SELECT * FROM backups WHERE created_at < ? AND backup_type = 'auto'");
                            $stmt->execute([$expired_date]);
                            $expired_backups = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($expired_backups as $backup) {
                                // 刪除實體檔案
                                $expired_file = $backup_dir . $backup['filename'];
                                if (file_exists($expired_file)) {
                                    unlink($expired_file);
                                }

                                // 從資料庫中刪除記錄
                                $stmt = $pdo->prepare("DELETE FROM backups WHERE id = ?");
                                $stmt->execute([$backup['id']]);
                            }
                        }

                        setFlashMessage('success', '自動備份已完成！');
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $errors[] = '自動備份檢查失敗：' . $e->getMessage();
}

// 處理手動備份請求
if (isset($_POST['action']) && $_POST['action'] === 'backup') {
    try {
        // 檢查是否有其他備份正在進行
        $lock_file = dirname(dirname(__DIR__)) . '/backups/backup.lock';
        if (file_exists($lock_file) && (time() - filemtime($lock_file)) < 300) {
            throw new Exception('另一個備份程序正在執行中，請稍後再試');
        }

        // 建立鎖定檔案
        file_put_contents($lock_file, date('Y-m-d H:i:s'));

        try {
            // 檢查 mysqldump 是否存在
            $mysqldump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            if (!file_exists($mysqldump_path)) {
                throw new Exception('找不到 mysqldump.exe，請確認 XAMPP 安裝路徑');
            }

            // 建立備份目錄
            $backup_dir = dirname(dirname(__DIR__)) . '/backups/';
            if (!file_exists($backup_dir)) {
                if (!mkdir($backup_dir, 0777, true)) {
                    throw new Exception('無法建立備份目錄');
                }
            }

            // 檢查目錄是否可寫
            if (!is_writable($backup_dir)) {
                throw new Exception('備份目錄沒有寫入權限');
            }

            // 生成備份檔案名稱
            $timestamp = date('Y-m-d_H-i');
            $backup_file = $backup_dir . 'backup_' . $timestamp . '.sql';

            // 建立備份指令（修改指令格式和參數）
            $cmd = sprintf(
                '"%s" --host="%s" --user="%s" --password="%s" --add-drop-table --default-character-set=utf8mb4 --complete-insert --routines --triggers --single-transaction %s > "%s" 2>&1',
                $mysqldump_path,
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                $backup_file
            );

            // 執行備份並捕獲輸出
            $output = [];
            $return_var = -1;
            exec($cmd, $output, $return_var);

            if ($return_var !== 0) {
                throw new Exception('備份執行失敗: ' . implode("\n", $output));
            }

            // 檢查備份檔案
            if (!file_exists($backup_file)) {
                throw new Exception('備份檔案未建立');
            }

            if (filesize($backup_file) === 0) {
                throw new Exception('備份檔案大小為0');
            }

            // 檢查檔案內容
            $file_content = file_get_contents($backup_file);
            if (empty($file_content) || !strpos($file_content, 'CREATE TABLE')) {
                throw new Exception('備份檔案內容無效');
            }

            // 記錄備份資訊到資料庫
            $stmt = $pdo->prepare("INSERT INTO backups (filename, file_size, backup_type, status, created_at) 
                                 VALUES (:filename, :file_size, :backup_type, :status, NOW())");
            $stmt->execute([
                ':filename' => 'backup_' . $timestamp . '.sql',
                ':file_size' => filesize($backup_file),
                ':backup_type' => 'manual',
                ':status' => 'success'
            ]);

            // 記錄操作日誌
            logAdminAction('建立備份', "手動建立資料庫備份：backup_{$timestamp}.sql");

            // 設置成功消息
            setFlashMessage('success', '備份已成功建立！');
        } finally {
            // 清理鎖定檔案
            if (file_exists($lock_file)) {
                unlink($lock_file);
            }
        }

        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $errors[] = '備份失敗：' . $e->getMessage();
        
        // 記錄失敗資訊到資料庫
        try {
            $stmt = $pdo->prepare("INSERT INTO backups (filename, backup_type, status, error_message, created_at) 
                                 VALUES (:filename, :backup_type, :status, :error_message, NOW())");
            $stmt->execute([
                ':filename' => isset($timestamp) ? 'backup_' . $timestamp . '.sql' : 'backup_failed.sql',
                ':backup_type' => 'manual',
                ':status' => 'failed',
                ':error_message' => $e->getMessage()
            ]);
        } catch (PDOException $e) {
            $errors[] = '記錄失敗資訊時發生錯誤：' . $e->getMessage();
        }
    }
}

// 處理刪除備份請求
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // 獲取備份資訊
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($backup) {
            // 構建完整的檔案路徑
            $backup_file = dirname(dirname(__DIR__)) . '/backups/' . $backup['filename'];
            
            // 檢查檔案是否存在並嘗試刪除
            if (file_exists($backup_file)) {
                // 確保檔案可寫入（可刪除）
                chmod($backup_file, 0777);
                
                // 嘗試刪除檔案
                if (!@unlink($backup_file)) {
                    // 如果刪除失敗，記錄錯誤訊息
                    $error = error_get_last();
                    throw new Exception('無法刪除實體備份檔案: ' . ($error['message'] ?? '未知錯誤'));
                }
            }

            // 從資料庫中刪除記錄
            $stmt = $pdo->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->execute([$_GET['delete']]);

            if ($stmt->rowCount() > 0) {
                // 記錄操作日誌
                logAdminAction('刪除備份', "刪除備份檔案：{$backup['filename']}");

                // 設置成功消息
                setFlashMessage('success', '備份已成功刪除！');
            } else {
                throw new Exception('無法從資料庫中刪除備份記錄');
            }

            header('Location: index.php');
            exit;
        } else {
            throw new Exception('找不到指定的備份記錄');
        }
    } catch (Exception $e) {
        $errors[] = '刪除失敗：' . $e->getMessage();
        setFlashMessage('error', '刪除失敗：' . $e->getMessage());
        header('Location: index.php');
        exit;
    }
}

// 處理下載備份請求
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    try {
        // 獲取備份資訊
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$_GET['download']]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($backup) {
            $file_path = '../../backups/' . $backup['filename'];
            if (file_exists($file_path)) {
                // 記錄操作日誌
                logAdminAction('下載備份', "下載備份檔案：{$backup['filename']}");

                // 設定檔案下載標頭
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
                header('Content-Length: ' . filesize($file_path));
                
                // 輸出檔案內容
                readfile($file_path);
                exit;
            }
        }
        // 如果檔案不存在，重定向回列表頁
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $errors[] = '下載失敗：' . $e->getMessage();
    }
}

// 獲取備份列表
try {
    // 設定分頁
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // 計算總記錄數
    $stmt = $pdo->query("SELECT COUNT(*) FROM backups");
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // 獲取備份記錄
    $stmt = $pdo->prepare("SELECT * FROM backups ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "讀取備份記錄失敗：" . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 備份管理</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <?php include '../includes/header.php'; ?>
            
            <div class="admin-content">
                <div class="content-header">
                    <h2>備份管理</h2>
                    <div class="content-header-actions">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="backup">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 建立備份
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($backups)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>檔案名稱</th>
                                        <th>檔案大小</th>
                                        <th>備份類型</th>
                                        <th>狀態</th>
                                        <th>建立時間</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                            <td><?php echo formatFileSize($backup['file_size']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $backup['backup_type'] === 'auto' ? 'info' : 'primary'; ?>">
                                                    <?php echo $backup['backup_type'] === 'auto' ? '自動' : '手動'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $backup['status'] === 'success' ? 'success' : 'danger'; ?>">
                                                    <?php echo $backup['status'] === 'success' ? '成功' : '失敗'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($backup['created_at'])); ?></td>
                                            <td>
                                                <a href="?download=<?php echo $backup['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-download"></i> 下載
                                                </a>
                                                <a href="?delete=<?php echo $backup['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('確定要刪除此備份檔案嗎？此操作無法復原！');">
                                                    <i class="fas fa-trash"></i> 刪除
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav class="pagination-container">
                                <ul class="pagination">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>目前沒有任何備份記錄</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 
