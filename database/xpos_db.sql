-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: xpos_db
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_name` (`name`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Ichimliklar','Gazli',3,'2026-01-10 01:04:13','2026-01-10 01:04:13'),(2,'Fast food','',3,'2026-01-10 01:14:52','2026-01-10 01:14:52');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,2,1,25000.00,25000.00),(2,1,3,1,35000.00,35000.00),(3,1,1,1,15000.00,15000.00),(4,2,2,1,25000.00,25000.00),(5,2,1,1,15000.00,15000.00),(6,3,2,2,25000.00,50000.00),(8,4,3,1,35000.00,35000.00),(9,4,1,2,15000.00,30000.00),(10,4,2,2,25000.00,50000.00),(11,5,2,1,25000.00,25000.00),(12,5,3,1,35000.00,35000.00),(13,6,2,2,25000.00,50000.00),(14,6,3,3,35000.00,105000.00),(15,7,2,1,25000.00,25000.00),(16,7,3,1,35000.00,35000.00),(17,8,2,1,25000.00,25000.00),(18,8,1,1,15000.00,15000.00),(19,9,2,1,25000.00,25000.00),(20,9,3,1,35000.00,35000.00),(21,10,2,1,25000.00,25000.00),(22,10,3,1,35000.00,35000.00),(23,11,2,1,25000.00,25000.00),(24,11,3,1,35000.00,35000.00),(25,12,2,1,25000.00,25000.00),(26,12,3,1,35000.00,35000.00),(27,13,2,1,25000.00,25000.00),(28,13,3,1,35000.00,35000.00),(29,14,3,1,35000.00,35000.00),(30,14,1,1,15000.00,15000.00),(31,15,2,1,25000.00,25000.00),(32,16,3,1,35000.00,35000.00),(33,17,3,1,35000.00,35000.00),(34,17,2,1,25000.00,25000.00),(35,18,1,1,15000.00,15000.00),(36,19,2,1,25000.00,25000.00),(37,19,3,1,35000.00,35000.00),(38,19,1,1,15000.00,15000.00),(39,20,2,1,25000.00,25000.00),(40,20,1,1,15000.00,15000.00),(41,21,2,2,25000.00,50000.00),(42,21,3,1,35000.00,35000.00),(43,21,1,1,15000.00,15000.00),(44,22,3,1,35000.00,35000.00),(45,22,2,1,25000.00,25000.00),(46,23,3,1,35000.00,35000.00),(47,23,2,1,25000.00,25000.00),(48,24,2,1,25000.00,25000.00),(49,24,3,1,35000.00,35000.00),(50,25,2,1,25000.00,25000.00),(51,25,3,1,35000.00,35000.00),(52,26,2,1,25000.00,25000.00),(53,26,3,1,35000.00,35000.00),(54,27,3,1,35000.00,35000.00),(55,28,3,1,35000.00,35000.00),(56,28,2,1,25000.00,25000.00),(57,29,2,1,25000.00,25000.00),(58,29,3,1,35000.00,35000.00),(59,30,3,1,35000.00,35000.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `service_charge` decimal(10,2) DEFAULT '0.00',
  `delivery_fee` decimal(10,2) DEFAULT '0.00',
  `discount` decimal(10,2) DEFAULT '0.00',
  `grand_total` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `order_type` enum('dine_in','delivery') COLLATE utf8mb4_unicode_ci DEFAULT 'dine_in',
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` text COLLATE utf8mb4_unicode_ci,
  `cancelled_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cancelled_by` (`cancelled_by`),
  KEY `idx_seller` (`seller_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_order_type` (`order_type`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,4,75000.00,0.00,0.00,0.00,75000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 01:18:48','2026-01-10 07:22:50'),(2,4,40000.00,0.00,0.00,0.00,40000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 01:45:10','2026-01-10 07:22:50'),(3,4,50000.00,0.00,0.00,0.00,50000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 01:45:26','2026-01-10 07:22:50'),(4,4,115000.00,0.00,0.00,0.00,115000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 02:05:52','2026-01-10 07:22:50'),(5,4,60000.00,0.00,0.00,0.00,60000.00,'completed','delivery','Tohirjon','+998778889966','Peshku',NULL,'2026-01-10 02:07:53','2026-01-10 07:22:50'),(6,4,155000.00,0.00,0.00,0.00,155000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 02:43:07','2026-01-10 07:22:50'),(7,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 02:51:34','2026-01-10 07:22:50'),(8,4,40000.00,0.00,0.00,0.00,40000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 02:51:54','2026-01-10 07:22:50'),(9,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 02:55:18','2026-01-10 07:22:50'),(10,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 02:57:25','2026-01-10 07:22:50'),(11,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 03:01:50','2026-01-10 07:22:50'),(12,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 03:19:54','2026-01-10 07:22:50'),(13,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 03:19:59','2026-01-10 07:22:50'),(14,4,50000.00,0.00,0.00,0.00,50000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 03:20:11','2026-01-10 07:22:50'),(15,4,25000.00,0.00,0.00,0.00,25000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 04:12:39','2026-01-10 07:22:50'),(16,4,35000.00,0.00,0.00,0.00,35000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 04:12:43','2026-01-10 07:22:50'),(17,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 04:12:48','2026-01-10 07:22:50'),(18,4,15000.00,0.00,0.00,0.00,15000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 04:12:55','2026-01-10 07:22:50'),(19,4,75000.00,0.00,0.00,0.00,75000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 04:13:02','2026-01-10 07:22:50'),(20,4,40000.00,0.00,0.00,0.00,40000.00,'cancelled','dine_in',NULL,NULL,NULL,4,'2026-01-10 06:46:27','2026-01-10 07:22:50'),(21,4,100000.00,0.00,0.00,0.00,100000.00,'completed','delivery','Alisher','+998996665544','Vobkent',NULL,'2026-01-10 06:47:15','2026-01-10 07:22:50'),(22,4,60000.00,0.00,0.00,0.00,60000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:14:57','2026-01-10 07:22:50'),(23,4,60000.00,6000.00,0.00,0.00,66000.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:22:58','2026-01-10 07:23:03'),(24,4,60000.00,6600.00,0.00,0.00,66600.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:28:33','2026-01-10 07:33:36'),(25,4,60000.00,6600.00,0.00,0.00,66600.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:31:02','2026-01-10 07:33:20'),(26,4,60000.00,6600.00,0.00,0.00,66600.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:33:45','2026-01-10 07:33:49'),(27,4,35000.00,3850.00,0.00,0.00,38850.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:34:46','2026-01-10 07:34:51'),(28,4,60000.00,6600.00,0.00,9000.00,57600.00,'completed','dine_in',NULL,NULL,NULL,NULL,'2026-01-10 07:35:56','2026-01-10 07:41:23'),(29,4,60000.00,6600.00,13000.00,9000.00,70600.00,'completed','delivery','thntyhnt','57577','gmthmtjh',NULL,'2026-01-10 07:41:42','2026-01-10 07:41:56'),(30,4,35000.00,0.00,3500.00,3500.00,35000.00,'completed','delivery','ffgh','46546','fghfdgh',NULL,'2026-01-10 08:18:54','2026-01-10 08:19:01');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_category` (`category_id`),
  KEY `idx_name` (`name`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Koka kola','',15000.00,'6961a8a7e4ff3_1768007847.jpg',3,'2026-01-10 01:17:27','2026-01-10 01:17:27'),(2,2,'Burger','',25000.00,'6961a8b780903_1768007863.jpg',3,'2026-01-10 01:17:43','2026-01-10 01:17:43'),(3,2,'Lavash','',35000.00,'6961a8ca26db5_1768007882.jpg',3,'2026-01-10 01:18:02','2026-01-10 01:18:02');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_type` enum('percentage','fixed','text') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'service_charge_percentage','0','percentage','Qo\'shimcha xizmat haqi (%)','2026-01-10 08:19:50',2),(2,'delivery_fee_type','percentage','text','Yetkazib berish to\'lovi turi: fixed/percentage','2026-01-10 08:18:20',2),(3,'delivery_fee_value','10','fixed','Yetkazib berish to\'lovi qiymati','2026-01-10 08:18:20',2),(4,'discount_percentage','0','percentage','Chegirma (%)','2026-01-10 08:19:27',2);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','manager','seller') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `login` (`login`),
  KEY `idx_login` (`login`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Admin','+998995622114','admin','$2y$10$BN2IWWzkK5rjlbwtAovzDe0c6n0p1DKybbUcJF4E/YZry2Ub6GJca','super_admin','2026-01-10 01:02:33','2026-01-10 01:02:33'),(3,'manager','+998887774455','manager','$2y$10$ygPEbdIWOXOcWfwJAF7sXOb.dWZAB6dIqJeSd8zHXIZ3I5Akd.hI6','manager','2026-01-10 01:03:49','2026-01-10 01:03:49'),(4,'seller','+998996665544','seller','$2y$10$LFYvVjS2dzCGSwq0ZqfgQekx6az7jnIXXKuVeAI./EgFXSTn/S0NS','seller','2026-01-10 01:13:41','2026-01-10 01:13:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-10 14:20:29
