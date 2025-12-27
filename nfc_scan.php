<?php
/**
 * NFC Profile Scanner Page
 * 
 * This page provides a Web NFC interface for scanning NFC tags
 * that contain user IDs and redirecting to their profiles.
 * 
 * Requirements:
 * - Chrome browser on Android (Web NFC only works on Chrome Android)
 * - HTTPS connection (Web NFC requires secure context)
 * - NFC-enabled Android device
 */

// Start session
session_start();
require_once dirname(__FILE__) . '/config/database.php';

// Set page title
$page_title = 'NFC Scanner';

// Include header only if logged in
$show_header = is_logged_in();
if ($show_header) {
    include 'includes/header.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (!$show_header): ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFC Profile Scanner - SocialNet</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dark Theme CSS -->
    <link rel="stylesheet" href="/social/assets/css/dark.css">
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <!-- NFC Scanner Section -->
        <div class="nfc-section">
            <!-- NFC Icon with Animation -->
            <div class="nfc-icon" id="nfcIcon">
                <i class="fas fa-wifi"></i>
            </div>
            
            <h3>NFC Profile Scanner</h3>
            <p>Tap an NFC tag to instantly open a user's profile</p>
            
            <!-- Scan Button -->
            <button type="button" class="btn btn-primary" id="scanBtn" onclick="startNFCScan()">
                <i class="fas fa-broadcast-tower"></i>
                <span>Start Scanning</span>
            </button>
            
            <!-- Status Display -->
            <div class="nfc-status" id="nfcStatus" style="display: none;">
                <div id="statusIcon">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--accent-teal);"></i>
                </div>
                <p id="statusText" style="margin-top: 12px;">Waiting for NFC tag...</p>
            </div>
            
            <!-- User Preview (shown when tag is read) -->
            <div id="userPreview" class="suggestion-card" style="display: none; margin-top: 24px;">
                <img id="previewAvatar" src="" alt="User" class="suggestion-avatar">
                <div class="suggestion-name" id="previewName"></div>
                <div class="suggestion-username" id="previewUsername"></div>
                <a id="previewLink" href="#" class="btn btn-primary" style="margin-top: 16px;">
                    <i class="fas fa-user"></i> View Profile
                </a>
            </div>
        </div>
        
        <!-- Browser Compatibility Notice -->
        <div class="alert alert-info" id="compatibilityNotice" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Browser Compatibility</strong><br>
                Web NFC is only supported on Chrome for Android. Make sure you're using HTTPS.
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="glass-card" style="padding: 24px; margin-top: 24px;">
            <h4 style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-question-circle" style="color: var(--accent-teal);"></i>
                How to Use NFC Profile Tags
            </h4>
            <ol style="color: var(--text-secondary); line-height: 1.8; padding-left: 20px;">
                <li>Click "Start Scanning" button above</li>
                <li>Hold your phone near an NFC tag</li>
                <li>The tag should contain only a user ID number</li>
                <li>You'll be automatically redirected to that user's profile</li>
            </ol>
        </div>
    </div>
    
    <?php if ($show_header): ?>
        <?php include 'includes/footer.php'; ?>
    <?php endif; ?>
    
    <!-- NFC JavaScript -->
    <script>
        // Check for Web NFC support on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkNFCSupport();
        });
        
        /**
         * Check if Web NFC is supported
         */
        function checkNFCSupport() {
            const compatibilityNotice = document.getElementById('compatibilityNotice');
            const scanBtn = document.getElementById('scanBtn');
            
            if (!('NDEFReader' in window)) {
                compatibilityNotice.style.display = 'flex';
                scanBtn.disabled = true;
                scanBtn.innerHTML = '<i class="fas fa-times-circle"></i><span>NFC Not Supported</span>';
                scanBtn.classList.remove('btn-primary');
                scanBtn.classList.add('btn-secondary');
                return false;
            }
            
            // Check for HTTPS
            if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                compatibilityNotice.innerHTML = `
                    <i class="fas fa-lock"></i>
                    <div>
                        <strong>HTTPS Required</strong><br>
                        Web NFC requires a secure HTTPS connection. Please access this page via HTTPS.
                    </div>
                `;
                compatibilityNotice.className = 'alert alert-warning';
                compatibilityNotice.style.display = 'flex';
            }
            
            return true;
        }
        
        /**
         * Start NFC Scanning
         */
        async function startNFCScan() {
            if (!('NDEFReader' in window)) {
                alert('Web NFC is not supported on this device/browser.\nPlease use Chrome on an Android device.');
                return;
            }
            
            try {
                const ndef = new NDEFReader();
                await ndef.scan();
                
                // Update UI to show scanning status
                updateScanningUI(true);
                
                console.log('NFC scan started successfully');
                
                // Handle NFC reading events
                ndef.addEventListener('reading', handleNFCReading);
                
                // Handle errors
                ndef.addEventListener('readingerror', () => {
                    updateStatus('error', 'Error reading NFC tag. Please try again.');
                });
                
            } catch (error) {
                console.error('NFC Error:', error);
                
                let errorMessage = 'Failed to start NFC scan.';
                
                if (error.name === 'NotAllowedError') {
                    errorMessage = 'NFC permission denied. Please allow NFC access and try again.';
                } else if (error.name === 'NotSupportedError') {
                    errorMessage = 'NFC is not supported on this device.';
                } else if (error.name === 'NotReadableError') {
                    errorMessage = 'NFC is disabled. Please enable NFC in your device settings.';
                }
                
                updateStatus('error', errorMessage);
            }
        }
        
        /**
         * Handle NFC Reading Event
         */
        async function handleNFCReading({ message, serialNumber }) {
            console.log('NFC Tag Serial Number:', serialNumber);
            console.log('NFC Records:', message.records.length);
            
            let userId = null;
            
            // Iterate through all records to find the user ID
            for (const record of message.records) {
                console.log('Record type:', record.recordType);
                console.log('Media type:', record.mediaType);
                
                if (record.recordType === 'text') {
                    const decoder = new TextDecoder(record.encoding || 'utf-8');
                    const text = decoder.decode(record.data);
                    console.log('Text content:', text);
                    
                    // Extract user ID (should be a number)
                    const extractedId = text.trim();
                    if (/^\d+$/.test(extractedId)) {
                        userId = parseInt(extractedId, 10);
                        break;
                    }
                } else if (record.recordType === 'url' || record.recordType === 'absolute-url') {
                    const decoder = new TextDecoder();
                    const url = decoder.decode(record.data);
                    console.log('URL content:', url);
                    
                    // Try to extract user ID from URL
                    const match = url.match(/[?&]id=(\d+)/);
                    if (match) {
                        userId = parseInt(match[1], 10);
                        break;
                    }
                } else if (record.recordType === 'unknown' || record.recordType === 'mime') {
                    // Try to decode as text anyway
                    try {
                        const decoder = new TextDecoder();
                        const text = decoder.decode(record.data);
                        if (/^\d+$/.test(text.trim())) {
                            userId = parseInt(text.trim(), 10);
                            break;
                        }
                    } catch (e) {
                        console.log('Could not decode record as text');
                    }
                }
            }
            
            if (userId) {
                updateStatus('success', 'NFC tag read successfully!');
                await fetchAndDisplayUser(userId);
            } else {
                updateStatus('error', 'No valid user ID found on this NFC tag.');
            }
        }
        
        /**
         * Fetch user data from API and display preview
         */
        async function fetchAndDisplayUser(userId) {
            try {
                updateStatus('loading', 'Loading user profile...');
                
                const response = await fetch(`/social/api/nfc.php?id=${userId}`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    displayUserPreview(data.data);
                    
                    // Auto-redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = `/social/user_profile.php?id=${userId}`;
                    }, 2000);
                    
                } else {
                    updateStatus('error', data.message || 'User not found');
                }
                
            } catch (error) {
                console.error('API Error:', error);
                updateStatus('error', 'Failed to fetch user profile');
            }
        }
        
        /**
         * Display user preview card
         */
        function displayUserPreview(user) {
            const preview = document.getElementById('userPreview');
            const avatar = document.getElementById('previewAvatar');
            const name = document.getElementById('previewName');
            const username = document.getElementById('previewUsername');
            const link = document.getElementById('previewLink');
            
            avatar.src = `/social/${user.profile_image}`;
            avatar.onerror = function() {
                this.src = '/social/assets/uploads/profiles/default-avatar.png';
            };
            name.textContent = user.username;
            username.textContent = `@${user.username}`;
            link.href = `/social/user_profile.php?id=${user.id}`;
            
            preview.style.display = 'block';
            preview.classList.add('slide-up');
            
            updateStatus('success', 'Redirecting to profile...');
        }
        
        /**
         * Update scanning UI
         */
        function updateScanningUI(isScanning) {
            const scanBtn = document.getElementById('scanBtn');
            const nfcStatus = document.getElementById('nfcStatus');
            const nfcIcon = document.getElementById('nfcIcon');
            
            if (isScanning) {
                scanBtn.innerHTML = '<i class="fas fa-stop-circle"></i><span>Scanning...</span>';
                scanBtn.disabled = true;
                nfcStatus.style.display = 'block';
                nfcStatus.classList.add('scanning');
                nfcIcon.style.animation = 'pulse-glow 1s infinite';
            } else {
                scanBtn.innerHTML = '<i class="fas fa-broadcast-tower"></i><span>Start Scanning</span>';
                scanBtn.disabled = false;
                nfcStatus.classList.remove('scanning');
            }
        }
        
        /**
         * Update status display
         */
        function updateStatus(type, message) {
            const statusIcon = document.getElementById('statusIcon');
            const statusText = document.getElementById('statusText');
            const nfcStatus = document.getElementById('nfcStatus');
            
            nfcStatus.style.display = 'block';
            
            switch (type) {
                case 'loading':
                    statusIcon.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--accent-teal);"></i>';
                    nfcStatus.classList.add('scanning');
                    break;
                case 'success':
                    statusIcon.innerHTML = '<i class="fas fa-check-circle" style="font-size: 24px; color: var(--success-color);"></i>';
                    nfcStatus.classList.remove('scanning');
                    break;
                case 'error':
                    statusIcon.innerHTML = '<i class="fas fa-times-circle" style="font-size: 24px; color: var(--danger-color);"></i>';
                    nfcStatus.classList.remove('scanning');
                    updateScanningUI(false);
                    break;
            }
            
            statusText.textContent = message;
        }
    </script>
</body>
</html>
