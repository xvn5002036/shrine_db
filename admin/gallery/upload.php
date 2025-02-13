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
                                album_id, filename, original_name, 
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
               (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) as photo_count
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
                                <div class="upload-hint">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    <p>拖放照片到此處或點擊選擇照片</p>
                                    <small>支援 JPG、PNG、GIF 格式，單檔最大 5MB</small>
                                </div>
                                <input type="file" id="photoInput" multiple accept="image/*" style="display: none;">
                            </div>
                            
                            <!-- 預覽區域 -->
                            <div id="previewArea" class="preview-area" style="display: none;">
                                <div class="preview-header">
                                    <h3>已選擇的照片</h3>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addMorePhotos">
                                        <i class="fas fa-plus"></i> 新增更多照片
                                    </button>
                                </div>
                                <div id="photoPreview" class="row g-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 相簿資訊表單 -->
                    <div class="col-md-6">
                        <div id="albumForm" class="album-form mt-4" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title mb-4">相簿資訊</h3>
                            <div class="mb-3">
                                        <label for="albumTitle" class="form-label">相簿標題 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="albumTitle" required>
                            </div>
                            <div class="mb-3">
                                        <label for="albumDescription" class="form-label">相簿描述</label>
                                        <textarea class="form-control" id="albumDescription" rows="3"></textarea>
                            </div>
                                    <div class="row">
                                        <div class="col-md-6">
                            <div class="mb-3">
                                                <label for="eventDate" class="form-label">活動日期 <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="eventDate" required>
                            </div>
                            </div>
                                        <div class="col-md-6">
                            <div class="mb-3">
                                                <label for="category" class="form-label">分類 <span class="text-danger">*</span></label>
                                                <select class="form-select" id="category" name="category" required>
                                                    <option value="">請選擇分類</option>
                                                    <option value="temple">宮廟建築</option>
                                                    <option value="ceremony">祭典活動</option>
                                                    <option value="collection">文物典藏</option>
                                                    <option value="festival">節慶活動</option>
                                </select>
                            </div>
                                        </div>
                                    </div>
                            <div class="mb-3">
                                        <label class="form-label">發布狀態</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" id="statusDraft" value="draft" checked>
                                            <label class="form-check-label" for="statusDraft">
                                                儲存為草稿
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" id="statusPublished" value="published">
                                            <label class="form-check-label" for="statusPublished">
                                                立即發布
                                            </label>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-primary" id="submitAlbum">
                                    <i class="fas fa-save"></i> 建立相簿
                                </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area.dragover {
    border-color: #4a90e2;
    background-color: #f8f9fa;
}

.upload-hint {
    color: #666;
}

.upload-hint i {
    font-size: 48px;
    color: #999;
    margin-bottom: 10px;
}

.preview-area {
    margin-top: 20px;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.preview-item {
    position: relative;
    margin-bottom: 15px;
}

.preview-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 4px;
}

.btn-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 4px;
    color: white;
    z-index: 1000;
}

.notification.error {
    background-color: #dc3545;
}

.notification.success {
    background-color: #28a745;
}

.progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    width: 0%;
    background-color: #4a90e2;
    z-index: 1000;
    animation: progress 2s ease-in-out infinite;
}

@keyframes progress {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

.drag-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.drag-zone.dragover {
    background-color: #e9ecef;
    border-color: #0d6efd;
}

.drag-zone i {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 10px;
}

#preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.preview-item {
    position: relative;
    width: 100px;
    height: 100px;
}

.preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.preview-item .remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const photoInput = document.getElementById('photoInput');
    const previewArea = document.getElementById('previewArea');
    const photoPreview = document.getElementById('photoPreview');
    const albumForm = document.getElementById('albumForm');
    const addMorePhotos = document.getElementById('addMorePhotos');
    const dragOverlay = document.getElementById('dragOverlay');
    let uploadedFiles = [];

    // 為整個文件添加拖放事件
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        document.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // 顯示拖放覆蓋層
    ['dragenter', 'dragover'].forEach(eventName => {
        document.addEventListener(eventName, function() {
            dragOverlay.classList.add('active');
        }, false);
    });

    // 隱藏拖放覆蓋層
    ['dragleave', 'drop'].forEach(eventName => {
        document.addEventListener(eventName, function(e) {
            // 只有當滑鼠離開視窗或完成拖放時才隱藏覆蓋層
            if (eventName === 'dragleave' && e.clientY > 0) {
                return;
            }
            dragOverlay.classList.remove('active');
        }, false);
    });

    // 處理整個文件的拖放
    document.addEventListener('drop', (e) => {
        handleFiles(e.dataTransfer.files);
    });

    // 點擊上傳區域
    uploadArea.addEventListener('click', () => {
        photoInput.click();
    });

    photoInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    // 新增更多照片
    addMorePhotos.addEventListener('click', () => {
        photoInput.click();
    });

    // 處理檔案
    function handleFiles(files) {
        const validFiles = Array.from(files).filter(file => {
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!validTypes.includes(file.type)) {
                showNotification('檔案格式不支援：' + file.name);
                return false;
            }
            if (file.size > maxSize) {
                showNotification('檔案超過 5MB 限制：' + file.name);
                return false;
            }
            return true;
        });

        if (validFiles.length === 0) {
            showNotification('沒有可用的圖片檔案');
            return;
        }

        uploadedFiles = uploadedFiles.concat(validFiles);
        showUploadPreview();
        uploadArea.style.display = 'none';
        previewArea.style.display = 'block';
        albumForm.style.display = 'block';
    }

    // 顯示預覽
    function showUploadPreview() {
        photoPreview.innerHTML = '';
        uploadedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'col-md-3';
                div.innerHTML = `
                    <div class="preview-item">
                        <img src="${e.target.result}" class="img-fluid" alt="預覽圖">
                        <button type="button" class="btn-remove" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                photoPreview.appendChild(div);

                // 綁定刪除按鈕事件
                div.querySelector('.btn-remove').addEventListener('click', function() {
                    uploadedFiles.splice(this.dataset.index, 1);
                    showUploadPreview();
                    if (uploadedFiles.length === 0) {
                        uploadArea.style.display = 'block';
                        previewArea.style.display = 'none';
                        albumForm.style.display = 'none';
                    }
                });
            };
            reader.readAsDataURL(file);
        });
    }

    // 提交相簿
    document.getElementById('submitAlbum').addEventListener('click', function() {
        const title = document.getElementById('albumTitle').value.trim();
        const description = document.getElementById('albumDescription').value.trim();
        const eventDate = document.getElementById('eventDate').value;
        const category = document.getElementById('category').value;
        const status = document.querySelector('input[name="status"]:checked').value;

        if (!title || !eventDate || !category) {
            showNotification('請填寫必要欄位');
            return;
        }

        if (uploadedFiles.length === 0) {
            showNotification('請至少上傳一張照片');
            return;
        }

        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('event_date', eventDate);
        formData.append('category', category);
        formData.append('status', status);

        uploadedFiles.forEach((file, index) => {
            formData.append(`photos[${index}]`, file);
        });

        // 顯示上傳進度
        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        document.body.appendChild(progressBar);

        fetch('upload_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('相簿建立成功', 'success');
                setTimeout(() => {
                    window.location.href = 'gallery.php';
                }, 1500);
            } else {
                throw new Error(data.message || '上傳失敗');
            }
        })
        .catch(error => {
            showNotification(error.message);
        })
        .finally(() => {
            progressBar.remove();
        });
    });

    // 通知函數
    function showNotification(message, type = 'error') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 
