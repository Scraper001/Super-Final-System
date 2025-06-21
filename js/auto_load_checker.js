/**
 * Auto Load Checker - Student Status Monitor
 * Automatically checks and updates student enrollment status
 * 
 * Usage:
 * 1. Include this file in your HTML: <script src="auto_load_checker.js"></script>
 * 2. Initialize: AutoLoadChecker.init();
 * 3. Or with options: AutoLoadChecker.init({ interval: 300000, autoStart: true });
 */

const AutoLoadChecker = {
    // Configuration
    config: {
        endpoint: 'functions/ajax/auto_load_checker.php',
        interval: 5 * 60 * 1000, // 5 minutes default
        autoStart: false,
        showNotifications: true,
        debugMode: false
    },
    
    // State
    state: {
        intervalId: null,
        isRunning: false,
        lastCheck: null,
        totalUpdated: 0,
        callbacks: {
            onUpdate: null,
            onError: null,
            onStart: null,
            onStop: null
        }
    },

    /**
     * Initialize the Auto Load Checker
     * @param {Object} options - Configuration options
     */
    init: function(options = {}) {
        // Merge options with default config
        this.config = Object.assign(this.config, options);
        
        // Set up callbacks
        if (options.onUpdate) this.state.callbacks.onUpdate = options.onUpdate;
        if (options.onError) this.state.callbacks.onError = options.onError;
        if (options.onStart) this.state.callbacks.onStart = options.onStart;
        if (options.onStop) this.state.callbacks.onStop = options.onStop;
        
        this.log('Auto Load Checker initialized');
        
        // Auto start if enabled
        if (this.config.autoStart) {
            this.start();
        }
        
        // Make functions globally available
        window.startAutoChecker = () => this.start();
        window.stopAutoChecker = () => this.stop();
        window.checkStudentsNow = () => this.checkStudentStatus();
        window.getCheckerStats = () => this.getStats();
        
        return this;
    },

    /**
     * Start the automatic checking
     */
    start: function() {
        if (this.state.isRunning) {
            this.log('Auto checker is already running');
            return;
        }
        
        this.state.intervalId = setInterval(() => {
            this.checkStudentStatus();
        }, this.config.interval);
        
        this.state.isRunning = true;
        this.log('Auto Load Checker started - checking every ' + (this.config.interval / 1000) + ' seconds');
        
        // Run initial check
        this.checkStudentStatus();
        
        // Callback
        if (this.state.callbacks.onStart) {
            this.state.callbacks.onStart();
        }
        
        return this;
    },

    /**
     * Stop the automatic checking
     */
    stop: function() {
        if (this.state.intervalId) {
            clearInterval(this.state.intervalId);
            this.state.intervalId = null;
        }
        
        this.state.isRunning = false;
        this.log('Auto Load Checker stopped');
        
        // Callback
        if (this.state.callbacks.onStop) {
            this.state.callbacks.onStop();
        }
        
        return this;
    },

    /**
     * Check student status and update if needed
     */
    checkStudentStatus: async function() {
        try {
            this.log('Checking student status...');
            
            const response = await fetch(this.config.endpoint + '?action=check');
            const data = await response.json();
            
            if (data.success) {
                this.state.lastCheck = new Date();
                
                if (data.updated_count > 0) {
                    this.state.totalUpdated += data.updated_count;
                    this.log(`Updated ${data.updated_count} student(s)`);
                    
                    // Show notification
                    if (this.config.showNotifications) {
                        this.showNotification(
                            `${data.updated_count} student(s) completed their program and are now available for enrollment.`,
                            'success'
                        );
                    }
                    
                    // Callback
                    if (this.state.callbacks.onUpdate) {
                        this.state.callbacks.onUpdate(data);
                    }
                    
                    // Dispatch custom event
                    this.dispatchEvent('studentsUpdated', data);
                } else {
                    this.log('No students to update');
                }
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
            
            return data;
        } catch (error) {
            this.log('Error checking student status: ' + error.message, 'error');
            
            if (this.state.callbacks.onError) {
                this.state.callbacks.onError(error);
            }
            
            // Show error notification
            if (this.config.showNotifications) {
                this.showNotification('Error checking student status: ' + error.message, 'error');
            }
            
            return { success: false, error: error.message };
        }
    },

    /**
     * Get system statistics
     */
    getStats: async function() {
        try {
            const response = await fetch(this.config.endpoint + '?action=stats');
            const data = await response.json();
            return data;
        } catch (error) {
            this.log('Error getting stats: ' + error.message, 'error');
            return { success: false, error: error.message };
        }
    },

    /**
     * Check system health
     */
    checkHealth: async function() {
        try {
            const response = await fetch(this.config.endpoint + '?action=health');
            const data = await response.json();
            return data;
        } catch (error) {
            this.log('Health check failed: ' + error.message, 'error');
            return { success: false, error: error.message };
        }
    },

    /**
     * Show notification to user
     */
    showNotification: function(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'auto-checker-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            max-width: 350px;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            cursor: pointer;
            background: ${this.getNotificationColor(type)};
        `;
        
        // Add icon and message
        const icon = this.getNotificationIcon(type);
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 16px;">${icon}</span>
                <span>${message}</span>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Click to dismiss
        notification.onclick = () => this.removeNotification(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            this.removeNotification(notification);
        }, 5000);
    },

    /**
     * Remove notification
     */
    removeNotification: function(notification) {
        if (notification && notification.parentNode) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    },

    /**
     * Get notification color based on type
     */
    getNotificationColor: function(type) {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        return colors[type] || colors.info;
    },

    /**
     * Get notification icon based on type
     */
    getNotificationIcon: function(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    },

    /**
     * Dispatch custom event
     */
    dispatchEvent: function(eventName, data) {
        const event = new CustomEvent('autoChecker:' + eventName, {
            detail: data,
            bubbles: true
        });
        document.dispatchEvent(event);
    },

    /**
     * Log messages (only if debug mode is enabled)
     */
    log: function(message, level = 'info') {
        if (this.config.debugMode) {
            const timestamp = new Date().toLocaleTimeString();
            const prefix = '[Auto Checker ' + timestamp + ']';
            
            switch(level) {
                case 'error':
                    console.error(prefix, message);
                    break;
                case 'warn':
                    console.warn(prefix, message);
                    break;
                default:
                    console.log(prefix, message);
            }
        }
    },

    /**
     * Get current status
     */
    getStatus: function() {
        return {
            isRunning: this.state.isRunning,
            lastCheck: this.state.lastCheck,
            totalUpdated: this.state.totalUpdated,
            interval: this.config.interval,
            endpoint: this.config.endpoint
        };
    },

    /**
     * Update configuration
     */
    updateConfig: function(newConfig) {
        const wasRunning = this.state.isRunning;
        
        if (wasRunning) {
            this.stop();
        }
        
        this.config = Object.assign(this.config, newConfig);
        
        if (wasRunning) {
            this.start();
        }
        
        this.log('Configuration updated');
        return this;
    }
};

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // You can auto-start here if needed
        // AutoLoadChecker.init({ autoStart: true });
    });
} else {
    // DOM is already ready
    // AutoLoadChecker.init({ autoStart: true });
}

// Export for different module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AutoLoadChecker;
}
if (typeof window !== 'undefined') {
    window.AutoLoadChecker = AutoLoadChecker;
}

/* 
USAGE EXAMPLES:

// Basic initialization
AutoLoadChecker.init();

// With custom options
AutoLoadChecker.init({
    interval: 2 * 60 * 1000, // 2 minutes
    autoStart: true,
    showNotifications: true,
    debugMode: true,
    onUpdate: function(data) {
        console.log('Students updated:', data);
        // Refresh your UI here
    },
    onError: function(error) {
        console.error('Checker error:', error);
    }
});

// Manual control
AutoLoadChecker.start();
AutoLoadChecker.stop();
AutoLoadChecker.checkStudentStatus();

// Listen for custom events
document.addEventListener('autoChecker:studentsUpdated', function(event) {
    console.log('Students updated event:', event.detail);
    // Handle the update in your application
});

// Get system stats
AutoLoadChecker.getStats().then(stats => {
    console.log('System stats:', stats);
});

// Check system health
AutoLoadChecker.checkHealth().then(health => {
    console.log('System health:', health);
});
*/