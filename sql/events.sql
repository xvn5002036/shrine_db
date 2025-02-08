-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 活動類型表
CREATE TABLE IF NOT EXISTS `event_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 活動表
CREATE TABLE IF NOT EXISTS `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_type_id` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `image` varchar(255) DEFAULT NULL,
    `location` varchar(255) NOT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `registration_deadline` datetime DEFAULT NULL,
    `max_participants` int(11) DEFAULT NULL,
    `current_participants` int(11) DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_type_id` (`event_type_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_start_date` (`start_date`),
    CONSTRAINT `fk_events_event_type_id` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 活動報名表
CREATE TABLE IF NOT EXISTS `event_registrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `participants` int(11) NOT NULL DEFAULT 1,
    `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_id` (`event_id`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_event_registrations_event_id` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_registrations_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設活動類型
INSERT INTO `event_types` (`name`, `description`, `status`, `sort_order`) VALUES
('法會活動', '各式法會與祈福活動', 'active', 1),
('節慶活動', '傳統節慶與慶典活動', 'active', 2),
('文化活動', '文化教育與推廣活動', 'active', 3),
('慈善活動', '慈善捐贈與關懷活動', 'active', 4);

-- 插入測試活動資料
INSERT INTO `events` (
    `event_type_id`, 
    `title`, 
    `description`, 
    `location`, 
    `start_date`, 
    `end_date`, 
    `registration_deadline`,
    `max_participants`
) VALUES
(1, '春季祈福大法會', 
   '一年一度春季祈福大法會，為信眾消災解厄，祈求平安。\n法會流程包括：\n1. 灑淨儀式\n2. 誦經祈福\n3. 消災祈安\n4. 圓滿功德', 
   '本宮大殿', 
   '2024-03-15 09:00:00', 
   '2024-03-15 17:00:00',
   '2024-03-10 23:59:59',
   200),
(2, '元宵節燈會活動', 
   '歡慶元宵節，本宮舉辦大型燈會活動。\n活動內容：\n1. 花燈展示\n2. 猜燈謎\n3. 民俗表演\n4. 祈福點燈', 
   '宮廟廣場', 
   '2024-02-24 18:00:00', 
   '2024-02-24 22:00:00',
   '2024-02-20 23:59:59',
   500),
(3, '傳統文化講座', 
   '邀請知名講師分享傳統文化知識。\n講座主題：\n1. 傳統節慶的意義\n2. 民間信仰與生活\n3. 宮廟文化的傳承', 
   '文化教室', 
   '2024-04-01 14:00:00', 
   '2024-04-01 16:00:00',
   '2024-03-28 23:59:59',
   50);

SET FOREIGN_KEY_CHECKS = 1; 