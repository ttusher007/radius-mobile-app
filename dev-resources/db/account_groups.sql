-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 09:05 AM
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
-- Table structure for table `account_groups`
--

CREATE TABLE `account_groups` (
  `Account_Group_Id` int UNSIGNED NOT NULL,
  `Account_Group_Name` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Master_Group_Id` int UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `account_groups`
--

INSERT INTO `account_groups` (`Account_Group_Id`, `Account_Group_Name`, `Master_Group_Id`, `updated_at`, `created_at`) VALUES
(102, 'Bank Account', 14, '2015-12-27 22:16:15', '2015-12-18 15:49:09'),
(103, 'Direct Expenses', 10, '2015-12-19 06:00:00', '2015-12-12 06:00:00'),
(104, 'Cash', 13, '2016-12-19 18:06:07', '2015-12-27 16:21:17'),
(105, 'Internal Manager', 15, '2018-07-19 11:15:07', '2016-01-24 12:00:00'),
(107, 'Supplier', 16, '2016-08-24 06:47:30', '2016-01-24 12:00:00'),
(109, 'Direct Income', 8, '2016-02-09 00:04:03', '0000-00-00 00:00:00'),
(110, 'Sales', 11, '2016-08-28 17:07:11', '0000-00-00 00:00:00'),
(111, 'Purchase', 12, '2016-08-28 17:07:28', '0000-00-00 00:00:00'),
(112, 'Indirect Expenses', 9, '2016-08-24 06:39:30', '2016-08-24 06:39:30'),
(113, 'Indirect Income', 7, '2016-08-24 06:39:55', '2016-08-24 06:39:55'),
(114, 'Manager', 15, '2018-07-19 11:15:24', '2016-08-24 07:01:55'),
(115, 'Accounts Payable', 16, '2016-08-24 07:02:37', '2016-08-24 07:02:37'),
(116, 'Accounts Receivable', 15, '2016-08-24 07:03:05', '2016-08-24 07:03:05'),
(117, 'Current Asset', 2, '2016-08-24 07:10:38', '2016-08-24 07:10:38'),
(118, 'Current Liabilities', 5, '2016-08-24 07:10:49', '2016-08-24 07:10:49'),
(119, 'Capital', 1, '2016-08-24 07:18:08', '2016-08-24 07:18:08'),
(120, 'ISP', 16, '2016-08-24 07:20:12', '2016-08-24 07:20:12'),
(121, 'Sales Discount', 17, '2016-08-25 12:24:15', '2016-08-25 12:24:03'),
(122, 'Purchase Discount', 18, '2016-08-26 12:55:22', '2016-08-26 12:55:22'),
(123, 'Sales Return', 19, '2016-08-26 12:56:10', '2016-08-26 12:56:10'),
(124, 'Purchase Return', 20, '2016-08-26 12:56:33', '2016-08-26 12:56:33'),
(126, 'Salary Advance', 2, '2016-11-30 10:57:05', '2016-11-30 10:57:05'),
(127, 'Employee Loan ( Current Asset )', 2, '2016-12-10 12:02:35', '2016-11-30 10:58:10'),
(128, 'I O U', 2, '2016-11-30 11:18:19', '2016-11-30 11:18:19'),
(129, 'Marketing Expenses  ', 9, '2016-12-03 04:41:18', '2016-12-03 04:41:18'),
(130, 'Office Expenses', 9, '2024-03-30 08:32:00', '2016-12-03 04:45:02'),
(131, 'Office Staff Accommodation Expenses', 9, '2016-12-03 04:51:55', '2016-12-03 04:51:55'),
(132, 'Office Rent Expenses', 9, '2016-12-03 05:17:58', '2016-12-03 05:17:58'),
(133, 'Conveyance Expenses ', 9, '2016-12-03 05:44:07', '2016-12-03 05:44:07'),
(134, 'Electricity Bill Expenses ', 9, '2016-12-03 12:48:13', '2016-12-03 12:48:13'),
(135, 'Refreshment Expenses', 9, '2016-12-03 13:15:39', '2016-12-03 13:15:39'),
(136, 'Telephone Expenses ', 9, '2016-12-03 13:27:53', '2016-12-03 13:27:53'),
(137, 'Loans & Advance ( Asset )', 2, '2016-12-10 09:47:23', '2016-12-10 09:47:23'),
(138, 'Equipment Asset', 3, '2016-12-29 08:09:44', '2016-12-10 11:22:10'),
(139, 'Furniture ', 3, '2016-12-10 11:47:12', '2016-12-10 11:47:12'),
(140, 'Loans ( Liability )', 6, '2016-12-10 11:57:00', '2016-12-10 11:57:00'),
(141, 'Sundry Debtors', 15, '2016-12-11 07:06:30', '2016-12-11 07:06:30'),
(142, 'Fixed Asset', 3, '2016-12-14 12:36:25', '2016-12-14 12:36:25'),
(143, 'Mess Expenses ', 9, '2017-04-16 05:38:39', '2017-04-16 05:38:39'),
(144, 'Mess Expenses ', 9, '2017-04-16 05:38:39', '2017-04-16 05:38:39'),
(145, 'Banasree Office Mess Expenses', 9, '2018-04-17 05:06:50', '2018-04-17 05:06:50'),
(146, 'Data Customer', 15, '2019-12-05 15:10:13', '2019-12-05 15:10:13'),
(147, 'Manager ( NTTN )', 15, '2020-01-18 05:11:56', '2020-01-18 05:11:56'),
(148, 'Equipment Expenses', 10, '2024-03-30 08:31:48', '2023-10-17 15:14:47'),
(149, 'Mobile Bill Expenses', 9, '2024-01-27 09:58:50', '2024-01-27 09:52:38'),
(150, 'Bank interest', 9, '2024-03-30 08:50:23', '2024-03-30 08:50:23'),
(151, 'Bank Charge', 9, '2024-03-30 08:57:39', '2024-03-30 08:57:39'),
(152, 'Inventories', 2, '2024-07-13 13:46:46', '2024-07-13 13:46:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_groups`
--
ALTER TABLE `account_groups`
  ADD PRIMARY KEY (`Account_Group_Id`),
  ADD KEY `fk` (`Master_Group_Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_groups`
--
ALTER TABLE `account_groups`
  MODIFY `Account_Group_Id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_groups`
--
ALTER TABLE `account_groups`
  ADD CONSTRAINT `fk` FOREIGN KEY (`Master_Group_Id`) REFERENCES `master_groups` (`Master_Group_Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
