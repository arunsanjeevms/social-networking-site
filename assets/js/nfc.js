/**
 * NFC Profile Scanner Module
 * 
 * This module provides Web NFC functionality for scanning NFC tags
 * that contain user IDs and redirecting to their profiles.
 * 
 * Requirements:
 * - Chrome browser on Android
 * - HTTPS connection (or localhost for testing)
 * - NFC-enabled Android device
 * 
 * Usage:
 * 1. Include this script in your page
 * 2. Call NFCScanner.init() to set up
 * 3. Call NFCScanner.startScan() to begin scanning
 */

const NFCScanner = {
    // Configuration
    config: {
        apiEndpoint: '/social/api/nfc.php',
        profileUrl: '/social/user_profile.php',
        autoRedirect: true,
        redirectDelay: 2000
    },
    
    // State
    isSupported: false,
    isScanning: false,
    reader: null,
    
    /**
     * Initialize the NFC Scanner
     */
    init: function() {
        this.checkSupport();
        return this;
    },
    
    /**
     * Check if Web NFC is supported
     */
    checkSupport: function() {
        this.isSupported = 'NDEFReader' in window;
        
        if (!this.isSupported) {
            console.warn('Web NFC is not supported in this browser');
        }
        
        return this.isSupported;
    },
    
    /**
     * Start NFC scanning
     * @param {Object} options - Scanning options
     * @returns {Promise}
     */
    startScan: async function(options = {}) {
        if (!this.isSupported) {
            return Promise.reject(new Error('Web NFC is not supported'));
        }
        
        if (this.isScanning) {
            console.log('Already scanning');
            return;
        }
        
        try {
            this.reader = new NDEFReader();
            await this.reader.scan();
            
            this.isScanning = true;
            console.log('NFC scan started');
            
            // Trigger callback if provided
            if (options.onScanStart) {
                options.onScanStart();
            }
            
            // Handle reading events
            this.reader.addEventListener('reading', (event) => {
                this.handleReading(event, options);
            });
            
            // Handle errors
            this.reader.addEventListener('readingerror', (event) => {
                console.error('NFC reading error:', event);
                if (options.onError) {
                    options.onError(new Error('Error reading NFC tag'));
                }
            });
            
            return Promise.resolve();
            
        } catch (error) {
            this.isScanning = false;
            console.error('NFC scan error:', error);
            
            // Provide user-friendly error messages
            let message = 'Failed to start NFC scan';
            
            if (error.name === 'NotAllowedError') {
                message = 'NFC permission denied. Please allow NFC access.';
            } else if (error.name === 'NotSupportedError') {
                message = 'NFC is not supported on this device.';
            } else if (error.name === 'NotReadableError') {
                message = 'NFC is disabled. Please enable NFC in device settings.';
            }
            
            if (options.onError) {
                options.onError(new Error(message));
            }
            
            return Promise.reject(new Error(message));
        }
    },
    
    /**
     * Handle NFC reading event
     * @param {Object} event - Reading event
     * @param {Object} options - Options
     */
    handleReading: function(event, options = {}) {
        const { message, serialNumber } = event;
        
        console.log('NFC Tag Serial:', serialNumber);
        console.log('Records count:', message.records.length);
        
        let userId = null;
        
        // Parse NFC records
        for (const record of message.records) {
            console.log('Record type:', record.recordType);
            
            userId = this.extractUserId(record);
            
            if (userId) {
                break;
            }
        }
        
        if (userId) {
            console.log('User ID found:', userId);
            
            if (options.onUserFound) {
                options.onUserFound(userId);
            }
            
            // Fetch user data
            this.fetchUser(userId, options);
            
        } else {
            console.log('No valid user ID found');
            
            if (options.onError) {
                options.onError(new Error('No valid user ID found on this tag'));
            }
        }
    },
    
    /**
     * Extract user ID from NFC record
     * @param {Object} record - NFC record
     * @returns {number|null}
     */
    extractUserId: function(record) {
        const decoder = new TextDecoder(record.encoding || 'utf-8');
        
        try {
            if (record.recordType === 'text') {
                const text = decoder.decode(record.data).trim();
                
                // Check if it's a plain number
                if (/^\d+$/.test(text)) {
                    return parseInt(text, 10);
                }
            } else if (record.recordType === 'url' || record.recordType === 'absolute-url') {
                const url = decoder.decode(record.data);
                
                // Extract ID from URL parameter
                const match = url.match(/[?&]id=(\d+)/);
                if (match) {
                    return parseInt(match[1], 10);
                }
            } else {
                // Try to decode as text anyway
                const text = decoder.decode(record.data).trim();
                if (/^\d+$/.test(text)) {
                    return parseInt(text, 10);
                }
            }
        } catch (e) {
            console.error('Error extracting user ID:', e);
        }
        
        return null;
    },
    
    /**
     * Fetch user data from API
     * @param {number} userId - User ID
     * @param {Object} options - Options
     */
    fetchUser: async function(userId, options = {}) {
        try {
            const response = await fetch(`${this.config.apiEndpoint}?id=${userId}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                console.log('User data:', data.data);
                
                if (options.onUserData) {
                    options.onUserData(data.data);
                }
                
                // Auto redirect if enabled
                if (this.config.autoRedirect) {
                    setTimeout(() => {
                        window.location.href = `${this.config.profileUrl}?id=${userId}`;
                    }, this.config.redirectDelay);
                }
                
            } else {
                throw new Error(data.message || 'User not found');
            }
            
        } catch (error) {
            console.error('API error:', error);
            
            if (options.onError) {
                options.onError(error);
            }
        }
    },
    
    /**
     * Stop NFC scanning
     */
    stopScan: function() {
        this.isScanning = false;
        this.reader = null;
        console.log('NFC scan stopped');
    }
};

/**
 * NFC Tag Writer (for testing/admin purposes)
 * Note: Writing NFC tags requires the same Chrome Android + HTTPS requirements
 */
const NFCWriter = {
    /**
     * Write user ID to NFC tag
     * @param {number} userId - User ID to write
     * @returns {Promise}
     */
    writeUserId: async function(userId) {
        if (!('NDEFReader' in window)) {
            return Promise.reject(new Error('Web NFC is not supported'));
        }
        
        try {
            const ndef = new NDEFReader();
            
            // Write text record with user ID
            await ndef.write({
                records: [
                    {
                        recordType: 'text',
                        data: String(userId)
                    }
                ]
            });
            
            console.log('NFC tag written successfully');
            return Promise.resolve();
            
        } catch (error) {
            console.error('NFC write error:', error);
            return Promise.reject(error);
        }
    },
    
    /**
     * Write profile URL to NFC tag
     * @param {number} userId - User ID
     * @param {string} baseUrl - Base URL of the site
     * @returns {Promise}
     */
    writeProfileUrl: async function(userId, baseUrl = '') {
        if (!('NDEFReader' in window)) {
            return Promise.reject(new Error('Web NFC is not supported'));
        }
        
        try {
            const ndef = new NDEFReader();
            const profileUrl = `${baseUrl}/social/user_profile.php?id=${userId}`;
            
            await ndef.write({
                records: [
                    {
                        recordType: 'url',
                        data: profileUrl
                    }
                ]
            });
            
            console.log('NFC URL tag written successfully');
            return Promise.resolve();
            
        } catch (error) {
            console.error('NFC write error:', error);
            return Promise.reject(error);
        }
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    NFCScanner.init();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { NFCScanner, NFCWriter };
}
