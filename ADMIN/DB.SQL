-- ============================================
-- SUPER ADMIN TABLES FOR SSMS TANZANIA
-- ============================================

-- 1. Extend users table (already exists)
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_ip VARCHAR(45) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL;

-- 2. School settings
CREATE TABLE IF NOT EXISTS school_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_name VARCHAR(200) NOT NULL DEFAULT 'SSMS Tanzania',
    school_logo VARCHAR(255) NULL,
    school_address TEXT,
    school_phone VARCHAR(50),
    school_email VARCHAR(100),
    school_website VARCHAR(100),
    tin_number VARCHAR(50),
    registration_number VARCHAR(50),
    motto VARCHAR(255),
    academic_year VARCHAR(20) DEFAULT '2025',
    current_term ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1',
    term_start_date DATE,
    term_end_date DATE,
    currency VARCHAR(10) DEFAULT 'TZS',
    timezone VARCHAR(50) DEFAULT 'Africa/Dar_es_Salaam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Grading system
CREATE TABLE IF NOT EXISTS grading_system (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grade VARCHAR(2) NOT NULL,
    min_score INT NOT NULL,
    max_score INT NOT NULL,
    description VARCHAR(100),
    points DECIMAL(3,1) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Fee structure (per class)
CREATE TABLE IF NOT EXISTS fee_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class VARCHAR(50) NOT NULL,
    fee_type VARCHAR(50) NOT NULL, -- Tuition, Transport, Uniforms, etc.
    amount DECIMAL(15,2) NOT NULL,
    term ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. School calendar
CREATE TABLE IF NOT EXISTS school_calendar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_type ENUM('holiday', 'exam', 'event', 'meeting', 'other') DEFAULT 'event',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 6. System logs
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 7. Backup records
CREATE TABLE IF NOT EXISTS backup_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    file_size VARCHAR(50),
    backup_type ENUM('auto', 'manual') DEFAULT 'manual',
    status ENUM('success', 'failed') DEFAULT 'success',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 8. Gallery (School Events)
CREATE TABLE IF NOT EXISTS gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(100),
    is_public BOOLEAN DEFAULT TRUE,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default grading system (Tanzania NECTA style)
INSERT INTO grading_system (grade, min_score, max_score, description, points) VALUES
('A', 80, 100, 'Outstanding', 4.0),
('B', 70, 79, 'Very Good', 3.5),
('C', 60, 69, 'Good', 3.0),
('D', 50, 59, 'Satisfactory', 2.5),
('E', 40, 49, 'Pass', 2.0),
('F', 0, 39, 'Fail', 0);

-- Insert default school settings
INSERT INTO school_settings (school_name, school_address, school_phone, school_email, motto, academic_year, current_term) VALUES
('SSMS Tanzania', 'Dar es Salaam, Tanzania', '+255 123 456 789', 'info@ssms.co.tz', 'Quality Education for All', '2025', 'Term 1');


-- .........................................................ADDED
-- ============================================
-- SUPER ADMIN TABLES FOR SSMS TANZANIA
-- ============================================

-- 1. Extend users table (already exists)
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_ip VARCHAR(45) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL;

-- 2. School settings
CREATE TABLE IF NOT EXISTS school_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_name VARCHAR(200) NOT NULL DEFAULT 'SSMS Tanzania',
    school_logo VARCHAR(255) NULL,
    school_address TEXT,
    school_phone VARCHAR(50),
    school_email VARCHAR(100),
    school_website VARCHAR(100),
    tin_number VARCHAR(50),
    registration_number VARCHAR(50),
    motto VARCHAR(255),
    academic_year VARCHAR(20) DEFAULT '2025',
    current_term ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1',
    term_start_date DATE,
    term_end_date DATE,
    currency VARCHAR(10) DEFAULT 'TZS',
    timezone VARCHAR(50) DEFAULT 'Africa/Dar_es_Salaam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Grading system
CREATE TABLE IF NOT EXISTS grading_system (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grade VARCHAR(2) NOT NULL,
    min_score INT NOT NULL,
    max_score INT NOT NULL,
    description VARCHAR(100),
    points DECIMAL(3,1) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Fee structure (per class)
CREATE TABLE IF NOT EXISTS fee_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class VARCHAR(50) NOT NULL,
    fee_type VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    term ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. School calendar
CREATE TABLE IF NOT EXISTS school_calendar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_type ENUM('holiday', 'exam', 'event', 'meeting', 'other') DEFAULT 'event',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 6. System logs
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 7. Backup records
CREATE TABLE IF NOT EXISTS backup_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    file_size VARCHAR(50),
    backup_type ENUM('auto', 'manual') DEFAULT 'manual',
    status ENUM('success', 'failed') DEFAULT 'success',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 8. Gallery (School Events)
CREATE TABLE IF NOT EXISTS gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(100),
    is_public BOOLEAN DEFAULT TRUE,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default grading system (Tanzania NECTA style)
INSERT INTO grading_system (grade, min_score, max_score, description, points) VALUES
('A', 80, 100, 'Outstanding', 4.0),
('B', 70, 79, 'Very Good', 3.5),
('C', 60, 69, 'Good', 3.0),
('D', 50, 59, 'Satisfactory', 2.5),
('E', 40, 49, 'Pass', 2.0),
('F', 0, 39, 'Fail', 0);

-- Insert default school settings
INSERT INTO school_settings (school_name, school_address, school_phone, school_email, motto, academic_year, current_term) VALUES
('SSMS Tanzania', 'Dar es Salaam, Tanzania', '+255 123 456 789', 'info@ssms.co.tz', 'Quality Education for All', '2025', 'Term 1');