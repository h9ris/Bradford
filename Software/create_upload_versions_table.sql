-- Create upload_versions table for version tracking
-- Run this if you want to enable data versioning

CREATE TABLE IF NOT EXISTS upload_versions (
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
