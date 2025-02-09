<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 處理分類過濾
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// 定義相簿分類
$categories = [
    '' => '全部相簿',
    'temple' => '宮廟建築',
    'ceremony' => '祭典活動',
    'collection' => '文物典藏',
    'festival' => '節慶活動'
];

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_started = false;
    try {
        // 檢查管理員ID是否存在
        if (!isset($_SESSION['admin_id'])) {
            throw new Exception('請先登入系統');
        }

        // 驗證管理員ID是否有效
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin' AND status = 1");
        $stmt->execute([$_SESSION['admin_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('無效的管理員帳號');
        }

        // 開始事務處理
        $pdo->beginTransaction();
        $transaction_started = true;

        // 驗證並處理相簿資訊
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_date = $_POST['event_date'];
        $status = $_POST['status'];
        $category = $_POST['category'];

        if (empty($title) || empty($event_date) || empty($category)) {
            throw new Exception('請填寫所有必填欄位');
        }

        // 插入相簿資訊
        $stmt = $pdo->prepare("
            INSERT INTO gallery_albums (
                title, description, event_date, category, 
                status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $title,
            $description,
            $event_date,
            $category,
            $status,
            $_SESSION['admin_id']
        ]);

        $album_id = $pdo->lastInsertId();

        // 處理上傳的圖片
        if (!empty($_FILES['photos']['name'][0])) {
            // 確保上傳目錄存在
            $upload_dir = $root_path . '/uploads/gallery/' . $album_id;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // 允許的圖片類型
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            // 處理每張圖片
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['photos']['type'][$key];
                    $file_size = $_FILES['photos']['size'][$key];
                    $file_name = $_FILES['photos']['name'][$key];

                    // 驗證檔案類型和大小
                    if (!in_array($file_type, $allowed_types)) {
                        continue;
                    }
                    if ($file_size > $max_file_size) {
                        continue;
                    }

                    // 生成唯一的檔案名
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_file_name = uniqid() . '.' . $extension;
                    $file_path = $upload_dir . '/' . $new_file_name;

                    // 移動檔案
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // 插入圖片資訊到資料庫
                        $stmt = $pdo->prepare("
                            INSERT INTO gallery_photos (
                                album_id, file_name, original_name, 
                                file_type, file_size, created_at
                            ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                        ");

                        $stmt->execute([
                            $album_id,
                            $new_file_name,
                            $file_name,
                            $file_type,
                            $file_size
                        ]);
                    }
                }
            }
        }

        // 提交事務
        $pdo->commit();
        $_SESSION['success'] = '相簿已成功建立';
        header('Location: upload.php');
        exit();

    } catch (Exception $e) {
        // 只在事務已開始時才進行回滾
        if ($transaction_started) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = '上傳失敗：' . $e->getMessage();
    }
}

// 獲取相簿列表
try {
    // 構建查詢條件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($category)) {
        $where_clause .= " AND a.category = :category";
        $params[':category'] = $category;
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM gallery_albums a $where_clause";
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 獲取相簿列表
    $sql = "
        SELECT a.*, u.username as created_by_name,
               (SELECT COUNT(*) FROM gallery_photos WHERE album_id = a.id) as photo_count
        FROM gallery_albums a
        LEFT JOIN users u ON a.created_by = u.id
        $where_clause
        ORDER BY a.created_at DESC
        LIMIT :offset, :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $albums = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching albums: ' . $e->getMessage());
    $albums = [];
    $total_pages = 0;
}

$page_title = '上傳相簿';
require_once '../includes/header.php';
?>

<!-- 在 body 開始處添加拖放提示 -->
<div id="dragOverlay" class="drag-overlay">
    <div class="drag-content">
        <i class="fas fa-cloud-upload-alt"></i>
        <h2>放開以上傳照片</h2>
    </div>
</div>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>相簿管理</h1>
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

        <!-- 分類過濾器 -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($categories as $key => $name): ?>
                        <a href="?category=<?php echo $key; ?>" 
                           class="btn <?php echo $category === $key ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 相簿列表 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">相簿列表</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>相簿標題</th>
                                <th>分類</th>
                                <th>活動日期</th>
                                <th>狀態</th>
                                <th>照片數量</th>
                                <th>建立者</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($albums)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">尚無相簿</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($albums as $album): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($album['title']); ?></td>
                                        <td><?php echo $categories[$album['category']] ?? '未分類'; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($album['event_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $album['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $album['status'] === 'published' ? '已發布' : '草稿'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $album['photo_count']; ?></td>
                                        <td><?php echo htmlspecialchars($album['created_by_name']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?php echo $album['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="編輯">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $album['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('確定要刪除此相簿嗎？此操作無法復原。')"
                                                   title="刪除">
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

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&category=<?php echo urlencode($category); ?>">
                                        上一頁
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&category=<?php echo urlencode($category); ?>">
                                        下一頁
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- 新增相簿區域 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">新增相簿</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- 上傳區域 -->
                    <div class="col-md-6">
                        <div class="upload-container">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-content">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <h3>拖放照片至此處上傳</h3>
                                    <p>或</p>
                                    <label for="fileInput" class="upload-btn">選擇檔案</label>
                                    <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
                                    <p class="upload-hint">支援 JPG、PNG 格式，單檔最大 5MB</p>
                                </div>
                            </div>
                            
                            <!-- 上傳預覽和進度 -->
                            <div class="upload-preview" id="uploadPreview">
                                <div class="preview-header">
                                    <h4>上傳項目</h4>
                                    <button type="button" class="btn btn-primary btn-sm start-upload" id="startUpload" style="display: none;">
                                        開始上傳
                                    </button>
                                </div>
                                <div class="preview-list" id="previewList"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 相簿資訊表單 -->
                    <div class="col-md-6">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="title" class="form-label">相簿標題 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback">請輸入相簿標題</div>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">相簿分類 <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">請選擇分類</option>
                                    <?php foreach ($categories as $key => $name): ?>
                                        <?php if ($key !== ''): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">請選擇相簿分類</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">相簿描述</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="event_date" class="form-label">活動日期 <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" required>
                                <div class="invalid-feedback">請選擇活動日期</div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">發布狀態</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft">草稿</option>
                                    <option value="published">發布</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 建立相簿
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 拖放覆蓋層樣式 */
.drag-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(99, 102, 241, 0.9);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
    pointer-events: none;
}

.drag-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease;
}

.drag-content {
    text-align: center;
    color: white;
}

.drag-content i {
    font-size: 5rem;
    margin-bottom: 1rem;
}

.drag-content h2 {
    font-size: 2rem;
    margin: 0;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* 修改上傳區域樣式 */
.upload-area {
    border: 2px dashed #cbd5e0;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
    background: #f8fafc;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
}

.upload-area.dragover {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
    transform: scale(1.02);
}

/* 預覽區域優化 */
.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.preview-card {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.preview-card:hover {
    transform: translateY(-2px);
}

/* 上傳區域樣式 */
.upload-container {
    margin-bottom: 2rem;
}

.upload-area {
    border: 2px dashed #cbd5e0;
    border-radius: 1rem;
    padding: 3rem;
    text-align: center;
    background: #f8fafc;
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-area.dragover {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.05);
}

.upload-icon {
    font-size: 4rem;
    color: #6366f1;
    margin-bottom: 1.5rem;
}

.upload-content h3 {
    font-size: 1.5rem;
    color: #1a202c;
    margin-bottom: 1rem;
}

.upload-btn {
    display: inline-block;
    padding: 0.8rem 2rem;
    background: #6366f1;
    color: #fff;
    border-radius: 50px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 1rem 0;
}

.upload-btn:hover {
    background: #4f46e5;
    transform: translateY(-2px);
}

.upload-hint {
    color: #64748b;
    font-size: 0.9rem;
    margin-top: 1rem;
}

/* 預覽區域樣式 */
.upload-preview {
    margin-top: 2rem;
    display: none;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.preview-header h4 {
    margin: 0;
    color: #1a202c;
}

.preview-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.preview-item {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.preview-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
}

.preview-info {
    padding: 0.75rem;
}

.preview-name {
    font-size: 0.9rem;
    color: #1a202c;
    margin-bottom: 0.5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.preview-size {
    font-size: 0.8rem;
    color: #64748b;
}

.preview-progress {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    margin-top: 0.5rem;
}

.progress-bar {
    height: 100%;
    background: #6366f1;
    border-radius: 2px;
    width: 0;
    transition: width 0.3s ease;
}

.preview-remove {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.preview-remove:hover {
    background: rgba(0, 0, 0, 0.7);
}

.upload-error {
    color: #dc2626;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dragOverlay = document.getElementById('dragOverlay');
    const mainContent = document.querySelector('.main-content');
    let dragCounter = 0;

    // 全域拖放事件處理
    document.addEventListener('dragenter', function(e) {
        e.preventDefault();
        dragCounter++;
        if (dragCounter === 1) {
            dragOverlay.classList.add('active');
        }
    });

    document.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dragCounter--;
        if (dragCounter === 0) {
            dragOverlay.classList.remove('active');
        }
    });

    document.addEventListener('dragover', function(e) {
        e.preventDefault();
    });

    document.addEventListener('drop', function(e) {
        e.preventDefault();
        dragCounter = 0;
        dragOverlay.classList.remove('active');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFiles(files);
        }
    });

    // 檔案處理函數
    function handleFiles(files) {
        const validFiles = Array.from(files).filter(file => {
            const isValid = validateFile(file);
            if (!isValid) {
                showNotification('檔案 ' + file.name + ' 不符合上傳要求', 'error');
            }
            return isValid;
        });

        if (validFiles.length === 0) return;

        // 顯示上傳預覽
        showUploadPreview(validFiles);
    }

    // 檔案驗證
    function validateFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!allowedTypes.includes(file.type)) {
            return false;
        }
        
        if (file.size > maxSize) {
            return false;
        }
        
        return true;
    }

    // 顯示通知
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // 顯示上傳預覽
    function showUploadPreview(files) {
        const previewContainer = document.getElementById('uploadPreview');
        const previewList = document.getElementById('previewList');
        
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-card';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" class="preview-image" alt="${file.name}">
                    <div class="preview-info">
                        <div class="preview-name">${file.name}</div>
                        <div class="preview-size">${formatFileSize(file.size)}</div>
                        <div class="preview-progress">
                            <div class="progress-bar"></div>
                        </div>
                    </div>
                `;
                previewList.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });

        previewContainer.style.display = 'block';
        document.getElementById('startUpload').style.display = 'block';
    }

    // 格式化檔案大小
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 
