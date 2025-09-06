<div x-data="{
    position: @entangle('chatPosition'),
    size: 'medium',
    theme: @entangle('theme'),
    showSettings: @entangle('showSettings'),
    showSessions: false,
    autoScroll: true,
    sounds: {{ config('chat.ui.enable_sounds', true) ? 'true' : 'false' }},
    animations: {{ config('chat.ui.enable_animations', true) ? 'true' : 'false' }},

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
}" x-init="
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
            const processingTime = data?.processing_time;
            const toastMessage = processingTime !== undefined 
                ? `Message sent in ${processingTime.toFixed(2)}s` 
                : 'Message sent successfully';
            showToast(toastMessage, 'success');
        });

        $wire.on('chat-error', (message) => {
            if (sounds) playSound('error');
            showToast(message, 'error');
        });

        $wire.on('chat-success', (message) => {
            if (sounds) playSound('success');
            showToast(message, 'success');
        });
    " class="ai-chat-widget" :class="{
        'position-fixed': true,
        'bottom-0 end-0': position === 'bottom-right',
        'bottom-0 start-0': position === 'bottom-left',
        'top-0 end-0': position === 'top-right',
        'top-0 start-0': position === 'top-left'
    }" style="z-index: 1050; margin: 20px;">

    <!-- Chat Toggle Button (when closed) -->
    <div x-show="!@this.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90">
        <button wire:click="openChat" class="btn btn-primary rounded-circle shadow-lg chat-toggle-btn"
            style="width: 52px; height: 48px;" title="Open AI Chat">
            {{-- <i class="fas fa-robot fa-lg"></i> --}}
            <i class="fa-regular fa-comment-dots fa-xl"></i>
            @if($messageCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $messageCount > 99 ? '99+' : $messageCount }}
                <span class="visually-hidden">unread messages</span>
            </span>
            @endif
        </button>
    </div>

    <!-- Chat Window -->
    <div x-show="@this.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95" class="chat-window card shadow-lg"
        style="width: 420px; height: 550px;" x-cloak>

        <!-- Chat Header -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3">
            <div class="d-flex align-items-center">
                {{-- <i class="fas fa-robot me-2"></i> --}}
                <i class="fa-regular fa-comment-dots"></i>
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
                {{-- <span class="badge {{ $providerStatus ? 'bg-success' : 'bg-warning' }} me-3">
                    <i class="fas fa-circle" style="font-size: 0.5em;"></i>
                    {{ ucfirst($selectedProvider) }}
                </span> --}}

                <!-- Action Buttons Group -->
                <div class="btn-group me-2" role="group">
                    <button wire:click="toggleSettings"
                        class="btn btn-sm btn-outline-light" :class="{ 'active': showSettings }" title="Settings">
                        <i class="fas fa-cog"></i>
                    </button>

                    {{-- <button @click="showSessions = !showSessions; showSettings = false"
                        class="btn btn-sm btn-outline-light" :class="{ 'active': showSessions }" title="Chat Sessions">
                        <i class="fas fa-history"></i>
                    </button> --}}
                </div>

                <!-- Window Controls Group -->
                <div class="btn-group" role="group">
                    {{-- <button wire:click="toggleMinimize" class="btn btn-sm btn-outline-light" title="Minimize">
                        <i class="fas" :class="@this.isMinimized ? 'fa-window-maximize' : 'fa-window-minimize'"></i>
                    </button> --}}

                    <button wire:click="closeChat" class="btn btn-sm btn-outline-light" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Panel -->
        <div x-show="showSettings" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2" class="card-body border-bottom bg-light p-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Settings</h6>
                <button @click="showSettings = false" class="btn btn-sm btn-outline-secondary" title="Close Settings">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Provider Selection -->
            <div class="mb-3">
                <label class="form-label small">AI Provider</label>
                <select wire:model.live="selectedProvider" wire:change="switchProvider"
                    class="form-select form-select-sm">
                    @foreach($availableProviders as $providerKey => $provider)
                    <option value="{{ $providerKey }}" {{ !$provider['available'] ? 'disabled' : '' }}>
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
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small mb-0">Model</label>
                    @php
                        $configDefault = config("chat.providers.{$selectedProvider}.default_model");
                        $isTemporary = $selectedModel !== $configDefault;
                    @endphp
                    @if($isTemporary)
                        <button wire:click="resetToDefaultModel" 
                                class="btn btn-xs btn-outline-warning" 
                                title="Reset to default model from config">
                            <i class="fas fa-undo fa-xs"></i> Reset
                        </button>
                    @endif
                </div>
                <select wire:model.live="selectedModel" class="form-select form-select-sm">
                    @foreach($availableModels as $model)
                    <option value="{{ $model }}">
                        {{ $model }}
                        @if($model === $configDefault)
                            (Default)
                        @endif
                    </option>
                    @endforeach
                </select>
                @if($isTemporary)
                    <small class="text-warning mt-1 d-block">
                        <i class="fas fa-exclamation-triangle fa-xs"></i>
                        Temporary change - will reset to <strong>{{ $configDefault }}</strong> on next login
                    </small>
                @else
                    <small class="text-success mt-1 d-block">
                        <i class="fas fa-check fa-xs"></i>
                        Using default from config/chat.php
                    </small>
                @endif
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
                <button wire:click="createNewSession" class="btn btn-sm btn-outline-primary flex-fill"
                    wire:loading.attr="disabled">
                    <i class="fas fa-plus"></i> New Chat
                </button>
                <button wire:click="exportChat" class="btn btn-sm btn-outline-secondary" wire:loading.attr="disabled"
                    @disabled(!$currentSessionId)>
                    <i class="fas fa-download"></i> Export
                </button>
                <button wire:click="debugState" class="btn btn-sm btn-outline-warning" title="Debug (temporary)">
                    <i class="fas fa-bug"></i>
                </button>
            </div>
        </div>

        <!-- Sessions Panel -->
        <div x-show="showSessions" x-transition:enter="transition ease-out duration-200"
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
                    <button wire:click="loadRecentSessions" class="btn btn-sm btn-outline-primary"
                        title="Refresh sessions" wire:loading.attr="disabled">
                        <i class="fas fa-sync-alt" wire:loading.class="fa-spin"></i>
                    </button>
                    <button @click="showSessions = false" class="btn btn-sm btn-outline-secondary" title="Close">
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
                            <div
                                class="small {{ $session['id'] === $currentSessionId ? 'text-white-50' : 'text-muted' }}">
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
                            onclick="return confirm('Delete this session?')" @endif title="Delete session">
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
                <button wire:click="clearAllSessions" class="btn btn-sm btn-outline-warning"
                    onclick="return confirm('Clear all chat sessions?')" title="Clear all sessions">
                    <i class="fas fa-trash-alt fa-xs me-1"></i>Clear All
                </button>
                @endif
            </div>
            @else
            <div class="text-center py-4 text-muted">
                <i class="fas fa-history fa-3x mb-3 opacity-25"></i>
                <h6 class="text-muted">No Chat History</h6>
                <p class="small mb-3">Start a conversation to see your chat sessions here</p>
                <button wire:click="createNewSession" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Start New Chat
                </button>
            </div>
            @endif
        </div>

        <!-- Chat Body -->
        <div class="card-body p-0" x-show="!@this.isMinimized && !showSettings && !showSessions">

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
                <div
                    class="message mb-3 {{ $message['message_type'] === 'user' ? 'message-user' : 'message-assistant' }}">
                    <div
                        class="d-flex {{ $message['message_type'] === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="message-content {{ $message['message_type'] === 'user' ? 'bg-primary text-white' : 'bg-white border' }}
                                            rounded px-3 py-2" style="max-width: 80%;">

                            @if($message['message_type'] === 'assistant')
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-robot text-primary me-2"></i>
                                <small class="text-muted">AI Assistant</small>
                            </div>
                            @endif

                            <div class="message-text" x-html="formatMessageText(@js($message['content']))"></div>

                            <div class="message-meta mt-2">
                                <small
                                    class="{{ $message['message_type'] === 'user' ? 'text-white-50' : 'text-muted' }}">
                                    {{
                                    Carbon\Carbon::parse($message['created_at'])->setTimezone(config('app.timezone'))->format('H:i')
                                    }}
                                    @if($message['message_type'] === 'assistant' && isset($message['processing_time']))
                                    • {{ number_format($message['processing_time'], 2) }}s
                                    @endif
                                </small>

                                @if($message['message_type'] === 'assistant')
                                {{-- Message Actions - hidden for greeting messages --}}
                                @if(!(isset($message['is_greeting']) && $message['is_greeting'] === true))
                                <div class="message-actions mt-1">
                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                        @click="copyMessageToClipboard($event, '{{ $loop->index }}')"
                                        data-message-content="{{ htmlspecialchars(strip_tags($message['content']), ENT_QUOTES, 'UTF-8') }}"
                                        title="Copy message">
                                        <i class="fas fa-copy fa-xs"></i>
                                    </button>
                                    @if(config('chat.ui.enable_message_regenerate', true))
                                    <button class="btn btn-sm btn-outline-primary"
                                            wire:click="regenerateMessage('{{ $message['id'] ?? '' }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="regenerateMessage"
                                            title="{{ $isRegenerating ? 'Sedang regenerate response...' : 'Regenerate response' }}" 
                                            @if(isset($message['id']) && isset($ratedMessages[$message['id']]) || $isRegenerating) 
                                            disabled data-bs-toggle="tooltip"
                                            data-bs-placement="top" 
                                            data-bs-title="{{ $isRegenerating ? 'Regeneration in progress' : 'Regeneration disabled after rating' }}"
                                            @endif>
                                        <i class="fas fa-redo fa-xs" wire:loading.class="fa-spin" wire:target="regenerateMessage"></i>
                                        <span wire:loading.remove wire:target="regenerateMessage">Regenerate</span>
                                        <span wire:loading wire:target="regenerateMessage" class="small">Regenerating...</span>
                                    </button>
                                    @endif
                                </div>
                                @endif

                                {{-- Rating Component - hidden for greeting messages --}}
                                @if(!(isset($message['is_greeting']) && $message['is_greeting'] === true))
                                <div class="mt-2">
                                    @livewire('chat-rating', ['messageId' => $message['id'], 'isRegenerating' => $isRegenerating],
                                    key('rating-'.$message['id']))
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

                <!-- Regenerate Indicator -->
                <div wire:loading.delay wire:target="regenerateMessage" class="message message-assistant mb-3">
                    <div class="d-flex justify-content-start">
                        <div class="bg-warning bg-opacity-10 border border-warning rounded px-3 py-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-redo text-warning me-2 fa-spin"></i>
                                <div class="typing-indicator">
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                </div>
                                <span class="ms-2 text-warning small">Regenerating response...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="card-footer bg-white border-top-0 p-3">
                <!-- Error/Success Messages with Auto-close -->
                @if($errorMessage)
                <div class="alert alert-danger alert-sm py-2 mb-2 position-relative" role="alert" 
                     x-data="alertAutoClose({{ config('chat.ui.alerts.autoclose_duration', 60) }}, {{ config('chat.ui.alerts.enable_autoclose', true) ? 'true' : 'false' }}, {{ config('chat.ui.alerts.show_countdown', true) ? 'true' : 'false' }}, {{ config('chat.ui.alerts.enable_pause_on_hover', true) ? 'true' : 'false' }})"
                     x-show="show"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     @mouseenter="pauseTimer"
                     @mouseleave="resumeTimer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ $errorMessage }}
                        </div>
                        @if(config('chat.ui.alerts.show_countdown', true))
                        <div class="ms-2 d-flex align-items-center">
                            <small class="text-muted me-2" x-show="showCountdown" x-text="timeLeft + 's'"></small>
                            <div class="countdown-progress" x-show="showCountdown">
                                <div class="progress" style="width: 30px; height: 4px;">
                                    <div class="progress-bar bg-light" role="progressbar" 
                                         :style="'width: ' + (timeLeft / duration * 100) + '%'"></div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <button type="button" class="btn-close btn-close-white ms-2" 
                                @click="closeAlert" aria-label="Close"></button>
                    </div>
                </div>
                @endif

                @if($successMessage)
                <div class="alert alert-success alert-sm py-2 mb-2 position-relative" role="alert"
                     x-data="alertAutoClose({{ config('chat.ui.alerts.autoclose_duration', 60) }}, {{ config('chat.ui.alerts.enable_autoclose', true) ? 'true' : 'false' }}, {{ config('chat.ui.alerts.show_countdown', true) ? 'true' : 'false' }}, {{ config('chat.ui.alerts.enable_pause_on_hover', true) ? 'true' : 'false' }})"
                     x-show="show"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     @mouseenter="pauseTimer"
                     @mouseleave="resumeTimer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <i class="fas fa-check-circle me-1"></i>
                            {{ $successMessage }}
                        </div>
                        @if(config('chat.ui.alerts.show_countdown', true))
                        <div class="ms-2 d-flex align-items-center">
                            <small class="text-muted me-2" x-show="showCountdown" x-text="timeLeft + 's'"></small>
                            <div class="countdown-progress" x-show="showCountdown">
                                <div class="progress" style="width: 30px; height: 4px;">
                                    <div class="progress-bar bg-light" role="progressbar" 
                                         :style="'width: ' + (timeLeft / duration * 100) + '%'"></div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <button type="button" class="btn-close ms-2" 
                                @click="closeAlert" aria-label="Close"></button>
                    </div>
                </div>
                @endif

                <!-- Input Form -->
                <div class="chat-input-container">
                    <form wire:submit.prevent="sendMessage" class="d-flex flex-column gap-2">
                        <div class="input-group">
                            <textarea wire:model.defer="newMessage" class="form-control chat-textarea" rows="3"
                                placeholder="{{ $isRegenerating ? 'Sedang regenerate response, mohon tunggu...' : 'Type your message here...' }}"
                                maxlength="{{ config('chat.security.max_message_length', 5000) }}"
                                wire:loading.attr="disabled" wire:keydown.ctrl.enter="sendMessage" @disabled($isLoading
                                || !$providerStatus || $isRegenerating) style="resize: none; border-right: none;"
                                @input="console.log('Textarea input changed:', $event.target.value)"
                                @focus="console.log('Textarea focused, current value:', $event.target.value)"
                                @blur="console.log('Textarea blurred, final value:', $event.target.value)"></textarea>
                            <div class="input-group-append d-flex flex-column justify-content-end">
                                <button type="submit" class="btn btn-primary chat-send-btn h-100"
                                    wire:loading.attr="disabled" @disabled($isLoading || !$providerStatus || $isRegenerating)
                                    title="{{ $isRegenerating ? 'Sedang regenerate response...' : 'Send message' }}">
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
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1"
                                    @click="console.log('Clear message button clicked, current message:', $wire.get('newMessage')); $wire.call('clearMessage')" title="Clear message">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info"
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

@push('scripts')
<script>
    // Listen for message-cleared event from Livewire
    document.addEventListener('livewire:init', () => {
        Livewire.on('message-cleared', (event) => {
            console.log('=== MESSAGE CLEARED EVENT ===');
            console.log('Event data:', event);
            console.log('Old message was:', event.old_message);
            console.log('Clear operation success:', event.success);
            console.log('Timestamp:', event.timestamp);
            console.log('============================');
        });
        
        // Monitor Livewire property changes
        Livewire.hook('morph.updated', ({ el, component }) => {
            if (el.classList.contains('chat-textarea')) {
                console.log('Livewire updated textarea, new value:', el.value);
            }
        });
        
        // Monitor all Livewire calls
        Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
            if (payload.calls && payload.calls.some(call => call.method === 'clearMessage')) {
                console.log('=== LIVEWIRE CLEAR MESSAGE CALL ===');
                console.log('Payload:', payload);
                console.log('================================');
            }
        });
    });
    
    // Additional debugging for Alpine.js interactions
    document.addEventListener('alpine:init', () => {
        console.log('Alpine.js initialized for AI Chat Widget');
    });
    
    // Debug clear button clicks
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded, setting up clear button debugging');
        
        // Add click listener to clear button for additional debugging
        const clearButton = document.querySelector('button[title="Clear message"]');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                console.log('=== CLEAR BUTTON CLICKED ===');
                console.log('Button element:', clearButton);
                console.log('Current textarea value:', document.querySelector('.chat-textarea')?.value);
                console.log('===========================');
            });
        }
    });
</script>
@endpush

@push('styles')
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
    /* .chat-window {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        width: 420px !important;
        height: 600px !important;
        position: relative;
    } */

    .chat-window {
        background: #ffffff !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15) !important;
        opacity: 1 !important;
        visibility: visible !important;
        display: block !important;
    }

    .chat-window .card-header {
        opacity: 1 !important;
        visibility: visible !important;
        display: flex !important;
    }

    .chat-window .card-header .btn,
    .chat-window .card-header button {
        opacity: 1 !important;
        visibility: visible !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
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
    
    /* Extra small button for model reset */
    .btn-xs {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
        line-height: 1.2;
        border-radius: 0.2rem;
    }
    
    /* Model status indicators */
    .text-warning small {
        font-size: 0.7rem;
    }
    
    .text-success small {
        font-size: 0.7rem;
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

    .message-text h1,
    .message-text h2,
    .message-text h3,
    .message-text h4,
    .message-text h5,
    .message-text h6 {
        margin: 0.5rem 0;
        font-weight: 600;
    }

    .message-text ul,
    .message-text ol {
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
        background-color: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
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

    .typing-indicator .dot:nth-child(1) {
        animation-delay: -0.32s;
    }

    .typing-indicator .dot:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes typing {

        0%,
        80%,
        100% {
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

    /* Auto-close Alert Styles */
    .countdown-progress .progress {
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
    }
    
    .countdown-progress .progress-bar {
        transition: width 1s linear;
        border-radius: 2px;
    }
    
    .alert-danger .countdown-progress .progress-bar {
        background-color: rgba(255, 255, 255, 0.8) !important;
    }
    
    .alert-success .countdown-progress .progress-bar {
        background-color: rgba(255, 255, 255, 0.8) !important;
    }
    
    .alert .btn-close {
        font-size: 0.75rem;
        opacity: 0.8;
    }
    
    .alert .btn-close:hover {
        opacity: 1;
    }
    
    .alert:hover .countdown-progress {
        opacity: 0.7;
    }
    
    .alert .countdown-progress small {
        font-size: 0.7rem;
        font-weight: 500;
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
    function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            // Show success message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Copied!',
                    text: 'Message copied to clipboard',
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Copied!',
                    text: 'Message copied to clipboard',
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
        document.body.removeChild(textArea);
    }
}

// Alpine.js component for auto-close alerts with countdown
function alertAutoClose(duration = 60, enableAutoClose = true, showCountdown = true, pauseOnHover = true) {
    return {
        show: true,
        timeLeft: duration,
        duration: duration,
        showCountdown: showCountdown,
        intervalId: null,
        isPaused: false,
        
        init() {
            if (enableAutoClose) {
                this.startTimer();
            }
        },
        
        startTimer() {
            this.intervalId = setInterval(() => {
                if (!this.isPaused) {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        this.closeAlert();
                    }
                }
            }, 1000);
        },
        
        pauseTimer() {
            if (pauseOnHover) {
                this.isPaused = true;
            }
        },
        
        resumeTimer() {
            if (pauseOnHover) {
                this.isPaused = false;
            }
        },
        
        closeAlert() {
            this.show = false;
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
            
            // Clear the Livewire message after animation
            setTimeout(() => {
                if (this.$el.classList.contains('alert-danger')) {
                    @this.set('errorMessage', '');
                } else if (this.$el.classList.contains('alert-success')) {
                    @this.set('successMessage', '');
                }
            }, 300);
        },
        
        destroy() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
        }
    }
}

// Initialize tooltips for disabled regenerate buttons
document.addEventListener('livewire:initialized', function() {
    const initTooltips = function() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    };

    // Initialize on page load
    if (typeof bootstrap !== 'undefined') {
        initTooltips();
    }

    // Re-initialize when content updates
    document.addEventListener('livewire:update', function() {
        setTimeout(function() {
            if (typeof bootstrap !== 'undefined') {
                initTooltips();
            }
        }, 100);
    });
});

// Register Alpine.js component globally
if (typeof Alpine !== 'undefined') {
    Alpine.data('alertAutoClose', alertAutoClose);
}
</script>
@endpush