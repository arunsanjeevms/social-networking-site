<?php
/**
 * Login Page - Modern Dark Theme
 * 
 * Features:
 * - Modern dark neon UI with floating labels
 * - Password show/hide toggle
 * - Secure authentication
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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
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
                // Password correct, regenerate session ID for security
                session_regenerate_id(true);
                
                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['last_activity'] = time();
                
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
            
            <h2 style="background: var(--gradient-primary); -webkit-background-clip: text; background-clip: text; color: transparent;">Welcome Back</h2>
            <p style="color: var(--text-secondary);">Sign in to continue to your account</p>
            
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
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
                <button type="submit" class="btn btn-primary" style="background: var(--gradient-primary); color: #000; box-shadow: var(--shadow-glow);">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Sign In</span>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>or</span>
            </div>
            
            <!-- NFC Login Button (Future Ready) -->
            
            
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
        
        // NFC Login (Placeholder for future implementation)
        async function startNFCLogin() {
            if ('NDEFReader' in window) {
                try {
                    const ndef = new NDEFReader();
                    await ndef.scan();
                    
                    // Show scanning status
                    const btn = document.getElementById('nfcLoginBtn');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Scanning...</span>';
                    
                    ndef.addEventListener('reading', ({ message, serialNumber }) => {
                        for (const record of message.records) {
                            if (record.recordType === "text") {
                                const decoder = new TextDecoder();
                                const userId = decoder.decode(record.data);
                                
                                // Redirect to NFC authentication
                                window.location.href = `nfc_login.php?id=${encodeURIComponent(userId)}`;
                            }
                        }
                    });
                } catch (error) {
                    alert('NFC Error: ' + error.message);
                }
            } else {
                alert('Web NFC is not supported on this device/browser.\nPlease use Chrome on an Android device.');
            }
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
