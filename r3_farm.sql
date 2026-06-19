-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 19, 2026 at 04:41 AM
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
-- Database: `r3_farm`
--
DROP DATABASE IF EXISTS `r3_farm`;
CREATE DATABASE IF NOT EXISTS `r3_farm`;
USE `r3_farm`;
-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('shipping','billing') DEFAULT 'shipping',
  `address_label` varchar(50) DEFAULT NULL,
  `street_address` varchar(255) NOT NULL,
  `region` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `barangay` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `is_default_shipping` tinyint(1) DEFAULT 0,
  `is_default_billing` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `address_type`, `address_label`, `street_address`, `region`, `city`, `barangay`, `postal_code`, `landmark`, `is_default_shipping`, `is_default_billing`, `created_at`, `updated_at`) VALUES
(2, 8, 'shipping', 'Home', 'Sam, Banlic, Calamba, Laguna', '', '', 'Banlic', '4027', 'hi', 1, 0, '2026-06-13 09:48:55', '2026-06-13 09:48:55'),
(4, 8, 'shipping', 'Home', '', '', '', '', '', '', 0, 0, '2026-06-14 01:11:41', '2026-06-14 02:57:01'),
(6, 11, 'shipping', 'Home', 'Scott Street', 'Batangas', 'Bauan', 'Banlic', '4027', '', 0, 0, '2026-06-14 01:51:01', '2026-06-14 02:42:09'),
(7, 11, 'shipping', 'Home', 'Sampa', 'Laguna', 'Calamba', 'Banlic', '4027', '', 1, 0, '2026-06-14 01:56:29', '2026-06-14 02:42:09'),
(8, 11, 'shipping', 'Home', '', '', '', '', '', '', 0, 0, '2026-06-14 02:13:37', '2026-06-14 02:42:09'),
(9, 12, 'shipping', 'Home', 'Scott Str, Banlic, Calamba, Laguna', '', '', 'Banlic', '4027', '', 1, 0, '2026-06-14 03:15:16', '2026-06-14 03:15:16'),
(10, 13, 'shipping', 'Home', 'Sampa, Banlic, Calamba, Laguna', '', '', 'Banlic', '4027', '', 1, 0, '2026-06-15 14:03:53', '2026-06-15 14:03:53'),
(11, 13, 'shipping', 'Home', '', '', '', '', '', '', 1, 0, '2026-06-15 14:04:39', '2026-06-15 14:04:39'),
(12, 11, 'shipping', 'Home', '', '', '', '', '', '', 1, 0, '2026-06-16 00:56:55', '2026-06-16 00:56:55'),
(13, 11, 'shipping', 'Home', '', '', '', '', '', '', 1, 0, '2026-06-16 01:02:27', '2026-06-16 01:02:27'),
(14, 15, 'shipping', 'Home', 'Scott Street, Banlic, Calamba, Laguna', '', '', 'Banlic', '4027', '', 1, 0, '2026-06-18 07:43:33', '2026-06-18 07:43:33'),
(15, 10, 'shipping', 'Home', 'Scott Strt.', 'Laguna', 'Calamba', 'Banlic', '4027', '', 1, 0, '2026-06-18 09:00:22', '2026-06-18 09:00:22'),
(16, 10, 'shipping', 'Home', 'scoot', 'Laguna', 'BiÃ±an', 'Banlic', '4027', '', 0, 0, '2026-06-18 09:02:38', '2026-06-18 09:02:38'),
(17, 18, 'shipping', 'Home', 'Acort', 'Laguna', 'Calamba', 'Banlic', '4027', '', 1, 0, '2026-06-19 01:40:25', '2026-06-19 01:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `shipping_address_id` int(11) NOT NULL,
  `billing_address_id` int(11) DEFAULT NULL,
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded','failed') DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT 'cash_on_delivery',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `customer_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `estimated_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `shipping_address_id`, `billing_address_id`, `order_status`, `payment_status`, `payment_method`, `payment_reference`, `subtotal`, `delivery_fee`, `tax_amount`, `discount_amount`, `total_amount`, `customer_notes`, `admin_notes`, `estimated_delivery_date`, `actual_delivery_date`, `cancelled_at`, `cancelled_reason`, `created_at`, `updated_at`) VALUES
(1, 'R3F-20260613-891F0B', 8, 2, NULL, 'pending', 'unpaid', 'credit_card', NULL, 120.00, 49.00, 0.00, 0.00, 169.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-13 09:48:55', '2026-06-13 09:48:55'),
(2, 'R3F-20260614-D98CF5', 8, 4, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 110.00, 49.00, 0.00, 0.00, 159.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-14 01:11:41', '2026-06-14 01:11:41'),
(3, 'R3F-20260614-49523E', 11, 8, NULL, 'cancelled', 'refunded', 'cash_on_delivery', NULL, 50.00, 49.00, 0.00, 0.00, 99.00, '', NULL, '1970-01-01', NULL, '2026-06-15 08:05:50', 'Found a better price', '2026-06-14 02:13:37', '2026-06-19 02:37:38'),
(4, 'R3F-20260614-EC99B7', 12, 9, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 75.00, 99.00, 0.00, 0.00, 174.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-14 03:15:16', '2026-06-14 03:15:16'),
(5, 'R3F-20260615-7EA5AE', 13, 10, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 50.00, 49.00, 0.00, 0.00, 99.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-15 14:03:53', '2026-06-15 14:03:53'),
(6, 'R3F-20260615-C4DDC1', 13, 11, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 75.00, 49.00, 0.00, 0.00, 124.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-15 14:04:39', '2026-06-15 14:04:39'),
(7, 'R3F-20260616-90A9FB', 11, 12, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 75.00, 0.00, 0.00, 15.00, 60.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-16 00:56:55', '2026-06-16 00:56:55'),
(8, 'R3F-20260616-2C76C0', 11, 13, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 60.00, 49.00, 0.00, 0.00, 109.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-16 01:02:27', '2026-06-16 01:02:27'),
(9, 'R3F-20260618-24E16A', 11, 7, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 225.00, 49.00, 0.00, 0.00, 274.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-18 07:27:44', '2026-06-18 07:27:44'),
(10, 'R3F-20260618-9B27AE', 15, 14, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 180.00, 0.00, 0.00, 0.00, 180.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-18 07:43:33', '2026-06-18 07:43:33'),
(11, 'R3F-20260618-BAB278', 10, 15, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 50.00, 49.00, 0.00, 0.00, 99.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-18 09:00:22', '2026-06-18 09:00:22'),
(12, 'R3F-20260618-977FC0', 10, 16, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 60.00, 49.00, 0.00, 0.00, 109.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-18 09:02:55', '2026-06-18 09:02:55'),
(13, 'R3F-20260619-4197FA', 18, 17, NULL, 'pending', 'unpaid', 'cash_on_delivery', NULL, 50.00, 49.00, 0.00, 0.00, 99.00, '', NULL, '1970-01-01', NULL, NULL, NULL, '2026-06-19 01:40:25', '2026-06-19 01:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price_per_unit` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price_per_unit`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price_per_unit`) VALUES
(1, 1, 4, 'Lettuce', 2, 60.00),
(2, 2, 4, 'Lettuce', 1, 60.00),
(3, 2, 3, 'Carrot', 1, 50.00),
(4, 3, 3, 'Carrot', 1, 50.00),
(5, 4, 5, 'Broccoli', 1, 75.00),
(6, 5, 3, 'Carrot', 1, 50.00),
(7, 6, 5, 'Broccoli', 1, 75.00),
(8, 7, 5, 'Broccoli', 1, 75.00),
(9, 8, 4, 'Lettuce', 1, 60.00),
(10, 9, 8, 'Banana', 5, 45.00),
(11, 10, 4, 'Lettuce', 3, 60.00),
(12, 11, 3, 'Carrot', 1, 50.00),
(13, 12, 4, 'Lettuce', 1, 60.00),
(14, 13, 3, 'Carrot', 1, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `changed_by`, `changed_at`) VALUES
(1, 1, 'pending', 'Order placed', 8, '2026-06-13 09:48:55'),
(2, 2, 'pending', 'Order placed', 8, '2026-06-14 01:11:41'),
(3, 3, 'pending', 'Order placed', 11, '2026-06-14 02:13:37'),
(4, 4, 'pending', 'Order placed', 12, '2026-06-14 03:15:16'),
(5, 3, 'cancelled', 'Cancelled by customer: Found a better price', 11, '2026-06-15 08:05:50'),
(6, 5, 'pending', 'Order placed', 13, '2026-06-15 14:03:53'),
(7, 6, 'pending', 'Order placed', 13, '2026-06-15 14:04:39'),
(8, 7, 'pending', 'Order placed', 11, '2026-06-16 00:56:55'),
(9, 8, 'pending', 'Order placed', 11, '2026-06-16 01:02:27'),
(10, 9, 'pending', 'Order placed', 11, '2026-06-18 07:27:44'),
(11, 10, 'pending', 'Order placed', 15, '2026-06-18 07:43:33'),
(12, 11, 'pending', 'Order placed', 10, '2026-06-18 09:00:22'),
(13, 12, 'pending', 'Order placed', 10, '2026-06-18 09:02:55'),
(14, 13, 'pending', 'Order placed', 18, '2026-06-19 01:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('vegetables','fruits','grains','cash_crops') NOT NULL,
  `current_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` enum('kg','pcs','liter','sack') DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `harvest_date` date NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `current_price`, `quantity`, `unit`, `image_url`, `created_at`, `harvest_date`, `updated_at`) VALUES
(2, 'Cucumber', 'vegetables', 35.00, 200, 'kg', 'uploads/1777469374_Poster.png', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(3, 'Carrot', 'vegetables', 50.00, 91, 'kg', 'images/carrot.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(4, 'Lettuce', 'vegetables', 60.00, 90, 'kg', 'images/lettuce.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(5, 'Broccoli', 'vegetables', 75.00, 97, 'kg', 'images/broccoli.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(6, 'Bell Pepper', 'vegetables', 45.00, 100, 'kg', 'images/bellpepper.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(7, 'Apple', 'fruits', 80.00, 100, 'kg', 'images/apple.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(8, 'Banana', 'fruits', 45.00, 95, 'kg', 'images/banana.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(9, 'Orange', 'fruits', 70.00, 100, 'kg', 'images/orange.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(10, 'Mango', 'fruits', 90.00, 100, 'kg', 'images/mango.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(11, 'Grapes', 'fruits', 120.00, 100, 'kg', 'images/grapes.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(12, 'Strawberry', 'fruits', 150.00, 100, 'kg', 'images/strawberry.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(13, 'Rice (5kg)', 'grains', 225.00, 98, 'sack', 'images/rice.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(14, 'Corn (1kg)', 'grains', 55.00, 100, 'kg', 'images/corn.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(15, 'Oats', 'grains', 85.00, 100, 'kg', 'images/oats.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(16, 'Quinoa', 'grains', 180.00, 100, 'kg', 'images/quinoa.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(17, 'Wheat Flour', 'grains', 65.00, 100, 'kg', 'images/wheat.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(18, 'Coffee Beans', 'cash_crops', 250.00, 100, 'kg', 'images/coffee.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(19, 'Cacao', 'cash_crops', 300.00, 100, 'kg', 'images/cacao.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(20, 'Tobacco', 'cash_crops', 150.00, 100, 'kg', 'images/tobacco.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(21, 'Sugarcane', 'cash_crops', 45.00, 100, 'kg', 'images/sugarcane.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(22, 'Cotton', 'cash_crops', 200.00, 100, 'kg', 'images/cotton.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(26, 'Heirloom Tomato', 'fruits', 120.00, 20, 'kg', 'uploads/1777514051_tomato.jpg', '2026-04-30 01:54:11', '2026-06-19', '2026-06-19 10:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `first_name` varchar(120) DEFAULT NULL,
  `middle_name` varchar(120) DEFAULT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone_num` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `middle_name`, `last_name`, `email`, `phone_num`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', '', '', '', 'admin@r3farm.com', NULL, 'admin123', 'admin', '2026-04-29 11:03:45'),
(2, 'Gab', '', '', '', 'gab@gmail.com', NULL, '123', 'user', '2026-04-29 11:25:40'),
(3, 'Rans Ringor', '', '', '', 'rans@sulasok.com', NULL, '123', 'user', '2026-04-29 14:09:31'),
(4, 'ryan', '', '', '', 'ryan@ryan.com', NULL, '9MG~2?!TFc?~Ch4', 'user', '2026-04-29 14:51:07'),
(5, 'asd', '', '', '', 'asd@asd', NULL, '9MG~2?!TFc?~Ch4', 'user', '2026-04-29 14:51:59'),
(6, 'asdasdasd', '', '', '', 'asdasdsass@asdasdsa', NULL, '9MG~2?!TFc?~Ch4', 'user', '2026-04-30 01:04:31'),
(7, 'asd', '', '', '', 'asd@wqe', NULL, '9MG~2?!TFc?~Ch4', 'user', '2026-04-30 01:18:17'),
(8, 'gabbb', '', '', '', 'gbb123@gil.com', NULL, '123456', 'user', '2026-04-30 08:53:14'),
(9, 'tien', '', '', '', 'tien@gmail.com', NULL, '$2y$10$q.Y9RpkXdWR56xSpUSghyO.9lr1HWPUZnW7KauJk.i9MapBq2qoOC', 'user', '2026-05-24 06:13:11'),
(10, 'tien', 'Tien', '', 'Nguyen', 'tien@pup.edu', '994553640', '$2y$10$tPesVRqHjSDDeRuLBF4v4.rSqaOCIrUgISVZQ6R9.H0NrQG2Q3NTy', 'user', '2026-06-08 09:02:51'),
(11, 'Edward Guevarra', 'Edward', '', 'Guevarra', 'ed@ed.com', '99934456', '$2y$10$FmED9rjDcQh5Qfh2CYIbcekTgwhfbYpjcnnHxTw0wx0FMo7Sl7C7m', 'user', '2026-06-13 05:08:35'),
(12, 'Rei', NULL, NULL, NULL, 'rei@gmail.com', NULL, '$2y$10$1f4Hyn9c.2siJb.FRbxvKeTbrUGmTKp5ZqaxwqZ8nzYTOzc0G/K/6', 'user', '2026-06-14 03:13:38'),
(13, 'last', NULL, NULL, NULL, 'last@gmail.com', NULL, '$2y$10$hYf/d1c8OYrK3Es2rgoPs.2ZHmcAY8gE5odmgxBUrXWnq9OKCpMyO', 'admin', '2026-06-15 14:03:08'),
(14, 'gedorah', NULL, NULL, NULL, 'ged@gmail.com', NULL, '$2y$10$I5HDip0VhMUJRyzDCSLzOeMfkJRbIQl8qcWITAaL5LhUY05eamYn.', 'user', '2026-06-18 06:15:30'),
(15, 'jin', NULL, NULL, NULL, 'jin@gmail.com', NULL, '$2y$10$4r4JdN6IrisEwStYda034eChWxDGGQC/npr.vaT94gSGDHdOWZ9Oq', 'user', '2026-06-18 07:42:21'),
(16, 'user', NULL, NULL, NULL, 'user@gmail.com', NULL, '$2y$10$VpUKCYVagsaLhCfMTLZyjOvrzHzU8tUN4KxiqmfQ6gtAicz6N5uzS', 'user', '2026-06-18 08:30:55'),
(17, 'user', NULL, NULL, NULL, 'user@mail.com', NULL, '$2y$10$ZQyb.hTAHe5om73Y50gtIesdS6SeX6zR/fzvPLo7Xcx2rgvVRQujW', 'user', '2026-06-18 08:33:53'),
(18, 'phone', 'phone', NULL, 'Phone', 'phone@gmail.c', '09761733395', '$2y$10$psgQNmJuUGz3b/ghYXxuM.XsX7mNr63.7kFUd1aluKVZqCXRksgLi', 'user', '2026-06-19 01:25:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `shipping_address_id` (`shipping_address_id`),
  ADD KEY `billing_address_id` (`billing_address_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_product` (`order_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Migrate older databases that pre-date the GCash payment-proof column.
-- Safe to re-run; MySQL/MariaDB will skip the statement if the column
-- already exists (the ALTER will fail with a duplicate-column error,
-- which we swallow via the IF NOT EXISTS guard below).
--
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `payment_proof` VARCHAR(255) DEFAULT NULL AFTER `payment_reference`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
