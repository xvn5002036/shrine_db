-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 用戶資料表
DROP TABLE IF EXISTS `addusers`;
CREATE TABLE `addusers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL COMMENT '用戶名',
    `password` varchar(255) NOT NULL COMMENT '密碼',
    `email` varchar(100) NOT NULL COMMENT '電子郵件',
    `phone` varchar(20) DEFAULT NULL COMMENT '電話',
    `first_name` varchar(50) DEFAULT NULL COMMENT '名字',
    `last_name` varchar(50) DEFAULT NULL COMMENT '姓氏',
    `role` enum('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色',
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1啟用，0停用',
    `last_login` datetime DEFAULT NULL COMMENT '最後登入時間',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1; 
