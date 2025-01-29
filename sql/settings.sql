-- 網站設定表
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(50) NOT NULL,
    `value` text,
    `type` varchar(20) DEFAULT 'text',
    `group` varchar(50) DEFAULT 'general',
    `label` varchar(100) NOT NULL,
    `description` text,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入基本設定
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `label`, `description`, `sort_order`) VALUES
('site_name', '宮廟名稱', 'text', 'general', '網站名稱', '顯示在網站標題和其他地方的名稱', 1),
('site_description', '宮廟簡介', 'textarea', 'general', '網站描述', '簡短的網站描述，用於SEO和網站介紹', 2),
('site_keywords', '宮廟,祈福,點燈', 'text', 'general', '網站關鍵字', '用於SEO的關鍵字，以逗號分隔', 3),
('contact_email', 'admin@shrine.com', 'email', 'contact', '聯絡信箱', '主要的聯絡信箱', 4),
('contact_phone', '02-1234-5678', 'text', 'contact', '聯絡電話', '主要的聯絡電話', 5),
('contact_address', '台灣省某某縣某某市某某路123號', 'text', 'contact', '聯絡地址', '實體地址', 6),
('social_facebook', '', 'text', 'social', 'Facebook連結', 'Facebook粉絲專頁網址', 7),
('social_line', '', 'text', 'social', 'Line ID', '官方Line帳號', 8),
('business_hours', '週一至週日 09:00-17:00', 'text', 'general', '營業時間', '宮廟開放時間', 9),
('maintenance_mode', '0', 'boolean', 'system', '維護模式', '是否開啟網站維護模式', 10),
('theme', 'default', 'text', 'appearance', '網站主題', '目前使用的網站主題', 11),
('footer_text', '© 2024 宮廟名稱 版權所有', 'text', 'general', '頁尾文字', '顯示在網站底部的版權文字', 12); 