-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:24 PM
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
-- Table structure for table `teneteval`
--
-- Creation: Oct 20, 2022 at 04:38 PM
-- Last update: Oct 20, 2022 at 04:38 PM
--

DROP TABLE IF EXISTS `teneteval`;
CREATE TABLE IF NOT EXISTS `teneteval` (
  `idteneteval` int(11) NOT NULL DEFAULT 0,
  `idstockinfo` int(11) NOT NULL DEFAULT 0,
  `ownerearnings` float NOT NULL DEFAULT 0,
  `discountrate` int(11) NOT NULL DEFAULT 0,
  `growthrate` int(11) NOT NULL DEFAULT 0,
  `value` int(11) NOT NULL DEFAULT 0,
  `simple` tinyint(1) NOT NULL DEFAULT 0,
  `managementowners` tinyint(1) NOT NULL DEFAULT 0,
  `benefitreinvest` tinyint(1) NOT NULL DEFAULT 0,
  `expandbypurchase` tinyint(1) NOT NULL DEFAULT 0,
  `regulated` tinyint(1) NOT NULL DEFAULT 0,
  `neededproduct` tinyint(1) NOT NULL DEFAULT 0,
  `closesubstitute` tinyint(1) NOT NULL DEFAULT 0,
  `mimiccompetition` tinyint(1) NOT NULL DEFAULT 0,
  `hyperactivity` tinyint(1) NOT NULL DEFAULT 0,
  `kellyoptimization` float NOT NULL DEFAULT 0,
  `riskprobability` float NOT NULL DEFAULT 0,
  `cosnsistanthistory` tinyint(1) NOT NULL DEFAULT 0,
  `communicatemorethangaap` tinyint(1) NOT NULL DEFAULT 0,
  `publicconfession` tinyint(1) NOT NULL DEFAULT 0,
  `retainearningsmv` tinyint(1) NOT NULL DEFAULT 0,
  `debtratio` float NOT NULL DEFAULT 0,
  `acceptabledebt` float NOT NULL DEFAULT 0,
  `roe` tinyint(1) NOT NULL DEFAULT 0,
  `lowcost` tinyint(1) NOT NULL DEFAULT 0,
  `frfeqreorg` tinyint(1) NOT NULL DEFAULT 0,
  `netincome` float NOT NULL DEFAULT 0,
  `depreciation` float NOT NULL DEFAULT 0,
  `depletion` float NOT NULL DEFAULT 0,
  `amortization` float NOT NULL DEFAULT 0,
  `capitalexpenses` float NOT NULL DEFAULT 0,
  `workingcapital` float NOT NULL DEFAULT 0,
  `marketcap` float NOT NULL DEFAULT 0,
  `marginsafety` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`idteneteval`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
