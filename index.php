<?php
/**
 * Login Page
 * 
 * This page allows users to login to their account.
 * Features:
 * - Email and password authentication
 * - Password verification using password_verify()
 * - Session creation on successful login
 * - Error messages for invalid credentials
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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // User found, verify password
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Password correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect to home page
                header("Location: home.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
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
    <title>Login - Social Network</title>
    
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
            
            <h2>Welcome Back</h2>
            <p>Login to your account</p>
            
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" onsubmit="return validateLoginForm(event)">
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
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <!-- Footer Link -->
            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Sign up here</a>
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
