-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 09:06 AM
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
-- Table structure for table `master_groups`
--

CREATE TABLE `master_groups` (
  `Master_Group_Id` int UNSIGNED NOT NULL,
  `Master_Group_Name` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Trial_Balance` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `master_groups`
--

INSERT INTO `master_groups` (`Master_Group_Id`, `Master_Group_Name`, `Trial_Balance`) VALUES
(1, 'Capital', 3),
(2, 'Current Asset', 2),
(3, 'Fixed Asset', 2),
(4, 'Profit/ Loss', 3),
(5, 'Current Liabilities', 3),
(6, 'Long Term Loan/Liabilities', 3),
(7, 'Indirect Income', 1),
(8, 'Direct Income', 1),
(9, 'Indirect Expenses', 1),
(10, 'Direct Expenses', 1),
(11, 'Sales', 1),
(12, 'Purchase', 1),
(13, 'Cash', 2),
(14, 'Bank', 2),
(15, 'Account Receivable', 2),
(16, 'Account Payable', 3),
(17, 'Sales Discount', 1),
(18, 'Purchase Discount', 1),
(19, 'Sales Return', 1),
(20, 'Purchase Return', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `master_groups`
--
ALTER TABLE `master_groups`
  ADD PRIMARY KEY (`Master_Group_Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `master_groups`
--
ALTER TABLE `master_groups`
  MODIFY `Master_Group_Id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
