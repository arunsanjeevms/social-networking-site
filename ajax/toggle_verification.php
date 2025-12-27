<?php
/**
 * AJAX: Toggle User Verification
 * Allows admins to verify/unverify users
 */

session_start();
require_once '../config/database.php';
require_once '../config/admin_auth.php';

header('Content-Type: application/json');

// Check if user is admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];

// Validate input
if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing user ID']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Check if user exists
$stmt = $conn->prepare("SELECT id, username, is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

// Don't allow admin to verify themselves
if ($user_id == $admin_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot verify yourself']);
    exit();
}

// Toggle verification status
$new_status = !$user['is_verified'];
$action = $new_status ? 'verified' : 'unverified';

// Update user verification status
if ($new_status) {
    // Verify user
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verified_at = NOW(), verified_by = ? WHERE id = ?");
    $stmt->bind_param("ii", $admin_id, $user_id);
} else {
    // Unverify user
    $stmt = $conn->prepare("UPDATE users SET is_verified = 0, verified_at = NULL, verified_by = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
}

if ($stmt->execute()) {
    // Log the action
    $stmt_log = $conn->prepare("INSERT INTO verification_logs (user_id, admin_id, action, reason) VALUES (?, ?, ?, ?)");
    $stmt_log->bind_param("iiss", $user_id, $admin_id, $action, $reason);
    $stmt_log->execute();
    
    echo json_encode([
        'success' => true,
        'is_verified' => $new_status,
        'message' => $new_status ? 'User verified successfully' : 'User verification removed'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update verification status']);
}
?>
