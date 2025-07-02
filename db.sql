-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 09:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salesinventorydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `contains`
--

CREATE TABLE `contains` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `costPrice` decimal(10,7) NOT NULL,
  `stockLevel` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contains`
--

INSERT INTO `contains` (`inventory_id`, `product_id`, `supplier_id`, `costPrice`, `stockLevel`) VALUES
(1, 1, 1, 40.0000000, 90);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contactInfo` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `contactInfo`, `address`, `created_at`) VALUES
(1, 'KRISH', '8210442560', 'BHAGA', '2025-07-02 18:40:35');

-- --------------------------------------------------------

--
-- Table structure for table `deliverypartner`
--

CREATE TABLE `deliverypartner` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contactInfo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliverypartner`
--

INSERT INTO `deliverypartner` (`id`, `name`, `contactInfo`) VALUES
(1, 'PANKAJ', '2345678901');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `warehouseLocation` varchar(255) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `warehouseLocation`, `capacity`, `created_at`) VALUES
(1, 'BHAGA', NULL, '2025-07-02 18:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `ordered`
--

CREATE TABLE `ordered` (
  `order_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `costPriceAtSale` decimal(10,7) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ordered`
--

INSERT INTO `ordered` (`order_id`, `inventory_id`, `product_id`, `supplier_id`, `costPriceAtSale`, `quantity`) VALUES
(1, 1, 1, 1, 40.0000000, 10);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `orderDate` date NOT NULL,
  `totalPrice` decimal(10,2) NOT NULL,
  `profit` decimal(10,2) DEFAULT NULL,
  `orderStatus` varchar(50) NOT NULL DEFAULT 'Pending',
  `paymentId` int(11) DEFAULT NULL,
  `transportationId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `orderDate`, `totalPrice`, `profit`, `orderStatus`, `paymentId`, `transportationId`) VALUES
(1, 1, '2025-07-03', 510.00, 110.00, 'Completed', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `paymentDate` date DEFAULT NULL,
  `paymentStatus` varchar(50) NOT NULL DEFAULT 'Pending',
  `amount` decimal(10,2) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_contactInfo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `paymentDate`, `paymentStatus`, `amount`, `customer_name`, `customer_contactInfo`, `created_at`) VALUES
(1, 1, '2025-07-03', 'Completed', 510.00, 'KRISH', '8210442560', '2025-07-02 18:43:45');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `created_at`) VALUES
(1, 'DAIRY MILK', 'CHOCOLATE', 51.00, '2025-07-02 18:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contactInfo` varchar(255) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contactInfo`, `location`, `created_at`) VALUES
(1, 'NITISH', '1234567890', 'BHAGA', '2025-07-02 18:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `transportation`
--

CREATE TABLE `transportation` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `deliveryPartner_id` int(11) DEFAULT NULL,
  `deliveryStatus` varchar(50) NOT NULL DEFAULT 'Processing',
  `deliveryDate` date DEFAULT NULL,
  `estimatedDeliveryDate` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transportation`
--

INSERT INTO `transportation` (`id`, `order_id`, `deliveryPartner_id`, `deliveryStatus`, `deliveryDate`, `estimatedDeliveryDate`, `created_at`) VALUES
(1, 1, 1, 'Delivered', '2025-07-03', NULL, '2025-07-02 18:43:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `contactInfo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `contactInfo`, `created_at`) VALUES
(1, 'NIKHIL', 'nikhilagarwal24242424@gmail.com', '$2y$10$tVkjzpavDfw4EX2opQCE/OSM/pq33TPiC9258593T6zgpBL7P8oLm', 'admin', '9263053097', '2025-07-02 18:21:27'),
(4, 'NIKHIL AGARWAL', 'nikhilagarwal24242@gmail.com', '$2y$10$NzsbQ1Qv.jdf73Tz9aOM9uuItHeZEMZkHLjC9e8wF0MH6bwjkCtRm', 'inventory_manager', '9263053097', '2025-07-02 18:24:05'),
(6, 'NIKHIL SAMALIA ', 'nikhilagarwal.231it044@nitk.edu.in', '$2y$10$J/6AmKkLx8TiG05wOAWy9uyhxD/mqItyArPGflPqCOzxdkHr8VWoK', 'sales Manager', '9263053097', '2025-07-02 18:26:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contains`
--
ALTER TABLE `contains`
  ADD PRIMARY KEY (`inventory_id`,`product_id`,`supplier_id`,`costPrice`),
  ADD KEY `fk_contains_products` (`product_id`),
  ADD KEY `fk_contains_suppliers` (`supplier_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliverypartner`
--
ALTER TABLE `deliverypartner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouseLocation` (`warehouseLocation`);

--
-- Indexes for table `ordered`
--
ALTER TABLE `ordered`
  ADD PRIMARY KEY (`order_id`,`inventory_id`,`product_id`,`supplier_id`,`costPriceAtSale`),
  ADD KEY `fk_ordered_contains` (`inventory_id`,`product_id`,`supplier_id`,`costPriceAtSale`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_customers` (`customer_id`),
  ADD KEY `fk_orders_payments` (`paymentId`),
  ADD KEY `fk_orders_transportation` (`transportationId`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payments_orders` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `transportation`
--
ALTER TABLE `transportation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transportation_orders` (`order_id`),
  ADD KEY `fk_transportation_deliverypartner` (`deliveryPartner_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deliverypartner`
--
ALTER TABLE `deliverypartner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transportation`
--
ALTER TABLE `transportation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contains`
--
ALTER TABLE `contains`
  ADD CONSTRAINT `fk_contains_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contains_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contains_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `ordered`
--
ALTER TABLE `ordered`
  ADD CONSTRAINT `fk_ordered_contains` FOREIGN KEY (`inventory_id`,`product_id`,`supplier_id`,`costPriceAtSale`) REFERENCES `contains` (`inventory_id`, `product_id`, `supplier_id`, `costPrice`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ordered_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customers` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_payments` FOREIGN KEY (`paymentId`) REFERENCES `payments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_transportation` FOREIGN KEY (`transportationId`) REFERENCES `transportation` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transportation`
--
ALTER TABLE `transportation`
  ADD CONSTRAINT `fk_transportation_deliverypartner` FOREIGN KEY (`deliveryPartner_id`) REFERENCES `deliverypartner` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transportation_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
