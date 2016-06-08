-- phpMyAdmin SQL Dump
-- version 4.0.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 23, 2016 at 17:12 PM
-- Server version: 5.1.71
-- PHP Version: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Table structure for table `entities`
--

DROP TABLE IF EXISTS `entities`;
CREATE TABLE `entities` (
  `stat_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` int(11) unsigned DEFAULT NULL,
  KEY `stat_id` (`stat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extensions`
--

DROP TABLE IF EXISTS `extensions`;
CREATE TABLE `extensions` (
  `stat_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `version` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  KEY `stat_id` (`stat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stats`
--

DROP TABLE IF EXISTS `stats`;
CREATE TABLE `stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uf` text COLLATE utf8_unicode_ci,
  `lang` text COLLATE utf8_unicode_ci,
  `version` text COLLATE utf8_unicode_ci,
  `ufv` text COLLATE utf8_unicode_ci,
  `MySQL` text COLLATE utf8_unicode_ci,
  `PHP` text COLLATE utf8_unicode_ci,
  `PPTypes` text COLLATE utf8_unicode_ci,
  `hash` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `geoip_isoCode` char(2) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `domain_isoCode` char(2) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `co` int(10) unsigned DEFAULT NULL,
  `communityMessagesUrl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `hash` (`hash`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;