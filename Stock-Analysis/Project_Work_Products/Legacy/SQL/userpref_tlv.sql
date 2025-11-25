-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:26 PM
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
-- Table structure for table `userpref_tlv`
--
-- Creation: Oct 20, 2022 at 04:38 PM
-- Last update: Oct 20, 2022 at 04:38 PM
--

DROP TABLE IF EXISTS `userpref_tlv`;
CREATE TABLE IF NOT EXISTS `userpref_tlv` (
  `iduserpref_tlv` int(11) NOT NULL AUTO_INCREMENT,
  `pref` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `length` int(11) NOT NULL DEFAULT 0,
  `defaultvalue` varchar(32) NOT NULL DEFAULT '',
  `minvalue` varchar(32) NOT NULL DEFAULT '',
  `maxvalue` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`iduserpref_tlv`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `userpref_tlv`
--

INSERT IGNORE INTO `userpref_tlv` (`iduserpref_tlv`, `pref`, `type`, `length`, `defaultvalue`, `minvalue`, `maxvalue`) VALUES
(1, 'Display0hel', 'Boolean', 1, '0', '0', '1');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
