-- =====================================================
-- Admin Module Database Schema Updates
-- =====================================================
-- Run this script to add admin functionality to existing database
-- =====================================================

USE social_network;

-- Add admin and status columns to users table
ALTER TABLE users 
ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER password,
ADD COLUMN status ENUM('active', 'blocked', 'suspended') DEFAULT 'active' AFTER is_admin,
ADD COLUMN blocked_at TIMESTAMP NULL AFTER status,
ADD COLUMN blocked_reason TEXT NULL AFTER blocked_at;

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT(11),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin activity log table
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    admin_id INT(11) NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT(11),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'SocialNet', 'text', 'Name of the social network'),
('site_description', 'Connect with friends and share your moments', 'text', 'Site description for meta tags'),
('registration_enabled', '1', 'boolean', 'Allow new user registrations'),
('post_max_length', '5000', 'number', 'Maximum characters per post'),
('image_max_size', '10', 'number', 'Maximum image upload size in MB'),
('stories_enabled', '1', 'boolean', 'Enable stories feature'),
('stories_duration', '24', 'number', 'Story visibility duration in hours'),
('comments_enabled', '1', 'boolean', 'Enable comments on posts'),
('likes_enabled', '1', 'boolean', 'Enable likes on posts'),
('maintenance_mode', '0', 'boolean', 'Put site in maintenance mode'),
('max_posts_per_day', '50', 'number', 'Maximum posts per user per day'),
('require_email_verification', '0', 'boolean', 'Require email verification for new accounts'),
('allow_nfc_login', '1', 'boolean', 'Enable NFC login feature')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Create first admin user (update this with your actual user ID)
-- Change the user_id (1) to match your existing user account
UPDATE users SET is_admin = 1 WHERE id = 1;

-- Add indexes for performance
ALTER TABLE users ADD INDEX idx_status (status);
ALTER TABLE users ADD INDEX idx_is_admin (is_admin);

SELECT 'Admin module schema created successfully!' AS message;
