-- SSMS Tanzania Database Backup
-- Generated: 2026-04-22 18:46:45
-- Database: accountant

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) NOT NULL,
  `attendance_date` date NOT NULL,
  `period` varchar(50) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `school_level` enum('primary','secondary') DEFAULT 'primary',
  `status` enum('present','absent','late') NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_date` (`student_id`,`attendance_date`),
  KEY `idx_date` (`attendance_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `attendance` (`id`, `student_id`, `attendance_date`, `period`, `subject_id`, `school_level`, `status`, `check_in`, `check_out`, `remarks`, `marked_by`, `created_at`) VALUES ('1', '1', '2026-04-14', NULL, NULL, 'primary', 'present', '08:00:00', NULL, 'On time', NULL, '2026-04-14 10:51:45');
INSERT INTO `attendance` (`id`, `student_id`, `attendance_date`, `period`, `subject_id`, `school_level`, `status`, `check_in`, `check_out`, `remarks`, `marked_by`, `created_at`) VALUES ('2', '2', '2026-04-14', NULL, NULL, 'primary', 'present', '08:00:00', NULL, 'On time', NULL, '2026-04-14 10:51:45');
INSERT INTO `attendance` (`id`, `student_id`, `attendance_date`, `period`, `subject_id`, `school_level`, `status`, `check_in`, `check_out`, `remarks`, `marked_by`, `created_at`) VALUES ('3', '4', '2026-04-14', NULL, NULL, 'primary', 'present', '08:00:00', NULL, 'On time', NULL, '2026-04-14 10:51:45');
INSERT INTO `attendance` (`id`, `student_id`, `attendance_date`, `period`, `subject_id`, `school_level`, `status`, `check_in`, `check_out`, `remarks`, `marked_by`, `created_at`) VALUES ('4', '1', '2026-04-21', NULL, NULL, 'primary', 'absent', NULL, NULL, '', '9', '2026-04-21 08:36:27');

DROP TABLE IF EXISTS `backup_records`;
CREATE TABLE `backup_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `backup_type` enum('auto','manual') DEFAULT 'manual',
  `status` enum('success','failed') DEFAULT 'success',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backup_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `backup_records` (`id`, `filename`, `file_size`, `backup_type`, `status`, `created_by`, `created_at`) VALUES ('1', 'ssms_backup_2026-04-16_08-23-15.sql', '54.63 KB', 'manual', 'success', '4', '2026-04-16 09:23:16');
INSERT INTO `backup_records` (`id`, `filename`, `file_size`, `backup_type`, `status`, `created_by`, `created_at`) VALUES ('2', 'ssms_backup_2026-04-18_17-46-55.sql', '46.87 KB', 'manual', 'success', '4', '2026-04-18 18:46:57');
INSERT INTO `backup_records` (`id`, `filename`, `file_size`, `backup_type`, `status`, `created_by`, `created_at`) VALUES ('3', 'ssms_backup_2026-04-19_15-33-55.sql', '47.09 KB', 'manual', 'success', '4', '2026-04-19 16:33:56');
INSERT INTO `backup_records` (`id`, `filename`, `file_size`, `backup_type`, `status`, `created_by`, `created_at`) VALUES ('4', 'ssms_backup_2026-04-19_15-48-53.sql', '58.39 KB', 'manual', 'success', '4', '2026-04-19 16:48:53');
INSERT INTO `backup_records` (`id`, `filename`, `file_size`, `backup_type`, `status`, `created_by`, `created_at`) VALUES ('5', 'ssms_backup_2026-04-19_15-48-56.sql', '58.62 KB', 'manual', 'success', '4', '2026-04-19 16:48:56');

DROP TABLE IF EXISTS `bank_statements`;
CREATE TABLE `bank_statements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `import_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `filename` varchar(255) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `starting_balance` decimal(15,2) DEFAULT NULL,
  `ending_balance` decimal(15,2) DEFAULT NULL,
  `status` enum('imported','processing','reconciled') DEFAULT 'imported',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bank_statements` (`id`, `import_date`, `filename`, `bank_name`, `account_number`, `starting_balance`, `ending_balance`, `status`) VALUES ('3', '2026-04-12 00:21:23', '1775942483_transactions.csv', 'CRDB Bank', '1111111112', NULL, NULL, 'imported');
INSERT INTO `bank_statements` (`id`, `import_date`, `filename`, `bank_name`, `account_number`, `starting_balance`, `ending_balance`, `status`) VALUES ('4', '2026-04-12 16:46:13', '1776001573_transactions.csv', 'CRDB Bank', '234567890', NULL, NULL, 'imported');
INSERT INTO `bank_statements` (`id`, `import_date`, `filename`, `bank_name`, `account_number`, `starting_balance`, `ending_balance`, `status`) VALUES ('5', '2026-04-12 16:49:30', '1776001770_businesses1.csv', 'CRDB Bank', '124567', NULL, NULL, 'imported');

DROP TABLE IF EXISTS `bank_transactions`;
CREATE TABLE `bank_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `bank_statement_id` bigint(20) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('credit','debit') NOT NULL,
  `matched_transaction_id` bigint(20) DEFAULT NULL,
  `match_status` enum('pending','matched','mismatch') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `matched_transaction_id` (`matched_transaction_id`),
  KEY `idx_bank_statement_id` (`bank_statement_id`),
  KEY `idx_match_status` (`match_status`),
  CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`),
  CONSTRAINT `bank_transactions_ibfk_2` FOREIGN KEY (`matched_transaction_id`) REFERENCES `transactions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('11', '3', 'MISM_001', '2025-04-04', 'Unknown payment - no reference', '50000.00', 'credit', NULL, 'mismatch', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('12', '3', 'TXN_001', '2025-04-01', 'Payment from Juma Hassan - SSMS001', '150000.00', 'credit', '2', 'matched', ' | Auto-matched on 2026-04-12 00:21:48');
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('13', '3', 'TXN_002', '2025-04-02', 'Payment from Asha Mushi - SSMS002', '75000.00', 'credit', NULL, 'mismatch', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('14', '3', 'TXN_003', '2025-04-03', 'Payment from John Mwita - SSMS003', '200000.00', 'credit', '3', 'matched', ' | Auto-matched on 2026-04-12 00:21:48');
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('15', '3', 'TXN_004', '2025-04-05', 'Payment from Fatma Said - SSMS004', '100000.00', 'credit', '4', 'matched', ' | Auto-matched on 2026-04-12 00:25:18');
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('16', '4', 'MISM_001', '2025-04-04', 'Unknown payment - no reference', '50000.00', 'credit', NULL, 'mismatch', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('17', '4', 'TXN_001', '2025-04-01', 'Payment from Juma Hassan - SSMS001', '150000.00', 'credit', '2', 'matched', ' | Auto-matched on 2026-04-12 16:46:45');
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('18', '4', 'TXN_002', '2025-04-02', 'Payment from Asha Mushi - SSMS002', '75000.00', 'credit', NULL, 'mismatch', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('19', '4', 'TXN_003', '2025-04-03', 'Payment from John Mwita - SSMS003', '200000.00', 'credit', '3', 'matched', ' | Auto-matched on 2026-04-12 16:46:45');
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('20', '4', 'TXN_004', '2025-04-05', 'Payment from Fatma Said - SSMS004', '100000.00', 'credit', '4', 'matched', ' | Auto-matched on 2026-04-12 16:46:45');
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('21', '5', '12', '1970-01-01', 'Ufundi', '685645807.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('22', '5', '30', '1970-01-01', 'Kampuni ya ujenzi', '0.00', 'debit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('23', '5', '22', '1970-01-01', 'Fundi umeme', '794843408.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('24', '5', '9', '1970-01-01', 'Ezekiel korongo', '756432134.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('25', '5', '11', '1970-01-01', 'Usafiri salama zaidi', '7865432134.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('26', '5', '15', '1970-01-01', 'Kampuni bora', '786544321.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('27', '5', '16', '1970-01-01', 'Usafiri', '765456332.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('28', '5', '17', '1970-01-01', 'Mgahawa', '765545594.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('29', '5', '18', '1970-01-01', 'Forex traders', '785932754.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('30', '5', '19', '1970-01-01', 'Fundi', '759591689.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('31', '5', '20', '1970-01-01', 'Fundi', '752397248.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('32', '5', '23', '1970-01-01', 'Saloon ya kike', '764961708.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('33', '5', '24', '1970-01-01', 'Boda boda', '766771819.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('34', '5', '25', '1970-01-01', 'Fundi bati', '715302579.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('35', '5', '26', '1970-01-01', 'Fundi', '712925020.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('36', '5', '27', '1970-01-01', 'Dalali', '717739232.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('37', '5', '28', '1970-01-01', 'Dalali', '742126410.00', 'credit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('38', '5', '29', '1970-01-01', 'Dalali', '0.00', 'debit', NULL, 'pending', NULL);
INSERT INTO `bank_transactions` (`id`, `bank_statement_id`, `transaction_ref`, `transaction_date`, `description`, `amount`, `transaction_type`, `matched_transaction_id`, `match_status`, `notes`) VALUES ('39', '5', '31', '1970-01-01', 'Fundi', '743437685.00', 'credit', NULL, 'pending', NULL);

DROP TABLE IF EXISTS `behavior_records`;
CREATE TABLE `behavior_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `behavior_type` enum('positive','negative','warning','achievement') DEFAULT 'positive',
  `description` text NOT NULL,
  `points` int(11) DEFAULT 0,
  `record_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `behavior_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `behavior_records_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `behavior_records` (`id`, `student_id`, `teacher_id`, `behavior_type`, `description`, `points`, `record_date`, `created_at`) VALUES ('1', '1', '9', 'positive', 'SDFGHJ', '0', '2026-04-14', '2026-04-14 10:47:49');
INSERT INTO `behavior_records` (`id`, `student_id`, `teacher_id`, `behavior_type`, `description`, `points`, `record_date`, `created_at`) VALUES ('2', '1', '9', 'positive', 'SDF', '100', '2026-04-14', '2026-04-14 10:53:57');

DROP TABLE IF EXISTS `daily_settlements`;
CREATE TABLE `daily_settlements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `settlement_date` date NOT NULL,
  `total_card_payments` decimal(15,2) DEFAULT 0.00,
  `total_cash_payments` decimal(15,2) DEFAULT 0.00,
  `total_mpesa_payments` decimal(15,2) DEFAULT 0.00,
  `total_bank_transfer` decimal(15,2) DEFAULT 0.00,
  `total_expenses` decimal(15,2) DEFAULT 0.00,
  `net_income` decimal(15,2) GENERATED ALWAYS AS (`total_card_payments` + `total_cash_payments` + `total_mpesa_payments` + `total_bank_transfer` - `total_expenses`) STORED,
  `bank_settlement_ref` varchar(100) DEFAULT NULL,
  `is_reconciled` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE `exam_results` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `points` int(11) DEFAULT NULL,
  `division` varchar(5) DEFAULT NULL,
  `is_best_7` tinyint(1) DEFAULT 0,
  `grade` varchar(2) DEFAULT NULL,
  `exam_type` enum('Term Exam','Mid Term','Quiz','Assignment','Final') DEFAULT 'Term Exam',
  `exam_date` date DEFAULT curdate(),
  `term` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_subject` (`subject`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `exam_results` (`id`, `student_id`, `subject`, `score`, `points`, `division`, `is_best_7`, `grade`, `exam_type`, `exam_date`, `term`, `academic_year`, `created_at`) VALUES ('1', '1', 'Mathematics', '85.00', NULL, NULL, '0', NULL, 'Term Exam', '2026-04-14', NULL, NULL, '2026-04-14 10:51:45');
INSERT INTO `exam_results` (`id`, `student_id`, `subject`, `score`, `points`, `division`, `is_best_7`, `grade`, `exam_type`, `exam_date`, `term`, `academic_year`, `created_at`) VALUES ('2', '1', 'English', '78.00', NULL, NULL, '0', NULL, 'Term Exam', '2026-04-14', NULL, NULL, '2026-04-14 10:51:46');
INSERT INTO `exam_results` (`id`, `student_id`, `subject`, `score`, `points`, `division`, `is_best_7`, `grade`, `exam_type`, `exam_date`, `term`, `academic_year`, `created_at`) VALUES ('3', '1', 'MATH', '100.00', NULL, NULL, '0', NULL, 'Mid Term', '2026-04-14', NULL, NULL, '2026-04-14 10:53:20');
INSERT INTO `exam_results` (`id`, `student_id`, `subject`, `score`, `points`, `division`, `is_best_7`, `grade`, `exam_type`, `exam_date`, `term`, `academic_year`, `created_at`) VALUES ('4', '1', 'MATH', '85.00', NULL, NULL, '0', 'A', 'Mid Term', '2026-04-21', 'Term 1', '2025', '2026-04-21 07:49:42');

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque') NOT NULL,
  `receipt_attachment` varchar(255) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_number` (`expense_number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `expenses` (`id`, `expense_number`, `category`, `description`, `amount`, `expense_date`, `payment_method`, `receipt_attachment`, `approved_by`, `notes`) VALUES ('1', 'EXP-20260407-4391', 'Other', 'MAFUTA YA GARI', '1000.00', '2026-04-07', 'bank_transfer', NULL, 'PPP', NULL);

DROP TABLE IF EXISTS `fee_structure`;
CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class` varchar(50) NOT NULL,
  `school_level` enum('primary','secondary') DEFAULT 'primary',
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `term` enum('Term 1','Term 2','Term 3') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `gallery` (`id`, `title`, `description`, `filename`, `original_filename`, `file_size`, `mime_type`, `is_public`, `uploaded_by`, `created_at`) VALUES ('6', 'graduation', 'graduation', '1776244042_69df554a8378f.jpg', 'hero.jpg', '190733', 'image/jpeg', '1', '4', '2026-04-15 12:07:22');
INSERT INTO `gallery` (`id`, `title`, `description`, `filename`, `original_filename`, `file_size`, `mime_type`, `is_public`, `uploaded_by`, `created_at`) VALUES ('7', 'talent', 'talent officer', '1776244589_69df576da493c.png', 'activity pic.png', '42822', 'image/png', '1', '4', '2026-04-15 12:16:29');

DROP TABLE IF EXISTS `grading_system`;
CREATE TABLE `grading_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade` varchar(2) NOT NULL,
  `min_score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `points` decimal(3,1) DEFAULT 0.0,
  `school_level` enum('primary','secondary','both') DEFAULT 'both',
  `division` varchar(5) DEFAULT NULL,
  `points_value` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('1', 'A', '80', '100', 'Outstanding', '4.0', 'both', NULL, NULL, '1', '2026-04-13 23:58:30');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('2', 'B', '70', '79', 'Very Good', '3.5', 'both', NULL, NULL, '1', '2026-04-13 23:58:30');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('3', 'C', '60', '69', 'Good', '3.0', 'both', NULL, NULL, '1', '2026-04-13 23:58:30');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('4', 'D', '50', '59', 'Satisfactory', '2.5', 'both', NULL, NULL, '1', '2026-04-13 23:58:30');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('5', 'E', '40', '49', 'Pass', '2.0', 'both', NULL, NULL, '1', '2026-04-13 23:58:30');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('6', 'F', '0', '39', 'Fail', '0.0', 'both', NULL, NULL, '1', '2026-04-13 23:58:30');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('8', 'A', '80', '100', 'Outstanding', '4.0', 'secondary', 'I', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('9', 'B', '70', '79', 'Very Good', '3.5', 'secondary', 'I', '2', '1', '2026-04-19 16:39:56');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('10', 'C', '60', '69', 'Good', '3.0', 'secondary', 'II', '3', '1', '2026-04-19 16:39:56');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('11', 'D', '50', '59', 'Satisfactory', '2.5', 'secondary', 'III', '4', '1', '2026-04-19 16:39:56');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('12', 'E', '40', '49', 'Pass', '2.0', 'secondary', 'III', '5', '1', '2026-04-19 16:39:56');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('13', 'F', '0', '39', 'Fail', '0.0', 'secondary', 'IV', '6', '1', '2026-04-19 16:39:56');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('14', 'A', '80', '100', 'Outstanding', '4.0', 'secondary', 'I', '1', '1', '2026-04-19 16:43:02');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('15', 'B', '70', '79', 'Very Good', '3.5', 'secondary', 'I', '2', '1', '2026-04-19 16:43:02');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('16', 'C', '60', '69', 'Good', '3.0', 'secondary', 'II', '3', '1', '2026-04-19 16:43:02');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('17', 'D', '50', '59', 'Satisfactory', '2.5', 'secondary', 'III', '4', '1', '2026-04-19 16:43:02');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('18', 'E', '40', '49', 'Pass', '2.0', 'secondary', 'III', '5', '1', '2026-04-19 16:43:02');
INSERT INTO `grading_system` (`id`, `grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `division`, `points_value`, `is_active`, `created_at`) VALUES ('19', 'F', '0', '39', 'Fail', '0.0', 'secondary', 'IV', '6', '1', '2026-04-19 16:43:02');

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE `inventory_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'pcs',
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `unit_price` decimal(15,2) NOT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `last_restocked_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  KEY `category_id` (`category_id`),
  KEY `idx_stock_item` (`item_code`),
  CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory_items` (`id`, `item_code`, `item_name`, `category_id`, `unit_of_measure`, `current_stock`, `reorder_level`, `unit_price`, `supplier`, `last_restocked_date`, `is_active`) VALUES ('1', 'NB001', 'Exercise Book (200 pages)', '3', 'pcs', '500', '50', '2500.00', NULL, NULL, '1');
INSERT INTO `inventory_items` (`id`, `item_code`, `item_name`, `category_id`, `unit_of_measure`, `current_stock`, `reorder_level`, `unit_price`, `supplier`, `last_restocked_date`, `is_active`) VALUES ('2', 'UNI001', 'School Uniform (Full Set)', '4', 'pcs', '100', '20', '45000.00', NULL, NULL, '1');
INSERT INTO `inventory_items` (`id`, `item_code`, `item_name`, `category_id`, `unit_of_measure`, `current_stock`, `reorder_level`, `unit_price`, `supplier`, `last_restocked_date`, `is_active`) VALUES ('3', 'PPP1', 'UGALI', '2', 'pcs', '27', '10', '1000.00', NULL, NULL, '1');
INSERT INTO `inventory_items` (`id`, `item_code`, `item_name`, `category_id`, `unit_of_measure`, `current_stock`, `reorder_level`, `unit_price`, `supplier`, `last_restocked_date`, `is_active`) VALUES ('4', 'PPP2', 'BOOK', '3', 'pcs', '300', '10', '300.00', NULL, NULL, '1');

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `due_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `student_id` (`student_id`),
  KEY `category_id` (`category_id`),
  KEY `idx_invoice_balance` (`balance`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `balance`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES ('1', 'INV-2025-001', '1', '1', '150000.00', '150000.00', '0.00', 'Term 1', '2025', 'paid', '2026-04-07', '2026-05-07');
INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `balance`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES ('2', 'INV-20260407-1', '1', '4', '1000.00', '1000.00', '0.00', 'Term 1', '2025', 'pending', '2026-04-07', '2026-05-07');
INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `balance`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES ('3', 'INV-20260411-2-152', '2', '1', '500000.00', '500000.00', '0.00', 'Term 1', '2025', 'pending', '2026-04-11', '2026-05-11');
INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `balance`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES ('4', 'INV-20260411-4-979', '4', '1', '500001.00', '50000.00', '450001.00', 'Term 1', '2025', 'pending', '2026-04-11', '2026-05-11');
INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `balance`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES ('5', 'INV-20260411-5-787', '5', '1', '1000000.00', '0.00', '1000000.00', 'Term 1', '2025', 'pending', '2026-04-11', '2026-05-11');
INSERT INTO `invoices` (`id`, `invoice_number`, `student_id`, `category_id`, `amount`, `amount_paid`, `balance`, `term`, `academic_year`, `status`, `issue_date`, `due_date`) VALUES ('6', 'INV-20260412-1-132', '1', '1', '500000000.00', '0.00', '500000000.00', 'Term 3', '2025', 'pending', '2026-04-12', '2026-04-12');

DROP TABLE IF EXISTS `low_stock_alerts`;
CREATE TABLE `low_stock_alerts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `alert_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_stock` int(11) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `status` enum('pending','acknowledged','resolved') DEFAULT 'pending',
  `resolved_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `idx_low_stock` (`status`),
  CONSTRAINT `low_stock_alerts_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `parent_students`;
CREATE TABLE `parent_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `relationship` enum('father','mother','guardian','other') DEFAULT 'guardian',
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_parent_student` (`parent_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `parent_students_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `relationship`, `is_primary`, `created_at`) VALUES ('1', '10', '1', 'father', '1', '2026-04-14 10:21:26');
INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `relationship`, `is_primary`, `created_at`) VALUES ('3', '5', '5', 'father', '1', '2026-04-15 08:52:33');
INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `relationship`, `is_primary`, `created_at`) VALUES ('4', '5', '2', 'mother', '1', '2026-04-15 08:58:26');
INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `relationship`, `is_primary`, `created_at`) VALUES ('7', '12', '5', 'father', '1', '2026-04-15 11:32:57');

DROP TABLE IF EXISTS `reconciliation_reports`;
CREATE TABLE `reconciliation_reports` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `bank_statement_id` bigint(20) NOT NULL,
  `total_bank_credits` decimal(15,2) DEFAULT NULL,
  `total_system_credits` decimal(15,2) DEFAULT NULL,
  `variance` decimal(15,2) GENERATED ALWAYS AS (`total_bank_credits` - `total_system_credits`) STORED,
  `reconciled_by` varchar(100) DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','reconciled','discrepancy') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_statement_id` (`bank_statement_id`),
  CONSTRAINT `reconciliation_reports_ibfk_1` FOREIGN KEY (`bank_statement_id`) REFERENCES `bank_statements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `revenue_categories`;
CREATE TABLE `revenue_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `revenue_categories` (`id`, `category_name`, `description`, `is_active`) VALUES ('1', 'Tuition', 'School fees per term', '1');
INSERT INTO `revenue_categories` (`id`, `category_name`, `description`, `is_active`) VALUES ('2', 'Canteen', 'Food and drinks', '1');
INSERT INTO `revenue_categories` (`id`, `category_name`, `description`, `is_active`) VALUES ('3', 'Stationery', 'Books, pens, notebooks', '1');
INSERT INTO `revenue_categories` (`id`, `category_name`, `description`, `is_active`) VALUES ('4', 'Uniforms', 'School uniforms and badges', '1');
INSERT INTO `revenue_categories` (`id`, `category_name`, `description`, `is_active`) VALUES ('5', 'Transport', 'School bus fees', '1');

DROP TABLE IF EXISTS `school_calendar`;
CREATE TABLE `school_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_type` enum('holiday','exam','event','meeting','other') DEFAULT 'event',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `school_calendar_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_calendar` (`id`, `title`, `description`, `event_date`, `event_type`, `is_active`, `created_by`, `created_at`) VALUES ('1', 'mahafali', 'darasa la saba', '2026-04-14', 'event', '1', '4', '2026-04-14 00:58:19');
INSERT INTO `school_calendar` (`id`, `title`, `description`, `event_date`, `event_type`, `is_active`, `created_by`, `created_at`) VALUES ('2', 'TALENT SHOW', 'talent show', '2026-04-15', 'event', '1', '4', '2026-04-15 11:40:28');

DROP TABLE IF EXISTS `school_settings`;
CREATE TABLE `school_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_name` varchar(200) NOT NULL DEFAULT 'SSMS Tanzania',
  `school_type` enum('primary','secondary','both') DEFAULT 'both',
  `enable_period_attendance` tinyint(1) DEFAULT 0,
  `default_grading_system` enum('primary','secondary') DEFAULT 'primary',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_settings` (`id`, `school_name`, `school_type`, `enable_period_attendance`, `default_grading_system`, `school_logo`, `school_address`, `school_phone`, `school_email`, `school_website`, `tin_number`, `registration_number`, `motto`, `academic_year`, `current_term`, `term_start_date`, `term_end_date`, `currency`, `timezone`, `created_at`, `updated_at`) VALUES ('1', 'SSMS Tanzania', 'both', '0', 'secondary', NULL, 'Dar es Salaam, Tanzania', '+255 123 456 789', 'info@ssms.co.tz', '', '', '', 'Quality Education for All', '2025', 'Term 1', '0000-00-00', '0000-00-00', 'TZS', 'Africa/Dar_es_Salaam', '2026-04-13 23:58:30', '2026-04-19 17:01:29');

DROP TABLE IF EXISTS `smart_cards`;
CREATE TABLE `smart_cards` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `card_uid` varchar(100) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `issued_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_uid` (`card_uid`),
  UNIQUE KEY `payment_reference` (`payment_reference`),
  KEY `student_id` (`student_id`),
  KEY `idx_card_uid` (`card_uid`),
  KEY `idx_payment_reference` (`payment_reference`),
  CONSTRAINT `smart_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `smart_cards` (`id`, `card_uid`, `student_id`, `payment_reference`, `balance`, `is_active`, `issued_date`, `expiry_date`) VALUES ('1', 'RFID:A1B2C3D4', '1', 'REF001', '250000.00', '1', '2026-04-07', NULL);
INSERT INTO `smart_cards` (`id`, `card_uid`, `student_id`, `payment_reference`, `balance`, `is_active`, `issued_date`, `expiry_date`) VALUES ('2', '2345678', '2', 'UX_200', '0.00', '1', '2026-04-11', NULL);
INSERT INTO `smart_cards` (`id`, `card_uid`, `student_id`, `payment_reference`, `balance`, `is_active`, `issued_date`, `expiry_date`) VALUES ('3', '2345000', '4', 'TXN_004', '0.00', '1', '2026-04-11', NULL);

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_type` enum('purchase','sale_card','sale_cash','return','adjustment') NOT NULL,
  `reference_id` bigint(20) DEFAULT NULL,
  `student_id` bigint(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `student_payments`;
CREATE TABLE `student_payments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) NOT NULL,
  `control_number` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','completed','failed','expired') DEFAULT 'pending',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `control_number` (`control_number`),
  KEY `student_id` (`student_id`),
  KEY `idx_control_number` (`control_number`),
  KEY `idx_status` (`status`),
  CONSTRAINT `student_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `class` varchar(50) NOT NULL,
  `school_level` enum('primary','secondary') DEFAULT 'primary',
  `prem_number` varchar(50) DEFAULT NULL,
  `psle_number` varchar(50) DEFAULT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `student_type_label` varchar(20) GENERATED ALWAYS AS (case when `school_level` = 'primary' then 'Pupil' when `school_level` = 'secondary' then 'Student' else 'Student' end) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_number` (`student_number`),
  UNIQUE KEY `prem_number` (`prem_number`),
  KEY `idx_student_number` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `school_level`, `prem_number`, `psle_number`, `index_number`, `parent_phone`, `is_active`, `created_at`, `student_type_label`) VALUES ('1', 'SSMS001', 'Juma Hassan', 'Form 1A', 'primary', NULL, NULL, NULL, '0712345678', '1', '2026-04-07 08:18:03', 'Pupil');
INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `school_level`, `prem_number`, `psle_number`, `index_number`, `parent_phone`, `is_active`, `created_at`, `student_type_label`) VALUES ('2', 'ss100', 'john joshua', '3A', 'primary', NULL, NULL, NULL, '0712583913', '1', '2026-04-11 04:31:17', 'Pupil');
INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `school_level`, `prem_number`, `psle_number`, `index_number`, `parent_phone`, `is_active`, `created_at`, `student_type_label`) VALUES ('4', 'ssm200', 'elia baltazary', '3A', 'primary', NULL, NULL, NULL, '0712583912', '1', '2026-04-11 05:19:24', 'Pupil');
INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `school_level`, `prem_number`, `psle_number`, `index_number`, `parent_phone`, `is_active`, `created_at`, `student_type_label`) VALUES ('5', 'sss200', 'kalebu', '3A', 'primary', NULL, NULL, NULL, '0712583914', '1', '2026-04-11 23:43:57', 'Pupil');
INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `school_level`, `prem_number`, `psle_number`, `index_number`, `parent_phone`, `is_active`, `created_at`, `student_type_label`) VALUES ('6', 'eliza', 'eliza', 'Not Assigned', 'primary', NULL, NULL, NULL, '', '1', '2026-04-14 09:46:32', 'Pupil');
INSERT INTO `students` (`id`, `student_number`, `full_name`, `class`, `school_level`, `prem_number`, `psle_number`, `index_number`, `parent_phone`, `is_active`, `created_at`, `student_type_label`) VALUES ('7', 'NAZIEL', 'NAZIELI', 'Standard 1', 'primary', '2024123456789', '', '', '0692416596', '1', '2026-04-19 17:26:11', 'Pupil');

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `school_level` enum('primary','secondary','both') DEFAULT 'both',
  `category` enum('core','science','arts','business','optional') DEFAULT 'core',
  `is_compulsory` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subject_code` (`subject_code`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('1', 'PRI_MATH', 'Hisabati', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('2', 'PRI_ENG', 'Kiingereza', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('3', 'PRI_SWA', 'Kiswahili', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('4', 'PRI_SCI', 'Sayansi', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('5', 'PRI_SST', 'Maarifa ya Jamii', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('6', 'PRI_VOC', 'Stadi za Kazi', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('7', 'PRI_SPORTS', 'Haiba na Michezo', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('8', 'PRI_CIVIC', 'Uraia', 'primary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('9', 'SEC_MATH', 'Mathematics', 'secondary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('10', 'SEC_ENG', 'English', 'secondary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('11', 'SEC_KISW', 'Kiswahili', 'secondary', 'core', '1', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('12', 'SEC_BIO', 'Biology', 'secondary', 'science', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('13', 'SEC_CHEM', 'Chemistry', 'secondary', 'science', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('14', 'SEC_PHY', 'Physics', 'secondary', 'science', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('15', 'SEC_HIST', 'History', 'secondary', 'arts', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('16', 'SEC_GEO', 'Geography', 'secondary', 'arts', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('17', 'SEC_CIV', 'Civics', 'secondary', 'arts', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('18', 'SEC_COMM', 'Commerce', 'secondary', 'business', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('19', 'SEC_BKEEP', 'Bookkeeping', 'secondary', 'business', '0', '1', '2026-04-19 16:39:56');
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`, `is_active`, `created_at`) VALUES ('20', 'SEC_COMP', 'Computer Science', 'secondary', 'optional', '0', '1', '2026-04-19 16:39:56');

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('1', '4', 'Created User', 'Created new parent account: asha_parent', '::1', NULL, '2026-04-15 07:04:15');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES ('2', '4', 'Created Student', 'Created primary student account: NAZIELI (NAZIEL)', '::1', NULL, '2026-04-19 17:26:11');

DROP TABLE IF EXISTS `teacher_classes`;
CREATE TABLE `teacher_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `class` varchar(50) NOT NULL,
  `is_class_teacher` tinyint(1) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT '2025',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_class` (`teacher_id`,`class`,`academic_year`),
  CONSTRAINT `teacher_classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teacher_classes` (`id`, `teacher_id`, `class`, `is_class_teacher`, `academic_year`, `created_at`) VALUES ('1', '9', 'Form 1A', '1', '2025', '2026-04-14 10:21:26');

DROP TABLE IF EXISTS `teacher_subjects`;
CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `class` varchar(50) NOT NULL,
  `academic_year` varchar(20) DEFAULT '2025',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject`, `class`, `academic_year`, `created_at`) VALUES ('1', '9', 'Mathematics', 'Form 1A', '2025', '2026-04-14 10:21:26');
INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject`, `class`, `academic_year`, `created_at`) VALUES ('2', '9', 'English', 'Form 1A', '2025', '2026-04-14 10:21:26');

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_ref` (`transaction_ref`),
  KEY `student_id` (`student_id`),
  KEY `card_id` (`card_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `idx_transaction_ref` (`transaction_ref`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_is_reconciled` (`is_reconciled`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`card_id`) REFERENCES `smart_cards` (`id`),
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transactions` (`id`, `transaction_ref`, `student_id`, `card_id`, `invoice_id`, `amount`, `payment_method`, `payment_channel`, `transaction_date`, `bank_statement_ref`, `is_reconciled`, `reconciled_date`, `notes`) VALUES ('1', 'TXN_1775544594_6353', '1', NULL, '2', '1000.00', 'mpesa', NULL, '2026-04-07 09:49:54', NULL, '0', NULL, NULL);
INSERT INTO `transactions` (`id`, `transaction_ref`, `student_id`, `card_id`, `invoice_id`, `amount`, `payment_method`, `payment_channel`, `transaction_date`, `bank_statement_ref`, `is_reconciled`, `reconciled_date`, `notes`) VALUES ('2', 'TXN_001', '1', NULL, '1', '150000.00', 'mpesa', NULL, '2026-04-11 03:16:24', NULL, '1', '2026-04-12', ' | RECONCILIATION REMOVED on 2026-04-12 00:18:26');
INSERT INTO `transactions` (`id`, `transaction_ref`, `student_id`, `card_id`, `invoice_id`, `amount`, `payment_method`, `payment_channel`, `transaction_date`, `bank_statement_ref`, `is_reconciled`, `reconciled_date`, `notes`) VALUES ('3', 'TXN_003', '2', NULL, '3', '500000.00', 'card', NULL, '2026-04-11 05:08:48', NULL, '1', '2026-04-12', ' | RECONCILIATION REMOVED on 2026-04-12 00:18:20');
INSERT INTO `transactions` (`id`, `transaction_ref`, `student_id`, `card_id`, `invoice_id`, `amount`, `payment_method`, `payment_channel`, `transaction_date`, `bank_statement_ref`, `is_reconciled`, `reconciled_date`, `notes`) VALUES ('4', 'TXN_004', '4', NULL, '4', '50000.00', 'card', NULL, '2026-04-11 05:23:05', NULL, '1', '2026-04-12', ' | EDITED: wrong (Old amount: 500001.00) | RECONCILIATION REMOVED on 2026-04-11 23:59:12 | EDITED: wrong (Old amount: 500000.00) | RECONCILIATION REMOVED on 2026-04-12 00:06:28 | REFERENCE CHANGED: wrong (Old: TXN_004) | REFERENCE CHANGED: wrong (Old: TXN_007)');
INSERT INTO `transactions` (`id`, `transaction_ref`, `student_id`, `card_id`, `invoice_id`, `amount`, `payment_method`, `payment_channel`, `transaction_date`, `bank_statement_ref`, `is_reconciled`, `reconciled_date`, `notes`) VALUES ('6', 'ws_CO_13042026080651717708374149', '4', NULL, NULL, '50.00', 'mpesa', NULL, '2026-04-13 08:06:48', NULL, '0', NULL, NULL);

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nin_number` varchar(50) DEFAULT NULL,
  `employment_id` varchar(50) DEFAULT NULL,
  `role` enum('super_admin','accountant','teacher','parent','student') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `nin_number` (`nin_number`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('3', 'elia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elia mhapa', 'eliamhapa34@gmail.com', NULL, NULL, NULL, 'accountant', '1', '2026-04-07 09:46:33', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('4', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'admin@ssms.co.tz', NULL, NULL, NULL, 'super_admin', '1', '2026-04-14 00:23:12', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('5', 'eliza', '$2y$10$B3H/0Gdjemq4anejSJOGKeTWEQrTkMjBTjXo60U77et9jgmQ98J0a', 'eliza', '', NULL, NULL, NULL, 'student', '1', '2026-04-14 01:54:04', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('9', 'teacher.juma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juma Teacher', 'teacher@ssms.com', NULL, NULL, NULL, 'teacher', '1', '2026-04-14 10:21:26', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('10', 'parent.juma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Parent of Juma', 'parent@ssms.com', NULL, NULL, NULL, 'parent', '1', '2026-04-14 10:21:26', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('11', 'asha_parent', '$2y$10$X8ob7g6VR0TpK8w3OHVQgeOdMg0PkvUxpjC0cGdX22pP1VPtyJtqO', 'asha_parent', NULL, NULL, NULL, NULL, 'parent', '1', '2026-04-15 07:04:15', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('12', 'bahati', '$2y$10$cZZg5fdN5DZad6mKCGRDgORZzJmOg6lB3V/lUZfDsjCi5r5Kpk.CO', 'bahati', '', '0752643189', NULL, NULL, 'parent', '1', '2026-04-15 11:32:57', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('17', 'NAZIELI', '$2y$10$Fqls4VhjZDXGjbQPOaJT6.JkMXXF3o9TD6Xs0n5zlq7/mO8UEHWt6', 'NAZIELI', 'eliamhapa34@gmail.com', NULL, NULL, NULL, 'student', '1', '2026-04-19 17:26:11', NULL, NULL, NULL, '0', NULL);
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `nin_number`, `employment_id`, `role`, `is_active`, `created_at`, `last_login`, `last_ip`, `profile_pic`, `login_attempts`, `locked_until`) VALUES ('18', 'NAZIEL', '$2y$10$msfzTBGDBlqCQgH698u/9u3YdzV.j0FgmIaIpF3iENFP0SaF4u0um', 'NAZIELI', '', NULL, NULL, NULL, 'student', '1', '2026-04-21 07:15:06', NULL, NULL, NULL, '0', NULL);

SET FOREIGN_KEY_CHECKS=1;
