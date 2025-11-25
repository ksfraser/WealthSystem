-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:08 PM
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
-- Table structure for table `alerts`
--
-- Creation: May 09, 2023 at 05:59 AM
--

DROP TABLE IF EXISTS `alerts`;
CREATE TABLE IF NOT EXISTS `alerts` (
  `idalerts` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `alertdescription` varchar(45) NOT NULL DEFAULT '',
  `alertfunctionname` varchar(45) NOT NULL DEFAULT '',
  `expirydate` date NOT NULL DEFAULT '2010-01-01' COMMENT 'Expiry Date of Alert',
  PRIMARY KEY (`idalerts`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `alerts`
--

INSERT IGNORE INTO `alerts` (`idalerts`, `alertdescription`, `alertfunctionname`, `expirydate`) VALUES
(1, 'test', 'testfcn', '2020-01-01'),
(2, 'Current Price Greater Than (criteria 1)', 'currentpricegreaterthan', '2020-01-01'),
(3, 'Current Price Less Than (criteria 1)', 'currentpricelessthan', '2020-01-01'),
(4, 'Average Volume Greater Than (criteria 1)', 'averagevolumegreaterthan', '2020-01-01'),
(5, 'Average Volume Less Than (criteria 1)', 'averagevolumelessthan', '2020-01-01'),
(6, 'Year High greater than (criteria 1)', 'yearhighgreaterthan', '2020-01-01'),
(7, 'Year High less than (criteria 1)', 'yearhighlessthan', '2020-01-01'),
(8, 'Year Low less than (criteria 1)', 'yearlowlessthan', '2020-01-01'),
(9, 'Year Low greater than (criteria 1)', 'yearlowgreaterthan', '2020-01-01'),
(10, 'Daily High less than (criteria 1)', 'highlessthan', '2020-01-01'),
(11, 'Daily High greater than (criteria 1)', 'highgreaterthan', '2020-01-01'),
(12, 'Daily Low less than (criteria 1)', 'lowlessthan', '2020-01-01'),
(13, 'Daily Low greater than (criteria 1)', 'lowgreaterthan', '2020-01-01'),
(14, 'Current Price Between (low) (high)', 'currentpricebetween', '2020-01-01'),
(15, 'Stock not updated since (days)(criteria 1)', 'stocknotupdatedsince', '2020-01-01'),
(16, 'Retractment Alert (Days)(Percentage)', 'alertretractment', '2020-01-01'),
(17, 'Advancement Alert (Days)(Percentage)', 'alertadvancement', '2020-01-01');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
