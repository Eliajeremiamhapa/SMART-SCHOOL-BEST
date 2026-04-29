-- ============================================
-- SSMS TANZANIA - FINANCE MODULE (ACCOUNTANT)
-- ============================================

-- 1. STUDENTS (reference table)
CREATE TABLE students (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    class VARCHAR(50) NOT NULL,
    parent_phone VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. SMART CARDS
CREATE TABLE smart_cards (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    card_uid VARCHAR(100) UNIQUE NOT NULL, -- RFID/NFC unique ID
    student_id BIGINT NOT NULL,
    payment_reference VARCHAR(50) UNIQUE NOT NULL, -- Digital Fee Mapping
    balance DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    issued_date DATE NOT NULL,
    expiry_date DATE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 3. REVENUE CATEGORIES (Tuition, Canteen, Stationery, Uniforms, Transport)
CREATE TABLE revenue_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- 4. FEE STRUCTURE (Kiasi cha ada kwa kila darasa/kategoria)
CREATE TABLE fee_structure (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    class VARCHAR(50) NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    term VARCHAR(20) NOT NULL, -- Term 1, Term 2, Term 3
    academic_year VARCHAR(20) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES revenue_categories(id)
);

-- 5. INVOICES / RECEIPTS
CREATE TABLE invoices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    student_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0.00,
    balance DECIMAL(15,2) GENERATED ALWAYS AS (amount - amount_paid) STORED,
    term VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (category_id) REFERENCES revenue_categories(id)
);

-- 6. TRANSACTIONS (All payments - Cash, Mobile Money, Card, Bank Transfer)
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transaction_ref VARCHAR(100) UNIQUE NOT NULL, -- Reference from M-Pesa/Bank
    student_id BIGINT NOT NULL,
    card_id BIGINT NULL, -- If paid via smart card
    invoice_id BIGINT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'mpesa', 'tigopesa', 'bank_transfer', 'card') NOT NULL,
    payment_channel VARCHAR(50), -- e.g., "Vodacom M-Pesa", "CRDB Bank"
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bank_statement_ref VARCHAR(100), -- For reconciliation
    is_reconciled BOOLEAN DEFAULT FALSE,
    reconciled_date DATE NULL,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (card_id) REFERENCES smart_cards(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

-- 7. BANK STATEMENTS IMPORT (For reconciliation)
CREATE TABLE bank_statements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    filename VARCHAR(255) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    starting_balance DECIMAL(15,2),
    ending_balance DECIMAL(15,2),
    status ENUM('imported', 'processing', 'reconciled') DEFAULT 'imported'
);

-- 8. BANK TRANSACTIONS (Extracted from imported statement)
CREATE TABLE bank_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bank_statement_id BIGINT NOT NULL,
    transaction_ref VARCHAR(100) NOT NULL, -- Reference number from bank
    transaction_date DATE NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    matched_transaction_id BIGINT NULL, -- Link to transactions table
    match_status ENUM('pending', 'matched', 'mismatch') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (bank_statement_id) REFERENCES bank_statements(id),
    FOREIGN KEY (matched_transaction_id) REFERENCES transactions(id)
);

-- 9. EXPENSES (School expenditures)
CREATE TABLE expenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    expense_number VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(100) NOT NULL, -- Salary, Utilities, Supplies, Maintenance
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'cheque') NOT NULL,
    receipt_attachment VARCHAR(255), -- File path
    approved_by VARCHAR(100),
    notes TEXT
);

-- 10. INVENTORY / STOCK (Bidhaa za shule)
CREATE TABLE inventory_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL, -- Links to revenue_categories
    unit_of_measure VARCHAR(20) DEFAULT 'pcs', -- pcs, kg, liters
    current_stock INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 10, -- Low stock alert threshold
    unit_price DECIMAL(15,2) NOT NULL,
    supplier VARCHAR(150),
    last_restocked_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES revenue_categories(id)
);

-- 11. STOCK TRANSACTIONS (In/Out movements)
CREATE TABLE stock_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    item_id BIGINT NOT NULL,
    transaction_type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_amount DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reference_type ENUM('purchase', 'sale_card', 'sale_cash', 'return', 'adjustment') NOT NULL,
    reference_id BIGINT, -- Could be transaction_id or expense_id
    student_id BIGINT NULL, -- If sold to a student
    notes TEXT,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- 12. LOW STOCK ALERTS (Generated automatically)
CREATE TABLE low_stock_alerts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    item_id BIGINT NOT NULL,
    alert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_stock INT NOT NULL,
    reorder_level INT NOT NULL,
    status ENUM('pending', 'acknowledged', 'resolved') DEFAULT 'pending',
    resolved_date DATE NULL,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- 13. RECONCILIATION REPORTS (Stored historical reports)
CREATE TABLE reconciliation_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    bank_statement_id BIGINT NOT NULL,
    total_bank_credits DECIMAL(15,2),
    total_system_credits DECIMAL(15,2),
    variance DECIMAL(15,2) GENERATED ALWAYS AS (total_bank_credits - total_system_credits) STORED,
    reconciled_by VARCHAR(100),
    report_file VARCHAR(255), -- PDF path
    status ENUM('pending', 'reconciled', 'discrepancy') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (bank_statement_id) REFERENCES bank_statements(id)
);

-- 14. DAILY SETTLEMENT SUMMARY (For card payments)
CREATE TABLE daily_settlements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    settlement_date DATE NOT NULL,
    total_card_payments DECIMAL(15,2) DEFAULT 0.00,
    total_cash_payments DECIMAL(15,2) DEFAULT 0.00,
    total_mpesa_payments DECIMAL(15,2) DEFAULT 0.00,
    total_bank_transfer DECIMAL(15,2) DEFAULT 0.00,
    total_expenses DECIMAL(15,2) DEFAULT 0.00,
    net_income DECIMAL(15,2) GENERATED ALWAYS AS (
        total_card_payments + total_cash_payments + total_mpesa_payments + total_bank_transfer - total_expenses
    ) STORED,
    bank_settlement_ref VARCHAR(100), -- Reference from bank for settled amount
    is_reconciled BOOLEAN DEFAULT FALSE,
    notes TEXT
);

-- ============================================
-- INDEXES for Performance
-- ============================================

CREATE INDEX idx_student_number ON students(student_number);
CREATE INDEX idx_card_uid ON smart_cards(card_uid);
CREATE INDEX idx_payment_reference ON smart_cards(payment_reference);
CREATE INDEX idx_transaction_ref ON transactions(transaction_ref);
CREATE INDEX idx_transaction_date ON transactions(transaction_date);
CREATE INDEX idx_is_reconciled ON transactions(is_reconciled);
CREATE INDEX idx_bank_statement_id ON bank_transactions(bank_statement_id);
CREATE INDEX idx_match_status ON bank_transactions(match_status);
CREATE INDEX idx_invoice_balance ON invoices(balance);
CREATE INDEX idx_stock_item ON inventory_items(item_code);
CREATE INDEX idx_low_stock ON low_stock_alerts(status);

-- ============================================
-- SAMPLE DATA (For testing)
-- ============================================

-- Insert Revenue Categories
INSERT INTO revenue_categories (category_name, description) VALUES
('Tuition', 'School fees per term'),
('Canteen', 'Food and drinks'),
('Stationery', 'Books, pens, notebooks'),
('Uniforms', 'School uniforms and badges'),
('Transport', 'School bus fees');

-- Insert Sample Student
INSERT INTO students (student_number, full_name, class, parent_phone) VALUES
('SSMS001', 'Juma Hassan', 'Form 1A', '0712345678');

-- Insert Smart Card for the student
INSERT INTO smart_cards (card_uid, student_id, payment_reference, balance, issued_date) VALUES
('RFID:A1B2C3D4', 1, 'REF001', 250000.00, CURDATE());

-- Insert Fee Structure
INSERT INTO fee_structure (class, category_id, amount, term, academic_year) VALUES
('Form 1A', 1, 150000.00, 'Term 1', '2025'),
('Form 1A', 5, 50000.00, 'Term 1', '2025');

-- Insert Invoice
INSERT INTO invoices (invoice_number, student_id, category_id, amount, amount_paid, term, academic_year, issue_date, due_date) VALUES
('INV-2025-001', 1, 1, 150000.00, 0.00, 'Term 1', '2025', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- Insert Inventory Items
INSERT INTO inventory_items (item_code, item_name, category_id, current_stock, reorder_level, unit_price) VALUES
('NB001', 'Exercise Book (200 pages)', 3, 500, 50, 2500.00),
('UNI001', 'School Uniform (Full Set)', 4, 100, 20, 45000.00);





-- added users
-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    role ENUM('super_admin', 'accountant', 'teacher', 'parent', 'student') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default accountant user (password: accountant123)
INSERT INTO users (username, password, full_name, role) VALUES
('accountant@ssms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Mwita', 'accountant');




-- ADDED TO RESOLVE PASSWORD ISSUES
-- First, check if user exists
SELECT * FROM users WHERE username = 'accountant@ssms.com';

-- If exists, delete it
DELETE FROM users WHERE username = 'accountant@ssms.com';

-- Insert fresh user (plain password will work with new login code)
INSERT INTO users (username, password, full_name, email, role, is_active) 
VALUES (
    'accountant@ssms.com',
    'accountant123',  -- Plain text password (temporarily)
    'John Mwita',
    'accountant@ssms.com',
    'accountant',
    1
);




-- kuhusu kubadilisha password
-- Angalia password ya sasa (inaonekana plain text 'accountant123' au hash?)
SELECT id, username, password FROM users WHERE username = 'accountant@ssms.com';

-- Badilisha kuwa hash sahihi
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'accountant@ssms.com';


-- payments
-- Create table for tracking payments
CREATE TABLE IF NOT EXISTS student_payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    control_number VARCHAR(100) UNIQUE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'expired') DEFAULT 'pending',
    transaction_ref VARCHAR(100),
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    INDEX idx_control_number (control_number),
    INDEX idx_status (status)
);



-- ADDED FOR RELATION
-- ============================================
-- TEACHER AND PARENT ASSIGNMENT TABLES
-- ============================================

-- 1. Teacher class assignment (which class a teacher teaches)
CREATE TABLE IF NOT EXISTS teacher_classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    class VARCHAR(50) NOT NULL,
    is_class_teacher TINYINT(1) DEFAULT 0,
    academic_year VARCHAR(20) DEFAULT '2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_class (teacher_id, class, academic_year)
);

-- 2. Teacher subjects (which subjects a teacher teaches)
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    class VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) DEFAULT '2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Parent-Student relationship
CREATE TABLE IF NOT EXISTS parent_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NOT NULL,
    student_id BIGINT NOT NULL,
    relationship ENUM('father', 'mother', 'guardian', 'other') DEFAULT 'guardian',
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parent_student (parent_id, student_id)
);

-- 4. Student behavior tracking
CREATE TABLE IF NOT EXISTS behavior_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    teacher_id INT NOT NULL,
    behavior_type ENUM('positive', 'negative', 'warning', 'achievement') DEFAULT 'positive',
    description TEXT NOT NULL,
    points INT DEFAULT 0,
    record_date DATE DEFAULT CURDATE(),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- INSERT SAMPLE DATA FOR TESTING
-- ============================================

-- Create a teacher user (password: password123)
INSERT INTO users (username, password, full_name, email, role, is_active) 
SELECT 'teacher.juma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juma Teacher', 'teacher@ssms.com', 'teacher', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'teacher.juma');

-- Get teacher ID
SET @teacher_id = (SELECT id FROM users WHERE username = 'teacher.juma' LIMIT 1);

-- Assign teacher to Form 1A class
INSERT INTO teacher_classes (teacher_id, class, is_class_teacher) 
SELECT @teacher_id, 'Form 1A', 1
WHERE NOT EXISTS (SELECT 1 FROM teacher_classes WHERE teacher_id = @teacher_id AND class = 'Form 1A');

-- Assign teacher subjects
INSERT INTO teacher_subjects (teacher_id, subject, class) 
SELECT @teacher_id, 'Mathematics', 'Form 1A'
WHERE NOT EXISTS (SELECT 1 FROM teacher_subjects WHERE teacher_id = @teacher_id AND subject = 'Mathematics');

INSERT INTO teacher_subjects (teacher_id, subject, class) 
SELECT @teacher_id, 'English', 'Form 1A'
WHERE NOT EXISTS (SELECT 1 FROM teacher_subjects WHERE teacher_id = @teacher_id AND subject = 'English');

-- Create a parent user (password: password123)
INSERT INTO users (username, password, full_name, email, role, is_active) 
SELECT 'parent.juma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Parent of Juma', 'parent@ssms.com', 'parent', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'parent.juma');

-- Get parent ID and student ID
SET @parent_id = (SELECT id FROM users WHERE username = 'parent.juma' LIMIT 1);
SET @student_id = (SELECT id FROM students WHERE student_number = 'SSMS001' LIMIT 1);

-- Link parent to student
INSERT INTO parent_students (parent_id, student_id, relationship, is_primary) 
SELECT @parent_id, @student_id, 'father', 1
WHERE @student_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM parent_students WHERE parent_id = @parent_id AND student_id = @student_id);


-- ADDED MISSED TABLE
-- ============================================
-- CREATE MISSING TABLES FOR TEACHER MODULE
-- ============================================

-- 1. Exam results table
CREATE TABLE IF NOT EXISTS exam_results (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    grade VARCHAR(2),
    exam_type ENUM('Term Exam', 'Mid Term', 'Quiz', 'Assignment', 'Final') DEFAULT 'Term Exam',
    exam_date DATE DEFAULT CURDATE(),
    term VARCHAR(20),
    academic_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_subject (subject)
);

-- 2. Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    check_in TIME,
    check_out TIME,
    remarks TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, attendance_date),
    INDEX idx_date (attendance_date),
    INDEX idx_status (status)
);

-- 3. Behavior records table (if not exists)
CREATE TABLE IF NOT EXISTS behavior_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    student_id BIGINT NOT NULL,
    teacher_id INT NOT NULL,
    behavior_type ENUM('positive', 'negative', 'warning', 'achievement') DEFAULT 'positive',
    description TEXT NOT NULL,
    points INT DEFAULT 0,
    record_date DATE DEFAULT CURDATE(),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_date (record_date)
);

-- 4. Insert sample attendance for testing (optional)
INSERT INTO attendance (student_id, attendance_date, status, check_in, remarks) 
SELECT id, CURDATE(), 'present', '08:00:00', 'On time' 
FROM students WHERE is_active = 1 LIMIT 3
ON DUPLICATE KEY UPDATE status = 'present';

-- 5. Insert sample exam results for testing (optional)
INSERT INTO exam_results (student_id, subject, score, exam_type, exam_date) 
SELECT id, 'Mathematics', 85, 'Term Exam', CURDATE() 
FROM students WHERE student_number = 'SSMS001' LIMIT 1
ON DUPLICATE KEY UPDATE score = 85;

INSERT INTO exam_results (student_id, subject, score, exam_type, exam_date) 
SELECT id, 'English', 78, 'Term Exam', CURDATE() 
FROM students WHERE student_number = 'SSMS001' LIMIT 1
ON DUPLICATE KEY UPDATE score = 78;

-- Verify tables created
SHOW TABLES LIKE 'exam_results';
SHOW TABLES LIKE 'attendance';
SHOW TABLES LIKE 'behavior_records';

-- ADDED STUDENT PARENT RELATION SHIP
-- Parent-Student relationship table
CREATE TABLE IF NOT EXISTS parent_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NOT NULL,
    student_id BIGINT NOT NULL,
    relationship ENUM('father', 'mother', 'guardian', 'other') DEFAULT 'guardian',
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parent_student (parent_id, student_id)
);

-- Sample parent user (password: password123)
INSERT INTO users (username, password, full_name, email, role, is_active) 
SELECT 'parent.juma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Parent of Juma', 'parent@ssms.com', 'parent', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'parent.juma');

-- Link parent to student (Juma Hassan - SSMS001)
INSERT INTO parent_students (parent_id, student_id, relationship, is_primary) 
SELECT u.id, s.id, 'father', 1
FROM users u, students s
WHERE u.username = 'parent.juma' AND s.student_number = 'SSMS001'
ON DUPLICATE KEY UPDATE relationship = 'father';


ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) NULL AFTER `email`;



-- ADDITIONALFEATURES.
-- ============================================
-- ADDITIONS FOR SSMS TANZANIA
-- PRIMARY & SECONDARY SCHOOL SUPPORT
-- ============================================

-- ============================================
-- 1. STUDENTS TABLE ADDITIONS
-- ============================================
-- Add school level (primary or secondary)
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `school_level` ENUM('primary', 'secondary') DEFAULT 'primary' 
AFTER `class`;

-- Add PREM Number (Unique ID for all students from primary to secondary)
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `prem_number` VARCHAR(50) UNIQUE DEFAULT NULL 
AFTER `school_level`;

-- Add PSLE Number (For secondary students - their primary leaving exam number)
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `psle_number` VARCHAR(50) DEFAULT NULL 
AFTER `prem_number`;

-- Add NECTA Index Number (For secondary students sitting for national exams)
ALTER TABLE `students` 
ADD COLUMN IF NOT EXISTS `index_number` VARCHAR(50) DEFAULT NULL 
AFTER `psle_number`;


-- ============================================
-- 2. USERS TABLE ADDITIONS (For teachers and staff)
-- ============================================
-- Add NIN (NIDA Number) for teachers and staff
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `nin_number` VARCHAR(50) UNIQUE DEFAULT NULL 
AFTER `phone`;

-- Add employment ID for teachers/staff
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `employment_id` VARCHAR(50) DEFAULT NULL 
AFTER `nin_number`;


-- ============================================
-- 3. GRADING SYSTEM TABLE ADDITIONS
-- ============================================
-- Add school_level to grading system (primary vs secondary)
ALTER TABLE `grading_system` 
ADD COLUMN IF NOT EXISTS `school_level` ENUM('primary', 'secondary', 'both') DEFAULT 'both' 
AFTER `points`;

-- Add division for secondary grading (I, II, III, IV)
ALTER TABLE `grading_system` 
ADD COLUMN IF NOT EXISTS `division` VARCHAR(5) DEFAULT NULL 
AFTER `school_level`;

-- Add points_value for secondary (A=1, B=2, C=3, D=4, E=5, F=6)
ALTER TABLE `grading_system` 
ADD COLUMN IF NOT EXISTS `points_value` INT DEFAULT NULL 
AFTER `division`;


-- ============================================
-- 4. SUBJECTS TABLE (New table for subject mapping)
-- ============================================
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `subject_code` VARCHAR(50) NOT NULL,
    `subject_name` VARCHAR(150) NOT NULL,
    `school_level` ENUM('primary', 'secondary', 'both') DEFAULT 'both',
    `category` ENUM('core', 'science', 'arts', 'business', 'optional') DEFAULT 'core',
    `is_compulsory` BOOLEAN DEFAULT TRUE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_subject_code` (`subject_code`)
);


-- ============================================
-- 5. TEACHER SUBJECTS TABLE (Link teachers to subjects)
-- ============================================
CREATE TABLE IF NOT EXISTS `teacher_subjects` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `teacher_id` INT NOT NULL,
    `subject_id` INT NOT NULL,
    `class` VARCHAR(50) NOT NULL,
    `school_level` ENUM('primary', 'secondary') DEFAULT 'secondary',
    `academic_year` VARCHAR(20) DEFAULT '2025',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_teacher_subject_class` (`teacher_id`, `subject_id`, `class`, `academic_year`)
);


-- ============================================
-- 6. ATTENDANCE TABLE ADDITIONS (For period-wise attendance)
-- ============================================
-- Add period/slot for secondary period-wise attendance
ALTER TABLE `attendance` 
ADD COLUMN IF NOT EXISTS `period` VARCHAR(50) DEFAULT NULL 
AFTER `attendance_date`;

-- Add subject_id for period-wise attendance
ALTER TABLE `attendance` 
ADD COLUMN IF NOT EXISTS `subject_id` INT DEFAULT NULL 
AFTER `period`;

-- Add school_level to attendance
ALTER TABLE `attendance` 
ADD COLUMN IF NOT EXISTS `school_level` ENUM('primary', 'secondary') DEFAULT 'primary' 
AFTER `subject_id`;


-- ============================================
-- 7. EXAM RESULTS TABLE ADDITIONS
-- ============================================
-- Add term (Term 1, Term 2, Term 3) to exam_results
ALTER TABLE `exam_results` 
ADD COLUMN IF NOT EXISTS `term` VARCHAR(20) DEFAULT 'Term 1' 
AFTER `exam_date`;

-- Add year to exam_results
ALTER TABLE `exam_results` 
ADD COLUMN IF NOT EXISTS `academic_year` VARCHAR(20) DEFAULT '2025' 
AFTER `term`;

-- Add points for secondary grading
ALTER TABLE `exam_results` 
ADD COLUMN IF NOT EXISTS `points` INT DEFAULT NULL 
AFTER `score`;

-- Add division for secondary results
ALTER TABLE `exam_results` 
ADD COLUMN IF NOT EXISTS `division` VARCHAR(5) DEFAULT NULL 
AFTER `points`;

-- Add is_best_7 for secondary (to track best 7 subjects)
ALTER TABLE `exam_results` 
ADD COLUMN IF NOT EXISTS `is_best_7` BOOLEAN DEFAULT FALSE 
AFTER `division`;


-- ============================================
-- 8. SCHOOL SETTINGS ADDITIONS
-- ============================================
-- Add school type (primary, secondary, both)
ALTER TABLE `school_settings` 
ADD COLUMN IF NOT EXISTS `school_type` ENUM('primary', 'secondary', 'both') DEFAULT 'both' 
AFTER `school_name`;

-- Add enable_period_attendance for secondary
ALTER TABLE `school_settings` 
ADD COLUMN IF NOT EXISTS `enable_period_attendance` BOOLEAN DEFAULT FALSE 
AFTER `school_type`;

-- Add default_grading_system (primary or secondary)
ALTER TABLE `school_settings` 
ADD COLUMN IF NOT EXISTS `default_grading_system` ENUM('primary', 'secondary') DEFAULT 'primary' 
AFTER `enable_period_attendance`;


-- ============================================
-- 9. FEES STRUCTURE ADDITIONS (Different fees for primary/secondary)
-- ============================================
-- Add school_level to fee_structure
ALTER TABLE `fee_structure` 
ADD COLUMN IF NOT EXISTS `school_level` ENUM('primary', 'secondary') DEFAULT 'primary' 
AFTER `class`;


-- ============================================
-- 10. INSERT DEFAULT SUBJECTS (Primary School - TET Curriculum)
-- ============================================
INSERT INTO `subjects` (`subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`) VALUES
('PRI_MATH', 'Hisabati', 'primary', 'core', 1),
('PRI_ENG', 'Kiingereza', 'primary', 'core', 1),
('PRI_SWA', 'Kiswahili', 'primary', 'core', 1),
('PRI_SCI', 'Sayansi', 'primary', 'core', 1),
('PRI_SST', 'Maarifa ya Jamii', 'primary', 'core', 1),
('PRI_VOC', 'Stadi za Kazi', 'primary', 'core', 1),
('PRI_SPORTS', 'Haiba na Michezo', 'primary', 'core', 1),
('PRI_CIVIC', 'Uraia', 'primary', 'core', 1)
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);

-- ============================================
-- 11. INSERT DEFAULT SUBJECTS (Secondary School - TET Curriculum)
-- ============================================
INSERT INTO `subjects` (`subject_code`, `subject_name`, `school_level`, `category`, `is_compulsory`) VALUES
('SEC_MATH', 'Mathematics', 'secondary', 'core', 1),
('SEC_ENG', 'English', 'secondary', 'core', 1),
('SEC_KISW', 'Kiswahili', 'secondary', 'core', 1),
('SEC_BIO', 'Biology', 'secondary', 'science', 0),
('SEC_CHEM', 'Chemistry', 'secondary', 'science', 0),
('SEC_PHY', 'Physics', 'secondary', 'science', 0),
('SEC_HIST', 'History', 'secondary', 'arts', 0),
('SEC_GEO', 'Geography', 'secondary', 'arts', 0),
('SEC_CIV', 'Civics', 'secondary', 'arts', 0),
('SEC_COMM', 'Commerce', 'secondary', 'business', 0),
('SEC_BKEEP', 'Bookkeeping', 'secondary', 'business', 0),
('SEC_COMP', 'Computer Science', 'secondary', 'optional', 0)
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);

-- ============================================
-- 12. INSERT GRADING SYSTEM FOR SECONDARY (Points: A=1 to F=6)
-- ============================================
INSERT INTO `grading_system` (`grade`, `min_score`, `max_score`, `description`, `points`, `school_level`, `points_value`, `division`) VALUES
('A', 80, 100, 'Outstanding', 4.0, 'secondary', 1, 'I'),
('B', 70, 79, 'Very Good', 3.5, 'secondary', 2, 'I'),
('C', 60, 69, 'Good', 3.0, 'secondary', 3, 'II'),
('D', 50, 59, 'Satisfactory', 2.5, 'secondary', 4, 'III'),
('E', 40, 49, 'Pass', 2.0, 'secondary', 5, 'III'),
('F', 0, 39, 'Fail', 0.0, 'secondary', 6, 'IV')
ON DUPLICATE KEY UPDATE points_value = VALUES(points_value);

-- ============================================
-- 13. UPDATE EXISTING STUDENTS TO PRIMARY (Default)
-- ============================================
UPDATE `students` SET `school_level` = 'primary' WHERE `school_level` IS NULL;

-- ============================================
-- 14. VERIFICATION QUERIES (Run to check) - FIXED
-- ============================================
SELECT '✅ students table updated' AS Status, COUNT(*) AS Total FROM students WHERE school_level IS NOT NULL
UNION ALL
SELECT '✅ grading_system updated', COUNT(*) FROM grading_system WHERE school_level = 'secondary'
UNION ALL
SELECT '✅ subjects table created', COUNT(*) FROM subjects
UNION ALL
SELECT '✅ teacher_subjects table created', COUNT(*) FROM teacher_subjects;