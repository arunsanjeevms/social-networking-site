<?php
/**
 * Logout Script
 * 
 * This script handles user logout by:
 * - Destroying the session
 * - Clearing session data
 * - Redirecting to login page
 */

// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>
