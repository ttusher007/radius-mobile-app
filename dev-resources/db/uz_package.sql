-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 23, 2026 at 09:58 AM
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
-- Table structure for table `uz_package`
--

CREATE TABLE `uz_package` (
  `id` int UNSIGNED NOT NULL,
  `packagename` varchar(30) NOT NULL,
  `packagerate` int NOT NULL,
  `poolname` varchar(15) NOT NULL,
  `is_ipv6_enabled` int NOT NULL DEFAULT '0',
  `mikrotik_profile` varchar(50) DEFAULT NULL,
  `ipv6_poolname` varchar(25) DEFAULT NULL,
  `ipv6_pdname` varchar(25) DEFAULT NULL,
  `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '0: inactive, 1: active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `uz_package`
--
DELIMITER $$
CREATE TRIGGER `aft_del_uzpkz` AFTER DELETE ON `uz_package` FOR EACH ROW begin
 
   DELETE from radgroupcheck 
        where groupname = old.packagename;

DELETE from radgroupreply
        where groupname = old.packagename;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `aft_ins_uzpkz` AFTER INSERT ON `uz_package` FOR EACH ROW begin
 

IF (new.is_ipv6_enabled = 0) THEN
   INSERT INTO radgroupreply(groupname, attribute, op, value) 
   VALUES
	(NEW.packagename,'Framed-Protocol',':=','PPP'), 
	(new.packagename,'Service-Type',':=','Framed-User'), 
	(new.packagename,'Framed-Compression',':=','Van-Jacobsen-TCP-IP'), 
	(new.packagename,'Framed-Pool',':=', new.poolname);

ELSEIF (new.is_ipv6_enabled = 1) THEN
   INSERT INTO radgroupreply(groupname, attribute, op, value) 
   VALUES
	(NEW.packagename,'Framed-Protocol',':=','PPP'), 
	(new.packagename,'Service-Type',':=','Framed-User'), 
	(new.packagename,'Framed-Compression',':=','Van-Jacobsen-TCP-IP'), 
	(new.packagename,'Framed-Pool',':=', new.poolname),

	(new.packagename,'Mikrotik-Group',':=',new.mikrotik_profile), 
    (new.packagename,'Framed-IPv6-Pool',':=',new.ipv6_poolname), 
	(new.packagename,'Mikrotik-Delegated-IPv6-Pool',':=', new.ipv6_pdname);
END IF;

	INSERT INTO radgroupcheck (groupname, attribute, op, value) VALUES 
	(new.packagename, 'Simultaneous-Use',':=','1');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `aft_upd_uzpkz` AFTER UPDATE ON `uz_package` FOR EACH ROW BEGIN 

IF (COALESCE(OLD.is_ipv6_enabled,0) = COALESCE(NEW.is_ipv6_enabled,0)) THEN 
  IF (NEW.poolname <> OLD.poolname) THEN
  
    Update radgroupreply set value = NEW.poolname
    where value = OLD.poolname
    and groupname = OLD.packagename; 
  END IF;
  
  IF (NEW.packagename <> OLD.packagename) THEN
  
    Update radgroupreply 
       set groupname = NEW.packagename
     where groupname = OLD.packagename; 
     
    Update radgroupcheck 
       set groupname = NEW.packagename
     where groupname = OLD.packagename; 
     
    Update radusergroup 
       set groupname = NEW.packagename
     where groupname = OLD.packagename;      
   END IF;

 IF (COALESCE(OLD.ipv6_poolname,'~') <> COALESCE(NEW.ipv6_poolname,'~') ) THEN 
    UPDATE radgroupreply
       SET value = NEW.ipv6_poolname
     WHERE groupname = NEW.packagename
       AND attribute = 'Framed-IPv6-Pool';
 END IF;

 IF ( COALESCE(OLD.ipv6_pdname,'~') <> COALESCE(NEW.ipv6_pdname,'~') ) THEN 
    UPDATE radgroupreply
       SET value = NEW.ipv6_pdname
     WHERE groupname = NEW.packagename
       AND attribute = 'Mikrotik-Delegated-IPv6-Pool';

 END IF; 

END IF; 


IF ( COALESCE(OLD.is_ipv6_enabled,0) <> COALESCE(NEW.is_ipv6_enabled,0) AND NEW.is_ipv6_enabled = 1) THEN 
 INSERT INTO radgroupreply(groupname, attribute, op, value) 
   VALUES
    
    (new.packagename,'Mikrotik-Group',':=',new.mikrotik_profile), 
    (new.packagename,'Framed-IPv6-Pool',':=',new.ipv6_poolname), 
	(new.packagename,'Mikrotik-Delegated-IPv6-Pool',':=', new.ipv6_pdname);

ELSEIF (COALESCE(OLD.is_ipv6_enabled,0) <> COALESCE(NEW.is_ipv6_enabled,0) AND NEW.is_ipv6_enabled = 0) THEN 
 DELETE FROM radgroupreply
  WHERE groupname = OLD.packagename
    AND attribute in ('Framed-IPv6-Pool','Mikrotik-Delegated-IPv6-Pool');

END IF;

END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `uz_package`
--
ALTER TABLE `uz_package`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uz_package_status_index` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `uz_package`
--
ALTER TABLE `uz_package`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
