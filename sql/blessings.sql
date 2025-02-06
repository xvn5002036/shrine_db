SET FOREIGN_KEY_CHECKS=0;

-- 祈福類型表
CREATE TABLE IF NOT EXISTS `blessing_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '祈福項目名稱',
    `slug` varchar(100) NOT NULL COMMENT 'URL友好的名稱',
    `description` text COMMENT '描述',
    `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '價格',
    `duration` varchar(50) DEFAULT NULL COMMENT '持續時間',
    `max_daily_slots` int(11) DEFAULT NULL COMMENT '每日最大預約數',
    `image_path` varchar(255) DEFAULT NULL COMMENT '圖片路徑',
    `is_featured` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否特色項目',
    `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序順序',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '狀態：0=停用，1=啟用',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `status` (`status`),
    KEY `is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 祈福時段表
CREATE TABLE IF NOT EXISTS `blessing_time_slots` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type_id` int(11) NOT NULL COMMENT '祈福類型ID',
    `day_of_week` tinyint(1) NOT NULL COMMENT '星期幾（1-7）',
    `start_time` time NOT NULL COMMENT '開始時間',
    `end_time` time NOT NULL COMMENT '結束時間',
    `max_slots` int(11) NOT NULL DEFAULT '1' COMMENT '最大預約數',
    `is_available` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否可用',
    PRIMARY KEY (`id`),
    KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 特殊日期表
CREATE TABLE IF NOT EXISTS `blessing_special_dates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date NOT NULL COMMENT '特殊日期',
    `type_id` int(11) DEFAULT NULL COMMENT '祈福類型ID（NULL表示適用所有類型）',
    `description` varchar(255) DEFAULT NULL COMMENT '說明',
    `is_closed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否休息',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `date` (`date`),
    KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設的祈福類型
INSERT INTO `blessing_types` 
(`name`, `slug`, `description`, `price`, `duration`, `max_daily_slots`, `is_featured`, `sort_order`) 
VALUES 
('平安祈福', 'peace-blessing', '為您及家人祈求平安，消災解厄', 1000.00, '約30分鐘', 20, 1, 1),
('財運祈福', 'wealth-blessing', '祈求財運亨通，事業順遂', 1200.00, '約45分鐘', 15, 1, 2),
('姻緣祈福', 'love-blessing', '祈求良緣，促進感情和諧', 1500.00, '約60分鐘', 10, 1, 3),
('學業祈福', 'study-blessing', '祈求學業進步，考試順利', 800.00, '約30分鐘', 25, 0, 4),
('健康祈福', 'health-blessing', '祈求身體健康，病痛消除', 1000.00, '約40分鐘', 20, 0, 5);

-- 插入預設的時段
INSERT INTO `blessing_time_slots` 
(`type_id`, `day_of_week`, `start_time`, `end_time`, `max_slots`) 
SELECT 
    id as type_id,
    1 as day_of_week,
    '09:00:00' as start_time,
    '17:00:00' as end_time,
    max_daily_slots as max_slots
FROM `blessing_types`;

SET FOREIGN_KEY_CHECKS=1; 