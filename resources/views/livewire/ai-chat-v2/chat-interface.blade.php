<div class="flex flex-col h-full">
    <!-- Chat Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">AI Assistant</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ask me anything about livestock management</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                @if($currentSessionId)
                    <button wire:click="exportChat"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                @endif
                <button wire:click="clearChat"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900" id="chat-messages">
        @if($isLoading && count($messages) === 0)
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-400">Connecting to AI assistant...</p>
                </div>
            </div>
        @elseif(count($messages) > 0)
            @foreach($messages as $message)
                <div class="mb-4 {{ $message['role'] === 'user' ? 'text-right' : '' }}">
                    <div class="inline-block max-w-3/4 {{ $message['role'] === 'user' ? 'bg-blue-500 text-white rounded-l-lg rounded-tr-lg' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-r-lg rounded-tl-lg' }} p-4 shadow-sm">
                        @if($message['role'] === 'assistant')
                            <div class="flex items-center mb-2">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center mr-2">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI Assistant</span>
                            </div>
                        @endif
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            @if(isset($message['content']))
                                {!! nl2br(e($message['content'])) !!}
                            @endif
                        </div>
                        @if($message['role'] === 'user')
                            <div class="flex items-center justify-end mt-2">
                                <span class="text-xs text-gray-300">{{ $message['created_at'] ?? '' }}</span>
                                <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center ml-2">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($isTyping)
                <div class="mb-4">
                    <div class="inline-block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-r-lg rounded-tl-lg p-4 shadow-sm">
                        <div class="flex items-center">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center mr-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI Assistant</span>
                        </div>
                        <div class="flex space-x-1 mt-2">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="flex flex-col items-center justify-center h-full text-center p-8">
                <div class="w-16 h-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">How can I help you today?</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Ask me anything about livestock management, feed inventory, health monitoring, or production tracking.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full max-w-2xl">
                    <button wire:click="sendQuickPrompt('What should I feed my chickens today?')"
                            class="text-left p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="font-medium text-gray-900 dark:text-white">Feeding Recommendations</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Get advice on feed types and schedules</div>
                    </button>
                    <button wire:click="sendQuickPrompt('How do I check for common poultry diseases?')"
                            class="text-left p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="font-medium text-gray-900 dark:text-white">Health Monitoring</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Learn about disease prevention</div>
                    </button>
                    <button wire:click="sendQuickPrompt('What are the best practices for egg production?')"
                            class="text-left p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="font-medium text-gray-900 dark:text-white">Production Optimization</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Maximize your output efficiently</div>
                    </button>
                    <button wire:click="sendQuickPrompt('Help me analyze my financial reports')"
                            class="text-left p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="font-medium text-gray-900 dark:text-white">Financial Analysis</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Understand your business metrics</div>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Chat Input -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        @if($error)
            <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 dark:text-red-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
                        <div class="mt-1 text-sm text-red-700 dark:text-red-300">
                            <p>{{ $error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="sendMessage" class="flex items-end space-x-3">
            <div class="flex-1">
                <textarea wire:model.defer="message"
                          placeholder="Type your message here..."
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                          rows="1"
                          style="min-height: 56px;"
                          wire:keydown.enter.prevent="sendMessage"
                          wire:keydown.shift.enter="insertNewLine"></textarea>
            </div>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                @if($isLoading)
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                @endif
            </button>
        </form>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Press Enter to send, Shift+Enter for new line
        </div>
    </div>
</div>

<script>
    // Auto-scroll to bottom of chat messages
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });

    // Scroll to bottom when new messages are added
    document.addEventListener('livewire:load', function() {
        Livewire.hook('message.processed', (message, component) => {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    });
</script>
