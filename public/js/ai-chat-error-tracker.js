/**
 * AI Chat Error Tracking Utility
 * This script helps track and diagnose errors in the AI Chat functionality
 */

class AiChatErrorTracker {
    constructor() {
        this.errorLog = [];
        this.init();
    }
    
    init() {
        console.log('📊 AI Chat Error Tracker initialized');
        this.setupErrorHandlers();
    }
    
    setupErrorHandlers() {
        // Global error handler
        window.addEventListener('error', (event) => {
            this.logError('Global error', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error ? event.error.message : null,
                stack: event.error ? event.error.stack : null
            });
            
            // Check for specific errors and provide recovery suggestions
            if (event.error && event.error.message && event.error.message.includes("Cannot read properties of undefined (reading 'uri')")) {
                console.warn('📊 Detected Livewire URI error - this typically happens when Livewire is not properly initialized or the component ID is incorrect');
                this.suggestRecovery('uriError');
            }
        });
        
        // Promise rejection handler
        window.addEventListener('unhandledrejection', (event) => {
            this.logError('Unhandled Promise rejection', {
                reason: event.reason ? event.reason.message : 'Unknown reason',
                stack: event.reason ? event.reason.stack : null
            });
        });
        
        // Livewire specific error handling
        if (typeof Livewire !== 'undefined') {
            document.addEventListener('livewire:initialized', () => {
                Livewire.hook('message.failed', (message, error) => {
                    this.logError('Livewire message failed', {
                        message: message,
                        error: error
                    });
                    this.suggestRecovery('livewireMessageFailed');
                });
            });
        }
        
        // Regular ping to check component health
        setInterval(() => this.checkComponentHealth(), 5000);
    }
    
    logError(type, details) {
        const errorEntry = {
            type,
            details,
            timestamp: new Date().toISOString()
        };
        
        this.errorLog.push(errorEntry);
        console.error(`📊 AI Chat Error [${type}]:`, details);
        
        // Limit log size
        if (this.errorLog.length > 50) {
            this.errorLog.shift();
        }
    }
    
    checkComponentHealth() {
        try {
            const componentElement = document.querySelector('[wire\\:id]');
            if (!componentElement) return;
            
            const componentId = componentElement.getAttribute('wire:id');
            if (!componentId) return;
            
            // Check if component is available in Livewire
            if (typeof Livewire === 'undefined' || !Livewire.find(componentId)) {
                this.logError('Component health check failed', {
                    componentId,
                    reason: 'Component not found in Livewire'
                });
                this.suggestRecovery('componentNotFound');
            }
        } catch (e) {
            this.logError('Error in component health check', {
                error: e.message,
                stack: e.stack
            });
        }
    }
    
    suggestRecovery(errorType) {
        switch (errorType) {
            case 'uriError':
                console.info('📊 Recovery suggestion: Try using the direct API endpoint instead of Livewire calls');
                // Try to trigger the API fallback
                const sendButton = document.querySelector('.chat-send-btn');
                if (sendButton) {
                    sendButton.addEventListener('click', () => {
                        console.log('📊 Applying automatic recovery for URI error');
                        // The click event will be handled by the existing click handler
                    });
                }
                break;
                
            case 'livewireMessageFailed':
                console.info('📊 Recovery suggestion: Try refreshing the page or using the direct API endpoint');
                break;
                
            case 'componentNotFound':
                console.info('📊 Recovery suggestion: The Livewire component may need to be reinitialized. Try refreshing the page.');
                // Consider auto-refresh after multiple failures
                break;
                
            default:
                console.info('📊 General recovery suggestion: Try refreshing the page or using an alternative method');
        }
    }
    
    getErrorLog() {
        return [...this.errorLog];
    }
    
    clearErrorLog() {
        this.errorLog = [];
        console.log('📊 Error log cleared');
    }
}

// Initialize the error tracker
document.addEventListener('DOMContentLoaded', () => {
    window.aiChatErrorTracker = new AiChatErrorTracker();
});