-- 備份記錄表
CREATE TABLE IF NOT EXISTS `backups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `file_size` BIGINT DEFAULT 0,
    `backup_type` ENUM('manual', 'auto') NOT NULL DEFAULT 'manual',
    `status` ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    `error_message` TEXT,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_backup_type` (`backup_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 清空現有資料（如果需要重新建立）
TRUNCATE TABLE `backups`; 