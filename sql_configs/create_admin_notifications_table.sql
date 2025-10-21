-- Create admin notifications table
CREATE TABLE IF NOT EXISTS `admin_notifications` (
    `notif_id` int(11) NOT NULL AUTO_INCREMENT,
    `notif_type` enum('order_new','order_status','order_warning','bulk_new','bulk_status','bulk_payment') NOT NULL,
    `notif_title` varchar(255) NOT NULL,
    `notif_message` text NOT NULL,
    `notif_link` varchar(500) DEFAULT NULL,
    `notif_reference_id` int(11) DEFAULT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`notif_id`),
    KEY `notif_type` (`notif_type`),
    KEY `is_read` (`is_read`),
    KEY `created_at` (`created_at`),
    KEY `notif_reference_id` (`notif_reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;