-- Create product_reviews table
-- This table stores customer reviews for products

CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'Order ID to verify purchase',
  `rating` tinyint(1) NOT NULL COMMENT 'Rating from 1 to 5',
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0 COMMENT 'Admin approval status',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Featured review flag',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `is_approved` (`is_approved`),
  UNIQUE KEY `unique_user_product_review` (`user_id`, `product_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for better query performance
CREATE INDEX `idx_reviews_product_approved` ON `product_reviews` (`product_id`, `is_approved`, `created_at` DESC);

