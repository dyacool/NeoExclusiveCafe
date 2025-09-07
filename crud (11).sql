-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2025 at 03:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crud`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_content`
--

CREATE TABLE `about_content` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `about_text` text NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_content`
--

INSERT INTO `about_content` (`id`, `title`, `about_text`, `image_path`, `updated_at`) VALUES
(1, 'About Us ', 'Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', '/NeoExclusiveCafe/images/about_1747371905.jpg', '2025-05-16 05:14:35');

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'Full administrative access to all features', '2025-04-10 03:28:07');

-- --------------------------------------------------------

--
-- Table structure for table `admin_role_permissions`
--

CREATE TABLE `admin_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `availtoday_order_limit`
--

CREATE TABLE `availtoday_order_limit` (
  `id` int(11) NOT NULL,
  `limit_orders` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `availtoday_order_limit`
--

INSERT INTO `availtoday_order_limit` (`id`, `limit_orders`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-08-13 00:00:51', '2025-08-13 08:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `availtoday_status`
--

CREATE TABLE `availtoday_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `availtoday_status`
--

INSERT INTO `availtoday_status` (`id`, `name`) VALUES
(1, 'Pick Up'),
(2, 'Delivery');

-- --------------------------------------------------------

--
-- Table structure for table `availtoday_timer`
--

CREATE TABLE `availtoday_timer` (
  `id` int(11) NOT NULL,
  `timer_value` time NOT NULL COMMENT 'The time value for the available today timer (e.g., 17:00:00)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether this timer is currently active',
  `description` varchar(255) DEFAULT 'Available today timer' COMMENT 'Description of what this timer represents',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `availtoday_timer`
--

INSERT INTO `availtoday_timer` (`id`, `timer_value`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(1, '17:00:00', 1, 'Default closing time for available today products', '2025-08-10 04:18:01', '2025-08-10 04:18:01');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `description`) VALUES
(1, 'General', 'General blog posts'),
(2, 'News', 'News and updates'),
(3, 'Tutorial', 'How-to guides and tutorials'),
(4, 'Review', 'Product reviews and feedback');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `description`, `image_path`, `author`, `created_at`) VALUES
(14, 'Making a beautiful sourdough loaf of bread', 'From flour to flavor – crafting the perfect sourdough loaf at home.', 'IMG_1551.JPG', 'Admin', '2025-05-03 22:49:50'),
(15, 'Basic Sourdough That I bake everyday', 'I’ve been baking sourdough for 5 years at home now, as our family really like to have a fresh bread for breakfast. ', '43115680_Unknown.JPG', 'Admin', '2025-05-03 22:50:46'),
(16, 'The Sourdough Business is Booming', 'Sourdough isn\\\'t just a baking trend—it’s now a growing business. ', '43384736_Unknown.JPG', 'Admin', '2025-05-03 22:53:19'),
(17, 'Cassava Cake Available for Pre-Order', 'Freshly baked cassava cake, rich and creamy—now available for pre-order! Perfect for any occasion or simple cravings.', '43116464_Unknown.JPG', 'Admin', '2025-05-03 22:54:29'),
(18, 'A Day in the Life of a Sourdough Baker', 'Take a behind-the-scenes look at the daily routine, challenges, and joy of baking fresh sourdough for our community.', '42851456_Unknown.JPG', 'Admin', '2025-05-03 22:57:56'),
(19, 'From Orders to Oven: How to Pre-Order Your Fresh Sourdough', 'Want fresh sourdough at your doorstep? Here\\\'s how our pre-order system works and why it\\\'s the best way to get your favorite loaf.', '469949382_1047363840407701_4773448878841417009_n.jpg', 'Admin', '2025-05-03 22:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `business_hours`
--

CREATE TABLE `business_hours` (
  `id` int(11) NOT NULL,
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `business_hours`
--

INSERT INTO `business_hours` (`id`, `opening_time`, `closing_time`, `created_at`, `updated_at`) VALUES
(1, '08:00:00', '23:42:00', '2025-08-10 03:51:16', '2025-08-17 13:47:48');

-- --------------------------------------------------------

--
-- Table structure for table `carousel_images`
--

CREATE TABLE `carousel_images` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_images`
--

INSERT INTO `carousel_images` (`id`, `image_url`, `title`, `display_order`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'images/carousel/1746540642_43384544_Unknown.JPG', 'Sourdough 1', 1, 1, '2025-05-06 14:10:42', '2025-08-03 06:45:13', 5, 5),
(5, 'images/carousel/1746967185_43384736_Unknown.JPG', 'Sourdough 2', 3, 1, '2025-05-11 12:39:45', '2025-08-03 06:45:13', 5, 5),
(6, 'images/carousel/1746967636_44186560_Unknown.JPG', 'Sourdough 3', 2, 1, '2025-05-11 12:47:16', '2025-08-03 06:45:13', 5, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `carousel_settings`
--

CREATE TABLE `carousel_settings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `button_text` varchar(100) NOT NULL,
  `button_link` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_settings`
--

INSERT INTO `carousel_settings` (`id`, `title`, `description`, `button_text`, `button_link`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'Welcome Back!', 'Explore our latest selection of freshly baked breads and products—made daily with love and quality ingredients.', 'Explore Menu', '/NeoExclusiveCafe/pages/users/user-products.php', '2025-05-06 14:03:12', '2025-05-06 14:28:15', 5, 5);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(70, 21, 19, 1, 130.00, '2025-05-16 09:08:07', '2025-05-16 09:08:07'),
(77, 4, 24, 1, 70.00, '2025-05-16 17:55:13', '2025-05-16 17:55:13'),
(81, 31, 14, 1, 220.00, '2025-05-17 01:10:45', '2025-05-17 01:10:45'),
(87, 5, 24, 3, 70.00, '2025-05-23 02:32:02', '2025-05-23 02:39:56'),
(94, 22, 12, 2, 220.00, '2025-05-23 03:23:26', '2025-05-23 03:23:28'),
(96, 22, 15, 1, 250.00, '2025-05-23 03:25:32', '2025-05-23 03:25:32'),
(97, 22, 23, 1, 175.00, '2025-05-23 03:25:48', '2025-05-23 03:25:48'),
(100, 35, 14, 1, 220.00, '2025-05-23 04:50:08', '2025-05-23 04:50:08'),
(102, 3, 14, 1, 220.00, '2025-08-03 22:31:57', '2025-08-03 22:31:57'),
(103, 3, 15, 1, 250.00, '2025-08-04 08:46:54', '2025-08-04 08:46:54'),
(104, 3, 27, 1, 180.00, '2025-08-04 09:59:29', '2025-08-08 05:56:19'),
(105, 3, 33, 1, 44.00, '2025-08-04 10:21:17', '2025-08-04 10:21:17'),
(106, 3, 34, 2, 3.33, '2025-08-04 10:21:18', '2025-08-04 10:27:01'),
(107, 3, 5, 3, 32.00, '2025-08-13 12:16:30', '2025-08-30 00:27:27'),
(108, 3, 6, 2, 4.44, '2025-08-13 12:58:56', '2025-08-13 19:55:36');

-- --------------------------------------------------------

--
-- Table structure for table `cart_availtoday`
--

CREATE TABLE `cart_availtoday` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cart items for Available Today products';

--
-- Dumping data for table `cart_availtoday`
--

INSERT INTO `cart_availtoday` (`id`, `user_id`, `product_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(1, 3, 7, 1, 40.44, '2025-08-30 07:35:26', '2025-08-30 07:35:26');

--
-- Triggers `cart_availtoday`
--
DELIMITER $$
CREATE TRIGGER `update_cart_availToday_timestamp` BEFORE UPDATE ON `cart_availtoday` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_faq`
--

CREATE TABLE `chatbot_faq` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_faq`
--

INSERT INTO `chatbot_faq` (`id`, `question`, `answer`, `created_at`) VALUES
(1, 'Mode of delivery', '-Lalamove\n-Grab \n-Toktok(for nearby villages)', '2025-05-10 02:54:52'),
(6, 'What are the ingredients you use?', 'Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.', '2025-05-12 01:33:08'),
(7, 'Do you have physical store?', 'We don\'t have a physical store yet, just deliveries.', '2025-05-12 01:34:02'),
(1, 'Mode of delivery', '-Lalamove\n-Grab \n-Toktok(for nearby villages)', '2025-05-10 02:54:52'),
(6, 'What are the ingredients you use?', 'Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.', '2025-05-12 01:33:08'),
(7, 'Do you have physical store?', 'We don\'t have a physical store yet, just deliveries.', '2025-05-12 01:34:02'),
(1, 'Mode of delivery', '-Lalamove\n-Grab \n-Toktok(for nearby villages)', '2025-05-10 02:54:52'),
(6, 'What are the ingredients you use?', 'Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.', '2025-05-12 01:33:08'),
(7, 'Do you have physical store?', 'We don\'t have a physical store yet, just deliveries.', '2025-05-12 01:34:02'),
(1, 'Mode of delivery', '-Lalamove\n-Grab \n-Toktok(for nearby villages)', '2025-05-10 02:54:52'),
(6, 'What are the ingredients you use?', 'Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.', '2025-05-12 01:33:08'),
(7, 'Do you have physical store?', 'We don\'t have a physical store yet, just deliveries.', '2025-05-12 01:34:02'),
(1, 'Mode of delivery', '-Lalamove\n-Grab \n-Toktok(for nearby villages)', '2025-05-10 02:54:52'),
(6, 'What are the ingredients you use?', 'Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.', '2025-05-12 01:33:08'),
(7, 'Do you have physical store?', 'We don\'t have a physical store yet, just deliveries.', '2025-05-12 01:34:02'),
(1, 'Mode of delivery', '-Lalamove\n-Grab \n-Toktok(for nearby villages)', '2025-05-10 02:54:52'),
(6, 'What are the ingredients you use?', 'Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.', '2025-05-12 01:33:08'),
(7, 'Do you have physical store?', 'We don\'t have a physical store yet, just deliveries.', '2025-05-12 01:34:02');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_knowledge`
--

CREATE TABLE `chatbot_knowledge` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_knowledge`
--

INSERT INTO `chatbot_knowledge` (`id`, `content`, `updated_at`) VALUES
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:23:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:27:10'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:42:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:51:19'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:02:32'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:03:01'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:23:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:27:10'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:42:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:51:19'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:02:32'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:03:01'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-16 18:07:28'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:23:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:27:10'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:42:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:51:19'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:02:32'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:03:01'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-22 12:48:44'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nSPX \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:27:25'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nSPX \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:28:53'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nSPX \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:30:05'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:30:43'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:23:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:27:10'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:42:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:51:19'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:02:32'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:03:01'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:23:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:27:10'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:42:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:51:19'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:02:32'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:03:01'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-16 18:07:28'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:23:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:27:10'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:42:20'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 15:51:19'),
(0, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:02:32'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-15 16:03:01');
INSERT INTO `chatbot_knowledge` (`id`, `content`, `updated_at`) VALUES
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-22 12:48:44'),
(0, 'The chatbot is about online bread store named NeoCafe, and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nSPX \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:27:25'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nSPX \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:28:53'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nSPX \nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:30:05'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and this are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-23 03:30:43');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `contact`, `address`, `created_at`) VALUES
(1, '09238412372', 'Pickup at store', '2025-04-29 06:47:17'),
(2, '09327548312', NULL, '2025-05-01 07:24:30'),
(3, '09327548312', NULL, '2025-05-01 07:27:59'),
(4, '09327548312', NULL, '2025-05-01 07:30:01'),
(5, '09327548312', NULL, '2025-05-01 07:31:37'),
(6, '09327548312', NULL, '2025-05-01 07:32:36'),
(7, '09327548312', NULL, '2025-05-01 07:49:37'),
(8, '09327548312', NULL, '2025-05-01 07:51:42'),
(9, '09327548312', NULL, '2025-05-01 07:54:17'),
(10, '09327548312', NULL, '2025-05-01 08:03:41'),
(11, '09327548312', NULL, '2025-05-01 08:07:52'),
(12, '09327548312', NULL, '2025-05-01 08:14:06'),
(13, '09327548312', 'Brgy. Santa Elena (Pob.), Santa Elena, Camarines Norte, Region V (Bicol Region)', '2025-05-01 08:45:05'),
(14, '09327548312', NULL, '2025-05-02 11:21:33'),
(15, '09327548312', 'Brgy. Santa Elena (Pob.), Santa Elena, Camarines Norte, Region V (Bicol Region)', '2025-05-02 12:14:51'),
(16, '09327548312', 'Brgy. Santa Elena (Pob.), Santa Elena, Camarines Norte, Region V (Bicol Region)', '2025-05-02 12:47:38'),
(17, '09327548312', NULL, '2025-05-02 12:51:29'),
(18, '09327548312', NULL, '2025-05-02 12:59:47'),
(19, '09327548312', NULL, '2025-05-02 14:35:51'),
(20, '09327548312', '', '2025-05-03 02:04:49'),
(21, '09327548312', 'Brgy. Barangay V (Pob.), Baler (Capital), Aurora, Region III (Central Luzon)', '2025-05-03 02:14:07'),
(22, '09327548312', 'Brgy. Santa Elena (Pob.), Santa Elena, Camarines Norte, Region V (Bicol Region)', '2025-05-03 02:18:12'),
(23, '09327548312', NULL, '2025-05-03 03:04:18'),
(24, '09127589340', NULL, '2025-05-15 17:45:31'),
(25, '09127589340', NULL, '2025-05-16 03:29:41'),
(26, '09127589340', NULL, '2025-05-16 04:34:08'),
(27, '09123456789', NULL, '2025-05-16 15:28:46'),
(28, '09123456789', NULL, '2025-05-16 15:37:50'),
(29, '09123456789', NULL, '2025-05-16 15:42:21'),
(30, '09123456789', NULL, '2025-05-16 15:53:25'),
(31, '09123456789', NULL, '2025-05-16 16:08:33'),
(32, '09123456789', NULL, '2025-05-16 16:12:15'),
(33, '09123456789', NULL, '2025-05-16 18:05:35'),
(34, '09123456789', NULL, '2025-05-22 06:32:24'),
(35, '09123456789', 'Brgy. Barangay 9 (Pob.), Lucena City (Capital), Quezon, Region IV-A (CALABARZON)', '2025-05-22 07:02:22'),
(36, '09123456789', NULL, '2025-05-23 03:04:46'),
(37, '09123456789', 'tabing ilog, Brgy. San Pioquinto, Malvar, Batangas, Region IV-A (CALABARZON)', '2025-05-23 03:16:29'),
(38, '11111111111', 'Brgy. San Pedro II (Western), Malvar, Batangas, Region IV-A (CALABARZON)', '2025-05-23 04:44:28');

-- --------------------------------------------------------

--
-- Table structure for table `date_limits`
--

CREATE TABLE `date_limits` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `limit_value` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `not_accepting_orders` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `date_limits`
--

INSERT INTO `date_limits` (`id`, `date`, `limit_value`, `created_at`, `updated_at`, `not_accepting_orders`) VALUES
(8, '2025-04-30', 3, '2025-04-26 08:33:10', '2025-04-26 09:39:41', 0),
(9, '2025-05-02', 0, '2025-04-26 09:39:24', '2025-04-26 09:39:24', 1),
(10, '2025-04-28', 3, '2025-04-26 09:39:56', '2025-04-26 09:39:56', 0),
(11, '2025-05-03', 0, '2025-04-29 05:58:03', '2025-04-29 23:59:12', 1),
(12, '2025-05-04', 1, '2025-04-30 14:02:48', '2025-04-30 14:02:48', 0),
(13, '2025-05-05', 1, '2025-05-01 08:44:09', '2025-05-01 09:36:48', 0),
(14, '2025-05-07', 0, '2025-05-02 13:01:54', '2025-05-02 13:01:54', 1),
(15, '2025-05-17', 1, '2025-05-15 17:29:32', '2025-05-15 17:33:22', 0),
(16, '2025-05-23', 1, '2025-05-15 17:29:40', '2025-05-22 07:06:26', 0),
(17, '2025-05-18', 1, '2025-05-15 17:29:48', '2025-05-15 17:30:05', 0),
(18, '2025-05-21', 6, '2025-05-15 17:30:01', '2025-05-15 17:30:01', 0),
(19, '2025-05-28', 0, '2025-05-23 04:56:29', '2025-05-23 04:56:29', 1),
(20, '2025-07-10', 3, '2025-07-09 09:10:25', '2025-07-09 09:10:30', 0),
(22, '2025-08-09', 0, '2025-08-07 12:29:29', '2025-08-07 12:29:29', 1),
(23, '2025-08-11', 0, '2025-08-09 06:39:10', '2025-08-09 06:39:10', 1),
(25, '2025-08-13', 0, '2025-08-13 00:18:21', '2025-08-13 00:18:21', 1),
(27, '2025-08-23', 0, '2025-08-23 13:56:40', '2025-08-23 13:56:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `footer_settings`
--

CREATE TABLE `footer_settings` (
  `id` int(11) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `facebook_link` varchar(255) NOT NULL,
  `instagram_link` varchar(255) NOT NULL,
  `email_link` varchar(255) NOT NULL,
  `map_iframe_src` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `footer_settings`
--

INSERT INTO `footer_settings` (`id`, `address`, `phone`, `email`, `facebook_link`, `instagram_link`, `email_link`, `map_iframe_src`, `updated_at`) VALUES
(1, '1234 Neo Cafe Street, Neo City', '+63 123-456-7890', 'contact@neocafe.com', 'https://www.facebook.com/neocafePH', 'https://www.instagram.com/neocafeph/', 'mailto:hannahzepeda@outlook.com', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d23948.00681163127!2d121.08426539847078!3d14.284901776776811!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d9c053d854a7%3A0xfb047daf43ff3a0c!2sNeo%20Cafe!5e1!3m2!1sen!2sph!4v1742737204716!5m2!1sen!2sph', '2025-05-10 12:57:34');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` datetime NOT NULL,
  `type` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `attempts`, `last_attempt`, `type`) VALUES
(1, '192.168.100.134', 5, '2025-07-03 15:32:50', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('promotion','order_update','system_alert') DEFAULT 'system_alert',
  `image_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_id` int(11) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `image_url`, `is_read`, `created_at`, `order_id`, `link`) VALUES
(1, 19, NULL, 'Your order #6 has been confirmed.', '', NULL, 0, '2025-05-14 05:46:09', NULL, NULL),
(2, 21, NULL, 'Your order #8 has been confirmed.', '', NULL, 1, '2025-05-14 05:48:02', NULL, NULL),
(15, 5, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(16, 3, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(17, 2, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(18, 25, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(19, 19, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(20, 23, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(21, 6, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(22, 18, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(23, 22, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(24, 4, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(25, 20, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(26, 21, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(27, 23, 'Order #18 Status Update', 'Your order #18 has been Confirmed.', 'order_update', '/assets/product-images/Cheesy_Bacon_Sourdough_-_3_Slices/20567FC9-B602-4BB2-BC47-3ADAF5001A30.jpg', 1, '2025-05-16 16:08:56', 18, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=18'),
(28, 4, 'Order #19 Status Update', 'Your order #19 has been Confirmed.', 'order_update', '/assets/product-images/Garlic_Pandesal_-_10_Pieces/download.jfif', 1, '2025-05-16 16:12:34', 19, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=19'),
(29, 30, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', '', NULL, 0, '2025-05-16 16:22:02', NULL, NULL),
(30, 4, 'Order #19 Status Update', 'Your order #19 has been Ready for Delivery.', 'order_update', '/assets/product-images/Garlic_Pandesal_-_10_Pieces/download.jfif', 0, '2025-05-16 16:30:04', 19, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=19'),
(31, 25, 'Order #20 Status Update', 'Your order #20 has been Confirmed.', 'order_update', '/assets/product-images/Sesame_Sourdough_Batard/IMG_1172.JPG', 0, '2025-05-16 18:06:03', 20, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=20'),
(32, 30, NULL, 'Your order #17 has been delivered.', '', NULL, 0, '2025-05-16 18:09:01', NULL, NULL),
(33, 23, 'Order #17 Status Update', 'Your order #17 has been Delivered.', 'order_update', '/assets/product-images/Garlic_Pandesal_with_Cheese_-_8_Pieces_/Soft and Airy Pandesal (Tangzhong)  – AeslinBakes.jfif', 1, '2025-05-16 18:09:01', 17, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=17'),
(34, 31, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', '', NULL, 0, '2025-05-17 01:07:05', NULL, NULL),
(35, 5, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(36, 30, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(37, 3, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:51:54', NULL, NULL),
(38, 2, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(39, 25, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(40, 26, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(41, 28, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(42, 31, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(43, 19, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(44, 23, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(45, 6, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(46, 18, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(47, 22, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(48, 4, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(49, 20, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(50, 21, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(51, 5, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(52, 30, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(53, 3, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:52:06', NULL, NULL),
(54, 2, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(55, 25, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(56, 26, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(57, 28, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(58, 31, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(59, 19, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(60, 23, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(61, 6, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(62, 18, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(63, 22, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(64, 4, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(65, 20, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(66, 21, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(67, 22, 'Order #21 Status Update', 'Your order #21 has been Preparing.', 'order_update', '/assets/product-images/Olive_Sourdough_Batard/IMG_0774.JPG', 0, '2025-05-22 06:45:32', 21, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=21'),
(68, 5, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(69, 30, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(70, 3, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 06:47:33', NULL, NULL),
(71, 2, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(72, 25, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(73, 26, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(74, 28, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(75, 31, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(76, 19, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(77, 23, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(78, 6, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(79, 18, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(80, 22, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(81, 4, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(82, 20, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(83, 21, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(84, 22, 'Order #21 Status Update', 'Your order #21 has been Delivered.', 'order_update', '/assets/product-images/Olive_Sourdough_Batard/IMG_0774.JPG', 0, '2025-05-22 06:53:22', 21, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=21'),
(85, 22, 'Order #21 Status Update', 'Your order #21 has been Picked-up.', 'order_update', '/assets/product-images/Olive_Sourdough_Batard/IMG_0774.JPG', 0, '2025-05-22 06:54:08', 21, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=21'),
(86, 4, 'Order #19 Status Update', 'Your order #19 has been Delivered.', 'order_update', '/assets/product-images/Garlic_Pandesal_-_10_Pieces/download.jfif', 0, '2025-05-22 06:55:01', 19, '/NeoExclusiveCafe/pages/users/order-details.php?order_id=19'),
(89, 35, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', '', NULL, 0, '2025-05-23 04:37:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orderdate_status`
--

CREATE TABLE `orderdate_status` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('accepting','not_accepting') NOT NULL DEFAULT 'accepting',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderdate_status`
--

INSERT INTO `orderdate_status` (`id`, `date`, `status`, `reason`, `created_at`, `updated_at`) VALUES
(3, '2025-04-29', 'not_accepting', 'Manually set to not accept orders', '2025-04-26 05:28:18', '2025-04-26 05:28:18'),
(4, '2025-04-28', 'accepting', 'Manually set to not accept orders', '2025-04-26 06:04:24', '2025-04-26 09:39:56'),
(11, '2025-04-30', 'accepting', 'Manually set to not accept orders', '2025-04-26 07:51:54', '2025-04-26 09:39:41'),
(19, '2025-05-02', 'not_accepting', NULL, '2025-04-26 09:39:24', '2025-04-26 09:39:24'),
(22, '2025-05-03', 'not_accepting', NULL, '2025-04-29 05:58:03', '2025-04-29 23:59:12'),
(25, '2025-05-04', 'accepting', NULL, '2025-04-30 14:02:48', '2025-04-30 14:02:48'),
(26, '2025-05-05', 'accepting', NULL, '2025-05-01 08:44:09', '2025-05-01 08:44:09'),
(28, '2025-05-07', 'not_accepting', NULL, '2025-05-02 13:01:54', '2025-05-02 13:01:54'),
(30, '2025-05-17', 'accepting', NULL, '2025-05-15 17:29:32', '2025-05-15 17:33:22'),
(31, '2025-05-23', 'accepting', NULL, '2025-05-15 17:29:40', '2025-05-22 07:06:26'),
(32, '2025-05-18', 'accepting', NULL, '2025-05-15 17:29:48', '2025-05-15 17:30:05'),
(34, '2025-05-21', 'accepting', NULL, '2025-05-15 17:30:01', '2025-05-15 17:30:01'),
(41, '2025-05-28', 'not_accepting', NULL, '2025-05-23 04:56:29', '2025-05-23 04:56:29'),
(42, '2025-07-10', 'accepting', NULL, '2025-07-09 09:10:25', '2025-07-09 09:10:30'),
(44, '2025-08-09', 'not_accepting', NULL, '2025-08-07 12:29:29', '2025-08-07 12:29:29'),
(45, '2025-08-11', 'not_accepting', NULL, '2025-08-09 06:39:10', '2025-08-09 06:39:10'),
(47, '2025-08-13', 'not_accepting', NULL, '2025-08-13 00:18:21', '2025-08-13 00:18:21'),
(49, '2025-08-23', 'not_accepting', NULL, '2025-08-23 13:56:40', '2025-08-23 13:56:40');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `customer_name` varchar(255) NOT NULL,
  `customer_contact` varchar(11) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `payment_method` varchar(100) NOT NULL,
  `total_items` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `delivery_method` enum('Delivery','Pick-up') NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `delivery_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_date`, `customer_name`, `customer_contact`, `customer_email`, `customer_address`, `payment_method`, `total_items`, `total_amount`, `status`, `delivery_method`, `delivery_date`, `pickup_date`, `delivery_time`, `notes`, `accepted_at`, `declined_at`, `pickup_time`, `customer_id`, `completion_date`, `payment_id`, `payment_status`, `amount_paid`, `paid_at`) VALUES
(11, '2025-05-16 01:45:31', 'Hannah Zepeda', '09127589340', 'hannah.zepeda03@gmail.com', NULL, '0', 6, 880.00, 'Picked-up', 'Pick-up', NULL, '2025-05-18', NULL, '', '2025-05-16 01:47:50', NULL, '06:00:00', 24, NULL, NULL, 'pending', NULL, NULL),
(12, '2025-05-16 11:29:41', 'Hannah Zepeda', '09127589340', 'hannah.zepeda03@gmail.com', NULL, '0', 2, 140.00, 'Delivered', 'Pick-up', NULL, '2025-05-18', NULL, '', '2025-05-16 11:29:58', NULL, '13:00:00', 25, NULL, NULL, 'pending', NULL, NULL),
(13, '2025-05-16 12:34:08', 'Hannah Zepeda', '09127589340', 'hannah.zepeda03@gmail.com', NULL, '0', 7, 640.00, 'Active', 'Pick-up', NULL, '2025-05-20', NULL, '', '2025-05-16 13:04:18', NULL, '08:00:00', 26, NULL, NULL, 'pending', NULL, NULL),
(14, '2025-05-16 23:28:46', 'Ainee Pascua', '09123456789', 'alrainepascua2@gmail.com', NULL, '0', 1, 220.00, 'Confirmed', 'Pick-up', NULL, '2025-05-21', NULL, '', NULL, NULL, '06:00:00', 27, NULL, NULL, 'pending', NULL, NULL),
(15, '2025-05-16 23:37:50', 'Ainee Pascua', '09123456789', 'alrainepascua2@gmail.com', NULL, '0', 1, 100.00, 'Confirmed', 'Pick-up', NULL, '2025-05-21', NULL, '', NULL, NULL, '06:00:00', 28, NULL, NULL, 'pending', NULL, NULL),
(16, '2025-05-16 23:42:21', 'Ainee Pascua', '09123456789', 'alrainepascua2@gmail.com', NULL, '0', 1, 220.00, 'Ready for Delivery', 'Pick-up', NULL, '2025-05-21', NULL, '', NULL, NULL, '06:00:00', 29, NULL, NULL, 'pending', NULL, NULL),
(23, '2025-05-23 11:04:46', 'ivy Padilla', '09123456789', 'padillaivydianne@gmail.com', NULL, '0', 1, 130.00, 'Pending', 'Pick-up', NULL, '2025-05-26', NULL, '', '2025-07-09 12:31:35', NULL, '06:00:00', 36, NULL, NULL, 'pending', NULL, NULL),
(24, '2025-05-23 11:16:29', 'ivy Padilla', '09123456789', 'padillaivydianne@gmail.com', 'tabing ilog, Brgy. San Pioquinto, Malvar, Batangas, Region IV-A (CALABARZON)', '0', 4, 810.00, 'Completed', 'Delivery', '2025-05-27', NULL, '06:00:00', '', '2025-07-09 12:31:35', NULL, NULL, 37, NULL, NULL, 'pending', NULL, NULL),
(25, '2025-05-23 12:44:28', 'asdadasdas adasdd', '11111111111', 'padillaivanbrayl@gmail.com', 'Brgy. San Pedro II (Western), Malvar, Batangas, Region IV-A (CALABARZON)', '0', 5, 550.00, 'Delivered', 'Delivery', '2025-05-23', NULL, '06:00:00', '', '2025-05-23 12:45:36', NULL, NULL, 38, '2025-05-23 12:45:54', NULL, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders_new`
--

CREATE TABLE `orders_new` (
  `order_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `customer_name` varchar(255) NOT NULL,
  `customer_contact` varchar(11) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `payment_method` varchar(100) NOT NULL,
  `total_items` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `delivery_method` enum('Delivery','Pick-up') NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `delivery_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_name`, `image_path`, `price`, `quantity`) VALUES
(1, 2, 'asdfasd', NULL, 33.00, 1),
(2, 5, 'Banana Cupcake', NULL, 500.00, 1),
(3, 6, 'asdfasd', NULL, 33.00, 1),
(4, 1, 'Banana Cupcake', NULL, 500.00, 1),
(5, 2, 'Apple', NULL, 55.55, 1),
(6, 3, 'Banana Cupcake', NULL, 500.00, 1),
(7, 4, 'Banana Cupcake', NULL, 500.00, 1),
(8, 5, 'Banana Cupcake', NULL, 500.00, 1),
(9, 6, 'asdfasd', NULL, 33.00, 1),
(10, 7, 'Banana Cupcake', NULL, 500.00, 1),
(11, 8, 'asdfasd', NULL, 33.00, 1),
(12, 9, 'Apple', NULL, 55.55, 16),
(13, 10, 'Banana Cupcake', NULL, 500.00, 1),
(14, 11, 'Banana Cake ', NULL, 180.00, 2),
(15, 11, '3 Slices - Cheesy Bacon Sourdough ', NULL, 130.00, 4),
(16, 12, 'Garlic Pandesal - 10 Pieces', NULL, 70.00, 2),
(17, 13, 'Garlic Pandesal with Cheese - 8 Pieces ', NULL, 100.00, 5),
(18, 13, 'Garlic Pandesal - 10 Pieces', NULL, 70.00, 2),
(19, 14, 'Sesame Sourdough Batard', NULL, 220.00, 1),
(20, 15, 'Garlic Pandesal with Cheese - 8 Pieces ', NULL, 100.00, 1),
(21, 16, 'Sesame Sourdough Batard', NULL, 220.00, 1),
(22, 17, 'Garlic Pandesal with Cheese - 8 Pieces ', NULL, 100.00, 1),
(23, 18, '3 Slices - Cheesy Bacon Sourdough ', NULL, 130.00, 1),
(24, 19, 'Garlic Pandesal - 10 Pieces', NULL, 70.00, 1),
(25, 20, 'Sesame Sourdough Batard', NULL, 220.00, 1),
(26, 21, 'Olive Sourdough Batard', NULL, 250.00, 2),
(27, 22, 'Classic Sourdough Batard', NULL, 220.00, 1),
(28, 23, '3 Slices - Cheesy Bacon Sourdough ', NULL, 130.00, 1),
(29, 24, 'Classic Sourdough Batard', NULL, 220.00, 3),
(30, 24, 'Garlic Pandesal with Cheese - 8 Pieces ', NULL, 100.00, 1),
(31, 25, 'Garlic Pandesal with Cheese - 8 Pieces ', NULL, 100.00, 5);

-- --------------------------------------------------------

--
-- Table structure for table `order_limits`
--

CREATE TABLE `order_limits` (
  `id` int(11) NOT NULL,
  `default_limit` int(11) NOT NULL DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_limits`
--

INSERT INTO `order_limits` (`id`, `default_limit`, `created_at`, `updated_at`) VALUES
(1, 3, '2025-05-01 08:28:41', '2025-08-23 13:56:48');

-- --------------------------------------------------------

--
-- Table structure for table `post_categories`
--

CREATE TABLE `post_categories` (
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `status_id` int(10) UNSIGNED DEFAULT NULL,
  `availtoday_status_id` int(11) DEFAULT NULL,
  `unavailable_status_id` int(11) DEFAULT NULL COMMENT 'References unavail_products_status table. NULL = available, NOT NULL = unavailable',
  `is_featured` tinyint(1) DEFAULT 0,
  `show_when_unavailable` tinyint(1) DEFAULT 0,
  `hide_when_unavailable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `auto_deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `price`, `quantity`, `status_id`, `availtoday_status_id`, `unavailable_status_id`, `is_featured`, `show_when_unavailable`, `hide_when_unavailable`, `created_at`, `updated_at`, `deleted_at`, `auto_deleted_at`) VALUES
(2, 'SD-00001', 'Saging', 'SADBASDFASDFs', 4.44, 4, 3, 1, NULL, 0, 0, 1, '2025-08-12 01:49:44', '2025-08-12 09:43:59', '2025-08-12 17:43:59', NULL),
(3, 'SD-00002', 'Aine', 'grrthrthjrtu', 4.44, 5, 1, 2, NULL, 1, 1, 0, '2025-08-12 02:00:36', '2025-08-12 09:43:57', '2025-08-12 17:43:57', NULL),
(4, 'SD-00003', 'Saging', 'sadfghasdgDS', 44.00, 4, 3, 2, NULL, 0, 0, 1, '2025-08-12 10:03:49', '2025-08-17 11:57:43', NULL, NULL),
(5, 'SD-00004', 'MASARAP NA HOTDOG', 'jahsdbkvabsdijhfa;osduhgbaksdbg', 32.00, 3, 1, NULL, NULL, 0, 0, 1, '2025-08-13 12:16:13', '2025-08-15 04:34:40', NULL, NULL),
(6, 'SD-00005', 'HAHAAHHATODGOODOGDGO', 'dfgsbdfsdgsasdg', 4.44, 3, 2, NULL, NULL, 0, 0, 1, '2025-08-13 12:58:45', '2025-08-26 07:20:38', NULL, NULL),
(7, 'SD-00006', 'goku', 'erybhwshs', 40.44, 5, 3, 2, NULL, 1, 0, 1, '2025-08-17 14:33:18', '2025-08-24 15:31:07', NULL, NULL),
(8, 'SD-00007', 'Test Today\'s Product', 'Test description for today\'s product', 99.99, 10, 3, 1, NULL, 0, 0, 1, '2025-08-24 11:57:25', '2025-08-24 12:07:33', '2025-08-24 20:07:33', NULL),
(9, 'SD-00008', 'Saging', 'asdgasdgvasdfgvasdfgas', 44.00, 3, 3, 1, NULL, 1, 1, 0, '2025-08-24 13:51:19', '2025-08-26 07:20:43', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_day`
--

CREATE TABLE `product_day` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `product_day`
--

INSERT INTO `product_day` (`id`, `product_id`, `day_of_week`, `created_at`) VALUES
(11, 2, 'Tuesday', '2025-08-12 01:50:50'),
(13, 3, 'Tuesday', '2025-08-12 09:02:46'),
(58, 5, 'Monday', '2025-08-15 04:34:40'),
(59, 5, 'Thursday', '2025-08-15 04:34:40'),
(67, 4, 'Sunday', '2025-08-17 11:57:43'),
(95, 6, 'Monday', '2025-08-26 07:20:38'),
(96, 6, 'Tuesday', '2025-08-26 07:20:38');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_removed` tinyint(1) DEFAULT 0,
  `temp_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `created_at`, `is_primary`, `is_removed`, `temp_filename`) VALUES
(5, 2, 'product-images/Saging_1754963384/primary_1754963384.jpg', '2025-08-12 01:49:44', 1, 0, NULL),
(6, 2, 'product-images/Saging_1754963384/additional_1754963384_1.jpg', '2025-08-12 01:49:44', 0, 0, NULL),
(7, 2, 'product-images/Saging_1754963384/additional_1754963384_2.jpg', '2025-08-12 01:49:44', 0, 0, NULL),
(8, 2, 'product-images/Saging_1754963384/additional_1754963384_3.jpg', '2025-08-12 01:49:44', 0, 0, NULL),
(9, 3, 'product-images/Aine_1754964036/primary_1754964036.jpg', '2025-08-12 02:00:36', 1, 0, NULL),
(10, 3, 'product-images/Aine_1754964036/additional_1754964036_1.jpg', '2025-08-12 02:00:36', 0, 0, NULL),
(11, 3, 'product-images/Aine_1754964036/additional_1754964036_2.jpg', '2025-08-12 02:00:36', 0, 0, NULL),
(12, 3, 'product-images/Aine_1754964036/additional_1754964036_3.jpg', '2025-08-12 02:00:36', 0, 0, NULL),
(29, 6, 'product-images/HAHAAHHATODGOODOGDGO/temp_6_1755342797_68a067cd056f5.jpg', '2025-08-16 11:13:22', 1, 0, NULL),
(30, 6, 'product-images/HAHAAHHATODGOODOGDGO/temp_6_1755342801_68a067d163dfe.jpg', '2025-08-16 11:13:22', 0, 0, NULL),
(31, 6, 'product-images/HAHAAHHATODGOODOGDGO/temp_6_1755342801_68a067d16b17a.jpg', '2025-08-16 11:13:22', 0, 0, NULL),
(33, 7, 'product-images/goku_1755441198/primary_1755441198.jpg', '2025-08-17 14:33:18', 1, 0, NULL),
(34, 7, 'product-images/goku_1755441198/additional_1755441198_1.jpg', '2025-08-17 14:33:18', 0, 0, NULL),
(35, 7, 'product-images/goku_1755441198/additional_1755441198_2.jpg', '2025-08-17 14:33:18', 0, 0, NULL),
(36, 7, 'product-images/goku_1755441198/additional_1755441198_3.jpg', '2025-08-17 14:33:18', 0, 0, NULL),
(37, 8, 'product-images/Test_Today_s_Product_1756036645/primary_1756036645.jpg', '2025-08-24 11:57:25', 1, 0, NULL),
(38, 9, 'product-images/Saging_1756043479/primary_1756043479.jpg', '2025-08-24 13:51:19', 1, 0, NULL),
(39, 9, 'product-images/Saging_1756043479/additional_1756043479_1.png', '2025-08-24 13:51:19', 0, 0, NULL),
(40, 9, 'product-images/Saging_1756043479/additional_1756043479_2.png', '2025-08-24 13:51:19', 0, 0, NULL),
(41, 9, 'product-images/Saging_1756043479/additional_1756043479_3.jpg', '2025-08-24 13:51:19', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_statuses`
--

CREATE TABLE `product_statuses` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_statuses`
--

INSERT INTO `product_statuses` (`id`, `name`) VALUES
(3, 'Available Today'),
(2, 'Delivery'),
(1, 'Pick Up');

-- --------------------------------------------------------

--
-- Table structure for table `regular_products_today_dates`
--

CREATE TABLE `regular_products_today_dates` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `available_date` date NOT NULL,
  `availtoday_status_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Stores specific dates when regular Delivery/Pick Up products are also available today';

--
-- Dumping data for table `regular_products_today_dates`
--

INSERT INTO `regular_products_today_dates` (`id`, `product_id`, `available_date`, `availtoday_status_id`, `created_at`, `updated_at`) VALUES
(8, 6, '2025-08-25', NULL, '2025-08-26 07:20:38', '2025-08-26 07:20:38'),
(9, 6, '2025-08-26', NULL, '2025-08-26 07:20:38', '2025-08-26 07:20:38'),
(10, 6, '2025-08-27', NULL, '2025-08-26 07:20:38', '2025-08-26 07:20:38');

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

CREATE TABLE `saved_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_posts`
--

INSERT INTO `saved_posts` (`id`, `user_id`, `post_id`, `saved_at`) VALUES
(4, 21, 13, '2025-05-13 14:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `service_cards`
--

CREATE TABLE `service_cards` (
  `id` int(11) NOT NULL,
  `icon_name` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_cards`
--

INSERT INTO `service_cards` (`id`, `icon_name`, `title`, `description`, `display_order`, `created_at`, `updated_at`) VALUES
(3, 'star', 'Fresh Ingredients', 'Nemo enim ipsam voluptatem quia voluptas sit', 3, '2025-05-20 04:18:45', '2025-05-20 04:56:35'),
(4, 'cog', 'Fresh Ingredients', 'Nemo enim ipsam voluptatem quia voluptas sit', 3, '2025-05-20 04:18:47', '2025-05-20 04:18:47'),
(5, 'cog', 'Fresh Ingredients', 'Nemo enim ipsam voluptatem quia voluptas sit', 3, '2025-05-20 04:18:49', '2025-05-20 04:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `service_section_settings`
--

CREATE TABLE `service_section_settings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'What We Provide',
  `subtitle` text NOT NULL DEFAULT 'We provide our best service to our clients. We always care about our customer\'s satisfaction.',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_section_settings`
--

INSERT INTO `service_section_settings` (`id`, `title`, `subtitle`, `updated_at`) VALUES
(1, 'What We Provide', 'We provide our best service to our clients. We always care about our customer\'s satisfaction.', '2025-05-20 06:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `todays_products_dates`
--

CREATE TABLE `todays_products_dates` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `available_date` date NOT NULL,
  `availtoday_status_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Stores specific dates when Today''s products are available, with optional delivery method specification';

--
-- Dumping data for table `todays_products_dates`
--

INSERT INTO `todays_products_dates` (`id`, `product_id`, `available_date`, `availtoday_status_id`, `created_at`, `updated_at`) VALUES
(1, 8, '2024-02-25', 1, '2025-08-24 11:57:25', '2025-08-24 11:57:25'),
(2, 8, '2024-02-26', 1, '2025-08-24 11:57:25', '2025-08-24 11:57:25'),
(3, 8, '2024-02-27', 1, '2025-08-24 11:57:25', '2025-08-24 11:57:25'),
(9, 7, '2025-08-27', 2, '2025-08-24 15:31:07', '2025-08-24 15:31:07'),
(10, 7, '2025-08-30', 2, '2025-08-24 15:31:07', '2025-08-24 15:31:07'),
(19, 9, '2025-08-25', 1, '2025-08-26 07:20:43', '2025-08-26 07:20:43'),
(20, 9, '2025-08-26', 1, '2025-08-26 07:20:43', '2025-08-26 07:20:43'),
(21, 9, '2025-08-27', 1, '2025-08-26 07:20:43', '2025-08-26 07:20:43'),
(22, 9, '2025-08-28', 1, '2025-08-26 07:20:43', '2025-08-26 07:20:43'),
(23, 9, '2025-08-29', 1, '2025-08-26 07:20:43', '2025-08-26 07:20:43');

-- --------------------------------------------------------

--
-- Table structure for table `unavail_products_status`
--

CREATE TABLE `unavail_products_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Tracks different types of unavailable product statuses';

--
-- Dumping data for table `unavail_products_status`
--

INSERT INTO `unavail_products_status` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Unavailable Pick Up', '2025-08-08 11:35:24', '2025-08-08 11:35:24'),
(2, 'Unavailable Delivery', '2025-08-08 11:35:24', '2025-08-08 11:35:24'),
(3, 'Unavailable Today', '2025-08-08 11:35:24', '2025-08-08 11:35:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `username` varchar(12) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_token_expires_at` datetime DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `email`, `password`, `reset_token_hash`, `reset_token_expires_at`, `is_verified`, `verification_token`, `verification_token_expires_at`, `is_admin`, `profile_image`, `created_at`) VALUES
(2, 'Aine', 'Pascua', 'Eniak', 'alrainepascua4@gmail.com', '$2y$10$WBQlWR0/1O4j5X.ZZ1tQqOp0unfz3pXdGRXI8CW4tWvZWxaT/l1V2', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-04-15 13:28:44'),
(3, 'Dya', 'Yin', 'Dyayin', 'dyayin12@gmail.com', '$2y$10$mv/mh2aFmXAWgv6298ZJreGkn50JcHXAwGNwtSO644HTLyEfHEKAW', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-04-15 13:28:44'),
(4, 'Ainee', 'Pascua', 'Try', 'alrainepascua2@gmail.com', '$2y$10$1hrKCZM/UEEuDEIQugb5rOUqj9X1dsBURnE8U53uEmtdpRsz4Oeda', NULL, NULL, 1, NULL, NULL, 0, '/assets/profile-images/profile_4_1745153346.jpg', '2025-04-15 13:28:44'),
(6, 'NGEK', 'NGEGNE', 'NGEK', 'ainepascua2@gmail.com', '$2y$10$OVnzwR5xg9/It.sjk6ZPdukcz2Xfig6NLgwSVdrUrIMGpNgtoHSL.', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-04-15 13:28:44'),
(18, 'Testing', 'HAHAHAHA', 'qwerty', 'randomakjsdnfkja@gmail.com', '$2y$10$kfAJRceD3R4lSVRaw71.P.QUlV8UhTUgWpYFnU1DUN/Gkf0HTGoSG', NULL, NULL, 0, 'abcd7445e65e7d36c3a1e69e9e74cf574ce9fef078d4ac7b03fb8b8207ad7d98', '2025-04-30 21:58:00', 0, NULL, '2025-04-30 13:25:51'),
(19, 'try', 'ulet', 'magsesendEma', 'emailsgasgasd@gmail.com', '$2y$10$ou2WIgM0umyo8CQk7SWuwertnDun3PS0WWJgysdHzF/UWkRRx55FG', NULL, NULL, 0, '87e10ca6e31e10c5d4fe769a6f7711a2943878ecb33070d5893c5de7996acc4d', '2025-04-30 22:00:36', 0, NULL, '2025-04-30 13:30:36'),
(20, 'Verify', 'Test', 'Verification', 'vtestingfor@gmail.com', '$2y$10$1zCgM5QbPBWb7hmUl68mHO2S2cmtA43jXewp40cGl8FUfjUnr/TQO', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-05-03 03:03:04'),
(21, 'Hannah', 'Zepeda', 'zpdhnnh', 'hannah.zepeda03@gmail.com', '$2y$10$zDs75tnwAb6Mi.b4xMlQQ.9sljEE8e8oBo3xg9/F97klRa7iffPKC', NULL, NULL, 1, NULL, NULL, 0, '/assets/profile-images/profile_21_1747234137.png', '2025-05-11 16:23:31'),
(22, 'ivy', 'Padilla', 'test', 'padillaivydianne@gmail.com', '$2y$10$01twKfo6HInurWSKwJCEEeR1wcpoE/yPYOZLE.aboX9OP1lxvCVJi', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-05-15 23:50:20'),
(23, 'new', 'test', 'new', 'padillaivydiane@gmail.com', '$2y$10$9A5TPCBui.Io/90gG0ijYen1G/PXqtJ0h7tdA7zOuKHG9eN4si0ym', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-05-16 00:03:24'),
(25, 'Ivy', 'Padilla', 'ivypadilla', 'ace96587@gmail.com', '$2y$10$inqNlZZFQSL18b5DCH9Hi.r0Pq463jKoUs1cmTnZd8UZFUl7LP5.K', NULL, NULL, 1, NULL, NULL, 0, '/assets/profile-images/profile_25_1747418467.jpg', '2025-05-04 00:54:16'),
(26, 'rj', 'jay', 'jay', 'jayyy@gmail.com', '$2y$10$I3/N.zMZyQ8edLnlCyx65OiyUMfGH27McQToCzjN1cB142CD/60fO', NULL, NULL, 0, '958f3e48b27fa50db4de6f11120d3f152dcc249f31f6c92ed43e34b8d8175a4f', '2025-05-17 00:47:08', 0, NULL, '2025-05-16 16:17:08'),
(28, 'rj', 'jayyyyyyyyy', 'jayyyyy', 'jayyyiglesia@gmail.com', '$2y$10$7enSi2rRfdJli2zwFpjLhOzAVcdCZAw6GM/SSYylEIbz69ZtZ6QQG', NULL, NULL, 0, 'd2d7fa09a04b26eaaa6753a2c11b4cf7de83aa6ae247f38f1f736387355fe8c9', '2025-05-17 00:48:56', 0, NULL, '2025-05-16 16:18:56'),
(30, 'rj', 'jayyyyyyyyy', 'asdfgh', 'kiaclaytondei@gmail.com', '$2y$10$UTGKiiiooBFXpkdIkUI5huJbokfxHkzAWsRtxjJLRQuWMikg.zYHy', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-05-16 16:21:43'),
(31, 'Ken', 'Rodriguez', 'Ken', 'kuaken07@gmail.com', '$2y$10$ktBHmeuVRlYsZJ1o3eqfSO6fd4sivDOV4CrCP19sksh/CFsuUVNt6', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-05-17 01:04:21'),
(35, 'asdadasdas', 'adasdd', 'hahahaha', 'padillaivanbrayl@gmail.com', '$2y$10$LN1ZlIlUXDr/gy.Zi2vDQ.4zsHWzCERf756TX9Dx3gQS/aii/ds5K', NULL, NULL, 1, NULL, NULL, 0, NULL, '2025-05-23 04:37:31'),
(36, 'Admin', 'User', 'admin', 'admin@neoexclusivecafe.com', '$2y$10$xhu5J7GZwXwcRozYYqRUO.YJ4kPBPdy22mmCX89yioudVac1lVxzK', NULL, NULL, 1, NULL, NULL, 1, NULL, '2025-07-09 03:56:16');

-- --------------------------------------------------------

--
-- Table structure for table `user_blog_post`
--

CREATE TABLE `user_blog_post` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published','archived') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_blog_post`
--

INSERT INTO `user_blog_post` (`id`, `user_id`, `title`, `content`, `image_path`, `created_at`, `updated_at`, `status`) VALUES
(10, 5, 'Sourdough', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus lacinia odio vitae vestibulum vestibulum.', 'assets/uploaded-images-users/blog_6816216e9f229.jpg', '2025-05-03 14:00:14', '2025-05-03 14:00:14', 'published'),
(11, 5, 'The Sourdough Bread Tasting', 'Roasted nuts on the nose, this bread has a light and bright buttery flavor with a subtle hit of tang at the finish. The crust is slightly nutty and cracked. Nice start!', 'uploads/blog/6818c9415ee85.JPG', '2025-05-05 14:20:33', '2025-05-05 14:20:49', 'published'),
(12, 21, 'Received the Product', 'Thanks', 'uploads/blog/6823666ef13ec.jpg', '2025-05-12 15:23:15', '2025-05-13 15:34:06', 'published'),
(13, 21, 'Thank u again', 'Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', 'uploads/blog/68236852062f7.JPG', '2025-05-13 14:41:41', '2025-05-14 14:13:34', 'published'),
(14, 25, 'Recommended!!!', 'I\'m gonna order again, this pastry right here is a lit pips!!!', 'assets/uploaded-images-users/blog_68277e10a6648.jpg', '2025-05-16 18:04:00', '2025-05-16 18:04:00', 'published');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES
(0, 1, '2025-06-15 11:28:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_cart_availtoday_details`
-- (See below for the actual view)
--
CREATE TABLE `view_cart_availtoday_details` (
`cart_id` int(11)
,`user_id` int(11)
,`product_id` int(11)
,`quantity` int(11)
,`cart_price` decimal(10,2)
,`created_at` timestamp
,`updated_at` timestamp
,`product_name` varchar(255)
,`product_description` text
,`current_price` decimal(10,2)
,`stock_quantity` int(11)
,`status_id` int(10) unsigned
,`status_name` varchar(50)
,`image_url` varchar(255)
,`total_price` decimal(20,2)
,`available_days` mediumtext
);

-- --------------------------------------------------------

--
-- Structure for view `view_cart_availtoday_details`
--
DROP TABLE IF EXISTS `view_cart_availtoday_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_cart_availtoday_details`  AS SELECT `c`.`id` AS `cart_id`, `c`.`user_id` AS `user_id`, `c`.`product_id` AS `product_id`, `c`.`quantity` AS `quantity`, `c`.`price` AS `cart_price`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `p`.`name` AS `product_name`, `p`.`description` AS `product_description`, `p`.`price` AS `current_price`, `p`.`quantity` AS `stock_quantity`, `p`.`status_id` AS `status_id`, `ps`.`name` AS `status_name`, `pi`.`image_url` AS `image_url`, `c`.`quantity`* `c`.`price` AS `total_price`, group_concat(`pd`.`day_of_week` order by field(`pd`.`day_of_week`,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') ASC separator ', ') AS `available_days` FROM ((((`cart_availtoday` `c` left join `products` `p` on(`c`.`product_id` = `p`.`id`)) left join `product_statuses` `ps` on(`p`.`status_id` = `ps`.`id`)) left join `product_images` `pi` on(`p`.`id` = `pi`.`product_id` and `pi`.`is_primary` = 1)) left join `product_day` `pd` on(`p`.`id` = `pd`.`product_id`)) WHERE `p`.`deleted_at` is null GROUP BY `c`.`id`, `c`.`user_id`, `c`.`product_id`, `c`.`quantity`, `c`.`price`, `c`.`created_at`, `c`.`updated_at`, `p`.`name`, `p`.`description`, `p`.`price`, `p`.`quantity`, `p`.`status_id`, `ps`.`name`, `pi`.`image_url` ORDER BY `c`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_role_permissions`
--
ALTER TABLE `admin_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `availtoday_order_limit`
--
ALTER TABLE `availtoday_order_limit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `availtoday_status`
--
ALTER TABLE `availtoday_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `availtoday_timer`
--
ALTER TABLE `availtoday_timer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_hours`
--
ALTER TABLE `business_hours`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carousel_images`
--
ALTER TABLE `carousel_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carousel_settings`
--
ALTER TABLE `carousel_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `cart_availtoday`
--
ALTER TABLE `cart_availtoday`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_product_quantity` (`user_id`,`product_id`,`quantity`),
  ADD KEY `idx_price_quantity` (`price`,`quantity`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `date_limits`
--
ALTER TABLE `date_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`);

--
-- Indexes for table `footer_settings`
--
ALTER TABLE `footer_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip_type` (`ip_address`,`type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orderdate_status`
--
ALTER TABLE `orderdate_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders_new`
--
ALTER TABLE `orders_new`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_order_items_order_id` (`order_id`);

--
-- Indexes for table `order_limits`
--
ALTER TABLE `order_limits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_categories`
--
ALTER TABLE `post_categories`
  ADD PRIMARY KEY (`post_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `idx_products_unavailable_status` (`unavailable_status_id`);

--
-- Indexes for table `product_day`
--
ALTER TABLE `product_day`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_day_lookup` (`product_id`,`day_of_week`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_ibfk_1` (`product_id`),
  ADD KEY `idx_product_images_removed` (`product_id`,`is_removed`);

--
-- Indexes for table `product_statuses`
--
ALTER TABLE `product_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `regular_products_today_dates`
--
ALTER TABLE `regular_products_today_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_regular_product_date_status` (`product_id`,`available_date`,`availtoday_status_id`),
  ADD KEY `idx_regular_products_today_date` (`available_date`),
  ADD KEY `idx_regular_products_today_lookup` (`product_id`,`available_date`),
  ADD KEY `idx_regular_products_today_status` (`availtoday_status_id`);

--
-- Indexes for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_save` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `todays_products_dates`
--
ALTER TABLE `todays_products_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_date_status` (`product_id`,`available_date`,`availtoday_status_id`),
  ADD KEY `idx_todays_products_date` (`available_date`),
  ADD KEY `idx_todays_products_lookup` (`product_id`,`available_date`),
  ADD KEY `idx_todays_products_status` (`availtoday_status_id`);

--
-- Indexes for table `unavail_products_status`
--
ALTER TABLE `unavail_products_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- Indexes for table `user_blog_post`
--
ALTER TABLE `user_blog_post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `availtoday_order_limit`
--
ALTER TABLE `availtoday_order_limit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `availtoday_timer`
--
ALTER TABLE `availtoday_timer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `business_hours`
--
ALTER TABLE `business_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carousel_images`
--
ALTER TABLE `carousel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `carousel_settings`
--
ALTER TABLE `carousel_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `cart_availtoday`
--
ALTER TABLE `cart_availtoday`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `date_limits`
--
ALTER TABLE `date_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `orderdate_status`
--
ALTER TABLE `orderdate_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `order_limits`
--
ALTER TABLE `order_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_day`
--
ALTER TABLE `product_day`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `product_statuses`
--
ALTER TABLE `product_statuses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `regular_products_today_dates`
--
ALTER TABLE `regular_products_today_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `todays_products_dates`
--
ALTER TABLE `todays_products_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `unavail_products_status`
--
ALTER TABLE `unavail_products_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `user_blog_post`
--
ALTER TABLE `user_blog_post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_role_permissions`
--
ALTER TABLE `admin_role_permissions`
  ADD CONSTRAINT `admin_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `admin_permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_availtoday`
--
ALTER TABLE `cart_availtoday`
  ADD CONSTRAINT `cart_availtoday_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_availtoday_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `post_categories`
--
ALTER TABLE `post_categories`
  ADD CONSTRAINT `post_categories_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_unavailable_status` FOREIGN KEY (`unavailable_status_id`) REFERENCES `unavail_products_status` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `product_statuses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_day`
--
ALTER TABLE `product_day`
  ADD CONSTRAINT `product_day_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `regular_products_today_dates`
--
ALTER TABLE `regular_products_today_dates`
  ADD CONSTRAINT `regular_products_today_dates_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `regular_products_today_dates_ibfk_2` FOREIGN KEY (`availtoday_status_id`) REFERENCES `availtoday_status` (`id`);

--
-- Constraints for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD CONSTRAINT `saved_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_posts_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `user_blog_post` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `todays_products_dates`
--
ALTER TABLE `todays_products_dates`
  ADD CONSTRAINT `todays_products_dates_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `todays_products_dates_ibfk_2` FOREIGN KEY (`availtoday_status_id`) REFERENCES `availtoday_status` (`id`);

--
-- Constraints for table `user_blog_post`
--
ALTER TABLE `user_blog_post`
  ADD CONSTRAINT `user_blog_post_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
