CREATE TABLE IF NOT EXISTS `contact_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設資料
INSERT INTO `contact_info` (`type`, `value`, `icon`, `status`, `sort_order`) VALUES
('address', '台北市中正區重慶南路一段2號', 'fas fa-map-marker-alt', 1, 1),
('phone', '(02) 2345-6789', 'fas fa-phone', 1, 2),
('email', 'info@example.com', 'fas fa-envelope', 1, 3),
('opening_hours_weekday', '平日：早上 6:00 - 晚上 21:00', 'far fa-clock', 1, 4),
('opening_hours_weekend', '假日：早上 5:30 - 晚上 22:00', 'far fa-clock', 1, 5); 