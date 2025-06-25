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

class FifoLivestockMutationConfigurable extends Component
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
    public $fifoItems;
    public $totalQuantity = 0;
    public $totalWeight = 0;

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

    // Tambahan property untuk input restriction
    public $inputRestrictions = [];
    public $restrictionMessage = '';
    public $restrictionTypes = [];
    public $restrictionDetails = [];
    public $restrictionAction = null;

    // New property for existing mutation items
    public $existingMutationItems = [];

    // Properties for adding new items to existing mutation
    public $newItemBatchId = null;
    public $newItemQuantity = 0;

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
        }

        $fifoConfig = CompanyConfig::getFifoMutationConfig();
        Log::info('DEBUG: [mount] CompanyConfig::getFifoMutationConfig()', $fifoConfig);
        $this->inputRestrictions = $fifoConfig['input_restrictions'] ?? [];
        if (empty($this->inputRestrictions)) {
            // Fallback default: semua restriction true
            $this->inputRestrictions = [
                'allow_same_day_repeated_input' => true,
                'allow_same_livestock_repeated_input' => true,
                'allow_same_livestock_same_day' => true,
            ];
            $this->restrictionMessage = '⚠️ Restriction config tidak ditemukan, menggunakan default (semua restriction diizinkan).';
            Log::warning('⚠️ Restriction config tidak ditemukan, menggunakan default (semua restriction diizinkan).');
        }

        Log::info('DEBUG: [mount] inputRestrictions', $this->inputRestrictions);

        Log::info('🔄 FIFO Livestock Mutation (Configurable) component mounted', [
            'user_id' => auth()->id(),
            'livestock_id' => $livestockId,
            'input_restrictions' => $this->inputRestrictions
        ]);

        // Auto-check for existing mutations on initial load
        $this->performInitialMutationCheck();
    }

    /**
     * Perform initial mutation check after component mount
     */
    private function performInitialMutationCheck(): void
    {
        try {
            // Only check if both date and livestock are set
            if ($this->mutationDate && $this->sourceLivestockId) {
                Log::info('🔍 Performing initial mutation check', [
                    'mutation_date' => $this->mutationDate,
                    'source_livestock_id' => $this->sourceLivestockId
                ]);

                // Load source livestock first if not already loaded
                if (!$this->sourceLivestock) {
                    $this->loadSourceLivestock();
                }

                // Check for existing mutations
                $this->checkForExistingMutations();
            } else {
                Log::info('🔍 Skipping initial mutation check - missing required data', [
                    'has_mutation_date' => !empty($this->mutationDate),
                    'has_source_livestock_id' => !empty($this->sourceLivestockId)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in performInitialMutationCheck', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Initialize component
     */
    private function initializeComponent(): void
    {
        $this->showModal = false;
        $this->isLoading = false;
        $this->isEditing = false;
        $this->fifoItems = collect();
        $this->totalQuantity = 0;
        $this->totalWeight = 0;

        // Set default date to today if not set
        if (empty($this->mutationDate)) {
            $this->mutationDate = now()->format('Y-m-d');
            Log::info('🔄 Default mutation date set to today', [
                'mutation_date' => $this->mutationDate
            ]);
        }

        // Reset edit mode data
        $this->existingMutationItems = collect();
        $this->newItemBatchId = null;
        $this->newItemQuantity = null;

        // Load configuration and options
        $this->loadConfiguration();
        $this->loadLivestockOptions();
        $this->loadCoopOptions();

        Log::info('🔄 Component initialized', [
            'mutation_date' => $this->mutationDate,
            'is_editing' => $this->isEditing,
            'show_modal' => $this->showModal,
            'total_livestock' => count($this->allLivestock),
            'total_coops' => count($this->allCoops)
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
     * Calculate available quantity for a specific batch
     */
    private function calculateBatchAvailableQuantity($batch): int
    {
        if (!$batch) {
            return 0;
        }

        return $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
    }

    /**
     * Calculate batch age in days
     */
    private function calculateBatchAge($batch): int
    {
        if (!$batch || !$batch->start_date) {
            return 0;
        }

        $startDate = $batch->start_date instanceof \Carbon\Carbon
            ? $batch->start_date
            : \Carbon\Carbon::parse($batch->start_date);

        return $startDate->diffInDays(now());
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
        $this->restrictionTypes = [];
        $this->restrictionDetails = [];
        $this->restrictionAction = null;
        $this->restrictionMessage = '';

        Log::info('DEBUG: [processFifoMutation] inputRestrictions', [
            'inputRestrictions' => $this->inputRestrictions,
            'mutationDate' => $this->mutationDate,
            'sourceLivestockId' => $this->sourceLivestockId,
        ]);

        // === RESTRICTION CHECKS (MUST BE FIRST) ===
        $restrictionViolated = false;
        // Cek restriction: allow_same_day_repeated_input
        if (isset($this->inputRestrictions['allow_same_day_repeated_input']) && !$this->inputRestrictions['allow_same_day_repeated_input']) {
            $exists = LivestockMutation::whereDate('tanggal', $this->mutationDate)
                ->where('source_livestock_id', $this->sourceLivestockId)
                ->first();
            Log::info('DEBUG: [restriction] allow_same_day_repeated_input', [
                'exists' => (bool)$exists,
                'mutationDate' => $this->mutationDate,
                'sourceLivestockId' => $this->sourceLivestockId
            ]);
            if ($exists && !($this->isEditing && in_array($exists->id, $this->existingMutationIds ?? []))) {
                $msg = 'Mutasi pada tanggal yang sama hanya diizinkan sekali.';
                $this->restrictionTypes[] = 'Same Day';
                $this->restrictionDetails[] = $msg;
                $this->restrictionDetails[] = 'Silakan <b>edit mutasi sebelumnya</b> jika ingin mengubah data.';
                $this->restrictionAction = [
                    'label' => 'Edit Mutasi',
                    'url' => null // UI-only, no redirect
                ];
                Log::info('Restriction triggered: Edit Mutasi only available via UI, no route redirect.', [
                    'restriction' => 'Same Day',
                    'mutation_id' => $exists->id
                ]);
                $this->restrictionMessage = $msg;
                $this->addError('mutationDate', $msg);
                Log::warning('🚫 Restriction: Mutasi pada tanggal sama dicegah', [
                    'mutationDate' => $this->mutationDate,
                    'sourceLivestockId' => $this->sourceLivestockId
                ]);
                $restrictionViolated = true;
            }
        }
        // Cek restriction: allow_same_livestock_repeated_input
        if (isset($this->inputRestrictions['allow_same_livestock_repeated_input']) && !$this->inputRestrictions['allow_same_livestock_repeated_input']) {
            $exists = LivestockMutation::where('source_livestock_id', $this->sourceLivestockId)
                ->first();
            Log::info('DEBUG: [restriction] allow_same_livestock_repeated_input', [
                'exists' => (bool)$exists,
                'sourceLivestockId' => $this->sourceLivestockId
            ]);
            if ($exists && !($this->isEditing && in_array($exists->id, $this->existingMutationIds ?? []))) {
                $msg = 'Livestock ini hanya dapat dimutasi sekali.';
                $this->restrictionTypes[] = 'Livestock Only';
                $this->restrictionDetails[] = $msg;
                $this->restrictionDetails[] = 'Silakan <b>edit mutasi sebelumnya</b> jika ingin mengubah data.';
                $this->restrictionAction = [
                    'label' => 'Edit Mutasi',
                    'url' => null // UI-only, no redirect
                ];
                Log::info('Restriction triggered: Edit Mutasi only available via UI, no route redirect.', [
                    'restriction' => 'Livestock Only',
                    'mutation_id' => $exists->id
                ]);
                $this->restrictionMessage = $msg;
                $this->addError('sourceLivestockId', $msg);
                Log::warning('🚫 Restriction: Mutasi livestock berulang dicegah', [
                    'sourceLivestockId' => $this->sourceLivestockId
                ]);
                $restrictionViolated = true;
            }
        }
        // Cek restriction: allow_same_livestock_same_day
        if (isset($this->inputRestrictions['allow_same_livestock_same_day']) && !$this->inputRestrictions['allow_same_livestock_same_day']) {
            $exists = LivestockMutation::where('source_livestock_id', $this->sourceLivestockId)
                ->whereDate('tanggal', $this->mutationDate)
                ->first();
            Log::info('DEBUG: [restriction] allow_same_livestock_same_day', [
                'exists' => (bool)$exists,
                'sourceLivestockId' => $this->sourceLivestockId,
                'mutationDate' => $this->mutationDate
            ]);
            if ($exists && !($this->isEditing && in_array($exists->id, $this->existingMutationIds ?? []))) {
                $msg = 'Livestock ini sudah dimutasi pada tanggal yang sama. Edit mutasi sebelumnya jika ingin mengubah.';
                $this->restrictionTypes[] = 'Livestock Same Day';
                $this->restrictionDetails[] = $msg;
                $this->restrictionDetails[] = 'Silakan <b>edit mutasi sebelumnya</b> jika ingin mengubah data.';
                $this->restrictionAction = [
                    'label' => 'Edit Mutasi',
                    'url' => null // UI-only, no redirect
                ];
                Log::info('Restriction triggered: Edit Mutasi only available via UI, no route redirect.', [
                    'restriction' => 'Livestock Same Day',
                    'mutation_id' => $exists->id
                ]);
                $this->restrictionMessage = $msg;
                $this->addError('sourceLivestockId', $msg);
                Log::warning('🚫 Restriction: Mutasi livestock sama di hari sama dicegah', [
                    'sourceLivestockId' => $this->sourceLivestockId,
                    'mutationDate' => $this->mutationDate
                ]);
                $restrictionViolated = true;
            }
        }
        // Jika ada restriction, batalkan proses mutasi
        if ($restrictionViolated) {
            Log::info('DEBUG: [processFifoMutation] Restriction violated, mutation aborted.');
            $this->showPreviewModal = false;
            $this->dispatch('fifo-mutation-restriction', [
                'message' => $this->restrictionMessage,
                'details' => $this->restrictionDetails,
                'type' => 'error'
            ]);
            return;
        }

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
                'existing_mutation_items' => $this->existingMutationItems,
                'is_editing' => $this->isEditing,
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
                $this->showPreviewModal = false;
                $this->dispatch('fifo-mutation-success', [
                    'message' => $successMessage,
                    'type' => 'success'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ FIFO mutation failed', [
                'error' => $e->getMessage(),
                'source_livestock_id' => $this->sourceLivestockId,
                'source_livestock_loaded' => !is_null($this->sourceLivestock),
                'quantity' => $this->quantity
            ]);
            $this->addError('mutation', $e->getMessage());
            $this->dispatch('fifo-mutation-error', [
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
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
        $this->existingMutationItems = [];
        $this->newItemBatchId = null;
        $this->newItemQuantity = 0;
        $this->isEditing = false;
        $this->editModeMessage = '';
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->processingMutation = false;
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
            // Load source livestock immediately when livestock ID is provided
            $this->loadSourceLivestock();
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

        // Auto-check for existing mutations after modal is opened and data is set
        if ($livestockId && !$editData) {
            $this->performDelayedMutationCheck();
        }
    }

    /**
     * Perform delayed mutation check after modal opening
     */
    private function performDelayedMutationCheck(): void
    {
        try {
            // Small delay to ensure all data is loaded
            if ($this->mutationDate && $this->sourceLivestockId && $this->sourceLivestock) {
                Log::info('🔍 Performing delayed mutation check after modal open', [
                    'mutation_date' => $this->mutationDate,
                    'source_livestock_id' => $this->sourceLivestockId,
                    'source_livestock_name' => $this->sourceLivestock->name ?? 'Unknown'
                ]);

                $this->checkForExistingMutations();
            } else {
                Log::info('🔍 Skipping delayed mutation check - data not ready', [
                    'has_mutation_date' => !empty($this->mutationDate),
                    'has_source_livestock_id' => !empty($this->sourceLivestockId),
                    'has_source_livestock' => !is_null($this->sourceLivestock)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error in performDelayedMutationCheck', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
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
        $this->editModeMessage = 'Mode Edit: Data mutasi akan diperbarui';

        // Load existing mutation data
        if (!empty($this->existingMutationIds)) {
            $this->loadExistingMutationData();
        }
    }

    /**
     * Load existing mutation data for edit mode
     */
    private function loadExistingMutationData(): void
    {
        try {
            $mutations = LivestockMutation::with(['items.batch', 'sourceLivestock', 'destinationLivestock', 'destinationCoop'])
                ->whereIn('id', $this->existingMutationIds)
                ->get();

            if ($mutations->count() > 0) {
                $firstMutation = $mutations->first();

                // Fix date format - ensure proper format
                $this->mutationDate = $firstMutation->tanggal instanceof \Carbon\Carbon
                    ? $firstMutation->tanggal->format('Y-m-d')
                    : \Carbon\Carbon::parse($firstMutation->tanggal)->format('Y-m-d');

                $this->sourceLivestockId = $firstMutation->source_livestock_id ?? $firstMutation->from_livestock_id;
                $this->type = $firstMutation->jenis;
                $this->direction = $firstMutation->direction;
                $this->reason = $firstMutation->keterangan;
                $this->destinationLivestockId = $firstMutation->destination_livestock_id ?? $firstMutation->to_livestock_id;
                $this->destinationCoopId = $firstMutation->destination_coop_id;

                // Calculate total quantity from all mutations
                $this->quantity = $mutations->sum(function ($mutation) {
                    return $mutation->items->sum('quantity');
                });

                // Load existing mutation items for editing
                $this->existingMutationItems = [];
                foreach ($mutations as $mutation) {
                    foreach ($mutation->items as $item) {
                        $this->existingMutationItems[] = [
                            'id' => $item->id,
                            'mutation_id' => $mutation->id,
                            'batch_id' => $item->batch_id,
                            'batch_name' => $item->batch->name ?? 'Unknown Batch',
                            'batch_start_date' => $item->batch->start_date ?? null,
                            'quantity' => $item->quantity,
                            'original_quantity' => $item->quantity, // Store original for comparison
                            'available_quantity' => $this->calculateBatchAvailableQuantity($item->batch),
                            'age_days' => $item->batch ? $this->calculateBatchAge($item->batch) : 0,
                            'weight' => $item->weight ?? 0,
                            'price' => $item->price ?? 0,
                        ];
                    }
                }

                // Update edit mode message with details
                $this->editModeMessage = sprintf(
                    'Mode Edit: %d mutasi FIFO akan diperbarui (Total: %d ekor)',
                    $mutations->count(),
                    $this->quantity
                );

                // Load source livestock
                $this->loadSourceLivestock();

                Log::info('✅ Existing mutation data loaded for edit', [
                    'mutation_count' => $mutations->count(),
                    'date' => $this->mutationDate,
                    'quantity' => $this->quantity,
                    'items_count' => count($this->existingMutationItems)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error loading existing mutation data', [
                'error' => $e->getMessage(),
                'mutation_ids' => $this->existingMutationIds,
                'trace' => $e->getTraceAsString()
            ]);

            $this->errorMessage = 'Gagal memuat data mutasi untuk edit: ' . $e->getMessage();
        }
    }

    /**
     * Reset edit mode
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

    /**
     * Update quantity for existing mutation item
     */
    public function updateExistingItemQuantity($itemIndex, $newQuantity): void
    {
        try {
            if (!isset($this->existingMutationItems[$itemIndex])) {
                Log::warning('⚠️ Invalid item index for quantity update', [
                    'index' => $itemIndex,
                    'total_items' => count($this->existingMutationItems)
                ]);
                return;
            }

            $item = &$this->existingMutationItems[$itemIndex];
            $oldQuantity = $item['quantity'];
            $maxQuantity = $item['available_quantity'] + $item['original_quantity']; // Available + what was originally taken

            // Validate new quantity
            if ($newQuantity < 0) {
                $this->addError("existingItem.{$itemIndex}.quantity", 'Kuantitas tidak boleh negatif');
                return;
            }

            if ($newQuantity > $maxQuantity) {
                $this->addError(
                    "existingItem.{$itemIndex}.quantity",
                    "Kuantitas melebihi ketersediaan. Maksimal: {$maxQuantity}"
                );
                return;
            }

            // Update quantity
            $item['quantity'] = $newQuantity;

            // Recalculate total quantity
            $this->quantity = array_sum(array_column($this->existingMutationItems, 'quantity'));

            Log::info('✅ Existing mutation item quantity updated', [
                'item_index' => $itemIndex,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'total_quantity' => $this->quantity
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error updating existing item quantity', [
                'error' => $e->getMessage(),
                'item_index' => $itemIndex,
                'new_quantity' => $newQuantity
            ]);
        }
    }

    /**
     * Remove existing mutation item
     */
    public function removeExistingItem($itemIndex): void
    {
        try {
            if (!isset($this->existingMutationItems[$itemIndex])) {
                Log::warning('⚠️ Invalid item index for removal', [
                    'index' => $itemIndex,
                    'total_items' => count($this->existingMutationItems)
                ]);
                return;
            }

            $removedItem = $this->existingMutationItems[$itemIndex];
            unset($this->existingMutationItems[$itemIndex]);

            // Reindex array
            $this->existingMutationItems = array_values($this->existingMutationItems);

            // Recalculate total quantity
            $this->quantity = array_sum(array_column($this->existingMutationItems, 'quantity'));

            Log::info('✅ Existing mutation item removed', [
                'removed_item' => $removedItem,
                'remaining_items' => count($this->existingMutationItems),
                'total_quantity' => $this->quantity
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error removing existing item', [
                'error' => $e->getMessage(),
                'item_index' => $itemIndex
            ]);
        }
    }

    /**
     * Add new item to existing mutation
     */
    public function addNewItemToExisting($batchId, $quantity): void
    {
        try {
            // Validate batch exists and has available quantity
            $batch = $this->availableBatches->firstWhere('id', $batchId);
            if (!$batch) {
                $this->addError('newItem.batch', 'Batch tidak ditemukan');
                return;
            }

            $availableQuantity = $this->calculateBatchAvailableQuantity($batch);
            if ($quantity > $availableQuantity) {
                $this->addError(
                    'newItem.quantity',
                    "Kuantitas melebihi ketersediaan. Tersedia: {$availableQuantity}"
                );
                return;
            }

            // Add new item
            $this->existingMutationItems[] = [
                'id' => null, // New item, no ID yet
                'mutation_id' => null,
                'batch_id' => $batchId,
                'batch_name' => $batch->name,
                'batch_start_date' => $batch->start_date,
                'quantity' => $quantity,
                'original_quantity' => 0, // New item
                'available_quantity' => $availableQuantity,
                'age_days' => $this->calculateBatchAge($batch),
                'weight' => 0,
                'price' => 0,
            ];

            // Recalculate total quantity
            $this->quantity = array_sum(array_column($this->existingMutationItems, 'quantity'));

            Log::info('✅ New item added to existing mutation', [
                'batch_id' => $batchId,
                'quantity' => $quantity,
                'total_items' => count($this->existingMutationItems),
                'total_quantity' => $this->quantity
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error adding new item to existing mutation', [
                'error' => $e->getMessage(),
                'batch_id' => $batchId,
                'quantity' => $quantity
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
     * Check if component is ready for production
     */
    public function isProductionReady(): bool
    {
        return !empty($this->allLivestock) &&
            !empty($this->allCoops) &&
            !empty($this->config);
    }

    /**
     * Get component status for debugging
     */
    public function getComponentStatus(): array
    {
        return [
            'livestock_count' => count($this->allLivestock),
            'coop_count' => count($this->allCoops),
            'config_loaded' => !empty($this->config),
            'source_livestock_loaded' => !is_null($this->sourceLivestock),
            'is_editing' => $this->isEditing,
            'existing_items_count' => count($this->existingMutationItems),
            'production_ready' => $this->isProductionReady()
        ];
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
}
