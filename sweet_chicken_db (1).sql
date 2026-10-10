-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2026 at 10:47 PM
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
-- Database: `sweet_chicken_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_performed` varchar(100) NOT NULL,
  `target_resource` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `ingredient` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `ingredient`, `quantity`, `unit`, `updated_at`) VALUES
(1, 'Chicken', 100, 'pcs', '2026-08-15 15:54:06'),
(2, 'Cooking Oil', 50, 'liters', '2026-08-15 15:54:06'),
(3, 'Burger Bun', 100, 'pcs', '2026-08-15 15:54:06'),
(4, 'French Fries', 80, 'packs', '2026-08-15 15:54:06'),
(5, 'Soft Drinks', 100, 'bottles', '2026-08-15 15:54:06');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `code` varchar(60) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('Available','Unavailable') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `code`, `name`, `category`, `description`, `price`, `image`, `stock`, `status`) VALUES
(1, 'legacy-2pc-fried-chicken', '2pc Fried Chicken', 'Chicken', '', 180.00, 'chicken1.png', 100, 'Available'),
(2, 'legacy-chicken-burger', 'Chicken Burger', 'Burger', 'Chicken Burger Deluxe', 150.00, 'burger1.png', 100, 'Available'),
(3, 'legacy-chicken-rice-meal', 'Chicken Rice Meal', 'Rice Meal', 'Chicken with Rice', 170.00, 'meal1.png', 100, 'Available'),
(4, 'legacy-french-fries', 'French Fries', 'Sides', 'Regular Fries', 80.00, 'fries.png', 100, 'Available'),
(5, 'legacy-soft-drinks', 'Soft Drinks', 'Drink', 'Coke', 50.00, 'coke.png', 100, 'Available'),
(6, 'barkada-7', 'Barkada Feast Combo - 7 pcs', 'Barkada Meals', '7 pcs • Choose 3 flavors • 2 rice • 2 drinks', 439.00, 'chicken.png', 99, 'Available'),
(7, 'barkada-4', 'Barkada Feast Combo - 4 pcs', 'Barkada Meals', '4 pcs • Choose 2 flavors • 2 rice • 2 drinks', 299.00, 'chicken.png', 97, 'Available'),
(8, 'chicken-3', '3pcs Chicken Combo', 'Flavored Chicken Combo', 'Choose 1 flavor', 209.00, 'chicken.png', 98, 'Available'),
(9, 'chicken-2', '2pcs Chicken Combo', 'flavored chicken combo', '', 169.00, 'chicken.png', 100, 'Unavailable'),
(10, 'chicken-pops', 'Chicken Pops Combo', 'Flavored Chicken Combo', 'Choose 1 flavor', 119.00, 'chicken.png', 100, 'Available'),
(11, 'soda-15l', 'Soda 1.5L', 'Soda', 'Coke / Sprite / Royal', 85.00, 'fries.png', 100, 'Available'),
(12, 'soda-mismo', 'Soda Mismo', 'Soda', 'Coke / Sprite / Royal', 25.00, 'fries.png', 100, 'Available'),
(13, 'graham-blueberry', 'Graham Ice Cream - Blueberry', 'Dessert', 'Blueberry', 39.00, 'pasta.png', 100, 'Available'),
(14, 'graham-strawberry', 'Graham Ice Cream - Strawberry', 'Dessert', 'Strawberry', 39.00, 'pasta.png', 100, 'Available'),
(15, 'graham-mango', 'Graham Ice Cream - Mango', 'Dessert', 'Mango', 39.00, 'pasta.png', 100, 'Available'),
(16, 'chao-fan', 'Chao Fan Rice', 'Add Ons', 'Add-on', 30.00, 'pasta.png', 100, 'Available'),
(17, 'plain-rice', 'Plain Rice', 'Add Ons', 'Add-on', 20.00, 'pasta.png', 100, 'Available'),
(18, 'garlic-mayo', 'Garlic Mayo Dip', 'Add Ons', 'Add-on', 20.00, 'pasta.png', 100, 'Available'),
(19, 'extra-chicken', 'Extra Chicken', 'Add Ons', 'Add-on', 59.00, 'chicken.png', 100, 'Available'),
(20, 'extra-sauce', 'Extra Sauce', 'Add Ons', 'Add-on', 15.00, 'pasta.png', 100, 'Available'),
(21, 'juice-pitcher', 'Juice Pitcher', 'Juice', 'Iced Tea / Red Tea', 99.00, 'pasta.png', 100, 'Available'),
(22, 'juice-cup', 'Juice Cup', 'Juice', 'Iced Tea / Red Tea', 25.00, 'pasta.png', 100, 'Available'),
(23, 'hotdog', 'hotdog', 'meals', '', 99.00, NULL, 100, 'Unavailable');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `order_type` varchar(30) NOT NULL DEFAULT 'Dine-In',
  `table_number` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','GCash') DEFAULT NULL,
  `gcash_reference` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Preparing','Ready','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `customer_name`, `phone`, `address`, `order_type`, `table_number`, `total`, `payment_method`, `gcash_reference`, `status`, `created_at`) VALUES
(1, NULL, 'Walk-in Customer', NULL, 'N/A', 'Walk-In', NULL, 209.00, 'Cash', NULL, 'Ready', '2026-08-15 15:58:18'),
(2, NULL, 'Walk-in Customer', NULL, 'N/A', 'Walk-In', NULL, 439.00, 'Cash', NULL, 'Completed', '2026-08-15 15:59:10'),
(3, 4, 'Martin Morales', '09669863132', 'N/A', 'Take-Out', NULL, 299.00, 'Cash', 'N/A', 'Pending', '2026-08-18 13:01:11'),
(4, NULL, 'Walk-in Customer', NULL, 'N/A', 'Dine-In', NULL, 209.00, 'Cash', NULL, 'Ready', '2026-08-23 14:21:36'),
(5, NULL, 'Walk-in Customer', NULL, 'N/A', 'Dine-In', NULL, 299.00, 'Cash', NULL, 'Ready', '2026-08-23 14:26:39'),
(6, 4, 'Martin Morales', '09669863132', 'N/A', 'Dine-In', NULL, 299.00, 'Cash', 'N/A', 'Ready', '2026-08-23 14:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_id`, `quantity`, `price`) VALUES
(1, 1, 8, 1, 209.00),
(2, 2, 6, 1, 439.00),
(3, 3, 7, 1, 299.00),
(4, 4, 8, 1, 209.00),
(5, 5, 7, 1, 299.00),
(6, 6, 7, 1, 299.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','GCash') DEFAULT NULL,
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Paid',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `payment_method`, `payment_status`, `payment_date`) VALUES
(1, 1, 209.00, 'Cash', 'Paid', '2026-08-15 15:58:18'),
(2, 2, 439.00, 'Cash', 'Paid', '2026-08-15 15:59:10'),
(3, 3, 299.00, 'Cash', 'Unpaid', '2026-08-18 13:01:11'),
(4, 4, 209.00, 'Cash', 'Paid', '2026-08-23 14:21:36'),
(5, 5, 299.00, 'Cash', 'Paid', '2026-08-23 14:26:39'),
(6, 6, 299.00, 'Cash', 'Unpaid', '2026-08-23 14:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `order_id`, `total`, `created_at`) VALUES
(1, 2, 439.00, '2026-08-15 16:04:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier','kitchen','customer') NOT NULL DEFAULT 'customer',
  `status` enum('Active','Deactivated') NOT NULL DEFAULT 'Active',
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `status`, `otp`, `otp_expiry`, `created_at`) VALUES
(1, 'System Administrator', 'admin@sweetchicken.com', '$2y$10$Nek/ByOyGIX1M01ycpvUxuRUPFTLaZIVp9s5C.curUggTEXnCqAU2', 'admin', 'Active', NULL, NULL, '2026-08-15 15:54:05'),
(2, 'martin', 'martinmorales2002@gmail.com', '$2y$10$BgFcgtYoWpp/sj0nKMKbBevk2/oPZqpgNXj5IRBhQB1cAxyP95MG2', 'cashier', 'Active', NULL, NULL, '2026-08-15 15:56:59'),
(3, 'martin', 'martinmorales0303@gmail.com', '$2y$10$PeNRN9yVjfBkrjKKV4aeiu08N3.y/uhGujDsUG/lfclof7oOtomVO', 'kitchen', 'Active', NULL, NULL, '2026-08-15 15:57:41'),
(4, 'martin', 'martinmorales20020303@gmail.com', '$2y$10$yfWVjDio2QbHW8YbFFJhXOtC4C9Y5RhKSG86Aqru0MTDWQGXU1KC6', 'customer', 'Active', NULL, NULL, '2026-08-15 16:08:27'),
(5, 'byron', 'by123!@gmail.com', '$2y$10$tLEZ7ttd.pdP6RnsrTUHLuX17tVUiR3sq.0UrleS.PbSdVHMH1QQW', 'cashier', 'Active', NULL, NULL, '2026-08-27 06:05:14'),
(6, 'ranz', 'ranz@gmail.com', '$2y$10$xeBoEHGfJhbuCCVv3WIfLOAtHyqSGLUZ4WoR/SeY1x32y8f27BFKW', 'customer', 'Active', NULL, NULL, '2026-08-27 06:08:59'),
(7, 'nero', 'sampangj30@gmail.com', '$2y$10$0YA49gWqKyqyP5dBrEsyc.Q2ZWJWplpAffIn3QXMhzB5WZncONGH.', 'customer', 'Active', NULL, NULL, '2026-09-29 14:03:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `idx_audit_user` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

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
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
