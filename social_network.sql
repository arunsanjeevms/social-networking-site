-- =====================================================
-- Social Network Database Schema with Dummy Data
-- =====================================================
-- Instructions:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Create a new database named 'social_network'
-- 3. Import this SQL file or run these queries
-- =====================================================

DROP DATABASE IF EXISTS social_network;
CREATE DATABASE social_network;
USE social_network;

-- =====================================================
-- TABLE STRUCTURES
-- =====================================================

-- Users Table
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posts Table
CREATE TABLE posts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Likes Table
CREATE TABLE likes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    post_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id),
    INDEX idx_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments Table
CREATE TABLE comments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    post_id INT(11) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stories Table (24-hour stories feature)
CREATE TABLE stories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    image VARCHAR(255) NOT NULL,
    caption VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookmarks Table
CREATE TABLE bookmarks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    post_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Followers Table
CREATE TABLE followers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    follower_id INT(11) NOT NULL,
    following_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower_id (follower_id),
    INDEX idx_following_id (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    from_user_id INT(11) NOT NULL,
    type ENUM('like', 'comment', 'follow', 'mention') NOT NULL,
    post_id INT(11),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reactions Table
CREATE TABLE reactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    post_id INT(11) NOT NULL,
    reaction VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (user_id, post_id),
    INDEX idx_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DUMMY DATA
-- =====================================================

-- Insert Users (password for all: 'password123')
INSERT INTO users (username, email, password, bio, created_at) VALUES 
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tech enthusiast | Developer | Coffee lover ☕', '2025-11-01 08:30:00'),
('sarah_smith', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Travel blogger 🌍 | Photography | Adventure seeker', '2025-11-02 10:15:00'),
('mike_johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fitness coach 💪 | Healthy living | Motivational speaker', '2025-11-03 14:20:00'),
('emma_wilson', 'emma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Artist 🎨 | Digital designer | Creative mind', '2025-11-04 09:45:00'),
('david_brown', 'david@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Music producer 🎵 | DJ | Electronic music enthusiast', '2025-11-05 16:30:00'),
('lisa_garcia', 'lisa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Food blogger 🍕 | Chef | Recipe creator', '2025-11-06 11:00:00'),
('alex_martinez', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Software engineer | AI researcher | Tech writer', '2025-11-07 13:20:00'),
('sophia_lee', 'sophia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fashion designer 👗 | Style influencer | Trendsetter', '2025-11-08 15:45:00'),
('ryan_taylor', 'ryan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gaming streamer 🎮 | Content creator | Esports fan', '2025-11-09 18:10:00'),
('olivia_anderson', 'olivia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Environmental activist 🌱 | Sustainability advocate', '2025-11-10 12:30:00');

-- Insert Posts
INSERT INTO posts (user_id, content, created_at) VALUES 
(1, 'Just launched my new portfolio website! Check it out and let me know what you think 🚀', '2025-11-15 09:00:00'),
(1, 'Working on an exciting new project using React and Node.js. The possibilities are endless! #coding #webdev', '2025-11-14 14:30:00'),
(2, 'Exploring the beautiful mountains of Colorado! The views are absolutely breathtaking 🏔️ #travel #nature', '2025-11-15 11:20:00'),
(2, 'Best coffee spots in Seattle - a thread ☕ Have you been to any of these amazing places?', '2025-11-13 16:45:00'),
(3, '30-minute morning workout routine that changed my life! Drop a 💪 if you want the full routine', '2025-11-15 07:30:00'),
(3, 'Reminder: Your only limit is you. Push harder today than you did yesterday! #fitness #motivation', '2025-11-12 06:15:00'),
(4, 'New digital art piece completed! This one took me 15 hours but I\'m so proud of the result 🎨✨', '2025-11-15 13:40:00'),
(4, 'Tips for beginner digital artists: Start with the basics, practice daily, and don\'t be afraid to experiment!', '2025-11-11 10:20:00'),
(5, 'Live DJ set tonight at 8 PM! Who\'s ready to dance? 🎵🎧 Drop a 🔥 if you\'re joining!', '2025-11-15 17:00:00'),
(5, 'Just released my new track on Spotify! Link in bio. Let me know what you think! #music #producer', '2025-11-10 19:30:00'),
(6, 'Recipe of the day: Homemade pizza with fresh basil and mozzarella 🍕 Who wants the recipe?', '2025-11-15 12:15:00'),
(6, 'The secret to perfect pasta is in the sauce! Here are my top 5 sauce recipes 👨‍🍳', '2025-11-09 14:00:00'),
(7, 'Exploring the future of AI and machine learning. The advancements this year have been incredible! 🤖', '2025-11-15 10:45:00'),
(7, 'Just wrote a blog post about clean code practices. Check it out! #programming #cleancode', '2025-11-08 15:30:00'),
(8, 'Fashion tip: Mix vintage with modern for a unique style! Here\'s my latest outfit 👗✨', '2025-11-15 14:20:00'),
(8, 'Attending Fashion Week next month! So excited to see the new collections 😍', '2025-11-07 16:45:00'),
(9, 'Just hit Diamond rank in ranked! The grind was real but totally worth it 🎮🏆', '2025-11-15 20:00:00'),
(9, 'Streaming tonight at 9 PM! Come hang out and let\'s have some fun! #gaming #twitch', '2025-11-06 18:20:00'),
(10, 'Small changes make big impacts! Here are 10 ways to live more sustainably 🌱♻️', '2025-11-15 08:30:00'),
(10, 'Joined a beach cleanup today! Together we collected over 200kg of trash 🌊 #environment', '2025-11-05 13:00:00');

-- Insert Likes (users liking various posts)
INSERT INTO likes (user_id, post_id, created_at) VALUES 
(2, 1, '2025-11-15 09:15:00'), (3, 1, '2025-11-15 09:30:00'), (4, 1, '2025-11-15 10:00:00'),
(1, 3, '2025-11-15 11:25:00'), (3, 3, '2025-11-15 11:40:00'), (5, 3, '2025-11-15 12:00:00'),
(1, 5, '2025-11-15 07:45:00'), (2, 5, '2025-11-15 08:00:00'), (4, 5, '2025-11-15 08:15:00'),
(2, 7, '2025-11-15 13:50:00'), (3, 7, '2025-11-15 14:00:00'), (1, 7, '2025-11-15 14:30:00'),
(1, 9, '2025-11-15 17:10:00'), (4, 9, '2025-11-15 17:20:00'), (6, 9, '2025-11-15 17:45:00'),
(2, 11, '2025-11-15 12:30:00'), (4, 11, '2025-11-15 12:45:00'), (5, 11, '2025-11-15 13:00:00'),
(3, 13, '2025-11-15 11:00:00'), (5, 13, '2025-11-15 11:15:00'), (6, 13, '2025-11-15 11:30:00'),
(1, 15, '2025-11-15 14:30:00'), (2, 15, '2025-11-15 14:45:00'), (3, 15, '2025-11-15 15:00:00'),
(2, 17, '2025-11-15 20:15:00'), (5, 17, '2025-11-15 20:30:00'), (7, 17, '2025-11-15 20:45:00'),
(3, 19, '2025-11-15 08:45:00'), (6, 19, '2025-11-15 09:00:00'), (8, 19, '2025-11-15 09:15:00');

-- Insert Comments
INSERT INTO comments (user_id, post_id, comment, created_at) VALUES 
(2, 1, 'Looks amazing! Great work 🎉', '2025-11-15 09:20:00'),
(3, 1, 'The design is so clean! What tech stack did you use?', '2025-11-15 09:35:00'),
(1, 3, 'Wow! This is on my bucket list. How long did you stay there?', '2025-11-15 11:30:00'),
(4, 3, 'The colors in this photo are stunning! 📸', '2025-11-15 12:10:00'),
(2, 5, 'Yes please! Share the routine! 💪', '2025-11-15 08:05:00'),
(4, 5, 'This is exactly what I needed! Thanks for the motivation!', '2025-11-15 08:20:00'),
(3, 7, 'This is incredible! How long have you been doing digital art?', '2025-11-15 14:05:00'),
(5, 7, 'The detail is amazing! Do you do commissions?', '2025-11-15 14:35:00'),
(4, 9, 'Can\'t wait! Going to be epic! 🔥🔥🔥', '2025-11-15 17:15:00'),
(6, 9, 'Count me in! See you there! 🎵', '2025-11-15 17:50:00'),
(4, 11, 'Yes! I need this recipe in my life! 🍕', '2025-11-15 12:35:00'),
(5, 11, 'This looks delicious! What kind of flour do you use?', '2025-11-15 13:05:00'),
(5, 13, 'AI is fascinating! Have you worked with GPT models?', '2025-11-15 11:20:00'),
(6, 13, 'Great insights! Following for more tech content 🤖', '2025-11-15 11:35:00'),
(2, 15, 'Love this style! Where did you get that jacket?', '2025-11-15 14:35:00'),
(3, 15, 'You always have the best outfits! 😍', '2025-11-15 15:05:00'),
(5, 17, 'Congrats! That\'s a huge achievement! 🏆', '2025-11-15 20:20:00'),
(7, 17, 'The grind paid off! What hero did you main?', '2025-11-15 20:50:00'),
(6, 19, 'This is so important! Thank you for sharing 🌱', '2025-11-15 08:50:00'),
(8, 19, 'Small steps lead to big changes! Love this 💚', '2025-11-15 09:20:00');

-- Insert Followers (who follows whom)
INSERT INTO followers (follower_id, following_id, created_at) VALUES 
(1, 2, '2025-11-02 10:30:00'), (1, 3, '2025-11-03 14:45:00'), (1, 4, '2025-11-04 10:00:00'),
(2, 1, '2025-11-01 09:00:00'), (2, 4, '2025-11-04 10:30:00'), (2, 6, '2025-11-06 11:30:00'),
(3, 1, '2025-11-01 09:15:00'), (3, 2, '2025-11-02 11:00:00'), (3, 5, '2025-11-05 17:00:00'),
(4, 1, '2025-11-01 09:30:00'), (4, 2, '2025-11-02 11:15:00'), (4, 8, '2025-11-08 16:00:00'),
(5, 1, '2025-11-01 09:45:00'), (5, 3, '2025-11-03 15:00:00'), (5, 9, '2025-11-09 18:30:00'),
(6, 2, '2025-11-02 11:30:00'), (6, 4, '2025-11-04 10:45:00'), (6, 10, '2025-11-10 13:00:00'),
(7, 1, '2025-11-01 10:00:00'), (7, 3, '2025-11-03 15:15:00'), (7, 5, '2025-11-05 17:15:00'),
(8, 2, '2025-11-02 12:00:00'), (8, 4, '2025-11-04 11:00:00'), (8, 6, '2025-11-06 12:00:00'),
(9, 1, '2025-11-01 10:15:00'), (9, 5, '2025-11-05 17:30:00'), (9, 7, '2025-11-07 14:00:00'),
(10, 2, '2025-11-02 12:15:00'), (10, 6, '2025-11-06 12:15:00'), (10, 8, '2025-11-08 16:30:00');

-- Insert Bookmarks
INSERT INTO bookmarks (user_id, post_id, created_at) VALUES 
(1, 3, '2025-11-15 11:45:00'), (1, 7, '2025-11-15 14:10:00'), (1, 13, '2025-11-15 11:10:00'),
(2, 1, '2025-11-15 09:25:00'), (2, 5, '2025-11-15 08:10:00'), (2, 11, '2025-11-15 12:40:00'),
(3, 7, '2025-11-15 14:15:00'), (3, 13, '2025-11-15 11:25:00'), (3, 19, '2025-11-15 09:05:00'),
(4, 1, '2025-11-15 09:40:00'), (4, 9, '2025-11-15 17:25:00'), (4, 15, '2025-11-15 14:40:00'),
(5, 3, '2025-11-15 12:05:00'), (5, 11, '2025-11-15 13:10:00'), (5, 17, '2025-11-15 20:25:00');

-- Insert Stories (recent stories from last few hours)
INSERT INTO stories (user_id, image, caption, created_at) VALUES 
(1, 'story_1_1731748800.jpg', 'Working late tonight 💻', '2025-11-16 02:00:00'),
(2, 'story_2_1731752400.jpg', 'Sunset views from my hotel 🌅', '2025-11-16 03:00:00'),
(3, 'story_3_1731756000.jpg', 'Morning workout done! ✅', '2025-11-16 04:00:00'),
(4, 'story_4_1731759600.jpg', 'New sketch in progress 🎨', '2025-11-16 05:00:00'),
(5, 'story_5_1731763200.jpg', 'Studio vibes 🎧', '2025-11-16 06:00:00');

-- Insert Notifications
INSERT INTO notifications (user_id, from_user_id, type, post_id, message, is_read, created_at) VALUES 
(1, 2, 'like', 1, 'sarah_smith liked your post', 0, '2025-11-15 09:15:00'),
(1, 3, 'comment', 1, 'mike_johnson commented on your post', 0, '2025-11-15 09:35:00'),
(2, 1, 'follow', NULL, 'john_doe started following you', 0, '2025-11-15 11:30:00'),
(3, 2, 'like', 5, 'sarah_smith liked your post', 1, '2025-11-15 08:00:00'),
(4, 3, 'comment', 7, 'mike_johnson commented on your post', 0, '2025-11-15 14:05:00'),
(5, 4, 'like', 9, 'emma_wilson liked your post', 1, '2025-11-15 17:20:00'),
(6, 4, 'comment', 11, 'emma_wilson commented on your post', 0, '2025-11-15 12:35:00'),
(7, 5, 'like', 13, 'david_brown liked your post', 1, '2025-11-15 11:15:00'),
(8, 2, 'comment', 15, 'sarah_smith commented on your post', 0, '2025-11-15 14:35:00'),
(9, 5, 'like', 17, 'david_brown liked your post', 1, '2025-11-15 20:30:00');

-- Insert Reactions
INSERT INTO reactions (user_id, post_id, reaction, created_at) VALUES 
(1, 3, '❤️', '2025-11-15 11:25:00'), (2, 1, '👍', '2025-11-15 09:15:00'),
(3, 5, '💪', '2025-11-15 08:00:00'), (4, 7, '😮', '2025-11-15 14:00:00'),
(5, 9, '🔥', '2025-11-15 17:20:00'), (6, 11, '😂', '2025-11-15 12:45:00'),
(7, 13, '👍', '2025-11-15 11:15:00'), (8, 15, '❤️', '2025-11-15 14:45:00'),
(9, 17, '🔥', '2025-11-15 20:30:00'), (10, 19, '❤️', '2025-11-15 09:00:00');

-- =====================================================
-- VIEWS FOR STATISTICS
-- =====================================================

-- View: User Statistics
CREATE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    COUNT(DISTINCT p.id) as total_posts,
    COUNT(DISTINCT l.id) as total_likes_received,
    COUNT(DISTINCT c.id) as total_comments_received,
    COUNT(DISTINCT f1.id) as followers_count,
    COUNT(DISTINCT f2.id) as following_count
FROM users u
LEFT JOIN posts p ON u.id = p.user_id
LEFT JOIN likes l ON p.id = l.post_id
LEFT JOIN comments c ON p.id = c.post_id
LEFT JOIN followers f1 ON u.id = f1.following_id
LEFT JOIN followers f2 ON u.id = f2.follower_id
GROUP BY u.id, u.username;

-- View: Popular Posts (most liked and commented)
CREATE VIEW popular_posts AS
SELECT 
    p.id,
    p.content,
    u.username,
    COUNT(DISTINCT l.id) as like_count,
    COUNT(DISTINCT c.id) as comment_count,
    (COUNT(DISTINCT l.id) + COUNT(DISTINCT c.id) * 2) as engagement_score
FROM posts p
JOIN users u ON p.user_id = u.id
LEFT JOIN likes l ON p.id = l.post_id
LEFT JOIN comments c ON p.id = c.post_id
GROUP BY p.id, p.content, u.username
ORDER BY engagement_score DESC;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX idx_posts_user_created ON posts(user_id, created_at DESC);
CREATE INDEX idx_likes_post_user ON likes(post_id, user_id);
CREATE INDEX idx_comments_post_created ON comments(post_id, created_at DESC);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at DESC);

-- =====================================================
-- COMPLETE!
-- =====================================================
-- Database setup complete with 10 users, 20 posts, likes, comments, 
-- followers, bookmarks, stories, notifications, and reactions!
-- Default password for all users: 'password123'
-- =====================================================
