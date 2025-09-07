-- Clean start
DROP DATABASE IF EXISTS civitrack;
CREATE DATABASE civitrack;
USE civitrack;

-- USERS (login + roles)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- store hashes
    role ENUM('citizen','police','admin') NOT NULL
) ENGINE=InnoDB;

-- CITIZEN (binds to a user)
CREATE TABLE citizen (
    NID VARCHAR(50) PRIMARY KEY,
    user_id INT UNIQUE,                          -- 1:1 login link (nullable until registration finishes)
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    address VARCHAR(255),
    phone VARCHAR(20),
    gender ENUM('male','female','other') DEFAULT NULL,
    reg_date DATE NOT NULL,
    is_deceased BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_citizen_user
      FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_citizen_last_name ON citizen(last_name);
CREATE INDEX idx_citizen_address ON citizen(address);

-- POLICE
CREATE TABLE police (
    police_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    CONSTRAINT fk_police_user
      FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ADMIN
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    CONSTRAINT fk_admin_user
      FOREIGN KEY (user_id) REFERENCES users(user_id)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- TAX (immutable by citizen)
CREATE TABLE tax_record (
    tax_record_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    yearly_income DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    UNIQUE KEY uq_tax_year (NID, year),
    CONSTRAINT fk_tax_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- EMPLOYMENT (immutable by citizen)
CREATE TABLE employment_record (
    employment_record_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    job_title VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    salary DECIMAL(10,2) DEFAULT NULL,
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_emp_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- CRIMINAL (police can update, not delete)
CREATE TABLE criminal_record (
    criminal_record_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL,
    case_type VARCHAR(100) NOT NULL,
    date_of_offence DATE NOT NULL,
    case_status ENUM('pending','under_investigation','closed','convicted','acquitted') NOT NULL DEFAULT 'pending',
    penalty VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    updated_date DATE DEFAULT NULL,
    CONSTRAINT fk_criminal_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- PASSPORT (1:1)
CREATE TABLE passport (
    passport_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL UNIQUE,
    passport_number VARCHAR(50) UNIQUE,
    issue_date DATE,
    expiry_date DATE,
    status ENUM('none','active','revoked','expired') NOT NULL DEFAULT 'none',
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_passport_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- DRIVING LICENSE (1:1)
CREATE TABLE driving_license (
    license_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL UNIQUE,
    license_number VARCHAR(50) UNIQUE,
    issue_date DATE,
    expiry_date DATE,
    status ENUM('none','active','suspended','expired') NOT NULL DEFAULT 'none',
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_license_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- DECEASED (archive)
CREATE TABLE deceased_citizen (
    deceased_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    address VARCHAR(255),
    phone VARCHAR(20),
    gender ENUM('male','female','other') DEFAULT NULL,
    reg_date DATE NOT NULL,
    death_certificate_number VARCHAR(100) NOT NULL UNIQUE,
    death_date DATE NOT NULL,
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE)
) ENGINE=InnoDB;

-- APPLICATIONS
CREATE TABLE passport_application (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL,
    application_date DATE NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    verified_by_police_id INT NULL,
    verification_date DATE NULL,
    rejection_reason TEXT NULL,
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_pa_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pa_police
      FOREIGN KEY (verified_by_police_id) REFERENCES police(police_id)
      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE license_application (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    NID VARCHAR(50) NOT NULL,
    application_date DATE NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    verified_by_police_id INT NULL,
    verification_date DATE NULL,
    rejection_reason TEXT NULL,
    created_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_la_citizen
      FOREIGN KEY (NID) REFERENCES citizen(NID)
      ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_la_police
      FOREIGN KEY (verified_by_police_id) REFERENCES police(police_id)
      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('police1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police'),
('citizen1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen');

INSERT INTO admin (user_id) VALUES (1);
INSERT INTO police (user_id, employee_id) VALUES (2, 'POL001');
INSERT INTO citizen (NID, user_id, first_name, last_name, email, address, phone, gender, reg_date) VALUES 
('1234567890', 3, 'John', 'Doe', 'john.doe@email.com', '123 Main St, City', '555-0123', 'male', '2023-01-15');

-- Sample tax records
INSERT INTO tax_record (NID, year, yearly_income, tax_amount, payment_status) VALUES 
('1234567890', 2023, 50000.00, 5000.00, 'paid'),
('1234567890', 2022, 45000.00, 4500.00, 'paid');

-- Sample employment records
INSERT INTO employment_record (NID, company_name, job_title, start_date, end_date, salary) VALUES 
('1234567890', 'Tech Corp', 'Software Developer', '2022-01-01', NULL, 50000.00),
('1234567890', 'Startup Inc', 'Junior Developer', '2021-06-01', '2021-12-31', 35000.00);

-- Sample criminal records
INSERT INTO criminal_record (NID, case_type, date_of_offence, case_status, penalty, description) VALUES 
('1234567890', 'Traffic Violation', '2023-03-15', 'closed', 'Fine $100', 'Speeding ticket');

-- Sample passport application
INSERT INTO passport_application (NID, application_date, status) VALUES 
('1234567890', '2023-12-01', 'pending');
