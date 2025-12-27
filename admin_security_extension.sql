-- =====================================================
-- Admin Security Enhancement Extension
-- =====================================================
-- Adds advanced security features to admin module
-- =====================================================

USE social_network;

-- Create login attempts tracking table
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(100),
    INDEX idx_ip_address (ip_address),
    INDEX idx_username (username),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin sessions table for enhanced tracking
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    admin_id INT(11) NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    logout_time TIMESTAMP NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_session_token (session_token),
    INDEX idx_is_active (is_active),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create security alerts table
CREATE TABLE IF NOT EXISTS security_alerts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('failed_login', 'suspicious_activity', 'privilege_escalation', 'mass_deletion', 'unusual_location', 'session_hijack') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    user_id INT(11),
    ip_address VARCHAR(45),
    description TEXT NOT NULL,
    details JSON,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_by INT(11),
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add security columns to users table
ALTER TABLE users 
ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER blocked_reason,
ADD COLUMN last_login_time TIMESTAMP NULL AFTER last_login_ip,
ADD COLUMN failed_login_attempts INT(11) DEFAULT 0 AFTER last_login_time,
ADD COLUMN account_locked_until TIMESTAMP NULL AFTER failed_login_attempts,
ADD COLUMN require_password_change TINYINT(1) DEFAULT 0 AFTER account_locked_until;

-- Add security settings to system_settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('max_login_attempts', '5', 'number', 'Maximum failed login attempts before account lock'),
('account_lockout_duration', '30', 'number', 'Account lockout duration in minutes'),
('session_timeout', '60', 'number', 'Admin session timeout in minutes'),
('password_min_length', '8', 'number', 'Minimum password length'),
('require_strong_password', '1', 'boolean', 'Require strong passwords (uppercase, lowercase, number, special char)'),
('enable_ip_whitelist', '0', 'boolean', 'Enable IP whitelist for admin access'),
('admin_ip_whitelist', '', 'text', 'Comma-separated list of allowed admin IP addresses'),
('enable_login_notifications', '1', 'boolean', 'Send email notifications for admin logins'),
('log_retention_days', '90', 'number', 'Number of days to retain security logs'),
('enable_rate_limiting', '1', 'boolean', 'Enable rate limiting for login attempts')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Create IP whitelist table
CREATE TABLE IF NOT EXISTS admin_ip_whitelist (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    description VARCHAR(255),
    added_by INT(11),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ip_address (ip_address),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Admin security enhancement extension applied successfully!' AS message;
