-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 建立相簿資料表
DROP TABLE IF EXISTS `gallery_albums`;
CREATE TABLE `gallery_albums` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL COMMENT '相簿標題',
    `description` text DEFAULT NULL COMMENT '相簿描述',
    `event_date` date NOT NULL COMMENT '活動日期',
    `category` varchar(50) NOT NULL COMMENT '相簿分類',
    `cover_photo` varchar(255) DEFAULT NULL COMMENT '封面照片',
    `status` enum('published','draft') NOT NULL DEFAULT 'draft' COMMENT '發布狀態',
    `created_by` int(11) NOT NULL COMMENT '建立者ID',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),
    CONSTRAINT `fk_gallery_albums_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='相簿資料表';

-- 建立相片資料表
DROP TABLE IF EXISTS `gallery_photos`;
CREATE TABLE `gallery_photos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `album_id` int(11) NOT NULL COMMENT '所屬相簿ID',
    `filename` varchar(255) NOT NULL COMMENT '檔案名稱',
    `original_name` varchar(255) NOT NULL COMMENT '原始檔案名稱',
    `file_type` varchar(50) NOT NULL COMMENT '檔案類型',
    `file_size` int(11) NOT NULL COMMENT '檔案大小',
    `description` text DEFAULT NULL COMMENT '照片描述',
    `sort_order` int(11) DEFAULT 0 COMMENT '排序順序',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '上傳時間',
    PRIMARY KEY (`id`),
    KEY `idx_album_id` (`album_id`),
    CONSTRAINT `fk_gallery_photos_album_id` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='相片資料表';

SET FOREIGN_KEY_CHECKS = 1; 
