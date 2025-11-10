-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql-neoexclusivecafe.alwaysdata.net
-- Generation Time: Nov 10, 2025 at 09:16 AM
-- Server version: 10.11.14-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `neoexclusivecafe_crud`
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
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48'),
(1, 'About Us', '<p>Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality bread and exceptional service\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.\r\n\r\nLorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos</p>', '/backend/assets/images/about_1761924730.jpg', '2025-10-31 15:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `affected_table` varchar(100) DEFAULT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `admin_name`, `action_type`, `action_description`, `affected_table`, `affected_id`, `ip_address`, `timestamp`) VALUES
(1, 2, 'Admin Account', 'UPDATE', 'Changed order #9 status to \'Delivered\'', 'orders', 9, '127.0.0.1', '2025-10-21 15:00:55'),
(2, 2, 'Admin Account', 'UPDATE', 'Changed bulk order #5 status to \'cancelled\'', 'bulk_orders', 5, '127.0.0.1', '2025-10-21 15:06:10'),
(3, 2, 'Admin Account', 'UPDATE', 'Updated account profile information', 'users', 2, '127.0.0.1', '2025-10-21 15:17:23'),
(4, 2, 'Admin Account', 'UPDATE', 'Updated carousel image: Sourdough 1', 'carousel_images', 1, '127.0.0.1', '2025-10-21 15:27:18'),
(5, 2, 'Admin Account', 'DELETE', 'Deleted carousel image: Sourdough 3', 'carousel_images', 6, '127.0.0.1', '2025-10-21 15:27:27'),
(6, 2, 'Admin Account', 'UPDATE', 'Restored product from archive: Cassava Cake', 'products', 12, '127.0.0.1', '2025-10-21 15:36:16'),
(7, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #1 status to \'approved\'', 'order_refunds', 1, '172.22.0.1', '2025-10-22 01:47:08'),
(8, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #1 status to \'completed\' and sent voucher', 'order_refunds', 1, '172.22.0.1', '2025-10-22 02:09:03'),
(9, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #1 status to \'completed\' and sent voucher', 'order_refunds', 1, '172.22.0.1', '2025-10-22 02:16:51'),
(10, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #1 status to \'completed\'', 'order_refunds', 1, '172.22.0.1', '2025-10-22 02:18:42'),
(11, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #2 status to \'completed\' and sent voucher', 'order_refunds', 2, '172.22.0.1', '2025-10-22 02:24:07'),
(12, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #1 status to \'rejected\' and sent email', 'order_refunds', 1, '172.22.0.1', '2025-10-22 02:45:28'),
(13, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #2 status to \'approved\' and sent email', 'order_refunds', 2, '172.22.0.1', '2025-10-22 02:52:51'),
(14, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #2 status to \'completed\' and sent voucher', 'order_refunds', 2, '172.22.0.1', '2025-10-22 02:54:01'),
(15, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated carousel settings', 'carousel_settings', 1, '127.0.0.1', '2025-10-22 04:12:30'),
(16, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated carousel settings', 'carousel_settings', 1, '127.0.0.1', '2025-10-22 04:12:59'),
(17, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-22 04:25:56'),
(18, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted product: Brownie', 'products', 15, '127.0.0.1', '2025-10-22 04:33:11'),
(19, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-22 05:37:15'),
(20, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-22 05:37:57'),
(21, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-22 05:38:19'),
(22, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-22 05:40:11'),
(23, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-22 05:40:31'),
(24, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-22 05:47:57'),
(25, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 02:45:36'),
(26, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 03:00:47'),
(27, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 03:14:47'),
(28, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 03:19:26'),
(29, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 03:23:12'),
(30, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 04:13:05'),
(31, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 04:35:42'),
(32, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 04:41:12'),
(33, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 04:43:56'),
(34, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 05:20:52'),
(35, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 05:32:46'),
(36, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 06:55:38'),
(37, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 08:29:59'),
(38, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 08:31:07'),
(39, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 09:02:00'),
(40, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 09:04:35'),
(41, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 09:05:00'),
(42, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 09:11:38'),
(43, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-23 09:13:35'),
(44, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-24 04:01:53'),
(45, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-24 04:02:10'),
(46, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated pricing for bulk order #5 (Total: ₱2,160.00)', 'bulk_orders', 5, '127.0.0.1', '2025-10-24 06:10:36'),
(47, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-24 06:14:40'),
(48, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-24 06:27:23'),
(49, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-24 06:50:05'),
(50, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-24 06:50:22'),
(51, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated pricing for bulk order #6 (Total: ₱2,700.00)', 'bulk_orders', 6, '127.0.0.1', '2025-10-24 07:20:14'),
(52, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #6 status to \'approved\'', 'bulk_orders', 6, '127.0.0.1', '2025-10-24 07:20:34'),
(53, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-24 07:59:44'),
(54, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-24 08:09:55'),
(55, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-24 08:10:34'),
(56, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-24 08:33:23'),
(57, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-24 08:34:17'),
(58, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-24 08:35:23'),
(59, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #2 status to \'approved\'', 'order_refunds', 2, '127.0.0.1', '2025-10-24 09:45:18'),
(60, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #3 status to \'approved\' and sent email', 'order_refunds', 3, '127.0.0.1', '2025-10-24 10:39:20'),
(61, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #3 status to \'completed\' and sent voucher', 'order_refunds', 3, '127.0.0.1', '2025-10-24 10:40:02'),
(62, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #2 status to \'completed\'', 'order_refunds', 2, '127.0.0.1', '2025-10-24 12:20:25'),
(63, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #5 status to \'Preparing\'', 'orders', 5, '127.0.0.1', '2025-10-24 14:02:14'),
(64, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #2 status to \'Preparing\'', 'orders', 2, '127.0.0.1', '2025-10-24 14:03:05'),
(65, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #2 status to \'Delivered\'', 'orders', 2, '127.0.0.1', '2025-10-24 14:03:29'),
(66, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #5 status to \'Delivered\'', 'orders', 5, '127.0.0.1', '2025-10-24 14:03:36'),
(67, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #4 status to \'approved\' and sent email', 'order_refunds', 4, '127.0.0.1', '2025-10-24 14:51:57'),
(68, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #4 status to \'completed\' and sent voucher', 'order_refunds', 4, '127.0.0.1', '2025-10-24 15:46:10'),
(69, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal - 10 Pieces (ID: 10)', 'products', 10, '127.0.0.1', '2025-10-25 03:04:51'),
(70, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-25 03:40:53'),
(71, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-25 05:38:59'),
(72, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-25 08:22:56'),
(73, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-25 08:44:26'),
(74, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-25 08:46:40'),
(75, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-25 16:05:04'),
(76, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-26 03:24:08'),
(77, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-26 03:25:30'),
(78, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-26 05:59:06'),
(79, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-26 06:01:18'),
(80, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-26 06:12:45'),
(81, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-26 06:14:24'),
(82, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-26 06:15:48'),
(83, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-26 06:25:19'),
(84, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-26 06:33:11'),
(85, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 12)', 'products', 12, '127.0.0.1', '2025-10-26 06:33:34'),
(86, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal with Cheese – 8 Pieces (ID: 11)', 'products', 11, '127.0.0.1', '2025-10-26 06:33:44'),
(87, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic Pandesal - 10 Pieces (ID: 10)', 'products', 10, '127.0.0.1', '2025-10-26 06:34:29'),
(88, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic and Mushroom Sourdough - 3 Slices (ID: 4)', 'products', 4, '127.0.0.1', '2025-10-26 06:36:12'),
(89, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Sausage and Spinach Sourdough - 3 Slices (ID: 5)', 'products', 5, '127.0.0.1', '2025-10-26 06:36:50'),
(90, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #8 status to \'Preparing\'', 'orders', 8, '127.0.0.1', '2025-10-26 06:54:44'),
(91, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #7 status to \'Picked-up\'', 'orders', 7, '127.0.0.1', '2025-10-26 07:13:02'),
(92, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #6 status to \'Picked-up\'', 'orders', 6, '127.0.0.1', '2025-10-26 07:13:10'),
(93, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #8 status to \'Picked-up\'', 'orders', 8, '127.0.0.1', '2025-10-26 07:14:55'),
(94, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #11 status to \'Preparing\'', 'orders', 11, '127.0.0.1', '2025-10-26 07:15:58'),
(95, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #11 status to \'Delivered\'', 'orders', 11, '127.0.0.1', '2025-10-26 07:16:05'),
(96, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #12 status to \'Delivered\'', 'orders', 12, '127.0.0.1', '2025-10-26 07:16:13'),
(97, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #2 status to \'completed\'', 'bulk_orders', 2, '127.0.0.1', '2025-10-26 07:20:44'),
(98, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #1 status to \'completed\'', 'bulk_orders', 1, '127.0.0.1', '2025-10-26 07:20:48'),
(99, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #4 status to \'rejected\'', 'bulk_orders', 4, '127.0.0.1', '2025-10-26 07:22:25'),
(100, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #4 status to \'cancelled\'', 'bulk_orders', 4, '127.0.0.1', '2025-10-26 07:22:31'),
(101, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #3 status to \'completed\'', 'bulk_orders', 3, '127.0.0.1', '2025-10-26 07:23:01'),
(102, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #5 status to \'completed\'', 'bulk_orders', 5, '127.0.0.1', '2025-10-26 07:23:08'),
(103, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #6 status to \'rejected\'', 'bulk_orders', 6, '127.0.0.1', '2025-10-26 07:23:15'),
(104, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #6 status to \'cancelled\'', 'bulk_orders', 6, '127.0.0.1', '2025-10-26 07:23:21'),
(105, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-26 08:19:09'),
(106, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-26 08:19:44'),
(107, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #20 status to \'Picked-up\'', 'orders', 20, '127.0.0.1', '2025-10-27 12:15:28'),
(108, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #5 status to \'approved\' and sent email', 'order_refunds', 5, '127.0.0.1', '2025-10-27 12:18:17'),
(109, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #5 status to \'completed\' and sent voucher', 'order_refunds', 5, '127.0.0.1', '2025-10-27 12:39:41'),
(110, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 14)', 'products', 14, '127.0.0.1', '2025-10-27 16:55:48'),
(111, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-27 17:00:08'),
(112, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 13)', 'products', 13, '127.0.0.1', '2025-10-27 17:00:54'),
(113, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Sausage and Spinach Sourdough - 3 Slices (ID: 5)', 'products', 5, '127.0.0.1', '2025-10-27 17:01:36'),
(114, 2, 'Annalyn  De Chavez', 'CREATE', 'Created promotion/coupon: ', 'promotions', 0, '127.0.0.1', '2025-10-28 14:51:52'),
(115, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #27 status to \'Preparing\'', 'orders', 27, '172.20.0.1', '2025-10-28 19:15:02'),
(116, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #27 status to \'Ready for Pick-up\'', 'orders', 27, '172.20.0.1', '2025-10-28 19:20:11'),
(117, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Garlic and Mushroom Sourdough - 3 Slices (ID: 4)', 'products', 4, '223.25.25.130', '2025-10-29 03:17:43'),
(118, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: kahit ano (SKU: SD-00014)', 'products', 19, '223.25.25.130', '2025-10-29 06:09:41'),
(119, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: test product (SKU: SD-00015)', 'products', 20, '223.25.25.130', '2025-10-29 06:19:36'),
(120, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: kahit ano (SKU: SD-00016)', 'products', 21, '223.25.25.130', '2025-10-29 06:29:08'),
(121, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: saging (SKU: SD-00017)', 'products', 22, '223.25.25.130', '2025-10-29 06:43:55'),
(122, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: test product (SKU: SD-00018)', 'products', 23, '223.25.25.130', '2025-10-29 06:54:03'),
(123, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new carousel image: test', 'carousel_images', 0, '223.25.25.130', '2025-10-29 08:32:03'),
(124, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted carousel image: Sourdough 1', 'carousel_images', 1, '223.25.25.130', '2025-10-29 08:32:15'),
(125, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted carousel image: Sourdough 2', 'carousel_images', 5, '223.25.25.130', '2025-10-29 08:32:20'),
(126, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new carousel image: Sourdough', 'carousel_images', 0, '223.25.25.130', '2025-10-29 08:32:41'),
(127, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 8)', 'products', 8, '127.0.0.1', '2025-10-29 10:14:56'),
(128, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Sourdough Baguette (ID: 9)', 'products', 9, '127.0.0.1', '2025-10-29 10:16:08'),
(129, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 8)', 'products', 8, '127.0.0.1', '2025-10-29 10:18:40'),
(130, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Classic Sourdough Bread (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-29 10:22:26'),
(131, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Classic Sourdough Bread (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-29 10:22:31'),
(132, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated profile picture', 'users', 2, '223.25.25.130', '2025-10-29 10:51:07'),
(133, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated account profile information', 'users', 2, '223.25.25.130', '2025-10-29 10:51:19'),
(134, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated account profile information', 'users', 2, '223.25.25.130', '2025-10-29 10:51:43'),
(135, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: Banana Cake (SKU: SD-00001)', 'products', 1, '223.25.25.130', '2025-10-30 03:41:53'),
(136, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '223.25.25.130', '2025-10-30 03:42:32'),
(137, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '223.25.25.130', '2025-10-30 03:51:35'),
(138, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: Cinnamon Rolls – 6 Pieces (SKU: SD-00002)', 'products', 2, '223.25.25.130', '2025-10-30 03:52:27'),
(139, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #1 status to \'Delivered\'', 'orders', 1, '223.25.25.130', '2025-10-30 03:57:55'),
(140, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #1 status to \'Confirmed\'', 'orders', 1, '49.149.129.27', '2025-10-30 03:59:55'),
(141, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #1 status to \'Delivered\'', 'orders', 1, '131.226.105.71', '2025-10-30 04:16:01'),
(142, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #6 status to \'approved\' and sent email', 'order_refunds', 6, '131.226.105.71', '2025-10-30 04:19:05'),
(143, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #6 status to \'completed\' and sent voucher', 'order_refunds', 6, '131.226.105.71', '2025-10-30 04:19:27'),
(144, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Cassava Cake (ID: 2)', 'products', 2, '223.25.25.130', '2025-10-30 04:40:26'),
(145, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated pricing for bulk order #9 (Total: ₱1,850.00)', 'bulk_orders', 9, '209.35.171.14', '2025-10-30 07:25:56'),
(146, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #9 status to \'ready_for_delivery\'', 'bulk_orders', 9, '209.35.171.14', '2025-10-30 07:29:02'),
(147, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #2 status to \'Delivered\'', 'orders', 2, '223.25.25.130', '2025-10-30 08:17:39'),
(148, 2, 'Annalyn  De Chavez', 'CREATE', 'Added delivery location: test, test (4322)', 'delivery_locations', 2, '127.0.0.1', '2025-10-30 12:52:37'),
(149, 2, 'Annalyn  De Chavez', 'CREATE', 'Added delivery location: Test, ahasdfasd (3214)', 'delivery_locations', 3, '127.0.0.1', '2025-10-30 12:57:01'),
(150, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted delivery location: test, test (4322)', 'delivery_locations', 2, '127.0.0.1', '2025-10-30 12:58:08'),
(151, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated delivery location: Test, Test City (3214)', 'delivery_locations', 3, '127.0.0.1', '2025-10-30 13:05:33'),
(152, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-30 18:15:00'),
(153, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #3 status to \'Delivered\'', 'orders', 3, '127.0.0.1', '2025-10-30 19:54:28'),
(154, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #4 status to \'Delivered\'', 'orders', 4, '127.0.0.1', '2025-10-30 19:54:35'),
(155, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #5 status to \'Delivered\'', 'orders', 5, '127.0.0.1', '2025-10-30 19:54:42'),
(156, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #6 status to \'Delivered\'', 'orders', 6, '127.0.0.1', '2025-10-30 19:55:01'),
(157, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #7 status to \'Delivered\'', 'orders', 7, '127.0.0.1', '2025-10-30 19:55:08'),
(158, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #8 status to \'Picked-up\'', 'orders', 8, '127.0.0.1', '2025-10-30 19:55:15'),
(159, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #9 status to \'Delivered\'', 'orders', 9, '127.0.0.1', '2025-10-30 19:55:22'),
(160, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #10 status to \'Delivered\'', 'orders', 10, '127.0.0.1', '2025-10-30 19:55:45'),
(161, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #11 status to \'Delivered\'', 'orders', 11, '127.0.0.1', '2025-10-30 19:55:52'),
(162, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #12 status to \'Delivered\'', 'orders', 12, '127.0.0.1', '2025-10-30 19:55:59'),
(163, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #13 status to \'Picked-up\'', 'orders', 13, '127.0.0.1', '2025-10-30 19:56:06'),
(164, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-30 20:27:09'),
(165, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-31 06:03:00'),
(166, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-31 07:20:15'),
(167, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: Oat Porridge Sourdough Batard (SKU: SD-00003)', 'products', 3, '127.0.0.1', '2025-10-31 07:20:42'),
(168, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-10-31 07:39:41'),
(169, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted product: Cassava Cake', 'products', 2, '127.0.0.1', '2025-10-31 07:40:09'),
(170, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated pricing for bulk order #8 (Total: ₱3,200.00)', 'bulk_orders', 8, '127.0.0.1', '2025-10-31 11:10:15'),
(171, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated pricing for bulk order #11 (Regular: ₱0.00, Discounted: ₱2,000.00) - Auto-approved', 'bulk_orders', 11, '127.0.0.1', '2025-10-31 11:53:27'),
(172, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #12 (Discount Total: ₱3,800.00)', 'bulk_orders', 12, '127.0.0.1', '2025-10-31 12:45:20'),
(173, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed bulk order #12 status to \'approved\'', 'bulk_orders', 12, '127.0.0.1', '2025-10-31 13:09:34'),
(174, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #20 status to \'Preparing\'', 'orders', 20, '127.0.0.1', '2025-10-31 14:05:30'),
(175, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #20 status to \'Ready for Delivery\'', 'orders', 20, '127.0.0.1', '2025-10-31 14:05:39'),
(176, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #20 status to \'Out for Delivery\'', 'orders', 20, '127.0.0.1', '2025-10-31 14:05:47'),
(177, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #20 status to \'Delivered\'', 'orders', 20, '127.0.0.1', '2025-10-31 14:05:54'),
(178, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #20 status to \'Confirmed\'', 'orders', 20, '127.0.0.1', '2025-10-31 14:13:19'),
(179, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:07:47'),
(180, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:08:44'),
(181, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:09:06'),
(182, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:13:24'),
(183, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:14:23'),
(184, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:15:01'),
(185, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:16:19'),
(186, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:17:08'),
(187, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:18:19'),
(188, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:18:40'),
(189, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:19:03'),
(190, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:19:53'),
(191, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:20:46'),
(192, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:21:11'),
(193, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:21:57'),
(194, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:22:30'),
(195, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:22:57'),
(196, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:23:28'),
(197, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated About page content', 'about_content', 1, '127.0.0.1', '2025-10-31 15:23:49'),
(198, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated terms and conditions content', 'terms_conditions', 1, '127.0.0.1', '2025-10-31 15:40:14'),
(199, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated terms and conditions content', 'terms_conditions', 1, '127.0.0.1', '2025-10-31 15:45:23'),
(200, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image deactivated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:02:42'),
(201, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:06:04'),
(202, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:12:17'),
(203, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:16:27'),
(204, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:20:23'),
(205, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:20:42'),
(206, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:21:20'),
(207, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:21:45'),
(208, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:22:30'),
(209, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:23:05'),
(210, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:23:49'),
(211, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:24:16'),
(212, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:24:54'),
(213, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:25:20'),
(214, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:25:35'),
(215, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:26:06'),
(216, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:26:32'),
(217, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:26:38'),
(218, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:26:43'),
(219, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:27:30'),
(220, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:28:02'),
(221, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:28:14'),
(222, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:28:34'),
(223, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:28:55'),
(224, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:30:56'),
(225, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:31:21'),
(226, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:31:27'),
(227, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:33:51'),
(228, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:34:23'),
(229, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:34:50'),
(230, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:39:44'),
(231, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:39:51'),
(232, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:39:57'),
(233, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:40:03'),
(234, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted carousel image: test', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:43:02'),
(235, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new carousel image: Sourdough', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:44:04'),
(236, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new carousel image: olive', 'carousel_images', 0, '127.0.0.1', '2025-10-31 16:49:09'),
(237, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image deactivated: Sourdough', 'carousel_images', 1, '127.0.0.1', '2025-10-31 16:52:20'),
(238, 2, 'Annalyn  De Chavez', 'UPDATE', 'Carousel image activated: Sourdough', 'carousel_images', 1, '127.0.0.1', '2025-10-31 16:52:30'),
(239, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated carousel settings', 'carousel_settings', 1, '127.0.0.1', '2025-10-31 16:54:37'),
(240, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated carousel settings', 'carousel_settings', 1, '127.0.0.1', '2025-10-31 16:54:58'),
(241, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated carousel settings', 'carousel_settings', 1, '127.0.0.1', '2025-10-31 16:55:22'),
(242, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated carousel settings', 'carousel_settings', 1, '127.0.0.1', '2025-10-31 16:56:11'),
(243, 2, 'Annalyn  De Chavez', 'CREATE', 'Created promotion/coupon: ', 'promotions', 0, '127.0.0.1', '2025-10-31 18:13:29'),
(244, 2, 'Annalyn  De Chavez', 'CREATE', 'Created promotion/coupon: ', 'promotions', 0, '127.0.0.1', '2025-10-31 18:21:47'),
(245, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-01 01:13:01'),
(246, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-01 02:50:37'),
(247, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 02:52:08'),
(248, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-01 08:20:56'),
(249, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 08:25:36'),
(250, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 08:27:04'),
(251, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 08:29:33'),
(252, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 08:31:01'),
(253, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 08:32:11'),
(254, 2, 'Annalyn  De Chavez', 'CREATE', 'Added delivery location: Sariaya, Quezon (4322)', 'delivery_locations', 4, '127.0.0.1', '2025-11-01 09:41:16'),
(255, 2, 'Annalyn  De Chavez', 'CREATE', 'Added delivery location: Sta. Rosa, Laguna (4026)', 'delivery_locations', 5, '127.0.0.1', '2025-11-01 09:42:26'),
(256, 2, 'Annalyn  De Chavez', 'CREATE', 'Added delivery location: Cabuyao, Laguna (4025)', 'delivery_locations', 6, '127.0.0.1', '2025-11-01 09:43:34'),
(257, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated delivery location: Cabuyao, Laguna (4025)', 'delivery_locations', 6, '127.0.0.1', '2025-11-01 09:47:24'),
(258, 2, 'Annalyn  De Chavez', 'CREATE', 'Added delivery location: Calamba, Laguna (4027)', 'delivery_locations', 7, '127.0.0.1', '2025-11-01 09:51:46'),
(259, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated delivery location: Cabuyao, Laguna (4025)', 'delivery_locations', 6, '127.0.0.1', '2025-11-01 09:58:10'),
(260, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated delivery location: Calamba, Laguna (4027)', 'delivery_locations', 7, '127.0.0.1', '2025-11-01 10:00:45'),
(261, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated delivery location: Cabuyao, Laguna (4025)', 'delivery_locations', 6, '127.0.0.1', '2025-11-01 10:11:16'),
(262, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-01 11:54:18'),
(263, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #24 status to \'Picked-up\'', 'orders', 24, '127.0.0.1', '2025-11-01 16:31:38'),
(264, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-01 18:03:17'),
(265, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-01 18:46:13'),
(266, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-01 18:47:18'),
(267, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-02 01:26:09'),
(268, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-02 02:26:19'),
(269, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 03:57:12'),
(270, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: test (SKU: SD-00004)', 'products', 4, '223.25.25.130', '2025-11-02 09:48:49'),
(271, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted product: test', 'products', 4, '223.25.25.130', '2025-11-02 09:49:26'),
(272, 2, 'Annalyn  De Chavez', 'CREATE', 'Added new product: asdg (SKU: SD-00004)', 'products', 5, '223.25.25.130', '2025-11-02 09:58:34'),
(273, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted product: asdg', 'products', 5, '223.25.25.130', '2025-11-02 09:58:43'),
(274, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status enabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 11:01:48'),
(275, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #17 (Discount Total: ₱0.00) and auto-approved order', 'bulk_orders', 17, '127.0.0.1', '2025-11-02 12:50:19'),
(276, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #17 (Discount Total: ₱1,900.00) and auto-approved order', 'bulk_orders', 17, '127.0.0.1', '2025-11-02 12:50:32'),
(277, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 14:30:38'),
(278, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #39 status to \'Ready for Delivery\'', 'orders', 39, '127.0.0.1', '2025-11-02 14:34:12'),
(279, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status enabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:24'),
(280, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status disabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:26'),
(281, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status enabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:29'),
(282, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status disabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:31'),
(283, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status enabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:34'),
(284, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status disabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:36'),
(285, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status enabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:39'),
(286, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status disabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 16:21:41'),
(287, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,000.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:00:25'),
(288, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,000.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:00:32'),
(289, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,000.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:02:36'),
(290, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,000.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:04:24'),
(291, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,900.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:08:26'),
(292, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,900.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:08:43'),
(293, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated discount pricing for bulk order #18 (Total: ₱1,900.00) and auto-approved', 'bulk_orders', 18, '127.0.0.1', '2025-11-02 18:09:11'),
(294, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 22:07:41'),
(295, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 22:09:44'),
(296, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-02 22:13:06'),
(297, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-02 22:14:53'),
(298, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 22:20:30'),
(299, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 22:30:01'),
(300, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 22:39:28'),
(301, 2, 'Annalyn  De Chavez', 'UPDATE', 'Auto-status enabled', 'order_status_settings', NULL, '127.0.0.1', '2025-11-02 22:43:46'),
(302, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 23:15:36'),
(303, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 23:20:02'),
(304, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-02 23:43:35'),
(305, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '216.247.81.243', '2025-11-03 02:21:11'),
(306, 2, 'Annalyn  De Chavez', 'UPDATE', 'Restored product from archive: test', 'products', 4, '127.0.0.1', '2025-11-03 03:02:46'),
(307, 18, 'test test', 'UPDATE', 'Changed account password', 'users', 18, '::1', '2025-11-03 21:38:28'),
(308, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #3 status to \'Ready for Pick-up\'', 'orders', 3, '136.158.67.82', '2025-11-04 10:22:05'),
(309, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: test (ID: 4)', 'products', 4, '127.0.0.1', '2025-11-04 10:54:32'),
(310, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: test (ID: 4)', 'products', 4, '127.0.0.1', '2025-11-04 11:37:54'),
(311, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: test (ID: 4)', 'products', 4, '127.0.0.1', '2025-11-04 11:38:43'),
(312, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: test (ID: 4)', 'products', 4, '127.0.0.1', '2025-11-04 11:42:28'),
(313, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #3 status to \'Picked-up\'', 'orders', 3, '127.0.0.1', '2025-11-04 12:25:57'),
(314, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #3 status to \'Ready for Pick-up\'', 'orders', 3, '127.0.0.1', '2025-11-04 12:31:46'),
(315, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed order #3 status to \'Picked-up\'', 'orders', 3, '127.0.0.1', '2025-11-04 12:44:49'),
(316, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: test (ID: 4)', 'products', 4, '127.0.0.1', '2025-11-04 12:52:27'),
(317, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: test (ID: 4)', 'products', 4, '127.0.0.1', '2025-11-04 13:32:31'),
(318, 2, 'Annalyn  De Chavez', 'DELETE', 'Deleted product: test', 'products', 4, '127.0.0.1', '2025-11-04 13:37:23'),
(319, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-04 14:55:55'),
(320, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-04 15:15:15'),
(321, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-04 15:34:10'),
(322, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-04 16:43:44'),
(323, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-04 16:44:20'),
(324, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-05 03:17:10'),
(325, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-05 07:49:51'),
(326, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-05 09:07:30'),
(327, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-05 09:23:06'),
(328, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated chatbot knowledge base', 'chatbot_knowledge', 1, '127.0.0.1', '2025-11-05 10:05:47'),
(329, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated chatbot knowledge base', 'chatbot_knowledge', 1, '127.0.0.1', '2025-11-05 10:06:06'),
(330, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated chatbot knowledge base', 'chatbot_knowledge', 1, '127.0.0.1', '2025-11-05 10:06:19'),
(331, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-05 10:43:12'),
(332, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-05 10:45:04'),
(333, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '127.0.0.1', '2025-11-05 10:45:58'),
(334, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Banana Cake (ID: 1)', 'products', 1, '127.0.0.1', '2025-11-05 10:50:17'),
(335, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '223.25.25.130', '2025-11-08 13:06:48'),
(336, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '136.158.66.240', '2025-11-09 12:51:35'),
(337, 2, 'Annalyn  De Chavez', 'UPDATE', 'Updated product: Oat Porridge Sourdough Batard (ID: 3)', 'products', 3, '136.158.66.240', '2025-11-09 12:57:49'),
(338, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #9 status to \'approved\' and sent email', 'order_refunds', 9, '136.158.66.240', '2025-11-09 16:11:26'),
(339, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed refund request #9 status to \'completed\' and sent voucher', 'order_refunds', 9, '136.158.66.240', '2025-11-09 16:11:51'),
(340, 2, 'Annalyn  De Chavez', 'UPDATE', 'Changed account password', 'users', 2, '127.0.0.1', '2025-11-10 01:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `notif_id` int(11) NOT NULL,
  `notif_type` enum('order_new','order_status','order_warning','order_due','order_overdue','bulk_new','bulk_status','bulk_payment','refund_new','refund_status') NOT NULL,
  `notif_title` varchar(255) NOT NULL,
  `notif_message` text NOT NULL,
  `notif_link` varchar(500) DEFAULT NULL,
  `notif_reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`notif_id`, `notif_type`, `notif_title`, `notif_message`, `notif_link`, `notif_reference_id`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 'bulk_new', 'Bulk Order #17 - New Request', 'User @ashbee88 submitted a bulk order request for review.', '/backend/pages/bulks/bulk-order.php?id=17', 17, 1, '2025-11-02 12:22:38', '2025-11-02 12:42:36'),
(2, 'bulk_new', 'Bulk Order #18 - New Request', 'User @ashbee88 submitted a bulk order request for review.', '/backend/pages/bulks/bulk-order.php?id=18', 18, 1, '2025-11-02 17:22:33', '2025-11-04 12:47:07'),
(3, '', 'Refund Request #999 - New Request', 'User @johndoe submitted a refund request of ₱150.75 for Order #123. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 999, 1, '2025-11-04 12:32:36', '2025-11-04 12:47:07'),
(4, '', 'Refund Request #998 - Status Updated', 'Refund request from @janesmith for Order #124 has been approved.', '/backend/pages/refund/refund-request-lists.php', 998, 1, '2025-11-04 12:32:36', '2025-11-04 12:47:07'),
(5, '', 'Refund Request #7 - New Request', 'User Hannah Zepeda submitted a refund request of ₱230.00 for Order #3. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 7, 1, '2025-11-04 12:35:43', '2025-11-04 12:47:07'),
(6, 'refund_new', 'Refund Request #7 - New Request', 'User Hannah Zepeda submitted a refund request of ₱230.00 for Order #3. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 7, 1, '2025-11-04 12:39:08', '2025-11-04 12:47:07'),
(7, 'refund_new', 'Refund Request #888 - New Request', 'User @testuser submitted a refund request of ₱99.50 for Order #222. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 888, 1, '2025-11-04 12:40:41', '2025-11-04 12:47:07'),
(8, 'refund_status', 'Refund Request #889 - Status Updated', 'Refund request from @anotheruser for Order #223 has been approved.', '/backend/pages/refund/refund-request-lists.php', 889, 1, '2025-11-04 12:40:41', '2025-11-04 12:47:07'),
(9, 'order_due', 'Order #3 - 📅 Due Today', '📅 Order from Hannah Zepeda is due for pickup today on 11/04/25 at 6:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=3', 3, 1, '2025-11-04 12:42:21', '2025-11-04 12:47:07'),
(10, 'order_overdue', 'Order #2 - 🔴 OVERDUE', '🔴 URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25 at 6:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=2', 2, 1, '2025-11-04 12:42:21', '2025-11-04 12:47:07'),
(11, 'order_overdue', 'Order #5 - 🔴 OVERDUE', '🔴 URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=5', 5, 1, '2025-11-04 12:42:22', '2025-11-04 12:47:07'),
(12, 'order_overdue', 'Order #6 - 🔴 OVERDUE', '🔴 URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=6', 6, 1, '2025-11-04 12:42:23', '2025-11-04 12:47:07'),
(13, 'order_status', 'Order #3 - Status Updated', 'User Hannah Zepeda order status has been updated to Picked-up', '/backend/pages/orders/view-orders.php?order_id=3', 3, 1, '2025-11-04 12:44:50', '2025-11-04 12:47:07'),
(14, 'refund_new', 'Refund Request #888 - New Request', 'User @testuser submitted a refund request of ₱99.50 for Order #222. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 888, 1, '2025-11-04 12:45:19', '2025-11-04 12:47:07'),
(15, 'refund_status', 'Refund Request #889 - Status Updated', 'Refund request from @anotheruser for Order #223 has been approved.', '/backend/pages/refund/refund-request-lists.php', 889, 1, '2025-11-04 12:45:20', '2025-11-04 12:47:07'),
(16, 'refund_new', 'Refund Request #8 - New Request', 'User Hannah Zepeda submitted a refund request of ₱230.00 for Order #3. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 8, 1, '2025-11-04 12:45:58', '2025-11-04 12:47:07'),
(17, 'refund_new', 'Refund Request #888 - New Request', 'User @testuser submitted a refund request of ₱99.50 for Order #222. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 888, 1, '2025-11-04 12:46:44', '2025-11-04 12:47:07'),
(18, 'refund_status', 'Refund Request #889 - Status Updated', 'Refund request from @anotheruser for Order #223 has been approved.', '/backend/pages/refund/refund-request-lists.php', 889, 1, '2025-11-04 12:46:44', '2025-11-04 12:47:07'),
(19, 'order_overdue', 'Order #2 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25 at 6:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=2', 2, 1, '2025-11-04 17:05:18', '2025-11-05 08:33:43'),
(20, 'order_overdue', 'Order #5 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=5', 5, 1, '2025-11-04 17:05:19', '2025-11-04 17:14:07'),
(21, 'order_overdue', 'Order #6 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=6', 6, 1, '2025-11-04 17:05:19', '2025-11-05 08:33:43'),
(22, 'order_due', 'Order #15 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/05/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=15', 15, 1, '2025-11-05 08:32:04', '2025-11-05 08:33:43'),
(23, 'order_due', 'Order #16 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/05/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=16', 16, 1, '2025-11-05 08:32:04', '2025-11-05 08:33:43'),
(24, 'order_due', 'Order #8 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=8', 8, 1, '2025-11-05 16:36:07', '2025-11-10 02:00:46'),
(25, 'order_due', 'Order #9 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=9', 9, 1, '2025-11-05 16:36:08', '2025-11-10 02:00:46'),
(26, 'order_due', 'Order #12 - Due Today', 'Order from Aine Pascua is due for pickup today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=12', 12, 1, '2025-11-05 16:36:08', '2025-11-10 02:00:46'),
(27, 'order_due', 'Order #13 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=13', 13, 1, '2025-11-05 16:36:09', '2025-11-10 02:00:46'),
(28, 'order_due', 'Order #14 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=14', 14, 1, '2025-11-05 16:36:09', '2025-11-10 02:00:46'),
(29, 'order_due', 'Order #17 - Due Today', 'Order from Aine Pascua is due for pickup today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=17', 17, 1, '2025-11-05 16:36:09', '2025-11-10 02:00:46'),
(30, 'order_due', 'Order #18 - Due Today', 'Order from Test Customer 164415 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=18', 18, 1, '2025-11-05 16:36:10', '2025-11-10 02:00:46'),
(31, 'order_due', 'Order #19 - Due Today', 'Order from Test Customer 164431 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=19', 19, 1, '2025-11-05 16:36:10', '2025-11-10 02:00:46'),
(32, 'order_due', 'Order #20 - Due Today', 'Order from Test Customer 164553 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=20', 20, 1, '2025-11-05 16:36:10', '2025-11-10 02:00:46'),
(33, 'order_due', 'Order #21 - Due Today', 'Order from Test Customer 164745 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=21', 21, 1, '2025-11-05 16:36:11', '2025-11-10 02:00:46'),
(34, 'order_due', 'Order #22 - Due Today', 'Order from Test Customer 165008 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=22', 22, 1, '2025-11-05 16:36:11', '2025-11-10 02:00:46'),
(35, 'order_due', 'Order #23 - Due Today', 'Order from Test Customer 165012 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=23', 23, 1, '2025-11-05 16:36:11', '2025-11-10 02:00:46'),
(36, 'order_due', 'Order #24 - Due Today', 'Order from Test Customer 170115 is due for pickup today on 11/06/25 at 2:00 pm. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=24', 24, 1, '2025-11-05 16:36:12', '2025-11-10 02:00:46'),
(37, 'order_due', 'Order #25 - Due Today', 'Order from Aine Pascua is due for pickup today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=25', 25, 1, '2025-11-05 16:36:12', '2025-11-10 02:00:46'),
(38, 'order_due', 'Order #26 - Due Today', 'Order from Aine Pascua is due for pickup today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=26', 26, 1, '2025-11-05 16:36:12', '2025-11-10 02:00:46'),
(39, 'order_overdue', 'Order #2 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25 at 6:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=2', 2, 1, '2025-11-05 16:36:13', '2025-11-10 02:00:46'),
(40, 'order_overdue', 'Order #5 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=5', 5, 1, '2025-11-05 16:36:14', '2025-11-10 02:00:46'),
(41, 'order_overdue', 'Order #6 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=6', 6, 1, '2025-11-05 16:36:14', '2025-11-10 02:00:46'),
(42, 'order_overdue', 'Order #15 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/05/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=15', 15, 1, '2025-11-05 16:36:15', '2025-11-10 02:00:46'),
(43, 'order_overdue', 'Order #16 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/05/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=16', 16, 1, '2025-11-05 16:36:15', '2025-11-10 02:00:46'),
(44, 'order_new', 'Order #27 - New Order Placed', 'User Aine Pascua placed an order for pickup', '/backend/pages/orders/view-orders.php?order_id=27', 27, 1, '2025-11-06 05:13:13', '2025-11-10 02:00:46'),
(45, 'order_new', 'Order #28 - New Order Placed', 'User Aine Pascua placed an order for pickup', '/backend/pages/orders/view-orders.php?order_id=28', 28, 1, '2025-11-06 05:26:59', '2025-11-10 02:00:46'),
(46, 'order_new', 'Order #29 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/09/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=29', 29, 1, '2025-11-06 07:34:27', '2025-11-10 02:00:46'),
(47, 'order_new', 'Order #30 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/09/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=30', 30, 1, '2025-11-06 08:25:42', '2025-11-10 02:00:46'),
(48, 'order_new', 'Order #31 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/11/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=31', 31, 1, '2025-11-06 08:39:40', '2025-11-10 02:00:46'),
(49, 'order_new', 'Order #32 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/06/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=32', 32, 1, '2025-11-06 08:47:02', '2025-11-10 02:00:46'),
(50, 'order_due', 'Order #32 - Due Today', 'Order from Aine Pascua is due for delivery today on 11/06/25 at 9:00 am. Please ensure it\'s ready!', '/backend/pages/orders/view-orders.php?order_id=32', 32, 1, '2025-11-06 09:39:07', '2025-11-10 02:00:46'),
(51, 'order_new', 'Order #33 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/11/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=33', 33, 1, '2025-11-06 10:07:31', '2025-11-10 02:00:46'),
(52, 'order_new', 'Order #34 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/20/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=34', 34, 1, '2025-11-06 10:40:13', '2025-11-10 02:00:46'),
(53, 'order_new', 'Order #35 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/13/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=35', 35, 1, '2025-11-06 11:18:46', '2025-11-10 02:00:46'),
(54, 'order_overdue', 'Order #2 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25 at 6:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=2', 2, 1, '2025-11-07 12:03:59', '2025-11-10 02:00:46'),
(55, 'order_overdue', 'Order #5 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=5', 5, 1, '2025-11-07 12:04:00', '2025-11-10 02:00:46'),
(56, 'order_overdue', 'Order #6 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/03/25. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=6', 6, 1, '2025-11-07 12:04:00', '2025-11-10 02:00:46'),
(57, 'order_overdue', 'Order #8 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=8', 8, 1, '2025-11-07 12:04:00', '2025-11-10 02:00:46'),
(58, 'order_overdue', 'Order #9 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=9', 9, 1, '2025-11-07 12:04:01', '2025-11-10 02:00:46'),
(59, 'order_overdue', 'Order #12 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for pickup on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=12', 12, 1, '2025-11-07 12:04:02', '2025-11-10 02:00:46'),
(60, 'order_overdue', 'Order #13 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=13', 13, 1, '2025-11-07 12:04:03', '2025-11-10 02:00:46'),
(61, 'order_overdue', 'Order #14 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=14', 14, 1, '2025-11-07 12:04:03', '2025-11-10 02:00:46'),
(62, 'order_overdue', 'Order #15 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/05/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=15', 15, 1, '2025-11-07 12:04:04', '2025-11-10 02:00:46'),
(63, 'order_overdue', 'Order #16 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/05/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=16', 16, 1, '2025-11-07 12:04:04', '2025-11-10 02:00:46'),
(64, 'order_overdue', 'Order #17 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for pickup on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=17', 17, 1, '2025-11-07 12:04:05', '2025-11-10 02:00:46'),
(65, 'order_overdue', 'Order #18 -  OVERDUE', 'URGENT: Order from Test Customer 164415 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=18', 18, 1, '2025-11-07 12:04:06', '2025-11-10 02:00:46'),
(66, 'order_overdue', 'Order #19 -  OVERDUE', 'URGENT: Order from Test Customer 164431 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=19', 19, 1, '2025-11-07 12:04:06', '2025-11-10 02:00:46'),
(67, 'order_overdue', 'Order #20 -  OVERDUE', 'URGENT: Order from Test Customer 164553 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=20', 20, 1, '2025-11-07 12:04:07', '2025-11-10 02:00:46'),
(68, 'order_overdue', 'Order #21 -  OVERDUE', 'URGENT: Order from Test Customer 164745 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=21', 21, 1, '2025-11-07 12:04:08', '2025-11-10 02:00:46'),
(69, 'order_overdue', 'Order #22 -  OVERDUE', 'URGENT: Order from Test Customer 165008 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=22', 22, 1, '2025-11-07 12:04:08', '2025-11-10 02:00:46'),
(70, 'order_overdue', 'Order #23 -  OVERDUE', 'URGENT: Order from Test Customer 165012 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=23', 23, 1, '2025-11-07 12:04:08', '2025-11-10 02:00:46'),
(71, 'order_overdue', 'Order #24 -  OVERDUE', 'URGENT: Order from Test Customer 170115 is overdue for pickup on 11/06/25 at 2:00 pm. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=24', 24, 1, '2025-11-07 12:04:09', '2025-11-10 02:00:46'),
(72, 'order_overdue', 'Order #25 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for pickup on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=25', 25, 1, '2025-11-07 12:04:09', '2025-11-10 02:00:46'),
(73, 'order_overdue', 'Order #26 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for pickup on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=26', 26, 1, '2025-11-07 12:04:10', '2025-11-10 02:00:46'),
(74, 'order_overdue', 'Order #32 -  OVERDUE', 'URGENT: Order from Aine Pascua is overdue for delivery on 11/06/25 at 9:00 am. Please take immediate action!', '/backend/pages/orders/view-orders.php?order_id=32', 32, 1, '2025-11-07 12:04:10', '2025-11-10 02:00:46'),
(75, 'order_new', 'Order #36 - New Order Placed', 'User Enia Nya placed an order for delivery on 11/15/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=36', 36, 1, '2025-11-07 14:31:04', '2025-11-10 02:00:46'),
(76, 'order_new', 'Order #37 - New Order Placed', 'User Aine Pascua placed an order for delivery on 11/13/25 at 9:00 am', '/backend/pages/orders/view-orders.php?order_id=37', 37, 1, '2025-11-08 12:39:38', '2025-11-10 02:00:46'),
(77, 'refund_new', 'Refund Request #9 - New Request', 'User Hannah Zepeda submitted a refund request of ₱230.00 for Order #3. Please review and take action.', '/backend/pages/refund/refund-request-lists.php', 9, 1, '2025-11-09 16:08:48', '2025-11-10 02:00:46'),
(78, 'bulk_payment', 'Bulk Order #18 - Payment Submitted', 'User @ashbee88 uploaded proof of payment. Please verify the details.', '/backend/pages/bulks/bulk-order.php?id=18', 18, 0, '2025-11-10 07:22:46', '2025-11-10 07:22:46');

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
-- Table structure for table `availtoday_cart`
--

CREATE TABLE `availtoday_cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `availtoday_order_limit`
--

CREATE TABLE `availtoday_order_limit` (
  `id` int(11) NOT NULL,
  `limit_orders` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `availtoday_order_limit`
--

INSERT INTO `availtoday_order_limit` (`id`, `limit_orders`, `created_at`, `updated_at`) VALUES
(1, 6, '2025-11-02 03:22:55', '2025-11-02 03:22:55'),
(2, 3, '2025-11-02 03:23:07', '2025-11-02 03:23:07'),
(3, 5, '2025-11-02 06:07:57', '2025-11-02 06:07:57');

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
(1, 'Pick-Up'),
(2, 'Delivery'),
(3, 'Delivery or Pick-Up');

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
  `adblog_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `cloud_url` varchar(500) DEFAULT NULL,
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `author` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`adblog_id`, `title`, `description`, `image_path`, `cloud_url`, `cloud_public_id`, `cloud_provider`, `author`, `created_at`) VALUES
(21, 'Sausage and Spinach Sourdough Toast!', 'Adding more to our selection of cheese toast for you to enjoy.\r\nSmoky sausage and the cheesy spinach topping will surely make your day.\r\nAvailable on #toastdaythursday!\r\nReserve yours now!', NULL, 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762756416/neocafe/admin_blog/admin_blog_6911893559fed.jpg', 'neocafe/admin_blog/admin_blog_6911893559fed', 'cloudinary', 'Admin', '2025-11-10 07:33:38');

-- --------------------------------------------------------

--
-- Table structure for table `bulk_orders`
--

CREATE TABLE `bulk_orders` (
  `id` int(11) NOT NULL,
  `unique_order_id` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `billing_address` text NOT NULL,
  `order_type` enum('delivery','pickup') NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `purpose` text NOT NULL,
  `date_needed` date NOT NULL,
  `time_needed` time NOT NULL,
  `note` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','payment_received','payment_rejected','ready_for_delivery','cancelled','rejected','completed') NOT NULL DEFAULT 'pending',
  `proof_of_payment` varchar(500) DEFAULT NULL,
  `admin_updated` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_total` decimal(10,2) DEFAULT NULL COMMENT 'Discounted total amount (NULL if no discount applied)',
  `discount_changes_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bulk_orders`
--

INSERT INTO `bulk_orders` (`id`, `unique_order_id`, `user_id`, `name`, `contact`, `email`, `billing_address`, `order_type`, `delivery_address`, `purpose`, `date_needed`, `time_needed`, `note`, `total_amount`, `total_items`, `status`, `proof_of_payment`, `admin_updated`, `admin_notes`, `created_at`, `updated_at`, `discount_total`, `discount_changes_count`) VALUES
(18, 'BO000018', 7, 'Hannah Zepeda', '09127589340', 'ob.zepeda.hannah.f19@gmail.com', 'Purok 1, Sampaloc 1, Sariaya Quezon 4322 near TR4', 'delivery', 'Purok 1, Sampaloc 1, Sariaya Quezon 4322 near TR4', 'Birthday Party', '2025-11-22', '10:00:00', '', 2000.00, 10, 'approved', '[{\"filename\":\"bulk_payment_full_BO000018_1762759862.jpg\",\"cloud_url\":\"https:\\/\\/res.cloudinary.com\\/dvdccumbs\\/image\\/upload\\/v1762759364\\/neocafe\\/bulk_payments\\/bulk_payment_full_BO000018_1762759862.jpg\",\"cloud_public_id\":\"neocafe\\/bulk_payments\\/bulk_payment_full_BO000018_1762759862\",\"cloud_provider\":\"cloudinary\",\"type\":\"full\",\"uploaded_at\":\"2025-11-10 15:31:08\",\"original_name\":\"img-eurosoft-scaled.jpg\"}]', '2025-11-02 18:09:10', NULL, '2025-11-02 17:22:32', '2025-11-10 07:22:45', 1900.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `bulk_order_items`
--

CREATE TABLE `bulk_order_items` (
  `id` int(11) NOT NULL,
  `bulk_order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL COMMENT 'Discounted price per item (NULL if no discount applied)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bulk_order_items`
--

INSERT INTO `bulk_order_items` (`id`, `bulk_order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `subtotal`, `discount_price`) VALUES
(42, 18, 1, 'Banana Cake', 200.00, 10, 2000.00, 190.00);

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
(1, '04:00:00', '23:00:00', '2025-08-10 03:51:16', '2025-11-10 02:01:08');

-- --------------------------------------------------------

--
-- Table structure for table `carousel_images`
--

CREATE TABLE `carousel_images` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `cloud_url` text DEFAULT NULL,
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

INSERT INTO `carousel_images` (`id`, `image_url`, `cloud_public_id`, `cloud_provider`, `cloud_url`, `title`, `display_order`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761929033/Home/assets/images/carousel/carousel_1761929533.jpg', 'Home/assets/images/carousel/carousel_1761929533', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761929033/Home/assets/images/carousel/carousel_1761929533.jpg', 'Sourdough', 1, 1, '2025-10-31 16:44:04', '2025-10-31 16:52:30', 2, 2),
(2, 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761929166/Home/assets/images/carousel/carousel_1761929660.jpg', 'Home/assets/images/carousel/carousel_1761929660', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761929166/Home/assets/images/carousel/carousel_1761929660.jpg', 'olive', 2, 1, '2025-10-31 16:49:08', '2025-10-31 16:49:08', 2, NULL);

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
(1, 'Welcome to Neo Cafe', 'Explore our latest selection of freshly baked breads and products, made with love and quality ingredients.', 'Explore Menu', '/frontend/pages/products/products-dashboard.php', '2025-05-06 14:03:12', '2025-10-31 16:54:37', 5, 2);

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
(45, 13, 1, 1, 200.00, '2025-10-30 07:39:15', '2025-10-30 07:39:15'),
(63, 15, 3, 1, 230.00, '2025-10-31 11:06:09', '2025-10-31 11:06:09'),
(84, 7, 3, 18, 230.00, '2025-11-04 08:29:37', '2025-11-09 12:48:11'),
(101, 23, 3, 50, 230.00, '2025-11-08 12:33:50', '2025-11-08 12:33:50'),
(103, 17, 3, 1, 230.00, '2025-11-09 19:29:46', '2025-11-09 19:29:46');

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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Pastries', 'pastries', 'Cakes, breads, and baked goods', 2, 1, '2025-10-23 04:32:55', '2025-10-23 09:36:17'),
(4, 'Bread', 'bread', 'Sweet treats and desserts', 1, 1, '2025-10-23 04:32:55', '2025-10-23 09:36:17'),
(5, 'Cakes', 'cakes', 'Light snacks and appetizers', 3, 1, '2025-10-23 04:32:55', '2025-10-23 09:36:17'),
(6, 'Softdrinks', '', 'Sweet', 4, 0, '2025-10-27 16:58:26', '2025-10-27 16:58:55');

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
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19');
INSERT INTO `chatbot_knowledge` (`id`, `content`, `updated_at`) VALUES
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(1, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:08:44'),
(2, 'The chatbot is about a cafe named NeoCafe, and this are the informations that is necessary for the users:\nMODE OF DELIVERY:\nLalamove \nGrab\nToktok - for nearby villages\n\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE AND REHEATING SUGGESTIONS:\n-STORAGE:\n To prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\n  REHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw.\nThe melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet, just deliveries\n\nMODE OF PAYMENT:\n-GCash\n-Maya', '2025-05-13 01:23:26'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19'),
(0, 'The chatbot is about online bread store named NeoCafe, answer specific answers based by their questions and don\'t add anymore information about NeoCafe and these are the informations that is necessary for the users:\n\nMODE OF DELIVERY:\nLalamove\nGrab\nToktok - for nearby villages\n\nEXACT LOCATION:\nhttps://maps.app.goo.gl/sRBP3pamzeqZLpSbA\n\nGOOD FOR HOW MANY DAYS?\n-BREAD SHELF LIFE:\n 3-4 days at room temperature\n\nSTORAGE:\nTo prolong moistness, slice the bread, wrap with plastic and keep in a freezer. It can last up up to a month. \n\nREHEATING:\nPlace frozen slices directly into preheated pan or oven toasted. No need to thaw. The melted ice will give extra moisture while reheating to give that crispier texture.\n\nINGREDIENTS:\n-Unbleached Wheat Flour, Water, Sourdough (unbleached whole wheat flour, rye flour), salt.\n\nDO YOU HAVE PHYSICAL STORE?\n-we dont have physical store yet\n\nMODE OF PAYMENT:\n-GCash\n-Maya\n-Bank Transfer\n\nTypes of Products\n- Same-Day Orders, Available only on specific scheduled dates (depending on product availability). Orders must be placed within business hours for the scheduled date.\n- Preorders - Available on specific scheduled days (e.g., Monday, Wednesday, Thursday). Orders must be placed at least 2 days before the chosen delivery or pick-up date. Example: If the delivery/pick-up day is Monday, the order must be placed by Saturday.', '2025-11-05 10:06:19');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupon_usage`
--

INSERT INTO `coupon_usage` (`id`, `user_id`, `coupon_id`, `order_id`, `used_at`) VALUES
(2, 5, 1, 13, '2025-10-30 19:21:08'),
(4, 16, 1, 7, '2025-11-03 01:57:21'),
(5, 17, 1, 8, '2025-11-04 09:31:05');

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
(38, '11111111111', 'Brgy. San Pedro II (Western), Malvar, Batangas, Region IV-A (CALABARZON)', '2025-05-23 04:44:28'),
(39, '09327548312', NULL, '2025-09-07 13:48:49'),
(40, '09327548312', NULL, '2025-09-09 08:38:32'),
(41, '09327548312', NULL, '2025-09-09 14:42:12'),
(42, '09327548312', NULL, '2025-09-09 15:29:33'),
(43, '09327548312', NULL, '2025-09-09 15:31:31'),
(44, '09238183823', 'hdshahdshjdfsjhdfsjh, Calamba, Laguna 4027', '2025-09-09 15:44:13'),
(45, '09238183823', NULL, '2025-09-09 15:45:11'),
(46, '09238183823', NULL, '2025-09-09 15:45:15'),
(47, '09238183823', NULL, '2025-09-09 15:45:18'),
(48, '09238183823', 'asdf, Sta. Rosa, Laguna 4034', '2025-09-09 15:51:52'),
(0, '09567457347', NULL, '2025-09-11 05:29:43'),
(0, '09567457347', NULL, '2025-09-11 05:34:26'),
(0, '09567457347', NULL, '2025-09-11 05:35:33'),
(0, '09567457347', NULL, '2025-09-11 05:38:42'),
(0, '09567457347', NULL, '2025-09-11 05:40:48'),
(0, '09567457347', NULL, '2025-09-11 05:43:47'),
(0, '09567457347', NULL, '2025-09-11 05:47:25');

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
(1, '2026-01-11', 0, '2025-10-31 02:58:40', '2025-10-31 02:58:40', 1),
(2, '2025-12-04', 0, '2025-10-31 04:25:30', '2025-10-31 04:25:30', 1),
(17, '2025-11-22', 0, '2025-11-05 15:02:23', '2025-11-05 15:02:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_locations`
--

CREATE TABLE `delivery_locations` (
  `delivery_id` int(11) NOT NULL,
  `municipality` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `postal_code` varchar(4) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `delivery_locations`
--

INSERT INTO `delivery_locations` (`delivery_id`, `municipality`, `city`, `postal_code`, `delivery_fee`, `created_at`, `updated_at`) VALUES
(1, 'Test Municipality', 'Test City', '1234', 50.00, '2025-10-11 14:49:52', '2025-10-11 14:49:52'),
(3, 'Test', 'Test City', '3214', 133.00, '2025-10-30 12:57:01', '2025-10-30 13:05:33'),
(4, 'Sariaya', 'Quezon', '4322', 60.00, '2025-11-01 09:41:16', '2025-11-01 09:41:16'),
(5, 'Sta. Rosa', 'Laguna', '4026', 50.00, '2025-11-01 09:42:26', '2025-11-01 09:42:26'),
(6, 'Cabuyao', 'Laguna', '4025', 110.00, '2025-11-01 09:43:34', '2025-11-01 10:11:16'),
(7, 'Calamba', 'Laguna', '4027', 110.00, '2025-11-01 09:51:46', '2025-11-01 10:00:45');

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
-- Table structure for table `image_migrations`
--

CREATE TABLE `image_migrations` (
  `id` int(11) NOT NULL,
  `local_path` varchar(500) NOT NULL,
  `cloudinary_url` varchar(500) NOT NULL,
  `cloudinary_public_id` varchar(255) NOT NULL,
  `image_type` enum('product','carousel','payment','refund','general','admin') NOT NULL,
  `migration_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('success','failed') DEFAULT 'success',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `image_migrations`
--

INSERT INTO `image_migrations` (`id`, `local_path`, `cloudinary_url`, `cloudinary_public_id`, `image_type`, `migration_date`, `status`, `error_message`) VALUES
(1, 'assets/images/20211114_064721.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761592976/neocafe/assets/general_20211114_064721.jpg', 'neocafe/assets/general_20211114_064721', 'general', '2025-10-27 19:22:58', 'success', NULL),
(2, 'assets/images/20211114_064746.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761592987/neocafe/assets/general_20211114_064746.jpg', 'neocafe/assets/general_20211114_064746', 'general', '2025-10-27 19:23:09', 'success', NULL),
(3, 'assets/images/20211115_233550 (1).jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593045/neocafe/assets/general_20211115_233550__1_.jpg', 'neocafe/assets/general_20211115_233550__1_', 'general', '2025-10-27 19:24:07', 'success', NULL),
(4, 'assets/images/20211115_233558.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593099/neocafe/assets/general_20211115_233558.jpg', 'neocafe/assets/general_20211115_233558', 'general', '2025-10-27 19:25:01', 'success', NULL),
(5, 'assets/images/blog.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593102/neocafe/assets/general_blog.jpg', 'neocafe/assets/general_blog', 'general', '2025-10-27 19:25:03', 'success', NULL),
(6, 'assets/images/car.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593108/neocafe/assets/general_car.jpg', 'neocafe/assets/general_car', 'general', '2025-10-27 19:25:10', 'success', NULL),
(7, 'assets/images/products-category.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593116/neocafe/assets/general_products-category.jpg', 'neocafe/assets/general_products-category', 'general', '2025-10-27 19:25:18', 'success', NULL),
(8, 'assets/images/20211114_064721.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761592976/neocafe/assets/general_20211114_064721.jpg', 'neocafe/assets/general_20211114_064721', 'general', '2025-10-27 19:28:12', 'success', NULL),
(9, 'assets/images/20211114_064746.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761592987/neocafe/assets/general_20211114_064746.jpg', 'neocafe/assets/general_20211114_064746', 'general', '2025-10-27 19:28:19', 'success', NULL),
(10, 'assets/images/20211115_233550 (1).jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593045/neocafe/assets/general_20211115_233550__1_.jpg', 'neocafe/assets/general_20211115_233550__1_', 'general', '2025-10-27 19:28:26', 'success', NULL),
(11, 'assets/images/20211115_233558.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593099/neocafe/assets/general_20211115_233558.jpg', 'neocafe/assets/general_20211115_233558', 'general', '2025-10-27 19:29:16', 'success', NULL),
(12, 'assets/images/blog.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593102/neocafe/assets/general_blog.jpg', 'neocafe/assets/general_blog', 'general', '2025-10-27 19:29:18', 'success', NULL),
(13, 'assets/images/car.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593108/neocafe/assets/general_car.jpg', 'neocafe/assets/general_car', 'general', '2025-10-27 19:30:10', 'success', NULL),
(14, 'assets/images/products-category.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593116/neocafe/assets/general_products-category.jpg', 'neocafe/assets/general_products-category', 'general', '2025-10-27 19:30:18', 'success', NULL),
(15, 'assets/images/weekly-product.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593423/neocafe/assets/general_weekly-product.jpg', 'neocafe/assets/general_weekly-product', 'general', '2025-10-27 19:30:26', 'success', NULL),
(16, 'assets/images/facebook.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593427/neocafe/assets/general_facebook.png', 'neocafe/assets/general_facebook', 'general', '2025-10-27 19:30:28', 'success', NULL),
(17, 'assets/images/image.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593429/neocafe/assets/general_image.png', 'neocafe/assets/general_image', 'general', '2025-10-27 19:30:30', 'success', NULL),
(18, 'assets/images/instagram.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593431/neocafe/assets/general_instagram.png', 'neocafe/assets/general_instagram', 'general', '2025-10-27 19:30:32', 'success', NULL),
(19, 'assets/images/login-background.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593437/neocafe/assets/general_login-background.jpg', 'neocafe/assets/general_login-background', 'general', '2025-10-27 19:30:38', 'success', NULL),
(20, 'assets/images/logo.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593440/neocafe/assets/general_logo.png', 'neocafe/assets/general_logo', 'general', '2025-10-27 19:30:40', 'success', NULL),
(21, 'assets/images/mail.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593442/neocafe/assets/general_mail.png', 'neocafe/assets/general_mail', 'general', '2025-10-27 19:30:43', 'success', NULL),
(22, 'assets/images/neocafegoldlogo.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593444/neocafe/assets/general_neocafegoldlogo.png', 'neocafe/assets/general_neocafegoldlogo', 'general', '2025-10-27 19:30:45', 'success', NULL),
(23, 'assets/images/remove.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593447/neocafe/assets/general_remove.png', 'neocafe/assets/general_remove', 'general', '2025-10-27 19:30:47', 'success', NULL),
(24, 'assets/images/user-logo.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593449/neocafe/assets/general_user-logo.png', 'neocafe/assets/general_user-logo', 'general', '2025-10-27 19:30:50', 'success', NULL),
(25, 'assets/images/20210514195047.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593453/neocafe/assets/general_20210514195047.jpg', 'neocafe/assets/general_20210514195047', 'general', '2025-10-27 19:30:55', 'success', NULL),
(26, 'assets/images/44184768_Unknown.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593486/neocafe/assets/general_44184768_Unknown.jpg', 'neocafe/assets/general_44184768_Unknown', 'general', '2025-10-27 19:31:29', 'success', NULL),
(27, 'assets/images/44452320_Unknown.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593505/neocafe/assets/general_44452320_Unknown.jpg', 'neocafe/assets/general_44452320_Unknown', 'general', '2025-10-27 19:31:47', 'success', NULL),
(28, 'assets/images/IMG_1171.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593553/neocafe/assets/general_IMG_1171.jpg', 'neocafe/assets/general_IMG_1171', 'general', '2025-10-27 19:32:37', 'success', NULL),
(29, 'assets/images/IMG_1174.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593558/neocafe/assets/general_IMG_1174.jpg', 'neocafe/assets/general_IMG_1174', 'general', '2025-10-27 19:32:39', 'success', NULL),
(30, 'assets/images/IMG_1311.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593577/neocafe/assets/general_IMG_1311.jpg', 'neocafe/assets/general_IMG_1311', 'general', '2025-10-27 19:32:58', 'success', NULL),
(31, 'assets/images/IMG_4377.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593583/neocafe/assets/general_IMG_4377.jpg', 'neocafe/assets/general_IMG_4377', 'general', '2025-10-27 19:33:05', 'success', NULL),
(32, 'assets/images/IMG_4379.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593588/neocafe/assets/general_IMG_4379.jpg', 'neocafe/assets/general_IMG_4379', 'general', '2025-10-27 19:33:09', 'success', NULL),
(33, 'assets/images/blogs-category.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593606/neocafe/assets/general_blogs-category.jpg', 'neocafe/assets/general_blogs-category', 'general', '2025-10-27 19:33:28', 'success', NULL),
(34, 'assets/bulk_payments/bulk_payment_1_1757079768.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593610/neocafe/bulk_payments/payment_bulk_payment_1_1757079768.jpg', 'neocafe/bulk_payments/payment_bulk_payment_1_1757079768', 'payment', '2025-10-27 19:33:32', 'success', NULL),
(35, 'assets/bulk_payments/bulk_payment_1_1757079905.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593618/neocafe/bulk_payments/payment_bulk_payment_1_1757079905.jpg', 'neocafe/bulk_payments/payment_bulk_payment_1_1757079905', 'payment', '2025-10-27 19:33:40', 'success', NULL),
(36, 'assets/bulk_payments/bulk_payment_1_1757080141.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593627/neocafe/bulk_payments/payment_bulk_payment_1_1757080141.jpg', 'neocafe/bulk_payments/payment_bulk_payment_1_1757080141', 'payment', '2025-10-27 19:33:49', 'success', NULL),
(37, 'assets/bulk_payments/bulk_payment_BO000005_1760896252.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593772/neocafe/bulk_payments/payment_bulk_payment_BO000005_1760896252.jpg', 'neocafe/bulk_payments/payment_bulk_payment_BO000005_1760896252', 'payment', '2025-10-27 19:36:16', 'success', NULL),
(38, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760896760.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761593920/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896760.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896760', 'payment', '2025-10-27 19:38:43', 'success', NULL),
(39, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760896938.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594075/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896938.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896938', 'payment', '2025-10-27 19:41:19', 'success', NULL),
(40, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897015.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594230/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897015.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897015', 'payment', '2025-10-27 19:43:54', 'success', NULL),
(41, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897039.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594240/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897039.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897039', 'payment', '2025-10-27 19:44:03', 'success', NULL),
(42, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897094.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594394/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897094.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897094', 'payment', '2025-10-27 19:46:37', 'success', NULL),
(43, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897223.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594403/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897223.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897223', 'payment', '2025-10-27 19:46:47', 'success', NULL),
(44, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897284.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594414/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897284.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897284', 'payment', '2025-10-27 19:46:58', 'success', NULL),
(45, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897299.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594424/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897299.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897299', 'payment', '2025-10-27 19:47:08', 'success', NULL),
(46, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897369.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594579/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897369.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897369', 'payment', '2025-10-27 19:49:43', 'success', NULL),
(47, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897503.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594742/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897503.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897503', 'payment', '2025-10-27 19:52:25', 'success', NULL),
(48, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897563.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594907/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897563.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897563', 'payment', '2025-10-27 19:55:11', 'success', NULL),
(49, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897649.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595078/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897649.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897649', 'payment', '2025-10-27 19:58:02', 'success', NULL),
(50, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897680.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595088/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897680.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897680', 'payment', '2025-10-27 19:58:11', 'success', NULL),
(51, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897693.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595240/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897693.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897693', 'payment', '2025-10-27 20:00:43', 'success', NULL),
(52, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897705.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595249/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897705.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897705', 'payment', '2025-10-27 20:00:52', 'success', NULL),
(53, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897712.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595259/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897712.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897712', 'payment', '2025-10-27 20:01:04', 'success', NULL),
(54, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897757.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595412/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897757.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897757', 'payment', '2025-10-27 20:03:35', 'success', NULL),
(55, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760898051.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595564/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898051.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898051', 'payment', '2025-10-27 20:06:07', 'success', NULL),
(56, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760898074.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595715/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898074.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898074', 'payment', '2025-10-27 20:08:38', 'success', NULL),
(57, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760898111.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595870/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898111.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898111', 'payment', '2025-10-27 20:11:13', 'success', NULL),
(58, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896418.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595880/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896418.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896418', 'payment', '2025-10-27 20:11:22', 'success', NULL),
(59, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896436.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595889/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896436.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896436', 'payment', '2025-10-27 20:11:32', 'success', NULL),
(60, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896450.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595898/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896450.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896450', 'payment', '2025-10-27 20:11:42', 'success', NULL),
(61, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896583.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595908/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896583.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896583', 'payment', '2025-10-27 20:11:52', 'success', NULL),
(62, 'assets/bulk_payments/bulk_payment_remaining_BO000005_1760896594.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596060/neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896594.jpg', 'neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896594', 'payment', '2025-10-27 20:14:23', 'success', NULL),
(63, 'assets/bulk_payments/bulk_payment_remaining_BO000005_1760896708.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596209/neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896708.jpg', 'neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896708', 'payment', '2025-10-27 20:16:52', 'success', NULL),
(64, 'assets/refund-proofs/refund_5_7_1761302742.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596215/neocafe/refund_proofs/refund_refund_5_7_1761302742.jpg', 'neocafe/refund_proofs/refund_refund_5_7_1761302742', 'refund', '2025-10-27 20:16:56', 'success', NULL),
(65, 'assets/refund-proofs/refund_10_7_1761045218.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596218/neocafe/refund_proofs/refund_refund_10_7_1761045218.png', 'neocafe/refund_proofs/refund_refund_10_7_1761045218', 'refund', '2025-10-27 20:16:59', 'success', NULL),
(66, 'assets/refund-proofs/refund_20_5_1761567434.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596221/neocafe/refund_proofs/refund_refund_20_5_1761567434.png', 'neocafe/refund_proofs/refund_refund_20_5_1761567434', 'refund', '2025-10-27 20:17:02', 'success', NULL),
(67, 'assets/refund-proofs/refund_2_7_1761317057.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596226/neocafe/refund_proofs/refund_refund_2_7_1761317057.jpg', 'neocafe/refund_proofs/refund_refund_2_7_1761317057', 'refund', '2025-10-27 20:17:07', 'success', NULL),
(68, 'assets/images/20211114_064721.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596827/neocafe/assets/general_20211114_064721.jpg', 'neocafe/assets/general_20211114_064721', 'general', '2025-10-27 20:27:09', 'success', NULL),
(69, 'assets/images/20211114_064746.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596834/neocafe/assets/general_20211114_064746.jpg', 'neocafe/assets/general_20211114_064746', 'general', '2025-10-27 20:27:16', 'success', NULL),
(70, 'assets/images/20211115_233550 (1).jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596884/neocafe/assets/general_20211115_233550__1_.jpg', 'neocafe/assets/general_20211115_233550__1_', 'general', '2025-10-27 20:28:06', 'success', NULL),
(71, 'assets/images/20211115_233558.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596936/neocafe/assets/general_20211115_233558.jpg', 'neocafe/assets/general_20211115_233558', 'general', '2025-10-27 20:28:58', 'success', NULL),
(72, 'assets/images/blog.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596939/neocafe/assets/general_blog.jpg', 'neocafe/assets/general_blog', 'general', '2025-10-27 20:29:00', 'success', NULL),
(73, 'assets/images/car.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596994/neocafe/assets/general_car.jpg', 'neocafe/assets/general_car', 'general', '2025-10-27 20:29:56', 'success', NULL),
(74, 'assets/images/products-category.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597001/neocafe/assets/general_products-category.jpg', 'neocafe/assets/general_products-category', 'general', '2025-10-27 20:30:03', 'success', NULL),
(75, 'assets/images/weekly-product.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597008/neocafe/assets/general_weekly-product.jpg', 'neocafe/assets/general_weekly-product', 'general', '2025-10-27 20:30:11', 'success', NULL),
(76, 'assets/images/facebook.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597012/neocafe/assets/general_facebook.png', 'neocafe/assets/general_facebook', 'general', '2025-10-27 20:30:13', 'success', NULL),
(77, 'assets/images/image.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597014/neocafe/assets/general_image.png', 'neocafe/assets/general_image', 'general', '2025-10-27 20:30:16', 'success', NULL),
(78, 'assets/images/instagram.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597017/neocafe/assets/general_instagram.png', 'neocafe/assets/general_instagram', 'general', '2025-10-27 20:30:19', 'success', NULL),
(79, 'assets/images/login-background.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597023/neocafe/assets/general_login-background.jpg', 'neocafe/assets/general_login-background', 'general', '2025-10-27 20:30:25', 'success', NULL),
(80, 'assets/images/logo.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597026/neocafe/assets/general_logo.png', 'neocafe/assets/general_logo', 'general', '2025-10-27 20:30:27', 'success', NULL),
(81, 'assets/images/mail.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597029/neocafe/assets/general_mail.png', 'neocafe/assets/general_mail', 'general', '2025-10-27 20:30:30', 'success', NULL),
(82, 'assets/images/neocafegoldlogo.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597032/neocafe/assets/general_neocafegoldlogo.png', 'neocafe/assets/general_neocafegoldlogo', 'general', '2025-10-27 20:30:33', 'success', NULL),
(83, 'assets/images/remove.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597034/neocafe/assets/general_remove.png', 'neocafe/assets/general_remove', 'general', '2025-10-27 20:30:35', 'success', NULL),
(84, 'assets/images/user-logo.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597037/neocafe/assets/general_user-logo.png', 'neocafe/assets/general_user-logo', 'general', '2025-10-27 20:30:38', 'success', NULL),
(85, 'assets/images/20210514195047.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597059/neocafe/assets/general_20210514195047.jpg', 'neocafe/assets/general_20210514195047', 'general', '2025-10-27 20:31:02', 'success', NULL),
(86, 'assets/images/44184768_Unknown.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597066/neocafe/assets/general_44184768_Unknown.jpg', 'neocafe/assets/general_44184768_Unknown', 'general', '2025-10-27 20:31:09', 'success', NULL),
(87, 'assets/images/44452320_Unknown.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597073/neocafe/assets/general_44452320_Unknown.jpg', 'neocafe/assets/general_44452320_Unknown', 'general', '2025-10-27 20:31:15', 'success', NULL),
(88, 'assets/images/IMG_1171.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597119/neocafe/assets/general_IMG_1171.jpg', 'neocafe/assets/general_IMG_1171', 'general', '2025-10-27 20:32:03', 'success', NULL),
(89, 'assets/images/IMG_1174.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597124/neocafe/assets/general_IMG_1174.jpg', 'neocafe/assets/general_IMG_1174', 'general', '2025-10-27 20:32:06', 'success', NULL),
(90, 'assets/images/IMG_1311.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597130/neocafe/assets/general_IMG_1311.jpg', 'neocafe/assets/general_IMG_1311', 'general', '2025-10-27 20:32:11', 'success', NULL),
(91, 'assets/images/IMG_4377.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597136/neocafe/assets/general_IMG_4377.jpg', 'neocafe/assets/general_IMG_4377', 'general', '2025-10-27 20:32:19', 'success', NULL),
(92, 'assets/images/IMG_4379.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597157/neocafe/assets/general_IMG_4379.jpg', 'neocafe/assets/general_IMG_4379', 'general', '2025-10-27 20:32:39', 'success', NULL),
(93, 'assets/images/blogs-category.JPG', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597174/neocafe/assets/general_blogs-category.jpg', 'neocafe/assets/general_blogs-category', 'general', '2025-10-27 20:32:55', 'success', NULL),
(94, 'assets/bulk_payments/bulk_payment_1_1757079768.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597178/neocafe/bulk_payments/payment_bulk_payment_1_1757079768.jpg', 'neocafe/bulk_payments/payment_bulk_payment_1_1757079768', 'payment', '2025-10-27 20:33:00', 'success', NULL),
(95, 'assets/bulk_payments/bulk_payment_1_1757079905.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597182/neocafe/bulk_payments/payment_bulk_payment_1_1757079905.jpg', 'neocafe/bulk_payments/payment_bulk_payment_1_1757079905', 'payment', '2025-10-27 20:33:04', 'success', NULL),
(96, 'assets/bulk_payments/bulk_payment_1_1757080141.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597191/neocafe/bulk_payments/payment_bulk_payment_1_1757080141.jpg', 'neocafe/bulk_payments/payment_bulk_payment_1_1757080141', 'payment', '2025-10-27 20:33:13', 'success', NULL),
(97, 'assets/bulk_payments/bulk_payment_BO000005_1760896252.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597343/neocafe/bulk_payments/payment_bulk_payment_BO000005_1760896252.jpg', 'neocafe/bulk_payments/payment_bulk_payment_BO000005_1760896252', 'payment', '2025-10-27 20:35:46', 'success', NULL),
(98, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760896760.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597500/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896760.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896760', 'payment', '2025-10-27 20:38:23', 'success', NULL),
(99, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760896938.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597655/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896938.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760896938', 'payment', '2025-10-27 20:40:59', 'success', NULL),
(100, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897015.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597666/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897015.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897015', 'payment', '2025-10-27 20:41:10', 'success', NULL),
(101, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897039.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597676/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897039.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897039', 'payment', '2025-10-27 20:41:19', 'success', NULL),
(102, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897094.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597685/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897094.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897094', 'payment', '2025-10-27 20:41:30', 'success', NULL),
(103, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897223.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597841/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897223.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897223', 'payment', '2025-10-27 20:44:04', 'success', NULL),
(104, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897284.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761597993/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897284.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897284', 'payment', '2025-10-27 20:46:36', 'success', NULL),
(105, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897299.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761598002/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897299.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897299', 'payment', '2025-10-27 20:46:46', 'success', NULL),
(106, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897369.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761598157/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897369.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897369', 'payment', '2025-10-27 20:49:20', 'success', NULL),
(107, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897503.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761598167/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897503.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897503', 'payment', '2025-10-27 20:49:30', 'success', NULL),
(108, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897563.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761594907/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897563.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897563', 'payment', '2025-10-27 20:52:05', 'success', NULL),
(109, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897649.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595078/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897649.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897649', 'payment', '2025-10-27 20:52:15', 'success', NULL),
(110, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897680.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595088/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897680.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897680', 'payment', '2025-10-27 20:54:46', 'success', NULL),
(111, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897693.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595240/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897693.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897693', 'payment', '2025-10-27 20:54:56', 'success', NULL),
(112, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897705.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595249/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897705.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897705', 'payment', '2025-10-27 20:55:06', 'success', NULL),
(113, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897712.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595259/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897712.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897712', 'payment', '2025-10-27 20:55:16', 'success', NULL),
(114, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760897757.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595412/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897757.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760897757', 'payment', '2025-10-27 20:55:27', 'success', NULL),
(115, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760898051.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595564/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898051.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898051', 'payment', '2025-10-27 20:57:59', 'success', NULL),
(116, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760898074.jpg', '', '', 'payment', '2025-10-27 20:58:30', 'failed', 'Server returned unexpected status code - 502 - <html>\r\n<head><title>502 Bad Gateway</title></head>\r\n<body>\r\n<center><h1>502 Bad Gateway</h1></center>\r\n</body>\r\n</html>\r\n'),
(117, 'assets/bulk_payments/bulk_payment_downpayment_BO000005_1760898111.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595870/neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898111.jpg', 'neocafe/bulk_payments/payment_bulk_payment_downpayment_BO000005_1760898111', 'payment', '2025-10-27 20:58:40', 'success', NULL),
(118, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896418.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595880/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896418.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896418', 'payment', '2025-10-27 21:01:16', 'success', NULL),
(119, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896436.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595889/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896436.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896436', 'payment', '2025-10-27 21:01:26', 'success', NULL),
(120, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896450.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595898/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896450.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896450', 'payment', '2025-10-27 21:01:36', 'success', NULL),
(121, 'assets/bulk_payments/bulk_payment_full_BO000005_1760896583.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761595908/neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896583.jpg', 'neocafe/bulk_payments/payment_bulk_payment_full_BO000005_1760896583', 'payment', '2025-10-27 21:04:00', 'success', NULL),
(122, 'assets/bulk_payments/bulk_payment_remaining_BO000005_1760896594.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596060/neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896594.jpg', 'neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896594', 'payment', '2025-10-27 21:04:10', 'success', NULL),
(123, 'assets/bulk_payments/bulk_payment_remaining_BO000005_1760896708.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596209/neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896708.jpg', 'neocafe/bulk_payments/payment_bulk_payment_remaining_BO000005_1760896708', 'payment', '2025-10-27 21:04:21', 'success', NULL),
(124, 'assets/refund-proofs/refund_5_7_1761302742.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596215/neocafe/refund_proofs/refund_refund_5_7_1761302742.jpg', 'neocafe/refund_proofs/refund_refund_5_7_1761302742', 'refund', '2025-10-27 21:04:32', 'success', NULL),
(125, 'assets/refund-proofs/refund_10_7_1761045218.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596218/neocafe/refund_proofs/refund_refund_10_7_1761045218.png', 'neocafe/refund_proofs/refund_refund_10_7_1761045218', 'refund', '2025-10-27 21:04:35', 'success', NULL),
(126, 'assets/refund-proofs/refund_20_5_1761567434.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596221/neocafe/refund_proofs/refund_refund_20_5_1761567434.png', 'neocafe/refund_proofs/refund_refund_20_5_1761567434', 'refund', '2025-10-27 21:04:37', 'success', NULL),
(127, 'assets/refund-proofs/refund_2_7_1761317057.png', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761596226/neocafe/refund_proofs/refund_refund_2_7_1761317057.jpg', 'neocafe/refund_proofs/refund_refund_2_7_1761317057', 'refund', '2025-10-27 21:04:42', 'success', NULL),
(128, 'product-images/Classic_Sourdough_Bread_1757776354/additional_1757776354_2.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599088/neocafe/products/product_1_additional_1757776354_2.jpg', 'neocafe/products/product_1_additional_1757776354_2', 'product', '2025-10-27 21:04:51', 'success', NULL),
(129, 'product-images/Three_Cheese_and_Basil_-_3_Slices_1757776431/primary_1757776431.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599105/neocafe/products/product_2_primary_1757776431.jpg', 'neocafe/products/product_2_primary_1757776431', 'product', '2025-10-27 21:05:07', 'success', NULL),
(130, 'product-images/Cheesy_Bacon_Sourdough_-_3_Slices_1757777066/primary_1757777066.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599140/neocafe/products/product_3_primary_1757777066.jpg', 'neocafe/products/product_3_primary_1757777066', 'product', '2025-10-27 21:05:44', 'success', NULL),
(131, 'product-images/Garlic_and_Mushroom_Sourdough_-_3_Slices_1757777128/primary_1757777128.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599199/neocafe/products/product_4_primary_1757777128.jpg', 'neocafe/products/product_4_primary_1757777128', 'product', '2025-10-27 21:06:42', 'success', NULL),
(132, 'product-images/Sausage_and_Spinach_Sourdough_-_3_Slices_1757777177/primary_1757777177.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599206/neocafe/products/product_5_primary_1757777177.jpg', 'neocafe/products/product_5_primary_1757777177', 'product', '2025-10-27 21:06:49', 'success', NULL),
(133, 'product-images/Sesame_Sourdough_Batard_1757777250/primary_1757777250.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599258/neocafe/products/product_6_primary_1757777250.jpg', 'neocafe/products/product_6_primary_1757777250', 'product', '2025-10-27 21:07:41', 'success', NULL),
(134, 'product-images/Olive_Sourdough_Batard_1757777312/primary_1757777312.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599264/neocafe/products/product_7_primary_1757777312.jpg', 'neocafe/products/product_7_primary_1757777312', 'product', '2025-10-27 21:07:46', 'success', NULL),
(135, 'product-images/Olive_Sourdough_Batard_1757777312/additional_1757777312_1.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599334/neocafe/products/product_7_additional_1757777312_1.jpg', 'neocafe/products/product_7_additional_1757777312_1', 'product', '2025-10-27 21:08:57', 'success', NULL),
(136, 'product-images/Olive_Sourdough_Batard_1757777312/additional_1757777312_2.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599398/neocafe/products/product_7_additional_1757777312_2.jpg', 'neocafe/products/product_7_additional_1757777312_2', 'product', '2025-10-27 21:10:01', 'success', NULL),
(137, 'product-images/Oat_Porridge_Sourdough_Batard_1757777390/primary_1757777390.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599403/neocafe/products/product_8_primary_1757777390.jpg', 'neocafe/products/product_8_primary_1757777390', 'product', '2025-10-27 21:10:06', 'success', NULL),
(138, 'product-images/Oat_Porridge_Sourdough_Batard_1757777390/additional_1757777390_1.jpg', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761599416/neocafe/products/product_8_additional_1757777390_1.jpg', 'neocafe/products/product_8_additional_1757777390_1', 'product', '2025-10-27 21:10:19', 'success', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `image_moderation_log`
--

CREATE TABLE `image_moderation_log` (
  `id` int(11) NOT NULL,
  `public_id` varchar(255) NOT NULL,
  `status` enum('approved','rejected','pending') NOT NULL,
  `kind` varchar(50) NOT NULL COMMENT 'Moderation provider: aws_rek, google_vision, etc.',
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Full moderation response with confidence scores' CHECK (json_valid(`response_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(15, 5, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(16, 3, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(17, 2, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(18, 25, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(19, 19, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(20, 23, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(21, 6, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(22, 18, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(23, 22, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(24, 4, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-16 15:48:58', NULL, NULL),
(25, 20, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(26, 21, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-16 15:48:58', NULL, NULL),
(29, 30, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', '', NULL, 0, '2025-05-16 16:22:02', NULL, NULL),
(34, 31, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', '', NULL, 0, '2025-05-17 01:07:05', NULL, NULL),
(35, 5, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:51:54', NULL, NULL),
(36, 30, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(37, 3, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:51:54', NULL, NULL),
(38, 2, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:51:54', NULL, NULL),
(39, 25, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(40, 26, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(41, 28, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(42, 31, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(43, 19, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(44, 23, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(45, 6, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(46, 18, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(47, 22, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:51:54', NULL, NULL),
(48, 4, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:51:54', NULL, NULL),
(49, 20, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(50, 21, 'Promotion: Olive Sourdough Batard', 'Olive Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:51:54', NULL, NULL),
(51, 5, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:52:06', NULL, NULL),
(52, 30, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(53, 3, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:52:06', NULL, NULL),
(54, 2, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:52:06', NULL, NULL),
(55, 25, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(56, 26, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(57, 28, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(58, 31, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(59, 19, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(60, 23, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(61, 6, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(62, 18, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(63, 22, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:52:06', NULL, NULL),
(64, 4, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 03:52:06', NULL, NULL),
(65, 20, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(66, 21, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 03:52:06', NULL, NULL),
(68, 5, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 06:47:33', NULL, NULL),
(69, 30, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(70, 3, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 06:47:33', NULL, NULL),
(71, 2, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 06:47:33', NULL, NULL),
(72, 25, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(73, 26, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(74, 28, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(75, 31, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(76, 19, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(77, 23, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(78, 6, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(79, 18, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(80, 22, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 06:47:33', NULL, NULL),
(81, 4, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-05-22 06:47:33', NULL, NULL),
(82, 20, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(83, 21, 'Promotion: Classic Sourdough Batard', 'Classic Sourdough Batard has been marked as Featured.', 'promotion', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-05-22 06:47:33', NULL, NULL),
(89, 35, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', '', NULL, 0, '2025-05-23 04:37:58', NULL, NULL),
(0, 0, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 1, '2025-09-10 15:32:18', NULL, NULL),
(0, 0, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 1, '2025-09-11 07:50:05', NULL, NULL),
(0, 4, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 1, '2025-09-11 16:13:12', NULL, NULL),
(0, 5, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 1, '2025-09-11 23:11:03', NULL, NULL),
(0, 8, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-10-15 13:22:45', NULL, NULL),
(0, 9, 'You received a refund voucher!', 'Your refund has been processed. Voucher code: VCHR-0C8DF856 (₱880.00, expires 2025-11-21)', 'promotion', NULL, 0, '2025-10-22 02:24:03', 10, NULL),
(0, 9, 'You received a refund voucher!', 'Your refund has been processed. Voucher code: VCHR-0B11E650 (₱880.00, expires 2025-11-22)', 'promotion', NULL, 0, '2025-10-22 02:53:57', 10, NULL),
(0, 10, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-10-22 05:17:32', NULL, NULL),
(0, 5, 'You received a refund voucher!', 'Your refund has been processed. Voucher code: VCHR-1E5631D0 (₱600.00, expires 2025-11-27)', 'promotion', NULL, 1, '2025-10-27 12:39:37', 20, NULL),
(0, 11, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-10-29 13:17:38', NULL, NULL),
(0, 13, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 1, '2025-10-30 07:04:10', NULL, NULL),
(0, 15, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-10-31 11:04:00', NULL, NULL),
(0, 5, 'Order Status Update', 'Your order #1 have been updated to Delivered.', 'order_update', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-11-02 23:49:52', 1, '../../pages/cart/order_details.php?order_id=1'),
(0, 16, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-11-03 01:39:08', NULL, NULL),
(0, 16, 'Order Status Update', 'Your order #7 have been updated to Delivered.', 'order_update', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-11-03 01:59:26', 7, '../../pages/cart/order_details.php?order_id=7'),
(0, 5, 'Order Status Update', 'Your order #4 have been updated to Delivered.', 'order_update', '/NeoExclusiveCafe/assets/images/default-product.png', 0, '2025-11-03 02:49:55', 4, '../../pages/cart/order_details.php?order_id=4'),
(0, 17, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 1, '2025-11-03 20:42:30', NULL, NULL),
(0, 20, NULL, 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-11-03 22:18:43', NULL, NULL),
(0, 7, 'Order Status Update', 'Your order #3 have been updated to Ready for Pick-up.', 'order_update', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-11-04 10:22:06', 3, '../../pages/cart/order_details.php?order_id=3'),
(0, 7, 'Order Status Update', 'Your order #3 have been updated to Picked-up.', 'order_update', '/NeoExclusiveCafe/assets/images/default-product.png', 1, '2025-11-04 12:44:51', 3, '../../pages/cart/order_details.php?order_id=3'),
(0, 22, 'Welcome to NeoExclusiveCafe!', 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-11-06 13:48:56', NULL, NULL),
(0, 23, 'Welcome to NeoExclusiveCafe!', 'Welcome to NeoExclusiveCafe! Your account has been verified.', 'system_alert', NULL, 0, '2025-11-07 14:09:41', NULL, NULL),
(0, 7, 'You received a refund voucher!', 'Your refund has been processed. Voucher code: VCHR-235CAD00 (₱230.00, expires 2025-12-10)', 'promotion', NULL, 0, '2025-11-09 16:11:50', 3, NULL);

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
(49, '2025-08-23', 'not_accepting', NULL, '2025-08-23 13:56:40', '2025-08-23 13:56:40'),
(0, '2025-09-11', 'not_accepting', NULL, '2025-09-11 07:40:28', '2025-09-11 07:40:28'),
(0, '2025-09-12', 'accepting', NULL, '2025-09-11 07:41:28', '2025-09-11 07:41:28'),
(0, '2025-09-12', 'accepting', NULL, '2025-09-11 07:41:32', '2025-09-11 07:41:32'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 12:53:09', '2025-09-13 12:53:09'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 12:53:11', '2025-09-13 12:53:11'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 12:53:20', '2025-09-13 12:53:20'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 12:53:22', '2025-09-13 12:53:22'),
(0, '2025-09-19', 'not_accepting', NULL, '2025-09-13 12:53:53', '2025-09-13 12:53:53'),
(0, '2025-09-19', 'accepting', NULL, '2025-09-13 12:54:15', '2025-09-13 12:54:15'),
(0, '2025-09-18', 'accepting', NULL, '2025-09-13 12:57:18', '2025-09-13 12:57:18'),
(0, '2025-09-18', 'accepting', NULL, '2025-09-13 12:57:19', '2025-09-13 12:57:19'),
(0, '2025-09-18', 'accepting', NULL, '2025-09-13 12:57:26', '2025-09-13 12:57:26'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 12:59:06', '2025-09-13 12:59:06'),
(0, '2025-09-16', 'not_accepting', NULL, '2025-09-13 13:00:50', '2025-09-13 13:00:50'),
(0, '2025-09-16', 'not_accepting', NULL, '2025-09-13 13:01:20', '2025-09-13 13:01:20'),
(0, '2025-09-17', 'not_accepting', NULL, '2025-09-13 13:02:20', '2025-09-13 13:02:20'),
(0, '2025-09-16', 'not_accepting', NULL, '2025-09-13 13:17:33', '2025-09-13 13:17:33'),
(0, '2025-09-16', 'not_accepting', NULL, '2025-09-13 13:17:48', '2025-09-13 13:17:48'),
(0, '2025-09-17', 'not_accepting', NULL, '2025-09-13 13:18:10', '2025-09-13 13:18:10'),
(0, '2025-09-17', 'not_accepting', NULL, '2025-09-13 13:18:18', '2025-09-13 13:18:18'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 13:18:28', '2025-09-13 13:18:28'),
(0, '2025-09-16', 'accepting', NULL, '2025-09-13 13:18:39', '2025-09-13 13:18:39'),
(0, '2025-09-16', 'accepting', NULL, '2025-09-13 13:18:41', '2025-09-13 13:18:41'),
(0, '2025-09-17', 'not_accepting', NULL, '2025-09-13 13:21:08', '2025-09-13 13:21:08'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 13:21:14', '2025-09-13 13:21:14'),
(0, '2025-09-15', 'not_accepting', NULL, '2025-09-13 13:21:29', '2025-09-13 13:21:29'),
(0, '2025-09-15', 'not_accepting', NULL, '2025-09-13 13:21:31', '2025-09-13 13:21:31'),
(0, '2025-09-15', 'accepting', NULL, '2025-09-13 13:21:39', '2025-09-13 13:21:39'),
(0, '2025-09-15', 'not_accepting', NULL, '2025-09-13 13:21:47', '2025-09-13 13:21:47'),
(0, '2025-09-15', 'accepting', NULL, '2025-09-13 13:21:55', '2025-09-13 13:21:55'),
(0, '2025-09-16', 'accepting', NULL, '2025-09-13 13:22:18', '2025-09-13 13:22:18'),
(0, '2025-09-16', 'not_accepting', NULL, '2025-09-13 13:22:20', '2025-09-13 13:22:20'),
(0, '2025-09-16', 'accepting', NULL, '2025-09-13 13:22:26', '2025-09-13 13:22:26'),
(0, '2025-09-16', 'accepting', NULL, '2025-09-13 13:22:29', '2025-09-13 13:22:29'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 13:22:49', '2025-09-13 13:22:49'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 13:22:50', '2025-09-13 13:22:50'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 13:23:12', '2025-09-13 13:23:12'),
(0, '2025-09-17', 'accepting', NULL, '2025-09-13 13:23:20', '2025-09-13 13:23:20'),
(0, '2025-09-19', 'accepting', NULL, '2025-09-13 13:23:42', '2025-09-13 13:23:42'),
(0, '2025-09-19', 'accepting', NULL, '2025-09-13 13:23:44', '2025-09-13 13:23:44'),
(0, '2025-09-15', 'accepting', NULL, '2025-09-13 13:24:51', '2025-09-13 13:24:51'),
(0, '2025-09-15', 'accepting', NULL, '2025-09-13 13:25:41', '2025-09-13 13:25:41'),
(0, '2025-09-15', 'not_accepting', NULL, '2025-09-13 13:25:53', '2025-09-13 13:25:53'),
(0, '2025-09-15', 'accepting', NULL, '2025-09-13 13:26:02', '2025-09-13 13:26:02'),
(0, '2025-10-21', 'not_accepting', NULL, '2025-10-20 05:59:02', '2025-10-20 05:59:02'),
(0, '2025-10-21', 'accepting', NULL, '2025-10-20 05:59:14', '2025-10-20 05:59:14'),
(0, '2025-10-23', 'not_accepting', NULL, '2025-10-22 05:55:15', '2025-10-22 05:55:15'),
(0, '2025-10-24', 'not_accepting', NULL, '2025-10-22 05:55:31', '2025-10-22 05:55:31'),
(0, '2026-01-11', 'not_accepting', NULL, '2025-10-31 02:58:40', '2025-10-31 02:58:40'),
(0, '2025-12-04', 'not_accepting', NULL, '2025-10-31 04:25:30', '2025-10-31 04:25:30'),
(0, '2025-11-22', 'accepting', NULL, '2025-11-05 14:10:16', '2025-11-05 14:10:16'),
(0, '2025-11-22', 'accepting', NULL, '2025-11-05 14:13:09', '2025-11-05 14:13:09'),
(0, '2025-11-22', 'accepting', NULL, '2025-11-05 14:14:03', '2025-11-05 14:14:03'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:15:01', '2025-11-05 14:15:01'),
(0, '2025-11-22', 'accepting', NULL, '2025-11-05 14:15:13', '2025-11-05 14:15:13'),
(0, '2025-11-20', 'accepting', NULL, '2025-11-05 14:26:30', '2025-11-05 14:26:30'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:27:31', '2025-11-05 14:27:31'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:32:19', '2025-11-05 14:32:19'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:35:02', '2025-11-05 14:35:02'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:38:14', '2025-11-05 14:38:14'),
(0, '2025-11-18', 'not_accepting', NULL, '2025-11-05 14:40:19', '2025-11-05 14:40:19'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:42:29', '2025-11-05 14:42:29'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 14:46:38', '2025-11-05 14:46:38'),
(0, '2025-11-22', 'accepting', NULL, '2025-11-05 14:47:47', '2025-11-05 14:47:47'),
(0, '2025-11-22', 'accepting', NULL, '2025-11-05 15:02:24', '2025-11-05 15:02:24'),
(0, '2025-11-22', 'not_accepting', NULL, '2025-11-05 15:02:47', '2025-11-05 15:02:47');

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
(1, '2025-11-03 06:43:09', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'sdfhdfg, Test, Test City 3214', 'gcash', 2, 510.00, 'Delivered', 'Delivery', '2025-11-03', NULL, NULL, '', NULL, NULL, NULL, NULL, '2025-11-03 07:49:51', 'src_PqAdku6f71DXx4VDCrdiS214', 'paid', 510.00, '2025-11-02 22:43:09'),
(2, '2025-11-03 06:45:22', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'sdfhdfg, Test, Test City 3214', 'gcash', 1, 280.00, 'Ready for Delivery', 'Delivery', '2025-11-03', NULL, '06:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_NxDZ4wEzzKnwQ4CoRW3umXMz', 'paid', 280.00, '2025-11-02 22:45:22'),
(3, '2025-11-03 06:47:37', 'Hannah Zepeda', '09127589340', 'ob.zepeda.hannah.f19@gmail.com', 'Purok 1, Sampaloc 1, Sariaya, Quezon', 'gcash', 1, 230.00, 'Picked-up', 'Pick-up', NULL, '2025-11-04', NULL, '', NULL, NULL, '06:00:00', NULL, NULL, 'src_S3R2k2j8DJ2YQCGxGLB5hqaz', 'paid', 230.00, '2025-11-02 22:47:37'),
(4, '2025-11-03 07:22:27', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'sdfhdfg, Test, Test City 3214', 'gcash', 2, 510.00, 'Delivered', 'Delivery', '2025-11-03', NULL, NULL, '', NULL, NULL, NULL, NULL, '2025-11-03 10:49:54', 'src_g8iEfiLhJ2uJPgfwe3vqq3v5', 'paid', 510.00, '2025-11-02 23:22:27'),
(5, '2025-11-03 07:27:57', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'sdfhdfg, Test, Test City 3214', 'gcash', 1, 280.00, 'Ready for Delivery', 'Delivery', '2025-11-03', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'src_vnprEwH9Qjfm2vjErJ29NrJX', 'paid', 280.00, '2025-11-02 23:27:57'),
(6, '2025-11-03 07:46:45', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'sdfhdfg, Test, Test City 3214', 'gcash', 1, 280.00, 'Ready for Delivery', 'Delivery', '2025-11-03', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'src_hPedxMrbHHc52dBrBSkmtEpz', 'paid', 280.00, '2025-11-02 23:46:45'),
(7, '2025-11-03 09:57:16', 'Allysa Borja', '09261738261', 'allysagene@outlook.com', 'GCFF J, Sariaya, Quezon 4322', 'gcash', 4, 920.00, 'Delivered', 'Delivery', '2025-11-03', NULL, NULL, '', NULL, NULL, NULL, NULL, '2025-11-03 09:59:25', 'src_ytuzEuz6Ti216jLYAKucyQEC', 'paid', 920.00, '2025-11-03 01:57:16'),
(8, '2025-11-04 17:30:55', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 3, 740.00, 'Confirmed', 'Delivery', '2025-11-06', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_uoyKqEgQxCaK4gPyAgTn9ELE', 'paid', 740.00, '2025-11-04 09:30:55'),
(9, '2025-11-04 17:41:35', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 1, 280.00, 'Confirmed', 'Delivery', '2025-11-06', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_VLmGne2FiiQEHK62rbd5GKMb', 'paid', 280.00, '2025-11-04 09:41:35'),
(10, '2025-11-05 13:26:05', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 2, 450.00, 'Confirmed', 'Delivery', '2025-11-08', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_ociVJRHTh9mBqpEc5XLiTL7i', 'paid', 450.00, '2025-11-05 05:26:05'),
(11, '2025-11-05 13:26:09', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 2, 450.00, 'Confirmed', 'Delivery', '2025-11-08', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_ociVJRHTh9mBqpEc5XLiTL7i', 'paid', 450.00, '2025-11-05 05:26:09'),
(12, '2025-11-05 14:04:26', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 1, 200.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, '', NULL, NULL, '09:00:00', NULL, NULL, 'src_E2917gTCTkEoQ2JjjGH2iRab', 'paid', 200.00, '2025-11-05 06:04:26'),
(13, '2025-11-05 14:10:44', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 4, 970.00, 'Confirmed', 'Delivery', '2025-11-06', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_BFPMSCWAn5EJBKbrYJnYosqP', 'paid', 970.00, '2025-11-05 06:10:44'),
(14, '2025-11-05 14:10:47', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 4, 970.00, 'Confirmed', 'Delivery', '2025-11-06', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_BFPMSCWAn5EJBKbrYJnYosqP', 'paid', 970.00, '2025-11-05 06:10:47'),
(15, '2025-11-05 15:51:57', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 3, 740.00, 'Confirmed', 'Delivery', '2025-11-05', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_xkjxyzhEPZ5KZjrqcUrjsN3L', 'paid', 740.00, '2025-11-05 07:51:57'),
(16, '2025-11-05 15:52:01', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 3, 740.00, 'Confirmed', 'Delivery', '2025-11-05', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_xkjxyzhEPZ5KZjrqcUrjsN3L', 'paid', 740.00, '2025-11-05 07:52:01'),
(17, '2025-11-05 15:58:53', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 1, 230.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, '', NULL, NULL, '09:00:00', NULL, NULL, 'src_QYSEKey84Lrto54VhiGxPh3N', 'paid', 230.00, '2025-11-05 07:58:53'),
(18, '2025-11-05 16:44:15', 'Test Customer 164415', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 16:44:15', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(19, '2025-11-05 16:44:31', 'Test Customer 164431', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 16:44:31', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(20, '2025-11-05 16:45:52', 'Test Customer 164553', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 16:45:53', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(21, '2025-11-05 16:47:45', 'Test Customer 164745', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 16:47:45', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(22, '2025-11-05 16:50:08', 'Test Customer 165008', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 16:50:08', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(23, '2025-11-05 16:50:11', 'Test Customer 165012', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 16:50:12', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(24, '2025-11-05 17:01:14', 'Test Customer 170115', '09123456789', 'test@example.com', 'Test Address, Test City', 'Cash on Delivery', 2, 250.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, 'TEST ORDER - Created by test tool at 2025-11-05 17:01:15', NULL, NULL, '14:00:00', NULL, NULL, NULL, 'pending', NULL, NULL),
(25, '2025-11-05 17:50:59', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 4, 920.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, '', NULL, NULL, '09:00:00', NULL, NULL, 'src_DXWSpy2jFHfnLAApPuELRkKq', 'paid', 920.00, '2025-11-05 09:50:59'),
(26, '2025-11-05 17:58:04', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 1, 230.00, 'Confirmed', 'Pick-up', NULL, '2025-11-06', NULL, '', NULL, NULL, '09:00:00', NULL, NULL, 'src_9Dcf9QCzNdDrVmdcaHKVFHE9', 'paid', 230.00, '2025-11-05 09:58:04'),
(27, '2025-11-06 13:13:07', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 2, 500.00, 'Confirmed', 'Pick-up', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'src_test_1762405978_4513', 'paid', 500.00, '2025-11-06 05:13:07'),
(28, '2025-11-06 13:26:54', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 2, 500.00, 'Confirmed', 'Pick-up', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'src_test_1762406801_7384', 'paid', 500.00, '2025-11-06 05:26:54'),
(29, '2025-11-06 15:34:25', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 6, 1430.00, 'Confirmed', 'Delivery', '2025-11-09', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_K1PLmztfrGmrdvaZQFEjzQQ2', 'paid', 1430.00, '2025-11-06 07:34:25'),
(30, '2025-11-06 16:25:40', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 5, 1200.00, 'Confirmed', 'Delivery', '2025-11-09', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_FAiY1mugX3dA1YBRHDkjMJoa', 'paid', 1200.00, '2025-11-06 08:25:40'),
(31, '2025-11-06 16:39:37', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 3, 740.00, 'Confirmed', 'Delivery', '2025-11-11', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_WT4pCcwu2DtVmLVzD5m5DexU', 'paid', 740.00, '2025-11-06 08:39:37'),
(32, '2025-11-06 16:46:59', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 2, 510.00, 'Ready for Delivery', 'Delivery', '2025-11-06', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_gPkAJ5xBszgwJQjfSgkgNQ5u', 'paid', 510.00, '2025-11-06 08:46:59'),
(33, '2025-11-06 18:07:29', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 1, 280.00, 'Confirmed', 'Delivery', '2025-11-11', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_hQBZTHmLsrwubGWN9vFmG4Hi', 'paid', 280.00, '2025-11-06 10:07:29'),
(34, '2025-11-06 18:40:11', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 1, 280.00, 'Confirmed', 'Delivery', '2025-11-20', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_YpxsriC2J5BDU4sYDLdVWcMx', 'paid', 280.00, '2025-11-06 10:40:11'),
(35, '2025-11-06 19:18:44', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 3, 740.00, 'Confirmed', 'Delivery', '2025-11-13', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_YfDAjKj8GmNCTtNbSjr3Dy5d', 'paid', 740.00, '2025-11-06 11:18:44'),
(36, '2025-11-07 22:31:02', 'Enia Nya', '09289269393', 'ainepascua2@gmail.com', 'gsdfnshh, Sariaya, Quezon 4322', 'gcash', 4, 980.00, 'Confirmed', 'Delivery', '2025-11-15', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_4SrLwzmX7EF4RkHodxttKiwm', 'paid', 980.00, '2025-11-07 14:31:02'),
(37, '2025-11-08 20:39:35', 'Aine Pascua', '09289269393', 'alrainepascua2@gmail.com', 'asdf, Sta. Rosa, Laguna 4026', 'gcash', 50, 11550.00, 'Confirmed', 'Delivery', '2025-11-13', NULL, '09:00:00', '', NULL, NULL, NULL, NULL, NULL, 'src_pLSQph6A1R93ZXMDYAbv4w2X', 'paid', 11550.00, '2025-11-08 12:39:35');

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
(1, 1, 'Oat Porridge Sourdough Batard', NULL, 230.00, 2),
(2, 2, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(3, 3, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(4, 4, 'Oat Porridge Sourdough Batard', NULL, 230.00, 2),
(5, 5, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(6, 6, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(7, 7, 'Oat Porridge Sourdough Batard', NULL, 230.00, 4),
(8, 8, 'Oat Porridge Sourdough Batard', NULL, 230.00, 3),
(9, 9, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(10, 10, 'Banana Cake', NULL, 200.00, 2),
(11, 11, 'Banana Cake', NULL, 200.00, 2),
(12, 12, 'Banana Cake', NULL, 200.00, 1),
(13, 13, 'Oat Porridge Sourdough Batard', NULL, 230.00, 4),
(14, 14, 'Oat Porridge Sourdough Batard', NULL, 230.00, 4),
(15, 15, 'Oat Porridge Sourdough Batard', NULL, 230.00, 3),
(16, 16, 'Oat Porridge Sourdough Batard', NULL, 230.00, 3),
(17, 17, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(18, 25, 'Oat Porridge Sourdough Batard', NULL, 230.00, 4),
(19, 26, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(20, 27, 'Unknown Item', NULL, 250.00, 2),
(21, 28, 'Unknown Item', NULL, 250.00, 2),
(22, 29, 'Oat Porridge Sourdough Batard', NULL, 230.00, 6),
(23, 30, 'Oat Porridge Sourdough Batard', NULL, 230.00, 5),
(24, 31, 'Oat Porridge Sourdough Batard', NULL, 230.00, 3),
(25, 32, 'Oat Porridge Sourdough Batard', NULL, 230.00, 2),
(26, 33, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(27, 34, 'Oat Porridge Sourdough Batard', NULL, 230.00, 1),
(28, 35, 'Oat Porridge Sourdough Batard', NULL, 230.00, 3),
(29, 36, 'Oat Porridge Sourdough Batard', NULL, 230.00, 4),
(30, 37, 'Oat Porridge Sourdough Batard', NULL, 230.00, 50);

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
(1, 2, '2025-10-31 02:52:16', '2025-11-01 13:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_refunds`
--

CREATE TABLE `order_refunds` (
  `refund_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `refund_reason` varchar(100) NOT NULL COMMENT 'Reason: spoiled, wrong_item, damaged',
  `refund_items` text NOT NULL COMMENT 'JSON array of items to refund with quantities',
  `refund_note` text DEFAULT NULL COMMENT 'Additional details from customer',
  `proof_image` varchar(255) NOT NULL COMMENT 'Path to uploaded proof image',
  `cloud_url` varchar(500) DEFAULT NULL,
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `refund_amount` decimal(10,2) NOT NULL COMMENT 'Total amount to be refunded',
  `refund_status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL COMMENT 'Admin response or notes',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores refund requests from customers with proof and admin status';

--
-- Dumping data for table `order_refunds`
--

INSERT INTO `order_refunds` (`refund_id`, `order_id`, `user_id`, `refund_reason`, `refund_items`, `refund_note`, `proof_image`, `cloud_url`, `cloud_public_id`, `cloud_provider`, `refund_amount`, `refund_status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(9, 3, 7, 'spoiled', '[{\"item_id\":\"3\",\"product_name\":\"Oat Porridge Sourdough Batard\",\"quantity\":1,\"price\":230}]', '', 'assets/refund-proofs/refund_3_7_1762704528.jpg', NULL, NULL, 'cloudinary', 230.00, 'completed', NULL, '2025-11-09 16:08:48', '2025-11-09 16:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_settings`
--

CREATE TABLE `order_status_settings` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL COMMENT 'NULL for global setting, or specific admin user ID',
  `auto_status_enabled` tinyint(1) DEFAULT 0 COMMENT '0 = manual, 1 = automatic',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores auto-status toggle preferences for order management';

--
-- Dumping data for table `order_status_settings`
--

INSERT INTO `order_status_settings` (`id`, `admin_id`, `auto_status_enabled`, `updated_at`) VALUES
(15, NULL, 1, '2025-11-02 22:43:46'),
(16, NULL, 0, '2025-11-04 07:48:08'),
(17, NULL, 1, '2025-11-04 07:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `order_update_flags`
--

CREATE TABLE `order_update_flags` (
  `id` int(11) NOT NULL,
  `flag_type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_update_flags`
--

INSERT INTO `order_update_flags` (`id`, `flag_type`, `created_at`) VALUES
(6, 'new_order', '2025-11-06 08:46:59'),
(7, 'new_order', '2025-11-06 10:07:29'),
(8, 'new_order', '2025-11-06 10:40:11'),
(9, 'new_order', '2025-11-06 11:18:44'),
(10, 'new_order', '2025-11-07 14:31:02'),
(11, 'new_order', '2025-11-08 12:39:35');

-- --------------------------------------------------------

--
-- Table structure for table `pending_payments`
--

CREATE TABLE `pending_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL COMMENT 'PayMongo source_id or payment_intent_id',
  `payment_type` enum('source','payment_intent') NOT NULL COMMENT 'Type of PayMongo payment',
  `order_type` enum('regular','availtoday') NOT NULL COMMENT 'Order type',
  `amount` decimal(10,2) NOT NULL COMMENT 'Payment amount in PHP',
  `payment_method` varchar(50) NOT NULL COMMENT 'gcash, paymaya, or card',
  `order_data` text NOT NULL COMMENT 'JSON encoded order data',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT (current_timestamp() + interval 1 hour) COMMENT 'Auto-expire after 1 hour'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Backup storage for pending PayMongo payments to handle session loss';

--
-- Dumping data for table `pending_payments`
--

INSERT INTO `pending_payments` (`id`, `user_id`, `payment_id`, `payment_type`, `order_type`, `amount`, `payment_method`, `order_data`, `created_at`, `expires_at`) VALUES
(14, 23, 'src_1Vo2FXKpVvvpvyMPQT6oKBmM', 'source', 'regular', 11560.00, 'gcash', '{\"cart_items\":[{\"name\":\"Oat Porridge Sourdough Batard\",\"price\":\"230.00\",\"quantity\":50,\"cart_id\":101,\"product_id\":3,\"status_id\":2,\"available_days\":\"Sunday,Tuesday,Thursday,Saturday\"}],\"selected_cart_ids\":\"101\",\"cart_total\":\"11500\",\"user_name\":\"Enia Nya\",\"user_email\":\"ainepascua2@gmail.com\",\"delivery_method\":\"delivery\",\"delivery_date\":\"2025-11-15\",\"pickup_date\":\"2025-11-08\",\"contact_number\":\"09289269393\",\"delivery_address\":\"gsdfnshh, Sariaya, Quezon 4322, Sariaya, Quezon 4322\",\"delivery_time\":\"09:00:00\",\"payment_method\":\"gcash\",\"notes\":\"\",\"customer_name\":\"Enia Nya\",\"customer_email\":\"ainepascua2@gmail.com\",\"shipping_fee\":60}', '2025-11-08 12:40:07', '2025-11-08 13:40:07');

-- --------------------------------------------------------

--
-- Table structure for table `pod_orders`
--

CREATE TABLE `pod_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `proof_image_path` varchar(500) NOT NULL COMMENT 'Cloudinary URL or relative path to proof image',
  `cloudinary_public_id` varchar(255) DEFAULT NULL COMMENT 'Cloudinary public ID for the proof image',
  `submitted_by` varchar(100) DEFAULT NULL COMMENT 'Rider name or ID who submitted proof',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `image_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `notes` text DEFAULT NULL COMMENT 'Optional notes from rider'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores proof of delivery images for completed orders';

--
-- Dumping data for table `pod_orders`
--

INSERT INTO `pod_orders` (`id`, `order_id`, `proof_image_path`, `cloudinary_public_id`, `submitted_by`, `submitted_at`, `image_size`, `notes`) VALUES
(1, 1, 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762127389/neocafe/delivery-proofs/order_1_20251103_074949.jpg', 'neocafe/delivery-proofs/order_1_20251103_074949', 'Rider', '2025-11-03 07:49:51', 106030, NULL),
(2, 7, 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762135164/neocafe/delivery-proofs/order_7_20251103_095924.jpg', 'neocafe/delivery-proofs/order_7_20251103_095924', 'Rider', '2025-11-03 09:59:25', 88947, NULL),
(3, 4, 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762138193/neocafe/delivery-proofs/order_4_20251103_104953.jpg', 'neocafe/delivery-proofs/order_4_20251103_104953', 'Rider', '2025-11-03 10:49:54', 144133, NULL);

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
-- Table structure for table `privacy_policy`
--

CREATE TABLE `privacy_policy` (
  `id` int(11) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL DEFAULT 'Privacy Policy',
  `content` longtext NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `privacy_policy`
--

INSERT INTO `privacy_policy` (`id`, `title`, `content`, `last_updated`, `created_at`) VALUES
(1, 'Privacy Policy', '<h2>Privacy Policy for Neo Exclusive Cafe</h2><p>\r\n</p><p>At Neo Exclusive Cafe, we are committed to protecting your privacy and ensuring the security of your personal information.</p><p>\r\n\r\n</p><h3>1. Information We Collect</h3><p>\r\n</p><p>We collect information you provide directly to us, such as when you create an account, make a reservation, or contact us.\r\n\r\n</p><h3>2. How We Use Your Information</h3><p>\r\n</p><p>We use the information we collect to:</p><p>\r\n</p><h3>3. Information Sharing</h3><p>\r\n</p><p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p><p>\r\n</p><h3>4. Data Security</h3><p>\r\n</p><p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p><p>\r\n</p><h3>5. Your Rights</h3><p>\r\n</p><p>You have the right to:\r\n\r\n</p><h3>6. Contact Us</h3><p>\r\n</p><p>If you have any questions about this Privacy Policy, please contact us at privacy@neoexclusivecafe.com</p>', '2025-09-23 14:05:36', '2025-09-22 10:49:31');

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
  `category_id` int(11) DEFAULT NULL,
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

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `price`, `quantity`, `status_id`, `category_id`, `availtoday_status_id`, `unavailable_status_id`, `is_featured`, `show_when_unavailable`, `hide_when_unavailable`, `created_at`, `updated_at`, `deleted_at`, `auto_deleted_at`) VALUES
(1, 'SD-00001', 'Banana Cake', 'A soft and moist banana cake made with ripe bananas and a hint of vanilla sweetness. Perfectly balanced in flavor, it’s a comforting treat for any occasion best enjoyed with coffee or tea', 200.00, 0, 4, 5, 3, 3, 1, 1, 0, '2025-10-30 03:41:52', '2025-11-05 10:50:14', NULL, NULL),
(3, 'SD-00003', 'Oat Porridge Sourdough Batard', 'A hearty sourdough batard enriched with creamy oat porridge, giving it a soft, moist crumb, subtle sweetness, and a wholesome nutty flavor.', 230.00, 20, 1, 4, 1, NULL, 1, 1, 0, '2025-10-31 07:20:40', '2025-11-09 12:57:48', NULL, NULL),
(4, 'SD-00004', 'test', 'test testtest test', 300.00, 2, 1, 4, 3, NULL, 0, 1, 0, '2025-11-02 09:48:48', '2025-11-04 13:37:22', '2025-11-04 21:37:22', NULL),
(5, 'SD-00004', 'asdg', 'asd', 33.00, 2, 1, NULL, NULL, NULL, 0, 0, 1, '2025-11-02 09:58:33', '2025-11-02 09:58:43', '2025-11-02 17:58:43', NULL);

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
(94, 15, 'Sunday', '2025-10-20 05:35:14'),
(95, 15, 'Saturday', '2025-10-20 05:35:14'),
(384, 6, 'Sunday', '2025-10-26 06:26:05'),
(385, 6, 'Tuesday', '2025-10-26 06:26:05'),
(386, 6, 'Thursday', '2025-10-26 06:26:05'),
(387, 6, 'Saturday', '2025-10-26 06:26:05'),
(388, 7, 'Sunday', '2025-10-26 06:26:05'),
(389, 7, 'Tuesday', '2025-10-26 06:26:05'),
(390, 7, 'Thursday', '2025-10-26 06:26:05'),
(391, 7, 'Saturday', '2025-10-26 06:26:05'),
(416, 12, 'Sunday', '2025-10-26 06:33:32'),
(417, 12, 'Tuesday', '2025-10-26 06:33:32'),
(418, 12, 'Thursday', '2025-10-26 06:33:32'),
(419, 12, 'Saturday', '2025-10-26 06:33:33'),
(420, 11, 'Sunday', '2025-10-26 06:33:42'),
(421, 11, 'Tuesday', '2025-10-26 06:33:43'),
(422, 11, 'Thursday', '2025-10-26 06:33:43'),
(423, 11, 'Saturday', '2025-10-26 06:33:43'),
(432, 13, 'Sunday', '2025-10-27 17:00:52'),
(433, 13, 'Tuesday', '2025-10-27 17:00:52'),
(434, 13, 'Thursday', '2025-10-27 17:00:53'),
(435, 13, 'Saturday', '2025-10-27 17:00:53'),
(436, 19, 'Sunday', '2025-10-29 06:09:40'),
(437, 19, 'Tuesday', '2025-10-29 06:09:40'),
(438, 19, 'Thursday', '2025-10-29 06:09:41'),
(439, 19, 'Saturday', '2025-10-29 06:09:41'),
(440, 20, 'Sunday', '2025-10-29 06:19:36'),
(441, 20, 'Tuesday', '2025-10-29 06:19:36'),
(442, 20, 'Thursday', '2025-10-29 06:19:36'),
(443, 20, 'Saturday', '2025-10-29 06:19:36'),
(444, 21, 'Sunday', '2025-10-29 06:29:07'),
(445, 21, 'Tuesday', '2025-10-29 06:29:07'),
(446, 21, 'Thursday', '2025-10-29 06:29:07'),
(447, 21, 'Saturday', '2025-10-29 06:29:07'),
(448, 22, 'Sunday', '2025-10-29 06:43:54'),
(449, 22, 'Tuesday', '2025-10-29 06:43:54'),
(450, 22, 'Thursday', '2025-10-29 06:43:54'),
(451, 22, 'Saturday', '2025-10-29 06:43:55'),
(452, 23, 'Sunday', '2025-10-29 06:54:02'),
(453, 23, 'Tuesday', '2025-10-29 06:54:03'),
(454, 23, 'Thursday', '2025-10-29 06:54:03'),
(455, 23, 'Saturday', '2025-10-29 06:54:03'),
(460, 9, 'Sunday', '2025-10-29 10:16:05'),
(461, 9, 'Tuesday', '2025-10-29 10:16:06'),
(462, 9, 'Thursday', '2025-10-29 10:16:06'),
(463, 9, 'Saturday', '2025-10-29 10:16:06'),
(464, 8, 'Sunday', '2025-10-29 10:18:38'),
(465, 8, 'Tuesday', '2025-10-29 10:18:38'),
(466, 8, 'Thursday', '2025-10-29 10:18:38'),
(467, 8, 'Saturday', '2025-10-29 10:18:38'),
(528, 2, 'Sunday', '2025-10-31 07:26:48'),
(529, 2, 'Tuesday', '2025-10-31 07:26:48'),
(530, 2, 'Thursday', '2025-10-31 07:26:48'),
(531, 2, 'Saturday', '2025-10-31 07:26:48'),
(584, 5, 'Sunday', '2025-11-02 09:58:34'),
(585, 5, 'Tuesday', '2025-11-02 09:58:34'),
(586, 5, 'Thursday', '2025-11-02 09:58:34'),
(587, 5, 'Saturday', '2025-11-02 09:58:34'),
(652, 4, 'Sunday', '2025-11-04 13:32:29'),
(653, 4, 'Tuesday', '2025-11-04 13:32:29'),
(654, 4, 'Thursday', '2025-11-04 13:32:30'),
(655, 4, 'Saturday', '2025-11-04 13:32:30'),
(712, 3, 'Sunday', '2025-11-09 12:57:48'),
(713, 3, 'Tuesday', '2025-11-09 12:57:48'),
(714, 3, 'Thursday', '2025-11-09 12:57:48'),
(715, 3, 'Saturday', '2025-11-09 12:57:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `cloud_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_removed` tinyint(1) DEFAULT 0,
  `temp_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `cloud_public_id`, `cloud_provider`, `cloud_url`, `created_at`, `is_primary`, `is_removed`, `temp_filename`) VALUES
(1, 1, NULL, 'assets/product-images/Unnamed_Product_1761795665/primary_1761795665', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761795665/assets/product-images/Unnamed_Product_1761795665/primary_1761795665.jpg', '2025-10-30 03:41:52', 1, 0, NULL),
(2, 1, NULL, 'assets/product-images/Unnamed_Product_1761795675/additional_1761796290_1761795675', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761795675/assets/product-images/Unnamed_Product_1761795675/additional_1761796290_1761795675.jpg', '2025-10-30 03:41:52', 0, 0, NULL),
(3, 1, NULL, 'assets/product-images/Unnamed_Product_1761795680/additional_1761795996_1761795680', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761795680/assets/product-images/Unnamed_Product_1761795680/additional_1761795996_1761795680.jpg', '2025-10-30 03:41:53', 0, 0, NULL),
(4, 3, NULL, 'assets/product-images/Oat_Porridge_Sourdough_Batard_1761895718/primary_1761895718', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761895219/assets/product-images/Oat_Porridge_Sourdough_Batard_1761895718/primary_1761895718.jpg', '2025-10-31 07:20:40', 1, 0, NULL),
(5, 3, NULL, 'assets/product-images/Oat_Porridge_Sourdough_Batard_1761895732/additional_1761896197_1761895732', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761895232/assets/product-images/Oat_Porridge_Sourdough_Batard_1761895732/additional_1761896197_1761895732.jpg', '2025-10-31 07:20:41', 0, 0, NULL),
(6, 4, NULL, 'assets/product-images/Unnamed_Product_1762076840/primary_1762076840', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762076840/assets/product-images/Unnamed_Product_1762076840/primary_1762076840.jpg', '2025-11-02 09:48:48', 1, 0, NULL),
(7, 4, NULL, 'assets/product-images/Unnamed_Product_1762076885/additional_1762077707_1762076885', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762076885/assets/product-images/Unnamed_Product_1762076885/additional_1762077707_1762076885.jpg', '2025-11-02 09:48:48', 0, 0, NULL),
(8, 4, NULL, 'assets/product-images/Unnamed_Product_1762076887/additional_1762076943_1762076887', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762076888/assets/product-images/Unnamed_Product_1762076887/additional_1762076943_1762076887.jpg', '2025-11-02 09:48:48', 0, 0, NULL),
(9, 4, NULL, 'assets/product-images/Unnamed_Product_1762076889/additional_1762077588_1762076889', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762076889/assets/product-images/Unnamed_Product_1762076889/additional_1762077588_1762076889.jpg', '2025-11-02 09:48:49', 0, 0, NULL),
(10, 5, NULL, 'assets/product-images/asdg_1762077436/primary_1762077436', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762077436/assets/product-images/asdg_1762077436/primary_1762077436.jpg', '2025-11-02 09:58:33', 1, 0, NULL),
(11, 5, NULL, 'assets/product-images/asdg_1762077450/additional_1762078194_1762077450', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762077450/assets/product-images/asdg_1762077450/additional_1762078194_1762077450.jpg', '2025-11-02 09:58:33', 0, 0, NULL),
(12, 5, NULL, 'assets/product-images/asdg_1762077455/additional_1762077961_1762077455', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762077455/assets/product-images/asdg_1762077455/additional_1762077961_1762077455.jpg', '2025-11-02 09:58:33', 0, 0, NULL),
(13, 5, NULL, 'assets/product-images/asdg_1762077458/additional_1762078079_1762077458', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762077458/assets/product-images/asdg_1762077458/additional_1762078079_1762077458.jpg', '2025-11-02 09:58:33', 0, 0, NULL);

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
(2, 'Delivery'),
(1, 'Pick Up'),
(3, 'Delivery or Pick Up'),
(4, 'Same Day Order');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `application_method` enum('voucher_code','automatic_discount') NOT NULL DEFAULT 'voucher_code',
  `type` enum('percentage','fixed','free_shipping') NOT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `applicable_to` enum('all','delivery','pickup','special') NOT NULL DEFAULT 'all',
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_user` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `activation_date` date NOT NULL,
  `expiration_date` date NOT NULL,
  `status` enum('active','inactive','expired','upcoming') DEFAULT 'active',
  `include_free_shipping` tinyint(1) DEFAULT 0,
  `prevent_discounted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `title`, `code`, `application_method`, `type`, `value`, `min_purchase`, `applicable_to`, `usage_limit`, `usage_limit_per_user`, `used_count`, `activation_date`, `expiration_date`, `status`, `include_free_shipping`, `prevent_discounted`, `created_at`, `updated_at`) VALUES
(0, '10.10 Specials', 'NEOCAFE10', 'automatic_discount', 'percentage', 25.00, 500.00, 'special', 10, 1, 0, '2025-10-27', '2025-11-10', 'active', 1, 1, '2025-10-11 12:24:31', '2025-10-30 16:53:20'),
(0, 'BIRTHDAY DISCOUNT', 'NEOTURNS5', 'voucher_code', 'percentage', 100.00, 500.00, 'delivery', 100, 1, 0, '2025-10-27', '2025-11-10', 'active', 1, 1, '2025-10-11 12:50:14', '2025-10-30 16:53:20'),
(1, 'Shipping Discount', '1010PROMO', 'automatic_discount', 'free_shipping', NULL, 299.00, 'all', 20, 1, 5, '2025-10-28', '2025-11-08', 'active', 1, 1, '2025-10-28 14:51:52', '2025-11-04 09:31:05'),
(0, 'Neo Cafe Discount', 'NEOCAFE20', 'voucher_code', 'percentage', 15.00, 200.00, 'delivery', NULL, 1, 0, '2025-11-01', '2025-11-03', 'active', 1, 1, '2025-10-31 18:13:29', '2025-10-31 18:13:29'),
(0, 'SAMPLE', 'SAMPLE2020', 'voucher_code', 'free_shipping', NULL, 200.00, 'delivery', 10, 1, 0, '2025-11-01', '2025-11-02', 'active', 1, 1, '2025-10-31 18:21:47', '2025-10-31 18:21:47');

-- --------------------------------------------------------

--
-- Table structure for table `quantity_per_day_sdo`
--

CREATE TABLE `quantity_per_day_sdo` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_vouchers`
--

CREATE TABLE `refund_vouchers` (
  `id` int(11) NOT NULL,
  `refund_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `voucher_code` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('active','used','expired') DEFAULT 'active',
  `expiry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund_vouchers`
--

INSERT INTO `refund_vouchers` (`id`, `refund_id`, `customer_id`, `voucher_code`, `amount`, `status`, `expiry_date`, `created_at`) VALUES
(9, 9, 7, 'VCHR-235CAD00', 230.00, 'active', '2025-12-10', '2025-11-09 16:11:50');

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

-- --------------------------------------------------------

--
-- Table structure for table `saved_customer_info`
--

CREATE TABLE `saved_customer_info` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `delivery_location_id` int(11) NOT NULL,
  `complete_address` text NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_customer_info`
--

INSERT INTO `saved_customer_info` (`id`, `user_id`, `label`, `first_name`, `last_name`, `email`, `phone`, `delivery_location_id`, `complete_address`, `is_primary`, `created_at`, `updated_at`) VALUES
(5, 7, 'Home', 'Hannah', 'Zepeda', 'ob.zepeda.hannah.f19@gmail.com', '09127589349', 1, 'Purok 1, Sampaloc 1, Sariaya, Quezon', 1, '2025-11-01 09:39:28', '2025-11-09 14:26:32'),
(6, 16, 'My Address', 'Allysa', 'Borja', 'allysagene@outlook.com', '09261738261', 4, 'GCFF J, Sariaya, Quezon 4322', 1, '2025-11-03 01:57:18', '2025-11-03 01:57:18'),
(7, 17, 'My Address', 'Aine', 'Pascua', 'alrainepascua2@gmail.com', '09289269393', 5, 'asdf, Sta. Rosa, Laguna 4026', 1, '2025-11-04 09:30:58', '2025-11-04 09:30:58'),
(8, 23, 'My Address', 'Enia', 'Nya', 'ainepascua2@gmail.com', '09289269393', 4, 'gsdfnshh, Sariaya, Quezon 4322', 1, '2025-11-07 14:31:03', '2025-11-07 14:31:03');

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
(3, 'star', 'Fresh Ingredients', 'Nemo enim ipsam voluptatem quia voluptas sit', 1, '2025-05-20 04:18:45', '2025-09-20 16:34:39'),
(4, 'truck', 'Delivery', 'Nemo enim ipsam voluptatem quia voluptas sit', 2, '2025-05-20 04:18:47', '2025-09-20 16:35:00'),
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
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','json','boolean','integer') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`, `created_at`) VALUES
(1, 'global_available_days', '[\"Sunday\",\"Tuesday\",\"Thursday\",\"Saturday\"]', 'json', 'Global available days for pre-order products', '2025-11-06 08:24:01', '2025-10-23 04:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `temp_uploaded_images`
--

CREATE TABLE `temp_uploaded_images` (
  `id` int(11) NOT NULL,
  `public_id` varchar(255) NOT NULL,
  `cloud_url` text NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `moderation_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `moderation_checked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_uploaded_images`
--

INSERT INTO `temp_uploaded_images` (`id`, `public_id`, `cloud_url`, `uploaded_at`, `moderation_status`, `moderation_checked_at`) VALUES
(20, 'assets/product-images/Unnamed_Product_1761795356/primary_1761795356', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761795363/assets/product-images/Unnamed_Product_1761795356/primary_1761795356.jpg', '2025-10-30 03:36:07', 'pending', NULL),
(25, 'assets/product-images/Cinnamon_Rolls_6_Pieces_1761798421/primary_1761798421', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761798421/assets/product-images/Cinnamon_Rolls_6_Pieces_1761798421/primary_1761798421.jpg', '2025-10-30 04:27:03', 'pending', NULL),
(26, 'assets/product-images/Cinnamon_Rolls_6_Pieces_1761798432/additional_1761798931_1761798432', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761798432/assets/product-images/Cinnamon_Rolls_6_Pieces_1761798432/additional_1761798931_1761798432.jpg', '2025-10-30 04:27:15', 'pending', NULL),
(27, 'assets/product-images/Cassava_Cake_1761799185/primary_1761799185', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761799185/assets/product-images/Cassava_Cake_1761799185/primary_1761799185.jpg', '2025-10-30 04:39:47', 'pending', NULL),
(28, 'assets/product-images/Cassava_Cake_1761799205/additional_1761799849_1761799205', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761799205/assets/product-images/Cassava_Cake_1761799205/additional_1761799849_1761799205.jpg', '2025-10-30 04:40:07', 'pending', NULL),
(29, 'assets/product-images/Unnamed_Product_1761842821/primary_1761842821', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761842322/assets/product-images/Unnamed_Product_1761842821/primary_1761842821.jpg', '2025-10-30 16:38:44', 'pending', NULL),
(30, 'assets/product-images/Unnamed_Product_1761842864/additional_1761842962_1761842864', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761842365/assets/product-images/Unnamed_Product_1761842864/additional_1761842962_1761842864.jpg', '2025-10-30 16:39:27', 'pending', NULL),
(31, 'assets/product-images/Unnamed_Product_1761843066/primary_1761843066', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761842566/assets/product-images/Unnamed_Product_1761843066/primary_1761843066.jpg', '2025-10-30 16:42:47', 'pending', NULL),
(32, 'assets/product-images/Unnamed_Product_1761843073/additional_1761843823_1761843073', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761842576/assets/product-images/Unnamed_Product_1761843073/additional_1761843823_1761843073.jpg', '2025-10-30 16:42:57', 'pending', NULL),
(33, 'assets/product-images/Unnamed_Product_1761892634/additional_1761893563_1761892634', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761892147/assets/product-images/Unnamed_Product_1761892634/additional_1761893563_1761892634.jpg', '2025-10-31 06:29:09', 'pending', NULL),
(36, 'assets/product-images/Unnamed_Product_1761893543/additional_1761893911_1761893543', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761893044/assets/product-images/Unnamed_Product_1761893543/additional_1761893911_1761893543.jpg', '2025-10-31 06:44:06', 'pending', NULL),
(37, 'assets/product-images/Unnamed_Product_1761893584/primary_1761893584', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761893085/assets/product-images/Unnamed_Product_1761893584/primary_1761893584.jpg', '2025-10-31 06:44:46', 'pending', NULL),
(40, 'assets/product-images/Unnamed_Product_1761894537/primary_1761894537', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761894039/assets/product-images/Unnamed_Product_1761894537/primary_1761894537.jpg', '2025-10-31 07:00:40', 'pending', NULL),
(41, 'assets/product-images/Unnamed_Product_1761894544/primary_1761894544', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761894044/assets/product-images/Unnamed_Product_1761894544/primary_1761894544.jpg', '2025-10-31 07:00:45', 'pending', NULL),
(42, 'assets/product-images/Unnamed_Product_1761894638/additional_1761894953_1761894638', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761894143/assets/product-images/Unnamed_Product_1761894638/additional_1761894953_1761894638.jpg', '2025-10-31 07:02:24', 'pending', NULL),
(47, 'Home/assets/images/carousel/carousel_1761926084', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761925588/Home/assets/images/carousel/carousel_1761926084.jpg', '2025-10-31 15:46:29', 'pending', NULL),
(48, 'Home/assets/images/carousel/carousel_1761927663', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761927163/Home/assets/images/carousel/carousel_1761927663.jpg', '2025-10-31 16:12:45', 'pending', NULL),
(49, 'Home/assets/images/carousel/carousel_1761929370', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761928870/Home/assets/images/carousel/carousel_1761929370.jpg', '2025-10-31 16:41:12', 'pending', NULL),
(57, 'assets/product-images/Unnamed_Product_1762076488/primary_1762076488', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762076491/assets/product-images/Unnamed_Product_1762076488/primary_1762076488.jpg', '2025-11-02 09:41:32', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `terms_conditions`
--

CREATE TABLE `terms_conditions` (
  `id` int(11) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL DEFAULT 'Terms and Conditions',
  `content` longtext NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms_conditions`
--

INSERT INTO `terms_conditions` (`id`, `title`, `content`, `last_updated`, `created_at`) VALUES
(1, 'Terms and Conditions', '<h3>Welcome to Neo Cafe!</h3><p class=\"ql-align-justify\"><br></p><p class=\"ql-align-justify\">These Terms and Conditions (“Terms”) govern your use of our website and services. By accessing, browsing, or purchasing through our website, you agree to be bound by these Terms. Please read them carefully before using our site.</p><p class=\"ql-align-justify\">\r\n</p><h3 class=\"ql-align-justify\"><strong>1. Definitions</strong></h3><ul><li class=\"ql-align-justify\"><strong>“Neo Café,” “we,” “us,” or “our”</strong> refers to Neo Café, the owner and operator of this website.</li><li class=\"ql-align-justify\"><strong>“User,” “Customer,” or “you”</strong> refers to anyone accessing or using our website and services.</li><li class=\"ql-align-justify\"><strong>“Website”</strong> refers to Neo Café’s official online platform.</li><li class=\"ql-align-justify\"><strong>“Products”</strong> refers to all baked goods, pastries, and items offered for sale on the website.</li></ul><p class=\"ql-align-justify\"><br></p><h3 class=\"ql-align-justify\"><strong>2. Use of the Website</strong></h3><ul><li class=\"ql-align-justify\">You agree to use this website only for lawful purposes. Misuse of the website, such as fraudulent transactions, hacking, or spreading harmful content, is strictly prohibited.</li><li class=\"ql-align-justify\"> Users are responsible for maintaining the confidentiality of their account information and login credentials.</li></ul><p class=\"ql-align-justify\"><br></p><h3 class=\"ql-align-justify\"><strong>3. Account Registration</strong></h3><ul><li class=\"ql-align-justify\">To access certain features, such as placing orders or posting reviews, you may need to create an account. You agree to provide accurate, current, and complete information during registration.</li></ul><p class=\"ql-align-justify\">\r\n</p><h3 class=\"ql-align-justify\"><strong>4. Orders and Payment Policy</strong></h3><ul><li class=\"ql-align-justify\">All orders are subject to product availability and confirmation by Neo Café.</li><li class=\"ql-align-justify\"><strong>Payment Policy:</strong> The website operates on a <strong>payment-first basis</strong>. <strong>Cash on Delivery (COD)</strong> is <strong>not available</strong>. Orders are processed only after successful payment through our integrated payment gateway.</li><li class=\"ql-align-justify\"><strong>Promotional Vouchers:</strong> Neo Café may offer limited-time promotional vouchers or discount codes, subject to specific terms and expiry dates.</li><li class=\"ql-align-justify\"><strong>Refund Policy:</strong> There will be <strong>no cash or money refunds</strong>. Approved refund requests will be compensated through <strong>store vouchers</strong> redeemable on future purchases.</li></ul><p class=\"ql-align-justify\">\r\n</p><h3 class=\"ql-align-justify\"><strong>5. Pre-Order Products</strong></h3><p class=\"ql-align-justify\">	Certain products are available for <strong>pre-order</strong> only.</p><ul><li class=\"ql-align-justify\">Pre-order products can be ordered <strong>on specific days of the week</strong> depending on the scheduled date of the products.</li><li class=\"ql-align-justify\">Orders must be placed <strong>at least two (2) days before the chosen delivery or pickup date/days</strong></li></ul><p class=\"ql-align-justify\"><br></p><h3 class=\"ql-align-justify\"><strong>6. Same-Day Orders</strong></h3><ul><li class=\"ql-align-justify\">Neo Café also offers <strong>same-day order</strong> options on <strong>specific dates only</strong>, depending on product availability and operating hours. Customers are encouraged to check the available date of delivery/pickup of the products before placing a same-day order.</li><li class=\"ql-align-justify\">Once the <strong style=\"font-size: 13px;\">order cutoff time for the day has ended</strong>, any items left in the checkout or shopping cart will be <strong style=\"font-size: 13px;\">automatically removed</strong> and will no longer be processed for that day. Customers who still wish to purchase after cutoff must place a new order on the next available same-day schedule.</li></ul><p class=\"ql-align-justify\"><br></p><h3><strong>7. Bulk Orders</strong></h3><ul><li> For bulk or large-quantity purchases, a separate order form is available on the website. Bulk orders require advance 2 weeks\' notice and confirmation from Neo Café. Payment terms, lead times, and pickup/delivery details will be communicated directly to the customer after order review.</li></ul><p><br></p><h3><strong>8. Delivery and Pick-Up</strong></h3><ul><li>Neo Café delivers within specified service areas only.</li><li>Delivery schedules and pickup options are shown upon checkout.</li><li>Customers must provide accurate delivery details. Neo Café will not be liable for failed deliveries due to incorrect or incomplete addresses.</li><li>Ownership and responsibility for products transfer to the customer upon delivery or pickup confirmation.</li></ul><p><br></p><h3><strong>9. Customer Testimonials</strong></h3><ul><li>Customers may submit <strong>testimonials or reviews</strong> about their experience and product satisfaction. By submitting content, you grant Neo Café the right to use, display, and publish your testimonial on the website or social media platforms for marketing purposes.</li></ul><p><br></p><h3><strong>10. Product Information</strong></h3><ul><li>We aim to provide accurate descriptions, photos, and details of all products. However, actual product appearance (such as color, shape, or size) may slightly vary from images shown on the website due to handmade preparation and lighting conditions. Neo Café is not responsible for such minor differences.</li></ul><p><br></p><h3><strong>11. Intellectual Property</strong></h3><ul><li>All website content, including images, text, design, code, and logos, is owned by Neo Café and protected under copyright and intellectual property laws. No part of the website may be copied, reproduced, or distributed without prior written permission from Neo Café.</li></ul><p><br></p><h3><strong>12. Privacy and Data Protection</strong></h3><ul><li>Neo Café respects your privacy. Personal data collected through this website is used solely for processing orders, communication, and service improvement. For details on data collection and security, please refer to our <strong style=\"font-size: 13px;\">Privacy Policy</strong>.</li></ul><p><br></p><h3><strong>13. Limitation of Liability</strong></h3><ul><li>Neo Café shall not be liable for any direct or indirect damages arising from website use, technical errors, or order delays beyond our control. We do not guarantee uninterrupted or error-free access to the website at all times.</li></ul><p><br></p><h3><strong>14. Modifications</strong></h3><ul><li>Neo Café reserves the right to modify or update these Terms at any time without prior notice. Changes will take effect immediately upon posting. Continued use of the website means you accept the revised Terms.</li></ul><p><br></p><h3><strong>15. Governing Law</strong></h3><ul><li>These Terms and Conditions shall be governed by and interpreted under the laws of the <strong>Republic of the Philippines</strong>. Any disputes shall be settled in the proper courts of <strong style=\"font-size: 13px;\">Lucena City, Quezon Province</strong></li></ul><p><br></p><h3><strong>16. Contact Information</strong></h3><p>	For questions, feedback, or concerns regarding these Terms and Conditions, please contact us:</p><ul><li>  <strong>Email:</strong> neocafe@gmail.com</li><li> <strong>Address: </strong>Philippines</li></ul>', '2025-10-31 15:45:22', '2025-09-22 08:15:33');

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
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `cloud_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `email`, `password`, `reset_token_hash`, `reset_token_expires_at`, `is_verified`, `verification_token`, `verification_token_expires_at`, `is_admin`, `profile_image`, `cloud_public_id`, `cloud_provider`, `cloud_url`, `created_at`) VALUES
(2, 'Annalyn ', 'De Chavez', 'admin', 'ainepascua4@gmail.com', '$2y$10$7Clb1maT8r7lxMCPZ2c44urKZ5E5lhLVhKBOWC8fpKLvBGiRt/QQC', '7e87080f074c30eb905a3905c97eff34f2ad8a128b31585569e079ae202919c3', '2025-11-04 06:39:20', 1, NULL, NULL, 1, '/assets/public/admin-profile-images/admin_2_1761053478.png', 'Home/assets/public/admin-profile-images/profile_2_1761735065', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761735065/Home/assets/public/admin-profile-images/profile_2_1761735065.png', '2025-09-11 08:17:59'),
(4, 'Hannah', 'Zepeda', 'zpdhnnh', 'hannah.zepeda03@gmail.com', '$2y$10$QPIoPQrRJK2ja3RskqjCver44JCmiykeZdRM6ypQy0EiHfGvrWIhK', 'b49406fd26f334e562debea928e1b7f5ba1d46b3c4566e82143d00259e4b1ba8', '2025-11-04 06:39:20', 1, NULL, NULL, 0, '/assets/public/profile-images/profile_bf9d2f596f12b417bb380cf0fd59e645.jpg', NULL, 'cloudinary', NULL, '2025-09-11 16:12:46'),
(7, 'Hannah', 'Zepeda', 'ashbee88', 'ob.zepeda.hannah.f19@gmail.com', '$2y$10$K1U7YKHm6xuK4PcTTc01uOKajD5qB8v3EpQWfcM2iWL5hj2UqxJW.', '3a8e8134eb633a1aa86725e65e27b04e5b796f30db96da94dd015a1c9641e628', '2025-11-04 06:39:20', 1, NULL, NULL, 0, '/assets/public/profile-images/profile_7dff7942ba14aaddfea7ecd098bae6fb.png', 'Home/assets/public/profile-images/profile_7_1762682672', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1762682174/Home/assets/public/profile-images/profile_7_1762682672.jpg', '2025-09-23 12:40:39'),
(11, 'Alraine', 'Pascua', 'aine', 'dyayin12@gmail.com', '$2y$12$wZLxDDdA74PE4OS8r2PTnOmG8Toz5QfIfhs8wKnozz2e3j0K38y3m', '58d0e8e586fc125711c711592170c083085cd116e07398b59ed1982e4405d3dc', '2025-11-04 06:39:21', 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-10-29 10:59:59'),
(12, 'Ken', 'Rodriguez', 'Toots', 'kuaken07@gmail.com', '$2y$12$PipGjGbFJwC7yRIRQpzwQ.gxAfCsb9p.7KMxC19mSTz4xsO7h4fKS', 'e448826e892398ef302b3e27dfe6b2d4fef93a08dc9a45c83a82f2651d7db769', '2025-11-04 06:39:21', 0, '384865b6f80223934fc245ba0cc9dd1a119b286984d50524436ebcfb0368b871', '2025-10-30 15:29:45', 0, NULL, NULL, 'cloudinary', NULL, '2025-10-30 06:59:45'),
(13, 'Ivy', 'Padilla', 'ayve', 'padillaivydianne@gmail.com', '$2y$12$8Whg7LImtnAgFbyLiqdtReFLlXCyCZEe6n5PYhncH6vMtJU1p.iKC', '243cf478dcddeebed7194fbe355433773a9694426749ff3a41b37f856922737f', '2025-11-04 06:39:22', 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-10-30 07:03:34'),
(14, 'Hannah', 'Zepeda', 'zpdhnnnhhh', 'hannahzepeda@outlook.com', '$2y$12$B0Yfo8tuyUXE70tkswRu2u5ljmAuBEIRgDdEwkIDpGH8o5TR.wQJW', '79dc7b5ba111491f73e4a2e44614cfa44a331d82b68e13a13eb6f0610b862cb5', '2025-11-04 06:39:22', 0, 'b5ad8f106879241c4c090dba8a29905a41c6300f8ec09ae8e0dbc9b3af7200d4', '2025-10-31 18:55:39', 0, NULL, NULL, 'cloudinary', NULL, '2025-10-31 10:25:39'),
(15, 'ja', 'po', 'ito', 'jayeanntrinidad12@gmail.com', '$2y$12$gyGj8CUCasY.NOkAAklCCeQXj/t0Ela8PQiPdREhmGDZ4tHpU5HV.', 'daedb4677734eb75fbbc635434544d9a81e60b8cad92792a21991f71c4735456', '2025-11-04 06:39:23', 1, NULL, NULL, 0, NULL, 'Home/assets/public/profile-images/profile_15_1761908823', 'cloudinary', 'https://res.cloudinary.com/dvdccumbs/image/upload/v1761908823/Home/assets/public/profile-images/profile_15_1761908823.jpg', '2025-10-31 10:38:58'),
(16, 'Allysa', 'Borja', 'Allysa123', 'allysagene@outlook.com', '$2y$10$9tgmCWOtdWmhaxM9vAhYIO5.F33nPUaEtI7qqWPaMbJ3Ko2VHCj6G', '6c4291c01cd502155d158023b26bab5409512ffb7ee4f782c2879223b533fb9f', '2025-11-04 06:39:23', 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-11-03 01:38:29'),
(17, 'Aine', 'Pascua', 'Dyayin', 'alrainepascua2@gmail.com', '$2y$10$mgTF6V47bjHQBXFQ6tZ.s.FomGkkXLozo8Bj5CxJQbjfKCx5Dj00q', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-11-03 20:35:53'),
(18, 'test', 'test', 'testmax', 'crownebu@gmail.com', '$2y$10$WLvjaIgvFOQtJcV6tZAkteA1GNWFxn.sXCdgt1SWbT0VLiRTFDRBG', '5df9700c26228c53695f0a6d5da7a88e8cde5bfb228f2b5a6424f55959dca877', '2025-11-04 06:39:24', 1, NULL, NULL, 1, NULL, NULL, 'cloudinary', NULL, '2025-11-03 21:36:15'),
(19, 'test', 'test', 'testmax1', 'testmax@gmail.com', '$2y$10$ek5Jm0MgtHSF3p.ul4tojeCihawebT0dMfYQd0.Nf.UAcsyXdRSiK', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-11-03 21:54:30'),
(20, 'test', 'test', 'testmax', 'afable.228779@lucena.sti.edu.ph', '$2y$10$lzdy6Z2kdGfLpbdaKhgS2e4G/PAlKCiTnidAmmMfd5XS8VzitXgFW', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-11-03 22:18:18'),
(22, 'Anna', 'Anna', 'Anna', 'rhezelalmendrala@gmail.com', '$2y$10$JZ1yeqsOY46L6EmPMkdare97AZ9jZqHgXi7RLWs36NpMk2pOQZkKm', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-11-06 13:48:14'),
(23, 'Enia', 'Nya', 'Enia', 'ainepascua2@gmail.com', '$2y$10$qmcJjKz0elpLMnfssDXn4ezgIRaysAe.Fu2n.Oha7Mo1jg5v3kdcS', NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, 'cloudinary', NULL, '2025-11-07 12:10:46');

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
  `cloud_url` varchar(500) DEFAULT NULL,
  `cloud_public_id` varchar(255) DEFAULT NULL,
  `cloud_provider` varchar(50) DEFAULT 'cloudinary',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published','archived') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_blog_post`
--

INSERT INTO `user_blog_post` (`id`, `user_id`, `title`, `content`, `image_path`, `cloud_url`, `cloud_public_id`, `cloud_provider`, `created_at`, `updated_at`, `status`) VALUES
(0, 7, 'Cravings Satisfied', 'I have been a fan of their sourdough bread the moment I\'ve tasted it, and the one that stands out more for me is the Garlic and mushroom sourdough toast. But to be honest. lahat naman talaga ng toasts nila ang sarap! highly recommended.', 'assets/uploaded-images-users/blog_6911933fdf8fb.jpg', NULL, NULL, 'cloudinary', '2025-11-10 07:24:48', '2025-11-10 07:24:48', 'published');

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `notif_type` (`notif_type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `notif_reference_id` (`notif_reference_id`);

--
-- Indexes for table `availtoday_cart`
--
ALTER TABLE `availtoday_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `availtoday_order_limit`
--
ALTER TABLE `availtoday_order_limit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`adblog_id`),
  ADD KEY `idx_cloud_public_id` (`cloud_public_id`);

--
-- Indexes for table `bulk_orders`
--
ALTER TABLE `bulk_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bulk_order_items`
--
ALTER TABLE `bulk_order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carousel_images`
--
ALTER TABLE `carousel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cloud_public_id` (`cloud_public_id`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_availtoday`
--
ALTER TABLE `cart_availtoday`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_name` (`name`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_coupon_order` (`user_id`,`coupon_id`,`order_id`),
  ADD KEY `idx_user_coupon` (`user_id`,`coupon_id`),
  ADD KEY `idx_coupon` (`coupon_id`);

--
-- Indexes for table `date_limits`
--
ALTER TABLE `date_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`);

--
-- Indexes for table `delivery_locations`
--
ALTER TABLE `delivery_locations`
  ADD PRIMARY KEY (`delivery_id`);

--
-- Indexes for table `image_migrations`
--
ALTER TABLE `image_migrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_path` (`local_path`),
  ADD KEY `idx_cloudinary_public_id` (`cloudinary_public_id`),
  ADD KEY `idx_image_type` (`image_type`);

--
-- Indexes for table `image_moderation_log`
--
ALTER TABLE `image_moderation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_public_id` (`public_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_delivery_date` (`delivery_date`),
  ADD KEY `idx_pickup_date` (`pickup_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_delivery_method_date_status` (`delivery_method`,`delivery_date`,`status`),
  ADD KEY `idx_delivery_method_pickup_status` (`delivery_method`,`pickup_date`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `order_refunds`
--
ALTER TABLE `order_refunds`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_refund_status` (`refund_status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_cloud_public_id` (`cloud_public_id`);

--
-- Indexes for table `order_status_settings`
--
ALTER TABLE `order_status_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_admin` (`admin_id`);

--
-- Indexes for table `order_update_flags`
--
ALTER TABLE `order_update_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_flag_type_created` (`flag_type`,`created_at`);

--
-- Indexes for table `pending_payments`
--
ALTER TABLE `pending_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `pod_orders`
--
ALTER TABLE `pod_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order` (`order_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_cloudinary_public_id` (`cloudinary_public_id`);

--
-- Indexes for table `privacy_policy`
--
ALTER TABLE `privacy_policy`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `product_day`
--
ALTER TABLE `product_day`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cloud_public_id` (`cloud_public_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_product_primary` (`product_id`,`is_primary`);

--
-- Indexes for table `quantity_per_day_sdo`
--
ALTER TABLE `quantity_per_day_sdo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_date` (`product_id`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `refund_vouchers`
--
ALTER TABLE `refund_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `refund_id` (`refund_id`);

--
-- Indexes for table `saved_customer_info`
--
ALTER TABLE `saved_customer_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_location_id` (`delivery_location_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_primary` (`is_primary`),
  ADD KEY `idx_user_primary` (`user_id`,`is_primary`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`);

--
-- Indexes for table `temp_uploaded_images`
--
ALTER TABLE `temp_uploaded_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_id` (`public_id`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`),
  ADD KEY `idx_public_id` (`public_id`),
  ADD KEY `idx_moderation_status` (`moderation_status`);

--
-- Indexes for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cloud_public_id` (`cloud_public_id`);

--
-- Indexes for table `user_blog_post`
--
ALTER TABLE `user_blog_post`
  ADD KEY `idx_cloud_public_id` (`cloud_public_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=341;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `availtoday_cart`
--
ALTER TABLE `availtoday_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `availtoday_order_limit`
--
ALTER TABLE `availtoday_order_limit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `adblog_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `bulk_orders`
--
ALTER TABLE `bulk_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `bulk_order_items`
--
ALTER TABLE `bulk_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `carousel_images`
--
ALTER TABLE `carousel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `cart_availtoday`
--
ALTER TABLE `cart_availtoday`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `date_limits`
--
ALTER TABLE `date_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `delivery_locations`
--
ALTER TABLE `delivery_locations`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `image_migrations`
--
ALTER TABLE `image_migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `image_moderation_log`
--
ALTER TABLE `image_moderation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_refunds`
--
ALTER TABLE `order_refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_status_settings`
--
ALTER TABLE `order_status_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_update_flags`
--
ALTER TABLE `order_update_flags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pending_payments`
--
ALTER TABLE `pending_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `pod_orders`
--
ALTER TABLE `pod_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_day`
--
ALTER TABLE `product_day`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=716;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `quantity_per_day_sdo`
--
ALTER TABLE `quantity_per_day_sdo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `refund_vouchers`
--
ALTER TABLE `refund_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `saved_customer_info`
--
ALTER TABLE `saved_customer_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `temp_uploaded_images`
--
ALTER TABLE `temp_uploaded_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `availtoday_cart`
--
ALTER TABLE `availtoday_cart`
  ADD CONSTRAINT `availtoday_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `availtoday_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_refunds`
--
ALTER TABLE `order_refunds`
  ADD CONSTRAINT `order_refunds_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_refunds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pending_payments`
--
ALTER TABLE `pending_payments`
  ADD CONSTRAINT `pending_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pod_orders`
--
ALTER TABLE `pod_orders`
  ADD CONSTRAINT `fk_pod_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `quantity_per_day_sdo`
--
ALTER TABLE `quantity_per_day_sdo`
  ADD CONSTRAINT `fk_quantity_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `refund_vouchers`
--
ALTER TABLE `refund_vouchers`
  ADD CONSTRAINT `refund_vouchers_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `refund_vouchers_ibfk_2` FOREIGN KEY (`refund_id`) REFERENCES `order_refunds` (`refund_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `saved_customer_info`
--
ALTER TABLE `saved_customer_info`
  ADD CONSTRAINT `saved_customer_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_customer_info_ibfk_2` FOREIGN KEY (`delivery_location_id`) REFERENCES `delivery_locations` (`delivery_id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`429123`@`%` EVENT `cleanup_expired_pending_payments` ON SCHEDULE EVERY 1 HOUR STARTS '2025-11-06 03:27:02' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM pending_payments WHERE expires_at < NOW()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
