<div>
    <!-- Debug: TempAuthorization component loaded -->
    <script>
        log('TempAuthorization ENHANCED component loaded', {
            showModal: @json($showModal), 
            authorized: @json($authorized),
            authMode: @json($authMode),
            selectedAuthMethod: @json($selectedAuthMethod)
        });
    </script>

    <!-- Status Display (if authorized) -->
    @if($authorized && $authExpiry)
    <div class="alert alert-warning d-flex align-items-center" wire:poll.10s="checkAuthorization">
        <i class="ki-outline ki-shield-tick fs-2 me-3 text-warning"></i>
        <div class="flex-grow-1">
            <div class="fw-bold">Autorisasi Temporer Aktif</div>
            <div class="text-muted small">
                Berakhir dalam: {{ $this->getTimeRemaining() }}
                | Alasan: {{ session('temp_auth_reason') }}
                | Oleh: {{ session('temp_auth_authorizer', session('temp_auth_user')) }}
                | Method: {{ ucfirst(session('temp_auth_method', 'password')) }}
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-light-danger" wire:click="revokeAuthorization"
            wire:confirm="Yakin ingin mencabut autorisasi temporer?">
            <i class="ki-outline ki-cross fs-4"></i>
            Cabut
        </button>
    </div>
    @endif

    <!-- Enhanced Modal for Multiple Auth Methods -->
    @if($showModal && !$authorized)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;"
        onclick="if(event.target === this) { @this.call('closeModal') }">
        <div style="background: white; padding: 0; border-radius: 12px; max-width: 600px; width: 95%; box-shadow: 0 8px 32px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;"
            onclick="event.stopPropagation()">

            <!-- Modal Header -->
            <div
                style="background: #f8f9fa; padding: 20px; border-radius: 12px 12px 0 0; border-bottom: 1px solid #dee2e6;">
                <div style="display: flex; justify-content: between; align-items: center;">
                    <h3 style="margin: 0; color: #333; display: flex; align-items: center;">
                        🔓 <span style="margin-left: 8px;">Autorisasi Temporer</span>
                    </h3>
                    <button wire:click="closeModal"
                        style="background: none; border: none; font-size: 24px; color: #999; cursor: pointer; line-height: 1;">&times;</button>
                </div>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 14px;">
                    Dapatkan akses edit untuk data yang dikunci selama {{ $authDurationMinutes }} menit
                </p>
            </div>

            <!-- Modal Body -->
            <div style="padding: 25px;">

                <!-- Auth Method Selection (if mixed mode) -->
                @if($authMode === 'mixed')
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px; color: #333;">Method
                        Autorisasi:</label>
                    <div style="display: flex; gap: 10px;">
                        <button wire:click="switchAuthMethod('password')"
                            style="padding: 10px 20px; border: 2px solid {{ $selectedAuthMethod === 'password' ? '#007bff' : '#ddd' }}; background: {{ $selectedAuthMethod === 'password' ? '#007bff' : 'white' }}; color: {{ $selectedAuthMethod === 'password' ? 'white' : '#333' }}; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            🔑 Password
                        </button>
                        <button wire:click="switchAuthMethod('user')"
                            style="padding: 10px 20px; border: 2px solid {{ $selectedAuthMethod === 'user' ? '#007bff' : '#ddd' }}; background: {{ $selectedAuthMethod === 'user' ? '#007bff' : 'white' }}; color: {{ $selectedAuthMethod === 'user' ? 'white' : '#333' }}; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            👤 User Authorization
                        </button>
                    </div>
                </div>
                @endif

                <!-- Error Message -->
                @if($errorMessage)
                <div
                    style="color: #dc3545; background: #f8d7da; padding: 12px; border: 1px solid #f5c6cb; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                    <strong>⚠️ Error:</strong> {{ $errorMessage }}
                </div>
                @endif

                <form wire:submit.prevent="grantAuthorization">

                    <!-- Password Authorization -->
                    @if($selectedAuthMethod === 'password')
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;">🔑 Password
                            Autorisasi:</label>
                        <input type="password" wire:model.defer="password"
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;"
                            placeholder="Masukkan password autorisasi..." autocomplete="new-password">
                        <small style="color: #666; font-size: 12px;">Default: admin123</small>
                    </div>
                    @endif

                    <!-- User Authorization -->
                    @if($selectedAuthMethod === 'user')
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;">👤
                            Email/Username Authorizer:</label>
                        <input type="text" wire:model.defer="authorizerEmail"
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;"
                            placeholder="Masukkan email atau username authorizer..." autocomplete="email">
                        <small style="color: #666; font-size: 12px;">Email atau username user yang memiliki hak
                            autorisasi</small>
                    </div>

                    @if(config('temp_auth.user.require_password', true))
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;">🔐 Password
                            Authorizer:</label>
                        <input type="password" wire:model.defer="authorizerPassword"
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;"
                            placeholder="Masukkan password authorizer..." autocomplete="new-password">
                        <small style="color: #666; font-size: 12px;">Password login user authorizer</small>
                    </div>
                    @endif
                    @endif

                    <!-- Reason (Required for both methods) -->
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;">📝 Alasan
                            Autorisasi:</label>
                        <textarea wire:model.defer="reason"
                            style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; resize: vertical; min-height: 80px;"
                            rows="3" placeholder="Jelaskan alasan mengapa perlu autorisasi temporer..."></textarea>
                        <small style="color: #666; font-size: 12px;">Alasan akan dicatat untuk audit trail</small>
                    </div>

                    <!-- Info Cards -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div
                            style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                            <div style="font-weight: bold; color: #1976d2; font-size: 14px;">⏱️ Durasi</div>
                            <div style="color: #424242; font-size: 13px;">{{ $authDurationMinutes }} menit</div>
                        </div>
                        <div
                            style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                            <div style="font-weight: bold; color: #f57c00; font-size: 14px;">🎯 Target</div>
                            <div style="color: #424242; font-size: 13px;">{{ $targetComponent ?: 'Form Data' }}</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 15px; justify-content: flex-end;">
                        <button type="button" wire:click="closeModal" @if($isProcessing) disabled @endif
                            style="padding: 12px 24px; background: {{ $isProcessing ? '#ccc' : '#6c757d' }}; color: white; border: none; border-radius: 6px; cursor: {{ $isProcessing ? 'not-allowed' : 'pointer' }}; font-size: 14px; font-weight: 500;">
                            ❌ Batal
                        </button>
                        <button type="submit" @if($isProcessing) disabled @endif
                            style="padding: 12px 24px; background: {{ $isProcessing ? '#ccc' : '#28a745' }}; color: white; border: none; border-radius: 6px; cursor: {{ $isProcessing ? 'not-allowed' : 'pointer' }}; font-size: 14px; font-weight: 500;">
                            @if($isProcessing)
                            ⏳ Memproses...
                            @else
                            🔓 Berikan Autorisasi
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        log('Enhanced modal is now visible!');
        // Auto focus pada field pertama yang visible
        setTimeout(() => {
            @if($selectedAuthMethod === 'password')
            const passwordInput = document.querySelector('input[type="password"]');
            if (passwordInput) {
                passwordInput.focus();
                log('Password field focused');
            }
            @elseif($selectedAuthMethod === 'user')
            const emailInput = document.querySelector('input[type="text"]');
            if (emailInput) {
                emailInput.focus();
                log('Email input field focused');
            }
            @endif
        }, 100);
    </script>
    @endif

    <!-- JavaScript Event Listeners -->
    <script>
        document.addEventListener('livewire:init', function () {
            log('TempAuthorization ENHANCED: Livewire initialized');
            
            // Listen for requestTempAuth event
            Livewire.on('requestTempAuth', function (targetComponent) {
                log('TempAuthorization ENHANCED: requestTempAuth event received', {targetComponent: targetComponent});
            });
            
            // Listen for auth events
            Livewire.on('tempAuthGranted', function (data) {
                log('TempAuthorization ENHANCED: tempAuthGranted event received', data);
            });

            Livewire.on('tempAuthRevoked', function () {
                log('TempAuthorization ENHANCED: tempAuthRevoked event received');
            });

            // Listen for close modal event
            Livewire.on('closeAuthModal', function () {
                log('TempAuthorization: closeAuthModal event received');
                setTimeout(() => {
                    @this.call('forceCloseModal');
                    log('Modal force closed');
                }, 1000); // Delay 1 second to show success message
            });

            // Listen for authorization success and auto-close modal
            Livewire.on('authorizationSuccess', function (data) {
                log('Authorization success event received:', data);
                setTimeout(() => {
                    @this.call('closeModal');
                    log('Modal closed after authorization success');
                }, data.closeDelay || 1500);
            });
        });
    </script>
</div>