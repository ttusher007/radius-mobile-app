-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 07:46 AM
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
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'Administrator with all permissions', '2016-04-21 15:12:42', '2026-05-01 03:32:37'),
(2, 'Support Executive', 'Support Executive', '2016-05-14 11:59:11', '2016-05-14 11:59:11'),
(3, 'Support Manager', 'Support Manager', '2016-05-17 00:33:25', '2016-05-17 00:33:25'),
(4, 'Accounts Manager', 'Accounts Manager', '2016-05-17 00:33:43', '2016-05-17 00:33:43'),
(5, 'Accounts Executive', 'Accounts Executive', '2016-05-17 00:34:18', '2016-05-17 00:34:18'),
(6, 'Asst. Manager', 'Asst. Manager', '2016-05-17 00:49:55', '2016-05-17 00:49:55'),
(7, 'Store Executive', 'Store Executive', '2016-09-15 06:33:26', '2018-04-23 08:38:56'),
(8, 'Reseller Admin', 'Reseller Admin', '2016-10-08 06:43:51', '2016-10-08 06:43:51'),
(9, 'Account Executive X', 'Account Executive X', '2016-11-08 11:08:08', '2016-11-08 11:08:08'),
(10, 'Accounts', 'Accounts', '2016-11-29 05:38:47', '2016-11-29 05:38:47'),
(11, 'Sub Reseller Admin', 'Sub Reseller Admin', '2016-12-04 16:30:30', '2026-03-08 17:57:42'),
(12, 'Store Manager', 'Store Manager', '2018-04-23 08:39:44', '2018-04-23 08:39:44'),
(13, 'HR Manager', 'HR Manager', '2018-07-15 05:35:13', '2018-07-15 05:35:13'),
(14, 'HR Executive', 'HR Executive', '2018-07-25 03:36:42', '2018-07-25 03:36:42'),
(15, 'Super Admin', 'Super Admin', '2018-07-28 02:23:25', '2018-07-28 02:23:25'),
(16, 'NOC', 'NOC', '2018-07-30 07:38:05', '2018-07-30 07:38:05'),
(17, 'Procurement Manager', 'Procurement Manager', '2018-08-06 11:38:32', '2018-08-06 11:38:32'),
(18, 'Store Manager', 'Store Manager', '2019-09-12 16:05:13', '2019-09-12 16:05:13'),
(19, 'Data Customer Manager', 'Data Customer Manager', '2019-12-05 15:44:58', '2019-12-05 15:44:58'),
(20, 'Area In-charge', 'Area In-charge', '2019-12-29 10:40:35', '2019-12-29 10:43:10'),
(21, 'Bill Man', 'Bill Man', '2019-12-29 12:12:48', '2019-12-29 12:12:48'),
(22, 'Network Admin', 'Administrator for Network Module', '2020-07-22 00:23:24', '2020-07-22 00:23:24'),
(23, 'Selfcare Manager', 'Selfcare Manager', '2020-11-13 05:25:19', '2020-11-13 05:25:19'),
(24, 'SMS', 'SMS', '2024-06-07 11:55:12', '2024-06-07 11:55:12'),
(25, 'Marketing_Manager', 'Marketing_Manager', '2024-10-29 12:49:40', '2024-10-29 12:49:40'),
(26, 'Data Customer', 'Data Customer', '2025-04-20 11:03:58', '2026-05-23 09:15:48'),
(27, 'KAM - Marketing', 'Key Account Manager, Marketing', '2026-02-02 03:51:33', '2026-02-02 03:51:33'),
(28, 'KAM Team Lead - Marketing', 'Team Leader of KAM [Marketing Department], Can Create upto Feasibilities and View all customers data.', '2026-02-03 02:15:42', '2026-02-03 02:15:42'),
(29, 'Reseller Admin Dynamic', 'Reseller Admin Dynamic Billing Cycle', '2026-03-08 17:58:05', '2026-03-08 17:58:05'),
(30, 'HR Accounts', 'HR Accounts', '2026-04-18 12:59:14', '2026-04-18 12:59:14'),
(31, 'Manager SMS', 'Role for Bulk SMS', '2026-05-07 07:16:43', '2026-05-07 07:16:43'),
(32, 'Data Customer Admin', 'For Data Customer Account View', '2026-05-23 09:23:22', '2026-05-23 09:23:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
