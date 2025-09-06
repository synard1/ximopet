/**
 * AI Chat State Manager
 * Helps manage the state of the chat widget across page loads
 * Provides more robust control over the chat display behavior
 */

const AiChatStateManager = {
    // Local storage keys
    STORAGE_KEY_CHAT_STATE: 'ximopet_chat_state',
    STORAGE_KEY_LAST_CLOSED: 'ximopet_chat_last_closed',

    /**
     * Initialize the state manager
     */
    init() {
        // Don't force chat closed on page load, allow the component to control its state

        // Set up event listeners for chat state changes
        document.addEventListener('DOMContentLoaded', () => {
            // Add listeners for chat state events
            window.addEventListener('chat-widget-open', () => {
                this.saveState(true);
                // Add show-chat-window class to ensure visibility
                setTimeout(() => {
                    const chatWindow = document.querySelector('.chat-window');
                    if (chatWindow) {
                        chatWindow.classList.add('show-chat-window');
                    }

                    // Ensure the widget has the chat-open class
                    // Try to find the chat widget element
                    const widget = document.querySelector('.ai-chat-widget') || document.querySelector('[wire\\:id][wire\\:initial-data*="ai-chat-widget"]');
                    if (widget) {
                        widget.classList.add('chat-open');
                    }
                }, 10);
            });

            window.addEventListener('chat-widget-close', () => {
                this.saveState(false);
                this.saveLastClosed();
                // Remove show-chat-window class
                const chatWindow = document.querySelector('.chat-window');
                if (chatWindow) {
                    chatWindow.classList.remove('show-chat-window');
                }

                // Ensure the widget removes the chat-open class
                // Try to find the chat widget element
                const widget = document.querySelector('.ai-chat-widget') || document.querySelector('[wire\\:id][wire\\:initial-data*="ai-chat-widget"]');
                if (widget) {
                    widget.classList.remove('chat-open');
                }
            });

            // Add click handler for chat bubble
            const chatBubble = document.querySelector('.chat-toggle-btn');
            if (chatBubble) {
                chatBubble.addEventListener('click', () => {
                    // Add direct event listener to ensure chat opens
                    setTimeout(() => {
                        if (window.Livewire) {
                            try {
                                // Dispatch the open event to trigger Livewire methods
                                window.dispatchEvent(new CustomEvent('chat-widget-open'));
                            } catch (e) {
                                console.error('Error ensuring chat is open:', e);
                            }
                        }
                    }, 100);
                });
            }
        });

        console.log('AI Chat State Manager initialized');
    },

    /**
     * Save the current state of the chat (open/closed)
     */
    saveState(isOpen) {
        try {
            localStorage.setItem(this.STORAGE_KEY_CHAT_STATE, JSON.stringify({
                isOpen: isOpen,
                timestamp: new Date().getTime()
            }));
        } catch (e) {
            console.error('Failed to save chat state:', e);
        }
    },

    /**
     * Get the saved state of the chat
     */
    getState() {
        try {
            const savedState = localStorage.getItem(this.STORAGE_KEY_CHAT_STATE);
            if (savedState) {
                return JSON.parse(savedState);
            }
        } catch (e) {
            console.error('Failed to get chat state:', e);
        }

        return {
            isOpen: false,
            timestamp: 0
        };
    },

    /**
     * Save the timestamp when the chat was last closed
     */
    saveLastClosed() {
        try {
            localStorage.setItem(this.STORAGE_KEY_LAST_CLOSED, new Date().getTime());
        } catch (e) {
            console.error('Failed to save last closed timestamp:', e);
        }
    },

    /**
     * Get the timestamp when the chat was last closed
     */
    getLastClosed() {
        try {
            return parseInt(localStorage.getItem(this.STORAGE_KEY_LAST_CLOSED) || '0');
        } catch (e) {
            console.error('Failed to get last closed timestamp:', e);
            return 0;
        }
    },

    /**
     * Check if the chat should be shown based on user preferences and timing
     */
    shouldAutoOpen() {
        // Check if auto-open is enabled in config
        const autoOpenEnabled = window.aiChatConfig?.autoOpen === true;

        if (!autoOpenEnabled) {
            return false;
        }

        // Get the last closed timestamp
        const lastClosed = this.getLastClosed();

        // If never closed before, or closed more than 30 minutes ago, auto-open
        const thirtyMinutesInMs = 30 * 60 * 1000;
        const now = new Date().getTime();

        return lastClosed === 0 || (now - lastClosed) > thirtyMinutesInMs;
    },

    /**
     * Force update the UI state
     */
    forceUpdate(isOpen) {
        // Try to find the chat widget element
        const chatWidget = document.querySelector('.ai-chat-widget') || document.querySelector('[wire\\:id][wire\\:initial-data*="ai-chat-widget"]');
        if (!chatWidget) return;

        if (isOpen) {
            chatWidget.classList.add('chat-open');

            // Ensure chat window is visible
            const chatWindow = chatWidget.querySelector('.chat-window');
            if (chatWindow) {
                chatWindow.classList.add('show-chat-window');
            }
        } else {
            chatWidget.classList.remove('chat-open');

            // Ensure chat bubble is visible
            const chatBubble = chatWidget.querySelector('.chat-bubble-wrapper');
            if (chatBubble) {
                chatBubble.style.display = 'block';
            }
        }

        // Dispatch custom event for other scripts
        window.dispatchEvent(new CustomEvent('chat-state-updated', {
            detail: { isOpen }
        }));
    }
};

// Initialize the state manager
AiChatStateManager.init();

// Expose to global scope
window.AiChatStateManager = AiChatStateManager;
