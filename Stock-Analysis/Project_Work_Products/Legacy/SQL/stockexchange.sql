-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:23 PM
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
-- Database: `back_finance`
--

-- --------------------------------------------------------

--
-- Table structure for table `stockexchange`
--
-- Creation: Oct 20, 2022 at 04:37 PM
--

DROP TABLE IF EXISTS `stockexchange`;
CREATE TABLE IF NOT EXISTS `stockexchange` (
  `idstockexchange` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Exchange` varchar(45) NOT NULL DEFAULT '',
  `YahooSymbol` varchar(45) NOT NULL DEFAULT '',
  `GlobeInvestorSymbol` varchar(45) NOT NULL DEFAULT '',
  `googlesymbol` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`idstockexchange`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `stockexchange`
--

INSERT IGNORE INTO `stockexchange` (`idstockexchange`, `Exchange`, `YahooSymbol`, `GlobeInvestorSymbol`, `googlesymbol`) VALUES
(1, 'Toronto Stock Exchange', 'TO', '', 'TSE'),
(2, 'Nasdaq', '', '', 'NASDAQ'),
(55, 'American Stock Exchange', '', '', 'NYSE'),
(56, 'TSX Venture Exchange', 'V', '', ''),
(57, 'Chicago Board of Trade', 'CBT', '', ''),
(58, 'Chicago Mercantile Exchange', 'CME', '', ''),
(59, 'New York Board of Trade', 'NYB', '', ''),
(60, 'New York Commodities Exchange', 'CMX', '', ''),
(61, 'New York Mercantile Exchange', 'NYM', '', ''),
(62, 'New York Stock Exchange', '', '', 'NYSE'),
(63, 'OTC Bulletin Board Market', 'OB', '', ''),
(64, 'Pink Sheets', 'PK', '', ''),
(65, 'Buenos Aires Stock Exchange', 'BA', '', ''),
(66, 'Vienna Stock Exchange', 'VI', '', ''),
(67, 'Australian Stock Exchange', 'AX', '', ''),
(68, 'Sao Paolo Stock Exchange', 'SA', '', ''),
(69, 'Shanghai Stock Exchange', 'SS', '', ''),
(70, 'Shenzhen Stock Exchange', 'SZ', '', ''),
(71, 'Copenhagen Stock Exchange', 'CO', '', ''),
(72, 'Paris Stock Exchange', 'PA', '', ''),
(73, 'Berlin Stock Exchange', 'BE', '', ''),
(74, 'Bremen Stock Exchange', 'BM', '', ''),
(75, 'Dusseldorf Stock Exchange', 'DU', '', ''),
(76, 'Frankfurt Stock Exchange', 'F', '', ''),
(77, 'Hamburg Stock Exchange', 'HM', '', ''),
(78, 'Hanover Stock Exchange', 'HA', '', ''),
(79, 'Munich Stock Exchange', 'MU', '', ''),
(80, 'Stuttgart Stock Exchange', 'SG', '', ''),
(81, 'XETRA Stock Exchange', 'DE', '', ''),
(82, 'Hong Kong Stock Exchange', 'HK', '', ''),
(83, 'Bombay Stock Exchange', 'BO', '', ''),
(84, 'National Stock Exchange of India', 'NS', '', ''),
(85, 'Jakarta Stock Exchange', 'JK', '', ''),
(86, 'Tel Aviv Stock Exchange', 'TA', '', ''),
(87, 'Milan Stock Exchange', 'MI', '', ''),
(88, 'Korea Stock Exchange', 'KS', '', ''),
(89, 'KOSDAQ', 'KQ', '', ''),
(90, 'Mexico Stock Exchange', 'MX', '', ''),
(91, 'Amsterdam Stock Exchange', 'AS', '', ''),
(92, 'New Zealand Stock Exchange', 'NZ', '', ''),
(93, 'Oslo Stock Exchange', 'OL', '', ''),
(94, 'Singapore Stock Exchange', 'SI', '', ''),
(95, 'Barcelona Stock Exchange', 'BC', '', ''),
(96, 'Bilbao Stock Exchange', 'BI', '', ''),
(97, 'Madrid Fixed Income Market', 'MF', '', ''),
(98, 'Madrid SE C.A.T.S.', 'MC', '', ''),
(99, 'Madrid Stock Exchange', 'MA', '', ''),
(100, 'Stockholm Stock Exchange', 'ST', '', ''),
(101, 'Swiss Exchange', 'SW', '', ''),
(102, 'Taiwan OTC Exchange', 'TWO', '', ''),
(103, 'Taiwan Stock Exchange', 'TW', '', ''),
(104, 'London Stock Exchange', 'L', '', '');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
