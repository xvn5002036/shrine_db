<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// 確保用戶已登入且為管理員
adminOnly();

// 處理備份建立
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 驗證輸入
        if (empty($_POST['backup_content'])) {
            throw new Exception('請選擇備份內容');
        }

        // 建立備份目錄
        $backup_dir = '../../backups/' . date('Y/m/d');
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }

        // 設定備份檔案名稱
        $timestamp = date('YmdHis');
        $filename = "backup_{$timestamp}";
        
        // 根據選擇的壓縮方式設定副檔名
        switch ($_POST['compression_type']) {
            case 'zip':
                $filename .= '.zip';
                break;
            case 'gzip':
                $filename .= '.sql.gz';
                break;
            default:
                $filename .= '.sql';
        }

        $file_path = 'backups/' . date('Y/m/d') . '/' . $filename;
        $full_path = '../../' . $file_path;

        // 執行備份
        $backup_content = $_POST['backup_content'];
        $success = false;
        $error_message = '';

        // 建立備份
        switch ($backup_content) {
            case 'database':
                // 資料庫備份
                $command = sprintf(
                    'mysqldump -h %s -u %s %s %s > %s',
                    DB_HOST,
                    DB_USER,
                    DB_PASS ? '-p' . DB_PASS : '',
                    DB_NAME,
                    $full_path
                );
                
                if ($_POST['compression_type'] === 'gzip') {
                    $command .= ' | gzip';
                }
                
                exec($command, $output, $return_var);
                $success = ($return_var === 0);
                break;

            case 'files':
                // 檔案備份
                if ($_POST['compression_type'] === 'zip') {
                    $zip = new ZipArchive();
                    if ($zip->open($full_path, ZipArchive::CREATE) === TRUE) {
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator('../../uploads/')
                        );
                        foreach ($iterator as $file) {
                            if (!$file->isDir()) {
                                $zip->addFile($file->getPathname(), 'uploads/' . $iterator->getSubPathName());
                            }
                        }
                        $zip->close();
                        $success = true;
                    }
                }
                break;

            case 'all':
                // 完整備份（資料庫 + 檔案）
                if ($_POST['compression_type'] === 'zip') {
                    // 先備份資料庫
                    $db_file = tempnam(sys_get_temp_dir(), 'db_');
                    $command = sprintf(
                        'mysqldump -h %s -u %s %s %s > %s',
                        DB_HOST,
                        DB_USER,
                        DB_PASS ? '-p' . DB_PASS : '',
                        DB_NAME,
                        $db_file
                    );
                    exec($command);

                    // 建立 ZIP 檔案
                    $zip = new ZipArchive();
                    if ($zip->open($full_path, ZipArchive::CREATE) === TRUE) {
                        // 加入資料庫備份
                        $zip->addFile($db_file, 'database.sql');

                        // 加入檔案
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator('../../uploads/')
                        );
                        foreach ($iterator as $file) {
                            if (!$file->isDir()) {
                                $zip->addFile($file->getPathname(), 'uploads/' . $iterator->getSubPathName());
                            }
                        }
                        $zip->close();
                        unlink($db_file);
                        $success = true;
                    }
                }
                break;
        }

        // 更新資料庫
        $stmt = $pdo->prepare("
            INSERT INTO backups (
                filename, file_path, file_size, backup_type,
                backup_content, compression_type, status,
                error_message, created_by, created_at,
                retention_days
            ) VALUES (
                ?, ?, ?, 'manual',
                ?, ?, ?,
                ?, ?, NOW(),
                ?
            )
        ");

        $file_size = file_exists($full_path) ? filesize($full_path) : 0;
        
        $stmt->execute([
            $filename,
            $file_path,
            $file_size,
            $backup_content,
            $_POST['compression_type'],
            $success ? 'success' : 'failed',
            $error_message,
            $_SESSION['user_id'],
            $_POST['retention_days']
        ]);

        if ($success) {
            $_SESSION['success'] = '備份已成功建立';
            header('Location: index.php');
            exit;
        } else {
            throw new Exception('備份建立失敗');
        }

    } catch (Exception $e) {
        $_SESSION['error'] = '建立備份時發生錯誤：' . $e->getMessage();
    }
}

// 頁面標題
$page_title = '建立備份';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
            </div>

            <?php include '../includes/message.php'; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label">備份內容 <span class="text-danger">*</span></label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="backup_content" 
                                                   id="backup_content_database" value="database" required>
                                            <label class="form-check-label" for="backup_content_database">
                                                資料庫
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="backup_content" 
                                                   id="backup_content_files" value="files">
                                            <label class="form-check-label" for="backup_content_files">
                                                檔案
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="backup_content" 
                                                   id="backup_content_all" value="all">
                                            <label class="form-check-label" for="backup_content_all">
                                                全部
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">壓縮方式</label>
                                    <select class="form-select" name="compression_type">
                                        <option value="none">不壓縮</option>
                                        <option value="zip" selected>ZIP</option>
                                        <option value="gzip">GZIP</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="retention_days" class="form-label">保留天數</label>
                                    <input type="number" class="form-control" id="retention_days" 
                                           name="retention_days" value="30" min="1" max="365">
                                    <div class="form-text">設定備份檔案的保留天數（1-365天）</div>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 建立備份
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> 返回列表
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">備份說明</h5>
                        </div>
                        <div class="card-body">
                            <h6>備份內容</h6>
                            <ul>
                                <li><strong>資料庫</strong>：備份所有資料表的資料</li>
                                <li><strong>檔案</strong>：備份上傳的圖片等檔案</li>
                                <li><strong>全部</strong>：同時備份資料庫和檔案</li>
                            </ul>

                            <h6>壓縮方式</h6>
                            <ul>
                                <li><strong>不壓縮</strong>：直接儲存原始檔案</li>
                                <li><strong>ZIP</strong>：使用 ZIP 格式壓縮（推薦）</li>
                                <li><strong>GZIP</strong>：使用 GZIP 格式壓縮（僅適用於資料庫備份）</li>
                            </ul>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 建議定期進行備份，並將備份檔案存放在安全的位置。
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 