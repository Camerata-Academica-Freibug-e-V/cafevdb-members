/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-12.3.3-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: cafevdb
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `ChangeLog`
--

DROP TABLE IF EXISTS `ChangeLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ChangeLog` (
  `updated` datetime(6) NOT NULL,
  `user` varchar(255) DEFAULT NULL,
  `host` varchar(255) DEFAULT NULL,
  `operation` varchar(255) DEFAULT NULL,
  `tab` varchar(255) DEFAULT NULL,
  `rowkey` varchar(255) DEFAULT NULL,
  `col` varchar(255) DEFAULT NULL,
  `oldval` blob DEFAULT NULL,
  `newval` blob DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ChangeLog`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ChangeLog` WRITE;
/*!40000 ALTER TABLE `ChangeLog` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChangeLog` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `CompositePayments`
--

DROP TABLE IF EXISTS `CompositePayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompositePayments` (
  `amount` decimal(7,2) NOT NULL DEFAULT 0.00,
  `date_of_receipt` datetime(6) DEFAULT NULL,
  `subject` varchar(1024) NOT NULL,
  `notification_message_id` varchar(512) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `sepa_transaction_id` int(11) DEFAULT NULL,
  `musician_id` int(11) NOT NULL,
  `bank_account_sequence` int(11) DEFAULT NULL,
  `debit_mandate_sequence` int(11) DEFAULT NULL,
  `pre_notification_message_id` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `supporting_document_id` int(11) DEFAULT NULL,
  `balance_documents_folder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_65D9920C2423759C` (`supporting_document_id`),
  UNIQUE KEY `UNIQ_65D9920C9B6CD002` (`pre_notification_message_id`),
  KEY `IDX_65D9920CD5560045` (`sepa_transaction_id`),
  KEY `IDX_65D9920C9523AA8A2301E184` (`musician_id`,`bank_account_sequence`),
  KEY `IDX_65D9920C9523AA8A544C02F9` (`musician_id`,`debit_mandate_sequence`),
  KEY `IDX_65D9920C166D1F9C` (`project_id`),
  KEY `IDX_65D9920C9523AA8A` (`musician_id`),
  KEY `IDX_65D9920C166D1F9C9523AA8A` (`project_id`,`musician_id`),
  KEY `IDX_65D9920C8A034ED2` (`balance_documents_folder_id`),
  CONSTRAINT `FK_65D9920C166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_65D9920C166D1F9C9523AA8A` FOREIGN KEY (`project_id`, `musician_id`) REFERENCES `ProjectParticipants` (`project_id`, `musician_id`),
  CONSTRAINT `FK_65D9920C2423759C` FOREIGN KEY (`supporting_document_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_65D9920C8A034ED2` FOREIGN KEY (`balance_documents_folder_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_65D9920C9523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_65D9920C9523AA8A2301E184` FOREIGN KEY (`musician_id`, `bank_account_sequence`) REFERENCES `SepaBankAccounts` (`musician_id`, `sequence`),
  CONSTRAINT `FK_65D9920C9523AA8A544C02F9` FOREIGN KEY (`musician_id`, `debit_mandate_sequence`) REFERENCES `SepaDebitMandates` (`musician_id`, `sequence`),
  CONSTRAINT `FK_65D9920C9B6CD002` FOREIGN KEY (`pre_notification_message_id`) REFERENCES `SentEmails` (`message_id`),
  CONSTRAINT `FK_65D9920CD5560045` FOREIGN KEY (`sepa_transaction_id`) REFERENCES `SepaBulkTransactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompositePayments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `CompositePayments` WRITE;
/*!40000 ALTER TABLE `CompositePayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompositePayments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `DatabaseStorageDirEntries`
--

DROP TABLE IF EXISTS `DatabaseStorageDirEntries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `DatabaseStorageDirEntries` (
  `name` varchar(256) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `parent_id` int(11) DEFAULT NULL,
  `type` enum('generic','folder','file') NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_E123333D727ACA705E237E06` (`parent_id`,`name`),
  KEY `IDX_E123333D727ACA70` (`parent_id`),
  KEY `IDX_E123333D93CB796C` (`file_id`),
  CONSTRAINT `FK_E123333D727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_E123333D93CB796C` FOREIGN KEY (`file_id`) REFERENCES `Files` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DatabaseStorageDirEntries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `DatabaseStorageDirEntries` WRITE;
/*!40000 ALTER TABLE `DatabaseStorageDirEntries` DISABLE KEYS */;
/*!40000 ALTER TABLE `DatabaseStorageDirEntries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `DatabaseStorages`
--

DROP TABLE IF EXISTS `DatabaseStorages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `DatabaseStorages` (
  `storage_id` varchar(512) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `root_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3594ED235CC5DB90` (`storage_id`),
  UNIQUE KEY `UNIQ_3594ED2379066886` (`root_id`),
  CONSTRAINT `FK_3594ED2379066886` FOREIGN KEY (`root_id`) REFERENCES `DatabaseStorageDirEntries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DatabaseStorages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `DatabaseStorages` WRITE;
/*!40000 ALTER TABLE `DatabaseStorages` DISABLE KEYS */;
/*!40000 ALTER TABLE `DatabaseStorages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `DoctrineMigrationsVersions`
--

DROP TABLE IF EXISTS `DoctrineMigrationsVersions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `DoctrineMigrationsVersions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime(6) DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DoctrineMigrationsVersions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `DoctrineMigrationsVersions` WRITE;
/*!40000 ALTER TABLE `DoctrineMigrationsVersions` DISABLE KEYS */;
INSERT INTO `DoctrineMigrationsVersions` VALUES
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version19700101000001','2026-10-25 17:29:51.119935',802),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version19700101000002','2026-10-25 17:29:51.988843',292),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version19700101000003','2026-10-25 17:29:52.309066',15),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260108084800','2026-10-25 17:29:52.350950',90),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260108115432','2026-10-25 17:29:52.469341',5),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260130130553','2026-10-25 17:29:52.500709',13),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260131090857','2026-10-25 17:29:52.539498',28),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260206193722','2026-10-25 17:29:52.594158',7),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260207000624','2026-10-25 17:29:52.628261',3),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260819094146','2026-10-25 17:29:52.659129',12),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260819094422','2026-10-25 17:29:52.698764',10),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20260819105948','2026-10-25 17:29:52.737178',30),
('OCA\\CAFEVDB\\Maintenance\\Migrations\\Version20261009150233','2026-10-25 17:29:52.795009',8);
/*!40000 ALTER TABLE `DoctrineMigrationsVersions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `DonationReceipts`
--

DROP TABLE IF EXISTS `DonationReceipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `DonationReceipts` (
  `mailing_date` datetime(6) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `donation_id` int(11) NOT NULL,
  `tax_exemption_notice_id` int(11) DEFAULT NULL,
  `supporting_document_id` int(11) DEFAULT NULL,
  `notification_message_id` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_AD46E7444DC1279C` (`donation_id`),
  UNIQUE KEY `UNIQ_AD46E7442423759C` (`supporting_document_id`),
  UNIQUE KEY `UNIQ_AD46E744A808B60B` (`notification_message_id`),
  UNIQUE KEY `donation_receipt_unique` (`donation_id`,`deleted`),
  KEY `IDX_AD46E74434E7630B` (`tax_exemption_notice_id`),
  CONSTRAINT `FK_AD46E7442423759C` FOREIGN KEY (`supporting_document_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_AD46E74434E7630B` FOREIGN KEY (`tax_exemption_notice_id`) REFERENCES `TaxExemptionNotices` (`id`),
  CONSTRAINT `FK_AD46E7444DC1279C` FOREIGN KEY (`donation_id`) REFERENCES `CompositePayments` (`id`),
  CONSTRAINT `FK_AD46E744A808B60B` FOREIGN KEY (`notification_message_id`) REFERENCES `SentEmails` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DonationReceipts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `DonationReceipts` WRITE;
/*!40000 ALTER TABLE `DonationReceipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `DonationReceipts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `EmailAttachments`
--

DROP TABLE IF EXISTS `EmailAttachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `EmailAttachments` (
  `file_name` varchar(512) NOT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `draft_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`file_name`),
  KEY `IDX_199F0CDBE2F3C5D1` (`draft_id`),
  CONSTRAINT `FK_199F0CDBE2F3C5D1` FOREIGN KEY (`draft_id`) REFERENCES `EmailDrafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EmailAttachments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `EmailAttachments` WRITE;
/*!40000 ALTER TABLE `EmailAttachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `EmailAttachments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `EmailDrafts`
--

DROP TABLE IF EXISTS `EmailDrafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `EmailDrafts` (
  `subject` varchar(256) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Message Data Without Attachments' CHECK (json_valid(`data`)),
  `auto_generated` tinyint(4) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EmailDrafts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `EmailDrafts` WRITE;
/*!40000 ALTER TABLE `EmailDrafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `EmailDrafts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `EmailTemplates`
--

DROP TABLE IF EXISTS `EmailTemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `EmailTemplates` (
  `tag` varchar(128) NOT NULL,
  `subject` varchar(1024) NOT NULL,
  `contents` longtext DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_51BDDDC389B783` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EmailTemplates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `EmailTemplates` WRITE;
/*!40000 ALTER TABLE `EmailTemplates` DISABLE KEYS */;
/*!40000 ALTER TABLE `EmailTemplates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `EncryptedFileOwners`
--

DROP TABLE IF EXISTS `EncryptedFileOwners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `EncryptedFileOwners` (
  `musician_id` int(11) NOT NULL,
  `encrypted_file_id` int(11) NOT NULL,
  PRIMARY KEY (`musician_id`,`encrypted_file_id`),
  KEY `IDX_5697DE239523AA8A` (`musician_id`),
  KEY `IDX_5697DE23EC15E76C` (`encrypted_file_id`),
  CONSTRAINT `FK_5697DE239523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_5697DE23EC15E76C` FOREIGN KEY (`encrypted_file_id`) REFERENCES `Files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EncryptedFileOwners`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `EncryptedFileOwners` WRITE;
/*!40000 ALTER TABLE `EncryptedFileOwners` DISABLE KEYS */;
INSERT INTO `EncryptedFileOwners` VALUES
(1,1);
/*!40000 ALTER TABLE `EncryptedFileOwners` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ExtLogEntries`
--

DROP TABLE IF EXISTS `ExtLogEntries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ExtLogEntries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(8) NOT NULL,
  `logged_at` datetime(6) NOT NULL,
  `object_class` varchar(191) NOT NULL,
  `version` int(11) NOT NULL,
  `data` longtext DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `remote_address` varchar(45) DEFAULT NULL,
  `object_id` varchar(573) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_class_lookup_idx` (`object_class`),
  KEY `log_date_lookup_idx` (`logged_at`),
  KEY `log_user_lookup_idx` (`username`),
  KEY `log_version_lookup_idx` (`object_id`,`object_class`,`version`),
  KEY `log_action_lookup_idx` (`action`,`object_class`),
  KEY `log_action_class_lookup_idx` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ExtLogEntries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ExtLogEntries` WRITE;
/*!40000 ALTER TABLE `ExtLogEntries` DISABLE KEYS */;
INSERT INTO `ExtLogEntries` VALUES
(1,'create','2026-10-25 17:29:52.124668','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:7:\"strings\";s:7:\"deleted\";N;}','john.doe','','1'),
(2,'create','2026-10-25 17:29:52.125316','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:6:\"string\";s:7:\"deleted\";N;}','john.doe','','2'),
(3,'create','2026-10-25 17:29:52.125726','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:7:\"plucked\";s:7:\"deleted\";N;}','john.doe','','3'),
(4,'create','2026-10-25 17:29:52.126111','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:4:\"wind\";s:7:\"deleted\";N;}','john.doe','','4'),
(5,'create','2026-10-25 17:29:52.126501','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:4:\"wood\";s:7:\"deleted\";N;}','john.doe','','5'),
(6,'create','2026-10-25 17:29:52.126875','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:5:\"brass\";s:7:\"deleted\";N;}','john.doe','','6'),
(7,'create','2026-10-25 17:29:52.127269','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:10:\"percussion\";s:7:\"deleted\";N;}','john.doe','','7'),
(8,'create','2026-10-25 17:29:52.127650','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:8:\"keyboard\";s:7:\"deleted\";N;}','john.doe','','8'),
(9,'create','2026-10-25 17:29:52.128023','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:13:\"miscellaneous\";s:7:\"deleted\";N;}','john.doe','','9'),
(10,'create','2026-10-25 17:29:52.128417','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',1,'a:2:{s:6:\"family\";s:17:\"not an instrument\";s:7:\"deleted\";N;}','john.doe','','10'),
(11,'create','2026-10-25 17:29:52.128846','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:6:\"violin\";s:9:\"sortOrder\";i:1;s:7:\"deleted\";N;}','john.doe','','1'),
(12,'create','2026-10-25 17:29:52.129229','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:5:\"viola\";s:9:\"sortOrder\";i:2;s:7:\"deleted\";N;}','john.doe','','2'),
(13,'create','2026-10-25 17:29:52.129642','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:11:\"violoncello\";s:9:\"sortOrder\";i:3;s:7:\"deleted\";N;}','john.doe','','3'),
(14,'create','2026-10-25 17:29:52.130019','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:11:\"double bass\";s:9:\"sortOrder\";i:4;s:7:\"deleted\";N;}','john.doe','','4'),
(15,'create','2026-10-25 17:29:52.130409','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:5:\"flute\";s:9:\"sortOrder\";i:10;s:7:\"deleted\";N;}','john.doe','','5'),
(16,'create','2026-10-25 17:29:52.130779','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:7:\"piccolo\";s:9:\"sortOrder\";i:11;s:7:\"deleted\";N;}','john.doe','','6'),
(17,'create','2026-10-25 17:29:52.131156','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:4:\"oboe\";s:9:\"sortOrder\";i:20;s:7:\"deleted\";N;}','john.doe','','7'),
(18,'create','2026-10-25 17:29:52.131675','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:12:\"English horn\";s:9:\"sortOrder\";i:25;s:7:\"deleted\";N;}','john.doe','','8'),
(19,'create','2026-10-25 17:29:52.132071','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:8:\"clarinet\";s:9:\"sortOrder\";i:30;s:7:\"deleted\";N;}','john.doe','','9'),
(20,'create','2026-10-25 17:29:52.132472','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:13:\"bass clarinet\";s:9:\"sortOrder\";i:35;s:7:\"deleted\";N;}','john.doe','','10'),
(21,'create','2026-10-25 17:29:52.132854','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:7:\"bassoon\";s:9:\"sortOrder\";i:40;s:7:\"deleted\";N;}','john.doe','','11'),
(22,'create','2026-10-25 17:29:52.133223','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:12:\"natural horn\";s:9:\"sortOrder\";i:50;s:7:\"deleted\";N;}','john.doe','','12'),
(23,'create','2026-10-25 17:29:52.133642','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:7:\"trumpet\";s:9:\"sortOrder\";i:60;s:7:\"deleted\";N;}','john.doe','','13'),
(24,'create','2026-10-25 17:29:52.134032','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:8:\"trombone\";s:9:\"sortOrder\";i:70;s:7:\"deleted\";N;}','john.doe','','14'),
(25,'create','2026-10-25 17:29:52.134435','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:13:\"bass trombone\";s:9:\"sortOrder\";i:71;s:7:\"deleted\";N;}','john.doe','','15'),
(26,'create','2026-10-25 17:29:52.134819','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:4:\"tuba\";s:9:\"sortOrder\";i:80;s:7:\"deleted\";N;}','john.doe','','16'),
(27,'create','2026-10-25 17:29:52.135215','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:4:\"harp\";s:9:\"sortOrder\";i:90;s:7:\"deleted\";N;}','john.doe','','17'),
(28,'create','2026-10-25 17:29:52.135591','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:6:\"guitar\";s:9:\"sortOrder\";i:95;s:7:\"deleted\";N;}','john.doe','','18'),
(29,'create','2026-10-25 17:29:52.135958','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:7:\"timpani\";s:9:\"sortOrder\";i:100;s:7:\"deleted\";N;}','john.doe','','19'),
(30,'create','2026-10-25 17:29:52.136333','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:4:\"drum\";s:9:\"sortOrder\";i:105;s:7:\"deleted\";N;}','john.doe','','20'),
(31,'create','2026-10-25 17:29:52.136699','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:9:\"bass drum\";s:9:\"sortOrder\";i:110;s:7:\"deleted\";N;}','john.doe','','21'),
(32,'create','2026-10-25 17:29:52.137065','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:7:\"cymbals\";s:9:\"sortOrder\";i:201;s:7:\"deleted\";N;}','john.doe','','22'),
(33,'create','2026-10-25 17:29:52.137478','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:12:\"glockenspiel\";s:9:\"sortOrder\";i:203;s:7:\"deleted\";N;}','john.doe','','23'),
(34,'create','2026-10-25 17:29:52.137849','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:9:\"xylophone\";s:9:\"sortOrder\";i:400;s:7:\"deleted\";N;}','john.doe','','24'),
(35,'create','2026-10-25 17:29:52.138232','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:5:\"piano\";s:9:\"sortOrder\";i:5000;s:7:\"deleted\";N;}','john.doe','','25'),
(36,'create','2026-10-25 17:29:52.138609','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:5:\"organ\";s:9:\"sortOrder\";i:5010;s:7:\"deleted\";N;}','john.doe','','26'),
(37,'create','2026-10-25 17:29:52.138979','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:11:\"harpsichord\";s:9:\"sortOrder\";i:5015;s:7:\"deleted\";N;}','john.doe','','27'),
(38,'create','2026-10-25 17:29:52.139382','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:7:\"celesta\";s:9:\"sortOrder\";i:5020;s:7:\"deleted\";N;}','john.doe','','28'),
(39,'create','2026-10-25 17:29:52.139763','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:9:\"bandoneon\";s:9:\"sortOrder\";i:5025;s:7:\"deleted\";N;}','john.doe','','29'),
(40,'create','2026-10-25 17:29:52.140132','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:9:\"accordion\";s:9:\"sortOrder\";i:5030;s:7:\"deleted\";N;}','john.doe','','30'),
(41,'create','2026-10-25 17:29:52.140523','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:9:\"associate\";s:9:\"sortOrder\";i:2147483647;s:7:\"deleted\";N;}','john.doe','','31'),
(42,'create','2026-10-25 17:29:52.140894','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',1,'a:3:{s:4:\"name\";s:16:\"business partner\";s:9:\"sortOrder\";i:2147483647;s:7:\"deleted\";N;}','john.doe','','32'),
(43,'create','2026-10-25 17:29:53.035442','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Project',1,'a:9:{s:4:\"year\";i:2099;s:4:\"name\";s:15:\"TestProject2099\";s:4:\"type\";s:9:\"temporary\";s:13:\"mailingListId\";N;s:21:\"registrationStartDate\";N;s:20:\"registrationDeadline\";N;s:7:\"deleted\";N;s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.816239\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.814794\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}','john.doe','','1'),
(44,'create','2026-10-25 17:29:53.035957','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Musician',1,'a:30:{s:7:\"surName\";s:12:\"Musterperson\";s:9:\"firstName\";s:3:\"Max\";s:8:\"nickName\";N;s:11:\"displayName\";N;s:6:\"gender\";N;s:10:\"userIdSlug\";s:16:\"lieschen.mueller\";s:14:\"userPassphrase\";N;s:4:\"city\";s:8:\"Nirgends\";s:6:\"street\";s:15:\"Unauffindbarweg\";s:12:\"streetNumber\";s:2:\"42\";s:17:\"addressSupplement\";s:8:\"Igloo 13\";s:5:\"poBox\";N;s:7:\"country\";s:2:\"AQ\";s:10:\"postalCode\";s:3:\"Z-7\";s:8:\"language\";N;s:11:\"mobilePhone\";s:4:\"0815\";s:14:\"fixedLinePhone\";s:4:\"4711\";s:8:\"birthday\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:5:\"email\";s:20:\"john.doe@nowhere.tld\";s:26:\"defaultParticipationStatus\";s:7:\"regular\";s:7:\"remarks\";N;s:23:\"cloudAccountDeactivated\";N;s:20:\"cloudAccountDisabled\";b:1;s:14:\"addressBookUri\";N;s:12:\"organization\";N;s:8:\"jobTitle\";N;s:7:\"deleted\";N;s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:4:\"uuid\";O:55:\"OCA\\CAFEVDB\\Wrapped\\Ramsey\\Uuid\\Lazy\\LazyUuidFromString\":1:{s:6:\"string\";s:36:\"00000000-0000-0000-0000-000000000000\";}}','john.doe','','1'),
(45,'create','2026-10-25 17:29:53.036488','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\MusicianEmailAddress',1,'a:3:{s:7:\"address\";s:20:\"john.doe@nowhere.tld\";s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}','john.doe','','john.doe@nowhere.tld 1'),
(46,'create','2026-10-25 17:29:53.036892','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\SepaBankAccount',1,'a:8:{s:8:\"sequence\";i:1;s:4:\"iban\";s:22:\"DE02700100800030876808\";s:3:\"bic\";s:11:\"PBNKDEFFXXX\";s:3:\"blz\";s:8:\"70010080\";s:16:\"bankAccountOwner\";s:17:\"Musterperson, Max\";s:7:\"deleted\";N;s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.913152\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.912906\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}','john.doe','','1 1'),
(47,'create','2026-10-25 17:29:53.083675','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\ProjectParticipant',1,'a:5:{s:12:\"registration\";b:0;s:19:\"participationStatus\";s:7:\"regular\";s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:52.874038\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"deleted\";N;}','john.doe','','1 1'),
(48,'create','2026-10-25 17:29:53.146296','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\MusicianInstrument',1,'a:4:{s:7:\"ranking\";i:1;s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.124101\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.123897\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"deleted\";N;}','john.doe','','1 1'),
(49,'create','2026-10-25 17:29:53.146753','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\MusicianInstrument',1,'a:4:{s:7:\"ranking\";i:1;s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.127424\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.127277\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"deleted\";N;}','john.doe','','1 4'),
(50,'create','2026-10-25 17:29:53.147274','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\ProjectInstrumentationNumber',1,'a:2:{s:5:\"voice\";i:0;s:8:\"quantity\";i:0;}','john.doe','',NULL),
(51,'create','2026-10-25 17:29:53.147645','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\ProjectInstrumentationNumber',1,'a:2:{s:5:\"voice\";i:1;s:8:\"quantity\";i:1;}','john.doe','','1 1 1'),
(52,'create','2026-10-25 17:29:53.148000','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\ProjectInstrumentationNumber',1,'a:2:{s:5:\"voice\";i:2;s:8:\"quantity\";i:2;}','john.doe','','1 1 2'),
(53,'create','2026-10-25 17:29:53.148555','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\ProjectInstrument',1,'a:2:{s:5:\"voice\";i:0;s:13:\"sectionLeader\";b:0;}','john.doe','',NULL),
(54,'update','2026-10-25 17:29:53.148964','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',2,'a:1:{s:4:\"name\";s:7:\"Violine\";}','john.doe','','1'),
(55,'update','2026-10-25 17:29:53.158393','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument',2,'a:1:{s:4:\"name\";s:10:\"Kontrabass\";}','john.doe','','4'),
(56,'update','2026-10-25 17:29:53.159499','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',2,'a:1:{s:6:\"family\";s:9:\"Streicher\";}','john.doe','','1'),
(57,'update','2026-10-25 17:29:53.160437','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily',2,'a:1:{s:6:\"family\";s:6:\"Saiten\";}','john.doe','','2'),
(58,'create','2026-10-25 17:29:53.227104','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\EncryptedFile',1,'a:6:{s:8:\"fileName\";s:7:\"file.md\";s:8:\"mimeType\";s:13:\"text/markdown\";s:4:\"size\";i:9;s:8:\"dataHash\";N;s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.196375\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.196596\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}','john.doe','','1'),
(59,'create','2026-10-25 17:29:53.248035','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\MusicianRowAccessToken',1,'a:4:{s:6:\"userId\";s:8:\"john.doe\";s:15:\"accessTokenHash\";s:128:\"d404559f602eab6fd602ac7680dacbfaadd13630335e951f097af3900e9de176b6db28512f2e000b9d04fba5133e8b1c6e8df59db3a8ab9d60be4b97cc9e81db\";s:7:\"created\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.240260\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:7:\"updated\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2026-10-25 17:29:53.240080\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}','john.doe','','1'),
(60,'update','2026-10-25 17:29:53.248494','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Project',2,'a:2:{s:21:\"registrationStartDate\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2099-01-01 00:00:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}s:20:\"registrationDeadline\";O:42:\"OCA\\CAFEVDB\\Wrapped\\Carbon\\CarbonImmutable\":3:{s:4:\"date\";s:26:\"2099-12-31 00:00:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}','john.doe','','1'),
(61,'update','2026-10-25 17:29:53.249798','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Musician',2,'a:1:{s:10:\"userIdSlug\";s:8:\"john.doe\";}','john.doe','','1'),
(62,'create','2026-10-25 17:29:53.494749','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\ProjectEvent',1,'a:9:{s:10:\"calendarId\";i:3;s:11:\"calendarUri\";s:5:\"other\";s:8:\"eventUid\";s:36:\"0c826812-96d1-48a4-95d2-e43c537b8461\";s:9:\"seriesUid\";N;s:8:\"eventUri\";s:40:\"B079DF16-D099-11F1-B470-E7E02CD0D418.ics\";s:12:\"recurrenceId\";i:0;s:8:\"sequence\";i:0;s:4:\"type\";s:6:\"VEVENT\";s:7:\"deleted\";N;}','john.doe','','1');
/*!40000 ALTER TABLE `ExtLogEntries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `FileData`
--

DROP TABLE IF EXISTS `FileData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `FileData` (
  `data_hash` char(32) NOT NULL,
  `data` longblob NOT NULL,
  `file_id` int(11) NOT NULL,
  `type` enum('generic','image','encrypted') NOT NULL,
  `lazy_file_data_dummy` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`file_id`,`lazy_file_data_dummy`),
  KEY `IDX_969FA96893CB796C` (`file_id`),
  CONSTRAINT `FK_969FA96893CB796C` FOREIGN KEY (`file_id`) REFERENCES `Files` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FileData`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `FileData` WRITE;
/*!40000 ALTER TABLE `FileData` DISABLE KEYS */;
INSERT INTO `FileData` VALUES
('d554528e77584bdcc1d29a2c230a7d2a','# Heading',1,'encrypted',1);
/*!40000 ALTER TABLE `FileData` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Files`
--

DROP TABLE IF EXISTS `Files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Files` (
  `file_name` varchar(512) DEFAULT NULL,
  `mime_type` varchar(128) NOT NULL,
  `size` int(11) NOT NULL DEFAULT -1,
  `data_hash` char(32) DEFAULT NULL,
  `updated` datetime(6) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `type` enum('generic','image','encrypted') NOT NULL,
  `width` int(11) DEFAULT -1,
  `height` int(11) DEFAULT -1,
  PRIMARY KEY (`id`),
  KEY `IDX_C7F46F5DD7DF1668` (`file_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Files`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Files` WRITE;
/*!40000 ALTER TABLE `Files` DISABLE KEYS */;
INSERT INTO `Files` VALUES
('file.md','text/markdown',9,'d554528e77584bdcc1d29a2c230a7d2a','2026-10-25 17:29:53.196375',1,'2026-10-25 17:29:53.196596','encrypted',-1,-1);
/*!40000 ALTER TABLE `Files` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GeoContinents`
--

DROP TABLE IF EXISTS `GeoContinents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GeoContinents` (
  `code` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `target` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `l10n_name` varchar(1024) NOT NULL,
  PRIMARY KEY (`code`,`target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GeoContinents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GeoContinents` WRITE;
/*!40000 ALTER TABLE `GeoContinents` DISABLE KEYS */;
/*!40000 ALTER TABLE `GeoContinents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GeoCountries`
--

DROP TABLE IF EXISTS `GeoCountries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GeoCountries` (
  `iso` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `target` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `l10n_name` varchar(1024) NOT NULL,
  `continent_code` char(2) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  PRIMARY KEY (`iso`,`target`),
  KEY `IDX_7DF803716C569B466F2FFC` (`continent_code`,`target`),
  CONSTRAINT `FK_7DF803716C569B466F2FFC` FOREIGN KEY (`continent_code`, `target`) REFERENCES `GeoContinents` (`code`, `target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GeoCountries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GeoCountries` WRITE;
/*!40000 ALTER TABLE `GeoCountries` DISABLE KEYS */;
/*!40000 ALTER TABLE `GeoCountries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GeoPostalCodeTranslations`
--

DROP TABLE IF EXISTS `GeoPostalCodeTranslations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GeoPostalCodeTranslations` (
  `target` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `translation` varchar(1024) NOT NULL,
  `geo_postal_code_id` int(11) NOT NULL,
  PRIMARY KEY (`geo_postal_code_id`,`target`),
  KEY `IDX_BC664719E70E684F` (`geo_postal_code_id`),
  CONSTRAINT `FK_BC664719E70E684F` FOREIGN KEY (`geo_postal_code_id`) REFERENCES `GeoPostalCodes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GeoPostalCodeTranslations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GeoPostalCodeTranslations` WRITE;
/*!40000 ALTER TABLE `GeoPostalCodeTranslations` DISABLE KEYS */;
/*!40000 ALTER TABLE `GeoPostalCodeTranslations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GeoPostalCodes`
--

DROP TABLE IF EXISTS `GeoPostalCodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GeoPostalCodes` (
  `country` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `state_province` char(3) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `postal_code` varchar(32) NOT NULL,
  `name` varchar(650) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B50ACD455373C966EA98E3765E237E06` (`country`,`postal_code`,`name`),
  KEY `updated` (`updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GeoPostalCodes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GeoPostalCodes` WRITE;
/*!40000 ALTER TABLE `GeoPostalCodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `GeoPostalCodes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GeoStatesProvinces`
--

DROP TABLE IF EXISTS `GeoStatesProvinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GeoStatesProvinces` (
  `country_iso` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `code` char(3) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `target` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `l10n_name` varchar(1024) NOT NULL,
  PRIMARY KEY (`country_iso`,`code`,`target`),
  KEY `IDX_40C5B1885A7049D0466F2FFC` (`country_iso`,`target`),
  CONSTRAINT `FK_40C5B1885A7049D0466F2FFC` FOREIGN KEY (`country_iso`, `target`) REFERENCES `GeoCountries` (`iso`, `target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GeoStatesProvinces`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GeoStatesProvinces` WRITE;
/*!40000 ALTER TABLE `GeoStatesProvinces` DISABLE KEYS */;
/*!40000 ALTER TABLE `GeoStatesProvinces` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GnuCashAccounts`
--

DROP TABLE IF EXISTS `GnuCashAccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GnuCashAccounts` (
  `guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `name` varchar(2028) NOT NULL,
  `account_type` varchar(2028) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `commodity_scu` int(11) NOT NULL,
  `non_std_scu` int(11) NOT NULL,
  `code` varchar(2028) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `description` varchar(2028) NOT NULL,
  `hidden` tinyint(4) NOT NULL,
  `placeholder` tinyint(4) NOT NULL,
  `commodity_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `parent_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  PRIMARY KEY (`guid`),
  KEY `IDX_1C4A70F24F9CBEC7` (`commodity_guid`),
  KEY `IDX_1C4A70F2168CF906` (`parent_guid`),
  CONSTRAINT `FK_1C4A70F2168CF906` FOREIGN KEY (`parent_guid`) REFERENCES `GnuCashAccounts` (`guid`),
  CONSTRAINT `FK_1C4A70F24F9CBEC7` FOREIGN KEY (`commodity_guid`) REFERENCES `GnuCashCommodities` (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GnuCashAccounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GnuCashAccounts` WRITE;
/*!40000 ALTER TABLE `GnuCashAccounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `GnuCashAccounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GnuCashBooks`
--

DROP TABLE IF EXISTS `GnuCashBooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GnuCashBooks` (
  `guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `root_account_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `root_template_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `UNIQ_A26D411FD96A93A7` (`root_account_guid`),
  UNIQUE KEY `UNIQ_A26D411FA501DD19` (`root_template_guid`),
  CONSTRAINT `FK_A26D411FA501DD19` FOREIGN KEY (`root_template_guid`) REFERENCES `GnuCashAccounts` (`guid`),
  CONSTRAINT `FK_A26D411FD96A93A7` FOREIGN KEY (`root_account_guid`) REFERENCES `GnuCashAccounts` (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GnuCashBooks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GnuCashBooks` WRITE;
/*!40000 ALTER TABLE `GnuCashBooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `GnuCashBooks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GnuCashCommodities`
--

DROP TABLE IF EXISTS `GnuCashCommodities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GnuCashCommodities` (
  `guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `namespace` varchar(2024) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `mnemonic` varchar(2028) NOT NULL,
  `fullname` varchar(2028) NOT NULL,
  `cusip` varchar(2028) NOT NULL,
  `fraction` int(11) NOT NULL,
  `quote_flag` tinyint(4) NOT NULL,
  `quote_source` varchar(2028) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `quote_tz` varchar(2028) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GnuCashCommodities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GnuCashCommodities` WRITE;
/*!40000 ALTER TABLE `GnuCashCommodities` DISABLE KEYS */;
/*!40000 ALTER TABLE `GnuCashCommodities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GnuCashSlots`
--

DROP TABLE IF EXISTS `GnuCashSlots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GnuCashSlots` (
  `obj_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `name` varchar(4096) NOT NULL,
  `slot_type` int(11) NOT NULL,
  `int64_val` int(11) DEFAULT NULL,
  `string_val` varchar(4096) DEFAULT NULL,
  `double_val` double DEFAULT NULL,
  `timespec_val` datetime(6) DEFAULT NULL,
  `guid_val` char(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `numeric_val_num` int(11) DEFAULT NULL,
  `numeric_val_denom` int(11) DEFAULT NULL,
  `gdate_val` datetime(6) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `slots_guid_index` (`obj_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GnuCashSlots`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GnuCashSlots` WRITE;
/*!40000 ALTER TABLE `GnuCashSlots` DISABLE KEYS */;
/*!40000 ALTER TABLE `GnuCashSlots` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GnuCashSplits`
--

DROP TABLE IF EXISTS `GnuCashSplits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GnuCashSplits` (
  `guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `memo` varchar(2028) NOT NULL,
  `action` varchar(2028) NOT NULL,
  `reconcile_state` char(1) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `reconcile_date` datetime(6) DEFAULT NULL,
  `value_num` int(11) NOT NULL,
  `value_denom` int(11) NOT NULL,
  `quantity_num` int(11) NOT NULL,
  `quantity_denom` int(11) NOT NULL,
  `lot_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `tx_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `account_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`guid`),
  KEY `splits_tx_guid_index` (`tx_guid`),
  KEY `splits_account_guid_index` (`account_guid`),
  CONSTRAINT `FK_E2EE9395A7FC4818` FOREIGN KEY (`account_guid`) REFERENCES `GnuCashAccounts` (`guid`),
  CONSTRAINT `FK_E2EE9395D252EC5E` FOREIGN KEY (`tx_guid`) REFERENCES `GnuCashTransactions` (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GnuCashSplits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GnuCashSplits` WRITE;
/*!40000 ALTER TABLE `GnuCashSplits` DISABLE KEYS */;
/*!40000 ALTER TABLE `GnuCashSplits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `GnuCashTransactions`
--

DROP TABLE IF EXISTS `GnuCashTransactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `GnuCashTransactions` (
  `guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `num` varchar(2028) NOT NULL,
  `post_date` datetime(6) NOT NULL DEFAULT '1970-01-01 00:00:00.000000',
  `enter_date` datetime(6) NOT NULL DEFAULT '1970-01-01 00:00:00.000000',
  `description` varchar(2028) DEFAULT NULL,
  `currency_guid` char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (`guid`),
  KEY `IDX_403125FA1D88CC6` (`currency_guid`),
  KEY `tx_post_date_index` (`post_date`),
  CONSTRAINT `FK_403125FA1D88CC6` FOREIGN KEY (`currency_guid`) REFERENCES `GnuCashCommodities` (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GnuCashTransactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `GnuCashTransactions` WRITE;
/*!40000 ALTER TABLE `GnuCashTransactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `GnuCashTransactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `InstrumentFamilies`
--

DROP TABLE IF EXISTS `InstrumentFamilies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `InstrumentFamilies` (
  `family` varchar(255) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_31147B76A5E6215B` (`family`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InstrumentFamilies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `InstrumentFamilies` WRITE;
/*!40000 ALTER TABLE `InstrumentFamilies` DISABLE KEYS */;
INSERT INTO `InstrumentFamilies` VALUES
('strings',1,NULL),
('string',2,NULL),
('plucked',3,NULL),
('wind',4,NULL),
('wood',5,NULL),
('brass',6,NULL),
('percussion',7,NULL),
('keyboard',8,NULL),
('miscellaneous',9,NULL),
('not an instrument',10,NULL);
/*!40000 ALTER TABLE `InstrumentFamilies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `InstrumentInsurances`
--

DROP TABLE IF EXISTS `InstrumentInsurances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `InstrumentInsurances` (
  `object` varchar(128) NOT NULL,
  `accessory` tinyint(4) DEFAULT 0,
  `manufacturer` varchar(128) NOT NULL,
  `year_of_construction` varchar(64) NOT NULL,
  `insurance_amount` int(11) NOT NULL,
  `start_of_insurance` datetime(6) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `instrument_holder_id` int(11) NOT NULL,
  `instrument_owner_id` int(11) DEFAULT NULL,
  `bill_to_party_id` int(11) NOT NULL,
  `broker_id` varchar(40) NOT NULL,
  `geographical_scope` enum('Domestic','Continent','Germany','Europe','World') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B9BA7EFA948FBE6` (`instrument_holder_id`),
  KEY `IDX_B9BA7EFDF95C1F8` (`instrument_owner_id`),
  KEY `IDX_B9BA7EF9D7A36FA` (`bill_to_party_id`),
  KEY `IDX_B9BA7EF6CC064FCBD069886` (`broker_id`,`geographical_scope`),
  CONSTRAINT `FK_B9BA7EF6CC064FCBD069886` FOREIGN KEY (`broker_id`, `geographical_scope`) REFERENCES `InsuranceRates` (`broker_id`, `geographical_scope`),
  CONSTRAINT `FK_B9BA7EF9D7A36FA` FOREIGN KEY (`bill_to_party_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_B9BA7EFA948FBE6` FOREIGN KEY (`instrument_holder_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_B9BA7EFDF95C1F8` FOREIGN KEY (`instrument_owner_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InstrumentInsurances`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `InstrumentInsurances` WRITE;
/*!40000 ALTER TABLE `InstrumentInsurances` DISABLE KEYS */;
/*!40000 ALTER TABLE `InstrumentInsurances` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Instruments`
--

DROP TABLE IF EXISTS `Instruments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Instruments` (
  `name` varchar(128) NOT NULL,
  `sort_order` int(11) NOT NULL COMMENT 'Orchestral Ordering',
  `deleted` datetime(6) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_65CC51DC5E237E06` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Instruments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Instruments` WRITE;
/*!40000 ALTER TABLE `Instruments` DISABLE KEYS */;
INSERT INTO `Instruments` VALUES
('violin',1,NULL,1),
('viola',2,NULL,2),
('violoncello',3,NULL,3),
('double bass',4,NULL,4),
('flute',10,NULL,5),
('piccolo',11,NULL,6),
('oboe',20,NULL,7),
('English horn',25,NULL,8),
('clarinet',30,NULL,9),
('bass clarinet',35,NULL,10),
('bassoon',40,NULL,11),
('natural horn',50,NULL,12),
('trumpet',60,NULL,13),
('trombone',70,NULL,14),
('bass trombone',71,NULL,15),
('tuba',80,NULL,16),
('harp',90,NULL,17),
('guitar',95,NULL,18),
('timpani',100,NULL,19),
('drum',105,NULL,20),
('bass drum',110,NULL,21),
('cymbals',201,NULL,22),
('glockenspiel',203,NULL,23),
('xylophone',400,NULL,24),
('piano',5000,NULL,25),
('organ',5010,NULL,26),
('harpsichord',5015,NULL,27),
('celesta',5020,NULL,28),
('bandoneon',5025,NULL,29),
('accordion',5030,NULL,30),
('associate',2147483647,NULL,31),
('business partner',2147483647,NULL,32);
/*!40000 ALTER TABLE `Instruments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `InsuranceBrokers`
--

DROP TABLE IF EXISTS `InsuranceBrokers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `InsuranceBrokers` (
  `short_name` varchar(40) NOT NULL,
  `long_name` varchar(512) NOT NULL,
  `address` varchar(512) NOT NULL,
  PRIMARY KEY (`short_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InsuranceBrokers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `InsuranceBrokers` WRITE;
/*!40000 ALTER TABLE `InsuranceBrokers` DISABLE KEYS */;
/*!40000 ALTER TABLE `InsuranceBrokers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `InsuranceRates`
--

DROP TABLE IF EXISTS `InsuranceRates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `InsuranceRates` (
  `geographical_scope` enum('Domestic','Continent','Germany','Europe','World') NOT NULL DEFAULT 'Germany',
  `rate` decimal(4,4) unsigned NOT NULL COMMENT 'fraction, not percentage, excluding taxes',
  `due_date` datetime(6) DEFAULT NULL COMMENT 'start of the yearly insurance period',
  `policy_number` varchar(255) DEFAULT NULL,
  `broker_id` varchar(40) NOT NULL,
  PRIMARY KEY (`broker_id`,`geographical_scope`),
  KEY `IDX_CB75C3526CC064FC` (`broker_id`),
  CONSTRAINT `FK_CB75C3526CC064FC` FOREIGN KEY (`broker_id`) REFERENCES `InsuranceBrokers` (`short_name`),
  CONSTRAINT `FK_CB75C3526CC064FC_ONUPDATE_CASCADE` FOREIGN KEY (`broker_id`) REFERENCES `InsuranceBrokers` (`short_name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InsuranceRates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `InsuranceRates` WRITE;
/*!40000 ALTER TABLE `InsuranceRates` DISABLE KEYS */;
/*!40000 ALTER TABLE `InsuranceRates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `InvoiceItems`
--

DROP TABLE IF EXISTS `InvoiceItems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `InvoiceItems` (
  `amount` decimal(7,2) NOT NULL DEFAULT 0.00,
  `subject` varchar(1024) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `field_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `debitor_id` int(11) NOT NULL,
  `receivable_key` binary(16) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `balance_documents_folder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_670E0FCF443707B0166D1F9C72757D19D151D1BF` (`field_id`,`project_id`,`debitor_id`,`receivable_key`),
  KEY `IDX_670E0FCF443707B0D151D1BF` (`field_id`,`receivable_key`),
  KEY `IDX_670E0FCF2989F1FD` (`invoice_id`),
  KEY `IDX_670E0FCF166D1F9C` (`project_id`),
  KEY `IDX_670E0FCF72757D19` (`debitor_id`),
  KEY `IDX_670E0FCF166D1F9C72757D19` (`project_id`,`debitor_id`),
  KEY `IDX_670E0FCF8A034ED2` (`balance_documents_folder_id`),
  CONSTRAINT `FK_670E0FCF166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_670E0FCF166D1F9C72757D19` FOREIGN KEY (`project_id`, `debitor_id`) REFERENCES `ProjectParticipants` (`project_id`, `musician_id`),
  CONSTRAINT `FK_670E0FCF2989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `Invoices` (`id`),
  CONSTRAINT `FK_670E0FCF443707B0166D1F9C72757D19D151D1BF` FOREIGN KEY (`field_id`, `project_id`, `debitor_id`, `receivable_key`) REFERENCES `ProjectParticipantFieldsData` (`field_id`, `project_id`, `musician_id`, `option_key`),
  CONSTRAINT `FK_670E0FCF443707B0D151D1BF` FOREIGN KEY (`field_id`, `receivable_key`) REFERENCES `ProjectParticipantFieldsDataOptions` (`field_id`, `key`),
  CONSTRAINT `FK_670E0FCF72757D19` FOREIGN KEY (`debitor_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_670E0FCF8A034ED2` FOREIGN KEY (`balance_documents_folder_id`) REFERENCES `DatabaseStorageDirEntries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvoiceItems`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `InvoiceItems` WRITE;
/*!40000 ALTER TABLE `InvoiceItems` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvoiceItems` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Invoices`
--

DROP TABLE IF EXISTS `Invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Invoices` (
  `invoice_number` varchar(255) NOT NULL,
  `amount` decimal(7,2) NOT NULL DEFAULT 0.00,
  `invoice_date` datetime(6) NOT NULL DEFAULT curdate(),
  `due_date` datetime(6) DEFAULT NULL,
  `balanced_date` datetime(6) DEFAULT NULL,
  `subject` varchar(1024) NOT NULL,
  `purpose` longtext DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `originator_id` int(11) DEFAULT NULL,
  `debitor_id` int(11) NOT NULL,
  `sepa_transaction_id` int(11) DEFAULT NULL,
  `bank_account_sequence` int(11) DEFAULT NULL,
  `debit_mandate_sequence` int(11) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `balance_documents_folder_id` int(11) DEFAULT NULL,
  `written_invoice_id` int(11) DEFAULT NULL,
  `taxation_statutory_source_id` int(11) NOT NULL,
  `notification_message_id` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_93594DC32DA68207` (`invoice_number`),
  UNIQUE KEY `UNIQ_93594DC397F6692F` (`written_invoice_id`),
  UNIQUE KEY `UNIQ_93594DC3A808B60B` (`notification_message_id`),
  KEY `IDX_93594DC33DA3F86F` (`originator_id`),
  KEY `IDX_93594DC372757D19` (`debitor_id`),
  KEY `IDX_93594DC3D5560045` (`sepa_transaction_id`),
  KEY `IDX_93594DC372757D192301E184` (`debitor_id`,`bank_account_sequence`),
  KEY `IDX_93594DC372757D19544C02F9` (`debitor_id`,`debit_mandate_sequence`),
  KEY `IDX_93594DC3166D1F9C` (`project_id`),
  KEY `IDX_93594DC3166D1F9C72757D19` (`project_id`,`debitor_id`),
  KEY `IDX_93594DC38A034ED2` (`balance_documents_folder_id`),
  KEY `IDX_93594DC366FAD11` (`taxation_statutory_source_id`),
  CONSTRAINT `FK_93594DC3166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_93594DC3166D1F9C72757D19` FOREIGN KEY (`project_id`, `debitor_id`) REFERENCES `ProjectParticipants` (`project_id`, `musician_id`),
  CONSTRAINT `FK_93594DC33DA3F86F` FOREIGN KEY (`originator_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_93594DC366FAD11` FOREIGN KEY (`taxation_statutory_source_id`) REFERENCES `TaxationStatutorySources` (`id`),
  CONSTRAINT `FK_93594DC372757D19` FOREIGN KEY (`debitor_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_93594DC372757D192301E184` FOREIGN KEY (`debitor_id`, `bank_account_sequence`) REFERENCES `SepaBankAccounts` (`musician_id`, `sequence`),
  CONSTRAINT `FK_93594DC372757D19544C02F9` FOREIGN KEY (`debitor_id`, `debit_mandate_sequence`) REFERENCES `SepaDebitMandates` (`musician_id`, `sequence`),
  CONSTRAINT `FK_93594DC38A034ED2` FOREIGN KEY (`balance_documents_folder_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_93594DC397F6692F` FOREIGN KEY (`written_invoice_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_93594DC3A808B60B` FOREIGN KEY (`notification_message_id`) REFERENCES `SentEmails` (`message_id`),
  CONSTRAINT `FK_93594DC3D5560045` FOREIGN KEY (`sepa_transaction_id`) REFERENCES `SepaBulkTransactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Invoices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Invoices` WRITE;
/*!40000 ALTER TABLE `Invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `Invoices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Migrations`
--

DROP TABLE IF EXISTS `Migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Migrations` (
  `version` char(14) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `migration_class_name` varchar(512) NOT NULL,
  `run_count` int(11) NOT NULL DEFAULT 1,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Migrations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Migrations` WRITE;
/*!40000 ALTER TABLE `Migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `MissingTranslations`
--

DROP TABLE IF EXISTS `MissingTranslations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `MissingTranslations` (
  `locale` varchar(5) NOT NULL,
  `translation_key_id` int(11) NOT NULL,
  PRIMARY KEY (`translation_key_id`,`locale`),
  KEY `IDX_DBBA64EAD07ED992` (`translation_key_id`),
  CONSTRAINT `FK_DBBA64EAD07ED992` FOREIGN KEY (`translation_key_id`) REFERENCES `TranslationKeys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MissingTranslations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `MissingTranslations` WRITE;
/*!40000 ALTER TABLE `MissingTranslations` DISABLE KEYS */;
/*!40000 ALTER TABLE `MissingTranslations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `MusicianEmailAddresses`
--

DROP TABLE IF EXISTS `MusicianEmailAddresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `MusicianEmailAddresses` (
  `address` varchar(254) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `musician_id` int(11) NOT NULL,
  PRIMARY KEY (`address`,`musician_id`),
  KEY `IDX_13DF84F69523AA8A` (`musician_id`),
  CONSTRAINT `FK_13DF84F69523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MusicianEmailAddresses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `MusicianEmailAddresses` WRITE;
/*!40000 ALTER TABLE `MusicianEmailAddresses` DISABLE KEYS */;
INSERT INTO `MusicianEmailAddresses` VALUES
('john.doe@nowhere.tld','2026-10-25 17:29:52.874038','2026-10-25 17:29:52.874038',1);
/*!40000 ALTER TABLE `MusicianEmailAddresses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `MusicianInstruments`
--

DROP TABLE IF EXISTS `MusicianInstruments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `MusicianInstruments` (
  `ranking` int(11) NOT NULL DEFAULT 1 COMMENT 'Ranking of the instrument w.r.t. to the given musician (lower is better)',
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `deleted` datetime(6) DEFAULT NULL,
  `musician_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  PRIMARY KEY (`musician_id`,`instrument_id`),
  KEY `IDX_332855779523AA8A` (`musician_id`),
  KEY `IDX_33285577CF11D9C` (`instrument_id`),
  CONSTRAINT `FK_332855779523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_33285577CF11D9C` FOREIGN KEY (`instrument_id`) REFERENCES `Instruments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='Join-table Musicians -> Instruments';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MusicianInstruments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `MusicianInstruments` WRITE;
/*!40000 ALTER TABLE `MusicianInstruments` DISABLE KEYS */;
INSERT INTO `MusicianInstruments` VALUES
(1,'2026-10-25 17:29:53.124101','2026-10-25 17:29:53.123897',NULL,1,1),
(1,'2026-10-25 17:29:53.127424','2026-10-25 17:29:53.127277',NULL,1,4);
/*!40000 ALTER TABLE `MusicianInstruments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `MusicianRowAccessTokens`
--

DROP TABLE IF EXISTS `MusicianRowAccessTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `MusicianRowAccessTokens` (
  `user_id` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `access_token_hash` char(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `musician_id` int(11) NOT NULL,
  PRIMARY KEY (`musician_id`),
  UNIQUE KEY `UNIQ_64C47A569982CF5B` (`access_token_hash`),
  UNIQUE KEY `UNIQ_64C47A56A76ED395` (`user_id`),
  CONSTRAINT `FK_64C47A569523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MusicianRowAccessTokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `MusicianRowAccessTokens` WRITE;
/*!40000 ALTER TABLE `MusicianRowAccessTokens` DISABLE KEYS */;
INSERT INTO `MusicianRowAccessTokens` VALUES
('john.doe','d404559f602eab6fd602ac7680dacbfaadd13630335e951f097af3900e9de176b6db28512f2e000b9d04fba5133e8b1c6e8df59db3a8ab9d60be4b97cc9e81db','2026-10-25 17:29:53.240260','2026-10-25 17:29:53.240080',1);
/*!40000 ALTER TABLE `MusicianRowAccessTokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Musicians`
--

DROP TABLE IF EXISTS `Musicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Musicians` (
  `sur_name` varchar(128) NOT NULL,
  `first_name` varchar(128) NOT NULL,
  `nick_name` varchar(128) DEFAULT NULL,
  `display_name` varchar(256) DEFAULT NULL,
  `gender` enum('male','female','diverse') DEFAULT NULL,
  `user_id_slug` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `user_passphrase` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `city` varchar(128) DEFAULT NULL,
  `street` varchar(128) DEFAULT NULL,
  `street_number` varchar(32) DEFAULT NULL,
  `address_supplement` varchar(128) DEFAULT NULL,
  `po_box` varchar(128) DEFAULT NULL,
  `country` char(2) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `postal_code` varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `language` char(5) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `mobile_phone` varchar(128) DEFAULT NULL,
  `fixed_line_phone` varchar(128) DEFAULT NULL,
  `birthday` datetime(6) DEFAULT NULL,
  `email` varchar(254) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `default_participation_status` enum('associated','conductor','passive','regular','soloist','temporary') NOT NULL DEFAULT 'regular',
  `remarks` varchar(1024) DEFAULT NULL,
  `cloud_account_deactivated` tinyint(4) DEFAULT NULL,
  `cloud_account_disabled` tinyint(4) DEFAULT 1,
  `updated` datetime(6) DEFAULT curtime(),
  `address_book_uri` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `deleted` datetime(6) DEFAULT NULL,
  `uuid` binary(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3CC48982D17F50A6` (`uuid`),
  UNIQUE KEY `UNIQ_3CC489824BB0996A` (`user_id_slug`),
  KEY `country_postal_code_deleted` (`country`,`postal_code`,`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Musicians`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Musicians` WRITE;
/*!40000 ALTER TABLE `Musicians` DISABLE KEYS */;
INSERT INTO `Musicians` VALUES
('Musterperson','Max',NULL,NULL,NULL,'john.doe',NULL,'Nirgends','Unauffindbarweg','42','Igloo 13',NULL,'AQ','Z-7',NULL,'0815','4711','2026-10-25 17:29:52.874038','john.doe@nowhere.tld','regular',NULL,NULL,1,'2026-10-25 17:29:53.251169',NULL,NULL,NULL,1,'2026-10-25 17:29:52.874038',NULL,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0');
/*!40000 ALTER TABLE `Musicians` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectApplications`
--

DROP TABLE IF EXISTS `ProjectApplications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectApplications` (
  `email` varchar(254) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `password_hash` varchar(254) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`data`)),
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `project_id` int(11) NOT NULL,
  `musician_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`project_id`,`email`),
  KEY `IDX_5F0E8E19166D1F9C` (`project_id`),
  KEY `IDX_5F0E8E199523AA8A` (`musician_id`),
  CONSTRAINT `FK_5F0E8E19166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_5F0E8E199523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectApplications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectApplications` WRITE;
/*!40000 ALTER TABLE `ProjectApplications` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectApplications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectEvents`
--

DROP TABLE IF EXISTS `ProjectEvents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectEvents` (
  `calendar_id` int(11) NOT NULL,
  `calendar_uri` varchar(764) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `event_uid` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `series_uid` binary(16) DEFAULT NULL,
  `event_uri` varchar(764) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recurrence_id` bigint(20) NOT NULL DEFAULT 0,
  `sequence` int(11) NOT NULL DEFAULT 0,
  `type` enum('VEVENT','VTODO','VJOURNAL','VCARD') NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `absence_field_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7E38FC8B166D1F9C7A7DD3924254C3D52C414CE8` (`project_id`,`calendar_uri`,`event_uid`,`recurrence_id`),
  UNIQUE KEY `UNIQ_7E38FC8B166D1F9CA40A2C84254C3D52C414CE8` (`project_id`,`calendar_id`,`event_uid`,`recurrence_id`),
  UNIQUE KEY `UNIQ_7E38FC8B166D1F9C7A7DD39295D374F22C414CE8` (`project_id`,`calendar_uri`,`event_uri`,`recurrence_id`),
  UNIQUE KEY `UNIQ_7E38FC8B166D1F9CA40A2C895D374F22C414CE8` (`project_id`,`calendar_id`,`event_uri`,`recurrence_id`),
  UNIQUE KEY `UNIQ_7E38FC8BA79D8A87` (`absence_field_id`),
  KEY `IDX_7E38FC8B166D1F9C` (`project_id`),
  CONSTRAINT `FK_7E38FC8B166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_7E38FC8BA79D8A87` FOREIGN KEY (`absence_field_id`) REFERENCES `ProjectParticipantFields` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectEvents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectEvents` WRITE;
/*!40000 ALTER TABLE `ProjectEvents` DISABLE KEYS */;
INSERT INTO `ProjectEvents` VALUES
(3,'other','0c826812-96d1-48a4-95d2-e43c537b8461',NULL,'B079DF16-D099-11F1-B470-E7E02CD0D418.ics',0,0,'VEVENT',1,NULL,1,NULL);
/*!40000 ALTER TABLE `ProjectEvents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectInstrumentationNumbers`
--

DROP TABLE IF EXISTS `ProjectInstrumentationNumbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectInstrumentationNumbers` (
  `voice` int(11) NOT NULL DEFAULT 0 COMMENT 'Voice specification if applicable, set to 0 if separation by voice is not needed',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of required musicians for this instrument',
  `project_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`instrument_id`,`voice`),
  KEY `IDX_D8939186166D1F9C` (`project_id`),
  KEY `IDX_D8939186CF11D9C` (`instrument_id`),
  CONSTRAINT `FK_D8939186166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_D8939186CF11D9C` FOREIGN KEY (`instrument_id`) REFERENCES `Instruments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectInstrumentationNumbers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectInstrumentationNumbers` WRITE;
/*!40000 ALTER TABLE `ProjectInstrumentationNumbers` DISABLE KEYS */;
INSERT INTO `ProjectInstrumentationNumbers` VALUES
(0,0,1,1),
(1,1,1,1),
(2,2,1,1);
/*!40000 ALTER TABLE `ProjectInstrumentationNumbers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectInstruments`
--

DROP TABLE IF EXISTS `ProjectInstruments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectInstruments` (
  `voice` int(11) NOT NULL DEFAULT 0 COMMENT 'Voice specification if applicable, set to 0 if separation by voice is not needed',
  `section_leader` tinyint(4) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL,
  `musician_id` int(11) NOT NULL,
  `instrument_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`musician_id`,`instrument_id`,`voice`),
  KEY `IDX_436762A6166D1F9C` (`project_id`),
  KEY `IDX_436762A69523AA8A` (`musician_id`),
  KEY `IDX_436762A6CF11D9C` (`instrument_id`),
  KEY `IDX_436762A6166D1F9C9523AA8A` (`project_id`,`musician_id`),
  KEY `IDX_436762A69523AA8ACF11D9C` (`musician_id`,`instrument_id`),
  KEY `IDX_436762A6166D1F9CCF11D9CE7FB583B` (`project_id`,`instrument_id`,`voice`),
  CONSTRAINT `FK_436762A6166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_436762A6166D1F9C9523AA8A` FOREIGN KEY (`project_id`, `musician_id`) REFERENCES `ProjectParticipants` (`project_id`, `musician_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_436762A6166D1F9CCF11D9CE7FB583B` FOREIGN KEY (`project_id`, `instrument_id`, `voice`) REFERENCES `ProjectInstrumentationNumbers` (`project_id`, `instrument_id`, `voice`),
  CONSTRAINT `FK_436762A69523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_436762A69523AA8ACF11D9C` FOREIGN KEY (`musician_id`, `instrument_id`) REFERENCES `MusicianInstruments` (`musician_id`, `instrument_id`),
  CONSTRAINT `FK_436762A6CF11D9C` FOREIGN KEY (`instrument_id`) REFERENCES `Instruments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectInstruments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectInstruments` WRITE;
/*!40000 ALTER TABLE `ProjectInstruments` DISABLE KEYS */;
INSERT INTO `ProjectInstruments` VALUES
(1,0,1,1,1);
/*!40000 ALTER TABLE `ProjectInstruments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectParticipantFields`
--

DROP TABLE IF EXISTS `ProjectParticipantFields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectParticipantFields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `multiplicity` enum('simple','single','multiple','parallel','recurring','groupofpeople','groupsofpeople') NOT NULL,
  `data_type` enum('boolean','cloud-file','cloud-folder','date','datetime','db-file','float','html','integer','liabilities','receivables','text') NOT NULL DEFAULT 'text',
  `due_date` datetime(6) DEFAULT NULL COMMENT 'Due-date for financial fields.',
  `deposit_due_date` datetime(6) DEFAULT NULL COMMENT 'Due-date of deposit for financial fields.',
  `balancing_account` varchar(1024) DEFAULT NULL,
  `tooltip` varchar(4096) DEFAULT NULL,
  `tab` varchar(256) DEFAULT NULL COMMENT 'Tab to display the field in. If empty, then the project tab is used.',
  `display_order` int(11) DEFAULT NULL,
  `participation_context` enum('associates','participants','unrestricted') NOT NULL DEFAULT 'unrestricted',
  `encrypted` tinyint(4) DEFAULT 0,
  `participant_access` enum('none','read','read-write') NOT NULL DEFAULT 'none',
  `deleted` datetime(6) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `default_value` binary(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F6F5D9C6166D1F9C` (`project_id`),
  KEY `IDX_F6F5D9C6BF396750F4510C3A` (`id`,`default_value`),
  KEY `IDX_F6F5D9C6BF396750166D1F9C` (`id`,`project_id`),
  CONSTRAINT `FK_F6F5D9C6166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_F6F5D9C6BF396750F4510C3A` FOREIGN KEY (`id`, `default_value`) REFERENCES `ProjectParticipantFieldsDataOptions` (`field_id`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectParticipantFields`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectParticipantFields` WRITE;
/*!40000 ALTER TABLE `ProjectParticipantFields` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectParticipantFields` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectParticipantFieldsData`
--

DROP TABLE IF EXISTS `ProjectParticipantFieldsData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectParticipantFieldsData` (
  `option_key` binary(16) NOT NULL,
  `option_value` mediumtext DEFAULT NULL,
  `deposit` decimal(7,2) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `deleted` datetime(6) DEFAULT NULL,
  `field_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `musician_id` int(11) NOT NULL,
  `supporting_document_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`field_id`,`project_id`,`musician_id`,`option_key`),
  UNIQUE KEY `UNIQ_E1AAA1E92423759C` (`supporting_document_id`),
  KEY `IDX_E1AAA1E9443707B0` (`field_id`),
  KEY `IDX_E1AAA1E9166D1F9C` (`project_id`),
  KEY `IDX_E1AAA1E99523AA8A` (`musician_id`),
  KEY `IDX_E1AAA1E9443707B03CEE7BEE` (`field_id`,`option_key`),
  KEY `IDX_E1AAA1E9166D1F9C9523AA8A` (`project_id`,`musician_id`),
  KEY `IDX_E1AAA1E9443707B0166D1F9C` (`field_id`,`project_id`),
  CONSTRAINT `FK_E1AAA1E9166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_E1AAA1E9166D1F9C9523AA8A` FOREIGN KEY (`project_id`, `musician_id`) REFERENCES `ProjectParticipants` (`project_id`, `musician_id`),
  CONSTRAINT `FK_E1AAA1E92423759C` FOREIGN KEY (`supporting_document_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_E1AAA1E9443707B0` FOREIGN KEY (`field_id`) REFERENCES `ProjectParticipantFields` (`id`),
  CONSTRAINT `FK_E1AAA1E9443707B03CEE7BEE` FOREIGN KEY (`field_id`, `option_key`) REFERENCES `ProjectParticipantFieldsDataOptions` (`field_id`, `key`),
  CONSTRAINT `FK_E1AAA1E99523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectParticipantFieldsData`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectParticipantFieldsData` WRITE;
/*!40000 ALTER TABLE `ProjectParticipantFieldsData` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectParticipantFieldsData` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectParticipantFieldsDataOptions`
--

DROP TABLE IF EXISTS `ProjectParticipantFieldsDataOptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectParticipantFieldsDataOptions` (
  `key` binary(16) NOT NULL,
  `label` varchar(128) DEFAULT NULL,
  `data` varchar(1024) DEFAULT NULL,
  `balancing_account` varchar(1024) DEFAULT NULL,
  `deposit` decimal(7,2) DEFAULT NULL,
  `limit` bigint(20) DEFAULT NULL,
  `tooltip` varchar(4096) DEFAULT NULL,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `field_id` int(11) NOT NULL,
  PRIMARY KEY (`field_id`,`key`),
  KEY `IDX_FA443FE443707B0` (`field_id`),
  KEY `IDX_FA443FE8A90ABA9` (`key`),
  CONSTRAINT `FK_FA443FE443707B0` FOREIGN KEY (`field_id`) REFERENCES `ProjectParticipantFields` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectParticipantFieldsDataOptions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectParticipantFieldsDataOptions` WRITE;
/*!40000 ALTER TABLE `ProjectParticipantFieldsDataOptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectParticipantFieldsDataOptions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectParticipants`
--

DROP TABLE IF EXISTS `ProjectParticipants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectParticipants` (
  `registration` tinyint(4) DEFAULT 0 COMMENT 'Participant has confirmed the registration.',
  `participation_status` enum('associated','conductor','passive','regular','soloist','temporary') NOT NULL DEFAULT 'regular',
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `deleted` datetime(6) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `musician_id` int(11) NOT NULL,
  `database_documents_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`project_id`,`musician_id`),
  UNIQUE KEY `UNIQ_D9AE987BC6073910` (`database_documents_id`),
  KEY `IDX_D9AE987B166D1F9C` (`project_id`),
  KEY `IDX_D9AE987B9523AA8A` (`musician_id`),
  CONSTRAINT `FK_D9AE987B166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_D9AE987B9523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_D9AE987BC6073910` FOREIGN KEY (`database_documents_id`) REFERENCES `DatabaseStorages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectParticipants`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectParticipants` WRITE;
/*!40000 ALTER TABLE `ProjectParticipants` DISABLE KEYS */;
INSERT INTO `ProjectParticipants` VALUES
(0,'regular','2026-10-25 17:29:52.874038','2026-10-25 17:29:52.874038',NULL,1,1,NULL);
/*!40000 ALTER TABLE `ProjectParticipants` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectPayments`
--

DROP TABLE IF EXISTS `ProjectPayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectPayments` (
  `amount` decimal(7,2) NOT NULL DEFAULT 0.00,
  `is_donation` tinyint(4) NOT NULL DEFAULT 0,
  `subject` varchar(1024) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `field_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `musician_id` int(11) NOT NULL,
  `receivable_key` binary(16) NOT NULL,
  `composite_payment_id` int(11) NOT NULL,
  `balance_documents_folder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F6372AE2443707B0166D1F9C9523AA8AD151D1BF` (`field_id`,`project_id`,`musician_id`,`receivable_key`),
  KEY `IDX_F6372AE2443707B0D151D1BF` (`field_id`,`receivable_key`),
  KEY `IDX_F6372AE2930D2644` (`composite_payment_id`),
  KEY `IDX_F6372AE2166D1F9C` (`project_id`),
  KEY `IDX_F6372AE29523AA8A` (`musician_id`),
  KEY `IDX_F6372AE2166D1F9C9523AA8A` (`project_id`,`musician_id`),
  KEY `IDX_F6372AE28A034ED2` (`balance_documents_folder_id`),
  CONSTRAINT `FK_F6372AE2166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_F6372AE2166D1F9C9523AA8A` FOREIGN KEY (`project_id`, `musician_id`) REFERENCES `ProjectParticipants` (`project_id`, `musician_id`),
  CONSTRAINT `FK_F6372AE2443707B0166D1F9C9523AA8AD151D1BF` FOREIGN KEY (`field_id`, `project_id`, `musician_id`, `receivable_key`) REFERENCES `ProjectParticipantFieldsData` (`field_id`, `project_id`, `musician_id`, `option_key`),
  CONSTRAINT `FK_F6372AE2443707B0D151D1BF` FOREIGN KEY (`field_id`, `receivable_key`) REFERENCES `ProjectParticipantFieldsDataOptions` (`field_id`, `key`),
  CONSTRAINT `FK_F6372AE28A034ED2` FOREIGN KEY (`balance_documents_folder_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_F6372AE2930D2644` FOREIGN KEY (`composite_payment_id`) REFERENCES `CompositePayments` (`id`),
  CONSTRAINT `FK_F6372AE29523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectPayments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectPayments` WRITE;
/*!40000 ALTER TABLE `ProjectPayments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectPayments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ProjectWebPages`
--

DROP TABLE IF EXISTS `ProjectWebPages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectWebPages` (
  `article_id` int(11) NOT NULL DEFAULT -1,
  `article_name` varchar(128) NOT NULL DEFAULT '',
  `category_id` int(11) NOT NULL DEFAULT -1,
  `priority` int(11) NOT NULL DEFAULT -1,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`article_id`),
  UNIQUE KEY `UNIQ_EB77064F166D1F9C7294869C` (`project_id`,`article_id`),
  KEY `IDX_EB77064F166D1F9C` (`project_id`),
  CONSTRAINT `FK_EB77064F166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectWebPages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ProjectWebPages` WRITE;
/*!40000 ALTER TABLE `ProjectWebPages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectWebPages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Projects`
--

DROP TABLE IF EXISTS `Projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Projects` (
  `year` int(10) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `type` enum('temporary','permanent','template') NOT NULL DEFAULT 'temporary',
  `mailing_list_id` varchar(128) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `registration_start_date` datetime(6) DEFAULT NULL,
  `registration_deadline` datetime(6) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `financial_balance_documents_storage_id` int(11) DEFAULT NULL,
  `registration_calendar_event_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A5E5D1F25E237E06` (`name`),
  UNIQUE KEY `UNIQ_A5E5D1F214CA24B1` (`financial_balance_documents_storage_id`),
  UNIQUE KEY `UNIQ_A5E5D1F2CCE7523B` (`registration_calendar_event_id`),
  CONSTRAINT `FK_A5E5D1F214CA24B1` FOREIGN KEY (`financial_balance_documents_storage_id`) REFERENCES `DatabaseStorages` (`id`),
  CONSTRAINT `FK_A5E5D1F2CCE7523B` FOREIGN KEY (`registration_calendar_event_id`) REFERENCES `ProjectEvents` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Projects`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Projects` WRITE;
/*!40000 ALTER TABLE `Projects` DISABLE KEYS */;
INSERT INTO `Projects` VALUES
(2099,'TestProject2099','temporary',NULL,'2099-01-01 00:00:00.000000','2099-12-31 00:00:00.000000',1,NULL,'2026-10-25 17:29:52.816239','2026-10-25 17:29:53.495398',NULL,1);
/*!40000 ALTER TABLE `Projects` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `SentEmails`
--

DROP TABLE IF EXISTS `SentEmails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SentEmails` (
  `message_id` varchar(256) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `bulk_recipients` longtext NOT NULL,
  `bulk_recipients_hash` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cc` longtext DEFAULT NULL,
  `bcc` longtext DEFAULT NULL,
  `subject` longtext NOT NULL,
  `subject_hash` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `html_body` longtext NOT NULL,
  `html_body_hash` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `project_id` int(11) DEFAULT NULL,
  `reference_id` varchar(256) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `sepa_bulk_transaction_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `IDX_80F49BA0166D1F9C` (`project_id`),
  KEY `IDX_80F49BA01645DEA9` (`reference_id`),
  KEY `IDX_80F49BA0ED6D4895` (`sepa_bulk_transaction_id`),
  CONSTRAINT `FK_80F49BA01645DEA9` FOREIGN KEY (`reference_id`) REFERENCES `SentEmails` (`message_id`),
  CONSTRAINT `FK_80F49BA0166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_80F49BA0ED6D4895` FOREIGN KEY (`sepa_bulk_transaction_id`) REFERENCES `SepaBulkTransactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SentEmails`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `SentEmails` WRITE;
/*!40000 ALTER TABLE `SentEmails` DISABLE KEYS */;
/*!40000 ALTER TABLE `SentEmails` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `SepaBankAccounts`
--

DROP TABLE IF EXISTS `SepaBankAccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SepaBankAccounts` (
  `sequence` int(11) NOT NULL,
  `iban` varchar(2048) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `bic` varchar(2048) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `blz` varchar(2048) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `bank_account_owner` varchar(2048) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `musician_id` int(11) NOT NULL,
  PRIMARY KEY (`musician_id`,`sequence`),
  KEY `IDX_4F1F148B9523AA8A` (`musician_id`),
  CONSTRAINT `FK_4F1F148B9523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SepaBankAccounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `SepaBankAccounts` WRITE;
/*!40000 ALTER TABLE `SepaBankAccounts` DISABLE KEYS */;
INSERT INTO `SepaBankAccounts` VALUES
(1,'DE02700100800030876808','PBNKDEFFXXX','70010080','Musterperson, Max',NULL,'2026-10-25 17:29:52.913152','2026-10-25 17:29:52.912906',1);
/*!40000 ALTER TABLE `SepaBankAccounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `SepaBulkTransactionBalancingData`
--

DROP TABLE IF EXISTS `SepaBulkTransactionBalancingData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SepaBulkTransactionBalancingData` (
  `sepa_bulk_transaction_id` int(11) NOT NULL,
  `database_storage_file_id` int(11) NOT NULL,
  PRIMARY KEY (`sepa_bulk_transaction_id`,`database_storage_file_id`),
  UNIQUE KEY `UNIQ_6EC2B1724D73A4D4` (`database_storage_file_id`),
  KEY `IDX_6EC2B172ED6D4895` (`sepa_bulk_transaction_id`),
  CONSTRAINT `FK_6EC2B1724D73A4D4` FOREIGN KEY (`database_storage_file_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_6EC2B172ED6D4895` FOREIGN KEY (`sepa_bulk_transaction_id`) REFERENCES `SepaBulkTransactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SepaBulkTransactionBalancingData`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `SepaBulkTransactionBalancingData` WRITE;
/*!40000 ALTER TABLE `SepaBulkTransactionBalancingData` DISABLE KEYS */;
/*!40000 ALTER TABLE `SepaBulkTransactionBalancingData` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `SepaBulkTransactionData`
--

DROP TABLE IF EXISTS `SepaBulkTransactionData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SepaBulkTransactionData` (
  `sepa_bulk_transaction_id` int(11) NOT NULL,
  `database_storage_file_id` int(11) NOT NULL,
  PRIMARY KEY (`sepa_bulk_transaction_id`,`database_storage_file_id`),
  UNIQUE KEY `UNIQ_1EBA3E5B4D73A4D4` (`database_storage_file_id`),
  KEY `IDX_1EBA3E5BED6D4895` (`sepa_bulk_transaction_id`),
  CONSTRAINT `FK_1EBA3E5B4D73A4D4` FOREIGN KEY (`database_storage_file_id`) REFERENCES `DatabaseStorageDirEntries` (`id`),
  CONSTRAINT `FK_1EBA3E5BED6D4895` FOREIGN KEY (`sepa_bulk_transaction_id`) REFERENCES `SepaBulkTransactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SepaBulkTransactionData`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `SepaBulkTransactionData` WRITE;
/*!40000 ALTER TABLE `SepaBulkTransactionData` DISABLE KEYS */;
/*!40000 ALTER TABLE `SepaBulkTransactionData` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `SepaBulkTransactions`
--

DROP TABLE IF EXISTS `SepaBulkTransactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SepaBulkTransactions` (
  `submission_deadline` datetime(6) NOT NULL,
  `submit_date` datetime(6) DEFAULT NULL,
  `due_date` datetime(6) NOT NULL,
  `submission_event_uri` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object URI',
  `submission_event_uid` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object UID',
  `submission_task_uri` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object URI',
  `submission_task_uid` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object UID',
  `due_event_uri` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object URI',
  `due_event_uid` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object UID',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `sepa_transaction` enum('debit_note','bank_transfer') NOT NULL,
  `pre_notification_deadline` datetime(6) DEFAULT NULL,
  `pre_notification_event_uri` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object URI',
  `pre_notification_event_uid` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object UID',
  `pre_notification_task_uri` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object URI',
  `pre_notification_task_uid` varchar(256) DEFAULT NULL COMMENT 'Cloud Calendar Object UID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SepaBulkTransactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `SepaBulkTransactions` WRITE;
/*!40000 ALTER TABLE `SepaBulkTransactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SepaBulkTransactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `SepaDebitMandates`
--

DROP TABLE IF EXISTS `SepaDebitMandates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SepaDebitMandates` (
  `sequence` int(11) NOT NULL,
  `mandate_reference` varchar(35) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `non_recurring` tinyint(4) NOT NULL,
  `mandate_date` datetime(6) DEFAULT NULL,
  `pre_notification_calendar_days` int(11) NOT NULL DEFAULT 14,
  `pre_notification_business_days` int(11) DEFAULT NULL,
  `last_used_date` datetime(6) DEFAULT NULL,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `musician_id` int(11) NOT NULL,
  `bank_account_sequence` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `written_mandate_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`musician_id`,`sequence`),
  UNIQUE KEY `UNIQ_1C50029D0BE4741` (`mandate_reference`),
  UNIQUE KEY `UNIQ_1C500299523AA8A5286D72B166D1F9C` (`musician_id`,`sequence`,`project_id`),
  UNIQUE KEY `UNIQ_1C50029D26EB11F` (`written_mandate_id`),
  KEY `IDX_1C500299523AA8A` (`musician_id`),
  KEY `IDX_1C500299523AA8A2301E184` (`musician_id`,`bank_account_sequence`),
  KEY `IDX_1C50029166D1F9C` (`project_id`),
  KEY `IDX_1C500299523AA8A2301E184166D1F9C` (`musician_id`,`bank_account_sequence`,`project_id`),
  CONSTRAINT `FK_1C50029166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `Projects` (`id`),
  CONSTRAINT `FK_1C500299523AA8A` FOREIGN KEY (`musician_id`) REFERENCES `Musicians` (`id`),
  CONSTRAINT `FK_1C500299523AA8A2301E184` FOREIGN KEY (`musician_id`, `bank_account_sequence`) REFERENCES `SepaBankAccounts` (`musician_id`, `sequence`),
  CONSTRAINT `FK_1C50029D26EB11F` FOREIGN KEY (`written_mandate_id`) REFERENCES `DatabaseStorageDirEntries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SepaDebitMandates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `SepaDebitMandates` WRITE;
/*!40000 ALTER TABLE `SepaDebitMandates` DISABLE KEYS */;
/*!40000 ALTER TABLE `SepaDebitMandates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `TableFieldTranslations`
--

DROP TABLE IF EXISTS `TableFieldTranslations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TableFieldTranslations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` varchar(8) NOT NULL,
  `object_class` varchar(191) NOT NULL,
  `field` varchar(32) NOT NULL,
  `foreign_key` varchar(64) NOT NULL,
  `content` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lookup_unique_idx` (`locale`,`object_class`,`field`,`foreign_key`),
  KEY `translations_lookup_idx` (`locale`,`object_class`,`foreign_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TableFieldTranslations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `TableFieldTranslations` WRITE;
/*!40000 ALTER TABLE `TableFieldTranslations` DISABLE KEYS */;
INSERT INTO `TableFieldTranslations` VALUES
(1,'de_DE','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument','name','1','Violine'),
(2,'de_DE','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\Instrument','name','4','Kontrabass'),
(3,'de_DE','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily','family','1','Streicher'),
(4,'de_DE','OCA\\CAFEVDB\\Database\\Doctrine\\ORM\\Entities\\InstrumentFamily','family','2','Saiten');
/*!40000 ALTER TABLE `TableFieldTranslations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `TaxExemptionItems`
--

DROP TABLE IF EXISTS `TaxExemptionItems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TaxExemptionItems` (
  `tax_exemption_notice_id` int(11) NOT NULL,
  `taxation_statutory_source_id` int(11) NOT NULL,
  PRIMARY KEY (`tax_exemption_notice_id`,`taxation_statutory_source_id`),
  KEY `IDX_9D0F193734E7630B` (`tax_exemption_notice_id`),
  KEY `IDX_9D0F193766FAD11` (`taxation_statutory_source_id`),
  CONSTRAINT `FK_9D0F193734E7630B` FOREIGN KEY (`tax_exemption_notice_id`) REFERENCES `TaxExemptionNotices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9D0F193766FAD11` FOREIGN KEY (`taxation_statutory_source_id`) REFERENCES `TaxationStatutorySources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TaxExemptionItems`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `TaxExemptionItems` WRITE;
/*!40000 ALTER TABLE `TaxExemptionItems` DISABLE KEYS */;
/*!40000 ALTER TABLE `TaxExemptionItems` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `TaxExemptionNotices`
--

DROP TABLE IF EXISTS `TaxExemptionNotices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TaxExemptionNotices` (
  `assessment_period_start` int(11) NOT NULL,
  `assessment_period_end` int(11) NOT NULL,
  `tax_office` varchar(256) NOT NULL,
  `tax_number` varchar(256) NOT NULL,
  `date_issued` datetime(6) DEFAULT NULL,
  `beneficiary_purpose` varchar(4096) NOT NULL,
  `membership_fees_are_donations` tinyint(4) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  `written_notice_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6417EA3735D82D9` (`written_notice_id`),
  CONSTRAINT `FK_6417EA3735D82D9` FOREIGN KEY (`written_notice_id`) REFERENCES `DatabaseStorageDirEntries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TaxExemptionNotices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `TaxExemptionNotices` WRITE;
/*!40000 ALTER TABLE `TaxExemptionNotices` DISABLE KEYS */;
/*!40000 ALTER TABLE `TaxExemptionNotices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `TaxationStatutorySources`
--

DROP TABLE IF EXISTS `TaxationStatutorySources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TaxationStatutorySources` (
  `tax_type` enum('corporate income tax','sales tax','trade tax','VAT','insurance tax') NOT NULL DEFAULT 'corporate income tax',
  `rate` decimal(2,2) unsigned NOT NULL DEFAULT 0.00,
  `country` char(2) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `law` varchar(255) NOT NULL,
  `hint` varchar(1024) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleted` datetime(6) DEFAULT NULL,
  `created` datetime(6) DEFAULT curtime(),
  `updated` datetime(6) DEFAULT curtime(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8F39BDDD905158D1C0B552F` (`tax_type`,`law`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TaxationStatutorySources`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `TaxationStatutorySources` WRITE;
/*!40000 ALTER TABLE `TaxationStatutorySources` DISABLE KEYS */;
/*!40000 ALTER TABLE `TaxationStatutorySources` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `TranslationKeys`
--

DROP TABLE IF EXISTS `TranslationKeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TranslationKeys` (
  `phrase` longtext NOT NULL COMMENT 'Keyword to be translated. Normally the untranslated text in locale en_US, but could be any unique tag',
  `phrase_hash` char(32) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F15EDA495A875D0C` (`phrase_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TranslationKeys`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `TranslationKeys` WRITE;
/*!40000 ALTER TABLE `TranslationKeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `TranslationKeys` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `TranslationLocations`
--

DROP TABLE IF EXISTS `TranslationLocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TranslationLocations` (
  `file` varchar(766) NOT NULL,
  `line` int(11) NOT NULL,
  `translation_key_id` int(11) NOT NULL,
  PRIMARY KEY (`translation_key_id`,`file`,`line`),
  KEY `IDX_F23942BBD07ED992` (`translation_key_id`),
  CONSTRAINT `FK_F23942BBD07ED992` FOREIGN KEY (`translation_key_id`) REFERENCES `TranslationKeys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TranslationLocations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `TranslationLocations` WRITE;
/*!40000 ALTER TABLE `TranslationLocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `TranslationLocations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `Translations`
--

DROP TABLE IF EXISTS `Translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Translations` (
  `locale` char(5) NOT NULL COMMENT 'Locale for translation, .e.g. en_US',
  `translation` varchar(1024) NOT NULL,
  `translation_key_id` int(11) NOT NULL,
  PRIMARY KEY (`translation_key_id`,`locale`),
  KEY `IDX_DE86017FD07ED992` (`translation_key_id`),
  CONSTRAINT `FK_DE86017FD07ED992` FOREIGN KEY (`translation_key_id`) REFERENCES `TranslationKeys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Translations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `Translations` WRITE;
/*!40000 ALTER TABLE `Translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Translations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `WebBrowserHistoryData`
--

DROP TABLE IF EXISTS `WebBrowserHistoryData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `WebBrowserHistoryData` (
  `hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `data` longblob NOT NULL COMMENT 'JSON encrypted',
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WebBrowserHistoryData`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `WebBrowserHistoryData` WRITE;
/*!40000 ALTER TABLE `WebBrowserHistoryData` DISABLE KEYS */;
/*!40000 ALTER TABLE `WebBrowserHistoryData` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `WebBrowserHistoryEntries`
--

DROP TABLE IF EXISTS `WebBrowserHistoryEntries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `WebBrowserHistoryEntries` (
  `path` varchar(32768) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `state_id` int(11) NOT NULL,
  `data_hash` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `position` int(10) unsigned NOT NULL,
  `window_history_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{}' CHECK (json_valid(`window_history_state`)),
  PRIMARY KEY (`state_id`,`position`),
  KEY `IDX_2059233F5D83CC1` (`state_id`),
  KEY `IDX_2059233F6AF7A95A` (`data_hash`),
  CONSTRAINT `FK_2059233F5D83CC1` FOREIGN KEY (`state_id`) REFERENCES `WebBrowserHistoryStates` (`id`),
  CONSTRAINT `FK_2059233F6AF7A95A` FOREIGN KEY (`data_hash`) REFERENCES `WebBrowserHistoryData` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WebBrowserHistoryEntries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `WebBrowserHistoryEntries` WRITE;
/*!40000 ALTER TABLE `WebBrowserHistoryEntries` DISABLE KEYS */;
/*!40000 ALTER TABLE `WebBrowserHistoryEntries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `WebBrowserHistoryStates`
--

DROP TABLE IF EXISTS `WebBrowserHistoryStates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `WebBrowserHistoryStates` (
  `user_id` varchar(256) NOT NULL,
  `created` datetime(6) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `updated` datetime(6) DEFAULT curtime(),
  `pos_state_id` int(11) DEFAULT NULL,
  `pos_position` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_FD38B3C7A76ED395B23DB7B8` (`user_id`,`created`),
  KEY `IDX_FD38B3C74CDC76F1F28AEC5` (`pos_state_id`,`pos_position`),
  CONSTRAINT `FK_FD38B3C74CDC76F1F28AEC5` FOREIGN KEY (`pos_state_id`, `pos_position`) REFERENCES `WebBrowserHistoryEntries` (`state_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WebBrowserHistoryStates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `WebBrowserHistoryStates` WRITE;
/*!40000 ALTER TABLE `WebBrowserHistoryStates` DISABLE KEYS */;
/*!40000 ALTER TABLE `WebBrowserHistoryStates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `instrument_instrument_family`
--

DROP TABLE IF EXISTS `instrument_instrument_family`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `instrument_instrument_family` (
  `instrument_id` int(11) NOT NULL,
  `instrument_family_id` int(11) NOT NULL,
  PRIMARY KEY (`instrument_id`,`instrument_family_id`),
  KEY `IDX_2C15852ACF11D9C` (`instrument_id`),
  KEY `IDX_2C15852AB4F8CF5C` (`instrument_family_id`),
  CONSTRAINT `FK_2C15852AB4F8CF5C` FOREIGN KEY (`instrument_family_id`) REFERENCES `InstrumentFamilies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2C15852ACF11D9C` FOREIGN KEY (`instrument_id`) REFERENCES `Instruments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instrument_instrument_family`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `instrument_instrument_family` WRITE;
/*!40000 ALTER TABLE `instrument_instrument_family` DISABLE KEYS */;
INSERT INTO `instrument_instrument_family` VALUES
(1,1),
(1,2),
(2,1),
(2,2),
(3,1),
(3,2),
(4,1),
(4,2),
(5,4),
(5,5),
(6,4),
(6,5),
(7,4),
(7,5),
(8,4),
(8,5),
(9,4),
(9,5),
(10,4),
(10,5),
(11,4),
(11,5),
(12,4),
(12,6),
(13,4),
(13,6),
(14,4),
(14,6),
(15,4),
(15,6),
(16,4),
(16,6),
(17,2),
(17,3),
(18,2),
(18,3),
(19,7),
(20,7),
(21,7),
(22,7),
(23,7),
(24,7),
(25,8),
(26,8),
(27,8),
(28,8),
(29,8),
(30,8),
(31,10),
(32,10);
/*!40000 ALTER TABLE `instrument_instrument_family` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping routines for database 'cafevdb'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `BIN2UUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE DEFINER=`phpunit`@`%` FUNCTION `BIN2UUID`(`b` BINARY(16)) RETURNS char(36) CHARSET ascii COLLATE ascii_general_ci
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN BIN_TO_UUID(b, 0);
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `BIN_TO_UUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE DEFINER=`phpunit`@`%` FUNCTION `BIN_TO_UUID`(`b` BINARY(16), `f` BOOLEAN) RETURNS char(36) CHARSET ascii COLLATE ascii_general_ci
    NO SQL
    DETERMINISTIC
BEGIN
  DECLARE hexStr CHAR(32);
  SET hexStr = HEX(b);
  RETURN LOWER(CONCAT(
           IF(f,SUBSTR(hexStr, 9, 8),SUBSTR(hexStr, 1, 8)), '-',
           IF(f,SUBSTR(hexStr, 5, 4),SUBSTR(hexStr, 9, 4)), '-',
           IF(f,SUBSTR(hexStr, 1, 4),SUBSTR(hexStr, 13, 4)), '-',
           SUBSTR(hexStr, 17, 4), '-',
           SUBSTR(hexStr, 21)
        ));
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `EXPLODE` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE DEFINER=`phpunit`@`%` FUNCTION `EXPLODE`(`delimiters` VARCHAR(12), `inputString` TEXT, `position` INT) RETURNS text CHARSET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci
    NO SQL
    DETERMINISTIC
RETURN
    REPLACE(
      SUBSTRING(
        SUBSTRING_INDEX(`inputString`, `delimiters`, `position`),
        LENGTH(SUBSTRING_INDEX(`inputString`, `delimiters`, `position` - 1)) + 1
      ),
      `delimiters`,
      ''
    )
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `UUID2BIN` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE DEFINER=`phpunit`@`%` FUNCTION `UUID2BIN`(`uuid` CHAR(36)) RETURNS binary(16)
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN UUID_TO_BIN(uuid, 0);
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `UUID_TO_BIN` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE DEFINER=`phpunit`@`%` FUNCTION `UUID_TO_BIN`(`uuid` CHAR(36), `f` BOOLEAN) RETURNS binary(16)
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN UNHEX(CONCAT(
  IF(f,SUBSTRING(uuid, 15, 4),SUBSTRING(uuid, 1, 8)),
  SUBSTRING(uuid, 10, 4),
  IF(f,SUBSTRING(uuid, 1, 8),SUBSTRING(uuid, 15, 4)),
  SUBSTRING(uuid, 20, 4),
  SUBSTRING(uuid, 25))
  );
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-25 18:29:53
