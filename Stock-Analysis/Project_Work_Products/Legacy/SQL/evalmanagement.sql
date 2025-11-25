-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:15 PM
-- Server version: 10.3.22-MariaDB-log
-- PHP Version: 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stock_market_2`
--

-- --------------------------------------------------------

--
-- Table structure for table `evalmanagement`
--
-- Creation: May 10, 2023 at 12:59 AM
-- Last update: May 10, 2023 at 12:59 AM
--

DROP TABLE IF EXISTS `evalmanagement`;
CREATE TABLE IF NOT EXISTS `evalmanagement` (
  `idevalmanagement` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL DEFAULT 0,
  `ownerearnings` float NOT NULL DEFAULT 0,
  `managementowners` tinyint(1) NOT NULL DEFAULT 0,
  `benefitreinvest` tinyint(1) NOT NULL DEFAULT 0,
  `expandbypurchase` tinyint(1) NOT NULL DEFAULT 0,
  `mimiccompetition` tinyint(1) NOT NULL DEFAULT 0,
  `hyperactivity` tinyint(1) NOT NULL DEFAULT 0,
  `cosnsistanthistory` tinyint(1) NOT NULL DEFAULT 0,
  `communicatemorethangaap` tinyint(1) NOT NULL DEFAULT 0,
  `publicconfession` tinyint(1) NOT NULL DEFAULT 0,
  `frfeqreorg` tinyint(1) NOT NULL DEFAULT 0,
  `lasteval` timestamp NOT NULL DEFAULT current_timestamp(),
  `user` varchar(45) NOT NULL DEFAULT '',
  `summary` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idevalmanagement`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `evalmanagement`
--

INSERT IGNORE INTO `evalmanagement` (`idevalmanagement`, `idstockinfo`, `ownerearnings`, `managementowners`, `benefitreinvest`, `expandbypurchase`, `mimiccompetition`, `hyperactivity`, `cosnsistanthistory`, `communicatemorethangaap`, `publicconfession`, `frfeqreorg`, `lasteval`, `user`, `summary`) VALUES
(1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2007-10-01 04:28:22', 'kevin', 4),
(2, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2007-10-01 04:39:04', 'kevin', 4),
(3, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2007-10-01 04:49:22', 'kevin', 4),
(4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, '2007-10-01 04:51:14', 'kevin', 4),
(5, 4, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, '2007-10-01 04:52:25', 'kevin', 4),
(6, 4, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, '2007-10-01 04:53:41', 'kevin', 4),
(7, 4, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, '2007-10-01 04:55:26', 'kevin', 4),
(8, 4, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, '2007-10-01 04:56:35', 'kevin', 5),
(9, 10, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, '2007-10-01 05:01:53', 'kevin', 6),
(10, 2, 0, 1, 1, 0, 1, 0, 0, 1, 0, 0, '2007-10-01 05:46:06', 'kevin', 6),
(11, 1, 0, 0, 1, 1, 0, 0, 1, 1, 0, 0, '2007-10-03 06:59:44', 'kevin', 6),
(12, 49, 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, '2007-10-03 07:00:07', 'kevin', 5),
(13, 51, 0, 1, 0, 0, 0, 0, 1, 1, 0, 0, '2007-10-07 06:06:27', 'kevin', 7),
(14, 20, 0, 1, 1, 1, 0, 1, 1, 0, 0, 0, '2007-10-28 05:23:27', 'kevin', 5),
(15, 5, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, '2007-12-13 06:49:41', 'kevin', 7),
(16, 53, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, '2008-01-29 06:00:59', 'kevin', 6),
(17, 54, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, '2008-01-29 06:08:28', 'kevin', 7),
(18, 55, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, '2008-01-29 06:17:51', 'kevin', 7),
(19, 56, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, '2008-01-29 06:24:26', 'kevin', 4),
(20, 57, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, '2008-01-29 06:36:22', 'kevin', 5),
(21, 68, 0, 1, 0, 1, 1, 1, 0, 0, 0, 1, '2008-04-16 04:28:55', 'KEVIN', 1),
(22, 70, 0, 1, 1, 0, 0, 0, 1, 0, 0, 0, '2008-04-16 04:45:53', 'KEVIN', 7),
(23, 15, 0, 1, 0, 0, 1, 1, 0, 0, 0, 1, '2010-10-22 16:27:17', 'KEVIN', 2),
(24, 5, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, '2014-03-05 05:29:44', 'KEVIN', 3);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
