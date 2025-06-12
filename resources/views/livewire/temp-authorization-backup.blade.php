<div>
    <!-- Debug: TempAuthorization component loaded -->
    <script>
        console.log('TempAuthorization component loaded', {showModal: @json($showModal), authorized: @json($authorized)});
        
        // Debug modal visibility
        @if($showModal)
        console.log('Modal should be visible now');
        setTimeout(() => {
            const modal = document.getElementById('tempAuthModal');
            if (modal) {
                console.log('Modal element found:', {
                    display: modal.style.display,
                    visibility: modal.style.visibility,
                    classes: modal.className,
                    offsetHeight: modal.offsetHeight,
                    offsetWidth: modal.offsetWidth
                });
            } else {
                console.log('Modal element NOT found in DOM');
            }
        }, 100);
        @endif
    </script>

    <!-- Temporary Authorization Status Display -->
    @if($authorized && $authExpiry)
    <div class="alert alert-warning d-flex align-items-center" wire:poll.10s="checkAuthorization">
        <i class="ki-outline ki-shield-tick fs-2 me-3 text-warning"></i>
        <div class="flex-grow-1">
            <div class="fw-bold">Autorisasi Temporer Aktif</div>
            <div class="text-muted small">
                Berakhir dalam: {{ $this->getTimeRemaining() }}
                | Alasan: {{ session('temp_auth_reason') }}
                | Oleh: {{ session('temp_auth_user') }}
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-light-danger" wire:click="revokeAuthorization"
            wire:confirm="Yakin ingin mencabut autorisasi temporer?">
            <i class="ki-outline ki-cross fs-4"></i>
            Cabut
        </button>
    </div>
    @endif

    <!-- Temporary Authorization Modal -->
    <div wire:ignore.self class="modal @if($showModal) show @endif" id="tempAuthModal" tabindex="-1" @if($showModal)
        style="display: block !important; z-index: 1050; position: fixed;" @else style="display: none;" @endif
        aria-hidden="@if($showModal) false @else true @endif">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ki-outline ki-shield-search fs-2 me-2 text-primary"></i>
                        Autorisasi Temporer
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                <form wire:submit.prevent="grantAuthorization">
                    <div class="modal-body">
                        <div class="alert alert-info mb-4">
                            <i class="ki-outline ki-information-5 fs-2 me-2"></i>
                            <strong>Informasi:</strong> Autorisasi temporer akan memberikan akses untuk mengubah data
                            yang sudah di-lock selama {{ $authDurationMinutes }} menit.
                        </div>

                        @if($errorMessage)
                        <div class="alert alert-danger">
                            <i class="ki-outline ki-cross-circle fs-2 me-2"></i>
                            {{ $errorMessage }}
                        </div>
                        @endif

                        <div class="mb-4">
                            <label class="form-label fw-semibold required">Password Autorisasi</label>
                            <input type="password" wire:model.defer="password" class="form-control"
                                placeholder="Masukkan password autorisasi" autocomplete="new-password">
                            <div class="form-text">Password khusus untuk autorisasi temporer</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold required">Alasan Autorisasi</label>
                            <textarea wire:model.defer="reason" class="form-control" rows="3"
                                placeholder="Jelaskan alasan mengapa perlu autorisasi temporer..."></textarea>
                            <div class="form-text">Alasan akan dicatat untuk audit trail</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light-primary">
                                    <div class="card-body p-3">
                                        <div class="fw-bold text-primary">Durasi Autorisasi</div>
                                        <div class="text-muted">{{ $authDurationMinutes }} menit</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light-warning">
                                    <div class="card-body p-3">
                                        <div class="fw-bold text-warning">Target</div>
                                        <div class="text-muted">{{ $targetComponent ?: 'Form Data' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeModal">
                            <i class="ki-outline ki-cross fs-4 me-1"></i>
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-outline ki-shield-tick fs-4 me-1"></i>
                            Berikan Autorisasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="modal-backdrop fade show" style="z-index: 1040;"></div>
    @endif

    <script>
        document.addEventListener('livewire:init', function () {
            console.log('TempAuthorization: Livewire initialized');
            
            // Listen for requestTempAuth event
            Livewire.on('requestTempAuth', function (targetComponent) {
                console.log('TempAuthorization: requestTempAuth event received', {targetComponent: targetComponent});
                
                // Force check modal after event
                setTimeout(() => {
                    const modal = document.getElementById('tempAuthModal');
                    if (modal) {
                        console.log('Modal after requestTempAuth:', {
                            display: modal.style.display,
                            classes: modal.className,
                            visible: modal.offsetHeight > 0
                        });
                    }
                }, 500);
            });
            
            // Auto focus password field when modal opens
            Livewire.on('tempAuthGranted', function () {
                console.log('TempAuthorization: tempAuthGranted event received');
                // Refresh the page or reload specific components if needed
                // window.location.reload();
            });

            Livewire.on('tempAuthRevoked', function () {
                console.log('TempAuthorization: tempAuthRevoked event received');
                // Refresh the page or reload specific components if needed
                // window.location.reload();
            });
        });

        // Auto focus on password field when modal is shown
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const modal = document.getElementById('tempAuthModal');
                        if (modal && modal.style.display === 'block') {
                            setTimeout(() => {
                                const passwordInput = modal.querySelector('input[type="password"]');
                                if (passwordInput) {
                                    passwordInput.focus();
                                }
                            }, 100);
                        }
                    }
                });
            });

            const modal = document.getElementById('tempAuthModal');
            if (modal) {
                observer.observe(modal, { attributes: true });
            }
        });
    </script>
</div>