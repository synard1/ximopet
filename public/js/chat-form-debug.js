/**
 * Chat Form Debug Script
 * Helps diagnose and fix issues with chat bubble and form visibility
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Chat Debug: Initializing chat form debug script');

    // Monitor chat state changes
    if (window.Livewire) {
        console.log('🔍 Chat Debug: Livewire detected');

        // Wait for Livewire to be fully initialized
        setTimeout(function() {
            try {
                // Try different methods to find the Livewire component
                let chatWidget = window.Livewire.find('ai-chat-widget');

                // If not found by ID, try to find by component name
                if (!chatWidget) {
                    const components = window.Livewire.components;
                    if (components && components.components) {
                        chatWidget = Object.values(components.components).find(comp =>
                            comp.fingerprint && comp.fingerprint.name === 'ai-chat-widget'
                        );
                    }
                }
                if (chatWidget) {
                    console.log('🔍 Chat Debug: Found chat widget component', { isOpen: chatWidget.isOpen });

                    // Add manual click handler to the bubble
                    const chatBubble = document.querySelector('.chat-toggle-btn');
                    if (chatBubble) {
                        console.log('🔍 Chat Debug: Found chat bubble button');

                        // Add direct click handler
                        chatBubble.addEventListener('click', function(e) {
                            console.log('🔍 Chat Debug: Chat bubble clicked');

                            // Force chat window to show after a slight delay
                            setTimeout(function() {
                                try {
                                    // Direct DOM manipulation to force visibility
                                    const chatWindow = document.querySelector('.chat-window');
                                    if (chatWindow) {
                                        console.log('🔍 Chat Debug: Forcing chat window visible');
                                        chatWindow.style.display = 'flex';
                                        chatWindow.classList.add('show-chat-window');

                                        // Also try to dispatch event to Livewire
                                        window.dispatchEvent(new CustomEvent('chat-widget-open'));

                                        // Set Livewire property directly if possible
                                        let chatWidget = window.Livewire.find('ai-chat-widget');

                                        // If not found by ID, try to find by component name
                                        if (!chatWidget) {
                                            const components = window.Livewire.components;
                                            if (components && components.components) {
                                                chatWidget = Object.values(components.components).find(comp =>
                                                    comp.fingerprint && comp.fingerprint.name === 'ai-chat-widget'
                                                );
                                            }
                                        }

                                        if (chatWidget) {
                                            try {
                                                chatWidget.set('isOpen', true);
                                                console.log('🔍 Chat Debug: Set isOpen to true via Livewire');
                                            } catch (e) {
                                                console.error('🔍 Chat Debug: Error setting isOpen via Livewire', e);
                                            }
                                        }
                                    } else {
                                        console.error('🔍 Chat Debug: Chat window not found');
                                    }
                                } catch (err) {
                                    console.error('🔍 Chat Debug: Error in bubble click handler', err);
                                }
                            }, 200);
                        });
                    } else {
                        console.error('🔍 Chat Debug: Chat bubble not found');
                    }

                    // Monitor when window becomes visible
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.attributeName === 'style' ||
                                mutation.attributeName === 'class') {
                                const chatWindow = document.querySelector('.chat-window');
                                if (chatWindow) {
                                    const isVisible = window.getComputedStyle(chatWindow).display !== 'none';
                                    console.log('🔍 Chat Debug: Chat window visibility changed:', isVisible);
                                }
                            }
                        });
                    });

                    // Start observing the chat window
                    const chatWindow = document.querySelector('.chat-window');
                    if (chatWindow) {
                        observer.observe(chatWindow, { attributes: true });
                        console.log('🔍 Chat Debug: Observing chat window visibility');
                    }

                } else {
                    console.error('🔍 Chat Debug: Chat widget component not found');
                }
            } catch (e) {
                console.error('🔍 Chat Debug: Error initializing debug script', e);
            }
        }, 1000);
    } else {
        console.error('🔍 Chat Debug: Livewire not available');
    }
});
