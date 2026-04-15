-- =====================================================
-- Bradford Portal Database Schema
-- =====================================================
-- Updated: April 2026 - Added first_name/last_name, Bradford schools dataset
-- =====================================================

CREATE DATABASE IF NOT EXISTS bradford_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bradford_portal;

-- users table with encrypted sensitive data
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) DEFAULT NULL,
    last_name VARCHAR(255) DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,         -- kept for backward compat
    is_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    two_factor_secret VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- uploaded data entries
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    data LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- activity log for admin tracking
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- data sharing between users
CREATE TABLE shared_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    upload_id INT NOT NULL,
    shared_by_user_id INT NOT NULL,
    shared_with_user_id INT NOT NULL,
    permissions ENUM('view','edit') DEFAULT 'view',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by_user_id) REFERENCES users(id),
    FOREIGN KEY (shared_with_user_id) REFERENCES users(id),
    UNIQUE KEY unique_share (upload_id, shared_with_user_id)
) ENGINE=InnoDB;

-- data versioning for audit trails
CREATE TABLE upload_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    upload_id INT NOT NULL,
    user_id INT NOT NULL,
    version_number INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    data LONGTEXT,
    change_description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_version (upload_id, version_number)
) ENGINE=InnoDB;

-- rate limiting for login attempts
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    successful TINYINT(1) DEFAULT 0,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_email_time (email, attempt_time)
) ENGINE=InnoDB;

-- postcodes table
CREATE TABLE postcodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    postcode VARCHAR(10) NOT NULL,
    postcode_ns VARCHAR(10),
    lsoa11 VARCHAR(10),
    msoa11 VARCHAR(10),
    ward_code_ons_nspl VARCHAR(10),
    ward_name_ons_nspl VARCHAR(100),
    constituency_code_ons_nspl VARCHAR(10),
    constituency_name_ons_nspl VARCHAR(100),
    ccg VARCHAR(10),
    ward_code_current VARCHAR(10),
    ward_name_current VARCHAR(100),
    constituency_code_current VARCHAR(10),
    constituency_name_current VARCHAR(100),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    date_of_termination VARCHAR(10)
) ENGINE=InnoDB;

-- Bradford schools seed data (for demo)
CREATE TABLE bradford_schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    address VARCHAR(255),
    postcode VARCHAR(10),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    phone VARCHAR(30),
    ofsted_rating VARCHAR(50)
) ENGINE=InnoDB;

INSERT INTO bradford_schools (name, type, address, postcode, latitude, longitude, phone, ofsted_rating) VALUES
('Bradford Grammar School', 'Independent', 'Keighley Road, Bradford', 'BD9 4JP', 53.8090, -1.7610, '01274 542492', 'Outstanding'),
('Belle Vue Girls Academy', 'Academy', 'Thorn Lane, Bradford', 'BD9 5AB', 53.8020, -1.7680, '01274 490333', 'Good'),
('Bradford Academy', 'Academy', 'Teasdale Street, Bradford', 'BD4 7QB', 53.7810, -1.7250, '01274 089200', 'Good'),
('Dixons City Academy', 'Academy', 'Ripley Street, Bradford', 'BD5 7RR', 53.7860, -1.7500, '01274 400600', 'Outstanding'),
('Hanson Academy', 'Academy', 'Sutton Avenue, Bradford', 'BD7 4RL', 53.7900, -1.7850, '01274 731011', 'Requires Improvement'),
('Immanuel College', 'Academy', 'New Line, Bradford', 'BD10 0JX', 53.8300, -1.7220, '01274 620461', 'Good'),
('Beckfoot School', 'Academy', 'Wagon Lane, Bingley', 'BD16 1EE', 53.8450, -1.8310, '01274 771444', 'Good'),
('Ilkley Grammar School', 'Academy', 'Cowpasture Road, Ilkley', 'LS29 8TR', 53.9250, -1.8230, '01943 608424', 'Good'),
('Titus Salt School', 'Academy', 'Higher Coach Road, Baildon', 'BD17 5RH', 53.8510, -1.7690, '01274 582 212', 'Good'),
('Keighley College', 'College', 'Bradford Road, Keighley', 'BD21 4HK', 53.8680, -1.9040, '01535 618600', 'Good'),
('St Bede and St Joseph Catholic College', 'Catholic Academy', 'Ashwell Road, Bradford', 'BD7 1QH', 53.7960, -1.7780, '01274 501414', 'Good'),
('Saltaire Primary School', 'Primary', 'Holywell Ash Lane, Saltaire', 'BD18 4NN', 53.8377, -1.7909, '01274 585074', 'Outstanding'),
('Shipley CE Primary School', 'Primary (CE)', 'Kirkgate, Shipley', 'BD18 3EH', 53.8330, -1.7710, '01274 584056', 'Good'),
('Bingley Grammar School', 'Academy', 'Keighley Road, Bingley', 'BD16 2RS', 53.8510, -1.8380, '01274 551376', 'Good'),
('Whetley Primary School', 'Primary', 'Whetley Lane, Bradford', 'BD8 9HS', 53.8040, -1.7760, '01274 545408', 'Good');

