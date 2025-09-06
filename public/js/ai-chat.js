/**
 * AI Chat Widget JavaScript
 * Enhances the Livewire chat component with additional functionality
 */

class AiChatManager {
    constructor() {
        this.isInitialized = false;
        this.websocketConnection = null;
        this.notificationPermission = null;
        this.settings = {
            sounds: true,
            notifications: true,
            autoScroll: true,
            theme: 'auto'
        };

        this.init();
    }

    /**
     * Initialize the chat manager
     */
    init() {
        if (this.isInitialized) return;

        this.loadSettings();
        this.setupEventListeners();
        this.requestNotificationPermission();
        this.initializeKeyboardShortcuts();
        this.setupAutoReconnect();

        this.isInitialized = true;
        console.log('AI Chat Manager initialized');
    }

    /**
     * Load settings from localStorage
     */
    loadSettings() {
        const savedSettings = localStorage.getItem('ai-chat-settings');
        if (savedSettings) {
            this.settings = { ...this.settings, ...JSON.parse(savedSettings) };
        }
    }

    /**
     * Save settings to localStorage
     */
    saveSettings() {
        localStorage.setItem('ai-chat-settings', JSON.stringify(this.settings));
    }

    /**
     * Setup event listeners for chat interactions
     */
    setupEventListeners() {
        // Listen for Livewire events
        document.addEventListener('livewire:initialized', () => {
            this.bindLivewireEvents();
        });

        // Window focus/blur for notification management
        window.addEventListener('focus', () => {
            this.clearNotifications();
        });

        // Visibility change for better resource management
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
            }
        });

        // Theme change detection
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.settings.theme === 'auto') {
                    this.updateTheme();
                }
            });
        }

        // Listen for chat state updates from the state manager
        window.addEventListener('chat-state-updated', (event) => {
            const { isOpen } = event.detail;
            if (isOpen) {
                this.handleChatOpened();
            } else {
                this.handleChatClosed();
            }
        });
    }

    /**
     * Bind Livewire-specific events
     */
    bindLivewireEvents() {
        // Message sent successfully
        window.addEventListener('message-sent', (event) => {
            this.handleMessageSent(event.detail);
        });

        // Chat opened
        window.addEventListener('chat-opened', () => {
            this.handleChatOpened();
        });

        // Chat closed
        window.addEventListener('chat-closed', () => {
            this.handleChatClosed();
        });

        // Error occurred
        window.addEventListener('chat-error', (event) => {
            this.handleChatError(event.detail);
        });

        // Success message
        window.addEventListener('chat-success', (event) => {
            this.handleChatSuccess(event.detail);
        });

        // Export chat
        window.addEventListener('export-chat', (event) => {
            this.handleChatExport(event.detail);
        });
    }

    /**
     * Handle message sent event
     */
    handleMessageSent(data) {
        if (this.settings.sounds) {
            this.playSound('send');
        }

        // Handle processing_time safely - it might be undefined in case of errors
        const processingTime = data.processing_time;
        let toastMessage = 'Message sent successfully';
        
        if (processingTime !== undefined && processingTime !== null) {
            toastMessage = `Message sent in ${processingTime.toFixed(2)}s`;
        }
        
        this.showToast(toastMessage, 'success');
        this.scrollToBottom();

        // Track analytics
        this.trackEvent('message_sent', {
            context_type: data.context_type,
            processing_time: processingTime
        });
    }

    /**
     * Handle chat opened event
     */
    handleChatOpened() {
        if (this.settings.sounds) {
            this.playSound('open');
        }

        this.trackEvent('chat_opened');

        // Focus message input
        setTimeout(() => {
            try {
                // Fix for the invalid selector error - use a simple class selector instead
                const messageInput = document.querySelector('.chat-textarea');
                if (messageInput) {
                    messageInput.focus();
                }
            } catch (e) {
                console.error('Error focusing on message input:', e);
            }
        }, 300);
    }

    /**
     * Handle chat closed event
     */
    handleChatClosed() {
        if (this.settings.sounds) {
            this.playSound('close');
        }

        this.trackEvent('chat_closed');
    }

    /**
     * Handle chat error event
     */
    handleChatError(message) {
        if (this.settings.sounds) {
            this.playSound('error');
        }

        this.showToast(message, 'error');
        this.trackEvent('chat_error', { message });
    }

    /**
     * Handle chat success event
     */
    handleChatSuccess(message) {
        if (this.settings.sounds) {
            this.playSound('success');
        }

        this.showToast(message, 'success');
    }

    /**
     * Handle chat export event
     */
    handleChatExport(data) {
        this.exportChatData(data.session, data.messages);
    }

    /**
     * Play notification sound
     */
    playSound(type) {
        if (!this.settings.sounds) return;

        try {
            // Create audio context for better browser support
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();

            // Define sound frequencies for different types
            const frequencies = {
                send: [800, 1000],
                receive: [600, 800],
                open: [400, 600, 800],
                close: [800, 600, 400],
                error: [300, 200],
                success: [600, 800, 1000]
            };

            const freq = frequencies[type] || frequencies.receive;
            this.playTone(audioContext, freq);

        } catch (error) {
            console.warn('Could not play sound:', error);
        }
    }

    /**
     * Play tone sequence
     */
    playTone(audioContext, frequencies) {
        frequencies.forEach((freq, index) => {
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.2);

            const startTime = audioContext.currentTime + (index * 0.15);
            oscillator.start(startTime);
            oscillator.stop(startTime + 0.2);
        });
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            // Use SweetAlert2 if available
            Swal.fire({
                title: this.getToastTitle(type),
                text: message,
                icon: this.getToastIcon(type),
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            // Fallback to browser notification or console
            this.showBrowserNotification(message, type);
        }
    }

    /**
     * Get toast title based on type
     */
    getToastTitle(type) {
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Info'
        };
        return titles[type] || 'Notification';
    }

    /**
     * Get toast icon based on type
     */
    getToastIcon(type) {
        const icons = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };
        return icons[type] || 'info';
    }

    /**
     * Show browser notification
     */
    showBrowserNotification(message, type = 'info') {
        if (!this.settings.notifications || this.notificationPermission !== 'granted') {
            console.log(`[${type.toUpperCase()}] ${message}`);
            return;
        }

        const notification = new Notification('AI Chat', {
            body: message,
            icon: '/favicon.ico',
            tag: 'ai-chat',
            badge: '/favicon.ico'
        });

        notification.onclick = () => {
            window.focus();
            notification.close();
        };

        // Auto-close after 5 seconds
        setTimeout(() => {
            notification.close();
        }, 5000);
    }

    /**
     * Request notification permission
     */
    async requestNotificationPermission() {
        if ('Notification' in window) {
            this.notificationPermission = await Notification.requestPermission();
        }
    }

    /**
     * Clear all notifications
     */
    clearNotifications() {
        // This would clear any persistent notifications
        // Implementation depends on notification system used
    }

    /**
     * Initialize keyboard shortcuts
     */
    initializeKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            // Ctrl/Cmd + Shift + C to toggle chat
            if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key === 'C') {
                event.preventDefault();
                this.toggleChat();
            }

            // Escape to close chat
            if (event.key === 'Escape') {
                // Try to find the chat widget element
                const chatWidget = document.querySelector('.ai-chat-widget') || document.querySelector('[wire\\:id][wire\\:initial-data*="ai-chat-widget"]');
                if (chatWidget && this.isChatOpen()) {
                    this.closeChat();
                }
            }
        });
    }

    /**
     * Toggle chat widget
     */
    toggleChat() {
        // Dispatch the appropriate event based on current state
        // Try to find the chat widget element
        const chatWidget = document.querySelector('.ai-chat-widget') || document.querySelector('[wire\\:id][wire\\:initial-data*="ai-chat-widget"]');
        if (chatWidget && chatWidget.classList.contains('chat-open')) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    /**
     * Open chat widget
     */
    openChat() {
        window.dispatchEvent(new CustomEvent('chat-widget-open'));
    }

    /**
     * Close chat widget
     */
    closeChat() {
        window.dispatchEvent(new CustomEvent('chat-widget-close'));
    }

    /**
     * Check if chat is open
     */
    isChatOpen() {
        // Try to find the chat widget element
        const chatWidget = document.querySelector('.ai-chat-widget') || document.querySelector('[wire\\:id][wire\\:initial-data*="ai-chat-widget"]');
        return chatWidget && chatWidget.classList.contains('chat-open');
    }

    /**
     * Scroll to bottom of messages
     */
    scrollToBottom() {
        if (!this.settings.autoScroll) return;

        setTimeout(() => {
            const messagesContainer = document.querySelector('.chat-messages');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }, 100);
    }

    /**
     * Export chat data
     */
    exportChatData(session, messages) {
        try {
            const exportData = {
                session: session,
                messages: messages,
                exported_at: new Date().toISOString(),
                export_version: '1.0'
            };

            const dataStr = JSON.stringify(exportData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

            const exportFileDefaultName = `chat-session-${session.id}-${new Date().toISOString().split('T')[0]}.json`;

            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();

            this.showToast('Chat exported successfully', 'success');

        } catch (error) {
            console.error('Export failed:', error);
            this.showToast('Export failed', 'error');
        }
    }

    /**
     * Update theme
     */
    updateTheme() {
        const isDark = this.settings.theme === 'dark' ||
                      (this.settings.theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
    }

    /**
     * Setup auto-reconnect for websockets (future enhancement)
     */
    setupAutoReconnect() {
        // Placeholder for websocket auto-reconnect logic
        // This can be implemented when adding real-time features
    }

    /**
     * Pause updates when tab is not visible
     */
    pauseUpdates() {
        // Pause any polling or real-time updates
        // to save resources when tab is not visible
    }

    /**
     * Resume updates when tab becomes visible
     */
    resumeUpdates() {
        // Resume any polling or real-time updates
    }

    /**
     * Track analytics events
     */
    trackEvent(eventName, data = {}) {
        // Integration with analytics systems
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'ai_chat',
                ...data
            });
        }

        // Console log for debugging
        console.log(`[Analytics] ${eventName}:`, data);
    }

    /**
     * Update settings
     */
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.saveSettings();

        // Apply theme if changed
        if (newSettings.theme) {
            this.updateTheme();
        }
    }

    /**
     * Get current settings
     */
    getSettings() {
        return { ...this.settings };
    }
}

// Initialize the chat manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.aiChatManager = new AiChatManager();
    });
} else {
    window.aiChatManager = new AiChatManager();
}

// Global utility functions
window.aiChatUtils = {
    /**
     * Send message programmatically
     */
    sendMessage: function(message) {
        window.dispatchEvent(new CustomEvent('chat-send-message', {
            detail: message
        }));
    },

    /**
     * Open chat with optional message
     */
    openChatWithMessage: function(message = null) {
        window.dispatchEvent(new CustomEvent('chat-widget-open'));

        if (message) {
            setTimeout(() => {
                this.sendMessage(message);
            }, 500);
        }
    },

    /**
     * Copy text to clipboard
     */
    copyToClipboard: function(text) {
        return navigator.clipboard.writeText(text).then(() => {
            if (window.aiChatManager) {
                window.aiChatManager.showToast('Copied to clipboard', 'success');
            }
            return true;
        }).catch(err => {
            console.error('Failed to copy text:', err);
            if (window.aiChatManager) {
                window.aiChatManager.showToast('Failed to copy text', 'error');
            }
            return false;
        });
    },

    /**
     * Format message with syntax highlighting (basic)
     */
    formatMessage: function(message) {
        // Basic markdown-like formatting
        return message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }
};

// Expose for global access
window.copyToClipboard = window.aiChatUtils.copyToClipboard;
