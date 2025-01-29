<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// 檢查管理員是否已登入
checkAdminLogin();

// 獲取全域 PDO 實例
$pdo = $GLOBALS['pdo'];

$errors = [];
$success = '';

// 處理新增/編輯表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);

        if (empty($name)) {
            $errors[] = "請輸入類型名稱";
        }

        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    $sql = "INSERT INTO donation_types (name, description, sort_order, status, created_at) 
                           VALUES (:name, :description, :sort_order, :status, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':sort_order' => $sort_order,
                        ':status' => $status
                    ]);
                    
                    // 記錄操作日誌
                    logAdminAction('新增捐款類型', "新增捐款類型：{$name}");
                    
                    // 設置成功消息
                    setFlashMessage('success', '新增捐款類型成功！');
                    header('Location: types.php');
                    exit;
                } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
                    $sql = "UPDATE donation_types SET name = :name, description = :description, 
                           sort_order = :sort_order, status = :status, updated_at = NOW() 
                           WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':sort_order' => $sort_order,
                        ':status' => $status,
                        ':id' => $_POST['id']
                    ]);
                    
                    // 記錄操作日誌
                    logAdminAction('更新捐款類型', "更新捐款類型：{$name}");
                    
                    // 設置成功消息
                    setFlashMessage('success', '更新捐款類型成功！');
                    header('Location: types.php');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = '保存失敗：' . $e->getMessage();
            }
        }
    }
}

// 處理刪除請求
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // 檢查是否有相關的捐款記錄
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE donation_type_id = ?");
        $stmt->execute([$_GET['delete']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $errors[] = "此捐款類型已有相關捐款記錄，無法刪除";
        } else {
            $stmt = $pdo->prepare("DELETE FROM donation_types WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            // 記錄操作日誌
            logAdminAction('刪除捐款類型', "刪除捐款類型 ID：{$_GET['delete']}");
            
            // 設置成功消息
            setFlashMessage('success', '捐款類型已成功刪除！');
            header('Location: types.php');
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = '刪除失敗：' . $e->getMessage();
    }
}

// 獲取捐款類型列表
$types_stmt = $pdo->query("SELECT * FROM donation_types ORDER BY sort_order, id");
$types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取單個捐款類型（用於編輯）
$edit_type = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM donation_types WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_type = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> - 捐款類型管理</title>
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
                    <h2>捐款類型管理</h2>
                </div>
                
                <div class="content-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3><?php echo $edit_type ? '編輯捐款類型' : '新增捐款類型'; ?></h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="form">
                                        <input type="hidden" name="action" value="<?php echo $edit_type ? 'edit' : 'add'; ?>">
                                        <?php if ($edit_type): ?>
                                            <input type="hidden" name="id" value="<?php echo $edit_type['id']; ?>">
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label for="name">類型名稱 <span class="text-danger">*</span></label>
                                            <input type="text" id="name" name="name" required
                                                   value="<?php echo htmlspecialchars($edit_type['name'] ?? ''); ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="description">說明</label>
                                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_type['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label for="sort_order">排序</label>
                                            <input type="number" id="sort_order" name="sort_order" min="0"
                                                   value="<?php echo htmlspecialchars($edit_type['sort_order'] ?? '0'); ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="status">狀態</label>
                                            <select id="status" name="status">
                                                <option value="1" <?php echo (isset($edit_type['status']) && $edit_type['status'] == 1) ? 'selected' : ''; ?>>啟用</option>
                                                <option value="0" <?php echo (isset($edit_type['status']) && $edit_type['status'] == 0) ? 'selected' : ''; ?>>停用</option>
                                            </select>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <?php echo $edit_type ? '更新' : '新增'; ?>
                                            </button>
                                            <?php if ($edit_type): ?>
                                                <a href="types.php" class="btn btn-secondary">取消</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3>捐款類型列表</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>排序</th>
                                                    <th>類型名稱</th>
                                                    <th>說明</th>
                                                    <th>狀態</th>
                                                    <th>操作</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($types as $type): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($type['sort_order']); ?></td>
                                                        <td><?php echo htmlspecialchars($type['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($type['description'] ?? ''); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $type['status'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $type['status'] ? '啟用' : '停用'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="?edit=<?php echo $type['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                                            <a href="?delete=<?php echo $type['id']; ?>" class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('確定要刪除此捐款類型嗎？');">刪除</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>
</html> 