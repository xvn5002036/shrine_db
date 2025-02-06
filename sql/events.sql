-- 活動類型表
CREATE TABLE IF NOT EXISTS `event_types` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 活動表
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_type_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `event_date` DATE NOT NULL,
    `event_time` TIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `max_participants` INT DEFAULT 0,
    `current_participants` INT DEFAULT 0,
    `status` ENUM('draft', 'published', 'cancelled') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_type_id) REFERENCES event_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入預設活動類型
INSERT INTO `event_types` (`name`, `description`) VALUES
('法會活動', '各類佛教法會與祈福活動'),
('節慶活動', '傳統節慶與慶典活動'),
('文化活動', '文化教育與藝術展演活動'),
('公益活動', '慈善與社會服務活動');

-- 插入範例活動
INSERT INTO `events` (
    `event_type_id`,
    `title`,
    `description`,
    `event_date`,
    `event_time`,
    `location`,
    `max_participants`,
    `status`
) VALUES
(1, '秋季祈福法會', '一年一度的秋季祈福法會，為信眾祈福消災。', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), '09:00:00', '大殿', 100, 'published'),
(2, '中元普渡', '傳統中元普渡法會，普施各路好兄弟。', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), '10:00:00', '廣場', 200, 'published'),
(3, '佛教藝術展', '展出多件珍貴的佛教文物與藝術作品。', DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY), '13:00:00', '文化館', 50, 'published'),
(4, '冬季送暖活動', '為弱勢族群送上溫暖的冬衣與物資。', DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), '14:00:00', '社區中心', 30, 'published'); 