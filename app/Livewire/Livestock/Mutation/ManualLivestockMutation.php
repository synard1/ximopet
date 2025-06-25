<?php

namespace App\Livewire\Livestock\Mutation;

use Livewire\Component;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockMutation;
use App\Models\Coop;
use App\Services\Livestock\LivestockMutationService;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;
use Carbon\Carbon;

/**
 * Manual Livestock Mutation Component
 * 
 * Comprehensive Livewire component for handling manual livestock mutations
 * with batch selection, edit mode, validation, and real-time feedback.
 * 
 * Features:
 * - Manual batch selection with real-time availability
 * - Edit mode with automatic data loading
 * - Configuration-based update strategies
 * - Comprehensive validation and error handling
 * - Real-time quantity calculations
 * - Audit trail and logging
 * - Future-proof extensible design
 */
class ManualLivestockMutation extends Component
{
    // Basic mutation properties
    public $mutationDate;
    public $sourceLivestockId;
    public $destinationLivestockId;
    public $destinationCoopId;
    public $mutationType = 'internal';
    public $mutationDirection = 'out';
    public $reason;
    public $notes;

    // Manual batch selection
    public $manualBatches = [];
    public $availableBatches = [];
    public $selectedBatchId;
    public $selectedBatchQuantity;
    public $selectedBatchNote;

    // Edit mode properties
    public $isEditing = false;
    public $existingMutationIds = [];
    public $editModeMessage = '';

    // UI state
    public $showModal = false;
    public $isLoading = false;
    public $showPreview = false;
    public $previewData = [];
    public $errorMessage = '';
    public $successMessage = '';

    // Configuration
    public $config = [];
    public $validationRules = [];
    public $workflowSettings = [];
    public $batchSettings = [];
    public $editModeSettings = [];

    // Livestock data
    public $sourceLivestock;
    public $destinationLivestock;
    public $allLivestock = [];
    public $allCoops = [];
    public $destinationCoop;

    protected $listeners = [
        'show-manual-mutation' => 'openModal',
        'openMutationModal' => 'openModal',
        'closeMutationModal' => 'closeModal',
        'refreshMutationData' => 'refreshData'
    ];

    /**
     * Component initialization
     */
    public function mount($livestockId = null)
    {
        $this->initializeComponent();

        // Auto-set source livestock if provided via mount
        if ($livestockId) {
            $this->sourceLivestockId = $livestockId;
            $this->loadSourceLivestock();
            $this->checkForExistingMutations();

            Log::info('🔄 Source livestock auto-set via mount', [
                'livestock_id' => $livestockId,
                'livestock_name' => $this->sourceLivestock->name ?? 'Not loaded'
            ]);
        }
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
        $company =

            Log::info('🔄 Manual Livestock Mutation component initialized', [
                'total_livestock' => count($this->allLivestock),
                'total_coops' => count($this->allCoops),
                'config_loaded' => !empty($this->config),
                'current_user_company' => auth()->user()->company_id ?? 'Not logged in'
            ]);
    }

    /**
     * Load configuration from CompanyConfig
     */
    private function loadConfiguration(): void
    {
        $this->config = CompanyConfig::getManualMutationConfig();
        $this->validationRules = CompanyConfig::getManualMutationValidationRules();
        $this->workflowSettings = CompanyConfig::getManualMutationWorkflowSettings();
        $this->batchSettings = CompanyConfig::getManualMutationBatchSettings();
        $this->editModeSettings = CompanyConfig::getManualMutationEditModeSettings();

        Log::info('📋 Configuration loaded for ManualLivestockMutation', [
            'config' => $this->config,
            'edit_mode_enabled' => $this->editModeSettings['enabled'] ?? false
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

        $this->allCoops = Coop::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get()
            ->map(function ($coop) {
                return [
                    'id' => $coop->id,
                    'name' => $coop->name,
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
                    'capacity' => $coop->capacity ?? 0,
                    'display_name' => sprintf(
                        '%s (%s) - %d ekor / %d kapasitas',
                        $coop->name,
                        $coop->farm->name ?? 'Unknown Farm',
                        $totalQuantity,
                        $coop->capacity ?? 0
                    )
                ];
            })
            ->toArray();
    }

    /**
     * Open modal for new mutation or edit existing
     */
    public function openModal($livestockId = null, $editData = null): void
    {
        // Reset component but preserve livestock_id if provided
        $preservedLivestockId = $livestockId;
        $this->resetComponent();

        // Set source livestock if provided
        if ($preservedLivestockId) {
            $this->sourceLivestockId = $preservedLivestockId;
            $this->loadSourceLivestock();
            $this->checkForExistingMutations();
        }

        if ($editData) {
            $this->loadEditMode($editData);
        }

        $this->showModal = true;
        $this->dispatch('show-livestock-mutation');

        Log::info('🔄 Mutation modal opened', [
            'source_livestock_id' => $this->sourceLivestockId,
            'source_livestock_name' => $this->sourceLivestock->name ?? 'Not loaded',
            'edit_mode' => $this->isEditing,
            'edit_data' => $editData,
            'provided_livestock_id' => $preservedLivestockId
        ]);
    }

    /**
     * Close modal and reset state
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetComponent();
        $this->dispatch('close-livestock-mutation');

        Log::info('🔄 Mutation modal closed');
    }

    /**
     * Close modal silently without events
     */
    public function closeModalSilent(): void
    {
        $this->showModal = false;
        $this->resetComponent();
    }

    /**
     * Reset component to initial state
     */
    private function resetComponent(): void
    {
        // Reset basic properties
        $this->mutationDate = now()->format('Y-m-d');
        $this->sourceLivestockId = null;
        $this->destinationLivestockId = null;
        $this->destinationCoopId = null;
        $this->mutationType = 'internal';
        $this->mutationDirection = 'out';
        $this->reason = '';
        $this->notes = '';

        // Reset batch selection
        $this->manualBatches = [];
        $this->availableBatches = [];
        $this->selectedBatchId = null;
        $this->selectedBatchQuantity = null;
        $this->selectedBatchNote = '';

        // Reset edit mode
        $this->resetEditMode();

        // Reset UI state
        $this->isLoading = false;
        $this->showPreview = false;
        $this->previewData = [];
        $this->errorMessage = '';
        $this->successMessage = '';

        // Reset livestock data
        $this->sourceLivestock = null;
        $this->destinationLivestock = null;
        $this->destinationCoop = null;
    }

    /**
     * Reset edit mode state
     */
    private function resetEditMode(): void
    {
        $this->isEditing = false;
        $this->existingMutationIds = [];
        $this->editModeMessage = '';
    }

    /**
     * Load edit mode with existing mutation data
     */
    private function loadEditMode(array $editData): void
    {
        if (!($this->editModeSettings['enabled'] ?? false)) {
            Log::warning('Edit mode attempted but not enabled in configuration');
            return;
        }

        try {
            $this->isEditing = true;
            $this->existingMutationIds = $editData['mutation_ids'] ?? [];

            // Load first mutation for form data
            if (!empty($this->existingMutationIds)) {
                $firstMutation = LivestockMutation::find($this->existingMutationIds[0]);

                if ($firstMutation) {
                    $this->mutationDate = $firstMutation->tanggal->format('Y-m-d');
                    $this->sourceLivestockId = $firstMutation->source_livestock_id;
                    $this->destinationLivestockId = $firstMutation->destination_livestock_id;
                    $this->destinationCoopId = $firstMutation->destination_coop_id ?? null;
                    $this->mutationType = $firstMutation->jenis;
                    $this->mutationDirection = $firstMutation->direction;
                    $this->reason = $firstMutation->data['reason'] ?? '';
                    $this->notes = $firstMutation->data['notes'] ?? '';

                    // Perbaikan: jika destinationCoopId/destinationLivestockId kosong, ambil dari data JSON
                    $destinationInfo = $firstMutation->data['destination_info'] ?? [];
                    if (empty($this->destinationCoopId) && isset($destinationInfo['coop']['id'])) {
                        $this->destinationCoopId = $destinationInfo['coop']['id'];
                    }
                    if (empty($this->destinationLivestockId) && isset($destinationInfo['livestock']['id'])) {
                        $this->destinationLivestockId = $destinationInfo['livestock']['id'];
                    }

                    $this->loadSourceLivestock();
                    $this->loadExistingMutationData();
                }
            }

            $this->editModeMessage = sprintf(
                'Mode Edit: Mengedit %d record mutasi pada tanggal %s',
                count($this->existingMutationIds),
                $this->mutationDate
            );

            Log::info('✅ Edit mode loaded successfully', [
                'mutation_ids' => $this->existingMutationIds,
                'mutation_date' => $this->mutationDate,
                'source_livestock_id' => $this->sourceLivestockId,
                'destinationCoopId' => $this->destinationCoopId,
                'destinationLivestockId' => $this->destinationLivestockId,
                'manualBatches_after_load' => $this->manualBatches
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error loading edit mode', [
                'error' => $e->getMessage(),
                'edit_data' => $editData
            ]);

            $this->errorMessage = 'Gagal memuat data edit: ' . $e->getMessage();
            $this->resetEditMode();
        }
    }

    /**
     * Load existing mutation data for edit mode
     */
    private function loadExistingMutationData(): void
    {
        try {
            // Ambil semua item dari livestock_mutation_items yang berelasi dengan mutation_ids
            $items = \App\Models\LivestockMutationItem::whereIn('livestock_mutation_id', $this->existingMutationIds)->get();

            $this->manualBatches = [];
            $batchQuantities = [];

            foreach ($items as $item) {
                $batchId = $item->batch_id;
                $quantity = $item->quantity;
                $note = $item->keterangan ?? null;
                // Perbaikan: cek tipe data payload
                if (is_array($item->payload)) {
                    $payload = $item->payload;
                } elseif (is_string($item->payload)) {
                    $payload = json_decode($item->payload, true) ?: [];
                } else {
                    $payload = [];
                }

                $batch = \App\Models\LivestockBatch::find($batchId);
                if ($batch) {
                    if (!isset($batchQuantities[$batchId])) {
                        $batchQuantities[$batchId] = [
                            'batch_id' => $batchId,
                            'batch_name' => $batch->name ?? 'Unknown Batch',
                            'quantity' => 0,
                            'note' => $note,
                            'available_quantity' => $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated,
                            'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null
                        ];
                    }
                    $batchQuantities[$batchId]['quantity'] += $quantity;
                } else {
                    // Batch tidak ditemukan
                    if (!isset($batchQuantities[$batchId])) {
                        $batchQuantities[$batchId] = [
                            'batch_id' => $batchId,
                            'batch_name' => 'Batch Tidak Ditemukan',
                            'quantity' => 0,
                            'note' => $note,
                            'available_quantity' => 0,
                            'age_days' => null
                        ];
                    }
                    $batchQuantities[$batchId]['quantity'] += $quantity;
                }
            }

            foreach ($batchQuantities as $batchData) {
                $this->manualBatches[] = $batchData;
            }

            Log::info('✅ Existing mutation data loaded (from items)', [
                'items_count' => $items->count(),
                'batches_loaded' => count($this->manualBatches),
                'total_quantity' => collect($this->manualBatches)->sum('quantity'),
                'manualBatches' => $this->manualBatches
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error loading existing mutation data (from items)', [
                'error' => $e->getMessage(),
                'mutation_ids' => $this->existingMutationIds
            ]);
            $this->errorMessage = 'Gagal memuat data mutasi existing: ' . $e->getMessage();
        }
    }

    /**
     * Cancel edit mode and return to create mode
     */
    public function cancelEditMode(): void
    {
        Log::info('🔄 Cancelling edit mode', [
            'existing_mutation_ids' => $this->existingMutationIds,
            'source_livestock_id' => $this->sourceLivestockId
        ]);

        $this->resetEditMode();
        $this->manualBatches = [];
        $this->successMessage = 'Mode edit dibatalkan. Kembali ke mode input baru.';

        $this->dispatch('edit-mode-cancelled', [
            'message' => 'Edit mode cancelled, returned to create mode'
        ]);
    }

    /**
     * Handle destination livestock change
     */
    public function updatedDestinationLivestockId(): void
    {
        if ($this->destinationLivestockId) {
            try {
                $this->destinationLivestock = Livestock::with(['farm', 'coop'])
                    ->findOrFail($this->destinationLivestockId);

                Log::info('✅ Destination livestock loaded', [
                    'livestock_id' => $this->destinationLivestockId,
                    'livestock_name' => $this->destinationLivestock->name
                ]);
            } catch (Exception $e) {
                Log::error('❌ Error loading destination livestock', [
                    'livestock_id' => $this->destinationLivestockId,
                    'error' => $e->getMessage()
                ]);

                $this->errorMessage = 'Gagal memuat data ternak tujuan: ' . $e->getMessage();
            }
        } else {
            $this->destinationLivestock = null;
        }
    }

    /**
     * Handle destination coop change
     */
    public function updatedDestinationCoopId(): void
    {
        if ($this->destinationCoopId) {
            try {
                $this->destinationCoop = Coop::with(['farm', 'livestocks'])
                    ->findOrFail($this->destinationCoopId);

                Log::info('✅ Destination coop loaded', [
                    'coop_id' => $this->destinationCoopId,
                    'coop_name' => $this->destinationCoop->name,
                    'farm_name' => $this->destinationCoop->farm->name ?? 'Unknown',
                    'current_livestock_count' => $this->destinationCoop->livestocks->count()
                ]);
            } catch (Exception $e) {
                Log::error('❌ Error loading destination coop', [
                    'coop_id' => $this->destinationCoopId,
                    'error' => $e->getMessage()
                ]);

                $this->errorMessage = 'Gagal memuat data kandang tujuan: ' . $e->getMessage();
            }
        } else {
            $this->destinationCoop = null;
        }
    }

    /**
     * Add batch to manual selection
     */
    public function addBatch(): void
    {
        $this->validate([
            'selectedBatchId' => 'required|exists:livestock_batches,id',
            'selectedBatchQuantity' => 'required|integer|min:1'
        ]);

        try {
            // Find the selected batch
            $selectedBatch = collect($this->availableBatches)
                ->firstWhere('batch_id', $this->selectedBatchId);

            if (!$selectedBatch) {
                throw new Exception('Batch tidak ditemukan');
            }

            // Check if batch already added
            $existingBatchIndex = collect($this->manualBatches)
                ->search(fn($batch) => $batch['batch_id'] === $this->selectedBatchId);

            if ($existingBatchIndex !== false) {
                throw new Exception('Batch sudah ditambahkan sebelumnya');
            }

            // Check available quantity
            if ($this->selectedBatchQuantity > $selectedBatch['available_quantity']) {
                throw new Exception(sprintf(
                    'Jumlah melebihi ketersediaan. Tersedia: %d, Diminta: %d',
                    $selectedBatch['available_quantity'],
                    $this->selectedBatchQuantity
                ));
            }

            // Add to manual batches
            $this->manualBatches[] = [
                'batch_id' => $this->selectedBatchId,
                'batch_name' => $selectedBatch['batch_name'],
                'quantity' => $this->selectedBatchQuantity,
                'note' => $this->selectedBatchNote ?: null,
                'available_quantity' => $selectedBatch['available_quantity'],
                'age_days' => $selectedBatch['age_days']
            ];

            // Reset selection after adding
            $this->selectedBatchId = null;
            $this->selectedBatchQuantity = null;
            $this->selectedBatchNote = '';

            // Feedback jika semua batch sudah dipilih
            if (count($this->manualBatches) === count($this->availableBatches)) {
                $this->successMessage = 'Semua batch sudah dipilih.';
            }

            $this->successMessage = sprintf(
                'Batch %s berhasil ditambahkan',
                $selectedBatch['batch_name']
            );

            Log::info('✅ Batch added to manual selection', [
                'batch_id' => $selectedBatch['batch_id'],
                'batch_name' => $selectedBatch['batch_name'],
                'quantity' => $this->selectedBatchQuantity,
                'manual_batches_count' => count($this->manualBatches)
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error adding batch', [
                'error' => $e->getMessage(),
                'selected_batch_id' => $this->selectedBatchId
            ]);

            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Remove batch from manual selection
     */
    public function removeBatch($index): void
    {
        if (isset($this->manualBatches[$index])) {
            $removedBatch = $this->manualBatches[$index];
            unset($this->manualBatches[$index]);
            $this->manualBatches = array_values($this->manualBatches);

            $this->successMessage = sprintf(
                'Batch %s berhasil dihapus dari seleksi',
                $removedBatch['batch_name']
            );

            Log::info('✅ Batch removed from manual selection', [
                'batch_id' => $removedBatch['batch_id'],
                'batch_name' => $removedBatch['batch_name'],
                'remaining_batches' => count($this->manualBatches)
            ]);
        }
    }

    /**
     * Show mutation preview
     */
    public function showPreview(): void
    {
        $this->errorMessage = '';
        $this->successMessage = '';
        try {
            $this->validateMutationData();
            $mutationService = app(LivestockMutationService::class);
            $mutationData = [
                'source_livestock_id' => $this->sourceLivestockId,
                'destination_livestock_id' => $this->destinationLivestockId,
                'destination_coop_id' => $this->destinationCoopId,
                'date' => $this->mutationDate,
                'type' => $this->mutationType,
                'direction' => $this->mutationDirection,
                'reason' => $this->reason,
                'notes' => $this->notes,
                'manual_batches' => $this->manualBatches,
                'mutation_method' => 'manual'
            ];
            $this->previewData = $mutationService->previewManualBatchMutation($mutationData);
            $this->showPreview = true;
            Log::info('✅ Mutation preview generated', [
                'can_fulfill' => $this->previewData['can_fulfill'],
                'total_quantity' => $this->previewData['total_quantity'],
                'batches_count' => $this->previewData['batches_count']
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error generating preview', [
                'error' => $e->getMessage()
            ]);
            $this->errorMessage = 'Gagal membuat preview: ' . $e->getMessage();
            $this->showPreview = false;
        }
    }

    /**
     * Hide mutation preview
     */
    public function hidePreview(): void
    {
        $this->showPreview = false;
        $this->previewData = [];
    }

    /**
     * Process the mutation
     */
    public function processMutation(): void
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        try {
            $this->validateMutationData();
            $mutationService = app(LivestockMutationService::class);
            $mutationData = [
                'source_livestock_id' => $this->sourceLivestockId,
                'destination_livestock_id' => $this->destinationLivestockId,
                'destination_coop_id' => $this->destinationCoopId,
                'date' => $this->mutationDate,
                'type' => $this->mutationType,
                'direction' => $this->mutationDirection,
                'reason' => $this->reason,
                'notes' => $this->notes,
                'manual_batches' => $this->manualBatches,
                'mutation_method' => 'manual',
                'is_editing' => $this->isEditing,
                'existing_mutation_ids' => $this->existingMutationIds
            ];
            $result = $mutationService->processMutation($mutationData);
            if ($result['success']) {
                $this->successMessage = $result['message'] ?? 'Mutasi berhasil diproses';
                $this->errorMessage = '';
                Log::info('🎉 Mutation processed successfully', [
                    'total_mutated' => $result['total_mutated'],
                    'processed_batches' => count($result['processed_batches']),
                    'edit_mode' => $result['edit_mode'] ?? false,
                    'update_strategy' => $result['update_strategy'] ?? 'CREATE_NEW'
                ]);
                if ($this->workflowSettings['auto_close_modal'] ?? true) {
                    $this->dispatch('mutation-completed', [
                        'success' => true,
                        'message' => $this->successMessage,
                        'result' => $result
                    ]);
                    $this->closeModalSilent();
                }
                $this->dispatch('refreshMutationData');
            } else {
                $this->successMessage = '';
                throw new Exception($result['message'] ?? 'Mutasi gagal diproses');
            }
        } catch (Exception $e) {
            Log::error('❌ Error processing mutation', [
                'error' => $e->getMessage(),
                'source_livestock_id' => $this->sourceLivestockId,
                'manual_batches' => $this->manualBatches
            ]);
            $this->errorMessage = $e->getMessage();
            $this->successMessage = '';
            $this->dispatch('mutation-completed', [
                'success' => false,
                'message' => $this->errorMessage
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Validate mutation data before processing
     */
    private function validateMutationData(): void
    {
        // Basic validation
        $this->validate([
            'mutationDate' => 'required|date',
            'sourceLivestockId' => 'required|exists:livestocks,id',
            'mutationType' => 'required|string',
            'mutationDirection' => 'required|in:in,out'
        ]);

        // Validate destination for outgoing mutations
        if ($this->mutationDirection === 'out' && ($this->validationRules['require_destination'] ?? true)) {
            // Require either destination coop or destination livestock
            if (!$this->destinationCoopId && !$this->destinationLivestockId) {
                throw new Exception('Kandang tujuan atau ternak tujuan harus dipilih untuk mutasi keluar');
            }

            if ($this->destinationCoopId) {
                $this->validate([
                    'destinationCoopId' => 'required|exists:coops,id'
                ]);
            }

            if ($this->destinationLivestockId) {
                $this->validate([
                    'destinationLivestockId' => 'exists:livestocks,id|different:sourceLivestockId'
                ]);
            }
        }

        // Validate manual batches
        if (empty($this->manualBatches)) {
            throw new Exception('Minimal satu batch harus dipilih untuk mutasi manual');
        }

        // Validate batch quantities
        foreach ($this->manualBatches as $batch) {
            if (!isset($batch['batch_id']) || !isset($batch['quantity'])) {
                throw new Exception('Data batch tidak lengkap');
            }

            if ($batch['quantity'] <= 0) {
                throw new Exception('Jumlah batch harus lebih besar dari 0');
            }

            $minQuantity = $this->validationRules['min_quantity'] ?? 1;
            if ($batch['quantity'] < $minQuantity) {
                throw new Exception("Jumlah minimal per batch: {$minQuantity}");
            }
        }

        // Validate total quantity
        $totalQuantity = collect($this->manualBatches)->sum('quantity');
        $minTotalQuantity = $this->validationRules['min_quantity'] ?? 1;

        if ($totalQuantity < $minTotalQuantity) {
            throw new Exception("Total jumlah minimal: {$minTotalQuantity}");
        }
    }

    /**
     * Get total quantity from manual batches
     */
    public function getTotalQuantityProperty(): int
    {
        return collect($this->manualBatches)->sum('quantity');
    }

    /**
     * Get total batches count
     */
    public function getTotalBatchesProperty(): int
    {
        return count($this->manualBatches);
    }

    /**
     * Check if form is valid for processing
     */
    public function getCanProcessProperty(): bool
    {
        $hasBasicRequirements = !empty($this->sourceLivestockId) &&
            !empty($this->mutationDate) &&
            !empty($this->manualBatches);

        // For outgoing mutations, check destination requirements
        if ($this->mutationDirection === 'out') {
            $hasDestination = !empty($this->destinationCoopId) || !empty($this->destinationLivestockId);
            return $hasBasicRequirements && $hasDestination;
        }

        // For incoming mutations, basic requirements are sufficient
        return $hasBasicRequirements;
    }

    /**
     * Check if add batch button should be enabled
     */
    public function getCanAddBatchProperty(): bool
    {
        $conditions = [
            'selectedBatchId_not_empty' => !empty($this->selectedBatchId),
            'selectedBatchQuantity_not_null' => $this->selectedBatchQuantity !== null,
            'selectedBatchQuantity_not_empty' => $this->selectedBatchQuantity !== '',
            'selectedBatchQuantity_is_numeric' => is_numeric($this->selectedBatchQuantity),
            'selectedBatchQuantity_greater_than_zero' => $this->selectedBatchQuantity > 0,
            'manualBatches_less_than_available' => count($this->manualBatches) < count($this->availableBatches)
        ];

        $canAdd = $conditions['selectedBatchId_not_empty'] &&
            $conditions['selectedBatchQuantity_not_null'] &&
            $conditions['selectedBatchQuantity_not_empty'] &&
            $conditions['selectedBatchQuantity_is_numeric'] &&
            $conditions['selectedBatchQuantity_greater_than_zero'] &&
            $conditions['manualBatches_less_than_available'];

        // Only log in debug mode to reduce verbosity
        if (config('app.debug')) {
            Log::info('🔍 getCanAddBatchProperty debug', [
                'selectedBatchId' => $this->selectedBatchId,
                'selectedBatchQuantity' => $this->selectedBatchQuantity,
                'selectedBatchQuantity_type' => gettype($this->selectedBatchQuantity),
                'manualBatches_count' => count($this->manualBatches),
                'availableBatches_count' => count($this->availableBatches),
                'conditions' => $conditions,
                'canAdd' => $canAdd
            ]);
        }

        return $canAdd;
    }

    /**
     * Refresh component data
     */
    public function refreshData(): void
    {
        $this->loadConfiguration();
        $this->loadLivestockOptions();
        $this->loadCoopOptions();

        if ($this->sourceLivestockId) {
            $this->loadAvailableBatches();
        }

        Log::info('🔄 Component data refreshed');
    }

    /**
     * Set source livestock directly (for external calls)
     */
    public function setSourceLivestock($livestockId): void
    {
        if ($livestockId) {
            $this->sourceLivestockId = $livestockId;
            $this->loadSourceLivestock();
            $this->checkForExistingMutations();

            Log::info('🔄 Source livestock set directly', [
                'livestock_id' => $livestockId,
                'livestock_name' => $this->sourceLivestock->name ?? 'Not loaded',
                'method' => 'setSourceLivestock'
            ]);
        }
    }

    /**
     * Debug method to check component state
     */
    public function debugComponentState(): array
    {
        return [
            'sourceLivestockId' => $this->sourceLivestockId,
            'destinationCoopId' => $this->destinationCoopId,
            'destinationLivestockId' => $this->destinationLivestockId,
            'mutationDirection' => $this->mutationDirection,
            'selectedBatchId' => $this->selectedBatchId,
            'selectedBatchQuantity' => $this->selectedBatchQuantity,
            'sourceLivestock' => $this->sourceLivestock ? [
                'id' => $this->sourceLivestock->id,
                'name' => $this->sourceLivestock->name,
                'farm' => $this->sourceLivestock->farm->name ?? 'Unknown',
                'coop' => $this->sourceLivestock->coop->name ?? 'Unknown'
            ] : null,
            'allLivestock_count' => count($this->allLivestock),
            'allCoops_count' => count($this->allCoops),
            'availableBatches_count' => count($this->availableBatches),
            'manualBatches_count' => count($this->manualBatches),
            'canAddBatch' => $this->getCanAddBatchProperty(),
            'canProcess' => $this->getCanProcessProperty(),
            'showModal' => $this->showModal,
            'isLoading' => $this->isLoading,
            'errorMessage' => $this->errorMessage
        ];
    }

    /**
     * Render the component
     */
    public function render()
    {
        Log::info('🔍 Render ManualLivestockMutation', [
            'isEditing' => $this->isEditing,
            'manualBatches' => $this->manualBatches,
            'manualBatches_count' => count($this->manualBatches)
        ]);
        return view('livewire.livestock.mutation.manual-livestock-mutation');
    }

    /**
     * Handler for when selectedBatchQuantity is updated
     */
    public function updatedSelectedBatchQuantity($value)
    {
        // Only log in debug mode
        if (config('app.debug')) {
            Log::info('🔄 selectedBatchQuantity updated', [
                'old_value' => $this->selectedBatchQuantity,
                'new_value' => $value,
                'value_type' => gettype($value),
                'canAddBatch_before' => $this->getCanAddBatchProperty()
            ]);
        }

        // Force re-evaluation of canAddBatch property
        $this->dispatch('batch-quantity-updated', [
            'quantity' => $value,
            'canAdd' => $this->getCanAddBatchProperty()
        ]);
    }

    /**
     * Handler for when selectedBatchId is updated
     */
    public function updatedSelectedBatchId($value)
    {
        // Only log in debug mode
        if (config('app.debug')) {
            Log::info('🔄 selectedBatchId updated', [
                'old_value' => $this->selectedBatchId,
                'new_value' => $value,
                'canAddBatch_after' => $this->getCanAddBatchProperty()
            ]);
        }

        // Reset quantity when batch changes
        $this->selectedBatchQuantity = null;
    }

    /**
     * Universal Livewire updated handler
     */
    public function updated($property, $value)
    {
        if (in_array($property, [
            'mutationDate',
            'sourceLivestockId',
            'mutationType',
            'mutationDirection'
        ])) {
            $this->resetBatchSelection();

            if ($this->sourceLivestockId) {
                $this->loadAvailableBatches();
            }

            if ($this->sourceLivestockId && $this->mutationDate) {
                $this->checkForExistingMutations();
            }
        }

        // Log property changes for debugging (only in debug mode)
        if (config('app.debug')) {
            Log::info('🔄 Property updated', [
                'property' => $property,
                'value' => $value,
                'value_type' => gettype($value),
                'canAddBatch' => $this->getCanAddBatchProperty(),
                'selectedBatchId' => $this->selectedBatchId,
                'selectedBatchQuantity' => $this->selectedBatchQuantity,
                'selectedBatchQuantity_type' => gettype($this->selectedBatchQuantity)
            ]);
        }
    }

    /**
     * Helper to reset batch selection
     */
    private function resetBatchSelection(): void
    {
        $this->manualBatches = [];
        $this->availableBatches = [];
        $this->selectedBatchId = null;
        $this->selectedBatchQuantity = null;
        $this->selectedBatchNote = '';
    }

    /**
     * Load source livestock data and available batches
     */
    private function loadSourceLivestock(): void
    {
        try {
            $this->sourceLivestock = Livestock::with(['farm', 'coop', 'batches', 'currentLivestock'])
                ->findOrFail($this->sourceLivestockId);

            // Ensure livestock is in the allLivestock array for dropdown
            $this->ensureLivestockInOptions($this->sourceLivestock);

            $this->loadAvailableBatches();

            Log::info('✅ Source livestock loaded', [
                'livestock_id' => $this->sourceLivestockId,
                'livestock_name' => $this->sourceLivestock->name,
                'farm_name' => $this->sourceLivestock->farm->name ?? 'Unknown',
                'coop_name' => $this->sourceLivestock->coop->name ?? 'Unknown',
                'current_quantity' => $this->sourceLivestock->currentLivestock->quantity ?? 0,
                'available_batches' => count($this->availableBatches)
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error loading source livestock', [
                'livestock_id' => $this->sourceLivestockId,
                'error' => $e->getMessage()
            ]);

            $this->errorMessage = 'Gagal memuat data ternak sumber: ' . $e->getMessage();
            $this->sourceLivestock = null;
        }
    }

    /**
     * Ensure livestock is available in dropdown options
     */
    private function ensureLivestockInOptions($livestock): void
    {
        // Remove existing entry with same id
        $this->allLivestock = collect($this->allLivestock)
            ->reject(fn($item) => $item['id'] === $livestock->id)
            ->values()
            ->toArray();

        $this->allLivestock[] = [
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
    }

    /**
     * Load available batches for manual selection
     */
    private function loadAvailableBatches(): void
    {
        if (!$this->sourceLivestockId) {
            $this->availableBatches = [];
            return;
        }

        try {
            $mutationService = app(LivestockMutationService::class);
            $batchData = $mutationService->getAvailableBatchesForMutation($this->sourceLivestockId);

            $this->availableBatches = $batchData['batches'] ?? [];

            Log::info('✅ Available batches loaded', [
                'livestock_id' => $this->sourceLivestockId,
                'total_batches' => count($this->availableBatches),
                'available_batches' => collect($this->availableBatches)->sum('available_quantity')
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error loading available batches', [
                'livestock_id' => $this->sourceLivestockId,
                'error' => $e->getMessage()
            ]);

            $this->availableBatches = [];
            $this->errorMessage = 'Gagal memuat data batch: ' . $e->getMessage();
        }
    }

    /**
     * Check for existing mutations on the selected date
     */
    private function checkForExistingMutations(): void
    {
        if (
            !($this->editModeSettings['enabled'] ?? false) ||
            !$this->sourceLivestockId ||
            !$this->mutationDate
        ) {
            return;
        }

        try {
            // Check if the table exists and has the required columns
            if (!Schema::hasTable('livestock_mutations')) {
                Log::warning('⚠️ livestock_mutations table does not exist, skipping existing mutation check');
                return;
            }

            if (!Schema::hasColumn('livestock_mutations', 'source_livestock_id')) {
                Log::warning('⚠️ source_livestock_id column does not exist in livestock_mutations table, skipping existing mutation check');
                return;
            }

            $existingMutations = LivestockMutation::where('source_livestock_id', $this->sourceLivestockId)
                ->whereDate('tanggal', $this->mutationDate)
                ->where('direction', $this->mutationDirection)
                ->where('jenis', $this->mutationType)
                ->get();

            if ($existingMutations->count() > 0 && !$this->isEditing) {
                $mutationIds = $existingMutations->pluck('id')->toArray();

                Log::info('🔍 Existing mutations found, switching to edit mode', [
                    'source_livestock_id' => $this->sourceLivestockId,
                    'mutation_date' => $this->mutationDate,
                    'mutation_count' => $existingMutations->count(),
                    'mutation_ids' => $mutationIds
                ]);

                $this->loadEditMode(['mutation_ids' => $mutationIds]);

                $this->dispatch('edit-mode-enabled', [
                    'message' => sprintf(
                        'Ditemukan %d record mutasi pada tanggal %s. Mode edit diaktifkan.',
                        $existingMutations->count(),
                        $this->mutationDate
                    ),
                    'mutation_count' => $existingMutations->count(),
                    'mutation_date' => $this->mutationDate
                ]);
            }
        } catch (Exception $e) {
            Log::error('❌ Error checking existing mutations', [
                'livestock_id' => $this->sourceLivestockId,
                'mutation_date' => $this->mutationDate,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);

            // Don't throw the error, just log it and continue
            // This prevents the component from breaking due to database issues
        }
    }
}
