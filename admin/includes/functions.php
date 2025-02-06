<?php
// 格式化日期時間
function formatDateTime($datetime) {
    return date('Y-m-d H:i', strtotime($datetime));
}

// 格式化金額
function formatAmount($amount) {
    return number_format($amount);
}

// 產生分頁連結
function generatePagination($current_page, $total_pages, $url_pattern = '?page=%d') {
    $links = [];
    
    // 前一頁
    if ($current_page > 1) {
        $links[] = sprintf('<li class="page-item"><a class="page-link" href="' . $url_pattern . '"><i class="fas fa-chevron-left"></i></a></li>', $current_page - 1);
    }

    // 頁碼
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);

    if ($start_page > 1) {
        $links[] = sprintf('<li class="page-item"><a class="page-link" href="' . $url_pattern . '">1</a></li>', 1);
        if ($start_page > 2) {
            $links[] = '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $links[] = sprintf('<li class="page-item active"><span class="page-link">%d</span></li>', $i);
        } else {
            $links[] = sprintf('<li class="page-item"><a class="page-link" href="' . $url_pattern . '">%d</a></li>', $i, $i);
        }
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $links[] = '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $links[] = sprintf('<li class="page-item"><a class="page-link" href="' . $url_pattern . '">%d</a></li>', $total_pages, $total_pages);
    }

    // 下一頁
    if ($current_page < $total_pages) {
        $links[] = sprintf('<li class="page-item"><a class="page-link" href="' . $url_pattern . '"><i class="fas fa-chevron-right"></i></a></li>', $current_page + 1);
    }

    return implode('', $links);
}

// 產生狀態標籤
function generateStatusBadge($status, $text) {
    $class = [
        'pending' => 'bg-warning',
        'confirmed' => 'bg-success',
        'cancelled' => 'bg-danger',
        'completed' => 'bg-info',
        'active' => 'bg-success',
        'inactive' => 'bg-secondary'
    ];

    return sprintf('<span class="badge %s">%s</span>', 
        $class[$status] ?? 'bg-secondary',
        htmlspecialchars($text)
    );
}

// 檢查並創建目錄
function ensureDirectoryExists($path) {
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

// 產生唯一檔名
function generateUniqueFilename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

// 檢查檔案類型
function isAllowedFileType($file, $allowed_types) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mime_type, $allowed_types);
}

// 處理檔案上傳
function handleFileUpload($file, $upload_dir, $allowed_types = null, $max_size = null) {
    try {
        // 檢查上傳錯誤
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('檔案上傳失敗');
        }

        // 檢查檔案大小
        if ($max_size && $file['size'] > $max_size) {
            throw new Exception('檔案大小超過限制');
        }

        // 檢查檔案類型
        if ($allowed_types && !isAllowedFileType($file, $allowed_types)) {
            throw new Exception('不支援的檔案類型');
        }

        // 確保目錄存在
        ensureDirectoryExists($upload_dir);

        // 產生唯一檔名
        $filename = generateUniqueFilename($file['name']);
        $filepath = $upload_dir . '/' . $filename;

        // 移動檔案
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('檔案移動失敗');
        }

        return $filename;
    } catch (Exception $e) {
        error_log($e->getMessage());
        throw $e;
    }
}
?> 