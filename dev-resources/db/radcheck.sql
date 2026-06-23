-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 09:57 AM
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
-- Table structure for table `radcheck`
--

CREATE TABLE `radcheck` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT ':=',
  `value` varchar(253) NOT NULL DEFAULT '',
  `enablemac` tinyint NOT NULL,
  `macaddress` varchar(17) DEFAULT NULL,
  `ipaddress` varchar(15) NOT NULL DEFAULT 'Dynamic',
  `enableuser` tinyint(1) NOT NULL DEFAULT '1',
  `expiredate` date NOT NULL,
  `expiryextension` date DEFAULT NULL,
  `allow_exp_connect` tinyint NOT NULL DEFAULT '0',
  `expiredchk` int NOT NULL DEFAULT '1',
  `billcycle_id` int NOT NULL,
  `packageid` int UNSIGNED NOT NULL,
  `sub_package_id` int DEFAULT NULL,
  `resellerid` int UNSIGNED NOT NULL,
  `allowpopid` int UNSIGNED NOT NULL,
  `clientname` varchar(30) NOT NULL,
  `dob` date DEFAULT NULL,
  `clientaddress` varchar(100) DEFAULT NULL,
  `clintcontactno` varchar(25) NOT NULL,
  `email` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `clintcreatedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `clientremarks` varchar(500) DEFAULT NULL,
  `tmpdel` int NOT NULL DEFAULT '0',
  `billable` int NOT NULL DEFAULT '1',
  `discount` int NOT NULL DEFAULT '0',
  `flat_level` varchar(25) DEFAULT NULL,
  `building_num` varchar(15) DEFAULT NULL,
  `building_name` varchar(30) DEFAULT NULL,
  `road_num` varchar(15) DEFAULT NULL,
  `road_name` varchar(50) DEFAULT NULL,
  `block_sector` varchar(5) DEFAULT NULL,
  `area` varchar(25) DEFAULT NULL,
  `ip_bill` int DEFAULT '0',
  `rating` decimal(2,1) DEFAULT '3.0',
  `port_id` bigint DEFAULT NULL,
  `nid` varchar(17) DEFAULT NULL,
  `passport` varchar(11) DEFAULT NULL,
  `created_by` int NOT NULL,
  `father` varchar(50) DEFAULT NULL,
  `mother` varchar(50) DEFAULT NULL,
  `pop_disable` int NOT NULL DEFAULT '0',
  `alternative_contact` varchar(100) DEFAULT NULL,
  `alternative_contact_2` varchar(11) DEFAULT NULL,
  `cable_meter` int DEFAULT NULL,
  `client_type` varchar(25) DEFAULT NULL,
  `police_station` varchar(35) DEFAULT NULL,
  `district` varchar(25) DEFAULT NULL,
  `connect_via` int NOT NULL DEFAULT '1',
  `olt_number` int DEFAULT NULL,
  `olt_slot` varchar(4) DEFAULT NULL,
  `pon_no` int DEFAULT NULL,
  `onu_mac` varchar(50) DEFAULT NULL,
  `marketing_method` varchar(50) DEFAULT NULL,
  `marketed_by` varchar(100) DEFAULT NULL,
  `marketed_by_emp_id` int UNSIGNED DEFAULT NULL,
  `comments` varchar(250) DEFAULT NULL,
  `vat_applicable` int NOT NULL DEFAULT '0',
  `deposit` int NOT NULL DEFAULT '0',
  `cable_operator` varchar(150) DEFAULT NULL,
  `connected_to` varchar(20) DEFAULT NULL,
  `paid_via` varchar(50) DEFAULT NULL,
  `original_package_id` int DEFAULT NULL,
  `gpon_epon` varchar(4) DEFAULT NULL,
  `deposit_remarks` varchar(400) DEFAULT NULL,
  `extra_bill` int DEFAULT NULL,
  `investment` int DEFAULT NULL,
  `investment_details` varchar(250) DEFAULT NULL,
  `thana` varchar(50) DEFAULT NULL,
  `from_tg_no` varchar(20) DEFAULT NULL,
  `from_cable_id` varchar(20) DEFAULT NULL,
  `from_cable_meter` varchar(50) DEFAULT NULL,
  `to_cable_id` varchar(20) DEFAULT NULL,
  `to_cable_meter` varchar(50) DEFAULT NULL,
  `vlan_id` varchar(50) DEFAULT NULL,
  `from_google_gps` varchar(50) DEFAULT NULL,
  `to_google_gps` varchar(50) DEFAULT NULL,
  `bill_collect_status` varchar(50) DEFAULT NULL,
  `billing_address` varchar(250) DEFAULT NULL,
  `connected_by` varchar(50) DEFAULT NULL,
  `last_power` varchar(5) DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `radcheck`
--
DELIMITER $$
CREATE TRIGGER `radcheck_after_insert_sync_groupname` AFTER INSERT ON `radcheck` FOR EACH ROW BEGIN
                DECLARE v_package_name varchar(30);

                SELECT packagename INTO v_package_name
                FROM uz_package
                WHERE id = NEW.packageid
                LIMIT 1;

                IF (v_package_name IS NOT NULL) THEN
                    INSERT IGNORE INTO radusergroup (username, groupname, priority)
                    VALUES (NEW.username, v_package_name, 1);
                END IF;
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `radcheck_after_update_sync_groupname` AFTER UPDATE ON `radcheck` FOR EACH ROW BEGIN
                DECLARE v_package_name varchar(30);

                IF (NEW.username <> OLD.username) THEN
                    UPDATE radusergroup
                    SET username = NEW.username
                    WHERE username = OLD.username;
                END IF;

                IF (NEW.packageid <> OLD.packageid) THEN
                    SELECT packagename INTO v_package_name
                    FROM uz_package
                    WHERE id = NEW.packageid
                    LIMIT 1;

                    IF (v_package_name IS NOT NULL) THEN
                        UPDATE radusergroup
                        SET groupname = v_package_name
                        WHERE username = NEW.username
                          AND priority = 1
                          AND groupname <> 'Disable';
                    END IF;
                END IF;
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `userAdd` AFTER INSERT ON `radcheck` FOR EACH ROW begin

DECLARE v_package_name varchar(30);
select packagename into v_package_name from uz_package where id = new.packageid;
insert into tblaccounts (id) values (new.id);

INSERT INTO radusergroup
(username, groupname, priority) VALUES
(new.username, v_package_name, 1);

INSERT INTO customer_last_login
(id,username) VALUES
(new.id,new.username);

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `userDelete` BEFORE DELETE ON `radcheck` FOR EACH ROW begin
delete from tblaccounts where id=old.id;
delete from radreply where username=old.username;
delete from radusergroup where username=old.username;
delete from customer_last_login where username=old.username;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `radcheck`
--
ALTER TABLE `radcheck`
  ADD PRIMARY KEY (`id`,`packageid`),
  ADD UNIQUE KEY `username_2` (`username`) USING BTREE,
  ADD KEY `username` (`username`(32)),
  ADD KEY `radcheck_ibfk_2` (`port_id`),
  ADD KEY `packageid` (`packageid`),
  ADD KEY `resellerid` (`resellerid`),
  ADD KEY `allowpopid` (`allowpopid`),
  ADD KEY `idx_radcheck_customer_state` (`id`,`enableuser`,`tmpdel`),
  ADD KEY `idx_radcheck_username` (`username`),
  ADD KEY `idx_resellerid_enableuser_tmpdel` (`resellerid`,`enableuser`,`tmpdel`),
  ADD KEY `idx_resellerid_expiredate_enableuser_tmpdel` (`resellerid`,`expiredate`,`enableuser`,`tmpdel`),
  ADD KEY `idx_sub_package_id_enableuser_tmpdel` (`sub_package_id`,`enableuser`,`tmpdel`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `radcheck`
--
ALTER TABLE `radcheck`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `radcheck`
--
ALTER TABLE `radcheck`
  ADD CONSTRAINT `radcheck_ibfk_2` FOREIGN KEY (`port_id`) REFERENCES `tbl_port` (`id`),
  ADD CONSTRAINT `radcheck_ibfk_3` FOREIGN KEY (`packageid`) REFERENCES `uz_package` (`id`),
  ADD CONSTRAINT `radcheck_ibfk_4` FOREIGN KEY (`resellerid`) REFERENCES `uz_resellers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
