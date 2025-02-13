-- 設定資料庫編碼
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `shrine_db`;

-- 新聞分類表
CREATE TABLE IF NOT EXISTS `news_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `slug` varchar(50) NOT NULL,
    `description` text,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 新聞表
CREATE TABLE IF NOT EXISTS `news` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `image` varchar(255) DEFAULT NULL,
    `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
    `views` int(11) NOT NULL DEFAULT 0,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_news_category_id` FOREIGN KEY (`category_id`) REFERENCES `news_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_news_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入預設新聞分類
INSERT INTO `news_categories` (`name`, `slug`, `description`, `status`, `sort_order`) VALUES
('宮廟公告', 'announcements', '重要公告與最新消息', 'active', 1),
('活動快訊', 'events', '近期活動與法會資訊', 'active', 2),
('祈福服務', 'blessings', '祈福與點燈服務資訊', 'active', 3),
('宮廟文化', 'culture', '宮廟文化與傳統習俗介紹', 'active', 4);

-- 插入測試新聞
INSERT INTO `news` (`category_id`, `title`, `slug`, `content`, `status`, `created_at`) VALUES
(1, '農曆新年開放時間公告', 'lunar-new-year-opening-hours', '農曆新年期間（除夕至初五）本宮將24小時開放，方便信眾參拜。\n初一至初五將有特別祈福法會，歡迎參加。', 'published', NOW()),
(2, '2024年春季祈福法會', 'spring-blessing-ceremony-2024', '2024年春季祈福法會將於3月份舉行，活動內容包括：\n1. 集體祈福\n2. 安太歲\n3. 點光明燈\n請信眾踴躍參加。', 'published', NOW()),
(3, '清明節祭祖服務開放預約', 'qingming-festival-service-booking', '本宮清明節祭祖服務已開放預約，提供以下服務：\n1. 祖先牌位安奉\n2. 清明祭祀\n3. 祖先超渡\n詳情請洽詢服務台。', 'published', NOW());

-- 為現有的新聞記錄生成 slug
UPDATE news 
SET slug = CONCAT('news-', id) 
WHERE slug = '' OR slug IS NULL;

SET FOREIGN_KEY_CHECKS = 1; 