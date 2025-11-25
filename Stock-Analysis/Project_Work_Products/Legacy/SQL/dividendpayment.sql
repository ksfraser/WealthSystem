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
-- Table structure for table `dividendpayment`
--
-- Creation: May 09, 2023 at 05:59 AM
--

DROP TABLE IF EXISTS `dividendpayment`;
CREATE TABLE IF NOT EXISTS `dividendpayment` (
  `iddividendpayment` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `stocksymbol` varchar(45) NOT NULL DEFAULT '',
  `dividendpershare` float NOT NULL DEFAULT 0 COMMENT 'Dividend Per Share',
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Last Updated',
  `idstockexchange` int(11) NOT NULL COMMENT 'The Exchange the stock is on',
  `exdividenddate` date NOT NULL COMMENT 'The date the owner of record for the stock is used to pay the dividend',
  `idstockinfo` int(11) NOT NULL COMMENT 'The Stockinfo Index',
  PRIMARY KEY (`iddividendpayment`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
