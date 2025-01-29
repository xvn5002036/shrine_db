-- 祈福項目類型表
CREATE TABLE IF NOT EXISTS `blessing_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `slug` varchar(50) NOT NULL,
    `description` text,
    `price` decimal(10,2) DEFAULT NULL,
    `duration` varchar(50) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 祈福登記表
CREATE TABLE IF NOT EXISTS `blessings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `recipient_name` varchar(100) NOT NULL,
    `recipient_birthdate` date DEFAULT NULL,
    `recipient_address` varchar(255) DEFAULT NULL,
    `blessing_date` date NOT NULL,
    `special_requests` text,
    `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    `blessing_status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    `amount` decimal(10,2) NOT NULL,
    `payment_method` varchar(50) DEFAULT NULL,
    `payment_reference` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_blessing_date` (`blessing_date`),
    CONSTRAINT `fk_blessings_type` FOREIGN KEY (`type_id`) REFERENCES `blessing_types` (`id`),
    CONSTRAINT `fk_blessings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 祈福排程表
CREATE TABLE IF NOT EXISTS `blessing_schedules` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `blessing_id` int(11) NOT NULL,
    `scheduled_date` date NOT NULL,
    `scheduled_time` time NOT NULL,
    `priest_id` int(11) DEFAULT NULL,
    `location` varchar(100) DEFAULT NULL,
    `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_blessing` (`blessing_id`),
    KEY `idx_priest` (`priest_id`),
    KEY `idx_schedule` (`scheduled_date`, `scheduled_time`),
    CONSTRAINT `fk_schedules_blessing` FOREIGN KEY (`blessing_id`) REFERENCES `blessings` (`id`),
    CONSTRAINT `fk_schedules_priest` FOREIGN KEY (`priest_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 