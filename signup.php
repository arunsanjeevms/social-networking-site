<?php
/**
 * Sign Up Page
 * 
 * This page allows new users to create an account.
 * Features:
 * - Username, email, and password registration
 * - Password confirmation
 * - Password hashing using password_hash()
 * - Duplicate email/username validation
 * - Prepared statements for SQL injection prevention
 */

// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: home.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Process signup form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email or username already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user into database
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Account created successfully! You can now login.";
                
                // Optional: Auto-login after signup
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                // Redirect to home page
                header("Location: home.php");
                exit();
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Social Network</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/social/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <!-- Logo -->
            <div class="logo text-center mb-20" style="justify-content: center;">
                <i class="fas fa-users"></i>
                <span>SocialNet</span>
            </div>
            
            <h2>Create Account</h2>
            <p>Join our community today</p>
            
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Signup Form -->
            <form method="POST" action="" onsubmit="return validateSignupForm(event)">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Create a password (min 6 characters)" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <!-- Footer Link -->
            <div class="auth-footer">
                Already have an account? <a href="index.php">Login here</a>
            </div>
            
            <!-- Theme Toggle -->
            <div class="text-center mt-20">
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="/social/assets/js/main.js"></script>
</body>
</html>
