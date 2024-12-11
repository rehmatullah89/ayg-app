/*
SQLyog Ultimate v12.09 (64 bit)
MySQL - 5.7.33-log : Database - ayg_dev
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`ayg_dev` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `ayg_dev`;

/*Table structure for table `delivery_action_logs` */

DROP TABLE IF EXISTS `delivery_action_logs`;

CREATE TABLE `delivery_action_logs` (
  `objectId` int(11) NOT NULL AUTO_INCREMENT,
  `airportIataCode` varchar(10) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `timeStamp` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`objectId`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
