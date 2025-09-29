<div x-data="{
    // Entangled Livewire properties
    isOpen: @entangle('isOpen'),
    position: @entangle('chatPosition'),
    theme: @entangle('theme'),
    
    // Local Alpine state
    showSettings: false,
    showSessions: false,
    showTemplates: false,
    showSearch: false,
    autoScroll: true,
    sounds: {{ config('chat.ui.enable_sounds', true) ? 'true' : 'false' }},
    animations: {{ config('chat.ui.enable_animations', true) ? 'true' : 'false' }},
    showChatWindow: false,
    
    // Methods
    toggleChat() {
        this.isOpen = !this.isOpen;
        if (this.isOpen) {
            $wire.openChat();
        } else {
            $wire.closeChat();
        }
    },
    
    closeChat() {
        this.isOpen = false;
        $wire.closeChat();
    },
    
    // Copy message function properly scoped within Alpine component
    copyMessageToClipboard(event, messageIndex) {
        const button = event.target.closest('button');
        const messageContent = button.dataset.messageContent;

        if (!messageContent) {
            this.showToast('No content to copy', 'error');
            return;
        }

        this.copyToClipboard(messageContent);
    },

    async copyToClipboard(text) {
        try {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(text);
                this.showToast('Message copied to clipboard!', 'success');
            } else {
                this.fallbackCopy(text);
            }
        } catch (err) {
            console.error('Copy failed:', err);
            this.fallbackCopy(text);
        }
    },

    fallbackCopy(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
            this.showToast('Message copied to clipboard!', 'success');
        } catch (err) {
            this.showToast('Failed to copy message', 'error');
        }

        document.body.removeChild(textArea);
    },

    // Helper functions
    playSound(type) {
        if (!this.sounds) return;
        console.log('Playing sound:', type);
    },

    showToast(message, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: type === 'error' ? 'Error' : 'Success',
                text: message,
                icon: type === 'error' ? 'error' : 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    },

    // Format message text with proper markdown rendering
    formatMessageText(text) {
        // Convert **bold** to <strong>bold</strong>
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Convert *italic* to <em>italic</em>
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Convert line breaks
        text = text.replace(/\n/g, '<br>');
        return text;
    }
}"
x-init="
    // Watch for isOpen changes to update CSS classes
    $watch('isOpen', value => {
        const widget = $el.closest('.ai-chat-widget');
        if (value) {
            widget.classList.add('chat-open');
            setTimeout(() => {
                this.showChatWindow = true;
            }, 50);
        } else {
            widget.classList.remove('chat-open');
            this.showChatWindow = false;
        }
    });
    
    // Global keyboard shortcut
    window.addEventListener('keydown', (e) => {
        // Ctrl+Shift+` to toggle chat widget
        if (e.ctrlKey && e.shiftKey && e.key === '`' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            this.toggleChat();
        }
    });

    // Auto-scroll to bottom when new messages arrive
    $watch('$wire.messages', () => {
        if (autoScroll) {
            $nextTick(() => {
                const messagesContainer = $el.querySelector('.chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        }
    });

    // Listen for Livewire events
    $wire.on('chat-opened', () => {
        if (sounds) playSound('open');
    });

    $wire.on('chat-closed', () => {
        if (sounds) playSound('close');
    });

    $wire.on('message-sent', (data) => {
        if (sounds) playSound('send');
        showToast('Message sent successfully', 'success');
    });

    $wire.on('chat-error', (message) => {
        if (sounds) playSound('error');
        showToast(message, 'error');
    });

    $wire.on('chat-success', (message) => {
        if (sounds) playSound('success');
        showToast(message, 'success');
    });

    $wire.on('show-sessions', () => {
        showSessions = true;
        showSettings = false;
        showTemplates = false;
        showSearch = false;
    });

    $wire.on('focus-input', () => {
        setTimeout(() => {
            const textarea = $el.querySelector('.chat-textarea');
            if (textarea) {
                textarea.focus();
                // Move cursor to end of text
                textarea.selectionStart = textarea.value.length;
                textarea.selectionEnd = textarea.value.length;
            }
        }, 100);
    });

    // Global keyboard shortcuts (even when chat is closed)
    window.addEventListener('keydown', (e) => {
        // Ctrl+Shift+` to toggle chat widget
        if (e.ctrlKey && e.shiftKey && e.key === '`') {
            e.preventDefault();
            this.toggleChat();
        }
    });

    // Keyboard shortcuts (when chat is open)
    window.addEventListener('keydown', (e) => {
        // Only process shortcuts when chat is open
        if (!this.isOpen) return;

        // Ctrl+T to toggle templates
        if (e.ctrlKey && e.key === 't') {
            e.preventDefault();
            showTemplates = !showTemplates;
            showSettings = false;
            showSessions = false;
            showSearch = false;
        }
        // Ctrl+F to toggle search
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            showSearch = !showSearch;
            showSettings = false;
            showSessions = false;
            showTemplates = false;
        }
        // Ctrl+H to toggle history/sessions
        if (e.ctrlKey && e.key === 'h') {
            e.preventDefault();
            showSessions = !showSessions;
            showSettings = false;
            showTemplates = false;
            showSearch = false;
        }
        // Ctrl+, to toggle settings
        if (e.ctrlKey && e.key === ',') {
            e.preventDefault();
            showSettings = !showSettings;
            showSessions = false;
            showTemplates = false;
            showSearch = false;
        }
        // Ctrl+M to toggle minimize
        if (e.ctrlKey && e.key === 'm') {
            e.preventDefault();
            $wire.toggleMinimize();
        }
        // Esc to close all panels
        if (e.key === 'Escape') {
            showSettings = false;
            showSessions = false;
            showTemplates = false;
            showSearch = false;
        }
    });
"
class="ai-chat-widget"
:class="{
    'position-fixed': true,
    'bottom-0 end-0': position === 'bottom-right',
    'bottom-0 start-0': position === 'bottom-left',
    'top-0 end-0': position === 'top-right',
    'top-0 start-0': position === 'top-left'
}"
style="z-index: 1050; margin: 20px;">

    <!-- Chat Toggle Button -->
    <div x-show="!isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="chat-bubble-wrapper"
         style="position: relative;">
        <button 
            @click="toggleChat()"
            class="btn btn-primary rounded-circle chat-toggle-btn"
            :class="{ 'show': !isOpen }"
            style="width: 48px; height: 48px; box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);"
            title="Open AI Chat">
            <i class="fas fa-robot"></i>
            @if($messageCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $messageCount > 99 ? '99+' : $messageCount }}
                </span>
            @endif
        </button>
    </div>

    <!-- Chat Window Container -->
    <div class="chat-window-container"
         x-show="isOpen"
         x-cloak
         @click.away="if (!$event.target.closest('.chat-window') && !$event.target.closest('.chat-toggle-btn')) { closeChat(); }"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
         
        <!-- Chat Window -->
        <div class="chat-window card"
             x-show="showChatWindow"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="width: 420px; height: 550px; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">

        <!-- Chat Header -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3">
            <style>
                /* Direct button overrides with maximum specificity */
                .card-header .btn-outline-light,
                .card-header .btn-sm,
                .card-header button {
                    color: white !important;
                    border: 2px solid white !important;
                    background-color: rgba(255,255,255,0.3) !important;
                    font-weight: bold !important;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
                    padding: 0.25rem 0.75rem !important;
                    box-shadow: 0 0 5px rgba(255,255,255,0.2) !important;
                }
                .card-header .btn:hover,
                .card-header button:hover {
                    background-color: rgba(255,255,255,0.5) !important;
                    box-shadow: 0 0 8px rgba(255,255,255,0.4) !important;
                }
            </style>
            <div class="d-flex align-items-center">
                <i class="fas fa-robot me-2"></i>
                <div>
                    <h6 class="mb-0">AI Assistant</h6>
                    <small class="opacity-75">
                        @if($currentSession)
                            {{ $currentSession['title'] ?? 'Untitled Session' }}
                        @else
                            Ready to help
                        @endif
                    </small>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <!-- Provider Status -->
                <span class="badge {{ $providerStatus ? 'bg-success' : 'bg-warning' }} me-3">
                    <i class="fas fa-circle" style="font-size: 0.5em;"></i>
                    {{ ucfirst($selectedProvider) }}
                </span>

                <!-- Action Buttons Group -->
                <div class="btn-group me-2" role="group">
                    <button @click="showSettings = !showSettings; showSessions = false; showTemplates = false; showSearch = false"
                            class="btn btn-sm btn-outline-light chat-header-btn settings-btn-{{ time() }}"
                            :class="{ 'active': showSettings }"
                            title="Settings"
                            style="color: white !important; background-color: rgba(255,255,255,0.4) !important; border: 2px solid white !important; font-weight: bold !important; text-shadow: 0 1px 1px rgba(0,0,0,0.5) !important; padding: 0.25rem 0.75rem !important; box-shadow: 0 0 5px rgba(255,255,255,0.3) !important;"
                            x-show="{{ config('chat.ui.show_settings_button', true) ? 'true' : 'false' }}">
                        <i class="fas fa-cog"></i>
                    </button>

                    <button @click="showSessions = !showSessions; showSettings = false; showTemplates = false; showSearch = false"
                            class="btn btn-sm btn-outline-light chat-header-btn history-btn-{{ time() }}"
                            :class="{ 'active': showSessions }"
                            title="Chat Sessions"
                            style="color: white !important; background-color: rgba(255,255,255,0.4) !important; border: 2px solid white !important; font-weight: bold !important; text-shadow: 0 1px 1px rgba(0,0,0,0.5) !important; padding: 0.25rem 0.75rem !important; box-shadow: 0 0 5px rgba(255,255,255,0.3) !important;"
                            x-show="{{ config('chat.ui.show_history_button', true) ? 'true' : 'false' }}">
                        <i class="fas fa-history"></i>
                    </button>

                    <button @click="showTemplates = !showTemplates; showSettings = false; showSessions = false; showSearch = false"
                            class="btn btn-sm btn-outline-light chat-header-btn templates-btn-{{ time() }}"
                            :class="{ 'active': showTemplates }"
                            style="color: white !important; background-color: rgba(255,255,255,0.4) !important; border: 2px solid white !important; font-weight: bold !important; text-shadow: 0 1px 1px rgba(0,0,0,0.5) !important; padding: 0.25rem 0.75rem !important; box-shadow: 0 0 5px rgba(255,255,255,0.3) !important;"
                            title="Chat Templates">
                        <i class="fas fa-file-alt"></i>
                    </button>

                    <button @click="showSearch = !showSearch; showSettings = false; showSessions = false; showTemplates = false"
                            class="btn btn-sm btn-outline-light chat-header-btn search-btn-{{ time() }}"
                            :class="{ 'active': showSearch }"
                            style="color: white !important; background-color: rgba(255,255,255,0.4) !important; border: 2px solid white !important; font-weight: bold !important; text-shadow: 0 1px 1px rgba(0,0,0,0.5) !important; padding: 0.25rem 0.75rem !important; box-shadow: 0 0 5px rgba(255,255,255,0.3) !important;"
                            title="Search Chat History">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Single Close Button -->
                <button @click="closeChat()"
                        class="btn btn-sm btn-outline-light chat-header-btn close-btn"
                        style="color: white !important; background-color: rgba(255,255,255,0.4) !important; border: 1px solid rgba(255,255,255,0.7) !important; padding: 0.25rem 0.75rem !important;"
                        title="Close chat">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Settings Panel -->
        <div x-show="showSettings"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="card-body border-bottom bg-light p-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Settings</h6>
                <button @click="showSettings = false"
                        class="btn btn-sm btn-outline-secondary"
                        title="Close Settings">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Provider Selection -->
            <div class="mb-3">
                <label class="form-label small">AI Provider</label>
                <select wire:model.live="selectedProvider"
                        wire:change="switchProvider"
                        class="form-select form-select-sm">
                    @foreach($availableProviders as $providerKey => $provider)
                        <option value="{{ $providerKey }}"
                                {{ !$provider['available'] ? 'disabled' : '' }}>
                            {{ $provider['name'] }}
                            @if(!$provider['available'])
                                (Offline)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Model Selection -->
            @if(!empty($availableModels))
                <div class="mb-3">
                    <label class="form-label small">Model</label>
                    <select wire:model.live="selectedModel" class="form-select form-select-sm">
                        @foreach($availableModels as $model)
                            <option value="{{ $model }}">{{ $model }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Context Type -->
            <div class="mb-3">
                <label class="form-label small">Context Type</label>
                <select wire:model.live="contextType" class="form-select form-select-sm">
                    @foreach($contextTypes as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
                <button wire:click="createNewSession"
                        class="btn btn-sm btn-outline-primary flex-fill"
                        wire:loading.attr="disabled">
                    <i class="fas fa-plus"></i> New Chat
                </button>
                <div class="btn-group" role="group">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            @disabled(!$currentSessionId)>
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        @foreach($exportFormats as $format => $label)
                            <li>
                                <button class="dropdown-item"
                                        wire:click="exportChatAs('{{ $format }}')"
                                        wire:loading.attr="disabled">
                                    {{ $label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <button wire:click="debugState"
                        class="btn btn-sm btn-outline-warning"
                        title="Debug (temporary)">
                    <i class="fas fa-bug"></i>
                </button>
            </div>
        </div>

        <!-- Sessions Panel -->
        <div x-show="showSessions"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="sessions-panel bg-light border-bottom p-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-history me-2 text-primary"></i>
                    Chat History
                    <span class="badge bg-primary ms-1">{{ count($sessions) }}</span>
                </h6>
                <div class="btn-group" role="group">
                    <button wire:click="loadRecentSessions"
                            class="btn btn-sm btn-outline-primary"
                            title="Refresh sessions"
                            wire:loading.attr="disabled">
                        <i class="fas fa-sync-alt" wire:loading.class="fa-spin"></i>
                    </button>
                    <button @click="showSessions = false"
                            class="btn btn-sm btn-outline-secondary"
                            title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            @if(!empty($sessions))
                <div class="sessions-list-container" style="max-height: 300px; overflow-y: auto;">
                    @foreach($sessions as $session)
                        <div class="session-item mb-2 p-3 rounded {{ $session['id'] === $currentSessionId ? 'bg-primary text-white' : 'bg-white border' }} cursor-pointer"
                             wire:click="switchSession('{{ $session['id'] }}')">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="fw-medium mb-1">
                                        {{ $session['title'] ?? 'Untitled Session' }}
                                    </div>
                                    <div class="small {{ $session['id'] === $currentSessionId ? 'text-white-50' : 'text-muted' }}">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ Carbon\Carbon::parse($session['last_activity_at'])->diffForHumans() }}
                                        @if(isset($session['messages_count']) && $session['messages_count'] > 0)
                                            <span class="ms-2">
                                                <i class="fas fa-comments me-1"></i>
                                                {{ $session['messages_count'] }} messages
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if(config('chat.ui.enable_session_delete', true))
                                    <button wire:click.stop="deleteSession('{{ $session['id'] }}')"
                                            class="btn btn-sm {{ $session['id'] === $currentSessionId ? 'btn-outline-light' : 'btn-outline-danger' }} ms-2"
                                            @if(config('chat.ui.confirm_session_delete', true))
                                                onclick="return confirm('Delete this session?')"
                                            @endif
                                            title="Delete session">
                                        <i class="fas fa-trash fa-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Session Management Actions -->
                <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Auto-delete after {{ config('chat.sessions.auto_delete_after_days', 30) }} days
                    </small>
                    @if(config('chat.ui.enable_session_delete', true))
                        <button wire:click="clearAllSessions"
                                class="btn btn-sm btn-outline-warning"
                                onclick="return confirm('Clear all chat sessions?')"
                                title="Clear all sessions">
                            <i class="fas fa-trash-alt fa-xs me-1"></i>Clear All
                        </button>
                    @endif
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-history fa-3x mb-3 opacity-25"></i>
                    <h6 class="text-muted">No Chat History</h6>
                    <p class="small mb-3">Start a conversation to see your chat sessions here</p>
                    <button wire:click="createNewSession"
                            class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Start New Chat
                    </button>
                </div>
            @endif
        </div>

        <!-- Templates Panel -->
        <div x-show="showTemplates"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="templates-panel bg-light border-bottom p-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-file-alt me-2 text-primary"></i>
                    Chat Templates
                </h6>
                <button @click="showTemplates = false"
                        class="btn btn-sm btn-outline-secondary"
                        title="Close Templates">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            @if(!empty($chatTemplates))
                <div class="templates-container" style="max-height: 300px; overflow-y: auto;">
                    @foreach($chatTemplates as $category => $templateGroup)
                        <div class="mb-3">
                            <h6 class="text-primary border-bottom pb-1">{{ $templateGroup['title'] }}</h6>
                            @foreach($templateGroup['templates'] as $template)
                                <div class="template-item mb-2 p-2 rounded bg-white border cursor-pointer"
                                     wire:click="useTemplate('{{ $template['template'] }}')"
                                     title="{{ $template['description'] }}">
                                    <div class="fw-medium small">{{ $template['name'] }}</div>
                                    <div class="text-muted small">{{ $template['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-file-alt fa-3x mb-3 opacity-25"></i>
                    <h6 class="text-muted">No Templates Available</h6>
                    <p class="small mb-0">Templates will appear here to help you get started</p>
                </div>
            @endif
        </div>

        <!-- Search Panel -->
        <div x-show="showSearch"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="search-panel bg-light border-bottom p-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-search me-2 text-primary"></i>
                    Search Chat History
                </h6>
                <button @click="showSearch = false"
                        class="btn btn-sm btn-outline-secondary"
                        title="Close Search">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form wire:submit.prevent="searchChatHistory" class="mb-3">
                <div class="input-group">
                    <input type="text"
                           wire:model.defer="searchQuery"
                           class="form-control form-control-sm"
                           placeholder="Search sessions and messages..."
                           aria-label="Search chat history">
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            @if(!empty($searchQuery))
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        Search results for: "{{ $searchQuery }}"
                    </small>
                    <button wire:click="clearSearch" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            @endif
        </div>

        <!-- Chat Body -->
        <div class="card-body p-0" x-show="!$wire.isMinimized">

            <!-- Messages Container -->
            <div class="chat-messages p-3"
                 style="height: 320px; overflow-y: auto; border-radius: 8px; margin: 8px; background: #f8f9fa;">

                @if(empty($messages))
                    <!-- Welcome Message -->
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-robot fa-3x mb-3 opacity-25"></i>
                        <h6>Hi! I'm your AI assistant</h6>
                        <p class="small mb-0">Ask me anything about your farm management, livestock, feeds, or supplies!</p>
                    </div>
                @else
                    <!-- Messages List -->
                    @foreach($messages as $message)
                        <div class="message mb-3 {{ $message['message_type'] === 'user' ? 'message-user' : 'message-assistant' }}">
                            <div class="d-flex {{ $message['message_type'] === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                                <div class="message-content {{ $message['message_type'] === 'user' ? 'bg-primary text-white' : 'bg-white border' }}
                                            rounded px-3 py-2"
                                     style="max-width: 80%;">

                                    @if($message['message_type'] === 'assistant')
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-robot text-primary me-2"></i>
                                            <small class="text-muted">AI Assistant</small>
                                        </div>
                                    @endif

                                    <div class="message-text" x-html="formatMessageText(@js($message['content']))"></div>

                                    <div class="message-meta mt-2">
                                        <small class="{{ $message['message_type'] === 'user' ? 'text-white-50' : 'text-muted' }}">
                                            {{ Carbon\Carbon::parse($message['created_at'])->setTimezone(config('app.timezone'))->format('H:i') }}
                                            @if($message['message_type'] === 'assistant' && isset($message['processing_time']))
                                                • {{ number_format($message['processing_time'], 2) }}s
                                            @endif
                                        </small>

                                        @if($message['message_type'] === 'assistant')
                                            <div class="message-actions mt-1">
                                                {{-- Debug info (temporary, remove in production) --}}
                                                @if(config('app.debug'))
                                                <div class="small text-muted mb-1">
                                                    <span title="Is Greeting: {{ var_export($message['is_greeting'] ?? false, true) }}">
                                                        {{ isset($message['is_greeting']) && $message['is_greeting'] ? '⚡ Greeting' : '🤖 AI' }}
                                                    </span>
                                                </div>
                                                @endif

                                                {{-- Copy button - hidden for greeting messages --}}
                                                @if(!(isset($message['is_greeting']) && $message['is_greeting'] === true))
                                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                                            @click="copyMessageToClipboard($event, '{{ $loop->index }}')"
                                                            data-message-content="{{ htmlspecialchars(strip_tags($message['content']), ENT_QUOTES, 'UTF-8') }}"
                                                            title="Copy message">
                                                        <i class="fas fa-copy fa-xs"></i>
                                                    </button>
                                                @endif

                                                {{-- Regenerate button - hidden for greeting messages --}}
                                                @if(config('chat.ui.enable_message_regenerate', true))
                                                    @if(!(isset($message['is_greeting']) && $message['is_greeting'] === true))
                                                        <button class="btn btn-sm btn-outline-primary"
                                                                wire:click="regenerateMessage('{{ $message['id'] ?? '' }}')"
                                                                title="Regenerate response"
                                                                @if(isset($message['id']) && isset($ratedMessages[$message['id']]))
                                                                    disabled
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="Regeneration disabled after rating"
                                                                @endif
                                                                >
                                                            <i class="fas fa-redo fa-xs"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>

                                            {{-- Rating Component - hidden for greeting messages --}}
                                            @if(!(isset($message['is_greeting']) && $message['is_greeting'] === true))
                                                <div class="mt-2">
                                                    @livewire('chat-rating', ['messageId' => $message['id']], key('rating-'.$message['id']))
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                <!-- Typing Indicator -->
                <div wire:loading.delay wire:target="sendMessage" class="message message-assistant mb-3">
                    <div class="d-flex justify-content-start">
                        <div class="bg-white border rounded px-3 py-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-robot text-primary me-2"></i>
                                <div class="typing-indicator">
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                </div>
                                <span class="ms-2 text-muted small">AI is thinking...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="card-footer bg-white border-top-0 p-3">
                <!-- Error/Success Messages -->
                @if($errorMessage)
                    <div class="alert alert-danger alert-sm py-2 mb-2" role="alert">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        {{ $errorMessage }}
                    </div>
                @endif

                @if($successMessage)
                    <div class="alert alert-success alert-sm py-2 mb-2" role="alert">
                        <i class="fas fa-check-circle me-1"></i>
                        {{ $successMessage }}
                    </div>
                @endif

                <!-- Input Form -->
                <div class="chat-input-container">
                    <form action="#" method="POST" onsubmit="return false;" class="d-flex flex-column gap-2" id="ai-chat-form">
                        <div class="input-group">
                            <textarea wire:model="newMessage"
                                    class="form-control chat-textarea"
                                    rows="3"
                                    placeholder="Type your message here..."
                                    maxlength="{{ config('chat.security.max_message_length', 5000) }}"
                                    wire:loading.attr="disabled"
                                    @disabled($isLoading || !$providerStatus)
                                    style="resize: none; border-right: none;"></textarea>
                            <div class="input-group-append d-flex flex-column justify-content-end">
                <button type="button"
                    class="btn btn-primary chat-send-btn h-100"
                    wire:loading.attr="disabled"
                    :disabled="!providerStatus || $wire.isLoading || !newMessage || !newMessage.trim()"
                    @click="$wire.sendMessage()"
                    title="Send message">
                                    <span wire:loading.remove wire:target="sendMessage">
                                        <i class="fas fa-paper-plane"></i>
                                    </span>
                                    <span wire:loading wire:target="sendMessage">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Press Ctrl+Enter to send</small>
                            <div class="chat-actions">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary me-1"
                                        @click="$wire.set('newMessage', '')"
                                        title="Clear message">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-info"
                                        @click="showSessions = !showSessions; showSettings = false"
                                        title="Show chat history">
                                    <i class="fas fa-history"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if(!$providerStatus)
                    <div class="text-center mt-2">
                        <small class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            AI provider is currently offline
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="{{ asset('css/ai-chat-visibility.css') }}" rel="stylesheet">
<link href="{{ asset('css/ai-chat-buttons-fix.css') }}" rel="stylesheet">
<link href="{{ asset('css/ai-chat-header-buttons.css') }}" rel="stylesheet">
<style>
/* Clean, Production-Ready Chat Widget Styles */
.ai-chat-widget {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    z-index: 1050;
}

/* Chat Toggle Button */
.chat-toggle-btn {
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.chat-toggle-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
}

/* Chat Window */
.chat-window {
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    width: 420px !important;
    height: 600px !important;
    position: relative;
}

/* Sessions Panel */
.sessions-panel {
    border-bottom: 1px solid #dee2e6;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.sessions-list-container {
    border-radius: 8px;
    background: white;
    border: 1px solid #e9ecef;
}

.sessions-list-container::-webkit-scrollbar {
    width: 6px;
}

.sessions-list-container::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 3px;
}

.sessions-list-container::-webkit-scrollbar-thumb {
    background: #c1c8cd;
    border-radius: 3px;
}

.sessions-list-container::-webkit-scrollbar-thumb:hover {
    background: #a8b2ba;
}

.session-item {
    transition: all 0.2s ease;
    cursor: pointer;
}

.session-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Templates Panel */
.templates-panel {
    border-bottom: 1px solid #dee2e6;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.template-item {
    transition: all 0.2s ease;
}

.template-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    background-color: #e9f7fe !important;
}

.templates-container {
    border-radius: 8px;
    background: white;
    border: 1px solid #e9ecef;
}

.templates-container::-webkit-scrollbar {
    width: 6px;
}

.templates-container::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 3px;
}

.templates-container::-webkit-scrollbar-thumb {
    background: #c1c8cd;
    border-radius: 3px;
}

.templates-container::-webkit-scrollbar-thumb:hover {
    background: #a8b2ba;
}

/* Search Panel */
.search-panel {
    border-bottom: 1px solid #dee2e6;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

/* Chat Messages */
.chat-messages {
    scrollbar-width: thin;
    scrollbar-color: #c1c8cd #f1f3f4;
}

.chat-messages::-webkit-scrollbar {
    width: 8px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #c1c8cd;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a8b2ba;
}

.message-content {
    word-wrap: break-word;
    line-height: 1.5;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-text {
    margin: 0;
    line-height: 1.6;
}

.message-text strong {
    font-weight: 600;
    color: inherit;
}

.message-text em {
    font-style: italic;
    color: inherit;
}

.message-text h1, .message-text h2, .message-text h3,
.message-text h4, .message-text h5, .message-text h6 {
    margin: 0.5rem 0;
    font-weight: 600;
}

.message-text ul, .message-text ol {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.message-text p {
    margin: 0.5rem 0;
}

.message-text p:first-child {
    margin-top: 0;
}

.message-text p:last-child {
    margin-bottom: 0;
}

.message-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.message:hover .message-actions {
    opacity: 1;
}

/* Chat Input */
.chat-input-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
    margin: -8px;
}

.chat-textarea {
    border: 1px solid #dee2e6;
    border-radius: 8px 0 0 8px;
    font-size: 14px;
    line-height: 1.4;
    transition: border-color 0.2s ease;
    resize: none;
}

.chat-textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.chat-send-btn {
    border-radius: 0 8px 8px 0;
    border: 1px solid #007bff;
    min-width: 50px;
    transition: all 0.2s ease;
}

.chat-send-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
}

/* Button Groups */
.btn-group .btn {
    transition: all 0.2s ease;
}

.btn-group .btn.active {
    background-color: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Header Buttons - Improved Visibility */
.chat-header-btn {
    color: white !important;
    border-color: rgba(255, 255, 255, 0.7) !important;
    background-color: rgba(255, 255, 255, 0.2) !important;
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
    font-weight: bold !important;
}

.chat-header-btn:hover {
    color: white !important;
    background-color: rgba(255, 255, 255, 0.35) !important;
    border-color: white !important;
    box-shadow: 0 0 5px rgba(255, 255, 255, 0.5) !important;
}

.chat-header-btn.active {
    background-color: rgba(255, 255, 255, 0.4) !important;
    border-color: white !important;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.6) !important;
}

/* Typing Indicator */
.typing-indicator {
    display: inline-flex;
    align-items: center;
}

.typing-indicator .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #007bff;
    margin: 0 1px;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator .dot:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator .dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Chat Rating Component */
.chat-rating {
    font-size: 0.85rem;
}

.chat-rating .rating-stars .btn {
    padding: 0.1rem 0.2rem;
    border: none;
    background: transparent;
}

.chat-rating .rating-stars .btn:hover {
    transform: scale(1.2);
}

.chat-rating .feedback-form .form-control {
    font-size: 0.8rem;
}

.chat-rating .feedback-form .btn {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
}

/* Disabled regenerate button */
.message-actions button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    position: relative;
}

.message-actions button:disabled:hover::after {
    content: attr(data-bs-title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    margin-bottom: 5px;
    z-index: 1000;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ai-chat-widget {
        margin: 10px;
    }

    .chat-window {
        width: calc(100vw - 20px) !important;
        max-width: 420px !important;
        height: 600px !important;
    }

    .sessions-list-container {
        max-height: 200px !important;
    }

    .chat-messages {
        height: 280px !important;
    }
}

@media (max-width: 480px) {
    .chat-window {
        width: calc(100vw - 20px) !important;
        max-width: 380px !important;
        height: 560px !important;
    }

    .sessions-list-container {
        max-height: 180px !important;
    }

    .chat-messages {
        height: 240px !important;
    }

    .btn-group .btn {
        font-size: 12px;
        padding: 0.25rem 0.5rem;
    }

    .card-header {
        padding: 0.75rem !important;
    }

    .card-header h6 {
        font-size: 0.9rem;
    }

    .card-header small {
        font-size: 0.75rem;
    }
}

/* Utility Classes */
.cursor-pointer {
    cursor: pointer;
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script>
    // Direct JavaScript to handle chat bubble clicks
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Chat widget script loaded');
        
        // Add direct click handler to chat bubble
        const chatBubble = document.querySelector('.chat-toggle-btn');
        if (chatBubble) {
            chatBubble.addEventListener('click', function() {
                console.log('Chat bubble clicked');
                // Directly apply classes to force chat window to show
                setTimeout(function() {
                    const widget = document.querySelector('.ai-chat-widget');
                    if (widget) {
                        widget.classList.add('chat-is-open');
                    }
                    
                    const chatWindow = document.querySelector('.chat-window');
                    if (chatWindow) {
                        chatWindow.classList.add('force-show-chat', 'show-chat-window');
                        chatWindow.style.display = 'flex';
                    }
                }, 50);
            });
        }
        
        // Listen for UI refresh events from Livewire
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('ui-refresh', () => {
                console.log('UI refresh event received');
                setTimeout(() => {
                    scrollChatToBottom();
                }, 100);
            });
        });
        
        // Fix form submission issue
        const chatForm = document.querySelector('.chat-input-container form');
        if (chatForm) {
            console.log('Chat form found, adding submit handler');
            chatForm.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
                e.preventDefault();
                
                // Use the fallback mechanism for all submissions to ensure consistency
                sendMessageFallback();
                
                // Prevent default Livewire handling which might cause URI errors
                return false;
            });
            
            // Override the form's action and method to prevent default Livewire handling
            chatForm.setAttribute('action', '#');
            chatForm.setAttribute('method', 'POST');
            chatForm.setAttribute('onsubmit', 'return false;');
        } else {
            console.warn('Chat form not found');
        }
    });

    // Function to scroll chat to bottom
    function scrollChatToBottom() {
        const messagesContainer = document.querySelector('.chat-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            console.log('Scrolled chat to bottom');
        }
    }

    // Ensure button styles are applied after DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Apply styles to all header buttons
        const applyButtonStyles = function() {
            const headerButtons = document.querySelectorAll('.card-header .btn, .card-header button');
            headerButtons.forEach(button => {
                button.style.color = 'white';
                button.style.backgroundColor = 'rgba(255,255,255,0.4)';
                button.style.border = '2px solid white';
                button.style.fontWeight = 'bold';
                button.style.textShadow = '0 1px 1px rgba(0,0,0,0.5)';
                button.style.padding = '0.25rem 0.75rem';
                button.style.boxShadow = '0 0 5px rgba(255,255,255,0.3)';
            });
        };

        // Apply styles immediately
        applyButtonStyles();

        // Apply styles after any Livewire updates
        document.addEventListener('livewire:update', () => {
            applyButtonStyles();
            scrollChatToBottom();
        });
    });
    
    // Fix for message sending - ensure the form can submit properly
    document.addEventListener('livewire:init', function() {
        console.log('Livewire initialized');
        
        // Add event listener for Ctrl+Enter in textarea
        const textarea = document.querySelector('.chat-textarea');
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    console.log('Ctrl+Enter pressed in textarea');
                    e.preventDefault();
                    sendMessageFallback();
                }
            });
        }
        
        // Ensure the form submit button works correctly
        const submitButton = document.querySelector('.chat-send-btn');
        if (submitButton) {
            console.log('Submit button found, adding click handler');
            submitButton.addEventListener('click', function(e) {
                console.log('Submit button clicked');
                e.preventDefault();
                sendMessageFallback();
            });
        } else {
            console.warn('Submit button not found');
        }
        
        // Safe Livewire initialization check
        if (typeof Livewire !== 'undefined') {
            console.log('Setting up Livewire event handlers');
            
            // Handle URI errors globally
            window.addEventListener('error', function(event) {
                if (event.error && event.error.message && event.error.message.includes("Cannot read properties of undefined (reading 'uri')")) {
                    console.warn('Caught URI undefined error, using fallback');
                    // Try to recover from the error
                    const form = document.querySelector('.chat-input-container form');
                    if (form) {
                        console.log('Recovering using form submission');
                        form.dispatchEvent(new Event('submit'));
                    }
                }
            });
        }
    });
    
    // Fallback function to send message if Livewire fails
    function sendMessageFallback() {
        console.log('sendMessageFallback called');
        const messageInput = document.querySelector('.chat-textarea');
        const message = messageInput ? messageInput.value.trim() : '';
        
        console.log('Message content:', message);
        
        if (!message) {
            console.warn('No message to send');
            return;
        }
        
        // Add user message to UI immediately for better user experience
        appendUserMessageToUI(message);
        
        // Clear the input immediately
        messageInput.value = '';
        
        // Get the current session ID if available
        let sessionId = null;
        try {
            const componentElement = document.querySelector('[wire\\:id]');
            if (componentElement) {
                const componentId = componentElement.getAttribute('wire:id');
                const component = Livewire.find(componentId);
                if (component) {
                    sessionId = component.get('currentSessionId');
                }
            }
        } catch (e) {
            console.error('Error getting session ID:', e);
        }
        
        // Try direct API call first (most reliable)
        callDirectApi(message, sessionId);
    }
    
    // Function to call the direct API endpoint
    function callDirectApi(message, sessionId = null) {
        console.log('Calling direct API endpoint');
        
        // Get the CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Call the API endpoint - use web routes instead of API routes to maintain the session
        fetch('/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin', // Include cookies for authentication
            body: JSON.stringify({
                message: message,
                session_id: sessionId
            })
        }).then(response => {
            console.log('API response status:', response.status);
            return response.json();
        }).then(data => {
            console.log('API response data:', data);
            
            if (data.success) {
                console.log('Message sent successfully via API');
                
                // Add the assistant message to the UI
                appendAssistantMessageToUI(data.assistant_message.content);
                
                // Try to refresh the Livewire component
                try {
                    const componentElement = document.querySelector('[wire\\:id]');
                    if (componentElement) {
                        const componentId = componentElement.getAttribute('wire:id');
                        Livewire.find(componentId).call('forceRefresh');
                    }
                } catch (e) {
                    console.error('Error refreshing component:', e);
                }
            } else {
                console.error('API error:', data.error);
                // Try form submission as fallback
                tryFormSubmission(message);
            }
        }).catch(error => {
            console.error('API call failed:', error);
            // Try form submission as fallback
            tryFormSubmission(message);
        });
    }
    
    // Function to try form submission as fallback
    function tryFormSubmission(message) {
        console.log('Trying form submission fallback');
        
        const form = document.querySelector('.chat-input-container form');
        if (!form) {
            console.error('Chat form not found for submission');
            return;
        }
        
        // Create a new FormData object
        const formData = new FormData();
        formData.append('newMessage', message);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        
        // Get the component ID if available
        const componentElement = document.querySelector('[wire\\:id]');
        const componentId = componentElement ? componentElement.getAttribute('wire:id') : null;
        
        // Submit via standard form submission to a controller route
        fetch('/chat/submit', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin', // Include cookies for authentication
            body: formData
        }).then(response => {
            console.log('Form submission response:', response.status);
            if (response.ok) {
                console.log('Message sent successfully via form submission');
                return response.json();
            } else {
                console.error('Failed to send message via form submission');
                throw new Error('Form submission failed');
            }
        }).then(data => {
            console.log('Form submission data:', data);
            if (data.success && data.assistant_message) {
                appendAssistantMessageToUI(data.assistant_message.content);
            }
        }).catch(error => {
            console.error('Error sending message via form submission:', error);
            // Last resort - fallback to immediate display without backend
            appendAssistantMessageToUI('I apologize, but I encountered an issue processing your message. Please try again or refresh the page.');
        });
    }
    
    // Function to append user message to UI immediately
    function appendUserMessageToUI(message) {
        const messagesContainer = document.querySelector('.chat-messages');
        if (!messagesContainer) return;
        
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + 
                    now.getMinutes().toString().padStart(2, '0');
        
        const messageHtml = `
            <div class="message mb-3 message-user">
                <div class="d-flex justify-content-end">
                    <div class="message-content bg-primary text-white rounded px-3 py-2" style="max-width: 80%;">
                        <div class="message-text">${message}</div>
                        <div class="message-meta mt-2">
                            <small class="text-white-50">${time}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add message to container
        messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
        
        // Scroll to bottom
        scrollChatToBottom();
        
        console.log('User message appended to UI');
    }
    
    // Function to append assistant message to UI
    function appendAssistantMessageToUI(message) {
        const messagesContainer = document.querySelector('.chat-messages');
        if (!messagesContainer) return;
        
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + 
                    now.getMinutes().toString().padStart(2, '0');
        
        const messageHtml = `
            <div class="message mb-3 message-assistant">
                <div class="d-flex justify-content-start">
                    <div class="message-content bg-white border rounded px-3 py-2" style="max-width: 80%;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-robot text-primary me-2"></i>
                            <small class="text-muted">AI Assistant</small>
                        </div>
                        <div class="message-text">${message}</div>
                        <div class="message-meta mt-2">
                            <small class="text-muted">${time}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add message to container
        messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
        
        // Scroll to bottom
        scrollChatToBottom();
        
        console.log('Assistant message appended to UI');
    }
</script>
@endpush
