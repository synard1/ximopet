<?php

namespace App\Livewire\Livestock\Mutation;

use App\Models\Livestock;
use App\Models\LivestockMutation;
use App\Models\Coop;
use App\Services\Livestock\LivestockMutationService;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class FifoLivestockMutation extends Component
{
    use WithPagination;

    // Basic mutation properties
    public $mutationDate;
    public $sourceLivestockId;
    public $destinationLivestockId;
    public $destinationCoopId;
    public $type = 'internal';
    public $direction = 'out';
    public $reason;
    public $notes;
    public $showFifoMutation = false;

    // FIFO specific properties
    public $mutationMethod = 'fifo';
    public $quantity = 0;
    public $fifoPreview = null;
    public $isPreviewMode = false;
    public $showPreviewModal = false;
    public $processingMutation = false;

    // Edit mode properties
    public $isEditing = false;
    public $existingMutationIds = [];
    public $editModeMessage = '';

    // UI state
    public $showModal = false;
    public $isLoading = false;
    public $errorMessage = '';
    public $successMessage = '';

    // Configuration
    public $config = [];
    public $validationRules = [];
    public $workflowSettings = [];
    public $fifoSettings = [];

    // Livestock data
    public $sourceLivestock;
    public $destinationLivestock;
    public $allLivestock = [];
    public $allCoops = [];
    public $destinationCoop;
    public $availableBatches;
    public $totalAvailableQuantity = 0;

    // Input restriction properties
    public $restrictionMessage = '';
    public $restrictionTypes = [];
    public $restrictionDetails = [];
    public $restrictionAction = null;

    protected $listeners = [
        'show-fifo-mutation' => 'openModal',
        'show-fifo-simple-modal' => 'openModal',
        'openFifoMutationModal' => 'openModal',
        'closeFifoMutationModal' => 'closeModal',
        'refreshFifoMutationData' => 'refreshData'
    ];

    // Validation rules
    protected $rules = [
        'mutationDate' => 'required|date',
        'sourceLivestockId' => 'required|string',
        'quantity' => 'required|integer|min:1',
        'type' => 'required|string',
        'direction' => 'required|in:in,out',
        'reason' => 'nullable|string|max:500',
        'destinationLivestockId' => 'nullable|string',
        'destinationCoopId' => 'nullable|string',
    ];

    protected $messages = [
        'mutationDate.required' => 'Tanggal mutasi harus diisi',
        'mutationDate.date' => 'Format tanggal tidak valid',
        'sourceLivestockId.required' => 'Sumber ternak harus dipilih',
        'quantity.required' => 'Kuantitas harus diisi',
        'quantity.integer' => 'Kuantitas harus berupa angka',
        'quantity.min' => 'Kuantitas minimal 1',
        'type.required' => 'Jenis mutasi harus dipilih',
        'direction.required' => 'Arah mutasi harus dipilih',
        'direction.in' => 'Arah mutasi harus in atau out',
        'reason.max' => 'Alasan maksimal 500 karakter',
    ];

    public function mount($livestockId = null)
    {
        $this->initializeComponent();

        if ($livestockId) {
            $this->sourceLivestockId = $livestockId;
            // Don't auto-load source livestock to prevent looping
            // User needs to click the load button manually
        }

        Log::info('🔄 FIFO Livestock Mutation component mounted', [
            'user_id' => auth()->id(),
            'livestock_id' => $livestockId
        ]);
    }

    /**
     * Initialize component with default values and configuration
     */
    private function initializeComponent(): void
    {
        $this->mutationDate = now()->format('Y-m-d');
        $this->loadConfiguration();
        $this->loadLivestockOptions();
        $this->loadCoopOptions();

        Log::info('🔄 FIFO Livestock Mutation component initialized', [
            'total_livestock' => count($this->allLivestock),
            'total_coops' => count($this->allCoops),
            'config_loaded' => !empty($this->config)
        ]);
    }

    /**
     * Load configuration from CompanyConfig
     */
    private function loadConfiguration(): void
    {
        $this->config = CompanyConfig::getFifoMutationConfig();
        $this->validationRules = CompanyConfig::getFifoMutationValidationRules();
        $this->workflowSettings = CompanyConfig::getFifoMutationWorkflowSettings();
        $this->fifoSettings = CompanyConfig::getFifoMutationConfig()['fifo_settings'] ?? [];

        Log::info('📋 Configuration loaded for FifoLivestockMutation', [
            'fifo_enabled' => $this->fifoSettings['enabled'] ?? false
        ]);
    }

    /**
     * Load livestock options for dropdown
     */
    private function loadLivestockOptions(): void
    {
        $this->allLivestock = Livestock::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($livestock) {
                return [
                    'id' => $livestock->id,
                    'name' => $livestock->name,
                    'farm_name' => $livestock->farm->name ?? 'Unknown Farm',
                    'coop_name' => $livestock->coop->name ?? 'Unknown Coop',
                    'current_quantity' => $livestock->currentLivestock->quantity ?? 0,
                    'display_name' => sprintf(
                        '%s (%s - %s)',
                        $livestock->name,
                        $livestock->farm->name ?? 'Unknown Farm',
                        $livestock->coop->name ?? 'Unknown Coop'
                    )
                ];
            })
            ->toArray();
    }

    /**
     * Load coop options for destination selection
     */
    private function loadCoopOptions(): void
    {
        $this->allCoops = Coop::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->with(['farm', 'livestocks'])
            ->orderBy('name')
            ->get()
            ->map(function ($coop) {
                $livestockCount = $coop->livestocks->count();
                $totalQuantity = $coop->livestocks->sum(function ($livestock) {
                    return $livestock->currentLivestock->quantity ?? 0;
                });

                return [
                    'id' => $coop->id,
                    'name' => $coop->name,
                    'farm_name' => $coop->farm->name ?? 'Unknown Farm',
                    'livestock_count' => $livestockCount,
                    'total_quantity' => $totalQuantity,
                    'display_name' => sprintf(
                        '%s (%s) - %d ternak, %d ekor',
                        $coop->name,
                        $coop->farm->name ?? 'Unknown Farm',
                        $livestockCount,
                        $totalQuantity
                    )
                ];
            })
            ->toArray();
    }

    /**
     * Load source livestock data
     */
    public function loadSourceLivestock()
    {
        if (!$this->sourceLivestockId) {
            $this->availableBatches = collect();
            $this->totalAvailableQuantity = 0;
            $this->sourceLivestock = null;
            return;
        }

        // Prevent loading the same livestock multiple times
        if ($this->sourceLivestock && $this->sourceLivestock->id === $this->sourceLivestockId) {
            Log::info('🔄 Source livestock already loaded, skipping reload', [
                'livestock_id' => $this->sourceLivestockId,
                'livestock_name' => $this->sourceLivestock->name
            ]);
            return;
        }

        try {
            $this->sourceLivestock = Livestock::with(['farm', 'coop', 'batches' => function ($query) {
                $query->where('status', 'active')
                    ->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0')
                    ->orderBy('start_date', 'asc');
            }])->findOrFail($this->sourceLivestockId);

            $this->availableBatches = $this->sourceLivestock->batches ?? collect();
            $this->totalAvailableQuantity = $this->calculateTotalAvailableQuantity();

            Log::info('📊 Source livestock loaded for FIFO mutation', [
                'livestock_id' => $this->sourceLivestockId,
                'livestock_name' => $this->sourceLivestock->name,
                'total_batches' => $this->availableBatches->count(),
                'total_available_quantity' => $this->totalAvailableQuantity,
                'source_livestock_loaded' => !is_null($this->sourceLivestock)
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error loading source livestock for FIFO mutation', [
                'livestock_id' => $this->sourceLivestockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('sourceLivestockId', 'Gagal memuat data ternak sumber: ' . $e->getMessage());
            $this->availableBatches = collect();
            $this->totalAvailableQuantity = 0;
            $this->sourceLivestock = null;
        }
    }

    /**
     * Calculate total available quantity from all batches
     */
    private function calculateTotalAvailableQuantity(): int
    {
        if (!$this->availableBatches || !$this->availableBatches instanceof \Illuminate\Support\Collection) {
            return 0;
        }

        return $this->availableBatches->sum(function ($batch) {
            return $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
        });
    }

    /**
     * Generate FIFO preview
     */
    public function generateFifoPreview()
    {
        $this->validate([
            'sourceLivestockId' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            $service = new LivestockMutationService();

            $mutationData = [
                'source_livestock_id' => $this->sourceLivestockId,
                'quantity' => $this->quantity,
                'date' => $this->mutationDate,
                'type' => $this->type,
                'direction' => $this->direction,
                'reason' => $this->reason,
                'destination_livestock_id' => $this->destinationLivestockId,
                'destination_coop_id' => $this->destinationCoopId,
            ];

            $this->fifoPreview = $service->previewFifoBatchMutation($mutationData);
            $this->isPreviewMode = true;
            $this->showPreviewModal = true;

            Log::info('👁️ FIFO preview generated', [
                'source_livestock_id' => $this->sourceLivestockId,
                'requested_quantity' => $this->quantity,
                'can_fulfill' => $this->fifoPreview['can_fulfill'],
                'batches_count' => $this->fifoPreview['batches_count']
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error generating FIFO preview', [
                'error' => $e->getMessage(),
                'source_livestock_id' => $this->sourceLivestockId,
                'quantity' => $this->quantity
            ]);
            $this->addError('quantity', $e->getMessage());
        }
    }

    /**
     * Process FIFO mutation
     */
    public function processFifoMutation()
    {
        $this->validate();

        // Cegah mutasi ke diri sendiri (livestock atau kandang sama)
        $isSameLivestock = $this->sourceLivestockId && $this->destinationLivestockId && $this->sourceLivestockId === $this->destinationLivestockId;
        $isSameCoop = $this->sourceLivestock && $this->destinationCoopId && $this->sourceLivestock->coop_id === $this->destinationCoopId;
        if ($isSameLivestock || $isSameCoop) {
            $msg = 'Mutasi ke ternak atau kandang yang sama tidak diperbolehkan.';
            Log::warning('🚫 Percobaan mutasi ke diri sendiri dicegah', [
                'sourceLivestockId' => $this->sourceLivestockId,
                'destinationLivestockId' => $this->destinationLivestockId,
                'sourceCoopId' => $this->sourceLivestock->coop_id ?? null,
                'destinationCoopId' => $this->destinationCoopId
            ]);
            $this->addError('destinationLivestockId', $msg);
            $this->addError('destinationCoopId', $msg);
            $this->errorMessage = $msg;
            return;
        }

        if (!$this->fifoPreview || !$this->fifoPreview['can_fulfill']) {
            $this->addError('quantity', 'Kuantitas tidak dapat dipenuhi dengan batch yang tersedia');
            return;
        }

        // Ensure source livestock is loaded before processing
        if (!$this->sourceLivestock && $this->sourceLivestockId) {
            $this->loadSourceLivestock();
        }

        // Double-check that source livestock is available
        if (!$this->sourceLivestock) {
            $this->addError('sourceLivestockId', 'Data ternak sumber tidak ditemukan. Silakan pilih ternak sumber lagi.');
            return;
        }

        $this->processingMutation = true;

        try {
            $service = new LivestockMutationService();

            $mutationData = [
                'source_livestock_id' => $this->sourceLivestockId,
                'quantity' => $this->quantity,
                'date' => $this->mutationDate,
                'type' => $this->type,
                'direction' => $this->direction,
                'reason' => $this->reason,
                'destination_livestock_id' => $this->destinationLivestockId,
                'destination_coop_id' => $this->destinationCoopId,
                'existing_mutation_ids' => $this->existingMutationIds,
            ];

            Log::info('🔄 Processing FIFO mutation with source livestock', [
                'source_livestock_id' => $this->sourceLivestockId,
                'source_livestock_name' => $this->sourceLivestock->name ?? 'Unknown',
                'quantity' => $this->quantity,
                'source_livestock_loaded' => !is_null($this->sourceLivestock)
            ]);

            $result = $service->processFifoMutation($this->sourceLivestock, $mutationData);

            if ($result['success']) {
                // Show success message with debugging
                $successMessage = $result['message'] ?? 'Mutasi FIFO berhasil diproses';

                // Customize message for edit mode
                if ($this->isEditing) {
                    $successMessage = 'Mutasi FIFO berhasil diperbarui';
                }

                $this->showSuccessMessage($successMessage);

                // Dispatch event for UI updates with debugging
                $eventData = [
                    'mutation_id' => $result['mutation_id'],
                    'method' => 'fifo',
                    'total_quantity' => $result['total_quantity']
                ];

                Log::info('🔥 Dispatching fifo-mutation-completed event', $eventData);
                $this->dispatch('fifo-mutation-completed', $eventData);

                Log::info('✅ FIFO mutation completed successfully', [
                    'mutation_id' => $result['mutation_id'],
                    'total_quantity' => $result['total_quantity'],
                    'batches_used' => $result['batches_used']
                ]);

                // Reset form after successful processing and event dispatch
                $this->resetForm(false);
            }
        } catch (\Exception $e) {
            Log::error('❌ FIFO mutation failed', [
                'error' => $e->getMessage(),
                'source_livestock_id' => $this->sourceLivestockId,
                'source_livestock_loaded' => !is_null($this->sourceLivestock),
                'quantity' => $this->quantity
            ]);
            $this->addError('mutation', $e->getMessage());
        } finally {
            $this->processingMutation = false;
            $this->showPreviewModal = false;
        }
    }

    /**
     * Reset form to initial state
     */
    public function resetForm($preserveSourceLivestock = false)
    {
        // Reset properties manually instead of using $this->reset() to prevent re-render
        $this->mutationDate = now()->format('Y-m-d');
        $this->quantity = 0;
        $this->type = 'internal';
        $this->direction = 'out';
        $this->reason = null;
        $this->destinationLivestockId = null;
        $this->destinationCoopId = null;
        $this->fifoPreview = null;
        $this->isPreviewMode = false;
        $this->showPreviewModal = false;
        $this->existingMutationIds = [];
        $this->isEditing = false;
        $this->editModeMessage = '';
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->processingMutation = false;

        // Reset restriction properties
        $this->restrictionMessage = '';
        $this->restrictionTypes = [];
        $this->restrictionDetails = [];
        $this->restrictionAction = null;

        // Only clear source livestock if not preserving it
        if (!$preserveSourceLivestock) {
            $this->sourceLivestockId = null;
            $this->sourceLivestock = null;
            $this->availableBatches = collect();
            $this->totalAvailableQuantity = 0;
        }

        Log::info('🔄 FIFO mutation form reset', [
            'preserve_source_livestock' => $preserveSourceLivestock,
            'source_livestock_id' => $this->sourceLivestockId,
            'source_livestock_loaded' => !is_null($this->sourceLivestock)
        ]);
    }

    /**
     * Show success message
     */
    private function showSuccessMessage($message)
    {
        $eventData = [
            'title' => 'Mutasi FIFO Berhasil',
            'message' => $message
        ];

        Log::info('🔥 Dispatching show-success-message event', $eventData);
        $this->dispatch('show-success-message', $eventData);
    }

    /**
     * Close preview modal
     */
    public function closePreviewModal()
    {
        $this->showPreviewModal = false;
        $this->fifoPreview = null;
        $this->isPreviewMode = false;
    }

    /**
     * Get FIFO configuration
     */
    public function getFifoConfig()
    {
        return CompanyConfig::getFifoMutationConfig()['fifo_settings'] ?? [];
    }

    /**
     * Check if FIFO is enabled
     */
    public function getFifoEnabledProperty()
    {
        $config = $this->getFifoConfig();
        return $config['enabled'] ?? false;
    }

    /**
     * Get minimum age for FIFO
     */
    public function getMinAgeDaysProperty()
    {
        $config = $this->getFifoConfig();
        return $config['min_age_days'] ?? 0;
    }

    /**
     * Get maximum age for FIFO
     */
    public function getMaxAgeDaysProperty()
    {
        $config = $this->getFifoConfig();
        return $config['max_age_days'] ?? 999;
    }

    /**
     * Validate quantity against available batches
     */
    public function updatedQuantity($value)
    {
        if ($value > 0 && $this->sourceLivestockId) {
            $this->validateOnly('quantity');

            if ($value > $this->totalAvailableQuantity) {
                $this->addError('quantity', "Kuantitas melebihi ketersediaan. Tersedia: {$this->totalAvailableQuantity}");
            }
        }
    }

    /**
     * Updated source livestock selection
     */
    public function updatedSourceLivestockId($value)
    {
        if ($value) {
            // Reset related data first
            $this->quantity = 0;
            $this->fifoPreview = null;
            $this->availableBatches = collect();
            $this->totalAvailableQuantity = 0;
            $this->sourceLivestock = null;

            // Check for existing mutations when both date and livestock are set
            if ($this->mutationDate) {
                $this->checkForExistingMutations();
            }
        } else {
            // Clear data when no livestock selected
            $this->availableBatches = collect();
            $this->totalAvailableQuantity = 0;
            $this->sourceLivestock = null;
            $this->quantity = 0;
            $this->fifoPreview = null;
            $this->resetEditMode();
        }
    }

    /**
     * Updated mutation date selection
     */
    public function updatedMutationDate($value)
    {
        Log::info('🔍 Mutation date updated triggered', [
            'new_value' => $value,
            'current_mutation_date' => $this->mutationDate,
            'source_livestock_id' => $this->sourceLivestockId
        ]);

        if ($value && $this->sourceLivestockId) {
            $this->checkForExistingMutations();
        } else {
            $this->resetEditMode();
        }
    }

    /**
     * Universal Livewire updated handler - fallback for specific methods
     */
    public function updated($property, $value)
    {
        Log::info('🔍 Universal updated triggered', [
            'property' => $property,
            'value' => $value,
            'mutation_date' => $this->mutationDate,
            'source_livestock_id' => $this->sourceLivestockId
        ]);

        // Handle mutation date changes
        if ($property === 'mutationDate') {
            if ($value && $this->sourceLivestockId) {
                $this->checkForExistingMutations();
            } else {
                $this->resetEditMode();
            }
        }

        // Handle source livestock changes
        if ($property === 'sourceLivestockId') {
            if ($value && $this->mutationDate) {
                $this->checkForExistingMutations();
            } else {
                $this->resetEditMode();
            }
        }
    }

    /**
     * Cancel edit mode and reset to normal mode
     */
    public function cancelEditMode(): void
    {
        $this->isEditing = false;
        $this->existingMutationIds = [];
        $this->editModeMessage = '';

        // Reset form to initial state
        $this->resetForm(false);

        // Dispatch edit mode cancelled event
        $this->dispatch('edit-mode-cancelled');

        Log::info('🔄 Edit mode cancelled for FIFO mutation', [
            'user_id' => auth()->id()
        ]);
    }

    // ===========================================
    // MUTATION CHECK METHODS - PRODUCTION READY
    // ===========================================

    /**
     * Primary method - Check for existing mutations (MAIN METHOD)
     * This is the core method called by all other trigger methods
     */
    public function checkForExistingMutations(): void
    {
        try {
            Log::info('🔍 checkForExistingMutations called', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'is_editing' => $this->isEditing,
                'direction' => $this->direction,
                'type' => $this->type,
                'method' => 'checkForExistingMutations'
            ]);

            // Early return conditions
            if (!$this->sourceLivestockId || !$this->mutationDate || $this->isEditing) {
                Log::info('🔍 checkForExistingMutations early return', [
                    'has_source_livestock' => !empty($this->sourceLivestockId),
                    'has_mutation_date' => !empty($this->mutationDate),
                    'is_editing' => $this->isEditing
                ]);
                return;
            }

            // Load source livestock first if not loaded
            if (!$this->sourceLivestock) {
                $this->loadSourceLivestock();
            }

            // Query for existing mutations
            $existingMutations = LivestockMutation::where('source_livestock_id', $this->sourceLivestockId)
                ->whereDate('tanggal', $this->mutationDate)
                ->where('direction', $this->direction)
                ->where('jenis', $this->type)
                ->get();

            Log::info('🔍 Existing mutations query result', [
                'count' => $existingMutations->count(),
                'query_params' => [
                    'source_livestock_id' => $this->sourceLivestockId,
                    'mutation_date' => $this->mutationDate,
                    'direction' => $this->direction,
                    'type' => $this->type
                ]
            ]);

            if ($existingMutations->count() > 0) {
                $this->handleExistingMutationsFound($existingMutations);
            } else {
                $this->handleNoExistingMutations();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in checkForExistingMutations', [
                'livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->errorMessage = 'Error checking existing mutations: ' . $e->getMessage();
        }
    }

    /**
     * Handle when existing mutations are found
     */
    private function handleExistingMutationsFound($existingMutations): void
    {
        $mutationIds = $existingMutations->pluck('id')->toArray();

        Log::info('🔍 Existing FIFO mutations found, auto-switching to edit mode', [
            'source_livestock_id' => $this->sourceLivestockId,
            'mutation_date' => $this->mutationDate,
            'mutation_count' => $existingMutations->count(),
            'mutation_ids' => $mutationIds
        ]);

        $this->loadEditMode(['mutation_ids' => $mutationIds]);

        // Show notification to user
        $this->successMessage = sprintf(
            'Ditemukan %d mutasi FIFO pada tanggal %s. Mode edit diaktifkan.',
            $existingMutations->count(),
            Carbon::parse($this->mutationDate)->format('d/m/Y')
        );

        // Dispatch edit mode enabled event
        $this->dispatch('edit-mode-enabled', [
            'message' => sprintf(
                'Ditemukan %d mutasi FIFO pada tanggal %s. Data telah dimuat untuk diedit.',
                $existingMutations->count(),
                Carbon::parse($this->mutationDate)->format('d/m/Y')
            ),
            'mutation_count' => $existingMutations->count(),
            'mutation_date' => $this->mutationDate
        ]);

        // Clear any restriction messages
        $this->clearRestrictionMessages();
    }

    /**
     * Handle when no existing mutations found
     */
    private function handleNoExistingMutations(): void
    {
        // No existing mutations found, ensure edit mode is reset
        $this->resetEditMode();
    }

    /**
     * Clear restriction messages
     */
    private function clearRestrictionMessages(): void
    {
        $this->restrictionMessage = '';
        $this->restrictionTypes = [];
        $this->restrictionDetails = [];
        $this->restrictionAction = null;
    }

    /**
     * Method 1 - Simple mutation check (SAFE METHOD)
     * This is the safest method to call from UI
     */
    public function checkMutations(): void
    {
        try {
            Log::info('🔍 checkMutations called (SAFE METHOD)', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'method' => 'checkMutations'
            ]);

            if ($this->sourceLivestockId && $this->mutationDate && !$this->isEditing) {
                $this->checkForExistingMutations();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in checkMutations', [
                'error' => $e->getMessage(),
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate
            ]);
        }
    }

    /**
     * Method 2 - Trigger existing mutation check (BACKUP METHOD)
     */
    public function triggerMutationCheck(): void
    {
        try {
            Log::info('🔍 triggerMutationCheck called (BACKUP METHOD)', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'method' => 'triggerMutationCheck'
            ]);

            if ($this->sourceLivestockId && $this->mutationDate) {
                $this->checkForExistingMutations();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in triggerMutationCheck', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Method 3 - Process mutation check (ALTERNATIVE METHOD)
     */
    public function processMutationCheck(): void
    {
        try {
            Log::info('🔍 processMutationCheck called (ALTERNATIVE METHOD)', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'method' => 'processMutationCheck'
            ]);

            if ($this->sourceLivestockId && $this->mutationDate && !$this->isEditing) {
                $this->checkForExistingMutations();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in processMutationCheck', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Method 4 - Manual trigger (LEGACY SUPPORT)
     * Keeping for backward compatibility
     */
    public function triggerExistingMutationCheck(): void
    {
        try {
            Log::info('🔍 triggerExistingMutationCheck called (LEGACY METHOD)', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'method' => 'triggerExistingMutationCheck'
            ]);

            if ($this->sourceLivestockId && $this->mutationDate) {
                $this->checkForExistingMutations();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in triggerExistingMutationCheck', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Method 5 - Alternative check (FALLBACK METHOD)
     */
    public function checkExistingMutations(): void
    {
        try {
            Log::info('🔍 checkExistingMutations called (FALLBACK METHOD)', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'method' => 'checkExistingMutations'
            ]);

            $this->checkForExistingMutations();
        } catch (\Exception $e) {
            Log::error('❌ Error in checkExistingMutations', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Method 6 - Simple do check (SIMPLE METHOD)
     * Removed the problematic doMutationCheck and replaced with simpler name
     */
    public function doCheck(): void
    {
        try {
            Log::info('🔍 doCheck called (SIMPLE METHOD)', [
                'source_livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'method' => 'doCheck'
            ]);

            if ($this->sourceLivestockId && $this->mutationDate && !$this->isEditing) {
                $this->checkForExistingMutations();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in doCheck', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===========================================
    // END MUTATION CHECK METHODS
    // ===========================================

    /**
     * Open modal
     */
    public function openModal($livestockId = null, $editData = null): void
    {
        // Prevent multiple opens
        if ($this->showModal) {
            Log::info('🔄 FIFO mutation modal already open, skipping', [
                'livestock_id' => $livestockId,
                'edit_mode' => !empty($editData)
            ]);
            return;
        }

        // Reset form only if not already in edit mode
        if (!$this->isEditing) {
            $this->resetForm(false);
        }

        if ($livestockId) {
            $this->sourceLivestockId = $livestockId;
            // Don't auto-load source livestock to prevent looping
            // User needs to click the load button manually
        }

        if ($editData) {
            $this->loadEditMode($editData);
        }

        $this->showModal = true;

        Log::info('🔄 FIFO mutation modal opened', [
            'livestock_id' => $livestockId,
            'edit_mode' => !empty($editData),
            'show_modal' => $this->showModal,
            'source_livestock_id' => $this->sourceLivestockId,
            'source_livestock_loaded' => !is_null($this->sourceLivestock)
        ]);

        // Dispatch event to show FIFO mutation container
        $this->dispatch('show-fifo-mutation');
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;

        // Reset all properties including source livestock when closing modal
        $this->resetForm(false);

        // Dispatch event to hide FIFO mutation container
        $this->dispatch('hide-fifo-mutation');

        Log::info('🔄 FIFO mutation modal closed', [
            'show_modal' => $this->showModal,
            'source_livestock_cleared' => is_null($this->sourceLivestock)
        ]);
    }

    /**
     * Load edit mode data
     */
    private function loadEditMode(array $editData): void
    {
        $this->isEditing = true;
        $this->existingMutationIds = $editData['mutation_ids'] ?? [];
        $this->editModeMessage = sprintf(
            'Mode Edit: %d mutasi FIFO akan diperbarui',
            count($this->existingMutationIds)
        );

        // Load existing mutation data
        if (!empty($this->existingMutationIds)) {
            $this->loadExistingMutationData();
        }

        Log::info('✅ Edit mode loaded successfully', [
            'mutation_ids' => $this->existingMutationIds,
            'mutation_count' => count($this->existingMutationIds)
        ]);
    }

    /**
     * Load existing mutation data for edit mode
     */
    private function loadExistingMutationData(): void
    {
        try {
            $mutation = LivestockMutation::with(['items.batch'])->find($this->existingMutationIds[0]);
            if ($mutation) {
                // Load basic mutation data
                $this->mutationDate = $mutation->tanggal->format('Y-m-d');
                $this->sourceLivestockId = $mutation->source_livestock_id ?? $mutation->from_livestock_id;
                $this->type = $mutation->jenis;
                $this->direction = $mutation->direction;
                $this->reason = $mutation->keterangan;
                $this->destinationLivestockId = $mutation->destination_livestock_id ?? $mutation->to_livestock_id;
                $this->destinationCoopId = $mutation->destination_coop_id;

                // Calculate total quantity from items
                $this->quantity = $mutation->items->sum('quantity');

                // Load source livestock if not already loaded
                if (!$this->sourceLivestock) {
                    $this->loadSourceLivestock();
                }

                Log::info('✅ Existing mutation data loaded for edit', [
                    'mutation_id' => $mutation->id,
                    'date' => $this->mutationDate,
                    'quantity' => $this->quantity,
                    'items_count' => $mutation->items->count()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error loading existing mutation data', [
                'error' => $e->getMessage(),
                'mutation_ids' => $this->existingMutationIds
            ]);

            $this->errorMessage = 'Gagal memuat data mutasi untuk edit: ' . $e->getMessage();
        }
    }

    /**
     * Reset edit mode state
     */
    private function resetEditMode(): void
    {
        if ($this->isEditing) {
            $this->isEditing = false;
            $this->existingMutationIds = [];
            $this->editModeMessage = '';

            Log::info('🔄 Edit mode reset', [
                'source_livestock_id' => $this->sourceLivestockId
            ]);
        }
    }

    /**
     * Refresh data
     */
    public function refreshData(): void
    {
        $this->loadLivestockOptions();
        $this->loadCoopOptions();

        if ($this->sourceLivestockId) {
            $this->loadSourceLivestock();
        }
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.livestock.mutation.fifo-livestock-mutation', [
            'livestockOptions' => $this->allLivestock,
            'coopOptions' => $this->allCoops,
            'fifoConfig' => $this->fifoSettings,
            'fifoEnabled' => $this->fifoEnabled,
            'minAgeDays' => $this->minAgeDays,
            'maxAgeDays' => $this->maxAgeDays,
        ]);
    }

    /**
     * Load existing mutation for edit from restriction action
     */
    public function loadExistingMutationForEdit(): void
    {
        try {
            if ($this->sourceLivestockId && $this->mutationDate) {
                $this->checkForExistingMutations();

                // Clear restriction messages after switching to edit mode
                $this->clearRestrictionMessages();

                Log::info('🔄 Loading existing mutation for edit via restriction action', [
                    'source_livestock_id' => $this->sourceLivestockId,
                    'mutation_date' => $this->mutationDate
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in loadExistingMutationForEdit', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
