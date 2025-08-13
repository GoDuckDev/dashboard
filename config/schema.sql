-- สร้างฐานข้อมูลและตาราง
CREATE DATABASE IF NOT EXISTS secure_login_system 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE secure_login_system;

-- ตารางผู้ใช้
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(32) NOT NULL,
    role_id INT NOT NULL DEFAULT 3,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    failed_attempts INT DEFAULT 0,
    last_failed_attempt TIMESTAMP NULL,
    account_locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- ตารางบทบาทและสิทธิ์
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสิทธิ์
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเชื่อม role กับ permission
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ตาราง session
CREATE TABLE user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ตาราง login log
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- เพิ่มข้อมูลบทบาทเริ่มต้น
INSERT INTO roles (role_name, description) VALUES
('admin', 'ผู้ดูแลระบบ มีสิทธิ์เต็ม'),
('manager', 'ผู้จัดการ มีสิทธิ์จัดการข้อมูล'),
('user', 'ผู้ใช้ทั่วไป มีสิทธิ์อ่านข้อมูล');

-- เพิ่มสิทธิ์เริ่มต้น
INSERT INTO permissions (permission_name, description) VALUES
('read_data', 'อ่านข้อมูล'),
('write_data', 'เขียนข้อมูล'),
('delete_data', 'ลบข้อมูล'),
('manage_users', 'จัดการผู้ใช้'),
('system_admin', 'ดูแลระบบ');

-- กำหนดสิทธิ์ให้บทบาท
INSERT INTO role_permissions (role_id, permission_id) VALUES
-- Admin ได้สิทธิ์ทุกอย่าง
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
-- Manager ได้สิทธิ์อ่าน เขียน และจัดการผู้ใช้
(2, 1), (2, 2), (2, 4),
-- User ได้เฉพาะสิทธิ์อ่าน
(3, 1);

-- เพิ่ม Foreign Key ให้ตาราง users
ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id);

-- สร้าง Index เพื่อประสิทธิภาพ
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_login_logs_user_id ON login_logs(user_id);
CREATE INDEX idx_login_logs_created ON login_logs(created_at);