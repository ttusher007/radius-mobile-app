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
-- Table structure for table `uz_poplist`
--

CREATE TABLE `uz_poplist` (
  `id` int UNSIGNED NOT NULL,
  `mkt_link_id` int UNSIGNED DEFAULT NULL,
  `popname` varchar(25) NOT NULL,
  `poplocation` varchar(250) NOT NULL,
  `popcontact` varchar(25) NOT NULL,
  `nasserverip` varchar(15) NOT NULL,
  `serverauth` tinyint NOT NULL,
  `allowresellerid` int UNSIGNED NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `popcreatedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `support_manager` int DEFAULT NULL,
  `geocoordinates` varchar(100) DEFAULT NULL,
  `billable` int NOT NULL DEFAULT '0',
  `sub_reseller` int NOT NULL DEFAULT '0',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `percentage` tinyint UNSIGNED NOT NULL DEFAULT '100',
  `vlans` varchar(200) DEFAULT NULL,
  `pppoe_services` varchar(255) DEFAULT NULL,
  `mapping_type` varchar(20) DEFAULT 'vlan',
  `ethernet_interfaces` varchar(255) DEFAULT NULL,
  `dynamic_billingcycle` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `allow_expiry_extention` tinyint NOT NULL DEFAULT '0',
  `allow_expired_customer_to_connect` tinyint NOT NULL DEFAULT '0',
  `buffer_hours` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Buffer hours added to expiry for all customers of this POP (0-24)',
  `sms` int NOT NULL DEFAULT '0',
  `is_online_payment_only` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `allow_online_payment` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_fixed_nc` int NOT NULL DEFAULT '0',
  `nc_amount` int NOT NULL DEFAULT '0',
  `does_approval_required` int NOT NULL DEFAULT '0',
  `is_pop_disable` tinyint UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `uz_poplist`
--
DELIMITER $$
CREATE TRIGGER `after_upd_uz_poplist` AFTER UPDATE ON `uz_poplist` FOR EACH ROW BEGIN

    UPDATE radcheck 
         SET resellerid = NEW.allowresellerid
       WHERE allowpopid = NEW.id;
       
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `uz_poplist`
--
ALTER TABLE `uz_poplist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `popname` (`popname`),
  ADD KEY `allowresellerid` (`allowresellerid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `uz_poplist`
--
ALTER TABLE `uz_poplist`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `uz_poplist`
--
ALTER TABLE `uz_poplist`
  ADD CONSTRAINT `uz_poplist_ibfk_1` FOREIGN KEY (`allowresellerid`) REFERENCES `uz_resellers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
