<?php
/**
 * AJAX Get Notifications Handler
 * Fetch user notifications for dropdown
 */

session_start();
require_once '../config/database.php';
require_once '../config/notifications.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'fetch';

switch ($action) {
    case 'fetch':
        $notifications = get_notifications($user_id, 15);
        $unread_count = get_unread_count($user_id);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'mark_read':
        if (isset($_POST['notification_id'])) {
            $notification_id = (int)$_POST['notification_id'];
            mark_notification_read($notification_id, $user_id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        }
        break;
        
    case 'mark_all_read':
        mark_all_notifications_read($user_id);
        echo json_encode(['success' => true]);
        break;
        
    case 'count':
        $unread_count = get_unread_count($user_id);
        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
