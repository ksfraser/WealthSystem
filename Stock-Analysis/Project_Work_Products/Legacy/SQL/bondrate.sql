-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:09 PM
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
-- Database: `stock_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `bondrate`
--
-- Creation: May 09, 2023 at 05:59 AM
--

DROP TABLE IF EXISTS `bondrate`;
CREATE TABLE IF NOT EXISTS `bondrate` (
  `idbondrate` int(11) NOT NULL AUTO_INCREMENT,
  `calendaryear` date NOT NULL DEFAULT '0000-00-00',
  `bondrate` float NOT NULL DEFAULT 3,
  `updateddate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last updated',
  PRIMARY KEY (`idbondrate`),
  UNIQUE KEY `calendaryear` (`calendaryear`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `bondrate`
--

INSERT IGNORE INTO `bondrate` (`idbondrate`, `calendaryear`, `bondrate`, `updateddate`) VALUES
(1, '2008-01-01', 0, '0000-00-00 00:00:00'),
(2, '2009-01-01', 0, '0000-00-00 00:00:00'),
(3, '2010-01-01', 1.36, '0000-00-00 00:00:00'),
(4, '2011-01-01', 1.68, '0000-00-00 00:00:00'),
(5, '2012-01-01', 1.53, '2011-06-10 23:05:12'),
(6, '2013-01-01', 1.53, '2011-06-10 23:05:12'),
(7, '2014-01-01', 2.07, '2011-06-10 23:05:12'),
(8, '2015-01-01', 2.07, '2011-06-10 23:05:12'),
(9, '2016-01-01', 2.07, '2011-06-10 23:05:12'),
(10, '2017-01-01', 2.74, '2011-06-10 23:05:12'),
(11, '2018-01-01', 2.74, '2011-06-10 23:05:12'),
(12, '2019-01-01', 2.74, '2011-06-10 23:05:12'),
(13, '2020-01-01', 2.74, '2011-06-10 23:05:12'),
(14, '2038-01-01', 4.03, '0000-00-00 00:00:00'),
(15, '2039-01-01', 4.07, '0000-00-00 00:00:00'),
(16, '2040-01-01', 3.53, '0000-00-00 00:00:00'),
(17, '2021-01-01', 2.74, '2011-06-10 23:05:13'),
(18, '2041-01-01', 3.45, '2011-06-10 23:05:13'),
(19, '2022-08-13', 2.23, '2012-08-14 07:57:02'),
(20, '2017-08-13', 1.6, '2012-08-14 07:58:07'),
(21, '2018-08-13', 1.6, '2012-08-14 07:58:51'),
(22, '2019-08-13', 1.6, '2012-08-14 07:58:58'),
(23, '2020-08-13', 1.6, '2012-08-14 07:59:05'),
(24, '2021-08-13', 1.6, '2012-08-14 07:59:14');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
