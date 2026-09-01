CREATE DATABASE  IF NOT EXISTS `pathwise` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `pathwise`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: pathwise
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ai_recommendations`
--

DROP TABLE IF EXISTS `ai_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_recommendations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `recommendation_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_recommendations_student_id_foreign` (`student_id`),
  KEY `ai_recommendations_course_id_foreign` (`course_id`),
  CONSTRAINT `ai_recommendations_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_recommendations_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_recommendations`
--

LOCK TABLES `ai_recommendations` WRITE;
/*!40000 ALTER TABLE `ai_recommendations` DISABLE KEYS */;
INSERT INTO `ai_recommendations` VALUES (3,3,10,100.00,'Excellent work! Based on your strong performance in Business Strategy and Leadership, this course is the next step to continue improving your skills.',0,'2026-08-31 19:12:21','2026-08-31 19:12:21');
/*!40000 ALTER TABLE `ai_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `lesson_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `due_date` datetime DEFAULT NULL,
  `max_score` int NOT NULL DEFAULT '100',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_course_id_foreign` (`course_id`),
  KEY `assignments_lesson_id_foreign` (`lesson_id`),
  CONSTRAINT `assignments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-07648b7b289dd3d52b9b622ac3e906a7','i:1;',1788232012),('laravel-cache-07648b7b289dd3d52b9b622ac3e906a7:timer','i:1788232012;',1788232012),('laravel-cache-676de78acf246fcc5d4486136560221e','i:1;',1788228239),('laravel-cache-676de78acf246fcc5d4486136560221e:timer','i:1788228239;',1788228239),('laravel-cache-941934b4e332fb0036bd72708fc86551','i:1;',1788232020),('laravel-cache-941934b4e332fb0036bd72708fc86551:timer','i:1788232020;',1788232020),('laravel-cache-9e87c2a19319ef33aa2569e239ef8a90','i:1;',1788231099),('laravel-cache-9e87c2a19319ef33aa2569e239ef8a90:timer','i:1788231099;',1788231099);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `certificate_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `issued_date` date NOT NULL,
  `certificate_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('issued','revoked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
  KEY `certificates_student_id_foreign` (`student_id`),
  KEY `certificates_course_id_foreign` (`course_id`),
  CONSTRAINT `certificates_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificates`
--

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
INSERT INTO `certificates` VALUES (1,3,9,'PATH-2026-00001','2026-07-04',NULL,'issued','2026-07-03 22:46:24','2026-07-03 22:46:24'),(2,3,17,'PATH-2026-00002','2026-09-01',NULL,'issued','2026-08-31 19:12:21','2026-08-31 19:12:21');
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_categories`
--

DROP TABLE IF EXISTS `course_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_categories`
--

LOCK TABLES `course_categories` WRITE;
/*!40000 ALTER TABLE `course_categories` DISABLE KEYS */;
INSERT INTO `course_categories` VALUES (1,'Accounting','Accounting and Financial Management Courses',NULL,1,'2026-06-16 19:44:40','2026-06-16 19:44:40'),(2,'Business','Business and Entrepreneurship',NULL,1,'2026-06-16 19:44:40','2026-06-16 19:44:40'),(3,'Technology','Information Technology and Programming',NULL,1,'2026-06-16 19:44:40','2026-06-16 19:44:40'),(4,'Marketing','Digital Marketing and Sales',NULL,1,'2026-06-16 19:44:40','2026-06-16 19:44:40');
/*!40000 ALTER TABLE `course_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_reviews`
--

DROP TABLE IF EXISTS `course_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_reviews`
--

LOCK TABLES `course_reviews` WRITE;
/*!40000 ALTER TABLE `course_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_templates`
--

DROP TABLE IF EXISTS `course_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_templates`
--

LOCK TABLES `course_templates` WRITE;
/*!40000 ALTER TABLE `course_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intro_video` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_free` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','pending','approved','rejected','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `estimated_hours` int DEFAULT NULL,
  `certificate_available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courses_teacher_id_foreign` (`teacher_id`),
  KEY `courses_category_id_foreign` (`category_id`),
  CONSTRAINT `courses_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `courses_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,2,1,'Basic Accounting Fundamentals','Learn accounting principles, journal entries, and financial statements.',NULL,NULL,'beginner',499.00,0,'published',10,1,'2026-06-16 19:44:40','2026-06-19 21:28:45'),(2,2,1,'Financial Accounting','Master preparation and analysis of financial statements.',NULL,NULL,'intermediate',899.00,0,'published',20,1,'2026-06-16 19:44:40','2026-06-17 05:02:53'),(3,2,1,'Advanced Cost Accounting','Cost accumulation, budgeting, and managerial decision making.',NULL,NULL,'advanced',1299.00,0,'published',30,1,'2026-06-16 19:44:40','2026-07-02 05:51:03'),(4,2,3,'Introduction to Web Development','Learn HTML, CSS, JavaScript and modern web applications.',NULL,NULL,'beginner',599.00,0,'published',15,1,'2026-06-16 19:44:40','2026-06-17 05:02:53'),(5,2,3,'Laravel Web Development','Build modern applications using Laravel framework.',NULL,NULL,'intermediate',1499.00,0,'published',35,1,'2026-06-16 19:44:40','2026-06-17 05:02:53'),(6,2,2,'Entrepreneurship Essentials','Learn how to start and manage your own business.',NULL,NULL,'beginner',699.00,0,'published',12,1,'2026-06-16 19:44:40','2026-06-17 05:02:53'),(7,2,4,'Digital Marketing Masterclass','SEO, Social Media Marketing, and Online Advertising.',NULL,NULL,'intermediate',999.00,0,'published',18,1,'2026-06-16 19:44:40','2026-06-17 05:02:53'),(8,1,2,'Business Planning Fundamentals','Business planning is the foundational management function of defining an organization\'s future direction and allocating resources to achieve specific goals.',NULL,NULL,'beginner',499.00,0,'published',10,1,'2026-06-21 18:44:26','2026-06-21 18:44:26'),(9,1,2,'Small Business Management','Small business management is the process of coordinating and overseeing all aspects of a company—typically defined by the U.S. Small Business Administration (SBA) as having fewer than 500 employees—to ensure smooth operations and goal achievement.  It involves planning, organizing, leading, and controlling resources, including staff, finances, marketing, and daily administrative tasks.',NULL,NULL,'intermediate',599.00,0,'published',15,1,'2026-06-21 18:45:04','2026-06-21 18:45:04'),(10,1,2,'Business Strategy and Leadership','Strategy provides the organizational direction and framework for decision-making, while leadership acts as the human enabler that mobilizes people to execute that strategy.',NULL,NULL,'advanced',499.00,0,'published',15,1,'2026-06-21 18:45:35','2026-06-21 18:45:35'),(11,1,4,'Social Media Marketing','Social media marketing is the use of social media platforms and websites to promote a product or service.',NULL,NULL,'beginner',399.00,0,'published',10,1,'2026-06-21 18:46:14','2026-06-21 18:46:14'),(13,1,4,'Marketing Analytics','Marketing analytics is the practice of collecting, aggregating, and analyzing data from various marketing channels to evaluate campaign effectiveness, understand customer behavior, and optimize return on investment (ROI).  By transforming raw data into actionable insights, businesses can make data-driven decisions to improve customer experiences, allocate budgets efficiently, and drive revenue growth.',NULL,NULL,'advanced',699.00,0,'published',12,1,'2026-06-21 18:47:13','2026-06-21 18:47:13'),(16,1,3,'Full Stack Development','Software development is a fast-paced, exciting field that reflects the centrality of software in today\'s world. From smartphones to organizational productivity to AI applications, the famous 2011 claim made by venture capitalist Marc Andreessen still rings true: “Software is eating the world.',NULL,NULL,'advanced',699.00,0,'published',16,1,'2026-06-21 18:48:51','2026-06-21 18:48:51'),(17,1,2,'Business Strategy and Leadership','Develop advanced leadership, strategic planning, innovation, and decision-making skills to drive business growth and competitive advantage in dynamic business environments.',NULL,NULL,'advanced',1299.00,0,'published',25,1,'2026-06-22 01:18:43','2026-06-22 01:18:43'),(18,2,3,'Introduction to Programming','A free course for beginners to learn programming basics.',NULL,NULL,'beginner',0.00,1,'published',5,0,'2026-07-03 17:14:20','2026-08-31 17:15:09'),(19,2,3,'Introduction to Programming','A free course for beginners to learn programming basics.',NULL,NULL,'beginner',0.00,0,'draft',8,1,'2026-08-31 19:08:19','2026-08-31 19:08:19');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `status` enum('active','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollments_student_id_foreign` (`student_id`),
  KEY `enrollments_course_id_foreign` (`course_id`),
  CONSTRAINT `enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (1,3,9,'completed','2026-07-03 22:43:23',NULL,100.00,'2026-07-03 22:43:23','2026-07-03 22:46:24'),(2,3,17,'completed','2026-08-23 18:26:46',NULL,100.00,'2026-08-23 18:26:46','2026-08-31 19:12:21'),(3,3,16,'active','2026-08-24 18:22:02',NULL,0.00,'2026-08-24 18:22:02','2026-08-24 18:22:02'),(4,3,11,'active','2026-08-24 18:41:36',NULL,0.00,'2026-08-24 18:41:36','2026-08-24 18:41:36');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_path_courses`
--

DROP TABLE IF EXISTS `learning_path_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_path_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_path_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `course_order` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_path_courses_learning_path_id_foreign` (`learning_path_id`),
  KEY `learning_path_courses_course_id_foreign` (`course_id`),
  CONSTRAINT `learning_path_courses_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_path_courses_learning_path_id_foreign` FOREIGN KEY (`learning_path_id`) REFERENCES `learning_paths` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_path_courses`
--

LOCK TABLES `learning_path_courses` WRITE;
/*!40000 ALTER TABLE `learning_path_courses` DISABLE KEYS */;
INSERT INTO `learning_path_courses` VALUES (1,1,6,1,NULL,NULL),(2,1,8,2,NULL,NULL),(3,1,9,3,NULL,NULL),(4,1,10,4,NULL,NULL),(5,1,17,5,NULL,NULL);
/*!40000 ALTER TABLE `learning_path_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_paths`
--

DROP TABLE IF EXISTS `learning_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_paths` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `difficulty_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_generated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_paths_student_id_foreign` (`student_id`),
  CONSTRAINT `learning_paths_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_paths`
--

LOCK TABLES `learning_paths` WRITE;
/*!40000 ALTER TABLE `learning_paths` DISABLE KEYS */;
INSERT INTO `learning_paths` VALUES (1,3,'Business Learning Path','Generated based on your learning performance. Strongest Category: Business. Average Quiz Score: 70%. Recommended courses match your current skill level (Intermediate).','intermediate',1,'2026-08-31 15:25:02','2026-08-31 15:25:02');
/*!40000 ALTER TABLE `learning_paths` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lesson_progress`
--

DROP TABLE IF EXISTS `lesson_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lesson_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `lesson_id` bigint unsigned NOT NULL,
  `status` enum('not_started','in_progress','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_spent_minutes` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_progress_student_id_foreign` (`student_id`),
  KEY `lesson_progress_lesson_id_foreign` (`lesson_id`),
  CONSTRAINT `lesson_progress_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_progress_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lesson_progress`
--

LOCK TABLES `lesson_progress` WRITE;
/*!40000 ALTER TABLE `lesson_progress` DISABLE KEYS */;
INSERT INTO `lesson_progress` VALUES (1,3,54,'completed','2026-07-03 22:43:41','2026-07-03 22:43:57',0,'2026-07-03 22:43:41','2026-07-03 22:43:57'),(2,3,55,'completed','2026-07-03 22:43:59','2026-07-03 22:44:01',0,'2026-07-03 22:43:59','2026-07-03 22:44:01'),(3,3,56,'completed','2026-07-03 22:44:02','2026-07-03 22:44:05',0,'2026-07-03 22:44:02','2026-07-03 22:44:05'),(4,3,57,'completed','2026-07-03 22:44:06','2026-07-03 22:44:08',0,'2026-07-03 22:44:06','2026-07-03 22:44:08'),(5,3,58,'completed','2026-07-03 22:44:12','2026-07-03 22:44:16',0,'2026-07-03 22:44:12','2026-07-03 22:44:16'),(6,3,142,'in_progress','2026-08-31 09:02:03',NULL,0,'2026-08-31 09:02:03','2026-08-31 09:02:03'),(7,3,157,'completed','2026-08-31 19:11:02','2026-08-31 19:11:07',0,'2026-08-31 19:11:02','2026-08-31 19:11:07'),(8,3,158,'completed','2026-08-31 19:11:10','2026-08-31 19:11:11',0,'2026-08-31 19:11:10','2026-08-31 19:11:11'),(9,3,159,'completed','2026-08-31 19:11:13','2026-08-31 19:11:15',0,'2026-08-31 19:11:13','2026-08-31 19:11:15'),(10,3,160,'completed','2026-08-31 19:11:17','2026-08-31 19:11:19',0,'2026-08-31 19:11:17','2026-08-31 19:11:19'),(11,3,161,'completed','2026-08-31 19:11:21','2026-08-31 19:11:31',0,'2026-08-31 19:11:21','2026-08-31 19:11:31');
/*!40000 ALTER TABLE `lesson_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lessons`
--

DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lessons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lesson_type` enum('video','document','text','quiz') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'video',
  `video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lesson_order` int NOT NULL DEFAULT '1',
  `duration_minutes` int DEFAULT NULL,
  `is_preview` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lessons_course_id_foreign` (`course_id`),
  CONSTRAINT `lessons_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lessons`
--

LOCK TABLES `lessons` WRITE;
/*!40000 ALTER TABLE `lessons` DISABLE KEYS */;
INSERT INTO `lessons` VALUES (44,1,'Introduction to Accounting','What is Accounting?\r\nPurpose of Accounting\r\nUsers of Financial Information\r\nImportance of Accounting in Business','text',NULL,NULL,1,20,1,1,'2026-06-20 05:40:19','2026-07-02 18:56:08'),(45,1,'Accounting Equation','Assets\r\nLiabilities\r\nOwner\'s Equity\r\nBasic Accounting Equation','text','https://music.youtube.com/watch?v=D0eE8l87oyY',NULL,2,20,1,1,'2026-06-20 05:41:09','2026-06-20 05:41:09'),(46,1,'Debits and Credits','Definition of Debit\r\nDefinition of Credit\r\nNormal Balances of Accounts\r\nRules of Debit and Credit','text','https://www.youtube.com/watch?v=8F2s8ivKXNY',NULL,3,25,1,1,'2026-06-20 05:41:50','2026-06-20 05:41:50'),(47,1,'Journal Entries','Content:\r\n\r\nPurpose of Journal Entries\r\nRecording Transactions\r\nDate\r\nAccount Titles\r\nDebit and Credit Columns\r\n\r\nExample Journal Entry:\r\n\r\nCash                10,000\r\n    Owner Capital          10,000','text','https://youtu.be/7ykkhepT9aE?si=C6tSzMuRirhVEhlh',NULL,4,25,1,1,'2026-06-20 05:42:32','2026-06-20 05:42:32'),(48,1,'Financial Statements','Income Statement\r\nBalance Sheet\r\nCash Flow Statement\r\nImportance of Financial Reports\r\n\r\nOverview only for beginners.','text','https://youtu.be/JJyLynh5d6M?si=_t2VzwNdSWcZYtql',NULL,5,30,0,1,'2026-06-20 05:43:10','2026-06-20 05:43:10'),(49,8,'Introduction to Business Planning','Business planning is the process of defining a company\'s goals, strategies, and actions needed to achieve success. A business plan serves as a roadmap that guides entrepreneurs and managers in making informed decisions.\r\n\r\nA well-prepared business plan includes the business objectives, target market, products or services offered, marketing strategies, operational plans, and financial projections.\r\n\r\nBenefits of business planning include:\r\n• Clear direction and goals\r\n• Better decision-making\r\n• Improved resource allocation\r\n• Increased chances of business success\r\n• Easier access to funding and investors\r\n\r\nBusiness planning is essential for both new and existing businesses because it helps identify opportunities, challenges, and strategies for growth.','text',NULL,NULL,1,25,1,1,'2026-06-21 19:26:24','2026-06-21 19:26:24'),(51,8,'Market Analysis and Target Customers','Market analysis helps businesses understand their customers, competitors, and industry trends.\r\n\r\nBusinesses should identify:\r\n• Target customers\r\n• Customer needs\r\n• Buying behaviors\r\n• Competitors\r\n• Market opportunities\r\n\r\nUnderstanding the target market allows businesses to create products and services that meet customer demands and improve profitability.','text',NULL,NULL,3,25,1,1,'2026-06-21 19:28:16','2026-06-21 19:28:16'),(52,8,'Business Resources and Operations','Business operations involve the daily activities required to deliver products and services to customers.\r\n\r\nResources needed for operations include:\r\n• Human resources\r\n• Financial resources\r\n• Equipment and technology\r\n• Raw materials\r\n\r\nEfficient operations help reduce costs, improve productivity, and increase customer satisfaction.','text',NULL,NULL,4,24,1,1,'2026-06-21 19:28:41','2026-06-21 19:28:41'),(53,8,'Monitoring and Evaluating Business Performance','Monitoring business performance helps determine whether goals and objectives are being achieved.\r\n\r\nCommon performance indicators include:\r\n• Sales revenue\r\n• Customer satisfaction\r\n• Profit margins\r\n• Market share\r\n• Productivity levels\r\n\r\nRegular evaluation allows businesses to identify weaknesses, improve strategies, and maintain competitiveness in the market.','text',NULL,NULL,5,34,1,1,'2026-06-21 19:29:00','2026-06-21 19:29:00'),(54,9,'Financial Planning for Small Businesses','Financial planning is the process of managing a business\'s income, expenses, assets, and liabilities to achieve financial stability and growth.\r\n\r\nSmall businesses should prepare budgets, monitor cash flows, and maintain accurate financial records. Effective financial planning helps business owners make informed decisions and avoid financial difficulties.\r\n\r\nKey components include:\r\n• Budgeting\r\n• Cash Flow Management\r\n• Profit Planning\r\n• Cost Control\r\n• Financial Forecasting\r\n\r\nBusinesses that practice proper financial planning are more likely to achieve long-term success.','text',NULL,NULL,1,30,1,1,'2026-06-22 01:02:50','2026-06-22 01:02:50'),(55,9,'Managing Employees and Teams','Employees play a critical role in business success. Effective management involves hiring qualified workers, providing training, maintaining communication, and motivating employees.\r\n\r\nGood leadership improves productivity and employee satisfaction. Business owners should create a positive work environment and encourage teamwork.\r\n\r\nBenefits of effective employee management:\r\n• Higher productivity\r\n• Better customer service\r\n• Reduced employee turnover\r\n• Improved workplace morale','video',NULL,NULL,2,30,1,1,'2026-06-22 01:03:06','2026-06-22 01:03:24'),(56,9,'Customer Relationship Management','Customer Relationship Management (CRM) focuses on building strong relationships with customers to increase satisfaction and loyalty.\r\n\r\nBusinesses should understand customer needs, respond to feedback, and provide quality products and services.\r\n\r\nBenefits of CRM:\r\n• Increased customer retention\r\n• Improved customer satisfaction\r\n• Better business reputation\r\n• Increased sales opportunities','text',NULL,NULL,3,23,1,1,'2026-06-22 01:03:47','2026-06-22 01:03:47'),(57,9,'Inventory and Operations Management','Inventory management ensures that a business maintains the right quantity of products to meet customer demand.\r\n\r\nOperations management focuses on organizing resources and processes efficiently.\r\n\r\nEffective inventory and operations management help:\r\n• Reduce waste\r\n• Minimize costs\r\n• Improve efficiency\r\n• Maintain customer satisfaction\r\n\r\nBusinesses should regularly monitor stock levels and operational performance.','text',NULL,NULL,4,34,1,1,'2026-06-22 01:04:04','2026-06-22 01:04:04'),(58,9,'Business Risk Management','Risk management involves identifying, assessing, and minimizing potential threats that may affect business operations.\r\n\r\nCommon business risks include:\r\n• Financial risks\r\n• Operational risks\r\n• Market risks\r\n• Legal risks\r\n• Technology risks\r\n\r\nBusiness owners should develop contingency plans and regularly evaluate potential risks to ensure business continuity.','text',NULL,NULL,5,34,1,1,'2026-06-22 01:05:12','2026-06-22 01:05:12'),(59,10,'Strategic Planning and Business Growth','Strategic planning is the process of defining an organization\'s long-term goals and determining the best actions to achieve them. It helps businesses identify opportunities, anticipate challenges, and allocate resources effectively.\r\n\r\nStrategic planning typically involves:\r\n\r\n• Analyzing the business environment\r\n• Setting long-term objectives\r\n• Developing growth strategies\r\n• Monitoring performance and results\r\n\r\nOrganizations that practice strategic planning are better prepared to compete in changing markets and achieve sustainable growth.','text',NULL,NULL,1,23,1,1,'2026-06-22 01:23:39','2026-06-22 01:23:39'),(60,10,'Leadership Styles and Organizational Success','Leadership plays a critical role in business success. Effective leaders inspire employees, guide decision-making, and create a positive organizational culture.\r\n\r\nCommon leadership styles include:\r\n\r\n• Autocratic Leadership\r\n• Democratic Leadership\r\n• Transformational Leadership\r\n• Transactional Leadership\r\n\r\nSuccessful leaders adapt their leadership style based on organizational needs and employee capabilities.','text',NULL,NULL,2,23,0,1,'2026-06-22 01:24:04','2026-06-22 01:24:04'),(61,10,'Competitive Advantage and Market Positioning','Competitive advantage refers to factors that allow a business to outperform competitors. Organizations can gain competitive advantage through innovation, superior quality, lower costs, or exceptional customer service.\r\n\r\nMarket positioning focuses on how customers perceive a company\'s products or services compared to competitors.\r\n\r\nBusinesses with strong competitive advantages are more likely to maintain profitability and long-term success.','text',NULL,NULL,3,23,1,1,'2026-06-22 01:24:35','2026-06-22 01:24:35'),(62,10,'Organizational Development and Change Management','Organizations must continuously improve to remain competitive. Organizational development focuses on improving processes, structures, and employee performance.\r\n\r\nChange management involves planning and implementing organizational changes while minimizing resistance from employees.\r\n\r\nEffective change management helps businesses adapt to technological advancements, market trends, and customer expectations.','text',NULL,NULL,4,23,1,1,'2026-06-22 01:24:55','2026-06-22 01:24:55'),(63,10,'Innovation and Strategic Decision-Making','Innovation involves developing new ideas, products, services, or processes that create value for customers and businesses.\r\n\r\nStrategic decision-making requires leaders to evaluate information, analyze risks, and select the best course of action for long-term success.\r\n\r\nBusinesses that encourage innovation and effective decision-making are more likely to achieve growth, competitiveness, and sustainability.','text',NULL,NULL,5,34,1,1,'2026-06-22 01:25:21','2026-06-22 01:25:21'),(64,2,'Introduction to Financial Accounting','This lesson covers Introduction for Financial Accounting.','text',NULL,NULL,1,20,1,1,'2026-07-02 23:11:37','2026-07-03 00:02:10'),(65,2,'Understanding Financial Statements','This lesson covers Core Concepts for Financial Accounting.','text',NULL,NULL,2,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:02:16'),(66,2,'Adjusting Journal Entries','This lesson covers Practical Applications for Financial Accounting.','text',NULL,NULL,3,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:02:21'),(67,2,'Preparing the Trial Balance','This lesson covers Advanced Topics for Financial Accounting.','text',NULL,NULL,4,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:02:27'),(68,2,'Financial Reporting and Analysis','This lesson covers Course Summary for Financial Accounting.','text',NULL,NULL,5,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:02:32'),(69,3,'Introduction to Cost Accounting','This lesson covers Introduction for Advanced Cost Accounting.','text',NULL,NULL,1,20,1,1,'2026-07-02 23:11:37','2026-07-03 00:09:59'),(70,3,'Cost Classification and Types','This lesson covers Core Concepts for Advanced Cost Accounting.','text',NULL,NULL,2,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:10:03'),(71,3,'Job Order vs Process Costing','This lesson covers Practical Applications for Advanced Cost Accounting.','text',NULL,NULL,3,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:10:07'),(72,3,'Budgeting and Cost Control','This lesson covers Advanced Topics for Advanced Cost Accounting.','text',NULL,NULL,4,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:10:12'),(73,3,'Cost Analysis and Decision Making','This lesson covers Course Summary for Advanced Cost Accounting.','text',NULL,NULL,5,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:10:16'),(74,4,'Introduction to Web Development','This lesson covers Introduction for Introduction to Web Development.','text',NULL,NULL,1,20,1,1,'2026-07-02 23:11:37','2026-07-03 00:15:02'),(75,4,'HTML Fundamentals','This lesson covers Core Concepts for Introduction to Web Development.','text',NULL,NULL,2,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:15:10'),(76,4,'CSS Styling Basics','This lesson covers Practical Applications for Introduction to Web Development.','text',NULL,NULL,3,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:15:16'),(77,4,'JavaScript Fundamentals','This lesson covers Advanced Topics for Introduction to Web Development.','text',NULL,NULL,4,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:15:24'),(78,4,'Building Your First Web Page','This lesson covers Course Summary for Introduction to Web Development.','text',NULL,NULL,5,20,0,1,'2026-07-02 23:11:37','2026-07-03 00:15:28'),(79,5,'Introduction to Laravel Framework','Learn what Laravel is, its features, and how to set up a development environment.','text',NULL,NULL,1,20,1,1,'2026-07-02 23:11:37','2026-07-03 01:41:18'),(80,5,'Laravel MVC Architecture','Understand Model-View-Controller pattern and how Laravel implements it.','text',NULL,NULL,2,20,0,1,'2026-07-02 23:11:37','2026-07-03 01:41:18'),(81,5,'Routing, Controllers and Views','Master Laravel routing, create controllers, and build Blade views.','text',NULL,NULL,3,20,0,1,'2026-07-02 23:11:37','2026-07-03 01:41:18'),(82,5,'Eloquent ORM and Database Management','Learn Eloquent models, relationships, migrations, and database operations.','text',NULL,NULL,4,20,0,1,'2026-07-02 23:11:37','2026-07-03 01:41:18'),(83,5,'Authentication and Laravel Features','Implement user authentication, middleware, and explore advanced Laravel features.','text',NULL,NULL,5,20,0,1,'2026-07-02 23:11:37','2026-07-03 01:41:18'),(129,8,'Setting Business Goals and Objectives','Learn how to define clear business goals and measurable objectives.','text',NULL,NULL,2,20,0,1,'2026-07-02 23:24:48','2026-07-02 23:24:48'),(132,7,'Introduction to Digital Marketing',NULL,'video',NULL,NULL,1,NULL,0,1,NULL,NULL),(133,7,'Search Engine Optimization (SEO) Fundamentals',NULL,'video',NULL,NULL,2,NULL,0,1,NULL,NULL),(134,7,'Social Media Marketing Strategies',NULL,'video',NULL,NULL,3,NULL,0,1,NULL,NULL),(135,7,'Paid Advertising and Campaign Management',NULL,'video',NULL,NULL,4,NULL,0,1,NULL,NULL),(136,7,'Digital Marketing Analytics and Performance Tracking',NULL,'video',NULL,NULL,5,NULL,0,1,NULL,NULL),(137,6,'Introduction to Entrepreneurship Essentials',NULL,'video',NULL,NULL,1,NULL,0,1,NULL,NULL),(138,6,'Business Planning and Idea Development',NULL,'video',NULL,NULL,2,NULL,0,1,NULL,NULL),(139,6,'Market Research and Customer Identification',NULL,'video',NULL,NULL,3,NULL,0,1,NULL,NULL),(140,6,'Financial Management for Startups',NULL,'video',NULL,NULL,4,NULL,0,1,NULL,NULL),(141,6,'Business Growth Strategies and Scaling',NULL,'video',NULL,NULL,5,NULL,0,1,NULL,NULL),(142,11,'Introduction to Social Media Marketing','Overview of social media platforms, their importance in business, and how to set marketing goals.','text',NULL,NULL,1,20,1,1,NULL,'2026-07-03 01:37:05'),(143,11,'Social Media Platforms & Audience Targeting','Learn about Facebook, Instagram, LinkedIn, TikTok, and how to create audience personas.','text',NULL,NULL,2,25,1,1,NULL,'2026-07-03 01:37:05'),(144,11,'Content Creation & Community Management','Master content calendars, visual creation, copywriting, and community engagement.','text',NULL,NULL,3,25,0,1,NULL,'2026-07-03 01:37:05'),(145,11,'Social Media Advertising & Campaigns','Understand paid vs organic reach, ad targeting, budgeting, and A/B testing.','text',NULL,NULL,4,30,0,1,NULL,'2026-07-03 01:37:05'),(146,11,'Analytics, Metrics & ROI Optimization','Track key metrics, use analytics tools, measure engagement, and calculate ROI.','text',NULL,NULL,5,25,0,1,NULL,'2026-07-03 01:37:05'),(147,13,'Introduction to Marketing Analytics','Learn what marketing analytics is, why data-driven decisions matter, and key terminology.','text',NULL,NULL,1,20,1,1,NULL,'2026-07-03 01:37:07'),(148,13,'Data Collection Methods & Tools','Explore primary vs secondary data, surveys, web analytics tools, CRM, and social listening.','text',NULL,NULL,2,25,1,1,NULL,'2026-07-03 01:37:07'),(149,13,'Descriptive & Diagnostic Analytics','Understand what happened, why it happened, trend analysis, and dashboard creation.','text',NULL,NULL,3,25,0,1,NULL,'2026-07-03 01:37:07'),(150,13,'Predictive Analytics & Forecasting','Learn regression analysis, machine learning basics, customer lifetime value, and churn prediction.','text',NULL,NULL,4,30,0,1,NULL,'2026-07-03 01:37:07'),(151,13,'Data Visualization & Marketing Reports','Master chart selection, tools like Tableau and Power BI, and storytelling with data.','text',NULL,NULL,5,25,0,1,NULL,'2026-07-03 01:37:07'),(152,16,'Introduction to Full Stack Development','Understand what full stack means, frontend vs backend, and tech stack overview.','text',NULL,NULL,1,20,1,1,NULL,'2026-07-03 01:37:09'),(153,16,'Frontend Development with HTML, CSS & JavaScript','Learn HTML5, CSS3 Flexbox/Grid, JavaScript ES6+, and responsive design.','text',NULL,NULL,2,30,1,1,NULL,'2026-07-03 01:37:09'),(154,16,'Backend Development & API Design','Master server-side programming, RESTful APIs, routing, controllers, and authentication.','text',NULL,NULL,3,30,0,1,NULL,'2026-07-03 01:37:09'),(155,16,'Database Design & Management','Explore relational vs NoSQL, normalization, SQL queries, ORM, and migrations.','text',NULL,NULL,4,25,0,1,NULL,'2026-07-03 01:37:09'),(156,16,'Deployment, Testing & DevOps Basics','Learn unit testing, CI/CD pipelines, cloud deployment, and monitoring.','text',NULL,NULL,5,25,0,1,NULL,'2026-07-03 01:37:09'),(157,17,'Introduction to Business Strategy & Leadership','Learn what business strategy is, the role of leadership, and strategic management process.','text',NULL,NULL,1,20,1,1,NULL,'2026-07-03 01:37:11'),(158,17,'Strategic Planning Frameworks & Tools','Master SWOT, PESTLE, Porter\'s Five Forces, BCG Matrix, and Balanced Scorecard.','text',NULL,NULL,2,25,1,1,NULL,'2026-07-03 01:37:11'),(159,17,'Leadership Styles & Team Development','Explore autocratic, democratic, transformational, servant leadership, and team building.','text',NULL,NULL,3,25,0,1,NULL,'2026-07-03 01:37:11'),(160,17,'Competitive Analysis & Market Positioning','Learn competitor analysis, value proposition, segmentation, and Blue Ocean Strategy.','text',NULL,NULL,4,30,0,1,NULL,'2026-07-03 01:37:11'),(161,17,'Innovation, Change Management & Strategic Execution','Understand innovation, Kotter\'s change model, overcoming resistance, and execution.','text',NULL,NULL,5,25,0,1,NULL,'2026-07-03 01:37:11'),(162,18,'What is Programming?','Learn what programming is, why it matters, and how computers execute code.','text',NULL,NULL,1,15,1,1,NULL,NULL),(163,18,'Variables and Data Types','Understand variables, strings, numbers, booleans, and how to store data.','text',NULL,NULL,2,20,1,1,NULL,NULL),(164,18,'Conditional Statements','Master if-else statements and logical operators to make decisions in code.','text',NULL,NULL,3,25,0,1,NULL,NULL),(165,18,'Loops and Iteration','Learn for loops, while loops, and how to repeat tasks efficiently.','text',NULL,NULL,4,25,0,1,NULL,NULL),(166,18,'Functions and Basic Projects','Create reusable code blocks with Functions and build your first simple project.','text',NULL,NULL,5,30,0,1,NULL,NULL);
/*!40000 ALTER TABLE `lessons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_01_01_000000_create_passkeys_table',1),(5,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(6,'2026_06_13_130807_create_course_categories_table',1),(7,'2026_06_13_130813_create_courses_table',1),(8,'2026_06_13_130819_create_course_templates_table',1),(9,'2026_06_13_130824_create_lessons_table',1),(10,'2026_06_13_130830_create_enrollments_table',1),(11,'2026_06_13_130836_create_lesson_progress_table',1),(12,'2026_06_13_130842_create_quizzes_table',1),(13,'2026_06_13_130851_create_quiz_questions_table',1),(14,'2026_06_13_130856_create_quiz_results_table',1),(15,'2026_06_13_130902_create_assignments_table',1),(16,'2026_06_13_130907_create_submissions_table',1),(17,'2026_06_13_130914_create_payments_table',1),(18,'2026_06_13_130919_create_certificates_table',1),(19,'2026_06_13_130925_create_course_reviews_table',1),(20,'2026_06_13_130931_create_learning_paths_table',1),(21,'2026_06_13_130935_create_learning_path_courses_table',1),(22,'2026_06_13_130940_create_ai_recommendations_table',1),(23,'2026_06_13_131743_create_roles_table',1),(24,'2026_06_13_131746_create_user_roles_table',1),(25,'2026_06_19_060210_update_learning_paths_table',2),(26,'2026_06_19_060934_update_learning_path_courses_table',3),(27,'2026_06_19_110518_add_ai_fields_to_learning_paths_table',4),(28,'2026_06_21_085552_add_course_order_to_learning_path_courses_table',5),(29,'2026_06_22_144422_create_transactions_table',6);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passkeys`
--

DROP TABLE IF EXISTS `passkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `passkeys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `credential` json NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`),
  CONSTRAINT `passkeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passkeys`
--

LOCK TABLES `passkeys` WRITE;
/*!40000 ALTER TABLE `passkeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `passkeys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint unsigned NOT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_a` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_b` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_c` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_d` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correct_answer` enum('A','B','C','D') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_questions_quiz_id_foreign` (`quiz_id`),
  CONSTRAINT `quiz_questions_quiz_id_foreign` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=433 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
INSERT INTO `quiz_questions` VALUES (18,9,'What is the basic accounting equation?','Assets = Liabilities + Equity','Assets = Revenue - Expenses','Cash = Income','Revenue = Equity','A',1,'2026-06-20 06:26:00','2026-06-20 06:26:00'),(19,9,'Which side of an account is increased when recording an asset?','Credit','Debit','Balance','Adjustment','B',1,'2026-06-20 06:26:34','2026-06-20 06:26:34'),(20,9,'Which accounting record is used to initially record business transactions?','Ledger','Trial Balance','Journal','Income Statement','C',1,'2026-06-20 06:27:14','2026-06-20 06:27:14'),(21,9,'Which financial statement shows a company\'s assets, liabilities, and equity?','Income Statement','Cash Flow Statement','Statement of Changes in Equity','Balance Sheet','B',1,'2026-06-20 06:27:44','2026-06-20 06:27:44'),(22,9,'What happens when a company receives cash from a customer?','Assets increase','Assets decrease','Liabilities decrease','Equity decreases','A',1,'2026-06-20 06:28:12','2026-06-20 06:28:12'),(23,10,'What is the primary purpose of a business plan?','To guide business operations and goals','To increase taxes','To hire employees only','To create advertisements','A',1,'2026-06-22 00:57:15','2026-06-22 00:57:15'),(24,10,'What does SMART stand for in goal setting?','Simple, Modern, Accurate, Relevant, Timely','Specific, Measurable, Achievable, Relevant, Time-bound','Strategic, Marketable, Affordable, Reliable, Tested','Systematic, Meaningful, Active, Realistic, Trackable','B',1,'2026-06-22 00:57:59','2026-06-22 00:57:59'),(25,10,'Why is market analysis important?','To understand customers and competitors','To increase expenses','To reduce product quality','To avoid planning','A',1,'2026-06-22 00:58:26','2026-06-22 00:58:26'),(26,10,'Which of the following is considered a business resource?','Human resources','Weather conditions','Social media trends only','Competitor advertisements','A',1,'2026-06-22 00:58:49','2026-06-22 00:58:49'),(27,10,'Which performance indicator helps measure business success?','Profit margin','Office paint color','Number of desks','Building height','B',1,'2026-06-22 00:59:12','2026-06-22 00:59:12'),(28,11,'What is the main purpose of financial planning?','To manage income and expenses effectively','To increase taxes','To avoid budgeting','To eliminate employees','A',1,'2026-06-22 01:06:16','2026-06-22 01:06:16'),(29,11,'Why is employee management important?','To increase workplace conflicts','To improve productivity and morale','To reduce communication','To increase turnover','B',1,'2026-06-22 01:06:40','2026-06-22 01:06:40'),(30,11,'What is the goal of Customer Relationship Management (CRM)?','To build strong customer relationships','To reduce product quality','To increase customer complaints','To avoid customer feedback','A',1,'2026-06-22 01:07:19','2026-06-22 01:07:19'),(31,11,'Which is a benefit of effective inventory management?','Increased waste','Higher expenses','Improved efficiency','Lower customer satisfaction','C',1,'2026-06-22 01:07:39','2026-06-22 01:07:39'),(32,11,'What is the purpose of risk management?','To ignore potential problems','To identify and minimize business risks','To increase uncertainty','To eliminate planning','B',1,'2026-06-22 01:08:29','2026-06-22 01:08:29'),(33,12,'What is the primary purpose of strategic planning?','To achieve long-term business goals','To increase daily expenses','To reduce customer satisfaction','To eliminate competition','A',1,'2026-06-22 01:26:39','2026-06-22 01:26:39'),(34,12,'Which leadership style encourages employee participation in decision-making?','Autocratic Leadership','Democratic Leadership','Passive Leadership','Authoritarian Leadership','B',1,'2026-06-22 01:27:04','2026-06-22 01:27:04'),(35,12,'What is competitive advantage?','A factor that allows a business to outperform competitors','A government regulation','A marketing expense','A business liability','A',1,'2026-06-22 01:27:26','2026-06-22 01:27:26'),(36,12,'Why is change management important?','To avoid innovation','To help organizations adapt effectively','To reduce employee productivity','To eliminate planning','B',1,'2026-06-22 01:27:53','2026-06-22 01:27:53'),(37,12,'What is the role of innovation in business?','To create value and support business growth','To increase operational risks only','To avoid decision-making','To reduce competitiveness','A',1,'2026-06-22 01:28:25','2026-06-22 01:28:25'),(113,29,'What is Laravel?','A PHP web application framework','A JavaScript library','A database management system','A CSS framework','A',1,'2026-07-02 23:11:37','2026-07-03 01:48:07'),(114,29,'Which design pattern does Laravel primarily follow?','Singleton','MVC (Model-View-Controller)','Observer','Factory','B',1,'2026-07-02 23:11:37','2026-07-03 01:48:07'),(115,29,'What is the command-line tool used in Laravel called?','Composer','NPM','Artisan','Tinker','C',1,'2026-07-02 23:11:37','2026-07-03 01:48:08'),(116,29,'Who originally created the Laravel framework?','Taylor Otwell','Evan You','Rasmus Lerdorf','Fabien Potencier','A',1,'2026-07-02 23:11:37','2026-07-03 01:48:08'),(117,29,'What templating engine does Laravel use by default?','Twig','Blade','Smarty','Handlebars','B',1,'2026-07-02 23:11:37','2026-07-03 01:48:09'),(118,30,'Question 1 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(119,30,'Question 2 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(120,30,'Question 3 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(121,30,'Question 4 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(122,30,'Question 5 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(123,31,'Question 1 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(124,31,'Question 2 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(125,31,'Question 3 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(126,31,'Question 4 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(127,31,'Question 5 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(128,32,'Question 1 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(129,32,'Question 2 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(130,32,'Question 3 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(131,32,'Question 4 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(132,32,'Question 5 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(133,33,'Question 1 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(134,33,'Question 2 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(135,33,'Question 3 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(136,33,'Question 4 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(137,33,'Question 5 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(138,34,'Question 1 about Introduction?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(139,34,'Question 2 about Introduction?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(140,34,'Question 3 about Introduction?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(141,34,'Question 4 about Introduction?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(142,34,'Question 5 about Introduction?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(143,35,'Question 1 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(144,35,'Question 2 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(145,35,'Question 3 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(146,35,'Question 4 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(147,35,'Question 5 about Core Concepts?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(148,36,'Question 1 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(149,36,'Question 2 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(150,36,'Question 3 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(151,36,'Question 4 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(152,36,'Question 5 about Practical Applications?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(153,37,'Question 1 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(154,37,'Question 2 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(155,37,'Question 3 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(156,37,'Question 4 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(157,37,'Question 5 about Advanced Topics?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(158,38,'Question 1 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(159,38,'Question 2 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(160,38,'Question 3 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(161,38,'Question 4 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(162,38,'Question 5 about Course Summary?','Correct Answer','Option B','Option C','Option D','A',1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(363,9,'What is the primary purpose of accounting?','To record and summarize financial transactions','To create websites','To design graphics','To manage social media','A',1,NULL,NULL),(364,9,'What is the basic accounting equation?','Assets = Liabilities + Owner\'s Equity','Assets = Income - Expenses','Profit = Assets + Liabilities','Revenue = Expenses + Assets','A',1,NULL,NULL),(365,9,'Which type of account normally increases with a debit?','Asset','Revenue','Owner\'s Equity','Liability','A',1,NULL,NULL),(366,9,'What is the main purpose of a journal entry?','To record business transactions in chronological order','To calculate employee salaries','To prepare tax returns only','To advertise products','A',1,NULL,NULL),(367,9,'Which financial statement shows a company\'s assets, liabilities, and owner\'s equity?','Balance Sheet','Income Statement','Cash Flow Statement','Statement of Changes in Equity','A',1,NULL,NULL),(368,79,'What is the purpose of financial accounting?','To record and report financial transactions','To design websites','To manage employees','To create advertisements','A',1,NULL,NULL),(369,79,'Which statement shows company performance over time?','Balance Sheet','Income Statement','Cash Register','Inventory List','B',1,NULL,NULL),(370,79,'What is the purpose of adjusting entries?','To correct accounts at period end','To hire employees','To sell products','To increase cash','A',1,NULL,NULL),(371,79,'What does a trial balance ensure?','Equal debits and credits','High profit','More customers','More expenses','A',1,NULL,NULL),(372,79,'Which statement shows financial position?','Income Statement','Balance Sheet','Cash Flow','Sales Report','B',1,NULL,NULL),(373,80,'What is cost accounting mainly used for?','To analyze and control business costs','To design websites','To manage employees','To sell products online','A',1,NULL,NULL),(374,80,'What is job order costing used for?','Mass production of identical goods','Custom or unique production jobs','Marketing analysis','Financial reporting','B',1,NULL,NULL),(375,80,'Which method tracks costs for continuous production?','Job order costing','Process costing','Cash accounting','Tax accounting','B',1,NULL,NULL),(376,80,'What is budgeting used for?','Planning and controlling costs','Increasing sales only','Hiring employees','Designing products','A',1,NULL,NULL),(377,80,'What does cost analysis help in?','Decision making in business','Web development','Graphic design','Social media posting','A',1,NULL,NULL),(378,81,'What does HTML stand for?','HyperText Markup Language','HighText Machine Language','Hyper Transfer Markup Language','Home Tool Markup Language','A',1,NULL,NULL),(379,81,'Which language is used to style web pages?','Python','CSS','Java','PHP','B',1,NULL,NULL),(380,81,'Which language makes web pages interactive?','HTML','CSS','JavaScript','SQL','C',1,NULL,NULL),(381,81,'Which HTML tag creates a hyperlink?','<img>','<link>','<a>','<href>','C',1,NULL,NULL),(382,81,'What is the purpose of CSS?','Store data','Create databases','Style and layout web pages','Manage servers','C',1,NULL,NULL),(383,82,'What is the main purpose of business planning?','To define business goals and strategies','To hire employees','To design logos','To manage social media','A',1,NULL,NULL),(384,82,'Why is market research important?','To understand customers and demand','To increase expenses','To avoid business planning','To reduce sales','A',1,NULL,NULL),(385,82,'What does financial management involve?','Managing money and budgeting','Hiring designers','Creating ads only','Writing blogs','A',1,NULL,NULL),(386,82,'What is business operations?','Daily activities that run the business','Only marketing tasks','Only accounting work','Only hiring staff','A',1,NULL,NULL),(387,82,'What is business growth strategy?','Plan to expand and improve business','Closing business','Reducing customers','Ignoring competition','A',1,NULL,NULL),(408,83,'Which social media platform is generally considered best for B2B marketing and professional networking?','TikTok','Instagram','LinkedIn','Snapchat','C',1,NULL,NULL),(409,83,'What is the primary purpose of creating audience personas?','To increase post frequency','To understand and target ideal customer segments','To eliminate content calendars','To reduce advertising costs to zero','B',1,NULL,NULL),(410,83,'What does A/B testing help determine in social media advertising?','Exact follower count','Which ad version performs better','Best time to delete posts','Whether to use only organic reach','B',1,NULL,NULL),(411,83,'Which metric is MOST important for measuring ROI in social media?','Number of likes on a post','Conversion rate and revenue generated','Total hashtags used','Profile picture updates','B',1,NULL,NULL),(412,83,'What is user-generated content (UGC)?','Content created by the brand team','Content created by customers about the brand','Paid advertisements','Official press releases','B',1,NULL,NULL),(413,84,'What is the primary difference between descriptive and diagnostic analytics?','Descriptive asks \"what happened\" while diagnostic asks \"why did it happen\"','Descriptive predicts future trends','Descriptive uses only surveys','There is no difference','A',1,NULL,NULL),(414,84,'Which tool is commonly used for web analytics?','Microsoft Word','Google Analytics','Adobe Photoshop','Slack','B',1,NULL,NULL),(415,84,'What does \"churn prediction\" help identify?','Best logo color','Customers likely to stop using the product','Most popular social platform','Cheapest advertising method','B',1,NULL,NULL),(416,84,'What is Customer Lifetime Value (CLV)?','Cost of acquiring a new customer','Total revenue expected from a single customer over time','Daily advertising budget','Number of social media followers','B',1,NULL,NULL),(417,84,'What is the best practice when presenting data to executives?','Include every raw data point','Use complex technical jargon','Focus on key insights with clear visuals','Avoid charts and use only tables','C',1,NULL,NULL),(418,85,'What does \"Full Stack\" refer to in web development?','Only frontend','Only backend','Both frontend and backend','Only server hardware','C',1,NULL,NULL),(419,85,'Which CSS feature is used for responsive grid layouts?','Float','CSS Grid','Text-decoration','Z-index','B',1,NULL,NULL),(420,85,'Which HTTP method updates an existing resource in RESTful API?','GET','POST','PUT or PATCH','DELETE','C',1,NULL,NULL),(421,85,'What is database normalization used for?','Speed up all queries','Reduce redundancy and improve integrity','Make backups automatic','Eliminate foreign keys','B',1,NULL,NULL),(422,85,'What is the purpose of CI/CD in DevOps?','Manual testing once per month','Automate testing and deployment','Delete old repositories','Prevent new code','B',1,NULL,NULL),(423,86,'What does SWOT analysis evaluate?','Only financial statements','Strengths, Weaknesses, Opportunities, and Threats','Number of employees','Logo color scheme','B',1,NULL,NULL),(424,86,'Which leadership style focuses on empowering team members?','Autocratic','Servant leadership','Laissez-faire','Transactional','B',1,NULL,NULL),(425,86,'Which factor is NOT one of Porter\'s Five Forces?','Threat of new entrants','Bargaining power of suppliers','Government tax regulations','Rivalry among competitors','C',1,NULL,NULL),(426,86,'What is the primary goal of Blue Ocean Strategy?','Compete directly with existing competitors','Create uncontested market space','Reduce product quality','Copy market leader','B',1,NULL,NULL),(427,86,'What is the first step in Kotter\'s Change Model?','Implement changes immediately','Create a sense of urgency','Remove resisting employees','Declare change complete','B',1,NULL,NULL),(428,88,'What is the primary purpose of a variable in programming?','To display images on screen','To store and manage data values','To connect to the internet','To print documents','B',1,NULL,NULL),(429,88,'Which of the following is a valid data type?','Loop','String','Function','Variable','B',1,NULL,NULL),(430,88,'What does an if-else statement do?','Repeats code multiple times','Makes decisions based on conditions','Stores data permanently','Connects to a database','B',1,NULL,NULL),(431,88,'Which loop is best when you know exactly how many times to repeat?','While loop','For loop','If loop','Switch loop','B',1,NULL,NULL),(432,88,'What is a function in programming?','A type of variable','A reusable block of code that performs a specific task','A programming language','A computer hardware component','B',1,NULL,NULL);
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_results`
--

DROP TABLE IF EXISTS `quiz_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `quiz_id` bigint unsigned NOT NULL,
  `score` int NOT NULL,
  `total_items` int NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `remarks` enum('passed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempt_number` int NOT NULL DEFAULT '1',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_results_student_id_foreign` (`student_id`),
  KEY `quiz_results_quiz_id_foreign` (`quiz_id`),
  CONSTRAINT `quiz_results_quiz_id_foreign` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_results_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_results`
--

LOCK TABLES `quiz_results` WRITE;
/*!40000 ALTER TABLE `quiz_results` DISABLE KEYS */;
INSERT INTO `quiz_results` VALUES (1,3,11,3,5,60.00,'failed',1,'2026-07-03 22:44:46','2026-07-03 22:44:46','2026-07-03 22:44:46'),(2,3,11,4,5,80.00,'passed',2,'2026-07-03 22:46:17','2026-07-03 22:46:17','2026-07-03 22:46:17'),(3,3,86,5,5,100.00,'passed',1,'2026-08-31 19:12:06','2026-08-31 19:12:06','2026-08-31 19:12:06');
/*!40000 ALTER TABLE `quiz_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizzes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `lesson_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passing_score` int NOT NULL DEFAULT '75',
  `time_limit_minutes` int DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quizzes_course_id_foreign` (`course_id`),
  KEY `quizzes_lesson_id_foreign` (`lesson_id`),
  CONSTRAINT `quizzes_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quizzes_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
INSERT INTO `quizzes` VALUES (9,1,NULL,'Basic Accounting FInal Quiz','This quiz evaluates the student\'s understanding of basic accounting concepts including the accounting equation, debits and credits, journal entries, and financial statements.',75,15,1,'2026-06-20 06:25:20','2026-06-20 06:25:20'),(10,8,NULL,'Business Planning Fundamentals Final Quiz','This quiz evaluates the learner\'s understanding of business planning, goal setting, market analysis, business operations, and performance evaluation.',75,15,1,'2026-06-22 00:56:04','2026-06-22 00:56:04'),(11,9,NULL,'Small Business Management Final Quiz','This quiz evaluates the learner\'s understanding of financial planning, employee management, customer relationship management, inventory control, and business risk management.',75,15,1,'2026-06-22 01:05:43','2026-06-22 01:05:43'),(12,10,NULL,'Business Strategy and Leadership Final Quiz','This quiz evaluates the learner\'s understanding of strategic planning, leadership styles, competitive advantage, organizational development, innovation, and strategic decision-making.',75,15,1,'2026-06-22 01:25:52','2026-06-22 01:25:52'),(29,5,NULL,'Introduction Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(30,5,NULL,'Core Concepts Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(31,5,NULL,'Practical Applications Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(32,5,NULL,'Advanced Topics Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(33,5,NULL,'Course Summary Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(34,7,NULL,'Introduction Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(35,7,NULL,'Core Concepts Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(36,7,NULL,'Practical Applications Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(37,7,NULL,'Advanced Topics Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(38,7,NULL,'Course Summary Quiz','Answer the questions below.',70,10,1,'2026-07-02 23:11:37','2026-07-02 23:11:37'),(79,2,NULL,'Financial Accounting Final Quiz','Final assessment for Financial Accounting',70,15,1,'2026-07-03 00:06:15','2026-07-03 00:06:15'),(80,3,NULL,'Advanced Cost Accounting Final Quiz','Final assessment for Advanced Cost Accounting',70,15,1,'2026-07-03 00:11:27','2026-07-03 00:11:27'),(81,4,NULL,'Introduction to Web Development Final Quiz','Final assessment for Introduction to Web Development',70,15,1,'2026-07-03 00:15:38','2026-07-03 00:15:38'),(82,6,NULL,'Entrepreneurship Essentials Final Quiz','Final assessment for Entrepreneurship Essentials',70,15,1,'2026-07-03 00:34:10','2026-07-03 00:34:10'),(83,11,NULL,'Social Media Marketing Final Quiz','This quiz evaluates understanding of social media platforms, content creation, advertising, and analytics.',75,15,1,'2026-07-03 01:27:03','2026-07-03 01:27:03'),(84,13,NULL,'Marketing Analytics Final Quiz','This quiz evaluates understanding of data collection, descriptive/diagnostic analytics, predictive analytics, and data visualization.',75,15,1,'2026-07-03 01:27:03','2026-07-03 01:27:03'),(85,16,NULL,'Full Stack Development Final Quiz','This quiz evaluates understanding of frontend development, backend APIs, database design, and deployment.',75,15,1,'2026-07-03 01:27:03','2026-07-03 01:27:03'),(86,17,NULL,'Business Strategy and Leadership Final Quiz','This quiz evaluates understanding of strategic planning, leadership styles, competitive analysis, and change management.',75,15,1,'2026-07-03 01:27:04','2026-07-03 01:27:04'),(87,5,NULL,'Laravel Web Development Final Quiz','This quiz evaluates understanding of Laravel framework, MVC architecture, routing, Eloquent ORM, and authentication.',75,15,1,'2026-07-03 01:41:18','2026-07-03 01:41:18'),(88,18,NULL,'Introduction to Programming Final Quiz','Test your understanding of programming basics.',60,10,1,'2026-07-03 17:16:25','2026-07-03 17:16:25');
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','System Administrator','2026-06-16 19:44:35','2026-06-16 19:44:35'),(2,'teacher','Course Instructor','2026-06-16 19:44:35','2026-06-16 19:44:35'),(3,'student','Platform Learner','2026-06-16 19:44:35','2026-06-16 19:44:35'),(4,'super_admin','Super Administrator / EDP','2026-08-20 04:40:19','2026-08-20 04:40:19');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('6T1c9HKPQ7hpPGolhXLiLO0QoMeBKQ0GBGt5Qs9k',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJzenIzWDdDdkU5UExydWVXZ3gyOFgwM0I5RnppN3NDd1pycHZhZmREIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvcGF0aHdpc2UudGVzdFwvdGVhY2hlclwvY291cnNlc1wvMThcL2xlc3NvbnMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL3BhdGh3aXNlLnRlc3RcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1788266354),('nPPuJepGFFTbkoi11VeiU1x3WVXScKMBnYyQiT2s',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','eyJfdG9rZW4iOiJCcTRMMldEVmNuMWRqT3Z1TnJmUVZsN2RjRDdJQkVEWjRQY2hacVp1IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvcGF0aHdpc2UudGVzdFwvcmVjb21tZW5kZWQtY291cnNlcyIsInJvdXRlIjoic3R1ZGVudC5yZWNvbW1lbmRhdGlvbnMifSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjN9',1788232375),('rxTLrQ9bybWVU4M6toRY7yVa8JKnQj929b0CN9mt',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJHbWlzTXMxT2d6ZEJOYWIwWmFBcXpxa3pOV1ZXRjZKUXRMWVdZcTM4IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvcGF0aHdpc2UudGVzdFwvdGVhY2hlclwvbXktY291cnNlcyIsInJvdXRlIjoidGVhY2hlci5teS1jb3Vyc2VzIn0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoyfQ==',1788232099),('SzlQoJyPE3m7k8BQRJ8clPOf2ESn4svfzYRhe3S1',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','eyJfdG9rZW4iOiJXNEY3WDMzT2k1bHNzeWlOOFBoMVE3YkxEcEF2dDg0NmtLNXk1TVQzIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvcGF0aHdpc2UudGVzdFwvcmVjb21tZW5kZWQtY291cnNlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvcGF0aHdpc2UudGVzdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1788266312);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `answer_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int DEFAULT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('submitted','graded','returned') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submissions_assignment_id_foreign` (`assignment_id`),
  KEY `submissions_student_id_foreign` (`student_id`),
  CONSTRAINT `submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `transaction_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_proof` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_transaction_no_unique` (`transaction_no`),
  KEY `transactions_student_id_foreign` (`student_id`),
  KEY `transactions_course_id_foreign` (`course_id`),
  KEY `transactions_approved_by_foreign` (`approved_by`),
  CONSTRAINT `transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,3,9,'TRX-20260704-00001',599.00,'GCash','533755','payment-proofs/cneSEHgSPPCMyuPeCLgtkKpkKUzRYJ2tBQONQYYT.png','approved',NULL,1,'2026-07-03 22:43:23','2026-07-03 22:40:28','2026-07-03 22:43:23'),(2,3,17,'TRX-20260824-00001',1299.00,'PayMongo','cs_7c6c497d54811a5da4b69ba4',NULL,'approved',NULL,1,'2026-08-23 18:26:46','2026-08-23 17:29:47','2026-08-23 18:26:46'),(3,3,16,'TRX-20260824-00002',699.00,'PayMongo','cs_6bc5d494d202a6d9ae3a7b97',NULL,'rejected','Payment rejected by administrator.',1,'2026-08-23 18:26:57','2026-08-23 17:45:41','2026-08-23 18:26:57'),(4,3,13,'TRX-20260824-00003',699.00,'PayMongo','cs_264852071903287aeb2d6f55',NULL,'pending',NULL,NULL,NULL,'2026-08-23 18:29:03','2026-08-23 18:29:04'),(5,3,16,'TRX-20260825-00001',699.00,'PayMongo','cs_dd220c991e83642f54d19753',NULL,'approved',NULL,1,'2026-08-24 18:22:02','2026-08-24 18:20:52','2026-08-24 18:22:02'),(6,3,11,'TRX-20260825-00002',399.00,'PayMongo','cs_0971de6f61d82d3ec994edec',NULL,'approved',NULL,1,'2026-08-24 18:41:36','2026-08-24 18:39:25','2026-08-24 18:41:36'),(7,3,8,'TRX-20260825-00003',499.00,'PayMongo','cs_c5ded433ac2a1a2b3e5d3f93',NULL,'pending',NULL,NULL,NULL,'2026-08-24 18:44:42','2026-08-24 18:44:44'),(8,3,10,'TRX-20260825-00004',499.00,'PayMongo',NULL,NULL,'rejected','PayMongo connection error.',NULL,NULL,'2026-08-24 18:47:42','2026-08-24 18:47:52'),(9,3,10,'TRX-20260825-00005',499.00,'PayMongo',NULL,NULL,'rejected','PayMongo connection error.',NULL,NULL,'2026-08-24 18:47:54','2026-08-24 18:47:54'),(10,3,10,'TRX-20260825-00006',499.00,'PayMongo',NULL,NULL,'rejected','PayMongo connection error.',NULL,NULL,'2026-08-24 18:47:56','2026-08-24 18:48:14'),(11,3,10,'TRX-20260825-00007',499.00,'PayMongo','cs_ba26157c79ba78a64328d346',NULL,'pending',NULL,NULL,NULL,'2026-08-24 18:48:20','2026-08-24 18:48:21');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (2,2,2,NULL,NULL),(4,5,3,NULL,NULL),(6,1,4,NULL,NULL),(7,6,1,NULL,NULL),(11,3,3,NULL,NULL);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Administrator','superadmin@pathwise.test',NULL,'$2y$12$Y3OpJPsI199uruu1dPsYMuGacHBwDYLMZhcG1VbAeHRSZI6BBXNRi',NULL,NULL,NULL,'bF9vYHIWjEocWMWFnKF87AqZ68B5hvLLoRn2vw2JUSeDU1S8tHnL076Vyxz3','2026-06-16 19:44:35','2026-08-21 16:41:05'),(2,'Teacher Account','teacher@pathwise.test',NULL,'$2y$12$5VA8VQ/PMYe/zAQvJRpypur9B4A7ojDoz0LSWSFldFQamYpPtdjqK',NULL,NULL,NULL,NULL,'2026-06-16 19:44:35','2026-06-16 19:44:35'),(3,'Student Account','student@pathwise.test',NULL,'$2y$12$R9JVS8yZlxuZtnkAL.yP9uoeWm./U6ry9a3hrsYBv8ujTORlO8B9m',NULL,NULL,NULL,'cVBjpnnaFFQIlasHM89DSpedz2vqbKquyPloR9ndTtLtWWsno3XKH3cun37C','2026-06-16 19:44:35','2026-06-16 19:44:35'),(5,'Michael','unloading660@gmail.com',NULL,'$2y$12$wVdZD7lgmAZsAZbgB/UUre91wjcojU6y9xidlNzPly6mI.CA0boqe',NULL,NULL,NULL,NULL,'2026-06-26 01:57:09','2026-06-26 01:57:09'),(6,'Department Administrator','departmentadmin@pathwise.test',NULL,'$2y$12$IZkJILlsWU.twnG1qt6SI.aI5AYDU.xwSVyX1wd4RyZk40bhLb7ai',NULL,NULL,NULL,'ouEfy1ts6z8mxqZHJsqwT7Zt3Xdo61WnCDmyRDtJOPBfpMcYgmOXehBAsP7M','2026-08-21 16:34:52','2026-08-21 16:34:52');
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

-- Dump completed on 2026-09-01 21:06:51
