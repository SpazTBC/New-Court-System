-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.24-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.0.0.6468
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for courtsystem
CREATE DATABASE IF NOT EXISTS `courtsystem` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `courtsystem`;

CREATE TABLE IF NOT EXISTS `client_intake` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `ssn_last_four` varchar(4) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(2) NOT NULL,
  `zip` varchar(10) NOT NULL,
  `case_type` varchar(50) NOT NULL,
  `referral_source` varchar(50) DEFAULT NULL,
  `case_description` text NOT NULL,
  `intake_date` datetime NOT NULL,
  `intake_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Dumping structure for table courtsystem.cases
CREATE TABLE IF NOT EXISTS `cases` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `caseid` text NOT NULL,
  `assigneduser` text NOT NULL,
  `assigned` text NOT NULL,
  `details` text NOT NULL,
  `supervisor` text NOT NULL,
  `shared01` text NOT NULL,
  `shared02` text NOT NULL,
  `shared03` text NOT NULL,
  `shared04` text NOT NULL,
  `type` text NOT NULL,
  `defendent` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.

-- Dumping structure for table courtsystem.evidence
CREATE TABLE IF NOT EXISTS `evidence` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `file` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.

-- Dumping structure for table courtsystem.users
CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(1) NOT NULL AUTO_INCREMENT,
  `charactername` text NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `job` text NOT NULL,
  `supervisorjob` tinyint(1) NOT NULL DEFAULT 0,
  `shared1` text NOT NULL,
  `shared2` text NOT NULL DEFAULT '0',
  `shared3` text NOT NULL DEFAULT '0',
  `shared4` text NOT NULL DEFAULT '0',
  `ip` text NOT NULL,
  `banned` tinyint(1) NOT NULL,
  `staff` tinyint(1) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
