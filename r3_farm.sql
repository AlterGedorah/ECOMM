-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 13, 2026 at 11:23 AM
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
CREATE DATABASE `r3_farm`;
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
  `region` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `is_default_shipping` tinyint(1) DEFAULT 0,
  `is_default_billing` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
(2, 'Cucumber', 'vegetables', 35.00, 200, NULL, 'uploads/1777469374_Poster.png', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(3, 'Carrot', 'vegetables', 50.00, 96, NULL, 'images/carrot.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(4, 'Lettuce', 'vegetables', 60.00, 98, NULL, 'images/lettuce.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(5, 'Broccoli', 'vegetables', 75.00, 100, NULL, 'images/broccoli.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(6, 'Bell Pepper', 'vegetables', 45.00, 100, NULL, 'images/bellpepper.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(7, 'Apple', 'fruits', 80.00, 100, NULL, 'images/apple.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(8, 'Banana', 'fruits', 45.00, 100, NULL, 'images/banana.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(9, 'Orange', 'fruits', 70.00, 100, NULL, 'images/orange.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(10, 'Mango', 'fruits', 90.00, 100, NULL, 'images/mango.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(11, 'Grapes', 'fruits', 120.00, 100, NULL, 'images/grapes.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(12, 'Strawberry', 'fruits', 150.00, 100, NULL, 'images/strawberry.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(13, 'Rice (5kg)', 'grains', 225.00, 98, NULL, 'images/rice.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(14, 'Corn (1kg)', 'grains', 55.00, 100, NULL, 'images/corn.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(15, 'Oats (500g)', 'grains', 85.00, 100, NULL, 'images/oats.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(16, 'Quinoa (500g)', 'grains', 180.00, 100, NULL, 'images/quinoa.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(17, 'Wheat Flour (1kg)', 'grains', 65.00, 100, NULL, 'images/wheat.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(18, 'Coffee Beans (250g)', 'cash_crops', 250.00, 100, NULL, 'images/coffee.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(19, 'Cacao (500g)', 'cash_crops', 300.00, 100, NULL, 'images/cacao.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(20, 'Tobacco (100g)', 'cash_crops', 150.00, 100, NULL, 'images/tobacco.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(21, 'Sugarcane (1kg)', 'cash_crops', 45.00, 100, NULL, 'images/sugarcane.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(22, 'Cotton (500g)', 'cash_crops', 200.00, 100, NULL, 'images/cotton.jpg', '2026-04-29 11:03:45', '0000-00-00', '0000-00-00 00:00:00'),
(26, 'Heirloom Tomato', 'vegetables', 120.00, 0, NULL, 'uploads/1777514051_tomato.jpg', '2026-04-30 01:54:11', '0000-00-00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `first_name` varchar(120) NOT NULL,
  `middle_name` varchar(120) NOT NULL,
  `last_name` varchar(120) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', '', '', '', 'admin@r3farm.com', 'admin123', 'admin', '2026-04-29 11:03:45'),
(2, 'Gab', '', '', '', 'gab@gmail.com', '123', 'user', '2026-04-29 11:25:40'),
(3, 'Rans Ringor', '', '', '', 'rans@sulasok.com', '123', 'user', '2026-04-29 14:09:31'),
(4, 'ryan', '', '', '', 'ryan@ryan.com', '9MG~2?!TFc?~Ch4', 'user', '2026-04-29 14:51:07'),
(5, 'asd', '', '', '', 'asd@asd', '9MG~2?!TFc?~Ch4', 'user', '2026-04-29 14:51:59'),
(6, 'asdasdasd', '', '', '', 'asdasdsass@asdasdsa', '9MG~2?!TFc?~Ch4', 'user', '2026-04-30 01:04:31'),
(7, 'asd', '', '', '', 'asd@wqe', '9MG~2?!TFc?~Ch4', 'user', '2026-04-30 01:18:17'),
(8, 'gabbb', '', '', '', 'gbb123@gil.com', '123456', 'user', '2026-04-30 08:53:14'),
(9, 'tien', '', '', '', 'tien@gmail.com', '$2y$10$q.Y9RpkXdWR56xSpUSghyO.9lr1HWPUZnW7KauJk.i9MapBq2qoOC', 'user', '2026-05-24 06:13:11'),
(10, 'tien', '', '', '', 'tien@pup.edu', '$2y$10$tPesVRqHjSDDeRuLBF4v4.rSqaOCIrUgISVZQ6R9.H0NrQG2Q3NTy', 'user', '2026-06-08 09:02:51'),
(11, 'Edward', '', '', '', 'ed@ed.com', '$2y$10$FmED9rjDcQh5Qfh2CYIbcekTgwhfbYpjcnnHxTw0wx0FMo7Sl7C7m', 'user', '2026-06-13 05:08:35');

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
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
