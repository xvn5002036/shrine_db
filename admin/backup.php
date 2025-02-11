<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// 檢查是否登入
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 檢查是否有管理員權限
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// 設定備份目錄
$backup_dir = '../backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// 處理備份請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['backup_type'])) {
            switch ($_POST['backup_type']) {
                case 'database':
                    // 資料庫備份
                    $timestamp = date('Y-m-d_H-i-s');
                    $filename = "db_backup_{$timestamp}.sql";
                    $backup_path = $backup_dir . '/' . $filename;

                    // 使用 mysqldump 命令，修正路徑問題
                    $mysqldump_path = 'C:/xampp/mysql/bin/mysqldump.exe';
                    $command = sprintf(
                        '"%s" --host=%s --user=%s --password=%s --databases %s > "%s"',
                        $mysqldump_path,
                        DB_HOST,
                        DB_USER,
                        DB_PASS,
                        DB_NAME,
                        $backup_path
                    );
                    
                    // 執行命令並檢查結果
                    $output = [];
                    $return_var = 0;
                    
                    // 使用 system() 而不是 exec()
                    system($command, $return_var);
                    
                    if ($return_var === 0 && file_exists($backup_path)) {
                        // 記錄備份資訊到資料庫
                        $stmt = $pdo->prepare("
                            INSERT INTO backups (filename, type, created_by, created_at)
                            VALUES (?, 'database', ?, NOW())
                        ");
                        $stmt->execute([$filename, $_SESSION['admin_id']]);
                        
                        $_SESSION['success'] = '資料庫備份成功';
                    } else {
                        throw new Exception('資料庫備份失敗，請確認 mysqldump 路徑是否正確');
                    }
                    break;

                case 'files':
                    // 檔案備份
                    $timestamp = date('Y-m-d_H-i-s');
                    $filename = "files_backup_{$timestamp}.zip";
                    $backup_path = $backup_dir . '/' . $filename;

                    // 檢查 ZipArchive 是否可用
                    if (!class_exists('ZipArchive')) {
                        throw new Exception('系統未安裝 ZIP 擴展');
                    }

                    // 建立 ZIP 檔案
                    $zip = new ZipArchive();
                    if ($zip->open($backup_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                        // 添加上傳目錄
                        addFolderToZip('../uploads', $zip);
                        $zip->close();

                        if (file_exists($backup_path)) {
                            // 記錄備份資訊
                            $stmt = $pdo->prepare("
                                INSERT INTO backups (filename, type, created_by, created_at)
                                VALUES (?, 'files', ?, NOW())
                            ");
                            $stmt->execute([$filename, $_SESSION['admin_id']]);

                            $_SESSION['success'] = '檔案備份成功';
                        } else {
                            throw new Exception('備份檔案建立失敗');
                        }
                    } else {
                        throw new Exception('無法建立 ZIP 檔案');
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '備份失敗：' . $e->getMessage();
    }
    
    // 確保重定向使用完整路徑
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 獲取備份列表
try {
    $stmt = $pdo->query("
        SELECT b.*, u.username as created_by_name 
        FROM backups b 
        LEFT JOIN users u ON b.created_by = u.id 
        ORDER BY b.created_at DESC
    ");
    $backups = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching backups: ' . $e->getMessage());
    $backups = [];
}

$page_title = '備份管理';
require_once 'includes/header.php';

// 輔助函數：遞迴添加目錄到 ZIP
function addFolderToZip($folder, $zipArchive, $subfolder = '') {
    $handle = opendir($folder);
    while (false !== ($f = readdir($handle))) {
        if ($f != '.' && $f != '..') {
            $filePath = "$folder/$f";
            $localPath = $subfolder ? "$subfolder/$f" : $f;
            
            if (is_file($filePath)) {
                $zipArchive->addFile($filePath, $localPath);
            } elseif (is_dir($filePath)) {
                $zipArchive->addEmptyDir($localPath);
                addFolderToZip($filePath, $zipArchive, $localPath);
            }
        }
    }
    closedir($handle);
}
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>備份管理</h1>
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

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 備份操作 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">建立備份</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="backup_type" value="database">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database"></i> 備份資料庫
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST">
                            <input type="hidden" name="backup_type" value="files">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-archive"></i> 備份檔案
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 備份列表 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">備份列表</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>檔案名稱</th>
                                <th>類型</th>
                                <th>建立者</th>
                                <th>建立時間</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backups)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">尚無備份記錄</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($backup['filename']); ?></td>
                                        <td>
                                            <?php if ($backup['type'] === 'database'): ?>
                                                <span class="badge bg-primary">資料庫</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">檔案</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($backup['created_by_name']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($backup['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="download_backup.php?id=<?php echo $backup['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="delete_backup.php?id=<?php echo $backup['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('確定要刪除此備份？')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.backup-info {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.badge {
    padding: 0.5em 0.8em;
}
</style>

<?php require_once 'includes/footer.php'; ?> 
