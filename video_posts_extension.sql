-- =====================================================
-- Video Posts Extension
-- =====================================================
-- Adds video upload support to posts
-- =====================================================

USE social_network;

-- Add video columns to posts table
ALTER TABLE posts 
ADD COLUMN video VARCHAR(255) NULL AFTER image,
ADD COLUMN media_type ENUM('none', 'image', 'video') DEFAULT 'none' AFTER video;

-- Update existing posts with images to set media_type
UPDATE posts SET media_type = 'image' WHERE image IS NOT NULL AND image != '';

-- Add index for media type
ALTER TABLE posts ADD INDEX idx_media_type (media_type);

SELECT 'Video posts extension applied successfully!' AS message;
