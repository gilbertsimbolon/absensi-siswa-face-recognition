-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: db_absensi_siswa
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('Ganjil','Genap') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ganjil',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_years_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_years`
--

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` VALUES (1,'2025/2026','Ganjil',1,'2026-10-15 13:55:26','2026-10-15 13:55:26');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `min_face_accuracy` decimal(5,2) NOT NULL DEFAULT '70.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (1,85.00,'2026-10-15 13:15:15','2026-10-20 05:06:42');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('hadir','terlambat','alpa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'alpa',
  `method` enum('face_recognition','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `snapshot_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_daily_attendance_unique` (`student_id`,`date`),
  KEY `attendances_class_id_date_index` (`class_id`,`date`),
  KEY `attendances_academic_year_id_date_index` (`academic_year_id`,`date`),
  KEY `attendances_status_index` (`status`),
  CONSTRAINT `attendances_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
INSERT INTO `attendances` VALUES (1,1,1,1,'2026-10-15','22:03:48','22:05:35','alpa','face_recognition',NULL,NULL,'Presensi otomatis melalui pindai wajah','2026-10-15 14:03:48','2026-10-15 14:05:35'),(7,1,1,1,'2026-10-19','18:27:39',NULL,'alpa','face_recognition',90.00,NULL,'Presensi melewati batas waktu toleransi (Alpa)','2026-10-19 10:27:39','2026-10-19 10:27:39'),(8,2,1,1,'2026-10-19','07:02:15',NULL,'hadir','face_recognition',94.37,NULL,'Presensi wajah Face AI (Akurasi: 94.37%, Waktu: 0.45s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(9,2,1,1,'2026-10-16','07:02:15',NULL,'hadir','face_recognition',94.37,NULL,'Presensi wajah Face AI (Akurasi: 94.37%, Waktu: 0.45s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(10,2,1,1,'2026-10-17','07:02:15',NULL,'hadir','face_recognition',94.37,NULL,'Presensi wajah Face AI (Akurasi: 94.37%, Waktu: 0.45s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(11,3,1,1,'2026-10-19','07:18:20',NULL,'terlambat','face_recognition',92.10,NULL,'Presensi wajah Face AI (Akurasi: 92.1%, Waktu: 0.48s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(12,3,1,1,'2026-10-16','07:18:20',NULL,'hadir','face_recognition',92.10,NULL,'Presensi wajah Face AI (Akurasi: 92.1%, Waktu: 0.48s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(13,3,1,1,'2026-10-17','07:18:20',NULL,'hadir','face_recognition',92.10,NULL,'Presensi wajah Face AI (Akurasi: 92.1%, Waktu: 0.48s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(14,4,1,1,'2026-10-19','06:54:10',NULL,'hadir','face_recognition',95.80,NULL,'Presensi wajah Face AI (Akurasi: 95.8%, Waktu: 0.51s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(15,4,1,1,'2026-10-16','06:54:10',NULL,'hadir','face_recognition',95.80,NULL,'Presensi wajah Face AI (Akurasi: 95.8%, Waktu: 0.51s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(16,4,1,1,'2026-10-17','06:54:10',NULL,'hadir','face_recognition',95.80,NULL,'Presensi wajah Face AI (Akurasi: 95.8%, Waktu: 0.51s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(17,5,1,1,'2026-10-19','07:08:18',NULL,'hadir','face_recognition',93.25,NULL,'Presensi wajah Face AI (Akurasi: 93.25%, Waktu: 0.46s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(18,5,1,1,'2026-10-16','07:08:18',NULL,'hadir','face_recognition',93.25,NULL,'Presensi wajah Face AI (Akurasi: 93.25%, Waktu: 0.46s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(19,5,1,1,'2026-10-17','07:08:18',NULL,'hadir','face_recognition',93.25,NULL,'Presensi wajah Face AI (Akurasi: 93.25%, Waktu: 0.46s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(20,6,1,1,'2026-10-19','07:22:45',NULL,'terlambat','face_recognition',91.40,NULL,'Presensi wajah Face AI (Akurasi: 91.4%, Waktu: 0.53s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(21,6,1,1,'2026-10-16','07:22:45',NULL,'hadir','face_recognition',91.40,NULL,'Presensi wajah Face AI (Akurasi: 91.4%, Waktu: 0.53s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(22,6,1,1,'2026-10-17','07:22:45',NULL,'hadir','face_recognition',91.40,NULL,'Presensi wajah Face AI (Akurasi: 91.4%, Waktu: 0.53s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:43:07'),(23,7,1,1,'2026-10-19','06:51:24',NULL,'hadir','face_recognition',96.15,NULL,'Presensi wajah Face AI (Akurasi: 96.15%, Waktu: 0.44s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(24,7,1,1,'2026-10-16','06:51:24',NULL,'hadir','face_recognition',96.15,NULL,'Presensi wajah Face AI (Akurasi: 96.15%, Waktu: 0.44s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(25,7,1,1,'2026-10-17','06:51:24',NULL,'hadir','face_recognition',96.15,NULL,'Presensi wajah Face AI (Akurasi: 96.15%, Waktu: 0.44s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(26,8,1,1,'2026-10-19','07:11:55',NULL,'hadir','face_recognition',92.80,NULL,'Presensi wajah Face AI (Akurasi: 92.8%, Waktu: 0.49s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(27,8,1,1,'2026-10-16','07:11:55',NULL,'hadir','face_recognition',92.80,NULL,'Presensi wajah Face AI (Akurasi: 92.8%, Waktu: 0.49s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(28,8,1,1,'2026-10-17','07:11:55',NULL,'hadir','face_recognition',92.80,NULL,'Presensi wajah Face AI (Akurasi: 92.8%, Waktu: 0.49s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:43:07'),(29,9,1,1,'2026-10-19','06:58:33',NULL,'hadir','face_recognition',94.90,NULL,'Presensi wajah Face AI (Akurasi: 94.9%, Waktu: 0.47s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(30,9,1,1,'2026-10-16','06:58:33',NULL,'hadir','face_recognition',94.90,NULL,'Presensi wajah Face AI (Akurasi: 94.9%, Waktu: 0.47s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(31,9,1,1,'2026-10-17','06:58:33',NULL,'hadir','face_recognition',94.90,NULL,'Presensi wajah Face AI (Akurasi: 94.9%, Waktu: 0.47s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(32,10,1,1,'2026-10-19','07:05:40',NULL,'hadir','face_recognition',93.60,NULL,'Presensi wajah Face AI (Akurasi: 93.6%, Waktu: 0.5s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(33,10,1,1,'2026-10-16','07:05:40',NULL,'hadir','face_recognition',93.60,NULL,'Presensi wajah Face AI (Akurasi: 93.6%, Waktu: 0.5s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(34,10,1,1,'2026-10-17','07:05:40',NULL,'hadir','face_recognition',93.60,NULL,'Presensi wajah Face AI (Akurasi: 93.6%, Waktu: 0.5s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(35,11,1,1,'2026-10-19','06:48:15',NULL,'hadir','face_recognition',97.00,NULL,'Presensi wajah Face AI (Akurasi: 97%, Waktu: 0.42s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(36,11,1,1,'2026-10-16','06:48:15',NULL,'hadir','face_recognition',97.00,NULL,'Presensi wajah Face AI (Akurasi: 97%, Waktu: 0.42s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(37,11,1,1,'2026-10-17','06:48:15',NULL,'hadir','face_recognition',97.00,NULL,'Presensi wajah Face AI (Akurasi: 97%, Waktu: 0.42s) - Sesuai','2026-10-19 12:24:44','2026-10-19 12:24:44'),(38,1,1,1,'2026-10-20','02:28:34',NULL,'hadir','face_recognition',NULL,NULL,'Presensi otomatis melalui pindai wajah','2026-10-19 18:28:34','2026-10-19 18:28:34');
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jam_masuk` time NOT NULL DEFAULT '07:00:00',
  `batas_terlambat` time NOT NULL DEFAULT '07:15:00',
  `batas_alpa` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classes_name_unique` (`name`),
  KEY `classes_grade_level_index` (`grade_level`),
  KEY `classes_teacher_id_index` (`teacher_id`),
  CONSTRAINT `classes_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,NULL,'X MIPA 1','X','07:00:00','07:15:00','08:30:00','2026-10-15 13:55:42','2026-10-15 13:55:42');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
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
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_16_043907_create_permission_tables',1),(5,'2026_06_16_133035_create_academic_years_table',1),(6,'2026_06_16_133036_create_teachers_table',1),(7,'2026_06_16_133037_create_classes_table',1),(8,'2026_06_16_133038_create_students_table',1),(9,'2026_10_05_060405_create_student_faces_table',1),(10,'2026_10_06_071422_create_teacher_faces_table',1),(11,'2026_10_06_093658_create_student_class_histories_table',1),(12,'2026_10_07_043224_create_attendances_table',1),(13,'2026_10_13_011610_create_notification_settings_table',1),(14,'2026_10_13_161531_create_app_settings_table',1),(15,'2026_10_20_131527_make_batas_alpa_nullable_in_classes_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wa_gateway_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http://127.0.0.1:3000',
  `wa_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `mail_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_port` int DEFAULT '587',
  `mail_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_encryption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'tls',
  `mail_from_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_from_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'SMAN 2 Tondano',
  `send_on_hadir` tinyint(1) NOT NULL DEFAULT '1',
  `send_on_terlambat` tinyint(1) NOT NULL DEFAULT '1',
  `send_on_alpa` tinyint(1) NOT NULL DEFAULT '1',
  `recipient_target` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ortu_dan_siswa',
  `admin_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_hadir` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_terlambat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_hadir` text COLLATE utf8mb4_unicode_ci,
  `template_terlambat` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
INSERT INTO `notification_settings` VALUES (1,'http://127.0.0.1:3000',NULL,0,'smtp.gmail.com',587,'gilbertttsimbolon@gmail.com','irfpezwggjaajadx','tls','gilbertttsimbolon@gmail.com','SMAN 2 Tondano',1,1,1,'parent','gilbertttsimbolon@gmail.com','Presensi Masuk: {nama_siswa} ({kelas}) - SMAN 2 Tondano','Pemberitahuan Keterlambatan: {nama_siswa} ({kelas}) - SMAN 2 Tondano',NULL,'Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas: {kelas}, NISN: {nisn}) telah hadir di SMAN 2 Tondano.\n\nStatus: {status}\nWaktu: {waktu} WITA\nTanggal: {tanggal}\n\nTerima kasih atas perhatian dan kerja samanya.','Yth. Bapak/Ibu {nama_ortu},\n\nDiberitahukan bahwa ananda *{nama_siswa}* (Kelas: {kelas}, NISN: {nisn}) tercatat *TERLAMBAT* saat hadir di SMAN 2 Tondano.\n\nStatus: {status}\nWaktu: {waktu} WITA (Batas Terlambat: {batas_terlambat} WITA)\nTanggal: {tanggal}\n\nMohon perhatian dan pendampingan Bapak/Ibu. Terima kasih.','2026-10-15 13:15:15','2026-10-20 05:30:53');
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(2,'user.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(3,'user.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(4,'user.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(5,'user.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(6,'teacher.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(7,'teacher.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(8,'teacher.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(9,'teacher.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(10,'student.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(11,'student.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(12,'student.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(13,'student.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(14,'class.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(15,'class.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(16,'class.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(17,'class.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(18,'subject.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(19,'subject.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(20,'subject.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(21,'subject.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(22,'academic-year.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(23,'academic-year.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(24,'academic-year.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(25,'academic-year.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(26,'device.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(27,'device.connect','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(28,'device.disconnect','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(29,'attendance.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(30,'attendance.create','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(31,'attendance.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(32,'attendance.delete','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(33,'report.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(34,'report.export','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(35,'setting.view','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(36,'setting.update','web','2026-10-15 13:15:12','2026-10-15 13:15:12');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(23,2),(24,2),(25,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(32,2),(33,2),(34,2),(35,2),(36,2),(1,3),(10,3),(29,3),(30,3),(31,3),(33,3),(1,4),(29,4),(1,5),(29,5),(33,5);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(2,'headmaster','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(3,'teacher','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(4,'student','web','2026-10-15 13:15:12','2026-10-15 13:15:12'),(5,'parent','web','2026-10-15 13:15:12','2026-10-15 13:15:12');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `sessions` VALUES ('3gMyDCfqamFicHc7P74RYzWqTlzwnAkvPA5mOV98',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJBTkNqWlFVdWJ5SGd0Rmd2RW0wVHMxZlVIYUFhMlRmdDlHajZLd2VjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9kYWZ0YXItc2lzd2EiLCJyb3V0ZSI6ImFwaS5wcmVzZW5zaS5kYWZ0YXItc2lzd2EifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792471751),('4NlYMmq16CzvdJimKH4O3InydgHyLsyGgMLxVIV3',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJHd2dwdFZ5dDBuZWJ5Z3lDaER6WmtyMm1iOHNGb2pwcjFSZVY2dDFrIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792472829),('6m9OeweQ4U0CEtUZKV3EDOP7w68tSZvolgxfOwo2',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJCaTl1U29QbWluRENJamxrWGxpZ284OVdqMHpuMkoxY2xwZVBTMWRDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9rb25maWd1cmFzaSIsInJvdXRlIjoiYXBpLnByZXNlbnNpLmtvbmZpZ3VyYXNpIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1792471138),('bFm4q0I5096JIx09ORuthYICRtm43d3ktBCJk1ux',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJKZTJVUmx6Wm43bmxnWTVDeDQ4cDR1OG1NM1hoczFIUmJ0TWtQTzVHIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYWRtaW5cL2Fic2Vuc2lcL3BpbmRhaSJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2FkbWluXC9kYXRhLXNpc3dhIiwicm91dGUiOiJhZG1pbi5zdHVkZW50LmluZGV4In0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1792473820),('dYVQWN1gOrPQG2BsPXzRSzXT2hx9LXesPQIOFtxE',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJHZ0RJUlRkcExwY255cUVscHFPZjlOeFBQckJQdkc0SEFZYU1Xa3FxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9rb25maWd1cmFzaSIsInJvdXRlIjoiYXBpLnByZXNlbnNpLmtvbmZpZ3VyYXNpIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1792472776),('dZmdXeMnKNVm1wXVfA2UbN4PaBmSPOEMMvgO18X4',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJuY1BTUGVSU0xiNE9RSnplYkxTMmhoSjhocW9UM2Z4ZTJHTlY0cXlVIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792473790),('e550nxVcdNVAkQeEfFblIqAVJ9vnpLBZJYW78s6h',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJUOFRaTVNEQWJJMUc2b2ZsZWZ4NWlPaFB3bVBSckV3VXRtaFEwak5SIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9kYWZ0YXItc2lzd2EiLCJyb3V0ZSI6ImFwaS5wcmVzZW5zaS5kYWZ0YXItc2lzd2EifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792472145),('eYot7BWOJxkbbuSxLzrctDM0VpjeihDVmM6KjleL',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJKZno2VVhHUjlYZFpndG0wRnVtbUlxTGZObEIzMWgyWmVRcVlsZmV0IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792472834),('NWgHGluXje5FV3Jj65v8J796iqyF49f4o7rN16nl',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiI3Nlp5S21jYkJyTTZqeUEwb1VyU1QyZW5nRndnRGtwbDBwT3BpelhxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9rb25maWd1cmFzaSIsInJvdXRlIjoiYXBpLnByZXNlbnNpLmtvbmZpZ3VyYXNpIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1792472152),('Q902rLzJLdmy46s9QIAjViHac5njS2gdVXTCpCNS',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJpek9kYTRWZXFqbXVYTnF6NlhpTVQzRmZYNUtuNHNDQThnWUJhVW5GIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2FkbWluXC9kYXRhLXNpc3dhIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYWRtaW5cL2Fic2Vuc2kiLCJyb3V0ZSI6ImFkbWluLmF0dGVuZGFuY2UuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=',1792501080),('rxZR27hkampB6b3aeS7SOul85IQGV1qDaRrfDarh',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJ5QXhHZDBMME5oQXo3M21SS3VGRjh0a3dsN1JYd3M0cmxKdjNYVHZYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9kYWZ0YXItc2lzd2EiLCJyb3V0ZSI6ImFwaS5wcmVzZW5zaS5kYWZ0YXItc2lzd2EifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792472775),('WNIeVyXMZUCAUiJoEbhFbt5HIjSAYaPoFxyaHB8p',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJBY0pGNUY1QmhubFk2Rm9aV3BVZTFCdHRHa1YydVNzbm5CTWpTWHM1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9kYWZ0YXItc2lzd2EiLCJyb3V0ZSI6ImFwaS5wcmVzZW5zaS5kYWZ0YXItc2lzd2EifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792471135),('xo3y8oHnXcvJnm5at7PWNdwVo4AjfqliEc3h6jf4',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJxY1c3WkR4ck9UNDRXWldlRzV0RER4WkJoakZiWnJUNkVZMEVpOHdHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL3ByZXNlbnNpXC9rb25maWd1cmFzaSIsInJvdXRlIjoiYXBpLnByZXNlbnNpLmtvbmZpZ3VyYXNpIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1792471755),('XVHKF6xp558DaRE2pNhu51a7LWVc6wVKq63GCNQk',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiJYUEVUTVEya1hiNzU3U2Y4dm16SEFUWHRlOXFFOUdUR2JDNGs5RGxiIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792471784),('zEgntFQwU6KFtblgb18sxFoukH9mwHSVT8Sf8TS5',NULL,'127.0.0.1','python-requests/2.31.0','eyJfdG9rZW4iOiI4ZnhhUFVlOUpmS3RDNzJadmlNZzZKaElFNE9uSzhlS3BEdGNsYmU0IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1792473796);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_class_histories`
--

DROP TABLE IF EXISTS `student_class_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_class_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_class_histories_academic_year_id_foreign` (`academic_year_id`),
  KEY `student_class_histories_student_id_academic_year_id_index` (`student_id`,`academic_year_id`),
  KEY `student_class_histories_class_id_academic_year_id_index` (`class_id`,`academic_year_id`),
  CONSTRAINT `student_class_histories_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_class_histories_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_class_histories_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_class_histories`
--

LOCK TABLES `student_class_histories` WRITE;
/*!40000 ALTER TABLE `student_class_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_class_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_faces`
--

DROP TABLE IF EXISTS `student_faces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_faces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `embedding` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_faces_student_id_index` (`student_id`),
  CONSTRAINT `student_faces_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_faces`
--

LOCK TABLES `student_faces` WRITE;
/*!40000 ALTER TABLE `student_faces` DISABLE KEYS */;
INSERT INTO `student_faces` VALUES (1,1,'faces/0001/1792072573_1_6ad0db7d90fe5.jpeg','[-0.09286487102508544, -0.02283536270260811, -0.0932728424668312, -0.01662951521575451, 0.041293591260910034, 0.04184120520949364, -0.02848266065120697, 0.09904834628105164, -0.07107769697904587, 0.12698890268802643, 0.049408186227083206, -0.04976779222488403, -0.08195527642965317, -0.21377387642860413, 0.043446969240903854, 0.04779697582125664, 0.08095870167016983, -0.01652110181748867, -0.00889679417014122, -0.10023556649684906, 0.15346287190914154, 0.0068491725251078606, -0.08396505564451218, 0.03529392555356026, -0.07214753329753876, 0.06193288415670395, -0.05255064740777016, 0.09187506884336472, 0.08513163030147552, 0.11072710156440736, 0.0263740848749876, 0.10320693999528883, 0.04372676461935043, -0.02584974281489849, 0.20149631798267365, -0.009052629582583904, -0.13644635677337646, -0.06434842199087143, 0.07772894203662872, -0.04233242943882942, -0.03691720589995384, 0.11052873730659483, -0.029750671237707135, -0.07334115356206894, 0.11153296381235124, 0.03681081160902977, 0.026067545637488365, -0.013237891718745232, 0.035496193915605545, -0.01033594086766243, -0.05188271775841713, 0.07429977506399155, 0.1462596207857132, -0.10427217930555344, -0.03808214142918587, 0.09301087260246275, 0.10387145727872849, -0.13838540017604828, 0.05029616877436638, -0.16220276057720184, -0.1446257084608078, -0.00421182019636035, -0.014204285107553003, 0.05238151177763939, 0.03473274037241936, -0.026471011340618137, 0.16198061406612396, 0.045777883380651474, 0.06308487057685852, -0.0718565285205841, 0.09150638431310654, -0.04326176643371582, -0.11699026077985764, -0.05378517881035805, 0.06835007667541504, 0.007556183263659477, -0.023182334378361705, 0.06531383097171783, -0.13628515601158142, 0.03042622283101082, 0.04509167745709419, -0.07259133458137512, -0.17385107278823853, -0.17270374298095703, 0.14860689640045166, -0.025790642946958545, -0.1458195596933365, 0.08554047346115112, -0.16914530098438263, 0.1170029416680336, 0.0214436836540699, 0.1714029759168625, -0.0787329375743866, 0.2085532546043396, 0.00218251789920032, -0.05327542498707771, 0.003635321045294404, -0.030577221885323524, -0.10122209042310716, 0.05286821722984314, -0.005044793710112572, -0.10016132891178132, -0.00030167127260938287, -0.08593890815973282, -0.12595921754837036, 0.09222570061683656, -0.05634677782654762, -0.05826924741268158, -0.03279854729771614, 0.18613117933273315, -0.0474579893052578, 0.10366123914718628, -0.08581466227769852, 0.07138992100954056, 0.07835932821035385, 0.011581122875213625, 0.023937640711665154, -0.16144128143787384, 0.0135275237262249, 0.0619683638215065, 0.0812828540802002, -0.049443524330854416, 0.03874649479985237, -0.038841649889945984, 0.06933597475290298, -0.12793204188346863, 0.0980888530611992, -0.016632532700896263]','2026-10-15 13:56:15','2026-10-18 17:30:19'),(2,1,'faces/0001/1791550586_photo_depan.jpeg','[-0.006621009204536676, 0.06485796719789505, -0.14410042762756348, 0.02191034145653248, 0.02168467454612255, -0.029809122905135155, 0.013243244029581549, 0.05501343309879303, -0.07239696383476257, 0.04001258313655853, 0.0662408322095871, -0.016069022938609123, -0.09384355694055556, -0.2168688327074051, 0.08308319002389908, 0.06013673543930054, 0.0912635251879692, -0.03026373870670795, -0.012574609369039536, -0.04856588691473007, 0.077959805727005, 0.005502667278051376, -0.10500655323266984, -0.03181157261133194, -0.08830230683088303, 0.01825422421097755, -0.1222555860877037, 0.13997311890125275, 0.037865906953811646, 0.08370727300643921, 0.05015459656715393, 0.023899411782622337, 0.040652405470609665, -0.03468222916126251, 0.1844853013753891, 0.091791070997715, -0.20655791461467743, -0.11420241743326189, 0.056741293519735336, -0.020165469497442245, -0.0987623780965805, 0.15020990371704102, -0.10269380360841752, -0.08903296291828156, 0.09126973152160645, 0.05589126795530319, -0.04014422371983528, 0.07015382498502731, -0.06628985702991486, 0.03520500659942627, -0.00962888915091753, 0.0430583655834198, 0.10965291410684586, -0.030961018055677417, 0.009071096777915956, 0.1469695270061493, 0.04547592252492905, -0.08916035294532776, 0.0027491857763379812, -0.13016872107982635, -0.12394098937511444, -0.02318410389125347, 0.016491524875164032, 0.0461953766644001, 0.11321166157722472, -0.03483307734131813, 0.11158334463834764, -0.002962365048006177, 0.05424097180366516, -0.03139301761984825, 0.15480506420135498, -0.01727294735610485, -0.11134988069534302, -0.025680124759674072, 0.03576217591762543, 0.06581518054008484, -0.011516218073666096, 0.09219086915254592, -0.08651223033666611, 0.04055153205990791, 0.05572685971856117, -0.03005582094192505, -0.14019225537776947, -0.18839240074157715, 0.2115938663482666, -0.17287243902683258, -0.15313757956027985, 0.007604191079735756, -0.0951584056019783, 0.06820894032716751, 0.016779176890850067, 0.15155138075351715, -0.07497357577085495, 0.2453081011772156, -0.09944894909858704, -0.06625454872846603, -0.03655882552266121, 0.00626915879547596, -0.09703841060400008, 0.05937537923455238, 0.0016989699797704816, -0.046442899852991104, 0.0176826361566782, -0.054793037474155426, -0.08272174000740051, 0.059213995933532715, 0.014597786590456964, -0.011575991287827492, -0.0489916168153286, 0.11540299654006958, -0.02633298560976982, 0.17422689497470856, -0.0864221528172493, 0.06508269160985947, 0.0909661278128624, 0.07781881839036942, 0.0881795734167099, -0.10719799250364304, 0.009277691133320332, 0.07825484126806259, 0.13541936874389648, -0.00010259498958475888, 0.06275953352451324, -0.09183772653341292, 0.06139233335852623, -0.11255069822072984, -0.02775564976036549, -0.06997509300708771]','2026-10-18 16:54:26','2026-10-18 17:30:19'),(3,1,'faces/0001/1791550590_photo_kanan.jpeg','[-0.05362337827682495, 0.06859496235847473, -0.2109135538339615, 0.023830313235521317, 0.06701599806547165, -0.08601240068674088, 0.02663736790418625, 0.045865677297115326, -0.05651867017149925, 0.05485440045595169, 0.1383957862854004, -0.044776227325201035, -0.05844436585903168, -0.23616135120391848, 0.09141802042722702, 0.033826928585767746, 0.08732076734304428, -0.06836386770009995, -0.04772857576608658, -0.08905530720949173, 0.0868171826004982, -0.051816411316394806, -0.1440141797065735, -0.00563220726326108, -0.11247438937425612, -0.0362478606402874, -0.078431636095047, 0.16518087685108185, -0.03474757447838783, 0.08802598714828491, 0.06674095243215561, 0.05783990770578384, 0.006268494762480259, -0.043730974197387695, 0.15611585974693298, 0.027141213417053223, -0.16739757359027865, -0.09722211956977844, 0.0873989462852478, -0.024783043190836903, -0.06091800704598427, 0.15177907049655914, -0.06378067284822464, -0.07293985038995743, 0.03580711781978607, 0.03410784527659416, 0.02508389949798584, 0.01136983558535576, -0.02182098478078842, 0.04790611565113067, -0.07823636382818222, 0.07746156305074692, 0.10120926052331924, -0.05336345359683037, 0.003130011260509491, 0.1601061373949051, 0.0707746371626854, -0.054020702838897705, 0.02560530975461006, -0.15459534525871277, -0.11739779263734818, 0.00037741599953733385, 0.02336648292839527, 0.07539231330156326, 0.1200137808918953, -0.036400824785232544, 0.07324414700269699, -0.018538832664489743, 0.03985350951552391, -0.07753194868564606, 0.11668497323989868, -0.01765959896147251, -0.09495189785957336, -0.034458186477422714, -0.041445598006248474, 0.07739455252885818, 0.00599747896194458, 0.09029623866081238, -0.11814770102500916, -0.01143801398575306, 0.04719319939613342, -0.023628301918506622, -0.1059936136007309, -0.15905728936195374, 0.17228583991527557, -0.1460067182779312, -0.14831240475177765, 0.02437971904873848, -0.14398787915706637, 0.08809753507375717, 0.03751508146524429, 0.090356707572937, -0.08616804331541061, 0.26274722814559937, -0.04431339353322983, -0.05755005404353142, -0.04644506424665451, -0.011447935365140438, -0.09875445067882538, 0.05274953320622444, -0.039004966616630554, -0.027781449258327484, 0.054910581558942795, -0.08320309966802597, -0.04259375110268593, 0.04662385210394859, 0.04311670735478401, 0.04454653337597847, -0.04727619141340256, 0.0952656865119934, 0.018094921484589577, 0.11707215756177902, -0.06349130719900131, 0.12700743973255155, 0.03060907870531082, 0.08683449774980545, 0.10418561846017838, -0.10601571947336196, -0.019413193687796593, 0.13662254810333252, 0.08786968886852264, 0.036118943244218826, 0.05273832380771637, -0.09553983062505722, 0.003270315704867244, -0.11903148889541626, -0.02023836225271225, -0.1389627605676651]','2026-10-18 16:54:26','2026-10-18 17:30:19'),(4,1,'faces/0001/1791550590_photo_kiri.jpeg','[-0.07129214704036713, -0.03164075314998627, -0.07669723033905029, -0.025046328082680706, 0.041614558547735214, 0.038325853645801544, -0.03674347326159477, 0.09668564796447754, -0.08021697402000427, 0.11945094168186188, 0.029180264100432396, -0.04525155946612358, -0.09673821181058884, -0.1935390681028366, 0.028181228786706924, 0.023435508832335472, 0.08049479871988297, -0.03305197134613991, -0.012786407954990864, -0.11936851590871812, 0.15283487737178802, -0.023583466187119484, -0.07678234577178955, 0.030243627727031708, -0.0550265833735466, 0.05324629321694374, -0.0403742678463459, 0.07422075420618057, 0.09240593761205672, 0.11270538717508316, 0.01818832941353321, 0.12736785411834717, 0.02584961429238319, 0.001848066342063248, 0.19373925030231476, 0.0016307812184095385, -0.138053297996521, -0.05589757487177849, 0.09010178595781326, -0.04518456757068634, -0.01212190743535757, 0.11641529947519302, -0.06300990283489227, -0.06962709128856659, 0.12257099896669388, 0.032957665622234344, 0.03885403648018837, -0.00921320542693138, 0.03973116725683212, -0.023983189836144447, -0.07261227071285248, 0.06875351071357727, 0.17052650451660156, -0.10787111520767212, -0.022022848948836327, 0.08086086809635162, 0.09938007593154909, -0.15426164865493774, 0.0830359235405922, -0.1444029062986374, -0.14967738091945648, -0.036751870065927505, -0.01378616038709879, 0.012583843432366848, 0.01872134581208229, -0.022613871842622757, 0.1545412838459015, 0.04401737451553345, 0.06526399403810501, -0.058874085545539856, 0.07370481640100479, -0.026328155770897865, -0.157467320561409, -0.041078757494688034, 0.055403225123882294, -0.005176128353923559, -0.03457596153020859, 0.06391402333974838, -0.11425717175006866, 0.014794557355344296, 0.05595897510647774, -0.09061609953641891, -0.1634240746498108, -0.17459557950496674, 0.15115191042423248, -0.006227815989404917, -0.1374705582857132, 0.06164384260773659, -0.1661268174648285, 0.12912920117378235, 0.004440044518560171, 0.17427173256874084, -0.1043444275856018, 0.18609365820884705, 0.014762628823518751, -0.0409693643450737, 0.009631379507482052, -0.053223587572574615, -0.07456597685813904, 0.0787583440542221, -0.024504268541932103, -0.09465833753347395, -0.02913767099380493, -0.08884163945913315, -0.1335955560207367, 0.12021961063146593, -0.07779817283153534, -0.04945254325866699, -0.05929072201251984, 0.1833578795194626, -0.049332454800605774, 0.0954660028219223, -0.09734641015529633, 0.031704939901828766, 0.08120288699865341, 0.030056869611144062, -0.0005701503250747919, -0.1832251101732254, -0.0007995764608494937, 0.04013921692967415, 0.08967725187540054, -0.06112932413816452, 0.02452579140663147, -0.05532127991318703, 0.052611541002988815, -0.10948778688907625, 0.10049404948949814, -0.03546641767024994]','2026-10-18 16:54:26','2026-10-18 17:30:19');
/*!40000 ALTER TABLE `student_faces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nisn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `gender` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_nisn_unique` (`nisn`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_nisn_index` (`nisn`),
  KEY `students_class_id_index` (`class_id`),
  KEY `students_status_index` (`status`),
  KEY `students_gender_index` (`gender`),
  CONSTRAINT `students_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,NULL,'Josep Gilbert Andriano Simbolon','0001',1,'L',NULL,'gilbertttsimbolon@gmail.com','Maca Simbolon',NULL,'22210149@unima.ac.id','aktif','2026-10-15 13:56:13','2026-10-20 05:30:23'),(2,NULL,'Rian Runtuwene','0021000101',1,'L',NULL,'rian.runtuwene@gmail.com','Johan Runtuwene',NULL,'johan.runtuwene@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(3,NULL,'Kezia Tambayong','0021000102',1,'P',NULL,'kezia.tambayong@gmail.com','Frans Tambayong',NULL,'frans.tambayong@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(4,NULL,'Rezky Kembuan','0021000103',1,'L',NULL,'rezky.kembuan@gmail.com','Ronald Kembuan',NULL,'ronald.kembuan@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(5,NULL,'Priscilla Walukow','0021000104',1,'P',NULL,'priscilla.walukow@gmail.com','Max Walukow',NULL,'max.walukow@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(6,NULL,'Christo Polii','0021000105',1,'L',NULL,'christo.polii@gmail.com','Donny Polii',NULL,'donny.polii@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(7,NULL,'Stela Mangundap','0021000106',1,'P',NULL,'stela.mangundap@gmail.com','Bobby Mangundap',NULL,'bobby.mangundap@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(8,NULL,'Farel Sumendap','0021000107',1,'L',NULL,'farel.sumendap@gmail.com','Jimmy Sumendap',NULL,'jimmy.sumendap@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(9,NULL,'Angelica Lapian','0021000108',1,'P',NULL,'angelica.lapian@gmail.com','Royke Lapian',NULL,'royke.lapian@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(10,NULL,'Devan Rorong','0021000109',1,'L',NULL,'devan.rorong@gmail.com','Stenly Rorong',NULL,'stenly.rorong@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23'),(11,NULL,'Gisela Senduk','0021000110',1,'P',NULL,'gisela.senduk@gmail.com','Ferry Senduk',NULL,'ferry.senduk@gmail.com','aktif','2026-10-19 12:24:44','2026-10-20 05:30:23');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_faces`
--

DROP TABLE IF EXISTS `teacher_faces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_faces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_faces_teacher_id_index` (`teacher_id`),
  CONSTRAINT `teacher_faces_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_faces`
--

LOCK TABLES `teacher_faces` WRITE;
/*!40000 ALTER TABLE `teacher_faces` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_faces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teachers_nip_unique` (`nip`),
  KEY `teachers_user_id_index` (`user_id`),
  KEY `teachers_nip_index` (`nip`),
  CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','administrator@smandutdo.com',NULL,'$2y$12$AH.7yE/qUa44uyEbkM0hpuzI42.1lN6YDp5sp9f3hh3Wp7520ivLO',NULL,'2026-10-15 13:15:14','2026-10-15 13:15:14');
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

-- Dump completed on 2026-10-20 23:46:05
