<?php
/**
 * Database Configuration File
 * 
 * This file contains database connection settings and establishes
 * a connection to MySQL using mysqli.
 * 
 * Make sure to:
 * 1. Start Apache and MySQL in XAMPP Control Panel
 * 2. Create the database 'social_network' in phpMyAdmin
 * 3. Import the social_network.sql file
 */

// Database credentials
define('DB_HOST', 'localhost');      // Database host (usually localhost)
define('DB_USER', 'root');           // Default XAMPP MySQL username
define('DB_PASS', '');               // Default XAMPP MySQL password (empty)
define('DB_NAME', 'social_network'); // Database name

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for emoji support
$conn->set_charset("utf8mb4");

/**
 * Function to sanitize user input
 * Prevents XSS attacks by converting special characters
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Function to check if user is logged in
 * Returns true if session contains user_id
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Function to redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}
?>
