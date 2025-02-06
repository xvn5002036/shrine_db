<?php
/**
 * 顯示系統訊息
 */

// 檢查是否有快閃訊息
if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $message) {
        $type = $message['type'] ?? 'info';
        $text = $message['message'] ?? '';
        
        // 根據類型設置對應的Bootstrap樣式類別
        $alertClass = 'alert-info';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
        }
        
        // 輸出訊息
        if (!empty($text)) {
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($text);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
    
    // 清除快閃訊息
    unset($_SESSION['flash_messages']);
}

// 檢查是否有一般訊息
if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
    foreach ($_SESSION['messages'] as $message) {
        $type = $message['type'] ?? 'info';
        $text = $message['message'] ?? '';
        
        // 根據類型設置對應的Bootstrap樣式類別
        $alertClass = 'alert-info';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
        }
        
        // 輸出訊息
        if (!empty($text)) {
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($text);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
    
    // 清除訊息
    unset($_SESSION['messages']);
}
?> 