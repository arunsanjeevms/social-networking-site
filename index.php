<?php
/**
 * Login Page - Modern Dark Theme
 * 
 * Features:
 * - Modern dark neon UI with floating labels
 * - Password show/hide toggle
 * - Secure authentication with session regeneration
 * - NFC login button (future ready)
 * - Fully responsive
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

// Include security functions (admin_auth.php is included within admin_security.php)
require_once 'config/admin_security.php';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check rate limiting
    if (is_rate_limited()) {
        $error = "Too many login attempts. Please try again later.";
        log_login_attempt($email, 0, 'rate_limited');
    }
    // Validate input
    elseif (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, email, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // User found, verify password
            $user = $result->fetch_assoc();
            
            // Check if account is locked
            if (is_account_locked($user['id'])) {
                $error = "Your account has been temporarily locked due to multiple failed login attempts. Please try again later.";
                log_login_attempt($email, 0, 'account_locked');
            }
            elseif (password_verify($password, $user['password'])) {
                // Password correct, regenerate session ID for security
                session_regenerate_id(true);
                
                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['last_activity'] = time();
                
                // Reset failed login attempts
                reset_failed_attempts($user['id']);
                
                // Update last login info
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $stmt_update = $conn->prepare("UPDATE users SET last_login_ip = ?, last_login_time = NOW() WHERE id = ?");
                $stmt_update->bind_param("si", $ip_address, $user['id']);
                $stmt_update->execute();
                $stmt_update->close();
                
                // Log successful login
                log_login_attempt($email, 1);
                
                // Create admin session if user is admin
                if ($user['is_admin']) {
                    create_admin_session($user['id']);
                }
                
                // Redirect to home page
                header("Location: home.php");
                exit();
            } else {
                // Increment failed attempts
                $stmt_fail = $conn->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
                $stmt_fail->bind_param("i", $user['id']);
                $stmt_fail->execute();
                $stmt_fail->close();
                
                // Check if should lock account
                $stmt_check = $conn->prepare("SELECT failed_login_attempts FROM users WHERE id = ?");
                $stmt_check->bind_param("i", $user['id']);
                $stmt_check->execute();
                $attempts_result = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();
                
                $max_attempts = (int)get_setting('max_login_attempts', '5');
                if ($attempts_result['failed_login_attempts'] >= $max_attempts) {
                    lock_account($user['id']);
                    $error = "Too many failed login attempts. Your account has been temporarily locked.";
                } else {
                    $remaining = $max_attempts - $attempts_result['failed_login_attempts'];
                    $error = "Invalid email or password. {$remaining} attempts remaining.";
                }
                
                log_login_attempt($email, 0, 'invalid_password');
            }
        } else {
            $error = "Invalid email or password";
            log_login_attempt($email, 0, 'user_not_found');
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
    <meta name="description" content="Login to SocialNet - Connect with friends and share your moments">
    <title>Login - SocialNet</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dark Theme CSS -->
    <link rel="stylesheet" href="/social/assets/css/dark.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <!-- Logo -->
            <div class="logo">
                <i class="fas fa-users"></i>
                <span>SocialNet</span>
            </div>
            
            <h2>Welcome Back</h2>
            <p>Sign in to continue to your account</p>
            
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" novalidate>
                <!-- Email Field with Floating Label -->
                <div class="form-group floating-label">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder=" " 
                        required
                        autocomplete="email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                </div>
                
                <!-- Password Field with Toggle -->
                <div class="form-group floating-label">
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder=" " 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                </div>
                
                <!-- Login Button -->
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Sign In</span>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>or</span>
            </div>
            
            <!-- NFC Login Button (Future Ready) -->
            <button type="button" class="btn btn-nfc" id="nfcLoginBtn" onclick="window.location.href='nfc_scan.php'">
                <i class="fas fa-wifi"></i>
                <span>Tap NFC to Login</span>
            </button>
            
            <!-- Footer Link -->
            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Create one now</a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-toggle-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Basic validation
            if (!email || !password) {
                e.preventDefault();
                showError('Please fill in all fields');
                return;
            }
            
            // Email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return;
            }
        });
        
        function showError(message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${message}</span>`;
            
            // Insert after the paragraph
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alert, form);
        }
        
        // Add input animations
        document.querySelectorAll('.form-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.closest('.form-group').classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.closest('.form-group').classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>
