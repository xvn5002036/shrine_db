-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 祈福服務類型表
DROP TABLE IF EXISTS `blessing_types`;
CREATE TABLE `blessing_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '類型名稱',
    `description` text COMMENT '類型說明',
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態：1啟用，0停用',
    `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否為特色類型',
    `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設祈福服務類型
INSERT INTO `blessing_types` (`name`, `description`, `status`, `is_featured`, `sort_order`) VALUES
('安太歲', '為信眾安奉太歲，祈求平安順遂', 1, 1, 1),
('光明燈', '為信眾點燈祈福，照亮前程', 1, 1, 2),
('平安祈福', '為信眾消災解厄，祈求平安', 1, 1, 3),
('姻緣祈福', '為信眾祈求良緣，圓滿姻緣', 1, 0, 4),
('財運祈福', '為信眾祈求財運亨通', 1, 0, 5);

-- 祈福服務表
DROP TABLE IF EXISTS `blessings`;
CREATE TABLE `blessings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type_id` int(11) NOT NULL COMMENT '服務類型ID',
    `name` varchar(255) NOT NULL COMMENT '服務名稱',
    `description` text COMMENT '服務說明',
    `price` decimal(10,2) NOT NULL COMMENT '服務價格',
    `image` varchar(255) DEFAULT NULL COMMENT '服務圖片',
    `duration` varchar(50) DEFAULT NULL COMMENT '服務期間',
    `max_participants` int(11) DEFAULT NULL COMMENT '最大參與人數',
    `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序順序',
    `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '狀態',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type_id` (`type_id`),
    CONSTRAINT `fk_blessings_type_id` FOREIGN KEY (`type_id`) REFERENCES `blessing_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設祈福服務
INSERT INTO `blessings` (`type_id`, `name`, `description`, `price`, `duration`, `max_participants`, `status`, `sort_order`) VALUES
(1, '安太歲服務', '為信眾安奉太歲，化解流年運勢，祈求平安順遂。', 600.00, '一年', 1, 'active', 10),
(2, '光明燈祈福', '為信眾點燈祈福，照亮前程，消除障礙。', 1200.00, '一年', 1, 'active', 20),
(3, '平安祈福法會', '舉行祈福法會，為信眾消災解厄，祈求平安。', 800.00, '一次', 10, 'active', 30),
(4, '姻緣祈福服務', '為信眾祈求姻緣，尋求良緣。', 1000.00, '三個月', 1, 'active', 40),
(5, '財運祈福服務', '為信眾祈求財運亨通，事業順遂。', 1500.00, '三個月', 1, 'active', 50);

-- 祈福服務預約表
DROP TABLE IF EXISTS `blessing_registrations`;
CREATE TABLE `blessing_registrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `blessing_id` int(11) NOT NULL COMMENT '服務ID',
    `user_id` int(11) DEFAULT NULL COMMENT '用戶ID',
    `name` varchar(100) NOT NULL COMMENT '預約者姓名',
    `phone` varchar(20) NOT NULL COMMENT '聯絡電話',
    `email` varchar(100) NOT NULL COMMENT '電子郵件',
    `participants` int(11) NOT NULL DEFAULT 1 COMMENT '參與人數',
    `special_requests` text COMMENT '特殊需求',
    `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '預約狀態',
    `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid' COMMENT '付款狀態',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_blessing_id` (`blessing_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_blessing_registrations_blessing_id` FOREIGN KEY (`blessing_id`) REFERENCES `blessings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_blessing_registrations_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;