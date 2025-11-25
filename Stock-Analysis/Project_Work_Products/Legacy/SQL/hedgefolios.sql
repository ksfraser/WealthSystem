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
-- Table structure for table `hedgefolios`
--
-- Creation: May 09, 2023 at 06:00 AM
-- Last update: May 09, 2023 at 06:00 AM
--

DROP TABLE IF EXISTS `hedgefolios`;
CREATE TABLE IF NOT EXISTS `hedgefolios` (
  `idhedgefolios` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The index of this table',
  `idstockinfo` int(11) NOT NULL DEFAULT 0 COMMENT 'The Stock being evaluated',
  `createddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Time that this record was created',
  `createduser` int(11) NOT NULL DEFAULT 0 COMMENT 'Record created by user',
  `updateddate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'The time this record was updated',
  `updateduser` int(11) NOT NULL DEFAULT 0 COMMENT 'Record updated by user',
  `supportlevel` float NOT NULL DEFAULT 0 COMMENT 'The technical indicator Support Level',
  `resistancelevel` float NOT NULL DEFAULT 0 COMMENT 'Technical indicator Resistance Level',
  `Volume` float NOT NULL DEFAULT 0 COMMENT 'Average trading volume',
  `bollingerbands` varchar(11) NOT NULL DEFAULT '' COMMENT 'The Bollinger Bands levels',
  `elliotwave` varchar(11) NOT NULL DEFAULT '' COMMENT 'The Elliot Wave values',
  `fibonacci` varchar(11) NOT NULL DEFAULT '' COMMENT 'Fibonacci indicators',
  `sentiment` varchar(11) NOT NULL DEFAULT '' COMMENT 'Sentiment Indicators',
  `trailingpe` float NOT NULL DEFAULT 0 COMMENT 'Trailing Price to Earnings',
  `forwardpe` float NOT NULL DEFAULT 0 COMMENT 'Forward Price to Earnings',
  `pricetobook` float NOT NULL DEFAULT 0 COMMENT 'Price to Book',
  `pricetosale` float NOT NULL DEFAULT 0 COMMENT 'Price to Sale',
  `pricetocash` float NOT NULL DEFAULT 0 COMMENT 'Price to Cash Flow',
  `expectedgrowth` float NOT NULL DEFAULT 0 COMMENT '5 years expected earnings growth',
  `chartpattern` varchar(11) NOT NULL DEFAULT '' COMMENT 'Chart Patterns',
  `candlesticks` varchar(11) NOT NULL DEFAULT '' COMMENT 'Candlesticks',
  PRIMARY KEY (`idhedgefolios`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
