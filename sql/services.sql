-- 服務類型表
CREATE TABLE IF NOT EXISTS `service_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL COMMENT '服務類型名稱',
    `slug` varchar(50) NOT NULL,
    `description` text COMMENT '類型描述',
    `sort_order` int(11) DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設服務類型
INSERT INTO `service_types` (`name`, `slug`, `description`, `sort_order`) VALUES
('祈福點燈', 'light', '點燈祈福服務', 1),
('安太歲', 'zodiac', '安太歲服務', 2),
('法會超渡', 'ceremony', '法會超渡服務', 3),
('宮廟祭祀', 'worship', '宮廟祭祀服務', 4);

-- 服務項目表
CREATE TABLE IF NOT EXISTS `services` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type_id` int(11) NOT NULL COMMENT '服務類型ID',
    `name` varchar(255) NOT NULL COMMENT '服務名稱',
    `slug` varchar(255) NOT NULL,
    `description` text NOT NULL COMMENT '服務描述',
    `price` decimal(10,2) DEFAULT NULL COMMENT '服務價格',
    `duration` varchar(50) DEFAULT NULL COMMENT '服務時長',
    `image` varchar(255) DEFAULT NULL COMMENT '服務圖片',
    `is_featured` tinyint(1) DEFAULT 0 COMMENT '是否特色服務',
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `booking_required` tinyint(1) DEFAULT 0 COMMENT '是否需要預約',
    `max_participants` int(11) DEFAULT NULL COMMENT '最大參與人數',
    `notice` text COMMENT '注意事項',
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_type` (`type_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_updated_by` (`updated_by`),
    CONSTRAINT `fk_services_type` FOREIGN KEY (`type_id`) REFERENCES `service_types` (`id`),
    CONSTRAINT `fk_services_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_services_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 服務預約表
CREATE TABLE IF NOT EXISTS `service_bookings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `service_id` int(11) NOT NULL COMMENT '服務ID',
    `user_id` int(11) NOT NULL COMMENT '用戶ID',
    `booking_date` date NOT NULL COMMENT '預約日期',
    `booking_time` time NOT NULL COMMENT '預約時間',
    `participant_count` int(11) DEFAULT 1 COMMENT '參與人數',
    `participant_names` text COMMENT '參與者姓名',
    `special_requests` text COMMENT '特殊要求',
    `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending' COMMENT '預約狀態',
    `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid' COMMENT '付款狀態',
    `total_amount` decimal(10,2) NOT NULL COMMENT '總金額',
    `notes` text COMMENT '備註',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_service` (`service_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_booking_date` (`booking_date`),
    CONSTRAINT `fk_bookings_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 服務時段設定表
CREATE TABLE IF NOT EXISTS `service_schedules` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `service_id` int(11) NOT NULL COMMENT '服務ID',
    `day_of_week` tinyint(1) NOT NULL COMMENT '星期幾(0-6)',
    `start_time` time NOT NULL COMMENT '開始時間',
    `end_time` time NOT NULL COMMENT '結束時間',
    `max_bookings` int(11) DEFAULT NULL COMMENT '每個時段最大預約數',
    `status` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_service_schedule` (`service_id`),
    CONSTRAINT `fk_schedule_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 