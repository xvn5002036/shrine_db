-- 活動類型表
CREATE TABLE IF NOT EXISTS `event_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `slug` varchar(50) NOT NULL,
    `description` text,
    `sort_order` int(11) DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 活動表
CREATE TABLE IF NOT EXISTS `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_type_id` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `image` varchar(255) DEFAULT NULL,
    `location` varchar(255) DEFAULT NULL,
    `event_date` datetime NOT NULL,
    `registration_url` varchar(255) DEFAULT NULL,
    `max_participants` int(11) DEFAULT NULL,
    `registration_deadline` datetime DEFAULT NULL,
    `is_featured` tinyint(1) DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`event_type_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_updated_by` (`updated_by`),
    CONSTRAINT `fk_events_type` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
    CONSTRAINT `fk_events_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 活動報名表
CREATE TABLE IF NOT EXISTS `event_registrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event_user` (`event_id`, `user_id`),
    KEY `idx_user` (`user_id`),
    CONSTRAINT `fk_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
    CONSTRAINT `fk_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設的活動類型
INSERT INTO `event_types` (`name`, `slug`, `description`, `sort_order`) VALUES
('一般活動', 'general', '一般性的活動和集會', 1),
('法會', 'dharma', '佛教法會和儀式', 2),
('節慶活動', 'festival', '傳統節日慶典活動', 3),
('教育活動', 'education', '佛學講座和教育課程', 4),
('義工服務', 'volunteer', '社區服務和義工活動', 5); 