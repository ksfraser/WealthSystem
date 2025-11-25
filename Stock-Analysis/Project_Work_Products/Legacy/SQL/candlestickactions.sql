-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql.ksfraser.com:3306
-- Generation Time: Sep 04, 2025 at 08:13 PM
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
-- Table structure for table `candlestickactions`
--
-- Creation: May 10, 2023 at 12:59 AM
-- Last update: May 10, 2023 at 12:59 AM
--

DROP TABLE IF EXISTS `candlestickactions`;
CREATE TABLE IF NOT EXISTS `candlestickactions` (
  `candlestick_name` varchar(32) NOT NULL COMMENT 'The name of the candlestick',
  `candlestick_name11` varchar(11) NOT NULL COMMENT 'The name trimmed to 11 chars as that happened in some tables',
  `candlestick_detail` varchar(255) NOT NULL COMMENT 'Details on the meaning of the candlestick',
  `candlestick_action` varchar(32) NOT NULL COMMENT 'What action to take because of this candlestick',
  `candlestick_action_value` int(11) NOT NULL DEFAULT 50 COMMENT 'The value used for determining action strength',
  UNIQUE KEY `Name` (`candlestick_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `candlestickactions`
--

INSERT IGNORE INTO `candlestickactions` (`candlestick_name`, `candlestick_name11`, `candlestick_detail`, `candlestick_action`, `candlestick_action_value`) VALUES
('White_Doji', 'White_Doji', 'White_Doji pattern found.  The White_Doji pattern indicates the top of an uptrend.  This is a heads up to be looking for a trend reversal. Buy puts, buy call calendar spreads, short the stock or sell calls.', 'sellstock', 1),
('Doji', 'Doji', 'Doji pattern found.  The Doji pattern indicates uncertainty.  This is a heads up to be looking for a trend reversal.', 'Alert', 50),
('Yesterday_Doji', 'Yesterday_D', 'Yesterday_Doji pattern found.  The Yesterday_Doji pattern indicates uncertainty.  This is a heads up to be looking for a trend reversal.', 'Alert', 50),
('Double_Doji', 'Double_Doji', 'Double_Doji pattern found.  The Double_Doji pattern indicates uncertainty followed by a significant move. Buy Straddles.', 'Alert', 50),
('Perfect_Doji', 'Perfect_Doj', 'Perfect Doji pattern found.  The Perfect Doji pattern indicates uncertainty.  This is a heads up to be looking for a trend reversal.', 'Alert', 50),
('Heikin_Ashi', 'Heikin_Ashi', 'Heikin_Ashi pattern found.', 'Alert', 50),
('Black_Doji', 'Black_Doji', 'Black_Doji pattern found.  The Black_Doji pattern indicates the bottom of a downtrend.  This is a heads up to be looking for a trend reversal. Buy calls, buy put calendar spreads, buy the stock or sell expensive puts.', 'buystock', 99),
('Doji_Near', 'Doji_Near', 'Doji_Near pattern found.  The Doji_Near pattern indicates ...', 'Alert', 50),
('Bullish_Engulfing', 'Bullish_Eng', 'Bullish_Engulfing pattern found.  The Bullish_Engulfing pattern indicates the end of a downtrend.  This is a heads up to check that this stock was in a downtrend. Buy calls, buy put calendar spreads, buy the stock or sell expensive puts.', 'buystock', 99),
('Bearish_Engulfing', 'Bearish_Eng', 'Bearish_Engulfing pattern found.  The Bearish_Engulfing pattern indicates the end of an uptrend.  This is a heads up to check that this stock was in an uptrend that is ending.  Buy puts, buy call calendar spreads, short the stock or sell calls.', 'sellstock', 1),
('Hammer', 'Hammer', 'Hammer pattern found.  The Hammer pattern indicates the price is trying to gauge the depth. This would suggest the bottom of a decline.  Black bodies (close lower than open) indicates Bearish indicators, and a white body indicates Bullish.  The Hammer is ', 'buystock', 99),
('Hanging_Man', 'Hanging_Man', 'Hanging_Man pattern found.  The Hanging_Man pattern indicates the price is trying to gauge the top. This would suggest the top of a run-up.  Black bodies (close lower than open) indicates Bearish indicators, and a white body indicates Bullish.  The Hangin', 'sellstock', 1),
('Piercing_Line', 'Piercing_Li', 'Piercing_Line pattern found.  The Piercing_Line pattern is a bottom reversal. It is a two candle pattern at the end of a declining market. The first day real body is black. The second day is a long white body. The white day opens sharply lower, under the ', 'buystock', 99),
('Dark_Cloud', 'Dark_Cloud', 'Dark_Cloud pattern found.  The Dark Cloud Cover is a two-day bearish pattern found at the end of an upturn or at the top of a congested trading area. The first day of the pattern is a strong white real body. The second day\\\'s price opens higher than any o', 'sellstock', 1),
('Bullish_Harami', 'Bullish_Har', 'Bullish_Harami pattern found. Means \\\'Pregnant Woman\\\'. It is the opposite of an Engulfing Pattern. In this pattern the Black candlestick engulfs the next day\\\'s White candlestick, or vice versus for the colors. Indicates a change in sentiment. The locati', 'buystock', 99),
('Bearish_Harami', 'Bearish_Har', 'Bearish_Harami pattern found. Means \\\'Pregnant Woman\\\'. It is the opposite of an Engulfing Pattern. In this pattern the Black candlestick engulfs the next day\\\'s White candlestick, or vice versus for the colors. Indicates a change in sentiment. The locati', 'sellstock', 1),
('Morning_Star', 'Morning_Sta', 'Morning_Star pattern found. Black candlestick, followed by a white lower than the black, followed by a white higher than the 1st white.  The Morning Star is a bottom reversal signal. Like the morning star, the planet Mercury, it foretells the sunrise, or ', 'buystock', 99),
('Evening_Star', 'Evening_Sta', 'Evening_Star pattern found. A white candlestick, a higher white, followed by a black.  The Evening Star is the exact opposite of the morning star. The evening star, the planet Venus, occurs just before the darkness sets in. The evening star is found at th', 'sellstock', 1),
('Bullish_Kicker', 'Bullish_Kic', 'Bullish_Kicker pattern found. The first day\\\'s open and the second day\\\'s open are the same BUT the price movement is in opposite directions.  Pattern Psychology: The Kicker Signal demonstrates a dramatic change in investor sentiment. The longer the candl', 'buystock', 99),
('Bearish_Kicker', 'Bearish_Kic', 'Bearish_Kicker pattern found. The first day\\\'s open and the second day\\\'s open are the same BUT the price movement is in opposite directions.  Pattern Psychology: The Kicker Signal demonstrates a dramatic change in investor sentiment. The longer the candl', 'sellstock', 1),
('Shooting_Star', 'Shooting_St', 'Shooting_Star pattern found.  One candle pattern appearing in an uptrend. The shadow (or tail) should be at least two times the length of the body. The color of the body is not important, although a black body has slightly more Bearish indications.  Patte', 'sellstock', 1),
('Inverted_Hammer', 'Inverted_Ha', 'Inverted_Hammer pattern found.   One candle pattern appearing in an uptrend. The shadow (or tail) should be at least two times the length of the body. The color of the body is not important, although a black body has slightly more Bearish indications.  Pa', 'buystock', 99);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
