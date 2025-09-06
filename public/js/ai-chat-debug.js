/**
 * AI Chat UI Debug Utility
 * This script helps diagnose and fix issues with the AI Chat UI
 */

class AiChatDebug {
    constructor() {
        this.initialized = false;
        this.init();
    }
    
    init() {
        if (this.initialized) return;
        
        console.log('🔧 AI Chat Debug Utility initialized');
        this.setupEventListeners();
        this.monitorChatState();
        
        this.initialized = true;
    }
    
    setupEventListeners() {
        // Listen for Livewire events
        if (typeof Livewire !== 'undefined') {
            document.addEventListener('livewire:initialized', () => {
                console.log('🔧 Livewire initialized, setting up debug listeners');
                
                // Listen for UI refresh events
                Livewire.on('ui-refresh', () => {
                    console.log('🔧 UI refresh event received');
                    this.verifyMessageDisplay();
                });
                
                // Listen for message sent events
                Livewire.on('message-sent', (data) => {
                    console.log('🔧 Message sent event received', data);
                    this.verifyMessageDisplay();
                });
            });
        }
        
        // Monitor form submission
        const chatForm = document.querySelector('.chat-input-container form');
        if (chatForm) {
            console.log('🔧 Monitoring chat form submission');
            chatForm.addEventListener('submit', (e) => {
                console.log('🔧 Form submit event detected');
            });
        }
        
        // Monitor send button clicks
        const sendButton = document.querySelector('.chat-send-btn');
        if (sendButton) {
            console.log('🔧 Monitoring send button clicks');
            sendButton.addEventListener('click', () => {
                console.log('🔧 Send button clicked');
                this.scheduleStateCheck();
            });
        }
    }
    
    monitorChatState() {
        // Create a mutation observer to monitor changes to the chat container
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            console.log('🔧 Setting up mutation observer for chat messages');
            
            const observer = new MutationObserver((mutations) => {
                console.log('🔧 Chat messages container mutated', mutations.length);
                this.verifyMessageDisplay();
            });
            
            observer.observe(chatMessages, { 
                childList: true, 
                subtree: true,
                characterData: true,
                attributes: true
            });
        }
    }
    
    verifyMessageDisplay() {
        const messagesContainer = document.querySelector('.chat-messages');
        if (!messagesContainer) return;
        
        const messageElements = messagesContainer.querySelectorAll('.message');
        console.log(`🔧 Message elements in DOM: ${messageElements.length}`);
        
        // Check if any user messages exist
        const userMessages = messagesContainer.querySelectorAll('.message-user');
        console.log(`🔧 User message elements: ${userMessages.length}`);
        
        // Check if any assistant messages exist
        const assistantMessages = messagesContainer.querySelectorAll('.message-assistant');
        console.log(`🔧 Assistant message elements: ${assistantMessages.length}`);
        
        // Ensure messages are properly visible
        this.ensureMessagesVisible();
    }
    
    scheduleStateCheck() {
        console.log('🔧 Scheduling state check');
        setTimeout(() => this.checkComponentState(), 500);
        setTimeout(() => this.checkComponentState(), 1000);
        setTimeout(() => this.checkComponentState(), 2000);
    }
    
    checkComponentState() {
        if (typeof Livewire === 'undefined') return;
        
        try {
            // Find the component
            const componentElement = document.querySelector('[wire\\:id]');
            if (componentElement) {
                const componentId = componentElement.getAttribute('wire:id');
                const component = Livewire.find(componentId);
                
                if (component) {
                    console.log('🔧 Component state:', {
                        messagesCount: component.get('messages').length,
                        isLoading: component.get('isLoading'),
                        isTyping: component.get('isTyping'),
                        newMessage: component.get('newMessage')
                    });
                    
                    // If component has messages but UI doesn't, force refresh
                    const messagesContainer = document.querySelector('.chat-messages');
                    const messageElements = messagesContainer ? messagesContainer.querySelectorAll('.message').length : 0;
                    
                    if (component.get('messages').length > 0 && messageElements === 0) {
                        console.log('🔧 Mismatch detected! Component has messages but UI doesn\'t, forcing refresh');
                        component.call('forceRefresh');
                    }
                }
            }
        } catch (e) {
            console.error('🔧 Error checking component state:', e);
        }
    }
    
    ensureMessagesVisible() {
        // Ensure all messages are visible
        const messageElements = document.querySelectorAll('.message');
        messageElements.forEach(message => {
            if (window.getComputedStyle(message).display === 'none') {
                console.log('🔧 Found hidden message, making visible', message);
                message.style.display = 'block';
            }
        });
        
        // Ensure chat window is scrolled to bottom
        const messagesContainer = document.querySelector('.chat-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
}

// Initialize the debug utility
document.addEventListener('DOMContentLoaded', () => {
    window.aiChatDebug = new AiChatDebug();
});