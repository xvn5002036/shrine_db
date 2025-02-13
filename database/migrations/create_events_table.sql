CREATE TABLE IF NOT EXISTS `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_type_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `image` varchar(255) DEFAULT NULL,
    `location` varchar(255) NOT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `registration_deadline` datetime DEFAULT NULL,
    `max_participants` int(11) DEFAULT NULL,
    `status` enum('draft', 'published') NOT NULL DEFAULT 'draft',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `event_type_id` (`event_type_id`),
    CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 