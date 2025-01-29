-- 創建捐款類型表（先創建被參照的表）
CREATE TABLE IF NOT EXISTS `donation_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '類型名稱',
  `description` text DEFAULT NULL COMMENT '說明',
  `sort_order` int unsigned DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '狀態',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='捐款類型';

-- 插入預設捐款類型
INSERT INTO `donation_types` (`name`, `description`, `sort_order`) VALUES 
('一般捐款', '支持寺廟日常運作與維護', 1),
('法會贊助', '贊助各項法會活動', 2),
('建設基金', '寺廟建設與修繕基金', 3),
('功德主', '成為寺廟功德主', 4),
('其他捐款', '其他用途捐款', 5);

-- 創建捐款記錄表
CREATE TABLE IF NOT EXISTS `donations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `donor_name` varchar(100) NOT NULL COMMENT '捐款人姓名',
  `contact` varchar(100) NOT NULL COMMENT '聯絡方式',
  `amount` decimal(10,2) NOT NULL COMMENT '捐款金額',
  `donation_type_id` int(10) unsigned DEFAULT NULL COMMENT '捐款類型',
  `donation_date` date NOT NULL COMMENT '捐款日期',
  `payment_method` varchar(50) NOT NULL COMMENT '付款方式',
  `receipt_number` varchar(50) DEFAULT NULL COMMENT '收據編號',
  `purpose` varchar(255) DEFAULT NULL COMMENT '捐款用途',
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '狀態',
  `notes` text DEFAULT NULL COMMENT '備註',
  `processed_by` int(10) unsigned DEFAULT NULL COMMENT '處理人員',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT '處理時間',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '建立時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時間',
  PRIMARY KEY (`id`),
  KEY `idx_donor_name` (`donor_name`),
  KEY `idx_donation_date` (`donation_date`),
  KEY `idx_status` (`status`),
  KEY `idx_processed_by` (`processed_by`),
  KEY `idx_donation_type` (`donation_type_id`),
  CONSTRAINT `fk_donations_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_donations_type` FOREIGN KEY (`donation_type_id`) REFERENCES `donation_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='捐款記錄'; 