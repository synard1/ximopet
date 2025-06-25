<!-- JavaScript Events -->
<script>
    document.addEventListener('livewire:init', () => {
    console.log('🔥 FIFO Mutation: Events initialized');
    
    // FIFO Mutation Completed
    Livewire.on('fifo-mutation-completed', (data) => {
        console.log('🔥 FIFO Mutation: Completed', data);
        
        const isEditMode = document.querySelector('.bg-warning') !== null;
        const title = isEditMode ? 'Mutasi FIFO Berhasil Diperbarui!' : 'Mutasi FIFO Berhasil!';
        const text = isEditMode ? 
            `Berhasil memperbarui mutasi ${data.total_quantity} ekor` :
            `Berhasil memutasi ${data.total_quantity} ekor`;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: title,
                text: text,
                confirmButtonText: 'OK'
            });
        }
    });

    // Success Message
    Livewire.on('show-success-message', (data) => {
        console.log('🔥 FIFO Mutation: Success message', data);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: data.title || 'Berhasil',
                text: data.message || 'Operasi berhasil',
                confirmButtonText: 'OK'
            });
        }
    });

    // Edit Mode Events
    Livewire.on('edit-mode-enabled', (data) => {
        console.log('🔥 FIFO Mutation: Edit mode enabled', data);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Mode Edit Diaktifkan',
                text: data.message || 'Data mutasi dimuat untuk diedit',
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        }
    });

    Livewire.on('edit-mode-cancelled', () => {
        console.log('🔥 FIFO Mutation: Edit mode cancelled');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Mode Edit Dibatalkan',
                text: 'Kembali ke mode input baru',
                confirmButtonText: 'OK',
                timer: 2000,
                timerProgressBar: true
            });
        }
    });

    // Enhanced mutation check trigger
    function triggerMutationCheckSafely() {
        if (window.Livewire && window.Livewire.find) {
            const component = window.Livewire.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
            if (component) {
                const methods = ['checkMutations', 'doCheck', 'triggerMutationCheck'];
                
                for (let method of methods) {
                    try {
                        component.call(method);
                        console.log(`🔥 FIFO Mutation: ${method} called successfully`);
                        break;
                    } catch (error) {
                        console.warn(`🔥 FIFO Mutation: ${method} failed`, error);
                    }
                }
            }
        }
    }

    // Backup triggers for form changes
    document.addEventListener('change', function(e) {
        if (e.target.matches('[wire\\:model\\.live="mutationDate"]') || 
            e.target.matches('[wire\\:model\\.live="sourceLivestockId"]')) {
            setTimeout(() => triggerMutationCheckSafely(), 100);
        }
    });

    // Enhanced modal event handling with auto-check
    document.addEventListener('livewire:navigated', function() {
        initializeFifoModalHandlers();
    });

    // Initialize modal handlers
    function initializeFifoModalHandlers() {
        // Modal shown event - auto-check for existing mutations
        document.addEventListener('show-fifo-mutation', function(e) {
            console.log('🔄 FIFO Modal show event triggered');
            
            // Auto-check for existing mutations after modal is fully shown
            setTimeout(() => {
                triggerAutoMutationCheck();
            }, 500); // Small delay to ensure modal is fully loaded
        });

        // Modal hidden event
        document.addEventListener('hide-fifo-livestock-mutation', function(e) {
            console.log('🔄 FIFO Modal hide event triggered');
            closeFifoModal();
        });

        // Edit mode enabled notification
        document.addEventListener('edit-mode-enabled', function(e) {
            console.log('✏️ Edit mode enabled event triggered', e.detail);
            
            if (e.detail && e.detail.message) {
                Swal.fire({
                    icon: 'info',
                    title: 'Mode Edit Aktif',
                    text: e.detail.message,
                    showConfirmButton: false,
                    timer: 3000,
                    toast: true,
                    position: 'top-end'
                });
            }
        });

        // Edit mode cancelled notification
        document.addEventListener('edit-mode-cancelled', function(e) {
            console.log('❌ Edit mode cancelled event triggered');
            
            Swal.fire({
                icon: 'success',
                title: 'Mode Edit Dibatalkan',
                text: 'Kembali ke mode create',
                showConfirmButton: false,
                timer: 2000,
                toast: true,
                position: 'top-end'
            });
        });

        // Restriction error notification
        document.addEventListener('fifo-mutation-restriction', function(e) {
            Swal.fire({
                icon: 'error',
                title: 'Pembatasan Mutasi',
                html: (e.detail && e.detail.message ? e.detail.message : 'Terjadi pembatasan mutasi.') +
                      (e.detail && e.detail.details ? '<br>' + e.detail.details.join('<br>') : ''),
                confirmButtonText: 'OK',
                didClose: function() {
                    // Auto-close modal if still open
                    const modal = document.querySelector('.modal.show');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }
                }
            });
        });

        // Success notification
        document.addEventListener('fifo-mutation-success', function(e) {
            Swal.fire({
                icon: 'success',
                title: 'Sukses',
                text: (e.detail && e.detail.message ? e.detail.message : 'Mutasi berhasil diproses!'),
                confirmButtonText: 'OK',
                didClose: function() {
                    // Auto-close modal if still open
                    const modal = document.querySelector('.modal.show');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }
                }
            });
        });

        // Error notification for mutation failure
        document.addEventListener('fifo-mutation-error', function(e) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Proses Mutasi',
                text: e.detail && e.detail.message ? e.detail.message : 'Terjadi error saat proses mutasi.',
                confirmButtonText: 'OK'
            });
        });
    }

    // Auto-check for existing mutations
    function triggerAutoMutationCheck() {
        try {
            console.log('🔍 Triggering auto mutation check...');
            
            // Get current values
            const dateInput = document.querySelector('input[wire\\:model\\.live="mutationDate"]');
            const livestockSelect = document.querySelector('select[wire\\:model\\.live="sourceLivestockId"]');
            
            if (dateInput && livestockSelect && dateInput.value && livestockSelect.value) {
                console.log('🔍 Auto-checking mutations for:', {
                    date: dateInput.value,
                    livestock: livestockSelect.value
                });
                
                // Trigger multiple methods for maximum reliability
                if (typeof @this !== 'undefined') {
                    // Method 1: Direct mutation check
                    if (typeof @this.checkForExistingMutations === 'function') {
                        @this.checkForExistingMutations();
                    }
                    
                    // Method 2: Trigger date update
                    if (typeof @this.updatedMutationDate === 'function') {
                        @this.updatedMutationDate(dateInput.value);
                    }
                    
                    // Method 3: Manual trigger
                    if (typeof @this.triggerExistingMutationCheck === 'function') {
                        @this.triggerExistingMutationCheck();
                    }
                }
            } else {
                console.log('🔍 Skipping auto-check - missing data:', {
                    hasDate: dateInput && dateInput.value,
                    hasLivestock: livestockSelect && livestockSelect.value
                });
            }
        } catch (error) {
            console.error('❌ Error in triggerAutoMutationCheck:', error);
        }
    }
});
</script>