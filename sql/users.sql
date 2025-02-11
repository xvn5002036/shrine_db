-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 用戶表
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL COMMENT '用戶名',
    `password` varchar(255) NOT NULL COMMENT '密碼',
    `email` varchar(100) NOT NULL COMMENT '電子郵件',
    `name` varchar(100) DEFAULT NULL COMMENT '真實姓名',
    `role` enum('admin','staff','user') NOT NULL DEFAULT 'user' COMMENT '角色',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '狀態：0=停用，1=啟用',
    `avatar` varchar(255) DEFAULT NULL COMMENT '頭像',
    `last_login` datetime DEFAULT NULL COMMENT '最後登入時間',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 登入嘗試記錄表
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL COMMENT 'IP地址',
    `username` varchar(50) NOT NULL COMMENT '嘗試登入的用戶名',
    `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '嘗試時間',
    PRIMARY KEY (`id`),
    KEY `idx_ip_time` (`ip_address`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1; 
