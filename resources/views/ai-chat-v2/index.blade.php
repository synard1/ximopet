@extends('layout.app')

@section('title', 'AI Chat V2')

@section('content')
<div class="container-fluid p-0" x-data="{ activeTab: 'chat' }">
    <div class="row g-0" style="min-height: calc(100vh - 56px);">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-2 border-end bg-light">
            <div class="d-flex flex-column h-100">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">AI Chat V2</h5>
                </div>
                
                <div class="nav flex-column nav-pills p-2" role="tablist">
                    <button class="nav-link active" 
                            :class="{ 'active': activeTab === 'chat' }"
                            @click="activeTab = 'chat'">
                        <i class="fas fa-comment me-2"></i>Chat
                    </button>
                    <button class="nav-link" 
                            :class="{ 'active': activeTab === 'sessions' }"
                            @click="activeTab = 'sessions'">
                        <i class="fas fa-history me-2"></i>Sessions
                    </button>
                    <button class="nav-link" 
                            :class="{ 'active': activeTab === 'settings' }"
                            @click="activeTab = 'settings'">
                        <i class="fas fa-cog me-2"></i>Settings
                    </button>
                </div>
                
                <div class="mt-auto p-3 border-top">
                    <button class="btn btn-outline-primary w-100" onclick="history.back()">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9 col-xl-10">
            <div class="tab-content h-100">
                <!-- Chat Tab -->
                <div class="tab-pane h-100" :class="{ 'active': activeTab === 'chat' }">
                    <div class="h-100 d-flex flex-column">
                        @livewire('ai-chat-v2.chat-interface', ['sessionId' => $sessionId ?? null])
                    </div>
                </div>
                
                <!-- Sessions Tab -->
                <div class="tab-pane h-100" :class="{ 'active': activeTab === 'sessions' }">
                    <div class="h-100 d-flex flex-column">
                        @livewire('ai-chat-v2.session-list', ['currentSessionId' => $sessionId ?? null])
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div class="tab-pane h-100" :class="{ 'active': activeTab === 'settings' }">
                    <div class="h-100 d-flex flex-column">
                        @livewire('ai-chat-v2.settings-panel')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function() {
        // Listen for session selection events
        Livewire.on('session-selected', (event) => {
            // Redirect to the session
            window.location.href = '{{ route('ai-chat-v2.show', '') }}/' + event.sessionId;
        });
        
        // Listen for session deletion events
        Livewire.on('session-deleted', (event) => {
            // Redirect to main chat
            window.location.href = '{{ route('ai-chat-v2.index') }}';
        });
        
        // Listen for settings update events
        Livewire.on('settings-updated', (event) => {
            // Reload the page to apply settings
            window.location.reload();
        });
    });
</script>
@endpush
@endsection