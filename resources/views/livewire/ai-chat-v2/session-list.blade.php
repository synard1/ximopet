<div class="h-full flex flex-col">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sessions</h2>
            <div class="flex items-center space-x-2">
                <button wire:click="toggleArchived"
                        class="px-3 py-1 text-sm rounded-lg {{ $showArchived ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                    {{ $showArchived ? 'Hide Archived' : 'Show Archived' }}
                </button>
                <button wire:click="$refresh"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="mt-3 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.debounce.300ms="searchTerm"
                   type="text"
                   placeholder="Search sessions..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            @if($searchTerm)
                <button wire:click="clearSearch"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="w-5 h-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
    </div>

    <!-- Loading Indicator -->
    @if($isLoading)
        <div class="flex-1 flex items-center justify-center">
            <div class="flex flex-col items-center">
                <svg class="w-8 h-8 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading sessions...</span>
            </div>
        </div>
    @else
        <!-- Session List -->
        <div class="flex-1 overflow-y-auto">
            @if($error)
                <div class="p-4">
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
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
                </div>
            @else
                @if(count($sessions) > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->getFilteredSessions() as $session)
                            <div wire:key="session-{{ $session['id'] }}"
                                 class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer {{ $currentSessionId === $session['id'] ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : '' }}"
                                 wire:click="selectSession('{{ $session['id'] }}')">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center">
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $session['title'] }}
                                            </h3>
                                            @if($session['is_archived'])
                                                <span class="ml-2 px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                                                    Archived
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $session['summary'] ?? 'No messages yet' }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                            {{ \Carbon\Carbon::parse($session['updated_at'])->format('M j, Y g:i A') }}
                                        </p>
                                    </div>
                                    <div class="ml-2 flex-shrink-0 flex space-x-1">
                                        @if(!$session['is_archived'])
                                            <button wire:click.stop="archiveSession('{{ $session['id'] }}')"
                                                    class="p-1 text-gray-400 hover:text-yellow-500 dark:hover:text-yellow-400 rounded-full hover:bg-yellow-100 dark:hover:bg-yellow-900/30">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                                </svg>
                                            </button>
                                        @else
                                            <button wire:click.stop="restoreSession('{{ $session['id'] }}')"
                                                    class="p-1 text-gray-400 hover:text-green-500 dark:hover:text-green-400 rounded-full hover:bg-green-100 dark:hover:bg-green-900/30">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click.stop="exportSession('{{ $session['id'] }}')"
                                                class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/30">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click.stop="deleteSession('{{ $session['id'] }}')"
                                                class="p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 rounded-full hover:bg-red-100 dark:hover:bg-red-900/30"
                                                onclick="confirm('Are you sure you want to delete this session?') || event.stopImmediatePropagation()">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No sessions yet</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Start a new conversation to create your first session.
                        </p>
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>
