-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2025 at 05:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `careprodb`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `id` int(12) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_no` int(11) NOT NULL,
  `group` varchar(120) NOT NULL,
  `message` longtext NOT NULL,
  `sms_sent` tinyint(1) DEFAULT 0,
  `sms_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `demo_payments`
--

CREATE TABLE `demo_payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `demo_type` enum('demo1','demo2','demo3','demo4') NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `expected_amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `is_full_payment` tinyint(1) DEFAULT 0,
  `allocation_source` enum('regular','excess_allocation') DEFAULT 'regular',
  `processed_by` varchar(50) DEFAULT 'System',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `enhanced_payment_summary`
-- (See below for the actual view)
--
CREATE TABLE `enhanced_payment_summary` (
`transaction_id` int(12)
,`student_id` int(11)
,`student_name` varchar(201)
,`program_name` varchar(255)
,`payment_type` varchar(50)
,`demo_type` varchar(50)
,`cash_received` decimal(10,2)
,`change_amount` decimal(10,2)
,`balance` decimal(10,2)
,`enrollment_status` varchar(50)
,`learning_mode` varchar(50)
,`package_name` varchar(200)
,`transaction_date` datetime
,`processed_by` varchar(50)
,`has_excess_processing` varchar(3)
);

-- --------------------------------------------------------

--
-- Table structure for table `excess_processing_log`
--

CREATE TABLE `excess_processing_log` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `excess_amount` decimal(10,2) NOT NULL,
  `excess_choice` enum('treat_as_initial','allocate_to_demos','return_as_change') NOT NULL,
  `allocations_data` text DEFAULT NULL,
  `final_change_amount` decimal(10,2) DEFAULT 0.00,
  `processed_at` datetime NOT NULL,
  `processed_by` varchar(50) DEFAULT 'System',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `level_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `level_name`, `created_at`, `updated_at`) VALUES
(2, 'Manager', '2025-06-08 22:34:41', '2025-06-08 22:34:41'),
(3, 'Staff', '2025-06-08 22:34:41', '2025-06-08 22:34:41'),
(4, 'Guest', '2025-06-08 22:34:41', '2025-06-08 22:34:41'),
(5, 'Developer', '2025-06-08 22:44:30', '2025-06-08 22:44:30');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(12) NOT NULL,
  `user_id` int(12) NOT NULL,
  `activity` varchar(120) NOT NULL,
  `dnt` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `activity`, `dnt`) VALUES
(1, 2, 'login in', '2025-06-06 17:25:08'),
(2, 2, 'Added Program: ', '2025-06-06 17:41:13'),
(3, 2, 'Deleted Program: 14', '2025-06-06 17:43:12'),
(4, 2, 'Added Program: ', '2025-06-06 17:53:56'),
(5, 2, 'Edited Program: Care Giving', '2025-06-06 18:27:34'),
(6, 2, 'Edited Program: Care Givings', '2025-06-06 18:27:45'),
(7, 2, 'Edited Program: Care Givings', '2025-06-06 18:41:53'),
(8, 2, 'Added Program: Care Giving', '2025-06-06 19:25:26'),
(9, 2, 'Added Student: 20250506A0001', '2025-06-06 19:26:43'),
(10, 2, 'Edited Program: Care Givings', '2025-06-06 19:38:19'),
(11, 2, 'Added Program: Care Givings', '2025-06-06 19:41:31'),
(12, 2, 'Deleted Program: 15', '2025-06-06 19:41:44'),
(13, 2, 'Added new Teacher: Rohan', '2025-06-06 21:40:09'),
(14, 2, 'Edited Teacher: Rohan2', '2025-06-06 21:40:15'),
(15, 2, 'Deleted Teacher: ', '2025-06-06 21:40:19'),
(16, 2, 'Deleted Teacher: ', '2025-06-06 21:40:22'),
(17, 2, 'Deleted Teacher: ', '2025-06-06 21:40:27'),
(18, 2, 'Deleted Teacher: ', '2025-06-06 21:40:35'),
(19, 2, 'Deleted Teacher: ', '2025-06-06 21:41:37'),
(20, 2, 'Deleted Users: 2', '2025-06-06 21:41:50'),
(21, 2, 'Added User: admin@holycross.edu.ph', '2025-06-06 21:41:59'),
(22, 2, 'Deleted Users: 5', '2025-06-06 21:42:06'),
(23, 2, 'login in', '2025-06-06 21:42:34'),
(24, 2, 'Deleted Users: 5', '2025-06-06 21:42:39'),
(25, 2, 'Deleted Users: 5', '2025-06-06 21:42:52'),
(26, 2, 'Deleted Users: 5', '2025-06-06 21:42:58'),
(27, 2, 'Deleted Users: 5', '2025-06-06 21:44:18'),
(28, 2, 'Deleted Program: 17', '2025-06-06 21:48:04'),
(29, 2, 'Deleted Program: 16', '2025-06-06 21:48:08'),
(30, 2, 'Added Program: Care Giving', '2025-06-06 21:50:22'),
(31, 2, 'Deleted Program: 17', '2025-06-06 21:50:57'),
(32, 2, 'Added Program: Care Givings', '2025-06-07 07:50:31'),
(33, 2, 'Added Program: Nursing Aid', '2025-06-07 07:56:41'),
(34, 2, 'Added Program: Nursing Aid', '2025-06-07 07:57:11'),
(35, 2, 'Deleted Program: 20', '2025-06-07 07:57:17'),
(36, 2, 'Edited Program: Nursing Aid2', '2025-06-07 08:00:08'),
(37, 2, 'Added Student: 20250506A0001', '2025-06-07 08:13:00'),
(38, 2, 'Added Program: Computer Science', '2025-06-07 08:17:13'),
(39, 2, 'Edited Program: Computer Science', '2025-06-07 08:17:25'),
(40, 2, 'Added Student: 20250607A0002', '2025-06-07 09:11:05'),
(41, 2, 'Added Student: 20250607A0003', '2025-06-07 09:14:51'),
(42, 2, 'Added Student: 20250607A0004', '2025-06-07 09:19:52'),
(43, 2, 'Added Student: 20250607A0005', '2025-06-07 09:26:04'),
(44, 2, 'Added Student: 20250607A0006', '2025-06-07 09:27:38'),
(45, 2, 'Added Student: 20250607A0007', '2025-06-07 09:29:30'),
(46, 2, 'Added Student: 20250607A0008', '2025-06-07 09:33:19'),
(47, 2, 'Added Student: 20250607A0009', '2025-06-07 09:34:40'),
(48, 2, 'Added Student: 20250607A0010', '2025-06-07 09:38:28'),
(49, 2, 'Added Program: Care Giving', '2025-06-07 12:10:55'),
(50, 2, 'Deleted Program: 21', '2025-06-07 12:48:40'),
(51, 2, 'Added Program: Electrical NC II', '2025-06-07 12:52:09'),
(52, 2, 'login in', '2025-06-07 13:35:53'),
(53, 2, 'Added Program: Nursing Aid NC3', '2025-06-07 17:41:52'),
(54, 2, 'Deleted Program: 22', '2025-06-07 17:42:19'),
(55, 2, 'Deleted Program: 24', '2025-06-07 17:42:25'),
(56, 2, 'login in', '2025-06-07 19:24:16'),
(57, 2, 'login in', '2025-06-07 19:28:50'),
(58, 2, 'login in', '2025-06-08 06:49:33'),
(59, 2, 'login in', '2025-06-08 08:10:26'),
(60, 2, 'Edited Program: Nursing Aid NC3', '2025-06-08 09:09:04'),
(61, 2, 'login in', '2025-06-08 14:31:23'),
(62, 2, 'Added Program: Care Giving NC2', '2025-06-08 14:50:20'),
(63, 2, 'Edited Program: Care Giving NC2', '2025-06-08 14:55:54'),
(64, 2, 'Edited Program: Care Giving NC2', '2025-06-08 15:17:30'),
(65, 2, 'Added Program: Care Giving NC4', '2025-06-08 15:28:28'),
(66, 2, 'Edited Program: Care Giving NC4', '2025-06-08 15:29:28'),
(67, 2, 'login in', '2025-06-08 15:46:15'),
(68, 2, 'login in', '2025-06-08 17:51:33'),
(69, 2, 'login in', '2025-06-08 18:39:01'),
(70, 2, 'Added Program:  WEB DEV', '2025-06-08 18:47:25'),
(71, 2, 'Added Program: SMAW NC2', '2025-06-08 22:23:51'),
(72, 2, 'Edited Program: SMAW NC2', '2025-06-08 22:24:18'),
(73, 2, 'Added Program: asdasdasd', '2025-06-09 00:24:38'),
(74, 2, 'login in', '2025-06-09 06:19:36'),
(75, 2, 'login in', '2025-06-09 06:22:45'),
(76, 2, 'Updated Level: Manager to Manager (ID: 2)', '2025-06-09 06:37:57'),
(77, 2, 'login in', '2025-06-10 17:09:40'),
(78, 2, 'login in', '2025-06-10 20:58:03'),
(79, 2, 'login in', '2025-06-10 22:00:52'),
(80, 2, 'login in', '2025-06-11 11:52:49'),
(81, 2, 'login in', '2025-06-11 15:04:50'),
(82, 2, 'login in', '2025-06-11 19:51:40'),
(83, 2, 'login in', '2025-06-12 05:48:21'),
(84, 2, 'login in', '2025-06-12 11:26:53'),
(85, 2, 'login in', '2025-06-13 15:16:37'),
(86, 2, 'login in', '2025-06-13 21:16:57'),
(87, 2, 'Added Program: Bookeeping NC2', '2025-06-13 21:21:18'),
(88, 2, 'Added Program:  WEB DEV2', '2025-06-13 21:52:31'),
(89, 2, 'login in', '2025-06-13 22:40:45'),
(90, 2, 'login in', '2025-06-13 22:54:01'),
(91, 2, 'login in', '2025-06-13 22:55:45'),
(92, 2, 'login in', '2025-06-14 09:02:50'),
(93, 2, 'login in', '2025-06-14 10:25:29'),
(94, 2, 'login in', '2025-06-18 06:28:03'),
(95, 2, 'login in', '2025-06-18 18:10:49'),
(96, 2, 'login in', '2025-06-19 09:58:02'),
(97, 2, 'login in', '2025-06-19 15:02:40'),
(98, 2, 'login in', '2025-06-19 16:39:31'),
(99, 2, 'login in', '2025-06-20 19:33:14'),
(100, 2, 'login in', '2025-06-20 22:09:33'),
(101, 2, 'login in', '2025-06-21 08:01:14'),
(102, 2, 'login in', '2025-06-21 10:23:45'),
(103, 2, 'Added Program: WEB DEV22', '2025-06-21 11:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `processed_by` varchar(50) DEFAULT 'System',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_transactions`
--

CREATE TABLE `pos_transactions` (
  `id` int(12) NOT NULL,
  `student_id` int(12) NOT NULL,
  `program_id` int(12) NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `learning_mode` varchar(50) NOT NULL,
  `package_name` varchar(200) DEFAULT NULL,
  `payment_type` varchar(50) NOT NULL,
  `demo_type` varchar(50) DEFAULT NULL,
  `selected_schedules` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `promo_discount` decimal(10,2) DEFAULT 0.00,
  `system_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `cash_received` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `enrollment_status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` varchar(255) DEFAULT NULL,
  `debit_amount` decimal(10,2) DEFAULT 0.00,
  `credit_amount` decimal(10,2) DEFAULT 0.00,
  `change_given` decimal(10,2) DEFAULT 0.00,
  `change_verified` tinyint(1) DEFAULT 0,
  `total_payment` decimal(10,2) DEFAULT 0.00,
  `program_details` text DEFAULT NULL,
  `package_details` text DEFAULT NULL,
  `excess_processing_data` text DEFAULT NULL,
  `processed_by` varchar(50) DEFAULT 'System',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_transactions`
--

INSERT INTO `pos_transactions` (`id`, `student_id`, `program_id`, `transaction_date`, `learning_mode`, `package_name`, `payment_type`, `demo_type`, `selected_schedules`, `subtotal`, `promo_discount`, `system_fee`, `total_amount`, `cash_received`, `change_amount`, `balance`, `status`, `enrollment_status`, `created_at`, `updated_at`, `description`, `debit_amount`, `credit_amount`, `change_given`, `change_verified`, `total_payment`, `program_details`, `package_details`, `excess_processing_data`, `processed_by`, `notes`) VALUES
(305, 20250535, 27, '2025-06-21 08:09:39', 'F2F', 'Package 2', 'initial_payment', NULL, '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', 35185.00, 1056.00, 0.00, 34129.00, 10000.00, 0.00, 24129.00, 'Active', 'Enrolled', '2025-06-21 00:09:39', '2025-06-21 00:09:39', 'Initial enrollment - Initial payment', 10000.00, 10000.00, 0.00, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(306, 20250535, 27, '2025-06-21 08:13:45', 'F2F', 'Package 2', 'demo_payment', 'demo1', '[{\"id\":\"82\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-09\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"},{\"id\":\"83\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-10\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"}]', 35185.00, 1056.00, 0.00, 34129.00, 6032.25, 3967.75, 18096.75, 'Active', 'Reserved', '2025-06-21 00:13:45', '2025-06-21 00:13:45', 'Demo payment - demo1 [Iterative: 1 steps, ₱3967.75 allocated]', 6032.25, 6032.25, 3967.75, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(307, 20250535, 27, '2025-06-21 08:13:45', 'F2F', 'Package 2', 'demo_payment', 'demo2', '[{\"id\":\"82\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-09\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"},{\"id\":\"83\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-10\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"}]', 35185.00, 1056.00, 0.00, 34129.00, 3967.75, 0.00, 14129.00, 'Active', 'Enrolled', '2025-06-21 00:13:45', '2025-06-21 00:13:45', 'Demo payment from excess allocation - Step 1: ₱3967.75 → demo2', 3967.75, 3967.75, 0.00, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(308, 20250544, 27, '2025-06-21 08:58:25', 'F2F', 'Regular Package', 'initial_payment', NULL, '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', 35185.00, 0.00, 0.00, 35185.00, 15000.00, 0.00, 20185.00, 'Active', 'Enrolled', '2025-06-21 00:58:25', '2025-06-21 00:58:25', 'Initial enrollment - Initial payment', 15000.00, 15000.00, 0.00, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(309, 20250544, 27, '2025-06-21 08:59:09', 'F2F', 'Regular Package', 'demo_payment', 'demo1', '[{\"id\":\"82\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-09\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"},{\"id\":\"83\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-10\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"}]', 35185.00, 0.00, 0.00, 35185.00, 7296.25, 2703.75, 12888.75, 'Active', 'Reserved', '2025-06-21 00:59:09', '2025-06-21 00:59:09', 'Demo payment - demo1', 7296.25, 7296.25, 2703.75, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(310, 20250544, 27, '2025-06-21 08:59:58', 'F2F', 'Regular Package', 'demo_payment', 'demo2', '[{\"id\":\"82\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-09\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"},{\"id\":\"83\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-10\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"}]', 35185.00, 0.00, 0.00, 35185.00, 7296.25, 2703.75, 5592.50, 'Active', 'Reserved', '2025-06-21 00:59:58', '2025-06-21 00:59:58', 'Demo payment - demo2', 7296.25, 7296.25, 2703.75, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(311, 20250543, 27, '2025-06-21 09:39:09', 'F2F', 'Regular Package', 'initial_payment', NULL, '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', 35185.00, 0.00, 0.00, 35185.00, 5000.00, 5000.00, 30185.00, 'Active', 'Enrolled', '2025-06-21 01:39:09', '2025-06-21 01:39:09', 'Initial enrollment - Initial payment', 5000.00, 5000.00, 5000.00, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(312, 20250542, 27, '2025-06-21 09:40:37', 'F2F', 'Regular Package', 'initial_payment', NULL, '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', 35185.00, 0.00, 0.00, 35185.00, 5000.00, 10000.00, 30185.00, 'Active', 'Enrolled', '2025-06-21 01:40:37', '2025-06-21 01:40:37', 'Initial enrollment - Initial payment', 5000.00, 5000.00, 10000.00, 0, 0.00, NULL, NULL, NULL, 'System', NULL),
(313, 20250541, 27, '2025-06-21 09:41:34', 'F2F', 'Regular Package', 'initial_payment', NULL, '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', 35185.00, 0.00, 0.00, 35185.00, 5000.00, 10000.00, 30185.00, 'Active', 'Enrolled', '2025-06-21 01:41:34', '2025-06-21 01:41:34', 'Initial enrollment - Initial payment', 5000.00, 5000.00, 10000.00, 0, 0.00, NULL, NULL, NULL, 'System', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `end_date` date DEFAULT NULL,
  `learning_mode` varchar(150) NOT NULL,
  `assesment_fee` int(11) NOT NULL,
  `tuition_fee` int(11) NOT NULL,
  `misc_fee` int(11) NOT NULL,
  `ojt_fee` int(11) NOT NULL,
  `system_fee` int(11) NOT NULL,
  `uniform_fee` int(11) NOT NULL,
  `id_fee` int(11) NOT NULL,
  `book_fee` int(11) NOT NULL,
  `kit_fee` int(11) NOT NULL,
  `grad_fee` int(150) NOT NULL,
  `total_tuition` int(11) NOT NULL,
  `demo1_fee_hidden` int(11) NOT NULL,
  `demo2_fee_hidden` int(11) NOT NULL,
  `demo3_fee_hidden` int(11) NOT NULL,
  `demo4_fee_hidden` int(11) NOT NULL,
  `reservation_fee` int(11) NOT NULL,
  `initial_fee` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`id`, `program_name`, `end_date`, `learning_mode`, `assesment_fee`, `tuition_fee`, `misc_fee`, `ojt_fee`, `system_fee`, `uniform_fee`, `id_fee`, `book_fee`, `kit_fee`, `grad_fee`, `total_tuition`, `demo1_fee_hidden`, `demo2_fee_hidden`, `demo3_fee_hidden`, `demo4_fee_hidden`, `reservation_fee`, `initial_fee`) VALUES
(19, 'Care Givings', '2026-06-08', 'F2F', 2000, 2000, 2000, 2000, 0, 2000, 2000, 2000, 2000, 0, 16000, 4000, 4000, 4000, 4000, 2000, 2000),
(23, 'Care Giving', '2026-06-08', 'Online', 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 1000, 0, 9000, 2250, 2250, 2250, 2250, 1000, 1000),
(25, 'Nursing Aid NC3', '2026-06-08', 'Online', 1265, 18036, 6000, 6000, 3000, 1500, 250, 900, 2500, 0, 39451, 8363, 8363, 8363, 8363, 1000, 6000),
(26, 'Care Giving NC2', '2026-06-08', 'F2F', 1265, 18035, 6000, 6000, 0, 1500, 250, 900, 2500, 0, 36450, 7612, 7612, 7612, 7612, 1000, 5000),
(27, 'Care Giving NC4', '2026-06-08', 'F2F', 0, 18035, 6000, 6000, 0, 1500, 250, 900, 2500, 0, 35185, 7296, 7296, 7296, 7296, 1000, 5000),
(28, ' WEB DEV', '2026-06-08', 'F2F', 4000, 4000, 4000, 4000, 0, 4000, 4000, 4000, 4000, 0, 32000, 6000, 6000, 6000, 6000, 4000, 4000),
(29, 'SMAW NC2', '2025-06-08', 'F2F', 1265, 18035, 6000, 6000, 0, 1500, 250, 900, 2500, 0, 39450, 8362, 8362, 8362, 8362, 1000, 5000),
(30, 'asdasdasd', '2025-06-06', 'F2F', 3000, 3000, 3000, 3000, 0, 3000, 3000, 3000, 3000, 0, 24000, 4500, 4500, 4500, 4500, 3000, 3000),
(31, 'Bookeeping NC2', NULL, 'F2F', 1265, 18035, 6000, 6000, 0, 1500, 250, 900, 2500, 0, 36450, 7612, 7612, 7612, 7612, 1000, 5000),
(32, ' WEB DEV2', NULL, 'F2F', 1265, 18035, 6000, 6000, 0, 1500, 250, 900, 2500, 0, 36450, 7612, 7612, 7612, 7612, 1000, 5000),
(33, 'WEB DEV22', NULL, 'F2F', 1000, 1000, 1000, 1000, 0, 1000, 1000, 1000, 100, 0, 7100, 1025, 1025, 1025, 1025, 1000, 2000);

-- --------------------------------------------------------

--
-- Table structure for table `promo`
--

CREATE TABLE `promo` (
  `id` int(12) NOT NULL,
  `program_id` varchar(12) NOT NULL,
  `package_name` varchar(200) NOT NULL,
  `enrollment_fee` double(10,2) DEFAULT NULL,
  `required_initial_payment` decimal(10,2) DEFAULT NULL,
  `custom_initial_payment` decimal(10,2) DEFAULT NULL,
  `percentage` int(100) NOT NULL,
  `promo_type` varchar(200) NOT NULL,
  `selection_type` int(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo`
--

INSERT INTO `promo` (`id`, `program_id`, `package_name`, `enrollment_fee`, `required_initial_payment`, `custom_initial_payment`, `percentage`, `promo_type`, `selection_type`) VALUES
(43, '18', '1', 1600.00, NULL, NULL, 2, 'sample', 1),
(44, '19', '1', 1600.00, NULL, NULL, 10, 'sample', 1),
(50, '23', '3', 180.00, NULL, NULL, 2, 'Online Promo', 1),
(53, '25', '1', 1973.00, NULL, NULL, 5, 'Early Bird', 1),
(57, '26', 'sample package', 8384.00, NULL, NULL, 23, 'sample', 1),
(58, '26', '2', 365.00, NULL, NULL, 1, '2', 1),
(60, '27', 'Package 1', 1759.00, NULL, NULL, 5, 'Fullpayment', 1),
(61, '27', 'Package 2', 1056.00, NULL, NULL, 3, 'Atleast ₱17,678.25 to avail this', 1),
(62, '28', '1', 640.00, NULL, NULL, 2, '3', 1),
(65, '29', '1', 1972.00, NULL, NULL, 5, 'Full Payment', 1),
(66, '29', '2', 1184.00, NULL, NULL, 3, 'Half Payment', 1),
(67, '30', '1', 960.00, NULL, NULL, 4, 'asd', 1),
(68, '31', '1', 1822.00, NULL, NULL, 5, 'Full Payment', 1),
(69, '31', '2', 1094.00, NULL, NULL, 3, 'Half Payment', 1),
(70, '32', '1', 1822.50, NULL, NULL, 5, '2', 1),
(71, '32', '2', 1093.50, NULL, NULL, 3, '2', 1),
(72, '33', '3', 0.00, NULL, 5000.00, 0, 'sample', 3);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(12) NOT NULL,
  `program_id` int(12) NOT NULL,
  `week_description` varchar(200) NOT NULL,
  `training_date` varchar(200) NOT NULL,
  `start_time` varchar(200) NOT NULL,
  `end_time` varchar(150) NOT NULL,
  `day_of_week` varchar(200) NOT NULL,
  `day_value` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `program_id`, `week_description`, `training_date`, `start_time`, `end_time`, `day_of_week`, `day_value`) VALUES
(60, 18, 'Week 1', '2025-06-11', '09:49', '09:49', 'Weekday 2', 'Weekday 2'),
(61, 19, 'Week 1', '2025-06-12', '07:50', '07:50', 'Weekday 2', 'Weekday 2'),
(67, 23, 'Week 1', '2025-06-12', '00:09', '00:09', 'Weekday 2', 'Weekday 2'),
(71, 25, 'Week 1', '2025-06-12', '05:41', '05:41', 'Weekday 1', 'Weekday 1'),
(72, 25, 'Week 2', '2025-06-16', '08:00', '17:00', 'Weekday 2', 'Weekday 2'),
(73, 25, 'Week 2', '2025-06-18', '09:08', '21:08', 'Weekday 2', 'Weekday 2'),
(78, 26, 'Week 2', '2025-06-20', '02:48', '02:48', 'Weekday 1', 'Weekday 1'),
(79, 26, 'Week 1', '2025-06-12', '02:48', '02:48', 'Weekday 3', 'Weekday 3'),
(82, 27, 'Week 1', '2025-06-09', '03:26', '03:26', 'Weekday 1', 'Weekday 1'),
(83, 27, 'Week 1', '2025-06-10', '03:26', '03:26', 'Weekday 1', 'Weekday 1'),
(84, 28, 'Week 1', '2025-06-18', '06:46', '06:46', 'Weekday 2', 'Weekday 2'),
(87, 29, 'Week 1', '2025-06-15', '10:18', '10:18', 'Weekend 1', 'Weekend 1'),
(88, 29, 'Week 2', '2025-06-17', '10:19', '10:19', 'Weekday 2', 'Weekday 2'),
(89, 30, 'Week 1', '2025-06-05', '00:24', '00:24', 'Weekend 1', 'Weekend 1'),
(90, 31, 'Week 0', '2025-06-13', '09:20', '09:20', 'Weekday 1', 'Weekday 1'),
(91, 32, 'Week 0', '2025-06-13', '09:52', '09:52', 'Weekday 2', 'Weekday 2'),
(92, 33, 'Week 1', '2025-06-23', '11:16', '11:16', 'Weekday 1', 'Weekday 1');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_locks`
--

CREATE TABLE `schedule_locks` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` int(12) NOT NULL,
  `student_id` int(12) NOT NULL,
  `program_id` int(12) NOT NULL,
  `pos_transaction_id` int(12) NOT NULL,
  `enrollment_date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'Reserved',
  `learning_mode` varchar(50) NOT NULL,
  `selected_schedules` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `schedule_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`id`, `student_id`, `program_id`, `pos_transaction_id`, `enrollment_date`, `status`, `learning_mode`, `selected_schedules`, `created_at`, `updated_at`, `schedule_updated_at`) VALUES
(177, 20250535, 27, 306, '2025-06-21 08:09:39', 'Reserved', 'F2F', '[{\"id\":\"82\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-09\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"},{\"id\":\"83\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-10\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"}]', '2025-06-21 00:09:39', '2025-06-21 00:13:45', NULL),
(178, 20250544, 27, 310, '2025-06-21 08:58:25', 'Reserved', 'F2F', '[{\"id\":\"82\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-09\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"},{\"id\":\"83\",\"week_description\":\"Week 1\",\"training_date\":\"2025-06-10\",\"start_time\":\"03:26\",\"end_time\":\"03:26\",\"day_of_week\":\"Weekday 1\"}]', '2025-06-21 00:58:25', '2025-06-21 00:59:58', NULL),
(179, 20250543, 27, 311, '2025-06-21 09:39:09', 'Enrolled', 'F2F', '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', '2025-06-21 01:39:09', '2025-06-21 01:39:09', NULL),
(180, 20250542, 27, 312, '2025-06-21 09:40:37', 'Enrolled', 'F2F', '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', '2025-06-21 01:40:37', '2025-06-21 01:40:37', NULL),
(181, 20250541, 27, 313, '2025-06-21 09:41:34', 'Enrolled', 'F2F', '[{\"id\":\"82\",\"schedule_id\":\"82\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-09\",\"trainingDate\":\"2025-06-09\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"},{\"id\":\"83\",\"schedule_id\":\"83\",\"week_description\":\"Week 1\",\"weekDescription\":\"Week 1\",\"training_date\":\"2025-06-10\",\"trainingDate\":\"2025-06-10\",\"start_time\":\"03:26\",\"startTime\":\"03:26\",\"end_time\":\"03:26\",\"endTime\":\"03:26\",\"day_of_week\":\"Weekday 1\",\"dayOfWeek\":\"Weekday 1\"}]', '2025-06-21 01:41:34', '2025-06-21 01:41:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_info_tbl`
--

CREATE TABLE `student_info_tbl` (
  `id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `civil_status` enum('single','married','divorced','widowed','separated') NOT NULL,
  `user_contact` bigint(12) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `brgy` varchar(100) NOT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `birth_region` varchar(100) NOT NULL,
  `birth_province` varchar(100) NOT NULL,
  `birth_city` varchar(100) NOT NULL,
  `birth_brgy` varchar(100) NOT NULL,
  `educational_attainment` varchar(100) NOT NULL,
  `classification` varchar(100) NOT NULL,
  `emergency_name` varchar(200) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `emergency_contact` varchar(20) NOT NULL,
  `emergency_email` varchar(100) NOT NULL,
  `emergency_region` varchar(100) NOT NULL,
  `emergency_province` varchar(100) NOT NULL,
  `emergency_city` varchar(100) NOT NULL,
  `emergency_brgy` varchar(100) NOT NULL,
  `emergency_purok` varchar(100) DEFAULT NULL,
  `referral` varchar(100) DEFAULT 'none',
  `other_referral` varchar(200) DEFAULT NULL,
  `knowledge_source` varchar(100) NOT NULL,
  `requirements` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `enrollment_status` enum('','Reserved','Enrolled','Completed','Dropped') NOT NULL DEFAULT '',
  `schedule` longtext NOT NULL,
  `programs` varchar(150) NOT NULL,
  `last_payment_date` datetime DEFAULT NULL,
  `total_paid` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_info_tbl`
--

INSERT INTO `student_info_tbl` (`id`, `student_number`, `entry_date`, `last_name`, `first_name`, `middle_name`, `birthdate`, `civil_status`, `user_contact`, `user_email`, `region`, `province`, `city`, `brgy`, `purok`, `birth_region`, `birth_province`, `birth_city`, `birth_brgy`, `educational_attainment`, `classification`, `emergency_name`, `relationship`, `emergency_contact`, `emergency_email`, `emergency_region`, `emergency_province`, `emergency_city`, `emergency_brgy`, `emergency_purok`, `referral`, `other_referral`, `knowledge_source`, `requirements`, `photo_path`, `created_at`, `updated_at`, `enrollment_status`, `schedule`, `programs`, `last_payment_date`, `total_paid`) VALUES
(20250535, '20250506A0001', '2025-06-07', 'Rohan', 'Taar', 'P', '2002-08-22', 'single', 0, '09492982928', 'Region XI (Davao Region)', 'Davao Del Norte', 'Talaingod', 'Dagohoy', '1', 'Region VIII (Eastern Visayas)', 'Eastern Samar', 'Maydolong', 'Maytigbao', 'High School Undergraduate', 'Working Student', 'weas', 'Spouse', '09939393939', 'weas@gmail.com', 'Region XI (Davao Region)', 'Davao Del Norte', 'Talaingod', 'Dagohoy', '1', 'none', NULL, 'Government Agency', 'PSA Birth Certificate, TOR, ALS cert or FORM 137, Passport size photos', 'uploads/student_photos/20250506A0001_1749255180.jpg', '2025-06-07 00:13:00', '2025-06-21 00:13:45', 'Reserved', '', '', NULL, 0.00),
(20250536, '20250607A0002', '2025-06-07', 'Diego', 'Rohan', 'M', '2025-06-12', 'single', 0, '09492982928', 'National Capital Region (NCR)', 'Ncr, City Of Manila, First District', 'San Miguel', 'Barangay 645', '1', 'Region XI (Davao Region)', 'Compostela Valley', 'Monkayo', 'Poblacion', 'Elementary Graduate', 'Regular Student', 'Rohan', 'Sibling', '09393939393', 'Rohan@gmail.com', 'National Capital Region (NCR)', 'Ncr, City Of Manila, First District', 'San Miguel', 'Barangay 645', '1', 'none', NULL, 'School/Institution', 'PSA Birth Certificate, TOR, ALS cert or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0002_1749258665.jpg', '2025-06-07 01:11:05', '2025-06-21 00:06:04', '', '', '', NULL, 0.00),
(20250537, '20250607A0003', '2025-06-07', 'Doe', 'Ford', 'M', '2025-06-07', 'married', 0, '09393939393', 'Region X (Northern Mindanao)', 'Lanao Del Norte', 'Nunungan', 'Masibay', '2', 'Region X (Northern Mindanao)', 'Misamis Occidental', 'Panaon', 'Villalin', 'High School Undergraduate', 'Senior Citizen', 'weas', 'Sibling', '09393939393', 'Rohan@gmail.com', 'Region X (Northern Mindanao)', 'Lanao Del Norte', 'Nunungan', 'Masibay', '2', 'none', NULL, 'Social Media', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0003_1749258891.jpg', '2025-06-07 01:14:52', '2025-06-21 00:06:04', '', '', '', NULL, 0.00),
(20250538, '20250607A0004', '2025-06-07', 'Santos', 'Rohan', 'D', '2025-06-13', 'divorced', 0, '09352632690', 'National Capital Region (NCR)', 'Ncr, City Of Manila, First District', 'Quiapo', 'Barangay 391', '', 'National Capital Region (NCR)', 'Ncr, City Of Manila, First District', 'San Miguel', 'Barangay 646', 'Elementary Graduate', 'Senior Citizen', 'sample Company', 'Spouse', '09393939393', 'weas@gmail.com', 'National Capital Region (NCR)', 'Ncr, City Of Manila, First District', 'Quiapo', 'Barangay 391', '', 'none', NULL, 'School/Institution', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0004_1749259192.jpg', '2025-06-07 01:19:52', '2025-06-21 00:06:04', '', '', '', NULL, 0.00),
(20250539, '20250607A0005', '2025-06-07', 'Santos', 'Rohan', 'M', '2025-06-12', 'divorced', 0, '09492982928', 'National Capital Region (NCR)', 'Ncr, Fourth District', 'Pasay City', 'Barangay 111', '1', 'Region XII (SOCCSKSARGEN)', 'Cotabato City', 'Cotabato City', 'Poblacion IV', 'Elementary Graduate', 'Working Student', 'weas', 'Sibling', '09939393939', 'Rohan@gmail.com', 'National Capital Region (NCR)', 'Ncr, Fourth District', 'Pasay City', 'Barangay 111', '1', 'none', NULL, 'Friend/Relative', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0005_1749259564.jpg', '2025-06-07 01:26:04', '2025-06-21 00:06:04', '', '', '', NULL, 0.00),
(20250540, '20250607A0006', '2025-06-07', 'Doe', 'Ford', 'D', '2025-06-11', 'married', 0, '09352632690', 'Region XI (Davao Region)', 'Davao Del Norte', 'Kapalong', 'Semong', '1', 'Region XI (Davao Region)', 'Davao Del Norte', 'San Isidro', 'San Miguel', 'High School Undergraduate', 'Working Student', 'weas', 'Spouse', '09393939393', 'Rohan@gmail.com', 'Region XI (Davao Region)', 'Davao Del Norte', 'Kapalong', 'Semong', '1', 'none', NULL, 'Website', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0006_1749259658.jpg', '2025-06-07 01:27:38', '2025-06-21 00:06:04', '', '', '', NULL, 0.00),
(20250541, '20250607A0007', '2025-06-07', 'Diego', 'asd', 'M', '2025-06-10', 'married', 0, '09393939393', 'Region XI (Davao Region)', 'Davao Del Sur', 'Padada', 'Southern Paligue', '', 'Region XI (Davao Region)', 'Davao Del Norte', 'Carmen', 'Mabuhay', 'High School Undergraduate', 'Working Student', 'weas', 'Parent', '09393939393', 'weas@gmail.com', 'Region XI (Davao Region)', 'Davao Del Sur', 'Padada', 'Southern Paligue', '', 'none', NULL, 'School/Institution', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0007_1749259770.jpg', '2025-06-07 01:29:30', '2025-06-21 01:41:34', 'Enrolled', '', '', NULL, 0.00),
(20250542, '20250607A0008', '2025-06-07', 'Diego', 'Ford', 'M', '2025-06-07', 'widowed', 0, '09393939393', 'Region XI (Davao Region)', 'Davao Del Sur', 'City Of Digos (Capital)', 'Kiagot', '2', 'Region XII (SOCCSKSARGEN)', 'Cotabato (North Cotabato)', 'Makilala', 'Indangan', 'Elementary Graduate', 'Regular Student', 'weas', 'Parent', '09393939393', 'Rohan@gmail.com', 'Region XI (Davao Region)', 'Davao Del Sur', 'City Of Digos (Capital)', 'Kiagot', '2', 'none', NULL, 'Friend/Relative', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0008_1749259999.jpg', '2025-06-07 01:33:19', '2025-06-21 01:40:37', 'Enrolled', '', '', NULL, 0.00),
(20250543, '20250607A0009', '2025-06-07', 'Santos', 'Rohan', 'D', '2025-06-11', 'single', 0, '09393939393', 'Region VII (Central Visayas)', 'Cebu', 'Borbon', 'Lugo', '', 'Region XI (Davao Region)', 'Compostela Valley', 'Laak (San Vicente)', 'Amor Cruz', 'Elementary Graduate', 'Regular Student', 'weas', 'Sibling', '09393939393', 'Rohan@gmail.com', 'Region VII (Central Visayas)', 'Cebu', 'Borbon', 'Lugo', '', 'none', NULL, 'Friend/Relative', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0009_1749260080.jpg', '2025-06-07 01:34:40', '2025-06-21 01:39:09', 'Enrolled', '', '', NULL, 0.00),
(20250544, '20250607A0010', '2025-06-07', 'Santos', 'Ford', 'Panay', '2025-06-13', 'divorced', 0, '09352632690', 'Region VIII (Eastern Visayas)', 'Eastern Samar', 'Maslog', 'San Roque', '', 'Region XII (SOCCSKSARGEN)', 'Cotabato City', 'Cotabato City', 'Poblacion VI', 'Elementary Graduate', 'Person with Disability', 'sample Company', 'Sibling', '09292929922', 'sdf@gmail.com', 'Region VIII (Eastern Visayas)', 'Eastern Samar', 'Maslog', 'San Roque', '', 'none', NULL, 'School/Institution', 'PSA Birth Certificate, TOR, ALS Certificate or FORM 137, Passport size photos', 'uploads/student_photos/20250607A0010_1749260308.png', '2025-06-07 01:38:28', '2025-06-21 00:59:09', 'Reserved', '', '', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `student_schedules`
--

CREATE TABLE `student_schedules` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `enrollment_date` datetime DEFAULT current_timestamp(),
  `status` enum('enrolled','completed','cancelled') DEFAULT 'enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `nttc` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `program` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tuitionfee`
--

CREATE TABLE `tuitionfee` (
  `id` int(11) NOT NULL,
  `program_name` varchar(200) NOT NULL,
  `package_number` int(11) NOT NULL,
  `tuition Fee` int(11) NOT NULL,
  `misc Fee` int(11) NOT NULL,
  `ojtmedical` int(11) NOT NULL,
  `system Fee` int(11) NOT NULL,
  `assessment Fee` int(11) NOT NULL,
  `uniform` int(11) NOT NULL,
  `IDfee` int(11) NOT NULL,
  `books` int(11) NOT NULL,
  `kit` int(11) NOT NULL,
  `demo1` int(11) NOT NULL,
  `demo2` int(11) NOT NULL,
  `demo3` int(11) NOT NULL,
  `demo4` int(11) NOT NULL,
  `totalbasedtuition` int(12) NOT NULL,
  `reservation_fee` int(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `level`, `created_at`) VALUES
(2, 'rohan', '', '$2y$10$EQZi7oqJeDfjdVOdfo0PQekkJ4umRSoO2TkKpVReSnxSXBE9eRfrm', 'Admin', '2025-05-06 09:33:38');

-- --------------------------------------------------------

--
-- Structure for view `enhanced_payment_summary`
--
DROP TABLE IF EXISTS `enhanced_payment_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `enhanced_payment_summary`  AS SELECT `t`.`id` AS `transaction_id`, `s`.`id` AS `student_id`, concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`, `p`.`program_name` AS `program_name`, `t`.`payment_type` AS `payment_type`, `t`.`demo_type` AS `demo_type`, `t`.`cash_received` AS `cash_received`, `t`.`change_amount` AS `change_amount`, `t`.`balance` AS `balance`, `t`.`enrollment_status` AS `enrollment_status`, `t`.`learning_mode` AS `learning_mode`, `t`.`package_name` AS `package_name`, `t`.`transaction_date` AS `transaction_date`, `t`.`processed_by` AS `processed_by`, CASE WHEN `t`.`excess_processing_data` is not null THEN 'Yes' ELSE 'No' END AS `has_excess_processing` FROM ((`pos_transactions` `t` join `student_info_tbl` `s` on(`t`.`student_id` = `s`.`id`)) join `program` `p` on(`t`.`program_id` = `p`.`id`)) ORDER BY `t`.`transaction_date` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `demo_payments`
--
ALTER TABLE `demo_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_demo_payment` (`student_id`,`program_id`,`demo_type`,`transaction_id`),
  ADD KEY `idx_student_demo` (`student_id`,`program_id`,`demo_type`),
  ADD KEY `idx_transaction` (`transaction_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `excess_processing_log`
--
ALTER TABLE `excess_processing_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction` (`transaction_id`),
  ADD KEY `idx_student_program` (`student_id`,`program_id`),
  ADD KEY `idx_processed_date` (`processed_at`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `level_name` (`level_name`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_program` (`student_id`,`program_id`),
  ADD KEY `idx_transaction` (`transaction_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_student_transactions` (`student_id`,`transaction_date`),
  ADD KEY `idx_student_balance` (`student_id`,`balance`),
  ADD KEY `idx_student_program_payment` (`student_id`,`program_id`,`payment_type`),
  ADD KEY `idx_pos_transactions_student_program` (`student_id`,`program_id`),
  ADD KEY `idx_pos_transactions_payment_type` (`payment_type`),
  ADD KEY `idx_pos_transactions_demo_type` (`demo_type`),
  ADD KEY `idx_pos_transactions_date` (`transaction_date`),
  ADD KEY `idx_pos_transactions_status` (`enrollment_status`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo`
--
ALTER TABLE `promo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule_locks`
--
ALTER TABLE `schedule_locks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule_lock` (`schedule_id`,`program_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_program_id` (`program_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`program_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `pos_transaction_id` (`pos_transaction_id`);

--
-- Indexes for table `student_info_tbl`
--
ALTER TABLE `student_info_tbl`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_name` (`last_name`,`first_name`),
  ADD KEY `idx_classification` (`classification`);

--
-- Indexes for table `student_schedules`
--
ALTER TABLE `student_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tuitionfee`
--
ALTER TABLE `tuitionfee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `demo_payments`
--
ALTER TABLE `demo_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `excess_processing_log`
--
ALTER TABLE `excess_processing_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `payment_history`
--
ALTER TABLE `payment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_transactions`
--
ALTER TABLE `pos_transactions`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=314;

--
-- AUTO_INCREMENT for table `program`
--
ALTER TABLE `program`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `promo`
--
ALTER TABLE `promo`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `schedule_locks`
--
ALTER TABLE `schedule_locks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `student_info_tbl`
--
ALTER TABLE `student_info_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20250545;

--
-- AUTO_INCREMENT for table `student_schedules`
--
ALTER TABLE `student_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tuitionfee`
--
ALTER TABLE `tuitionfee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `demo_payments`
--
ALTER TABLE `demo_payments`
  ADD CONSTRAINT `demo_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info_tbl` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demo_payments_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demo_payments_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `excess_processing_log`
--
ALTER TABLE `excess_processing_log`
  ADD CONSTRAINT `excess_processing_log_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `excess_processing_log_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student_info_tbl` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `excess_processing_log_ibfk_3` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD CONSTRAINT `payment_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info_tbl` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_history_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_history_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_schedules`
--
ALTER TABLE `student_schedules`
  ADD CONSTRAINT `student_schedules_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info_tbl` (`id`),
  ADD CONSTRAINT `student_schedules_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
