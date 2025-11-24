<?php
/**
 * Setup Verification Script
 * 
 * This script checks if your environment is properly configured
 * Run this file to verify your setup before using the social network
 * 
 * Access: http://localhost/social/check_setup.php
 */

// Prevent direct access after initial setup
// Comment out this line for first-time setup check
// die("Setup check disabled. Uncomment line 11 in check_setup.php to run again.");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Verification - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #1877f2;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .check-item.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .check-item i {
            font-size: 24px;
            margin-right: 15px;
            width: 30px;
        }
        .success i { color: #28a745; }
        .error i { color: #dc3545; }
        .warning i { color: #ffc107; }
        .check-item .text {
            flex: 1;
        }
        .check-item .title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .check-item .description {
            font-size: 14px;
            color: #666;
        }
        .section-title {
            font-size: 20px;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1877f2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 5px;
        }
        .btn:hover {
            background: #166fe5;
        }
        .summary {
            background: #e7f3ff;
            border: 2px solid #1877f2;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .summary h3 {
            color: #1877f2;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-check-circle"></i> Setup Verification</h1>
            <p>Social Network Environment Check</p>
        </div>
        
        <div class="content">
            <?php
            $errors = 0;
            $warnings = 0;
            $success = 0;
            
            // Check 1: PHP Version
            echo '<h2 class="section-title">System Requirements</h2>';
            
            $php_version = phpversion();
            if (version_compare($php_version, '7.0.0', '>=')) {
                echo '<div class="check-item success">
                    <i class="fas fa-check-circle"></i>
                    <div class="text">
                        <div class="title">PHP Version: ' . $php_version . '</div>
                        <div class="description">PHP version is compatible</div>
                    </div>
                </div>';
                $success++;
            } else {
                echo '<div class="check-item error">
                    <i class="fas fa-times-circle"></i>
                    <div class="text">
                        <div class="title">PHP Version: ' . $php_version . '</div>
                        <div class="description">PHP 7.0 or higher required</div>
                    </div>
                </div>';
                $errors++;
            }
            
            // Check 2: MySQLi Extension
            if (extension_loaded('mysqli')) {
                echo '<div class="check-item success">
                    <i class="fas fa-check-circle"></i>
                    <div class="text">
                        <div class="title">MySQLi Extension</div>
                        <div class="description">Database extension is available</div>
                    </div>
                </div>';
                $success++;
            } else {
                echo '<div class="check-item error">
                    <i class="fas fa-times-circle"></i>
                    <div class="text">
                        <div class="title">MySQLi Extension</div>
                        <div class="description">MySQLi extension is not loaded</div>
                    </div>
                </div>';
                $errors++;
            }
            
            // Check 3: Database Connection
            echo '<h2 class="section-title">Database Configuration</h2>';
            
            @include 'config/database.php';
            
            if (isset($conn) && $conn->connect_error === null) {
                echo '<div class="check-item success">
                    <i class="fas fa-check-circle"></i>
                    <div class="text">
                        <div class="title">Database Connection</div>
                        <div class="description">Successfully connected to MySQL</div>
                    </div>
                </div>';
                $success++;
                
                // Check if tables exist
                $tables = ['users', 'posts', 'likes', 'comments'];
                $tables_exist = true;
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result->num_rows == 0) {
                        $tables_exist = false;
                        break;
                    }
                }
                
                if ($tables_exist) {
                    echo '<div class="check-item success">
                        <i class="fas fa-check-circle"></i>
                        <div class="text">
                            <div class="title">Database Tables</div>
                            <div class="description">All required tables are present</div>
                        </div>
                    </div>';
                    $success++;
                } else {
                    echo '<div class="check-item error">
                        <i class="fas fa-times-circle"></i>
                        <div class="text">
                            <div class="title">Database Tables</div>
                            <div class="description">Required tables are missing. Import social_network.sql</div>
                        </div>
                    </div>';
                    $errors++;
                }
            } else {
                echo '<div class="check-item error">
                    <i class="fas fa-times-circle"></i>
                    <div class="text">
                        <div class="title">Database Connection</div>
                        <div class="description">Cannot connect to database. Check config/database.php</div>
                    </div>
                </div>';
                $errors++;
            }
            
            // Check 4: File Structure
            echo '<h2 class="section-title">File Structure</h2>';
            
            $required_files = [
                'config/database.php' => 'Database Configuration',
                'includes/header.php' => 'Header Include',
                'includes/footer.php' => 'Footer Include',
                'assets/css/style.css' => 'Main Stylesheet',
                'assets/js/main.js' => 'JavaScript File',
                'index.php' => 'Login Page',
                'signup.php' => 'Signup Page',
                'home.php' => 'Home Feed',
                'profile.php' => 'Profile Page',
                'create_post.php' => 'Create Post Page'
            ];
            
            $missing_files = 0;
            foreach ($required_files as $file => $name) {
                if (file_exists($file)) {
                    $success++;
                } else {
                    echo '<div class="check-item error">
                        <i class="fas fa-times-circle"></i>
                        <div class="text">
                            <div class="title">Missing: ' . $name . '</div>
                            <div class="description">File not found: ' . $file . '</div>
                        </div>
                    </div>';
                    $missing_files++;
                    $errors++;
                }
            }
            
            if ($missing_files == 0) {
                echo '<div class="check-item success">
                    <i class="fas fa-check-circle"></i>
                    <div class="text">
                        <div class="title">Core Files</div>
                        <div class="description">All required files are present</div>
                    </div>
                </div>';
            }
            
            // Check 5: Upload Directories
            echo '<h2 class="section-title">Upload Directories</h2>';
            
            $upload_dirs = [
                'assets/uploads/profiles' => 'Profile Images',
                'assets/uploads/posts' => 'Post Images'
            ];
            
            foreach ($upload_dirs as $dir => $name) {
                if (is_dir($dir)) {
                    if (is_writable($dir)) {
                        echo '<div class="check-item success">
                            <i class="fas fa-check-circle"></i>
                            <div class="text">
                                <div class="title">' . $name . ' Directory</div>
                                <div class="description">Directory exists and is writable</div>
                            </div>
                        </div>';
                        $success++;
                    } else {
                        echo '<div class="check-item warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="text">
                                <div class="title">' . $name . ' Directory</div>
                                <div class="description">Directory exists but may not be writable</div>
                            </div>
                        </div>';
                        $warnings++;
                    }
                } else {
                    echo '<div class="check-item warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="text">
                            <div class="title">' . $name . ' Directory</div>
                            <div class="description">Directory does not exist (will be created on first upload)</div>
                        </div>
                    </div>';
                    $warnings++;
                }
            }
            
            // Check for default avatar
            if (file_exists('assets/uploads/profiles/default-avatar.png')) {
                echo '<div class="check-item success">
                    <i class="fas fa-check-circle"></i>
                    <div class="text">
                        <div class="title">Default Avatar</div>
                        <div class="description">Default profile picture is present</div>
                    </div>
                </div>';
                $success++;
            } else {
                echo '<div class="check-item warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="text">
                        <div class="title">Default Avatar</div>
                        <div class="description">Run generate_avatar.html to create default profile picture</div>
                    </div>
                </div>';
                $warnings++;
            }
            ?>
            
            <!-- Summary -->
            <div class="summary">
                <h3><i class="fas fa-clipboard-check"></i> Summary</h3>
                <p>
                    <strong style="color: #28a745;">✓ Success:</strong> <?php echo $success; ?> checks passed<br>
                    <?php if ($errors > 0): ?>
                        <strong style="color: #dc3545;">✗ Errors:</strong> <?php echo $errors; ?> issues found<br>
                    <?php endif; ?>
                    <?php if ($warnings > 0): ?>
                        <strong style="color: #ffc107;">⚠ Warnings:</strong> <?php echo $warnings; ?> warnings<br>
                    <?php endif; ?>
                </p>
                
                <?php if ($errors == 0): ?>
                    <p style="margin-top: 15px; color: #28a745; font-weight: bold;">
                        <i class="fas fa-thumbs-up"></i> Your setup is ready! You can start using the social network.
                    </p>
                <?php else: ?>
                    <p style="margin-top: 15px; color: #dc3545; font-weight: bold;">
                        <i class="fas fa-exclamation-circle"></i> Please fix the errors above before proceeding.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <?php if ($errors == 0): ?>
                <a href="index.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Go to Login Page
                </a>
            <?php endif; ?>
            <a href="SETUP_GUIDE.md" class="btn" style="background: #6c757d;">
                <i class="fas fa-book"></i> View Setup Guide
            </a>
            <br><br>
            <small style="color: #666;">
                Re-run this check anytime by visiting: http://localhost/social/check_setup.php
            </small>
        </div>
    </div>
</body>
</html>
