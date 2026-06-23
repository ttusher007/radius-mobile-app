-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 10:19 AM
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
-- Table structure for table `tblpayment`
--

CREATE TABLE `tblpayment` (
  `id` int NOT NULL,
  `cid` int UNSIGNED NOT NULL,
  `mrn` varchar(50) NOT NULL,
  `col_by` varchar(50) NOT NULL,
  `col_date` date NOT NULL,
  `amt` int NOT NULL,
  `entry_by` varchar(50) NOT NULL,
  `ledger_id` int NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `tblpayment`
--
DELIMITER $$
CREATE TRIGGER `paymentAdd` AFTER INSERT ON `tblpayment` FOR EACH ROW begin
DECLARE V_JOURNAL_ID DECIMAL(12);
    DECLARE V_Ledger_Id_D integer;
    DECLARE V_Ledger_Id_C integer;

    update tblaccounts  set balance = balance-new. amt where id=new.cid;
    SET V_JOURNAL_ID = getJournalSequence(NEW.entry_by);

    select ledger_id into V_Ledger_Id_C
    from ledgers l, radcheck r
    where r.resellerid = l.Reseller_Id
      and r.id = NEW.cid;

    INSERT INTO journal(date, ledger_id, posting, amount,  journal_id, ref_id,mrn, Remarks, entry_by, journal_type)
    VALUES (NEW.col_Date, NEW.ledger_id, 'D', NEW.amt,V_JOURNAL_ID, NEW.cid,NEW.mrn, CONCAT('Received customer payment for ID ', NEW.cid, ' against Trx # ', NEW.mrn), NEW.entry_by, 'LMR');

    INSERT INTO journal(date, ledger_id, posting, amount,  journal_id, ref_id,mrn, Remarks, entry_by, journal_type)
    VALUES (NEW.col_Date, V_Ledger_Id_C, 'C', NEW.amt,V_JOURNAL_ID, NEW.cid,NEW.mrn, CONCAT('Received customer payment for ID ', NEW.cid, ' against Trx # ', NEW.mrn), NEW.entry_by, 'LMR');
  
 end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `paymentDelete` AFTER DELETE ON `tblpayment` FOR EACH ROW begin
update tblaccounts  set balance = balance+old.amt where id=old.cid;
delete from journal where mrn = old.mrn;
end
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblpayment`
--
ALTER TABLE `tblpayment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mrn` (`mrn`),
  ADD KEY `cid` (`cid`),
  ADD KEY `tblpayment_mrn_idx` (`mrn`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblpayment`
--
ALTER TABLE `tblpayment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblpayment`
--
ALTER TABLE `tblpayment`
  ADD CONSTRAINT `tblpayment_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `radcheck` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
