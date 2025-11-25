-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:16 PM
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
-- Database: `stock_market_motleyfool`
--

-- --------------------------------------------------------

--
-- Table structure for table `motleyfool`
--
-- Creation: May 09, 2023 at 07:23 AM
-- Last update: May 09, 2023 at 07:23 AM
--

DROP TABLE IF EXISTS `motleyfool`;
CREATE TABLE IF NOT EXISTS `motleyfool` (
  `idmotleyfool` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `valuationdate` date NOT NULL COMMENT 'The date this stock is evaluated',
  `stocksymbol` varchar(6) NOT NULL COMMENT 'The Stock Symbol',
  `businessmodel` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the business model a profit maker',
  `reasonablevaluation` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the P/E valuation reasonable.  Is there a chance the company will make the kind of returns suggested by the PE ratio',
  `focusoncore` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the company focused on its core business or is it trying to expand non core services',
  `doubledigitrisingsales` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the sales growth at 10 percent or better',
  `risingfreecashflow` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the free cash flow rising',
  `risingbookvalue` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the book value rising',
  `improvingmargin` int(1) NOT NULL DEFAULT 0 COMMENT 'Is their margin improving',
  `risingreturnonequity` int(1) NOT NULL DEFAULT 0 COMMENT 'Is the ROE rising',
  `insiderownership` int(1) NOT NULL DEFAULT 0 COMMENT 'Does the executives own a significant number of shares',
  `regulardividends` int(1) NOT NULL DEFAULT 0 COMMENT 'Does the company pay dividends consistently',
  `evaluationuser` varchar(32) NOT NULL COMMENT 'The user that provided this valuation',
  PRIMARY KEY (`idmotleyfool`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
