-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 08:22 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `accountant`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_records`
--

CREATE TABLE `backup_records` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `backup_type` enum('auto','manual') DEFAULT 'manual',
  `status` enum('success','failed') DEFAULT 'success',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_statements`
--

CREATE TABLE `bank_statements` (
  `id` bigint(20) NOT NULL,
  `import_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `filename` varchar(255) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `starting_balance` decimal(15,2) DEFAULT NULL,
  `ending_balance` decimal(15,2) DEFAULT NULL,
  `status` enum('imported','processing','reconciled') DEFAULT 'imported'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_statements`
--

INSERT INTO `bank_statements` (`id`, `import_date`, `filename`, `bank_name`, `account_number`, `starting_balance`, `ending_balance`, `status`) VALUES
(3, '2026-04-11 21:21:23', '1775942483_transactions.csv', 'CRDB Bank', '1111111112', NULL, NULL, 'imported'),
(4, '2026-04-12 13:46:13', '1776001573_transactions.csv', 'CRDB Bank', '234567890', NULL, NULL, 'imported'),
(5, '2026-04-12 13:49:30', '1776001770_businesses1.csv', 'CRDB Bank', '124567', NULL, NULL, 'imported');

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `id` bigint(20) NOT NULL,
  `bank_statement_id` bigint(20) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('credit','debit') NOT NULL,
  `matched_transaction_id` bigint(20) DEFAULT NULL,
  `match_status` enum('pending','matched','mismatch') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_transactions`
--

INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES
(11, 3, 'MISM_001', '2025-04-04', 'Unknown payment - no reference', '50000.00', 'credit', NULL, 'mismatch', NULL),
(12, 3, 'TXN_001', '2025-04-01', 'Payment from Juma Hassan - SSMS001', '150000.00', 'credit', 2, 'matched', ' | Auto-matched on 2026-04-12 00:21:48'),
(13, 3, 'TXN_002', '2025-04-02', 'Payment from Asha Mushi - SSMS002', '75000.00', 'credit', NULL, 'mismatch', NULL),
(14, 3, 'TXN_003', '2025-04-03', 'Payment from John Mwita - SSMS003', '200000.00', 'credit', 3, 'matched', ' | Auto-matched on 2026-04-12 00:21:48'),
(15, 3, 'TXN_004', '2025-04-05', 'Payment from Fatma Said - SSMS004', '100000.00', 'credit', 4, 'matched', ' | Auto-matched on 2026-04-12 00:25:18'),
(16, 4, 'MISM_001', '2025-04-04', 'Unknown payment - no reference', '50000.00', 'credit', NULL, 'mismatch', NULL),
(17, 4, 'TXN_001', '2025-04-01', 'Payment from Juma Hassan - SSMS001', '150000.00', 'credit', 2, 'matched', ' | Auto-matched on 2026-04-12 16:46:45'),
(18, 4, 'TXN_002', '2025-04-02', 'Payment from Asha Mushi - SSMS002', '75000.00', 'credit', NULL, 'mismatch', NULL),
(19, 4, 'TXN_003', '2025-04-03', 'Payment from John Mwita - SSMS003', '200000.00', 'credit', 3, 'matched', ' | Auto-matched on 2026-04-12 16:46:45'),
(20, 4, 'TXN_004', '2025-04-05', 'Payment from Fatma Said - SSMS004', '100000.00', 'credit', 4, 'matched', ' | Auto-matched on 2026-04-12 16:46:45'),
(21, 5, '12', '1970-01-01', 'Ufundi', '685645807.00', 'credit', NULL, 'pending', NULL),
(22, 5, '30', '1970-01-01', 'Kampuni ya ujenzi', '0.00', 'debit', NULL, 'pending', NULL),
(23, 5, '22', '1970-01-01', 'Fundi umeme', '794843408.00', 'credit', NULL, 'pending', NULL),
(24, 5, '9', '1970-01-01', 'Ezekiel korongo', '756432134.00', 'credit', NULL, 'pending', NULL),
(25, 5, '11', '1970-01-01', 'Usafiri salama zaidi', '7865432134.00', 'credit', NULL, 'pending', NULL),
(26, 5, '15', '1970-01-01', 'Kampuni bora', '786544321.00', 'credit', NULL, 'pending', NULL),
(27, 5, '16', '1970-01-01', 'Usafiri', '765456332.00', 'credit', NULL, 'pending', NULL),
(28, 5, '17', '1970-01-01', 'Mgahawa', '765545594.00', 'credit', NULL, 'pending', NULL),
(29, 5, '18', '1970-01-01', 'Forex traders', '785932754.00', 'credit', NULL, 'pending', NULL),
(30, 5, '19', '1970-01-01', 'Fundi', '759591689.00', 'credit', NULL, 'pending', NULL),
(31, 5, '20', '1970-01-01', 'Fundi', '752397248.00', 'credit', NULL, 'pending', NULL),
(32, 5, '23', '1970-01-01', 'Saloon ya kike', '764961708.00', 'credit', NULL, 'pending', NULL),
(33, 5, '24', '1970-01-01', 'Boda boda', '766771819.00', 'credit', NULL, 'pending', NULL),
(34, 5, '25', '1970-01-01', 'Fundi bati', '715302579.00', 'credit', NULL, 'pending', NULL),
(35, 5, '26', '1970-01-01', 'Fundi', '712925020.00', 'credit', NULL, 'pending', NULL),
(36, 5, '27', '1970-01-01', 'Dalali', '717739232.00', 'credit', NULL, 'pending', NULL),
(37, 5, '28', '1970-01-01', 'Dalali', '742126410.00', 'credit', NULL, 'pending', NULL),
(38, 5, '29', '1970-01-01', 'Dalali', '0.00', 'debit', NULL, 'pending', NULL),
(39, 5, '31', '1970-01-01', 'Fundi', '743437685.00', 'credit', NULL, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_settlements`
--

CREATE TABLE `daily_settlements` (
  `id` bigint(20) NOT NULL,
  `settlement_date` date NOT NULL,
  `total_card_payments` decimal(15,2) DEFAULT 0.00,
  `total_cash_payments` decimal(15,2) DEFAULT 0.00,
  `total_mpesa_payments` decimal(15,2) DEFAULT 0.00,
  `total_bank_transfer` decimal(15,2) DEFAULT 0.00,
  `total_expenses` decimal(15,2) DEFAULT 0.00,
  `net_income` decimal(15,2) GENERATED ALWAYS AS (`total_card_payments` + `total_cash_payments` + `total_mpesa_payments` + `total_bank_transfer` - `total_expenses`) STORED,
  `bank_settlement_ref` varchar(100) DEFAULT NULL,
  `is_reconciled` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) NOT NULL,
  `expense_number` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque') NOT NULL,
  `receipt_attachment` varchar(255) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_number`, `category`, `description`, `amount`, `expense_date`, `payment_method`, `receipt_attachment`, `approved_by`, `notes`) VALUES
(1, 'EXP-20260407-4391', 'Other', 'MAFUTA YA GARI', '1000.00', '2026-04-07', 'bank_transfer', NULL, 'PPP', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL,
  `class` varchar(50) NOT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `term` enum('Term 1','Term 2','Term 3') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `filename`, `original_filename`, `file_size`, `mime_type`, `is_public`, `uploaded_by`, `created_at`) VALUES
(1, 'mahafali', 'mahafal 2026', '1776117902_69dd688ea2d3a.jpg', 'hero.jpg', 190733, 'image/jpeg', 1, 4, '2026-04-13 22:05:02'),
(2, 'mahafali', 'mahafal 2026', '1776120367_69dd722f3e3f5.jpg', 'hero.jpg', 190733, 'image/jpeg', 1, 4, '2026-04-13 22:46:07');

-- --------------------------------------------------------

--
-- Table structure for table `grading_system`
--

CREATE TABLE `grading_system` (
  `id` int(11) NOT NULL,
  `grade` varchar(2) NOT NULL,
  `min_score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `points` decimal(3,1) DEFAULT 0.0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_system`
--

INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `is_active`, `created_at`) VALUES
(1, 'A', 80, 100, 'Outstanding', '4.0', 1, '2026-04-13 20:58:30'),
(2, 'B', 70, 79, 'Very Good', '3.5', 1, '2026-04-13 20:58:30'),
(3, 'C', 60, 69, 'Good', '3.0', 1, '2026-04-13 20:58:30'),
(4, 'D', 50, 59, 'Satisfactory', '2.5', 1, '2026-04-13 20:58:30'),
(5, 'E', 40, 49, 'Pass', '2.0', 1, '2026-04-13 20:58:30'),
(6, 'F', 0, 39, 'Fail', '0.0', 1, '2026-04-13 20:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` bigint(20) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'pcs',
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `unit_price` decimal(15,2) NOT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `last_restocked_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `item_code`, `item_name`, `category_id`, `unit_of_measure`, `current_stock`, `reorder_level`, `unit_price`, `supplier`, `last_restocked_date`, `is_active`) VALUES
(1, 'NB001', 'Exercise Book (200 pages)', 3, 'pcs', 500, 50, '2500.00', NULL, NULL, 1),
(2, 'UNI001', 'School Uniform (Full Set)', 4, 'pcs', 100, 20, '45000.00', NULL, NULL, 1),
(3, 'PPP1', 'UGALI', 2, 'pcs', 27, 10, '1000.00', NULL, NULL, 1),
(4, 'PPP2', 'BOOK', 3, 'pcs', 300, 10, '300.00', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) GENERATED ALWAYS AS (`amount` - `amount_paid`) STORED,
  `term` varchar(20) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `status` enum('pending','partial','paid') DEFAULT 'pending',
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES
(1, 'INV-2025-001', 1, 1, '150000.00', '150000.00', 'Term 1', '2025', 'paid', '2026-04-07', '2026-05-07'),
(2, 'INV-20260407-1', 1, 4, '1000.00', '1000.00', 'Term 1', '2025', 'pending', '2026-04-07', '2026-05-07'),
(3, 'INV-20260411-2-152', 2, 1, '500000.00', '500000.00', 'Term 1', '2025', 'pending', '2026-04-11', '2026-05-11'),
(4, 'INV-20260411-4-979', 4, 1, '500001.00', '50000.00', 'Term 1', '2025', 'pending', '2026-04-11', '2026-05-11'),
(5, 'INV-20260411-5-787', 5, 1, '1000000.00', '0.00', 'Term 1', '2025', 'pending', '2026-04-11', '2026-05-11'),
(6, 'INV-20260412-1-132', 1, 1, '500000000.00', '0.00', 'Term 3', '2025', 'pending', '2026-04-12', '2026-04-12');

-- --------------------------------------------------------

--
-- Table structure for table `low_stock_alerts`
--

CREATE TABLE `low_stock_alerts` (
  `id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `alert_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_stock` int(11) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `status` enum('pending','acknowledged','resolved') DEFAULT 'pending',
  `resolved_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reconciliation_reports`
--

CREATE TABLE `reconciliation_reports` (
  `id` bigint(20) NOT NULL,
  `report_date` date NOT NULL,
  `bank_statement_id` bigint(20) NOT NULL,
  `total_bank_credits` decimal(15,2) DEFAULT NULL,
  `total_system_credits` decimal(15,2) DEFAULT NULL,
  `variance` decimal(15,2) GENERATED ALWAYS AS (`total_bank_credits` - `total_system_credits`) STORED,
  `reconciled_by` varchar(100) DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','reconciled','discrepancy') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revenue_categories`
--

CREATE TABLE `revenue_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revenue_categories`
--

INSERT INTO `revenue_categories` (`id`, `category_name`, `description`, `is_active`) VALUES
(1, 'Tuition', 'School fees per term', 1),
(2, 'Canteen', 'Food and drinks', 1),
(3, 'Stationery', 'Books, pens, notebooks', 1),
(4, 'Uniforms', 'School uniforms and badges', 1),
(5, 'Transport', 'School bus fees', 1);

-- --------------------------------------------------------

--
-- Table structure for table `school_calendar`
--

CREATE TABLE `school_calendar` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_type` enum('holiday','exam','event','meeting','other') DEFAULT 'event',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_calendar`
--

INSERT INTO `school_calendar` (`id`, `title`, `description`, `event_date`, `event_type`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'mahafali', 'darasa la saba', '2026-04-14', 'event', 1, 4, '2026-04-13 21:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `school_settings`
--

CREATE TABLE `school_settings` (
  `id` int(11) NOT NULL,
  `school_name` varchar(200) NOT NULL DEFAULT 'SSMS Tanzania',
  `school_logo` varchar(255) DEFAULT NULL,
  `school_address` text DEFAULT NULL,
  `school_phone` varchar(50) DEFAULT NULL,
  `school_email` varchar(100) DEFAULT NULL,
  `school_website` varchar(100) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `motto` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT '2025',
  `current_term` enum('Term 1','Term 2','Term 3') DEFAULT 'Term 1',
  `term_start_date` date DEFAULT NULL,
  `term_end_date` date DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'TZS',
  `timezone` varchar(50) DEFAULT 'Africa/Dar_es_Salaam',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_settings`
--

INSERT INTO `school_settings` (`id`, `school_name`, `school_logo`, `school_address`, `school_phone`, `school_email`, `school_website`, `tin_number`, `registration_number`, `motto`, `academic_year`, `current_term`, `term_start_date`, `term_end_date`, `currency`, `timezone`, `created_at`, `updated_at`) VALUES
(1, 'SSMS Tanzania', NULL, 'Dar es Salaam, Tanzania', '+255 123 456 789', 'info@ssms.co.tz', NULL, NULL, NULL, 'Quality Education for All', '2025', 'Term 1', NULL, NULL, 'TZS', 'Africa/Dar_es_Salaam', '2026-04-13 20:58:30', '2026-04-13 20:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `smart_cards`
--

CREATE TABLE `smart_cards` (
  `id` bigint(20) NOT NULL,
  `card_uid` varchar(100) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `issued_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smart_cards`
--

INSERT INTO `smart_cards` (`id`, `card_uid`, `student_id`, `payment_reference`, `balance`, `is_active`, `issued_date`, `expiry_date`) VALUES
(1, 'RFID:A1B2C3D4', 1, 'REF001', '250000.00', 1, '2026-04-07', NULL),
(2, '2345678', 2, 'UX_200', '0.00', 1, '2026-04-11', NULL),
(3, '2345000', 4, 'TXN_004', '0.00', 1, '2026-04-11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_type` enum('purchase','sale_card','sale_cash','return','adjustment') NOT NULL,
  `reference_id` bigint(20) DEFAULT NULL,
  `student_id` bigint(20) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `class` varchar(50) NOT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `parent_phone`, `is_active`, `created_at`) VALUES
(1, 'SSMS001', 'Juma Hassan', 'Form 1A', '0712345678', 1, '2026-04-07 05:18:03'),
(2, 'ss100', 'john joshua', '2A', '0712583913', 1, '2026-04-11 01:31:17'),
(4, 'ssm200', 'elia baltazary', '3A', '0712583912', 1, '2026-04-11 02:19:24'),
(5, 'sss200', 'kalebu', '3A', '0712583914', 1, '2026-04-11 20:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `student_payments`
--

CREATE TABLE `student_payments` (
  `id` bigint(20) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `control_number` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','completed','failed','expired') DEFAULT 'pending',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `card_id` bigint(20) DEFAULT NULL,
  `invoice_id` bigint(20) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','mpesa','tigopesa','bank_transfer','card') NOT NULL,
  `payment_channel` varchar(50) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `bank_statement_ref` varchar(100) DEFAULT NULL,
  `is_reconciled` tinyint(1) DEFAULT 0,
  `reconciled_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_ref`, `student_id`, `card_id`, `invoice_id`, `amount`, `payment_method`, `payment_channel`, `transaction_date`, `bank_statement_ref`, `is_reconciled`, `reconciled_date`, `notes`) VALUES
(1, 'TXN_1775544594_6353', 1, NULL, 2, '1000.00', 'mpesa', NULL, '2026-04-07 06:49:54', NULL, 0, NULL, NULL),
(2, 'TXN_001', 1, NULL, 1, '150000.00', 'mpesa', NULL, '2026-04-11 00:16:24', NULL, 1, '2026-04-12', ' | RECONCILIATION REMOVED on 2026-04-12 00:18:26'),
(3, 'TXN_003', 2, NULL, 3, '500000.00', 'card', NULL, '2026-04-11 02:08:48', NULL, 1, '2026-04-12', ' | RECONCILIATION REMOVED on 2026-04-12 00:18:20'),
(4, 'TXN_004', 4, NULL, 4, '50000.00', 'card', NULL, '2026-04-11 02:23:05', NULL, 1, '2026-04-12', ' | EDITED: wrong (Old amount: 500001.00) | RECONCILIATION REMOVED on 2026-04-11 23:59:12 | EDITED: wrong (Old amount: 500000.00) | RECONCILIATION REMOVED on 2026-04-12 00:06:28 | REFERENCE CHANGED: wrong (Old: TXN_004) | REFERENCE CHANGED: wrong (Old: TXN_007)'),
(6, 'ws_CO_13042026080651717708374149', 4, NULL, NULL, '50.00', 'mpesa', NULL, '2026-04-13 05:06:48', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('super_admin','accountant','teacher','parent','student') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES
(3, 'elia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elia mhapa', 'eliamhapa34@gmail.com', 'accountant', 1, '2026-04-07 06:46:33', NULL, NULL, NULL, 0, NULL),
(4, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'admin@ssms.co.tz', 'super_admin', 1, '2026-04-13 21:23:12', NULL, NULL, NULL, 0, NULL),
(5, 'eliza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eliza', '', 'student', 1, '2026-04-13 22:54:04', NULL, NULL, NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_records`
--
ALTER TABLE `backup_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `bank_statements`
--
ALTER TABLE `bank_statements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matched_transaction_id` (`matched_transaction_id`),
  ADD KEY `idx_bank_statement_id` (`bank_statement_id`),
  ADD KEY `idx_match_status` (`match_status`);

--
-- Indexes for table `daily_settlements`
--
ALTER TABLE `daily_settlements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_number` (`expense_number`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `grading_system`
--
ALTER TABLE `grading_system`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_stock_item` (`item_code`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_invoice_balance` (`balance`);

--
-- Indexes for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_low_stock` (`status`);

--
-- Indexes for table `reconciliation_reports`
--
ALTER TABLE `reconciliation_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_statement_id` (`bank_statement_id`);

--
-- Indexes for table `revenue_categories`
--
ALTER TABLE `revenue_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_calendar`
--
ALTER TABLE `school_calendar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smart_cards`
--
ALTER TABLE `smart_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_uid` (`card_uid`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_card_uid` (`card_uid`),
  ADD KEY `idx_payment_reference` (`payment_reference`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `idx_student_number` (`student_number`);

--
-- Indexes for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_control_number` (`control_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `idx_transaction_ref` (`transaction_ref`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_is_reconciled` (`is_reconciled`);

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
-- AUTO_INCREMENT for table `backup_records`
--
ALTER TABLE `backup_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_statements`
--
ALTER TABLE `bank_statements`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `daily_settlements`
--
ALTER TABLE `daily_settlements`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_structure`
--
ALTER TABLE `fee_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grading_system`
--
ALTER TABLE `grading_system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reconciliation_reports`
--
ALTER TABLE `reconciliation_reports`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revenue_categories`
--
ALTER TABLE `revenue_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_calendar`
--
ALTER TABLE `school_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `smart_cards`
--
ALTER TABLE `smart_cards`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backup_records`
--
ALTER TABLE `backup_records`
  ADD CONSTRAINT `backup_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`),
  ADD CONSTRAINT `bank_transactions_ibfk_2` FOREIGN KEY (`matched_transaction_id`) REFERENCES `transactions` (`id`);

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`id`);

--
-- Constraints for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD CONSTRAINT `low_stock_alerts_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`);

--
-- Constraints for table `reconciliation_reports`
--
ALTER TABLE `reconciliation_reports`
  ADD CONSTRAINT `reconciliation_reports_ibfk_1` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`);

--
-- Constraints for table `school_calendar`
--
ALTER TABLE `school_calendar`
  ADD CONSTRAINT `school_calendar_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `smart_cards`
--
ALTER TABLE `smart_cards`
  ADD CONSTRAINT `smart_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD CONSTRAINT `student_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`card_id`) REFERENCES `smart_cards` (`id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
