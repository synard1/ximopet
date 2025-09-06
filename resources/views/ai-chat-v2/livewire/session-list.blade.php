<div>
    <div class="h-full bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Chat Sessions</h3>
                <button wire:click="toggleArchived" 
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    {{ $showArchived ? 'Hide Archived' : 'Show Archived' }}
                </button>
            </div>
            
            <!-- Search -->
            <div class="relative">
                <input 
                    wire:model.debounce.300ms="searchTerm" 
                    wire:keydown.enter="searchSessions"
                    type="text" 
                    placeholder="Search sessions..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                @if($searchTerm)
                    <button wire:click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Error Message -->
        @if($error)
            <div class="mx-4 mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <div class="flex items-center justify-between">
                    <span class="text-sm">{{ $error }}</span>
                    <button wire:click="$set('error', null)" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif
        
        <!-- Sessions List -->
        <div class="flex-1 overflow-y-auto">
            @if($isLoading)
                <div class="flex items-center justify-center p-8">
                    <svg class="w-8 h-8 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            @elseif(empty($sessions))
                <div class="flex items-center justify-center p-8 text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-sm font-medium">No sessions found</p>
                        <p class="text-xs">{{ $searchTerm ? 'Try a different search term' : 'Start a new conversation' }}</p>
                    </div>
                </div>
            @else
                <div class="space-y-1 p-2">
                    @foreach($this->getFilteredSessions() as $session)
                        <div class="group relative">
                            <div 
                                wire:click="selectSession('{{ $session['id'] }}')"
                                class="flex items-center p-3 rounded-lg cursor-pointer transition-colors {{ $currentSessionId === $session['id'] ? 'bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }} {{ isset($session['is_archived']) && $session['is_archived'] ? 'opacity-60' : '' }}"
                            >
                                <div class="flex-1 min-w-0">
                                    <!-- Session Title -->
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $session['title'] ?? 'Untitled Session' }}
                                        </h4>
                                        @if(isset($session['is_archived']) && $session['is_archived'])
                                            <span class="ml-2 px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                                                Archived
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Session Summary/Preview -->
                                    @if(isset($session['summary']) && $session['summary'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                                            {{ $session['summary'] }}
                                        </p>
                                    @endif
                                    
                                    <!-- Session Meta -->
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center space-x-2 text-xs text-gray-400 dark:text-gray-500">
                                            <span>{{ $session['message_count'] ?? 0 }} messages</span>
                                            <span>•</span>
                                            <span>{{ \Carbon\Carbon::parse($session['updated_at'])->diffForHumans() }}</span>
                                        </div>
                                        
                                        @if(isset($session['provider']) && $session['provider'])
                                            <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded">
                                                {{ $session['provider'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Session Actions (visible on hover) -->
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="flex items-center space-x-1">
                                    <!-- Edit Title -->
                                    <button 
                                        onclick="editSessionTitle('{{ $session['id'] }}', '{{ addslashes($session['title'] ?? '') }}')"
                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                                        title="Edit title"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Export -->
                                    <button 
                                        wire:click="exportSession('{{ $session['id'] }}')"
                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                                        title="Export session"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Archive/Restore -->
                                    @if(isset($session['is_archived']) && $session['is_archived'])
                                        <button 
                                            wire:click="restoreSession('{{ $session['id'] }}')"
                                            class="p-1 text-green-400 hover:text-green-600 rounded"
                                            title="Restore session"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <button 
                                            wire:click="archiveSession('{{ $session['id'] }}')"
                                            class="p-1 text-yellow-400 hover:text-yellow-600 rounded"
                                            title="Archive session"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    <!-- Delete -->
                                    <button 
                                        onclick="confirmDeleteSession('{{ $session['id'] }}', '{{ addslashes($session['title'] ?? 'Untitled Session') }}')"
                                        class="p-1 text-red-400 hover:text-red-600 rounded"
                                        title="Delete session"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ count($sessions) }} {{ $showArchived ? 'total' : 'active' }} sessions
            </div>
        </div>
    </div>

    <script>
        // Edit session title
        function editSessionTitle(sessionId, currentTitle) {
            const newTitle = prompt('Enter new title:', currentTitle);
            if (newTitle !== null && newTitle.trim() !== '' && newTitle !== currentTitle) {
                @this.call('updateSessionTitle', sessionId, newTitle.trim());
            }
        }
        
        // Confirm delete session
        function confirmDeleteSession(sessionId, title) {
            if (confirm(`Are you sure you want to delete the session "${title}"? This action cannot be undone.`)) {
                @this.call('deleteSession', sessionId);
            }
        }
        
        // Download file event listener
        window.addEventListener('downloadFile', function(event) {
            const { filename, content, mimeType } = event.detail;
            
            const blob = new Blob([content], { type: mimeType });
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });
    </script>
</div>