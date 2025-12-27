<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

$conn = new mysqli('localhost', 'root', '', 'social_network');

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    exit(1);
}

echo "Connection successful!\n";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "Users table exists\n";
    
    // Check user count
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "Users in database: " . $row['count'] . "\n";
    
    // Show first user
    $result = $conn->query("SELECT id, username FROM users LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "First user: ID=" . $user['id'] . ", Username=" . $user['username'] . "\n";
    } else {
        echo "No users found\n";
    }
} else {
    echo "Users table not found!\n";
}

$conn->close();
?>
