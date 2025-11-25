-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:14 PM
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
-- Table structure for table `evalbusiness`
--
-- Creation: May 10, 2023 at 12:59 AM
-- Last update: May 10, 2023 at 12:59 AM
--

DROP TABLE IF EXISTS `evalbusiness`;
CREATE TABLE IF NOT EXISTS `evalbusiness` (
  `idevalbusiness` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL DEFAULT 0,
  `simple` tinyint(1) NOT NULL DEFAULT 0,
  `regulated` tinyint(1) NOT NULL DEFAULT 0,
  `neededproduct` tinyint(1) NOT NULL DEFAULT 0,
  `noclosesubstitute` tinyint(1) NOT NULL DEFAULT 0,
  `cosnsistanthistory` tinyint(1) NOT NULL DEFAULT 0,
  `lasteval` timestamp NOT NULL DEFAULT current_timestamp(),
  `user` varchar(45) NOT NULL DEFAULT '',
  `summary` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idevalbusiness`)
) ENGINE=MyISAM AUTO_INCREMENT=58 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `evalbusiness`
--

INSERT IGNORE INTO `evalbusiness` (`idevalbusiness`, `idstockinfo`, `simple`, `regulated`, `neededproduct`, `noclosesubstitute`, `cosnsistanthistory`, `lasteval`, `user`, `summary`) VALUES
(1, 4, 0, 0, 0, 0, 0, '0000-00-00 00:00:00', '', 1),
(2, 10, 1, 1, 1, 1, 0, '2007-10-01 05:34:55', 'KEVIN', 3),
(3, 2, 1, 1, 1, 0, 1, '2007-10-01 05:45:21', 'kevin', 3),
(4, 17, 1, 1, 1, 0, 1, '2007-10-03 02:57:19', 'kevin', 3),
(5, 49, 1, 1, 1, 0, 1, '2007-10-03 06:59:18', 'kevin', 3),
(6, 50, 1, 1, 1, 0, 1, '2007-10-07 05:51:40', 'kevin', 3),
(7, 5, 1, 0, 1, 1, 1, '2007-10-08 22:33:01', 'kevin', 5),
(8, 14, 1, 0, 1, 0, 1, '2007-10-14 22:40:10', 'kevin', 4),
(9, 20, 1, 0, 1, 1, 1, '2007-10-28 05:22:26', 'kevin', 5),
(10, 34, 1, 1, 1, 1, 1, '2007-12-13 07:00:31', 'kevin', 4),
(11, 53, 1, 1, 1, 0, 0, '2008-01-29 05:49:26', 'kevin', 2),
(12, 54, 1, 1, 1, 0, 1, '2008-01-29 06:11:29', 'kevin', 3),
(13, 55, 1, 0, 1, 1, 1, '2008-01-29 06:15:19', 'kevin', 5),
(14, 56, 1, 1, 1, 0, 1, '2008-01-29 06:26:56', 'kevin', 3),
(15, 57, 1, 1, 1, 1, 0, '2008-01-29 06:34:14', 'kevin', 3),
(16, 67, 1, 1, 1, 0, 1, '2008-02-11 02:10:43', 'KEVIN', 3),
(17, 5, 1, 0, 1, 0, 1, '2008-02-11 03:47:16', 'KEVIN', 4),
(18, 68, 1, 0, 1, 0, 0, '2008-02-17 16:05:25', 'KEVIN', 3),
(19, 68, 0, 0, 0, 0, 0, '2008-04-16 04:20:28', 'KEVIN', 1),
(20, 70, 1, 0, 1, 1, 1, '2008-04-16 04:42:15', 'KEVIN', 5),
(21, 2, 0, 1, 1, 0, 0, '2009-05-24 01:09:47', 'KEVIN', 1),
(22, 3, 1, 1, 1, 0, 1, '2009-05-24 01:10:04', 'KEVIN', 3),
(23, 5, 1, 1, 1, 0, 1, '2009-05-24 01:10:20', 'KEVIN', 3),
(24, 6, 1, 0, 1, 0, 0, '2009-05-24 01:10:42', 'KEVIN', 3),
(25, 9, 1, 1, 1, 1, 1, '2009-05-24 01:10:56', 'KEVIN', 4),
(26, 11, 1, 0, 1, 0, 0, '2009-05-24 01:11:17', 'KEVIN', 3),
(27, 12, 0, 1, 1, 0, 1, '2009-05-24 01:11:40', 'KEVIN', 2),
(28, 13, 0, 1, 1, 1, 0, '2009-05-24 01:12:01', 'KEVIN', 2),
(29, 14, 1, 1, 0, 0, 0, '2009-05-24 01:12:24', 'KEVIN', 1),
(30, 15, 1, 1, 1, 1, 0, '2009-05-24 01:12:53', 'KEVIN', 3),
(31, 16, 1, 1, 1, 0, 1, '2009-05-24 01:13:07', 'KEVIN', 3),
(32, 17, 1, 0, 1, 0, 0, '2009-05-24 01:13:30', 'KEVIN', 3),
(33, 18, 1, 0, 1, 0, 0, '2009-05-24 01:13:50', 'KEVIN', 3),
(34, 19, 1, 1, 0, 0, 0, '2009-05-24 01:14:07', 'KEVIN', 1),
(35, 20, 1, 0, 1, 0, 0, '2009-05-24 01:14:24', 'KEVIN', 3),
(36, 21, 1, 0, 1, 0, 0, '2009-05-24 01:14:41', 'KEVIN', 3),
(37, 23, 1, 0, 1, 0, 0, '2009-05-24 01:14:56', 'KEVIN', 3),
(38, 24, 1, 0, 1, 0, 1, '2009-05-24 01:15:17', 'KEVIN', 4),
(39, 25, 1, 0, 1, 0, 0, '2009-05-24 01:15:32', 'KEVIN', 3),
(40, 26, 1, 0, 1, 0, 0, '2009-05-24 01:15:48', 'KEVIN', 3),
(41, 27, 1, 0, 0, 0, 0, '2009-05-24 01:16:10', 'KEVIN', 2),
(42, 29, 1, 0, 1, 0, 0, '2009-05-24 01:16:30', 'KEVIN', 3),
(43, 30, 1, 0, 1, 0, 0, '2009-05-24 01:16:46', 'KEVIN', 3),
(44, 31, 1, 1, 1, 0, 0, '2009-05-24 01:17:05', 'KEVIN', 2),
(45, 34, 1, 0, 1, 0, 0, '2009-05-24 01:17:25', 'KEVIN', 3),
(46, 35, 1, 0, 1, 0, 0, '2009-05-24 01:17:48', 'KEVIN', 3),
(47, 36, 1, 0, 0, 0, 0, '2009-05-24 01:18:10', 'KEVIN', 2),
(48, 37, 1, 0, 1, 0, 0, '2009-05-24 01:18:25', 'KEVIN', 3),
(49, 38, 1, 0, 1, 0, 0, '2009-05-24 01:18:47', 'KEVIN', 3),
(50, 39, 0, 0, 1, 0, 0, '2009-05-24 01:19:10', 'KEVIN', 2),
(51, 41, 1, 0, 0, 1, 0, '2009-05-24 01:19:40', 'KEVIN', 3),
(52, 43, 1, 0, 1, 0, 0, '2009-05-24 01:19:59', 'KEVIN', 3),
(53, 48, 1, 1, 1, 0, 0, '2009-05-24 01:20:27', 'KEVIN', 2),
(54, 49, 0, 1, 1, 0, 0, '2009-05-24 01:20:46', 'KEVIN', 1),
(55, 116, 1, 0, 1, 0, 0, '2009-05-24 01:21:49', 'KEVIN', 3),
(56, 15, 0, 1, 0, 0, 0, '2010-10-22 16:26:06', 'KEVIN', 0),
(57, 5, 1, 1, 1, 0, 0, '2014-03-05 05:25:45', 'KEVIN', 2);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
