<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once '../../config/config.php';
require_once '../includes/auth_check.php';
require_once '../../includes/db_connect.php';

// 檢查是否有提供ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = '無效的相簿ID';
    header('Location: upload.php');
    exit();
}

$id = (int)$_GET['id'];

// 定義相簿分類
$categories = [
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

        // 更新相簿資訊
        $stmt = $pdo->prepare("
            UPDATE gallery_albums 
            SET title = ?, description = ?, event_date = ?, 
                category = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $title,
            $description,
            $event_date,
            $category,
            $status,
            $id
        ]);

        // 處理上傳的新圖片
        if (!empty($_FILES['photos']['name'][0])) {
            // 確保上傳目錄存在
            $upload_dir = $root_path . '/uploads/gallery/' . $id;
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
                            $id,
                            $new_file_name,
                            $file_name,
                            $file_type,
                            $file_size
                        ]);
                    }
                }
            }
        }

        // 處理要刪除的圖片
        if (!empty($_POST['delete_photos'])) {
            foreach ($_POST['delete_photos'] as $photo_id) {
                // 獲取圖片資訊
                $stmt = $pdo->prepare("SELECT file_name FROM gallery_photos WHERE id = ? AND album_id = ?");
                $stmt->execute([$photo_id, $id]);
                $photo = $stmt->fetch();

                if ($photo) {
                    // 刪除實體檔案
                    $file_path = $root_path . '/uploads/gallery/' . $id . '/' . $photo['file_name'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }

                    // 從資料庫中刪除記錄
                    $stmt = $pdo->prepare("DELETE FROM gallery_photos WHERE id = ?");
                    $stmt->execute([$photo_id]);
                }
            }
        }

        // 提交事務
        $pdo->commit();
        $_SESSION['success'] = '相簿已成功更新';
        header('Location: upload.php');
        exit();

    } catch (Exception $e) {
        // 只在事務已開始時才進行回滾
        if ($transaction_started) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = '更新失敗：' . $e->getMessage();
    }
}

try {
    // 獲取相簿資訊
    $stmt = $pdo->prepare("
        SELECT a.*, u.username as created_by_name
        FROM gallery_albums a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $album = $stmt->fetch();

    if (!$album) {
        $_SESSION['error'] = '找不到指定的相簿';
        header('Location: upload.php');
        exit();
    }

    // 獲取相簿的所有照片
    $stmt = $pdo->prepare("
        SELECT * FROM gallery_photos 
        WHERE album_id = ? 
        ORDER BY sort_order ASC, created_at DESC
    ");
    $stmt->execute([$id]);
    $photos = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = '資料庫錯誤：' . $e->getMessage();
    header('Location: upload.php');
    exit();
}

$page_title = '編輯相簿';
require_once '../includes/header.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="toolbar">
                <h1>編輯相簿</h1>
                <div class="btn-toolbar">
                    <a href="upload.php" class="btn btn-secondary">
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

        <!-- 在 body 開始處添加拖放提示 -->
        <div id="dragOverlay" class="drag-overlay">
            <div class="drag-content">
                <i class="fas fa-cloud-upload-alt"></i>
                <h2>放開以上傳照片</h2>
            </div>
        </div>

        <div class="row">
            <!-- 相簿資訊表單 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">相簿資訊</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="title" class="form-label">相簿標題 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($album['title']); ?>" required>
                                <div class="invalid-feedback">請輸入相簿標題</div>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">相簿分類 <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">請選擇分類</option>
                                    <?php foreach ($categories as $key => $name): ?>
                                        <?php if ($key !== ''): ?>
                                            <option value="<?php echo $key; ?>" 
                                                <?php echo $album['category'] === $key ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">請選擇相簿分類</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">相簿描述</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                    echo htmlspecialchars($album['description']); 
                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="event_date" class="form-label">活動日期 <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" 
                                       value="<?php echo $album['event_date']; ?>" required>
                                <div class="invalid-feedback">請選擇活動日期</div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">發布狀態</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo $album['status'] === 'draft' ? 'selected' : ''; ?>>
                                        草稿
                                    </option>
                                    <option value="published" <?php echo $album['status'] === 'published' ? 'selected' : ''; ?>>
                                        發布
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 更新相簿
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 上傳區域 -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">照片管理</h5>
                    </div>
                    <div class="card-body">
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

                        <!-- 現有照片列表 -->
                        <div class="existing-photos mt-4">
                            <h5>現有照片</h5>
                            <div class="row g-3">
                                <?php if (empty($photos)): ?>
                                    <div class="col-12">
                                        <p class="text-center text-muted">尚無照片</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($photos as $photo): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="photo-card">
                                                <img src="/uploads/gallery/<?php echo $album['id']; ?>/<?php echo $photo['file_name']; ?>" 
                                                     class="img-fluid" alt="<?php echo htmlspecialchars($photo['original_name']); ?>">
                                                <div class="photo-card-overlay">
                                                    <button type="button" class="btn btn-sm btn-danger delete-photo" 
                                                            data-photo-id="<?php echo $photo['id']; ?>"
                                                            onclick="return confirm('確定要刪除此照片嗎？')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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

/* 現有照片樣式 */
.photo-card {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.photo-card img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
}

.photo-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-card:hover .photo-card-overlay {
    opacity: 1;
}

.delete-photo {
    padding: 0.5rem;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dragOverlay = document.getElementById('dragOverlay');
    const uploadArea = document.getElementById('uploadArea');
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

    // 上傳區域拖放處理
    uploadArea.addEventListener('dragenter', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFiles(files);
        }
    });

    // 點擊上傳區域觸發檔案選擇
    const fileInput = document.getElementById('fileInput');
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
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
        previewList.innerHTML = ''; // 清空現有預覽
        
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
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

    // 刪除照片
    document.querySelectorAll('.delete-photo').forEach(button => {
        button.addEventListener('click', function() {
            const photoId = this.dataset.photoId;
            if (confirm('確定要刪除此照片嗎？')) {
                // 發送刪除請求
                fetch(`delete_photo.php?id=${photoId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.col-md-6').remove();
                        showNotification('照片已成功刪除', 'success');
                    } else {
                        showNotification(data.message || '刪除失敗', 'error');
                    }
                })
                .catch(error => {
                    showNotification('刪除失敗', 'error');
                });
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 