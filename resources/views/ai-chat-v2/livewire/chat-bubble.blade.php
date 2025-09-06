<div>
    <div class="fixed bottom-4 right-4 z-50">
        <!-- Chat Bubble Button -->
        <div class="relative">
            @if(!$isOpen && !$isMinimized)
                <button 
                    wire:click="toggleBubble" 
                    class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg transition-all duration-300 hover:scale-110"
                    title="Open AI Chat"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-3.774-.9L3 21l1.9-6.226A8.955 8.955 0 013 12a8 8 0 018-8 8 8 0 018 8z"></path>
                    </svg>
                    
                    <!-- Notification Badge -->
                    @if(count($messages) > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ min(count($messages), 9) }}{{ count($messages) > 9 ? '+' : '' }}
                        </span>
                    @endif
                </button>
            @endif
            
            <!-- Minimized State -->
            @if($isMinimized)
                <button 
                    wire:click="toggleBubble" 
                    class="bg-gray-600 hover:bg-gray-700 text-white rounded-full p-3 shadow-lg transition-all duration-300"
                    title="Restore Chat"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-3.774-.9L3 21l1.9-6.226A8.955 8.955 0 013 12a8 8 0 018-8 8 8 0 018 8z"></path>
                    </svg>
                </button>
            @endif
        </div>
        
        <!-- Chat Window -->
        @if($isOpen)
            <div class="bg-white rounded-lg shadow-2xl w-80 h-96 mb-4 flex flex-col border border-gray-200 animate-in slide-in-from-bottom-2 duration-300">
                <!-- Header -->
                <div class="bg-blue-600 text-white p-3 rounded-t-lg flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-3.774-.9L3 21l1.9-6.226A8.955 8.955 0 013 12a8 8 0 018-8 8 8 0 018 8z"></path>
                        </svg>
                        <span class="font-medium text-sm">AI Chat</span>
                    </div>
                    
                    <div class="flex items-center space-x-1">
                        <!-- Open in New Window -->
                        <button 
                            wire:click="openInNewWindow" 
                            class="text-white hover:text-gray-200 p-1 rounded transition-colors"
                            title="Open in new window"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </button>
                        
                        <!-- Minimize -->
                        <button 
                            wire:click="minimizeBubble" 
                            class="text-white hover:text-gray-200 p-1 rounded transition-colors"
                            title="Minimize"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        
                        <!-- Close -->
                        <button 
                            wire:click="toggleBubble" 
                            class="text-white hover:text-gray-200 p-1 rounded transition-colors"
                            title="Close"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50">
                    @if($error)
                        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">
                            {{ $error }}
                        </div>
                    @endif
                    
                    @if(empty($messages))
                        <div class="text-center text-gray-500 text-sm mt-8">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-3.774-.9L3 21l1.9-6.226A8.955 8.955 0 013 12a8 8 0 018-8 8 8 0 018 8z"></path>
                            </svg>
                            <p>Start a conversation!</p>
                            <p class="text-xs mt-1">Type a message below</p>
                        </div>
                    @else
                        @foreach($messages as $message)
                            <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-xs px-3 py-2 rounded-lg text-sm {{ $message['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 border' }}">
                                    {{ $message['content'] }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                    
                    @if($isLoading)
                        <div class="flex justify-start">
                            <div class="bg-white border rounded-lg px-3 py-2 text-sm">
                                <div class="flex items-center space-x-1">
                                    <div class="flex space-x-1">
                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                    </div>
                                    <span class="text-gray-500 text-xs ml-2">AI is typing...</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Input Area -->
                <div class="border-t border-gray-200 p-3">
                    <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                        <input 
                            type="text" 
                            wire:model="currentMessage" 
                            placeholder="Type your message..." 
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            {{ $isLoading ? 'disabled' : '' }}
                        >
                        <button 
                            type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg px-3 py-2 transition-colors"
                            {{ $isLoading || empty(trim($currentMessage)) ? 'disabled' : '' }}
                        >
                            @if($isLoading)
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            @endif
                        </button>
                    </form>
                    
                    <!-- Quick Actions -->
                    <div class="mt-2 text-center">
                        <button 
                            wire:click="openInNewWindow" 
                            class="text-blue-600 hover:text-blue-800 text-xs underline"
                        >
                            Open full chat for better experience
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Handle opening chat in new window
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-chat-window', (event) => {
                window.open(event.url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            });
        });
        
        // Auto-scroll messages in bubble
        document.addEventListener('livewire:updated', () => {
            const messagesContainer = document.querySelector('.overflow-y-auto');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        });
    </script>
</div>