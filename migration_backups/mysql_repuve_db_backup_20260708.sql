-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: repuve_db
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `arco_infraestructura`
--

DROP TABLE IF EXISTS `arco_infraestructura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arco_infraestructura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arco_id` int NOT NULL,
  `infraestructura_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_arco_infraestructura` (`arco_id`,`infraestructura_id`),
  KEY `idx_arco_infra_arco` (`arco_id`),
  KEY `idx_arco_infra_infra` (`infraestructura_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arco_infraestructura`
--

LOCK TABLES `arco_infraestructura` WRITE;
/*!40000 ALTER TABLE `arco_infraestructura` DISABLE KEYS */;
INSERT INTO `arco_infraestructura` VALUES (2,33,1,'2026-05-25 06:09:38'),(3,5,2,'2026-05-27 07:17:10'),(4,31,2,'2026-05-27 07:17:10'),(6,22,3,'2026-05-31 09:33:53');
/*!40000 ALTER TABLE `arco_infraestructura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `arco_material`
--

DROP TABLE IF EXISTS `arco_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arco_material` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arco_id` int NOT NULL,
  `material_id` int NOT NULL,
  `cantidad` float NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `arco_id` (`arco_id`)
) ENGINE=InnoDB AUTO_INCREMENT=754 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arco_material`
--

LOCK TABLES `arco_material` WRITE;
/*!40000 ALTER TABLE `arco_material` DISABLE KEYS */;
INSERT INTO `arco_material` VALUES (20,6,11,2,NULL,NULL),(21,6,13,2,NULL,NULL),(22,6,12,2,NULL,NULL),(23,6,9,1,NULL,NULL),(24,6,10,1,NULL,NULL),(25,6,8,1,NULL,NULL),(26,6,16,10,NULL,NULL),(27,6,15,5,NULL,NULL),(28,6,14,5,NULL,NULL),(29,7,11,2,NULL,NULL),(30,7,13,2,NULL,NULL),(31,7,15,4,NULL,NULL),(32,7,14,4,NULL,NULL),(33,7,16,14,NULL,NULL),(34,7,8,1,NULL,NULL),(35,7,12,2,NULL,NULL),(36,7,10,1,NULL,NULL),(37,7,9,1,NULL,NULL),(239,3,7,2,NULL,'9E0F44CPAJFFE7B'),(240,3,10,1,NULL,'370-20-12-0287'),(241,3,7,1,NULL,'8B0159EAAJ3FAD7'),(242,3,10,1,NULL,'370-11-41-0260'),(243,3,8,1,NULL,''),(244,3,9,1,NULL,''),(245,3,11,1,NULL,''),(246,3,11,1,NULL,''),(247,3,11,1,NULL,''),(248,3,11,1,NULL,''),(261,31,11,1,NULL,NULL),(262,31,11,1,NULL,NULL),(263,31,15,4,NULL,NULL),(264,31,14,4,NULL,NULL),(265,31,8,1,NULL,NULL),(266,31,10,1,NULL,NULL),(267,31,13,1,NULL,'5661015500079'),(268,31,13,1,NULL,'5661015500080'),(269,31,9,1,NULL,'5661001000009'),(284,21,11,1,NULL,NULL),(285,21,13,1,NULL,NULL),(286,21,13,1,NULL,NULL),(287,21,8,1,NULL,NULL),(288,21,11,1,NULL,NULL),(289,21,8,1,NULL,NULL),(290,21,10,1,NULL,NULL),(322,33,11,1,NULL,NULL),(323,33,11,1,NULL,NULL),(324,33,18,15,NULL,NULL),(325,33,16,14,NULL,NULL),(326,33,8,1,NULL,NULL),(327,33,19,1,NULL,NULL),(328,33,10,1,NULL,'370-14-22-0101'),(329,33,24,1,NULL,NULL),(330,33,20,1,NULL,NULL),(344,34,11,1,NULL,NULL),(345,34,11,1,NULL,NULL),(346,34,13,1,NULL,NULL),(347,34,13,1,NULL,NULL),(348,34,15,2.5,NULL,NULL),(349,34,14,2.5,NULL,NULL),(350,34,16,16,NULL,NULL),(351,34,25,1,NULL,NULL),(352,34,25,1,NULL,NULL),(353,34,26,1,NULL,NULL),(354,34,20,1,NULL,NULL),(355,34,21,1,NULL,NULL),(356,34,12,1,NULL,NULL),(385,5,10,1,NULL,NULL),(386,5,9,1,NULL,'PS306GF-UPS-15A2408MX024'),(387,5,11,1,NULL,NULL),(388,5,11,1,NULL,NULL),(389,5,8,1,NULL,NULL),(390,5,16,18,NULL,NULL),(391,5,11,1,NULL,NULL),(392,5,11,1,NULL,NULL),(393,5,13,1,NULL,'MX05452857'),(394,5,13,1,NULL,NULL),(395,5,15,2,NULL,NULL),(396,5,14,2,NULL,NULL),(397,5,12,1,NULL,NULL),(398,5,12,1,NULL,NULL),(399,35,11,1,NULL,NULL),(400,35,11,1,NULL,NULL),(401,35,11,1,NULL,NULL),(402,35,11,1,NULL,NULL),(403,35,13,1,NULL,NULL),(404,35,13,1,NULL,NULL),(405,35,15,2.5,NULL,NULL),(406,35,14,2.5,NULL,NULL),(407,35,16,35,NULL,NULL),(408,35,21,1,NULL,NULL),(409,35,20,1,NULL,NULL),(410,35,10,1,NULL,NULL),(411,35,12,1,NULL,NULL),(412,35,12,1,NULL,NULL),(413,35,9,1,NULL,NULL),(472,36,25,1,NULL,NULL),(473,36,25,1,NULL,NULL),(474,36,11,1,NULL,NULL),(475,36,11,1,NULL,NULL),(476,36,18,10,NULL,NULL),(477,36,8,1,NULL,NULL),(478,36,10,1,NULL,NULL),(479,36,19,1,NULL,NULL),(480,36,16,15,NULL,NULL),(481,32,11,1,NULL,NULL),(482,32,11,1,NULL,NULL),(483,32,11,1,NULL,NULL),(484,32,11,1,NULL,NULL),(485,32,18,8,NULL,NULL),(486,32,16,30,NULL,NULL),(487,32,8,1,NULL,NULL),(488,32,10,1,NULL,'370-12-38-0199'),(489,32,10,1,NULL,'370-19-49-0864'),(490,32,28,1,NULL,NULL),(491,32,7,1,NULL,'8B0159EAAJ81B1A'),(492,32,7,1,NULL,'8B0159EAAJ3FF94'),(493,32,17,1,NULL,NULL),(494,32,9,1,NULL,NULL),(497,22,13,1,NULL,NULL),(498,22,13,1,NULL,NULL),(508,11,9,1,NULL,NULL),(509,11,10,1,NULL,NULL),(510,11,8,1,NULL,NULL),(511,11,16,18,NULL,NULL),(512,11,17,1,NULL,NULL),(513,11,11,1,NULL,NULL),(514,11,11,1,NULL,NULL),(515,11,13,1,NULL,NULL),(516,11,13,1,NULL,NULL),(517,11,12,1,NULL,NULL),(518,11,12,1,NULL,NULL),(519,11,17,1,NULL,NULL),(520,11,14,2.5,NULL,NULL),(521,11,15,2.5,NULL,NULL),(555,18,11,1,NULL,NULL),(556,18,11,1,NULL,NULL),(557,18,13,1,NULL,NULL),(558,18,13,1,NULL,NULL),(559,18,8,1,NULL,NULL),(560,18,12,1,NULL,NULL),(561,18,12,1,NULL,NULL),(562,18,10,1,NULL,NULL),(563,18,9,1,NULL,NULL),(564,18,16,15,NULL,NULL),(565,18,14,2.5,NULL,NULL),(566,18,15,2.5,NULL,NULL),(567,18,30,4,NULL,NULL),(568,38,11,1,NULL,NULL),(569,38,11,1,NULL,NULL),(570,38,11,1,NULL,NULL),(571,38,11,1,NULL,NULL),(572,38,11,1,NULL,NULL),(573,38,11,1,NULL,NULL),(574,38,20,1,NULL,NULL),(575,38,10,1,NULL,NULL),(576,38,26,1,NULL,NULL),(577,38,18,20,NULL,NULL),(578,38,9,1,NULL,NULL),(579,38,29,1,NULL,NULL),(580,38,30,10,NULL,NULL),(581,37,11,1,NULL,NULL),(582,37,11,1,NULL,NULL),(583,37,13,1,NULL,NULL),(584,37,13,1,NULL,NULL),(585,37,15,1.5,NULL,NULL),(586,37,14,1.5,NULL,NULL),(587,37,16,15,NULL,NULL),(588,37,25,1,NULL,NULL),(589,37,25,1,NULL,NULL),(590,37,29,1,NULL,NULL),(591,37,20,1,NULL,NULL),(592,37,12,1,NULL,NULL),(593,37,12,1,NULL,NULL),(594,37,26,1,NULL,NULL),(595,5,13,1,NULL,'5661015500076'),(596,5,13,1,NULL,'5661015500077'),(631,39,13,1,NULL,NULL),(632,39,13,1,NULL,NULL),(633,39,11,1,NULL,NULL),(634,39,11,1,NULL,NULL),(635,39,15,2.5,NULL,NULL),(636,39,14,2.5,NULL,NULL),(637,39,16,14,NULL,NULL),(638,39,29,1,NULL,NULL),(639,39,12,1,NULL,NULL),(640,39,12,1,NULL,NULL),(642,39,9,1,NULL,NULL),(643,39,30,4,NULL,NULL),(644,23,11,1,NULL,NULL),(645,23,11,1,NULL,NULL),(646,23,13,1,NULL,NULL),(647,23,13,1,NULL,NULL),(648,23,16,30,NULL,NULL),(649,23,20,1,NULL,NULL),(650,23,29,1,NULL,NULL),(651,23,26,1,NULL,NULL),(652,23,30,8,NULL,NULL),(653,23,18,10,NULL,NULL),(654,40,20,1,NULL,NULL),(655,40,12,1,NULL,NULL),(656,40,12,1,NULL,NULL),(657,40,13,1,NULL,NULL),(658,40,13,1,NULL,NULL),(659,40,15,2.5,NULL,NULL),(660,40,14,2.5,NULL,NULL),(661,40,16,13,NULL,NULL),(662,40,10,1,NULL,NULL),(663,40,29,1,NULL,NULL),(664,40,9,1,NULL,NULL),(665,41,13,1,NULL,NULL),(666,41,13,1,NULL,NULL),(667,41,11,1,NULL,NULL),(668,41,11,1,NULL,NULL),(669,41,15,2.5,NULL,NULL),(670,41,14,2.5,NULL,NULL),(671,41,16,15,NULL,NULL),(672,41,29,1,NULL,NULL),(673,41,12,1,NULL,NULL),(674,41,12,1,NULL,NULL),(675,41,20,1,NULL,NULL),(676,41,9,1,NULL,NULL),(677,41,30,20,NULL,NULL),(697,22,11,1,NULL,NULL),(698,22,11,1,NULL,NULL),(701,22,14,4,NULL,NULL),(702,22,15,4,NULL,NULL),(703,22,26,1,NULL,NULL),(704,22,9,1,NULL,NULL),(705,22,16,18,NULL,NULL),(706,22,17,1,NULL,NULL),(707,22,8,1,NULL,NULL),(708,22,12,1,NULL,NULL),(709,22,12,1,NULL,NULL),(710,22,30,6,NULL,NULL),(711,39,26,1,NULL,NULL),(712,39,20,1,NULL,NULL),(713,42,13,1,NULL,NULL),(714,42,13,1,NULL,NULL),(715,42,11,1,NULL,NULL),(716,42,11,1,NULL,NULL),(717,42,15,6,NULL,NULL),(718,42,14,6,NULL,NULL),(719,42,17,1,NULL,NULL),(721,42,12,1,NULL,NULL),(722,42,12,1,NULL,NULL),(723,42,9,1,NULL,NULL),(724,42,30,6,NULL,NULL),(725,42,16,16,NULL,NULL),(726,42,31,1,NULL,NULL),(727,43,11,1,NULL,NULL),(728,43,11,1,NULL,NULL),(729,43,13,1,NULL,NULL),(730,43,13,1,NULL,NULL),(731,43,15,5,NULL,NULL),(732,43,14,5,NULL,NULL),(733,43,16,17,NULL,NULL),(734,43,31,1,NULL,NULL),(735,43,17,1,NULL,NULL),(736,43,12,1,NULL,NULL),(737,43,12,1,NULL,NULL),(738,43,26,1,NULL,NULL),(739,43,30,6,NULL,NULL),(740,44,13,1,NULL,NULL),(741,44,13,1,NULL,NULL),(742,44,11,1,NULL,NULL),(743,44,11,1,NULL,NULL),(744,44,15,3,NULL,NULL),(745,44,14,3,NULL,NULL),(746,44,16,17,NULL,NULL),(747,44,31,1,NULL,NULL),(748,44,17,1,NULL,NULL),(749,44,12,1,NULL,NULL),(750,44,12,1,NULL,NULL),(751,44,26,1,NULL,NULL),(752,44,9,1,NULL,NULL),(753,44,30,6,NULL,NULL);
/*!40000 ALTER TABLE `arco_material` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `arcos`
--

DROP TABLE IF EXISTS `arcos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arcos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ubicacion_id` int NOT NULL,
  `fecha_instalacion` datetime DEFAULT NULL,
  `lng` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lat` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ubicacion_id` (`ubicacion_id`),
  CONSTRAINT `arcos_ibfk_1` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `arcos`
--

LOCK TABLES `arcos` WRITE;
/*!40000 ALTER TABLE `arcos` DISABLE KEYS */;
INSERT INTO `arcos` VALUES (3,'Cinca',3,'2019-10-29 00:00:00','-99.475129','17.507284'),(5,'Bulevar de las Naciones',4,'2025-12-20 04:00:00','-99.802755','16.791057'),(6,'Salida Taxco',5,'2025-12-03 00:00:00',NULL,NULL),(7,'Entrada Taxco',5,'2025-12-02 00:00:00',NULL,NULL),(11,'Cima',4,'2026-02-13 00:00:00',' -99.859994','16.877297'),(18,'Entrada Iguala',6,'2026-03-27 00:00:00','-99.501801','18.314115'),(21,'Libramiento Tixtla',3,'2022-10-12 00:00:00','-99.501286','17.592812'),(22,'Horquetas',4,'2026-03-21 00:00:00','-99.574117','16.755672'),(23,'San Juan de los llanos',9,'2026-04-01 00:00:00','-98.446532,3a','16.6832135'),(31,'Cayaco - Las Cruces',4,'2017-06-17 21:04:00','99.8103806,3a','16.8651166'),(32,'Lazaro Cardenas',3,'2019-12-06 00:13:00','-99.515489','17.583102'),(33,'Hospital',3,'2021-01-16 01:34:00','-99.522409','17.605768'),(34,'Tierras Pietras - Tixtla',3,'2021-09-29 16:04:00','-99.515833','17.603447'),(35,'Autopista Chilpancingo - Acapulco',3,'2021-11-11 16:30:00','-99.481361','17.513030'),(36,'Chichihualco',3,'2023-11-16 15:40:00','-99.5192992','17.5917693'),(37,'Amojileca',3,'2021-09-29 15:04:00','-99.525065','17.5551348'),(38,'Renacimiento',4,'2024-04-30 02:35:00','-99.8306721','16.8981042'),(39,'OMETEPEC-IGUALAPA',9,'2021-02-20 16:40:00','98.428594','16.708102'),(40,'OMETEPEC-LAS IGUANAS',9,'2021-10-21 15:30:00','-98.4242755,3a','16.6809867'),(41,'OMETEPEC-XOCHIS',9,'2021-10-22 14:40:00','-98.3869374,3a','16.6987012'),(42,'AEROPUERTO-ZIHUATANEJO',7,'2004-10-04 15:00:00','-101.52811409','17.647651'),(43,'LAZARO CARDENAS-IXTAPA',7,'2024-10-03 14:04:00','-101.5748369','17.6593304'),(44,'ZIHUATANEJO-MIRADOR',7,'2024-10-03 15:04:00','101.6021398','17.6785773');
/*!40000 ALTER TABLE `arcos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bitacora_checklist`
--

DROP TABLE IF EXISTS `bitacora_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora_checklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bitacora_id` int NOT NULL,
  `concepto_id` int NOT NULL,
  `realizado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_bitacora_id` (`bitacora_id`),
  KEY `idx_concepto_id` (`concepto_id`),
  CONSTRAINT `fk_checklist_bitacora` FOREIGN KEY (`bitacora_id`) REFERENCES `bitacoras_arco` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_checklist_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `checklist_conceptos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora_checklist`
--

LOCK TABLES `bitacora_checklist` WRITE;
/*!40000 ALTER TABLE `bitacora_checklist` DISABLE KEYS */;
INSERT INTO `bitacora_checklist` VALUES (1,1,1,1),(2,1,2,1),(3,1,3,1),(4,1,4,1),(5,1,5,1),(6,1,7,1),(7,1,8,1),(8,1,9,1),(9,1,10,1),(10,1,11,1),(21,3,1,1),(22,3,2,1),(23,3,3,1),(24,3,4,1),(25,3,7,1),(26,3,8,1),(27,3,9,1),(28,3,10,1),(29,3,11,1),(30,4,1,1),(31,4,2,1),(32,4,6,1),(33,4,9,1);
/*!40000 ALTER TABLE `bitacora_checklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bitacoras_arco`
--

DROP TABLE IF EXISTS `bitacoras_arco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacoras_arco` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arco_id` int NOT NULL,
  `encargado` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_general_ci,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_arco_id` (`arco_id`),
  CONSTRAINT `fk_bitacora_arco` FOREIGN KEY (`arco_id`) REFERENCES `arcos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacoras_arco`
--

LOCK TABLES `bitacoras_arco` WRITE;
/*!40000 ALTER TABLE `bitacoras_arco` DISABLE KEYS */;
INSERT INTO `bitacoras_arco` VALUES (1,23,'Jose Luis Romero Palacios','Se Realizo La instalación sin poblemas','2026-04-05 15:58:43'),(3,18,'Carlos Chavelaz Gonzalez','','2026-04-05 20:11:39'),(4,22,'luis','lfdjdl','2026-04-26 13:01:17');
/*!40000 ALTER TABLE `bitacoras_arco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_conceptos`
--

DROP TABLE IF EXISTS `checklist_conceptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_conceptos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_conceptos`
--

LOCK TABLES `checklist_conceptos` WRITE;
/*!40000 ALTER TABLE `checklist_conceptos` DISABLE KEYS */;
INSERT INTO `checklist_conceptos` VALUES (1,'Instalación de Estructura del arco',1),(2,'Instalación de gabinete',1),(3,'Instalación de panel solar y batería',1),(4,'Antena RFID instalada y fijada',1),(5,'Cámara LPR instalada y enfocada',1),(6,'Conexión eléctrica con cable 1+1',1),(7,'Cableado UTP',1),(8,'Prueba de lectura RFID correcta',1),(9,'Prueba de captura de placas correcta',1),(10,'Servidor / envío a plataforma validado',1),(11,'Prueba de energía',1),(12,'Colocación de respaldo UPS',1);
/*!40000 ALTER TABLE `checklist_conceptos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `infraestructura_material`
--

DROP TABLE IF EXISTS `infraestructura_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `infraestructura_material` (
  `id` int NOT NULL AUTO_INCREMENT,
  `infraestructura_id` int NOT NULL,
  `material_id` int NOT NULL,
  `cantidad` float NOT NULL DEFAULT '1',
  `serie` varchar(50) DEFAULT NULL,
  `fecha_instalacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_infra_material_infra` (`infraestructura_id`),
  KEY `idx_infra_material_material` (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `infraestructura_material`
--

LOCK TABLES `infraestructura_material` WRITE;
/*!40000 ALTER TABLE `infraestructura_material` DISABLE KEYS */;
INSERT INTO `infraestructura_material` VALUES (4,1,19,1,NULL,'2026-05-25 00:09:38'),(5,1,8,1,NULL,'2026-05-25 00:09:38'),(6,1,18,10,NULL,'2026-05-25 00:09:38'),(7,1,8,1,NULL,'2026-05-25 00:09:38'),(8,2,20,1,NULL,'2025-06-27 01:16:00'),(9,2,23,1,NULL,'2025-06-27 01:16:00'),(10,2,8,1,NULL,'2025-06-27 01:16:00'),(11,2,8,1,NULL,'2025-06-27 01:16:00'),(12,2,8,1,NULL,'2025-06-27 01:16:00'),(13,2,8,1,NULL,'2025-06-27 01:16:00'),(14,2,8,1,NULL,'2025-06-27 01:16:00'),(25,3,20,1,NULL,'2026-05-31 03:33:53'),(26,3,9,1,'PS306GF-UPS-15A2408MX023','2026-05-31 03:33:53'),(27,3,13,1,NULL,'2026-05-31 03:33:53'),(28,3,13,1,NULL,'2026-05-31 03:33:53'),(29,3,15,2.5,NULL,'2026-05-31 03:33:53'),(30,3,14,2.5,NULL,'2026-05-31 03:33:53'),(31,3,29,1,NULL,'2026-05-31 03:33:53'),(32,3,12,1,NULL,'2026-05-31 03:33:53'),(33,3,12,1,NULL,'2026-05-31 03:33:53'),(34,3,30,5,NULL,'2026-05-31 03:33:53');
/*!40000 ALTER TABLE `infraestructura_material` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `infraestructura_nodos`
--

DROP TABLE IF EXISTS `infraestructura_nodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `infraestructura_nodos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('Puente/Poste','Sitio/Torre') NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `ubicacion_id` int DEFAULT NULL,
  `lat` varchar(30) DEFAULT NULL,
  `lng` varchar(30) DEFAULT NULL,
  `descripcion` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_infra_tipo_nombre` (`tipo`,`nombre`),
  KEY `idx_infra_ubicacion` (`ubicacion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `infraestructura_nodos`
--

LOCK TABLES `infraestructura_nodos` WRITE;
/*!40000 ALTER TABLE `infraestructura_nodos` DISABLE KEYS */;
INSERT INTO `infraestructura_nodos` VALUES (1,'Puente/Poste','Salto Hospital Poste 12',3,'17.605125','-99.520715',NULL,'2026-05-24 23:53:14'),(2,'Sitio/Torre','Cumbres',4,'16.810472','-99.811859',NULL,'2026-05-27 07:17:10'),(3,'Sitio/Torre','Cuartel de Policias',4,'16.755646','-99.572439',NULL,'2026-05-31 09:29:47');
/*!40000 ALTER TABLE `infraestructura_nodos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `infraestructura_revision_evidencias`
--

DROP TABLE IF EXISTS `infraestructura_revision_evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `infraestructura_revision_evidencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `revision_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mimetype` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_infra_revision_evidencias_revision_id` (`revision_id`),
  CONSTRAINT `infraestructura_revision_evidencias_ibfk_1` FOREIGN KEY (`revision_id`) REFERENCES `infraestructura_revisiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `infraestructura_revision_evidencias`
--

LOCK TABLES `infraestructura_revision_evidencias` WRITE;
/*!40000 ALTER TABLE `infraestructura_revision_evidencias` DISABLE KEYS */;
INSERT INTO `infraestructura_revision_evidencias` VALUES (1,4,'1780250109_6a1c75fdd98917.13988036.jpg','image/jpeg','2026-05-31 17:55:09'),(2,4,'1780250109_6a1c75fddf87c4.27906511.jpg','image/jpeg','2026-05-31 17:55:09'),(3,4,'1780250109_6a1c75fde1f7a6.61719002.jpg','image/jpeg','2026-05-31 17:55:09');
/*!40000 ALTER TABLE `infraestructura_revision_evidencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `infraestructura_revision_material`
--

DROP TABLE IF EXISTS `infraestructura_revision_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `infraestructura_revision_material` (
  `id` int NOT NULL AUTO_INCREMENT,
  `revision_id` int NOT NULL,
  `material_id` int NOT NULL,
  `cantidad` float NOT NULL DEFAULT '1',
  `serie` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_infra_rev_mat_revision` (`revision_id`),
  KEY `idx_infra_rev_mat_material` (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `infraestructura_revision_material`
--

LOCK TABLES `infraestructura_revision_material` WRITE;
/*!40000 ALTER TABLE `infraestructura_revision_material` DISABLE KEYS */;
INSERT INTO `infraestructura_revision_material` VALUES (5,4,13,1,''),(6,4,13,1,'');
/*!40000 ALTER TABLE `infraestructura_revision_material` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `infraestructura_revisiones`
--

DROP TABLE IF EXISTS `infraestructura_revisiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `infraestructura_revisiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `infraestructura_id` int NOT NULL,
  `fecha_mantenimiento` datetime NOT NULL,
  `tipo_mantenimiento` enum('Preventivo','Correctivo') NOT NULL DEFAULT 'Correctivo',
  `observaciones` text,
  `tecnico_responsable` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_infra_rev_infra` (`infraestructura_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `infraestructura_revisiones`
--

LOCK TABLES `infraestructura_revisiones` WRITE;
/*!40000 ALTER TABLE `infraestructura_revisiones` DISABLE KEYS */;
INSERT INTO `infraestructura_revisiones` VALUES (4,3,'2026-04-25 03:54:00','Correctivo','Se cambio baterias por medio uso','Carlos Serafin Chavelas Gonzalez','2026-05-31 17:55:09');
/*!40000 ALTER TABLE `infraestructura_revisiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materiales`
--

DROP TABLE IF EXISTS `materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materiales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `medida` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materiales`
--

LOCK TABLES `materiales` WRITE;
/*!40000 ALTER TABLE `materiales` DISABLE KEYS */;
INSERT INTO `materiales` VALUES (7,'Camara LPR','pz','1764729971_camara lpr.png',NULL),(8,'Enlace Ubiquiti M5','pz','1779659842_Enlace Ubiquiti M5.png',NULL),(9,'Switch 5 Puertos Controntolador Solar Wiltek','pz','1764729996_switch 5 puertos panel solar.png',NULL),(10,'SpeedWay 4 Conectores','pz','1764730013_speedway.jpg',NULL),(11,'Antena Yagi RFID 5 Elementos','pz','1780038784_Antena Yagi de 5 Elementos.png',NULL),(12,'Panel Solar Epcom 19V 150W','pz','1764730052_PANEL SOLAR.PNG',NULL),(13,'Bateria Solar','pz','1764730062_bateria.png',NULL),(14,'Cable FotoVoltaico Positivo','m','1764730460_cable fotovoltaico positivo.png',NULL),(15,'Cable FotoVoltaico Negativo','m','1764730481_cable fotovoltaico negativo.png',NULL),(16,'Cable TNC','m','1764731117_Cable TNC.png',NULL),(17,'Gabinete Doble Cerradura','pz','1772599896_Gabinete doble Cerradura.jpg',NULL),(18,'Cable 1+1','m','1772600756_cable 1+1.jpg',NULL),(19,'Gabinete 1 nivel','pz','1779087212_gabinete 1 nivel.png',NULL),(20,'Gabinete con soporte en forma L','pz','1779658323_Gabinete con soporte en forma de L.jpg',NULL),(21,'Enlace Ubiquiti AC','pz','1779659869_Enlace Ubiquiti AC.png',NULL),(22,'Switch 8 Puetos Controlador Solar Wiltek','pz','1779659937_switch 8 puertos.jpg',NULL),(23,'Switch 16 Puertos Wiltek','pz','1779659957_switch 16 puertos.png',NULL),(24,'Switch 5 Puertos TP Link','pz','1779661807_switch 5 puertos TP Link.jpg',NULL),(25,'Controlador Solar POE','pz','1779663102_Controlador Solar POE.png',NULL),(26,'SpeedWay 2 Conectores','pz','1779663580_Speedway 2 Conectores .jpg',NULL),(27,'Switch 24 Puertos cisco','pz','1780024851_switch 24 puertos cisco.jpg',NULL),(28,'Swich 5 puertos HikVision','pz','1780203061_switch 5 puertos Hikvision.jpg',NULL),(29,'Enlace Ubiquiti AC ISO','pz','1780217319_enlace Ubiquiti AC ISO.png',NULL),(30,'UTP Cat 5e Exterior','m','1780217657_Cable UTP Cat 5e Exterior.png',NULL),(31,'Cambium Force 300','pz','1780271136_Enlace Cambium force 300.png',NULL);
/*!40000 ALTER TABLE `materiales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revision_evidencias`
--

DROP TABLE IF EXISTS `revision_evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revision_evidencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `revision_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mimetype` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_revision` (`revision_id`),
  CONSTRAINT `fk_revision` FOREIGN KEY (`revision_id`) REFERENCES `revisiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revision_evidencias`
--

LOCK TABLES `revision_evidencias` WRITE;
/*!40000 ALTER TABLE `revision_evidencias` DISABLE KEYS */;
INSERT INTO `revision_evidencias` VALUES (78,65,'1779077275_6a0a909b51b75.jpg','image/jpeg','2026-05-18 04:07:55',NULL),(79,65,'1779077275_6a0a909b55be9.jpg','image/jpeg','2026-05-18 04:07:55',NULL),(81,67,'1779087638_6a0ab9169530a.jpg','image/jpeg','2026-05-18 07:00:38',NULL),(86,70,'1779865267_6a1696b3ab653.jpg','image/jpeg','2026-05-27 07:01:07',NULL),(87,70,'1779865267_6a1696b3ae675.jpg','image/jpeg','2026-05-27 07:01:07',NULL),(88,70,'1779865267_6a1696b3afed1.jpg','image/jpeg','2026-05-27 07:01:07',NULL),(89,70,'1779865267_6a1696b3b165a.jpg','image/jpeg','2026-05-27 07:01:07',NULL),(90,70,'1779865267_6a1696b3b2ab2.jpg','image/jpeg','2026-05-27 07:01:07',NULL),(96,76,'1780211404_6a1bdecc1bf55.jpg','image/jpeg','2026-05-31 07:10:04',NULL),(97,76,'1780211404_6a1bdecc1d9d9.jpg','image/jpeg','2026-05-31 07:10:04',NULL),(98,76,'1780211404_6a1bdecc1e866.jpg','image/jpeg','2026-05-31 07:10:04',NULL),(99,76,'1780211404_6a1bdecc20319.jpg','image/jpeg','2026-05-31 07:10:04',NULL),(100,76,'1780211404_6a1bdecc21172.jpg','image/jpeg','2026-05-31 07:10:04',NULL),(101,79,'1780216323_6a1bf20389942.jpg','image/jpeg','2026-05-31 08:32:03',NULL),(102,81,'1780258497_6a1c96c14647d6.05367476.jpg','image/jpeg','2026-05-31 20:14:57',NULL),(103,81,'1780258497_6a1c96c14a3063.86558227.jpg','image/jpeg','2026-05-31 20:14:57',NULL),(104,82,'1780265572_6a1cb264770d82.14592795.jpg','image/jpeg','2026-05-31 22:12:52',NULL),(105,83,'1780269150_6a1cc05e75e060.98323092.jpg','image/jpeg','2026-05-31 23:12:30',NULL),(106,83,'1780269150_6a1cc05e780878.75085299.jpg','image/jpeg','2026-05-31 23:12:30',NULL),(107,84,'1780272041_6a1ccba9d48162.76494754.jpg','image/jpeg','2026-06-01 00:00:41',NULL),(108,84,'1780272041_6a1ccba9d75215.26657019.jpg','image/jpeg','2026-06-01 00:00:41',NULL),(109,84,'1780272041_6a1ccba9d882e3.88109745.jpg','image/jpeg','2026-06-01 00:00:41',NULL),(110,84,'1780272041_6a1ccba9d9b516.97328373.jpg','image/jpeg','2026-06-01 00:00:41',NULL),(111,84,'1780272041_6a1ccba9dad495.19569479.jpg','image/jpeg','2026-06-01 00:00:41',NULL),(112,84,'1780272041_6a1ccba9dbfc54.38078989.jpg','image/jpeg','2026-06-01 00:00:41',NULL),(113,85,'1780286343_6a1d03879a8a28.46617032.jpg','image/jpeg','2026-06-01 03:59:03',NULL),(114,85,'1780286343_6a1d03879e96e8.81994781.jpg','image/jpeg','2026-06-01 03:59:03',NULL),(115,85,'1780286343_6a1d0387a04f50.69801048.jpg','image/jpeg','2026-06-01 03:59:03',NULL),(116,85,'1780286343_6a1d0387a1c5c7.18320891.jpg','image/jpeg','2026-06-01 03:59:03',NULL),(117,85,'1780286343_6a1d0387a35df1.89074428.jpg','image/jpeg','2026-06-01 03:59:03',NULL),(118,85,'1780286343_6a1d0387a5e719.43446008.jpg','image/jpeg','2026-06-01 03:59:03',NULL),(119,86,'1780293172_6a1d1e34112af1.58038565.jpg','image/jpeg','2026-06-01 05:52:52',NULL),(120,86,'1780293172_6a1d1e3413cf88.39504541.jpg','image/jpeg','2026-06-01 05:52:52',NULL),(121,86,'1780293172_6a1d1e34156c13.71653275.jpg','image/jpeg','2026-06-01 05:52:52',NULL),(122,86,'1780293172_6a1d1e34174461.73483966.jpg','image/jpeg','2026-06-01 05:52:52',NULL),(123,86,'1780293172_6a1d1e3418f0f5.09390810.jpg','image/jpeg','2026-06-01 05:52:52',NULL);
/*!40000 ALTER TABLE `revision_evidencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revision_material`
--

DROP TABLE IF EXISTS `revision_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revision_material` (
  `id` int NOT NULL AUTO_INCREMENT,
  `revision_id` int NOT NULL,
  `arco_material_id` int DEFAULT NULL,
  `material_id` int NOT NULL,
  `cantidad` float DEFAULT '1',
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serie` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accion` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cambio',
  PRIMARY KEY (`id`),
  KEY `revision_id` (`revision_id`),
  KEY `material_id` (`material_id`),
  KEY `idx_revision_material_arco_material_id` (`arco_material_id`),
  CONSTRAINT `revision_material_ibfk_1` FOREIGN KEY (`revision_id`) REFERENCES `revisiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revision_material`
--

LOCK TABLES `revision_material` WRITE;
/*!40000 ALTER TABLE `revision_material` DISABLE KEYS */;
INSERT INTO `revision_material` VALUES (78,65,267,13,1,NULL,'5661015500079','cambio'),(79,65,268,13,1,NULL,'5661015500080','cambio'),(81,67,491,7,1,NULL,'8B0159EAAJ81B1A','cambio'),(86,70,595,13,1,NULL,'5661015500076','cambio'),(87,70,596,13,1,NULL,'5661015500077','cambio'),(91,74,446,9,1,NULL,NULL,'cambio'),(93,76,487,8,1,NULL,NULL,'cambio'),(96,78,497,13,1,NULL,NULL,'cambio'),(97,78,498,13,1,NULL,NULL,'cambio'),(98,79,515,13,1,NULL,NULL,'cambio'),(99,79,516,13,1,NULL,NULL,'cambio'),(100,80,580,30,10,NULL,NULL,'cambio'),(101,81,657,13,1,NULL,NULL,'cambio'),(102,81,658,13,1,NULL,NULL,'cambio'),(103,82,665,13,1,NULL,NULL,'cambio'),(104,82,666,13,1,NULL,NULL,'cambio'),(105,83,631,13,1,NULL,NULL,'cambio'),(106,83,632,13,1,NULL,NULL,'cambio'),(107,83,712,17,1,NULL,NULL,'cambio'),(108,83,711,10,1,NULL,NULL,'cambio'),(109,83,640,12,1,NULL,NULL,'cambio'),(110,83,639,12,1,NULL,NULL,'cambio'),(111,83,635,15,2.5,NULL,NULL,'cambio'),(112,83,636,14,2.5,NULL,NULL,'cambio'),(113,84,713,13,1,NULL,NULL,'cambio'),(114,84,714,13,1,NULL,NULL,'cambio'),(115,85,740,13,1,NULL,NULL,'cambio'),(116,85,741,13,1,NULL,NULL,'cambio'),(117,86,729,13,1,NULL,NULL,'cambio'),(118,86,730,13,1,NULL,NULL,'cambio');
/*!40000 ALTER TABLE `revision_material` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revisiones`
--

DROP TABLE IF EXISTS `revisiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revisiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `arco_id` int NOT NULL,
  `fecha_mantenimiento` datetime NOT NULL,
  `tipo_mantenimiento` enum('Preventivo','Correctivo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Correctivo',
  `observaciones` text COLLATE utf8mb4_general_ci,
  `tecnico_responsable` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arco_id` (`arco_id`),
  CONSTRAINT `revisiones_ibfk_1` FOREIGN KEY (`arco_id`) REFERENCES `arcos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revisiones`
--

LOCK TABLES `revisiones` WRITE;
/*!40000 ALTER TABLE `revisiones` DISABLE KEYS */;
INSERT INTO `revisiones` VALUES (65,31,'2026-05-12 13:03:00','Correctivo','Se cambiaron baterias por deterioro ','Carlos Serafin Chavelas Gonzalez'),(67,32,'2026-05-07 15:00:00','Correctivo','Se reparo camara LPR Sur Norte y se reparo conecto de luz AC','Carlos Serafin Chavelas Gonzalez'),(70,5,'2026-05-25 11:00:00','Correctivo','Se cambiaron las baterias por que ya no retienen carga, comprobado con el tester de baterias','Carlos Serafin Chavelas Gonzalez'),(74,32,'2026-05-29 11:14:00','Correctivo','Se cambio el swich uno de la marca hikvision por un wiltek porque ya se bloqueo y dejo de pasar datos de red','Carlos Serafin Chavelas Gonzalez'),(76,32,'2025-12-29 04:22:00','Correctivo','Cambio de enlace por daño','Luis Alberto Castro Garcia'),(78,22,'2026-04-25 02:19:00','Correctivo','Cambio de baterias por incendio lo que ocaciono que se inflaran las baterias','Carlos Serafin Chavelas Gonzalez'),(79,11,'2026-04-25 02:29:00','Correctivo','Cambio de baterias medio uso','Carlos Serafin Chavelas Gonzalez'),(80,38,'2026-04-25 02:57:00','Correctivo','Se cambio Cable UTP por que fue cortado','Carlos Serafin Chavelas Gonzolez'),(81,40,'2026-03-31 14:11:00','Preventivo','Limpieza de paneles Solares y cambio de baterias ','Carlos Serafin Chavelas Gonzalez'),(82,41,'2026-03-31 14:04:00','Preventivo','Cambio de baterias vida util proxima acabar','Carlos Serafin Chavelas Gonzalez'),(83,39,'2026-03-30 16:11:00','Correctivo','vestido de arco por daño causado por huracan, ','Carlos Serafin Chavelas Gonzalez'),(84,42,'2026-04-21 15:49:00','Correctivo','Cambio de baterias ya no retienen carga','Carlos Serafin Chavelas Gonzalez'),(85,44,'2026-04-21 14:40:00','Preventivo','Se cambio baterias y se limpiaron los paneles solared','Carlos Serafin Chavelas Gonzalez'),(86,43,'2026-04-21 16:04:00','Preventivo','Se limpiaron los paneles solares y se cambiaron las baterias','Carlos Serafin Chavelas Gonzalez');
/*!40000 ALTER TABLE `revisiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ubicaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `lat` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lng` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (3,'Chilpancingo','17.460713','-99.497681'),(4,'Acapulco','16.851862','-99.821777'),(5,'Taxco',NULL,NULL),(6,'Iguala',NULL,NULL),(7,'Zihuatanejo',NULL,NULL),(8,'San Marcos',NULL,NULL),(9,'Ometepec',NULL,NULL),(10,'Coyuca','17.007186','-100084279');
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'jlromero','$2y$10$CXwyF7DhaIufmCM6HOGqNeet4lRAkugpdXrdMfivyyIV6.FmUY9a2','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'repuve_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-08 22:12:13
