<?php
/**
 * Notification Helper Functions
 * Create and manage user notifications
 */

require_once 'database.php';

/**
 * Create a notification
 * @param int $user_id - User who will receive the notification
 * @param int $from_user_id - User who triggered the notification
 * @param string $type - Type: 'like', 'comment', 'follow', 'mention'
 * @param int $post_id - Related post ID (optional)
 * @param string $message - Notification message
 */
function create_notification($user_id, $from_user_id, $type, $post_id = null, $message = '') {
    global $conn;
    
    // Don't notify yourself
    if ($user_id == $from_user_id) {
        return false;
    }
    
    // Check if similar notification already exists (within last hour)
    $stmt = $conn->prepare("SELECT id FROM notifications 
                            WHERE user_id = ? 
                            AND from_user_id = ? 
                            AND type = ? 
                            AND post_id <=> ?
                            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                            LIMIT 1");
    $stmt->bind_param("iisi", $user_id, $from_user_id, $type, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return false; // Don't create duplicate notification
    }
    $stmt->close();
    
    // Create notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, from_user_id, type, post_id, message) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $user_id, $from_user_id, $type, $post_id, $message);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Get user notifications
 * @param int $user_id - User ID
 * @param int $limit - Number of notifications to fetch
 * @param bool $unread_only - Fetch only unread notifications
 */
function get_notifications($user_id, $limit = 20, $unread_only = false) {
    global $conn;
    
    $where = $unread_only ? "AND n.is_read = 0" : "";
    
    $query = "SELECT n.*, 
              u.username as from_username, 
              u.profile_image as from_profile_image,
              p.content as post_content,
              p.user_id as post_owner_id
              FROM notifications n
              JOIN users u ON n.from_user_id = u.id
              LEFT JOIN posts p ON n.post_id = p.id
              WHERE n.user_id = ? {$where}
              ORDER BY n.created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure all fields have default values
        $row['from_username'] = $row['from_username'] ?? 'Unknown User';
        $row['from_profile_image'] = $row['from_profile_image'] ?? 'default-avatar.png';
        $row['post_content'] = $row['post_content'] ?? '';
        $row['type'] = $row['type'] ?? 'like';
        $row['is_read'] = $row['is_read'] ?? 0;
        $notifications[] = $row;
    }
    
    $stmt->close();
    return $notifications;
}

/**
 * Get unread notification count
 */
function get_unread_count($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count 
                            FROM notifications 
                            WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return (int)$result['unread_count'];
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id, $user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications 
                            SET is_read = 1 
                            WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Mark all notifications as read
 */
function mark_all_notifications_read($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications 
                            SET is_read = 1 
                            WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Delete old notifications (older than 30 days)
 */
function cleanup_old_notifications() {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM notifications 
                            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $stmt->close();
}

/**
 * Format notification message
 */
function format_notification_message($notification) {
    $username = htmlspecialchars($notification['from_username']);
    
    switch ($notification['type']) {
        case 'like':
            return "<strong>{$username}</strong> liked your post";
        case 'comment':
            return "<strong>{$username}</strong> commented on your post";
        case 'follow':
            return "<strong>{$username}</strong> started following you";
        case 'mention':
            return "<strong>{$username}</strong> mentioned you in a post";
        default:
            return htmlspecialchars($notification['message']);
    }
}

/**
 * Get notification icon
 */
function get_notification_icon($type) {
    $icons = [
        'like' => '<i class="fas fa-heart" style="color: #f43f5e;"></i>',
        'comment' => '<i class="fas fa-comment" style="color: #3b82f6;"></i>',
        'follow' => '<i class="fas fa-user-plus" style="color: #10b981;"></i>',
        'mention' => '<i class="fas fa-at" style="color: #8b5cf6;"></i>'
    ];
    
    return $icons[$type] ?? '<i class="fas fa-bell"></i>';
}

/**
 * Time ago helper for notifications
 */
function notification_time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return $minutes == 1 ? "1m ago" : "{$minutes}m ago";
    } else if ($hours <= 24) {
        return $hours == 1 ? "1h ago" : "{$hours}h ago";
    } else if ($days <= 7) {
        return $days == 1 ? "1d ago" : "{$days}d ago";
    } else {
        return date('M d', strtotime($timestamp));
    }
}
