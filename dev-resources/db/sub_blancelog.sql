-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 10:36 AM
-- Server version: 8.0.25
-- PHP Version: 7.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `radius`
--

-- --------------------------------------------------------

--
-- Table structure for table `sub_blancelog`
--

CREATE TABLE `sub_blancelog` (
  `id` int UNSIGNED NOT NULL,
  `log_date_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action_by` varchar(15) NOT NULL,
  `action_to` varchar(64) NOT NULL,
  `action` varchar(100) NOT NULL,
  `action_taka` decimal(10,2) NOT NULL,
  `manager_amount` double(10,2) DEFAULT NULL,
  `remarks` varchar(200) NOT NULL,
  `bill_type_id` int DEFAULT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `pop_id` int UNSIGNED DEFAULT NULL,
  `package_id` int UNSIGNED DEFAULT NULL,
  `sub_package_id` int UNSIGNED DEFAULT NULL,
  `package_rate` decimal(10,2) DEFAULT NULL,
  `sub_package_rate` decimal(10,2) DEFAULT NULL,
  `reseller_percentage` decimal(6,3) DEFAULT NULL,
  `ip_bill` decimal(10,2) DEFAULT NULL,
  `extra_bill` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `days` int DEFAULT NULL,
  `unit` varchar(10) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `profit` decimal(10,2) DEFAULT NULL,
  `data_source` varchar(20) DEFAULT NULL COMMENT 'live | backfill | NULL=legacy'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `sub_blancelog`
--
DELIMITER $$
CREATE TRIGGER `subBalanceLogAdd` AFTER INSERT ON `sub_blancelog` FOR EACH ROW begin
update uz_poplist set balance= balance-new.action_taka where id=new.action_by;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `subBalanceLogDelete` BEFORE DELETE ON `sub_blancelog` FOR EACH ROW begin
update uz_poplist set blance = blance+old.action_taka where id=old.action_by;

END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sub_blancelog`
--
ALTER TABLE `sub_blancelog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_type_id` (`bill_type_id`),
  ADD KEY `sub_blancelog_idx_action_by_log` (`action_by`,`log_date_time`),
  ADD KEY `sub_blancelog_idx_pop_log` (`pop_id`,`log_date_time`),
  ADD KEY `sub_blancelog_idx_customer_log` (`customer_id`,`log_date_time`),
  ADD KEY `sub_blancelog_idx_bill_type_log` (`bill_type_id`,`log_date_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sub_blancelog`
--
ALTER TABLE `sub_blancelog`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
