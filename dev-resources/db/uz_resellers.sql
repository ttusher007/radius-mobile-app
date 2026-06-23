-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 09:59 AM
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
-- Table structure for table `uz_resellers`
--

CREATE TABLE `uz_resellers` (
  `id` int UNSIGNED NOT NULL,
  `mkt_link_id` int UNSIGNED DEFAULT NULL,
  `resellerid` varchar(25) NOT NULL,
  `resellerpass` varchar(30) NOT NULL,
  `resellername` varchar(40) NOT NULL,
  `reselleraddress` varchar(100) NOT NULL,
  `resellercontact` varchar(25) NOT NULL,
  `contact_support` varchar(25) DEFAULT NULL,
  `contact_billing` varchar(25) DEFAULT NULL,
  `permissionlevel` int NOT NULL,
  `resellerblance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('Yes','No') NOT NULL,
  `resellercreatedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resellerremarks` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `billable` int NOT NULL DEFAULT '1',
  `sms` int NOT NULL DEFAULT '0',
  `percentage` tinyint UNSIGNED NOT NULL DEFAULT '100',
  `dynamic_billingcycle` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `fixed_billing_cycle` tinyint NOT NULL DEFAULT '0',
  `is_online_payment_only` int NOT NULL DEFAULT '0',
  `active` int NOT NULL DEFAULT '1',
  `inactive_remarks` varchar(500) DEFAULT NULL,
  `recharge_settings` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `uz_resellers`
--
ALTER TABLE `uz_resellers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_id` (`active`,`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `uz_resellers`
--
ALTER TABLE `uz_resellers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
