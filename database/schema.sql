-- LBSCEK Hostel Management System - Database Schema
-- Ladies' Hostel (LH LBSCEK): 278 students, 8 staff, 141 rooms

CREATE DATABASE IF NOT EXISTS hostel_lbscek;
USE hostel_lbscek;

-- Hostel structure
CREATE TABLE hostels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('men', 'ladies') NOT NULL,
    total_rooms INT NOT NULL DEFAULT 141,
    capacity INT NOT NULL DEFAULT 278,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hostel_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    capacity INT NOT NULL DEFAULT 2,
    current_occupancy INT DEFAULT 0,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    FOREIGN KEY (hostel_id) REFERENCES hostels(id),
    UNIQUE KEY (hostel_id, room_number)
);

-- Staff members
CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hostel_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('warden', 'caretaker', 'mess_manager', 'security', 'admin') NOT NULL,
    phone VARCHAR(20),
    face_encoding_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id)
);

-- Students
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hostel_id INT NOT NULL,
    room_id INT,
    student_id VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    department VARCHAR(100),
    year INT,
    face_encoding_id VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);
-- Face encodings for recognition
CREATE TABLE face_encodings (
    id VARCHAR(50) PRIMARY KEY,
    user_type ENUM('student', 'staff') NOT NULL,
    user_id INT NOT NULL,
    encoding_data LONGTEXT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hostel entry/exit logs
CREATE TABLE entry_exit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    type ENUM('entry', 'exit') NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    method ENUM('face', 'manual') DEFAULT 'face',
    verified BOOLEAN DEFAULT TRUE,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id),
    INDEX idx_student_date (student_id, recorded_at)
);

CREATE TABLE mess_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner') NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_date DATE GENERATED ALWAYS AS (DATE(marked_at)) STORED,
    method ENUM('face', 'manual') DEFAULT 'face',
    verified BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY unique_meal (student_id, meal_type, attendance_date),
    INDEX idx_student_meal (student_id, marked_at)
);

-- Fee structure
CREATE TABLE fee_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hostel_id INT NOT NULL,
    fee_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    period ENUM('monthly', 'quarterly', 'semester', 'annual') DEFAULT 'monthly',
    effective_from DATE,
    effective_to DATE,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id)
);

-- Mess Cut Requests
CREATE TABLE IF NOT EXISTS mess_cut_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Monthly Bills (Payment Tracking)
CREATE TABLE IF NOT EXISTS monthly_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bill_month VARCHAR(7) NOT NULL,
    hostel_fee DECIMAL(10,2) DEFAULT 780.00,
    establishment_fee DECIMAL(10,2) DEFAULT 850.00,
    mess_fee DECIMAL(10,2) NOT NULL,
    current_bill DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(255) NULL,
    published_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    fine DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'submitted', 'confirmed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bill (student_id, bill_month),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Mess Purchases (Expenses)
CREATE TABLE IF NOT EXISTS mess_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category ENUM('provisions', 'vegetables', 'meat', 'fuel', 'others') DEFAULT 'provisions',
    quantity DECIMAL(10,2),
    unit VARCHAR(20),
    total_price DECIMAL(10,2) NOT NULL,
    purchase_date DATE NOT NULL,
    invoice_no VARCHAR(100),
    added_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL
);

-- Fee payments
CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('online', 'upi', 'card', 'cash', 'bank_transfer') NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structure(id),
    INDEX idx_student_payment (student_id, payment_date)
);

-- Insert default hostel (Ladies' Hostel LBSCEK)
INSERT INTO hostels (name, type, total_rooms, capacity, address) VALUES
('Ladies'' Hostel LBSCEK', 'ladies', 141, 278, 'LBS College of Engineering, Kasaragod');

-- Insert sample fee structure
INSERT INTO fee_structure (hostel_id, fee_type, amount, period, effective_from, is_active) VALUES
(1, 'Hostel Fee', 5000.00, 'monthly', CURDATE(), TRUE),
(1, 'Mess Fee', 3500.00, 'monthly', CURDATE(), TRUE);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
CREATE TABLE IF NOT EXISTS mess_secretaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-----my change-----INSERT INTO mess_secretaries (name, email, password_hash) 

-----
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
CREATE TABLE IF NOT EXISTS mess_secretaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
