<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  SOCIAL NETWORK - FINAL SETUP & DEPLOYMENT GUIDE
 * ═══════════════════════════════════════════════════════════════
 * 
 * This guide will walk you through the complete setup process
 * from installation to running your social network website.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Setup Guide - Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1877f2 0%, #0d5dbf 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
        }
        .step {
            background: #f8f9fa;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
            border-left: 5px solid #1877f2;
        }
        .step-number {
            background: #1877f2;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
        }
        .step h3 {
            display: inline-block;
            color: #1877f2;
            font-size: 22px;
            margin-bottom: 15px;
        }
        .step-content {
            margin-left: 55px;
            margin-top: 15px;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Consolas', 'Monaco', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        ul, ol {
            margin-left: 20px;
            margin-top: 10px;
        }
        li {
            margin: 8px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #1877f2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #166fe5;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .checklist {
            list-style: none;
            margin-left: 0;
        }
        .checklist li {
            padding: 8px 0;
        }
        .checklist li:before {
            content: "☐ ";
            font-size: 20px;
            margin-right: 10px;
            color: #1877f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users"></i> Social Network</h1>
            <p style="font-size: 18px;">Complete Setup & Deployment Guide</p>
        </div>
        
        <div class="content">
            <!-- Introduction -->
            <div class="info">
                <h3><i class="fas fa-info-circle"></i> Welcome!</h3>
                <p>This guide will help you set up and run your social networking website on XAMPP. Follow each step carefully for a successful installation.</p>
            </div>
            
            <!-- Prerequisites -->
            <div class="step">
                <span class="step-number">1</span>
                <h3>Prerequisites Check</h3>
                <div class="step-content">
                    <p>Before starting, ensure you have:</p>
                    <ul class="checklist">
                        <li>XAMPP installed on your computer</li>
                        <li>All project files in <code>c:\xampp\htdocs\social\</code></li>
                        <li>Basic understanding of how to use XAMPP</li>
                        <li>A web browser (Chrome, Firefox, Edge, etc.)</li>
                    </ul>
                </div>
            </div>
            
            <!-- Step 2: Start XAMPP -->
            <div class="step">
                <span class="step-number">2</span>
                <h3>Start XAMPP Services</h3>
                <div class="step-content">
                    <ol>
                        <li>Open <strong>XAMPP Control Panel</strong></li>
                        <li>Click <strong>Start</strong> button next to <strong>Apache</strong></li>
                        <li>Click <strong>Start</strong> button next to <strong>MySQL</strong></li>
                        <li>Wait until both show green status and say "Running"</li>
                    </ol>
                    
                    <div class="warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Common Issue:</strong>
                        If Apache doesn't start, it might be because port 80 is being used by another program (like Skype or IIS). 
                        You can either close that program or change Apache's port in XAMPP Config.
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Create Database -->
            <div class="step">
                <span class="step-number">3</span>
                <h3>Create Database</h3>
                <div class="step-content">
                    <ol>
                        <li>Open your web browser</li>
                        <li>Navigate to: <code class="code-block">http://localhost/phpmyadmin</code></li>
                        <li>Click on <strong>"New"</strong> in the left sidebar</li>
                        <li>Enter database name: <code>social_network</code></li>
                        <li>Set collation to: <strong>utf8mb4_general_ci</strong></li>
                        <li>Click <strong>"Create"</strong> button</li>
                    </ol>
                    
                    <div class="success">
                        <strong><i class="fas fa-check-circle"></i> Success:</strong>
                        You should see "Database social_network has been created" message.
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Import Database Schema -->
            <div class="step">
                <span class="step-number">4</span>
                <h3>Import Database Schema</h3>
                <div class="step-content">
                    <ol>
                        <li>In phpMyAdmin, click on <strong>social_network</strong> database (left sidebar)</li>
                        <li>Click the <strong>"Import"</strong> tab at the top</li>
                        <li>Click <strong>"Choose File"</strong> button</li>
                        <li>Navigate to and select: <code>c:\xampp\htdocs\social\social_network.sql</code></li>
                        <li>Scroll down and click <strong>"Go"</strong> button at the bottom</li>
                        <li>Wait for the import to complete</li>
                    </ol>
                    
                    <div class="success">
                        <strong><i class="fas fa-check-circle"></i> Success:</strong>
                        You should see "Import has been successfully finished" and 4 tables created: users, posts, likes, comments.
                    </div>
                    
                    <div class="info">
                        <strong><i class="fas fa-database"></i> What's Included:</strong>
                        The SQL file creates all necessary tables and includes a demo account for testing.
                    </div>
                </div>
            </div>
            
            <!-- Step 5: Verify File Structure -->
            <div class="step">
                <span class="step-number">5</span>
                <h3>Verify File Structure</h3>
                <div class="step-content">
                    <p>Ensure all files are in place. Your folder structure should look like this:</p>
                    <div class="code-block">
c:\xampp\htdocs\social\
├── index.php
├── signup.php
├── home.php
├── profile.php
├── create_post.php
├── config\database.php
├── includes\header.php
├── includes\footer.php
├── assets\css\style.css
├── assets\js\main.js
├── ajax\like_post.php
└── ajax\add_comment.php
                    </div>
                    
                    <p style="margin-top: 15px;">
                        <a href="check_setup.php" class="btn">
                            <i class="fas fa-check-circle"></i> Run Automated File Check
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Step 6: Create Default Avatar -->
            <div class="step">
                <span class="step-number">6</span>
                <h3>Create Default Avatar (Optional but Recommended)</h3>
                <div class="step-content">
                    <p>A default avatar is used when users haven't uploaded their profile picture yet.</p>
                    
                    <p><strong>Method 1: Using Avatar Generator</strong></p>
                    <ol>
                        <li>Open in browser: <code class="code-block">http://localhost/social/generate_avatar.html</code></li>
                        <li>Click "Download as default-avatar.png"</li>
                        <li>Save to: <code>c:\xampp\htdocs\social\assets\uploads\profiles\default-avatar.png</code></li>
                    </ol>
                    
                    <p style="margin-top: 15px;"><strong>Method 2: Use SVG (Already Created)</strong></p>
                    <p>A default SVG avatar file has been automatically created at:<br>
                    <code>assets\uploads\profiles\default-avatar.svg</code></p>
                    
                    <div class="info">
                        <strong><i class="fas fa-lightbulb"></i> Note:</strong>
                        If you skip this step, the website will still work, but profile pictures will show as broken images until users upload their own photos.
                    </div>
                </div>
            </div>
            
            <!-- Step 7: Access Website -->
            <div class="step">
                <span class="step-number">7</span>
                <h3>Access Your Website</h3>
                <div class="step-content">
                    <ol>
                        <li>Open your web browser</li>
                        <li>Navigate to: <code class="code-block">http://localhost/social</code></li>
                        <li>You should see the login page!</li>
                    </ol>
                    
                    <div class="success">
                        <strong><i class="fas fa-rocket"></i> Congratulations!</strong>
                        Your social network is now live and running!
                    </div>
                    
                    <p style="margin-top: 20px;">
                        <a href="http://localhost/social" class="btn btn-success" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Open Social Network
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Step 8: Test the Website -->
            <div class="step">
                <span class="step-number">8</span>
                <h3>Test Your Installation</h3>
                <div class="step-content">
                    <p><strong>Option 1: Use Demo Account</strong></p>
                    <div class="code-block">
Email: demo@example.com
Password: password123
                    </div>
                    
                    <p style="margin-top: 15px;"><strong>Option 2: Create New Account</strong></p>
                    <ol>
                        <li>Click "Sign up here" on the login page</li>
                        <li>Fill in username, email, and password</li>
                        <li>Click "Create Account"</li>
                        <li>You'll be automatically logged in!</li>
                    </ol>
                    
                    <p style="margin-top: 15px;"><strong>Things to Try:</strong></p>
                    <ul>
                        <li><i class="fas fa-moon"></i> Toggle dark mode (moon icon in nav)</li>
                        <li><i class="fas fa-plus-circle"></i> Create a new post</li>
                        <li><i class="fas fa-heart"></i> Like a post (instant update!)</li>
                        <li><i class="fas fa-comment"></i> Add a comment</li>
                        <li><i class="fas fa-user"></i> Update your profile picture</li>
                        <li><i class="fas fa-edit"></i> Edit your bio</li>
                    </ul>
                </div>
            </div>
            
            <!-- Troubleshooting -->
            <div class="step">
                <span class="step-number">?</span>
                <h3>Troubleshooting</h3>
                <div class="step-content">
                    <p><strong>Problem: "Connection failed" error</strong></p>
                    <ul>
                        <li>Make sure MySQL is running (green in XAMPP)</li>
                        <li>Verify database name is exactly: <code>social_network</code></li>
                        <li>Check config/database.php for correct credentials</li>
                    </ul>
                    
                    <p style="margin-top: 15px;"><strong>Problem: Blank white page</strong></p>
                    <ul>
                        <li>Make sure Apache is running</li>
                        <li>Check that files are in correct location</li>
                        <li>Press F12 and check Console for errors</li>
                    </ul>
                    
                    <p style="margin-top: 15px;"><strong>Problem: Page not found (404)</strong></p>
                    <ul>
                        <li>Verify URL is: <code>http://localhost/social</code></li>
                        <li>Check folder name is exactly "social" (lowercase)</li>
                        <li>Ensure Apache is running</li>
                    </ul>
                    
                    <p style="margin-top: 15px;"><strong>Problem: Styles not loading</strong></p>
                    <ul>
                        <li>Check that assets/css/style.css exists</li>
                        <li>Clear browser cache (Ctrl + F5)</li>
                        <li>Check browser console (F12) for 404 errors</li>
                    </ul>
                    
                    <p style="margin-top: 20px;">
                        <a href="check_setup.php" class="btn">
                            <i class="fas fa-wrench"></i> Run Diagnostic Check
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Next Steps -->
            <div class="info">
                <h3><i class="fas fa-graduation-cap"></i> What You've Accomplished</h3>
                <p>You've successfully set up a complete social networking website with:</p>
                <ul>
                    <li>✅ Secure user authentication</li>
                    <li>✅ Post creation with image uploads</li>
                    <li>✅ Real-time likes and comments (AJAX)</li>
                    <li>✅ Profile management</li>
                    <li>✅ Dark/Light theme</li>
                    <li>✅ Responsive design</li>
                    <li>✅ Security best practices</li>
                </ul>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <h3>📚 Additional Resources</h3>
            <p style="margin: 15px 0;">
                <a href="README.md" class="btn"><i class="fas fa-book"></i> Full Documentation</a>
                <a href="SETUP_GUIDE.md" class="btn"><i class="fas fa-file-alt"></i> Quick Guide</a>
                <a href="PROJECT_INDEX.html" class="btn"><i class="fas fa-list"></i> File Index</a>
            </p>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <p style="color: #666;">
                    <i class="fas fa-heart" style="color: #f02849;"></i> 
                    Built as an educational project to demonstrate full-stack web development
                </p>
            </div>
        </div>
    </div>
</body>
</html>
