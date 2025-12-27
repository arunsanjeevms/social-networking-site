-- =====================================================
-- Admin Post Management Extension
-- =====================================================
-- Adds post management capabilities for admins
-- =====================================================

USE social_network;

-- Add deleted tracking to posts table
ALTER TABLE posts 
ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER image,
ADD COLUMN deleted_by INT(11) NULL AFTER is_deleted,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER deleted_by,
ADD COLUMN delete_reason TEXT NULL AFTER deleted_at,
ADD INDEX idx_is_deleted (is_deleted);

-- Add foreign key for deleted_by
ALTER TABLE posts 
ADD FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;

SELECT 'Admin post management extension applied successfully!' AS message;
