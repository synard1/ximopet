<div class="ai-chat-bubble-container" wire:key="ai-chat-bubble-container">
    <!-- Debug information -->
    <div style="display: none;">
        Chat Bubble Component Loaded
        Auth Check: {{ Auth::check() ? 'Authenticated' : 'Not Authenticated' }}
        User ID: {{ Auth::check() ? Auth::id() : 'N/A' }}
        Is Open: {{ $isOpen ? 'Yes' : 'No' }}
        Is Minimized: {{ $isMinimized ? 'Yes' : 'No' }}
    </div>
    
    <!-- Always show a visible debug element when authenticated -->
    @auth
    <div style="position: fixed; top: 0; left: 0; background: red; color: white; padding: 5px; font-size: 12px; z-index: 9999999;">
        AI Chat V2 Debug: Auth={{ Auth::check() ? 'Yes' : 'No' }}, User={{ Auth::check() ? Auth::id() : 'None' }}
    </div>
    @endauth
    
    <!-- Chat Bubble Button -->
    @if(!$isOpen && !$isMinimized)
        <button wire:click="toggleBubble" 
                class="ai-chat-bubble"
                aria-label="Open AI Chat">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
        </button>
    @endif

    <!-- Minimized Chat -->
    @if($isMinimized)
        <div class="ai-chat-window">
            <div class="ai-chat-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">AI Assistant</span>
                </div>
                <div class="flex space-x-1">
                    <button wire:click="toggleBubble" 
                            class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Expand chat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <button wire:click="openFullChat" 
                            class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Open full chat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Open Chat Window -->
    @if($isOpen)
        <div class="ai-chat-window">
            <!-- Chat Header -->
            <div class="ai-chat-header">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">AI Assistant</span>
                </div>
                <div class="flex space-x-1">
                    <button wire:click="minimizeBubble" 
                            class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Minimize chat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </button>
                    <button wire:click="openFullChat" 
                            class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Open full chat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"></path>
                        </svg>
                    </button>
                    <button wire:click="toggleBubble" 
                            class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Close chat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="ai-chat-messages">
                @if(count($messages) > 0)
                    @foreach($messages as $message)
                        <div class="mb-3 {{ $message['role'] === 'user' ? 'text-right' : '' }}">
                            <div class="inline-block max-w-full {{ $message['role'] === 'user' ? 'bg-blue-500 text-white rounded-l-lg rounded-tr-lg' : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-r-lg rounded-tl-lg' }} p-2 text-sm">
                                @if($message['role'] === 'assistant')
                                    <div class="flex items-center mb-1">
                                        <div class="w-5 h-5 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center mr-1">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI</span>
                                    </div>
                                @endif
                                <div class="prose prose-xs dark:prose-invert max-w-none">
                                    {{ $message['content'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">How can I help you today?</p>
                    </div>
                @endif

                @if($isLoading)
                    <div class="mb-3">
                        <div class="inline-block bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-r-lg rounded-tl-lg p-2">
                            <div class="flex items-center">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center mr-1">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI</span>
                            </div>
                            <div class="flex space-x-1 mt-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Chat Input -->
            <div class="ai-chat-input-container">
                @if($error)
                    <div class="mb-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-700 dark:text-red-300">
                        {{ $error }}
                    </div>
                @endif

                <form wire:submit.prevent="sendMessage" class="flex items-center space-x-2">
                    <input wire:model="currentMessage"
                           type="text"
                           placeholder="Type a message..."
                           class="ai-chat-input"
                           wire:keydown.enter.prevent="sendMessage">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="ai-chat-send-button"
                            aria-label="Send message">
                        @if($isLoading)
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                            </svg>
                        @endif
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>