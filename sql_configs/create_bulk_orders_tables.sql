-- Create bulk_orders table
CREATE TABLE IF NOT EXISTS `bulk_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_order_id` varchar(20) NOT NULL UNIQUE,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','payment_received','ready_for_delivery','cancelled','completed') NOT NULL DEFAULT 'pending',
  `proof_of_payment` varchar(500) DEFAULT NULL,
  `admin_updated` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bulk_order_items table
CREATE TABLE IF NOT EXISTS `bulk_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_order_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bulk_order_id` (`bulk_order_id`),
  CONSTRAINT `bulk_order_items_ibfk_1` FOREIGN KEY (`bulk_order_id`) REFERENCES `bulk_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
