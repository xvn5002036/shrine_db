-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 系統設定表
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(50) NOT NULL COMMENT '設定鍵名',
    `setting_value` text COMMENT '設定值',
    `setting_group` varchar(20) NOT NULL DEFAULT 'general' COMMENT '設定群組',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設設定
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', '宮廟管理系統', 'site'),
('site_description', '宮廟管理系統是一個全方位的宮廟管理平台', 'site'),
('site_keywords', '宮廟,管理系統,祈福,活動', 'site'),
('site_email', 'admin@example.com', 'site'),
('site_phone', '02-1234-5678', 'site'),
('site_address', '台北市中正區範例路123號', 'site'),
('business_hours', '週一至週日 09:00-17:00', 'site'),
('maintenance_mode', '0', 'system'),
('registration_enabled', '1', 'system'),
('email_notification', '1', 'system'),
('items_per_page', '10', 'system');

SET FOREIGN_KEY_CHECKS = 1; 
