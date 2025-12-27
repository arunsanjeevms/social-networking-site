<?php
/**
 * NFC API Endpoint
 * 
 * Returns user details in JSON format when accessed with ?id=<user_id>
 * Used for NFC-based profile fetching
 * 
 * Security:
 * - Validates and sanitizes user_id input
 * - Uses prepared statements
 * - Returns limited user information (no sensitive data)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client, but log them

// Set JSON content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include database connection (adjust path since we're in api/ folder)
require_once dirname(__DIR__) . '/config/database.php';

// Check if connection exists and is valid
if (!isset($conn) || !$conn) {
    error_log('NFC API Error: Database connection failed');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'data' => null
    ]);
    exit();
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['message'] = 'User ID is required';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

// Sanitize and validate user_id (must be a positive integer)
$user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($user_id === false) {
    $response['message'] = 'Invalid user ID format';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

try {
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, email, bio, profile_image, created_at FROM users WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Build profile URL
        $profile_url = 'user_profile.php?id=' . $user['id'];
        
        // Build profile image URL
        $profile_image_url = 'assets/uploads/profiles/' . ($user['profile_image'] ?: 'default-avatar.png');
        
        // Calculate stats
        $stats = getUserStats($conn, $user_id);
        
        // Return user data (excluding sensitive info like password)
        $response['success'] = true;
        $response['message'] = 'User found';
        $response['data'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'bio' => $user['bio'] ?? '',
            'profile_image' => $profile_image_url,
            'profile_url' => $profile_url,
            'member_since' => date('F Y', strtotime($user['created_at'])),
            'stats' => $stats
        ];
        
        http_response_code(200);
    } else {
        $response['message'] = 'User not found';
        http_response_code(404);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $response['message'] = 'Server error';
    http_response_code(500);
    
    // Log error for debugging (don't expose details to client)
    $error_msg = 'NFC API Error for user_id=' . $user_id . ': ' . $e->getMessage();
    error_log($error_msg);
    
    // For debugging - remove in production
    // $response['debug'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);

/**
 * Get user statistics
 */
function getUserStats($conn, $user_id) {
    $stats = [
        'posts' => 0,
        'likes' => 0,
        'followers' => 0,
        'following' => 0
    ];
    
    // Get post count (with error handling)
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND is_deleted = 0");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['posts'] = (int)($row['count'] ?? 0);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Error getting post count: ' . $e->getMessage());
    }
    
    // Get total likes received (with error handling)
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['likes'] = (int)($row['count'] ?? 0);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Error getting likes count: ' . $e->getMessage());
    }
    
    // Get followers count (if follows table exists)
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['followers'] = (int)($row['count'] ?? 0);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Error getting followers count: ' . $e->getMessage());
    }
    
    // Get following count (if follows table exists)
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['following'] = (int)($row['count'] ?? 0);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Error getting following count: ' . $e->getMessage());
    }
    
    return $stats;
}
?>
