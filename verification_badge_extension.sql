-- ============================================
-- Verification Badge System
-- Adds verification status to users
-- ============================================

-- Add verification column to users table
ALTER TABLE users 
ADD COLUMN is_verified BOOLEAN DEFAULT FALSE AFTER is_admin,
ADD COLUMN verified_at DATETIME NULL AFTER is_verified,
ADD COLUMN verified_by INT NULL AFTER verified_at,
ADD INDEX idx_verified (is_verified);

-- Add foreign key for verified_by (admin who verified the user)
ALTER TABLE users
ADD CONSTRAINT fk_verified_by 
FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;

-- Create verification log table for tracking
CREATE TABLE IF NOT EXISTS verification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    action ENUM('verified', 'unverified') NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_admin (admin_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Success message
SELECT 'Verification badge system installed successfully!' as message;
