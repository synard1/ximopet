<?php

namespace App\Livewire\MasterData\Livestock;

use Livewire\Component;
use App\Models\Livestock;
use App\Models\Recording;
use App\Services\Livestock\FIFODepletionService;
use App\Config\LivestockDepletionConfig;
use App\Traits\HasFifoDepletion;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class FifoDepletion extends Component
{
    use HasFifoDepletion;

    // Component properties
    public $showModal = false;
    public $livestock;
    public $livestockId;

    // Form data
    public $depletionType = 'mortality';
    public $depletionDate;
    public $totalQuantity = 1;
    public $reason = '';
    public $notes = '';

    // FIFO preview data
    public $previewData = null;
    public $canProcess = false;
    public $fifoDistribution = [];

    // UI state
    public $step = 1; // 1: Input, 2: Preview, 3: Result
    public $isLoading = false;
    public $customErrors = [];
    public $successMessage = '';
    public $isEditMode = false;
    public $existingDepletions = [];

    // Validation rules
    protected $rules = [
        'depletionType' => 'required|string',
        'depletionDate' => 'required|date',
        'totalQuantity' => 'required|integer|min:1',
        'reason' => 'nullable|string|max:500',
        'notes' => 'nullable|string|max:1000'
    ];

    protected $messages = [
        'depletionType.required' => 'Tipe depletion wajib dipilih.',
        'depletionDate.required' => 'Tanggal depletion wajib diisi.',
        'depletionDate.date' => 'Format tanggal tidak valid.',
        'totalQuantity.required' => 'Total quantity wajib diisi.',
        'totalQuantity.integer' => 'Total quantity harus berupa angka.',
        'totalQuantity.min' => 'Total quantity minimal 1.',
    ];

    protected $listeners = [
        'show-fifo-depletion' => 'handleShowModal'
    ];

    /**
     * Initialize component
     */
    public function mount()
    {
        $this->depletionDate = now()->format('Y-m-d');
        $this->totalQuantity = 1;
        $this->customErrors = [];
        $this->livestock = null;
        $this->livestockId = null;
        $this->previewData = null;
        $this->fifoDistribution = [];
        $this->successMessage = '';
        $this->showModal = false;
        $this->step = 1;
        $this->isLoading = false;
        $this->canProcess = false;
        $this->isEditMode = false;
        $this->existingDepletions = [];

        Log::info('FifoDepletion component mounted successfully');
    }

    /**
     * Get available depletion types for form dropdown
     */
    public function getDepletionTypesProperty()
    {
        return LivestockDepletionConfig::getTypesForForm(false, false);
    }

    /**
     * Validate depletion type using config
     */
    public function updatedDepletionType($value)
    {
        if (!LivestockDepletionConfig::isValidType($value)) {
            $this->addError('depletionType', 'Invalid depletion type selected.');
        } else {
            $this->clearValidation('depletionType');
        }
    }

    /**
     * Real-time quantity validation
     */
    public function updatedTotalQuantity($value)
    {
        if ($value < 1) {
            $this->addError('totalQuantity', 'Total quantity minimal 1.');
        } else {
            $this->clearValidation('totalQuantity');
        }
    }

    /**
     * Check for existing depletions when date changes
     */
    public function updatedDepletionDate($value)
    {
        if ($this->livestockId && $value) {
            $hasExisting = $this->checkExistingDepletions($value);

            // Auto-load data to form if found existing depletions
            if ($hasExisting) {
                $this->loadExistingDataToForm();
            }
        }
    }

    public function handleShowModal($livestockId)
    {
        if ($livestockId) {
            $this->openModal($livestockId);
        }
    }

    public function openModal($livestockId)
    {
        try {
            $this->reset(['previewData', 'fifoDistribution', 'successMessage']);
            $this->customErrors = [];
            $this->step = 1;
            $this->livestockId = $livestockId;
            $this->livestock = Livestock::findOrFail($livestockId);

            // Validate that livestock supports FIFO depletion
            if (!$this->validateFifoSupport()) {
                return;
            }

            // Check for existing depletions on current date
            $hasExisting = $this->checkExistingDepletions();
            if ($hasExisting) {
                $this->loadExistingDataToForm();
            }

            $this->showModal = true;

            Log::info('FIFO depletion modal opened', [
                'livestock_id' => $livestockId,
                'livestock_name' => $this->livestock->name,
                'has_existing_depletions' => $hasExisting,
                'is_edit_mode' => $this->isEditMode
            ]);
        } catch (Exception $e) {
            Log::error('Error opening FIFO depletion modal', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            $this->customErrors = ['general' => 'Error loading livestock data: ' . $e->getMessage()];
        }
    }

    public function closeModal()
    {
        // Reset all component state
        $this->showModal = false;
        $this->step = 1;
        $this->isLoading = false;
        $this->canProcess = false;
        $this->isEditMode = false;

        // Clear all data
        $this->previewData = null;
        $this->fifoDistribution = [];
        $this->existingDepletions = [];
        $this->customErrors = [];
        $this->successMessage = '';

        // Reset form fields to defaults
        $this->depletionType = 'mortality';
        $this->depletionDate = now()->format('Y-m-d');
        $this->totalQuantity = 1;
        $this->reason = '';
        $this->notes = '';

        // Clear livestock data
        $this->livestock = null;
        $this->livestockId = null;

        Log::info('FIFO depletion modal closed and reset');
    }

    /**
     * Validate that livestock supports FIFO depletion
     */
    private function validateFifoSupport()
    {
        try {
            $config = $this->livestock->getConfiguration();
            $depletionMethod = $config['depletion_method'] ?? 'manual';

            Log::info('FIFO Support Validation', [
                'livestock_id' => $this->livestockId,
                'config' => $config,
                'depletion_method' => $depletionMethod
            ]);

            if ($depletionMethod !== 'fifo') {
                $this->customErrors = ['config' => 'Livestock ini tidak menggunakan metode FIFO untuk depletion. Silakan gunakan Manual Depletion atau ubah konfigurasi terlebih dahulu.'];
                return false;
            }

            // Check if livestock has active batches
            $activeBatchesCount = $this->livestock->getActiveBatchesCount();
            $batches = $this->livestock->batches()->where('status', 'active')->get();

            Log::info('FIFO Batch Validation', [
                'livestock_id' => $this->livestockId,
                'active_batches_count' => $activeBatchesCount,
                'batch_details' => $batches->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'name' => $batch->name,
                        'status' => $batch->status,
                        'initial_quantity' => $batch->initial_quantity,
                        'current_quantity' => $batch->initial_quantity - ($batch->quantity_depletion ?? 0) - ($batch->quantity_sales ?? 0) - ($batch->quantity_mutated ?? 0)
                    ];
                })->toArray()
            ]);

            if ($activeBatchesCount < 1) {
                $this->customErrors = ['batches' => 'FIFO depletion memerlukan minimal 1 batch aktif. Livestock ini memiliki ' . $activeBatchesCount . ' batch aktif.'];
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Error validating FIFO support', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->customErrors = ['validation' => 'Error validating FIFO support: ' . $e->getMessage()];
            return false;
        }
    }

    /**
     * Find existing recording for the selected date and livestock
     */
    private function findExistingRecording($date = null)
    {
        $searchDate = $date ?: $this->depletionDate;

        try {
            $recording = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $searchDate)
                ->first();

            if ($recording) {
                Log::info('Found existing recording for FIFO depletion', [
                    'livestock_id' => $this->livestockId,
                    'recording_id' => $recording->id,
                    'date' => $searchDate
                ]);
            } else {
                Log::info('No existing recording found for FIFO depletion', [
                    'livestock_id' => $this->livestockId,
                    'date' => $searchDate
                ]);
            }

            return $recording;
        } catch (Exception $e) {
            Log::error('Error finding existing recording', [
                'livestock_id' => $this->livestockId,
                'date' => $searchDate,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check for existing FIFO depletions on selected date
     */
    private function checkExistingDepletions($date = null)
    {
        $searchDate = $date ?: $this->depletionDate;

        try {
            $existingDepletions = \App\Models\LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $searchDate)
                ->where('data->method', 'fifo')
                ->get();

            if ($existingDepletions->isNotEmpty()) {
                $this->isEditMode = true;
                $this->existingDepletions = $existingDepletions->map(function ($depletion) {
                    return [
                        'id' => $depletion->id,
                        'jenis' => $depletion->jenis,
                        'jumlah' => $depletion->jumlah,
                        'data' => $depletion->data,
                        'metadata' => $depletion->metadata,
                        'created_at' => $depletion->created_at->format('Y-m-d H:i:s')
                    ];
                })->toArray();

                Log::info('Found existing FIFO depletions for edit mode', [
                    'livestock_id' => $this->livestockId,
                    'date' => $searchDate,
                    'depletion_count' => $existingDepletions->count(),
                    'total_quantity' => $existingDepletions->sum('jumlah')
                ]);

                return true;
            } else {
                $this->isEditMode = false;
                $this->existingDepletions = [];
                return false;
            }
        } catch (Exception $e) {
            Log::error('Error checking existing depletions', [
                'livestock_id' => $this->livestockId,
                'date' => $searchDate,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function previewDepletion()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically handled by Livewire
            return;
        }

        try {
            $this->isLoading = true;
            $this->customErrors = [];

            // Check for existing depletions first
            $hasExistingDepletions = $this->checkExistingDepletions();

            if ($hasExistingDepletions && !$this->isEditMode) {
                // Load existing data into form for editing
                $this->loadExistingDataToForm();
                return;
            }

            // If in edit mode, we proceed with preview generation using the form data
            // Find existing recording for the selected date
            $existingRecording = $this->findExistingRecording();

            // Use modular FIFO depletion service via trait
            $options = [
                'date' => $this->depletionDate,
                'reason' => $this->reason ?: null,
                'notes' => $this->notes ?: null,
                'original_type' => $this->depletionType
            ];

            $this->previewData = $this->previewFifoDepletion(
                $this->depletionType,
                (int) $this->totalQuantity,
                $this->livestock,
                $options
            );

            Log::info('FIFO depletion preview response', [
                'livestock_id' => $this->livestockId,
                'is_edit_mode' => $this->isEditMode,
                'preview_data_keys' => array_keys($this->previewData),
                'distribution_keys' => array_keys($this->previewData['distribution'] ?? []),
                'actual_distribution_count' => count($this->previewData['distribution']['distribution'] ?? [])
            ]);

            // Handle preview data structure from modular service
            if (isset($this->previewData['data'])) {
                $previewDataContent = $this->previewData['data'];
                $distributionData = $previewDataContent['distribution'] ?? [];
                $actualDistribution = $distributionData['distribution'] ?? [];
            } else {
                // Fallback for direct service response
                $distributionData = $this->previewData['distribution'] ?? [];
                $actualDistribution = $distributionData['distribution'] ?? [];
            }

            // Debug logging for validation
            Log::info('FIFO Preview Validation Debug', [
                'livestock_id' => $this->livestockId,
                'is_edit_mode' => $this->isEditMode,
                'has_distribution_data' => !empty($distributionData),
                'has_actual_distribution' => !empty($actualDistribution),
                'distribution_count' => count($actualDistribution),
                'validation_data' => $distributionData['validation'] ?? [],
                'is_complete' => $distributionData['validation']['is_complete'] ?? 'not_set',
                'total_distributed' => $distributionData['validation']['total_distributed'] ?? 'not_set',
                'remaining' => $distributionData['validation']['remaining'] ?? 'not_set'
            ]);

            // Check if we can fulfill the request
            $hasDistribution = !empty($actualDistribution);
            $isComplete = ($distributionData['validation']['is_complete'] ?? false);
            $totalDistributed = $distributionData['validation']['total_distributed'] ?? 0;
            $remaining = $distributionData['validation']['remaining'] ?? $this->totalQuantity;

            // Allow processing if we have distribution and total distributed equals requested quantity
            $this->canProcess = $hasDistribution && ($isComplete || ($totalDistributed >= $this->totalQuantity) || ($remaining <= 0));

            // Ensure fifoDistribution is always a proper indexed array with correct field mapping
            $this->fifoDistribution = [];
            if (is_array($actualDistribution)) {
                foreach ($actualDistribution as $batch) {
                    $this->fifoDistribution[] = [
                        'batch_name' => $batch['batch_name'] ?? 'Unknown',
                        'start_date' => $batch['start_date'] ?? 'Unknown',
                        'age_days' => $batch['age_days'] ?? 0,
                        'available_quantity' => $batch['current_quantity'] ?? 0,
                        'quantity_to_take' => $batch['depletion_quantity'] ?? 0,
                        'remaining_after' => $batch['remaining_after_depletion'] ?? 0
                    ];
                }
            }

            $this->step = 2;

            if (!$this->canProcess) {
                $totalDistributed = $distributionData['validation']['total_distributed'] ?? 0;
                $remaining = $distributionData['validation']['remaining'] ?? $this->totalQuantity;

                $errorMessage = "Cannot fulfill FIFO depletion request completely. ";
                $errorMessage .= "Can only distribute {$totalDistributed} out of {$this->totalQuantity} requested.";

                if ($remaining > 0) {
                    $errorMessage .= " Shortfall: {$remaining} units.";
                }

                if (empty($actualDistribution)) {
                    $errorMessage = "No available batches found for FIFO depletion. Please check livestock configuration and batch availability.";
                }

                $this->customErrors = ['preview' => $errorMessage];
            }

            // Add recording information to preview data
            if ($existingRecording) {
                $this->previewData['recording_info'] = [
                    'recording_id' => $existingRecording->id,
                    'recording_date' => $existingRecording->tanggal->format('Y-m-d'),
                    'current_stock' => $existingRecording->stock_akhir ?? 0,
                    'mortality' => $existingRecording->payload['mortality'] ?? 0,
                    'culling' => $existingRecording->payload['culling'] ?? 0
                ];
            }

            // Add edit mode information to preview
            if ($this->isEditMode) {
                $this->previewData['edit_mode_info'] = [
                    'existing_depletions_count' => count($this->existingDepletions),
                    'existing_total_quantity' => array_sum(array_column($this->existingDepletions, 'jumlah')),
                    'will_replace_existing' => true,
                    'edit_date' => $this->depletionDate
                ];
            }

            Log::info('FIFO depletion preview generated', [
                'livestock_id' => $this->livestockId,
                'is_edit_mode' => $this->isEditMode,
                'can_process' => $this->canProcess,
                'total_quantity' => $this->totalQuantity,
                'batches_affected' => count($this->fifoDistribution),
                'recording_id' => $existingRecording ? $existingRecording->id : null
            ]);
        } catch (Exception $e) {
            Log::error('Error generating FIFO preview', [
                'livestock_id' => $this->livestockId,
                'is_edit_mode' => $this->isEditMode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Try to provide a fallback preview using basic batch data
            try {
                $this->createFallbackPreview();
            } catch (Exception $fallbackError) {
                Log::error('Fallback preview also failed', [
                    'livestock_id' => $this->livestockId,
                    'fallback_error' => $fallbackError->getMessage()
                ]);

                $this->customErrors = ['preview' => 'Error generating preview: ' . $e->getMessage()];
            }
        } finally {
            $this->isLoading = false;
        }
    }

    public function processDepletion()
    {
        if (!$this->canProcess && !$this->isEditMode) {
            $this->customErrors = ['process' => 'Cannot process depletion. Please check preview first.'];
            return;
        }

        try {
            $this->isLoading = true;
            $this->customErrors = [];

            // Track if we're in edit mode before resetting it
            $wasEditMode = $this->isEditMode;

            // If in edit mode, delete existing records first
            if ($this->isEditMode && !empty($this->existingDepletions)) {
                $this->deleteAllExistingDepletions();

                // Reset edit mode for new processing
                $this->isEditMode = false;
                $this->existingDepletions = [];
            }

            // Find existing recording for the selected date
            $existingRecording = $this->findExistingRecording($this->depletionDate);

            // Use modular FIFO depletion service via trait
            $options = [
                'date' => $this->depletionDate,
                'reason' => $this->reason ?: "FIFO depletion via FifoDepletion component",
                'notes' => $this->notes ?: "Depletion recorded on " . now()->format('Y-m-d H:i:s') . " by " . (auth()->user()->name ?? 'Unknown User'),
                'original_type' => $this->depletionType
            ];

            $result = $this->storeDeplesiWithFifo(
                $this->depletionType,
                (int) $this->totalQuantity,
                $existingRecording ? $existingRecording->id : null,
                $this->livestock,
                $options
            );

            if ($result && isset($result['success']) && $result['success']) {
                $editModeText = $wasEditMode ? " (Data existing telah diupdate)" : "";
                $this->successMessage = "FIFO depletion berhasil diproses{$editModeText}. Total depleted: {$result['total_quantity']} dari {$result['batches_affected']} batch.";
                $this->step = 3;

                Log::info('FIFO depletion processed successfully', [
                    'livestock_id' => $this->livestockId,
                    'was_edit_mode' => $wasEditMode,
                    'total_quantity' => $result['total_quantity'],
                    'batches_affected' => $result['batches_affected'],
                    'recording_id' => $existingRecording ? $existingRecording->id : null
                ]);

                // Emit event to refresh parent components
                $this->dispatch('depletion-processed', [
                    'livestock_id' => $this->livestockId,
                    'type' => $this->depletionType,
                    'total_depleted' => $result['total_quantity'],
                    'method' => 'fifo'
                ]);
            } else {
                $errorMsg = $result['error'] ?? 'FIFO depletion process failed';
                $this->customErrors = ['process' => 'Processing failed: ' . $errorMsg];
            }
        } catch (Exception $e) {
            Log::error('Error processing FIFO depletion', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->customErrors = ['process' => 'Error processing depletion: ' . $e->getMessage()];
        } finally {
            $this->isLoading = false;
        }
    }

    public function backToInput()
    {
        $this->step = 1;
        $this->previewData = null;
        $this->fifoDistribution = [];
        $this->canProcess = false;
        $this->customErrors = [];
    }

    public function resetForm()
    {
        $this->reset(['previewData', 'fifoDistribution', 'successMessage']);
        $this->customErrors = [];
        $this->step = 1;
        $this->canProcess = false;
        $this->isEditMode = false;
        $this->existingDepletions = [];
        $this->depletionDate = now()->format('Y-m-d');
        $this->totalQuantity = 1;
    }

    /**
     * Delete existing FIFO depletion record
     */
    public function deleteExistingDepletion($depletionId)
    {
        try {
            $depletion = \App\Models\LivestockDepletion::findOrFail($depletionId);

            // Verify it belongs to this livestock
            if ($depletion->livestock_id !== $this->livestockId) {
                $this->customErrors = ['delete' => 'Unauthorized to delete this depletion record.'];
                return;
            }

            // Get batch info for rollback
            $batchId = $depletion->data['batch_id'] ?? null;
            $quantity = $depletion->jumlah;
            $depletionType = $depletion->jenis;

            // Rollback batch quantities
            if ($batchId) {
                $batch = \App\Models\LivestockBatch::find($batchId);
                if ($batch) {
                    switch ($depletionType) {
                        case 'mortality':
                        case 'culling':
                            $batch->quantity_depletion = max(0, ($batch->quantity_depletion ?? 0) - $quantity);
                            break;
                        case 'sales':
                            $batch->quantity_sales = max(0, ($batch->quantity_sales ?? 0) - $quantity);
                            break;
                        case 'mutation':
                            $batch->quantity_mutated = max(0, ($batch->quantity_mutated ?? 0) - $quantity);
                            break;
                    }
                    $batch->save();
                }
            }

            // Rollback livestock totals
            if ($this->livestock) {
                $this->livestock->quantity_depletion = max(0, ($this->livestock->quantity_depletion ?? 0) - $quantity);
                $this->livestock->save();

                // Update CurrentLivestock quantity (recalculate from all batches)
                $currentLivestock = \App\Models\CurrentLivestock::where('livestock_id', $this->livestockId)->first();
                if ($currentLivestock) {
                    $totalCurrentQuantity = $this->livestock->batches()
                        ->where('status', 'active')
                        ->get()
                        ->sum(function ($batch) {
                            return $batch->initial_quantity
                                - ($batch->quantity_depletion ?? 0)
                                - ($batch->quantity_sales ?? 0)
                                - ($batch->quantity_mutated ?? 0);
                        });

                    $currentLivestock->quantity = $totalCurrentQuantity;
                    $currentLivestock->save();
                }
            }

            // Delete the depletion record
            $depletion->delete();

            // Refresh existing depletions list
            $this->checkExistingDepletions();

            $this->successMessage = "FIFO depletion record deleted successfully. Quantity {$quantity} has been restored.";

            Log::info('FIFO depletion record deleted', [
                'livestock_id' => $this->livestockId,
                'depletion_id' => $depletionId,
                'quantity_restored' => $quantity,
                'type' => $depletionType
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting FIFO depletion', [
                'livestock_id' => $this->livestockId,
                'depletion_id' => $depletionId,
                'error' => $e->getMessage()
            ]);

            $this->customErrors = ['delete' => 'Error deleting depletion: ' . $e->getMessage()];
        }
    }

    /**
     * Load existing depletion data into form for editing
     */
    private function loadExistingDataToForm()
    {
        if (empty($this->existingDepletions)) {
            return;
        }

        // Get the first (or most recent) depletion for editing
        $firstDepletion = $this->existingDepletions[0];

        // Load data into form fields
        $this->depletionType = $firstDepletion['jenis'];
        $this->totalQuantity = array_sum(array_column($this->existingDepletions, 'jumlah'));
        $this->reason = $firstDepletion['data']['reason'] ?? '';
        $this->notes = $firstDepletion['data']['notes'] ?? '';

        // Set informational message instead of error
        $depletionCount = count($this->existingDepletions);
        $this->customErrors = []; // Clear any existing errors

        // Add success message for edit mode
        $this->successMessage = "Mode Edit: Tanggal {$this->depletionDate} memiliki {$depletionCount} record FIFO depletion dengan total quantity {$this->totalQuantity}. Anda dapat mengedit atau menghapus data existing.";

        Log::info('Loaded existing FIFO depletion data to form', [
            'livestock_id' => $this->livestockId,
            'date' => $this->depletionDate,
            'depletion_count' => $depletionCount,
            'total_quantity' => $this->totalQuantity,
            'type' => $this->depletionType
        ]);
    }

    /**
     * Generate preview for edit mode
     */
    public function previewEditMode()
    {
        if (!$this->isEditMode) {
            $this->customErrors = ['preview' => 'Not in edit mode.'];
            return;
        }

        // Clear any existing success messages
        $this->successMessage = '';

        // Proceed with normal preview generation
        $this->previewDepletion();
    }

    /**
     * Switch to create new mode (ignore existing depletions)
     */
    public function forceCreateNew()
    {
        $this->isEditMode = false;
        $this->existingDepletions = [];
        $this->customErrors = [];
        $this->successMessage = '';
        $this->step = 1;

        // Reset form to default values
        $this->totalQuantity = 1;
        $this->reason = '';
        $this->notes = '';

        Log::info('Switched to create new mode', [
            'livestock_id' => $this->livestockId,
            'date' => $this->depletionDate
        ]);
    }

    /**
     * Delete all existing depletions for the selected date
     */
    public function deleteAllExistingDepletions()
    {
        try {
            $deletedCount = 0;
            $totalQuantityRestored = 0;

            foreach ($this->existingDepletions as $depletionData) {
                $depletion = \App\Models\LivestockDepletion::find($depletionData['id']);
                if ($depletion) {
                    $batchId = $depletion->data['batch_id'] ?? null;
                    $quantity = $depletion->jumlah;
                    $depletionType = $depletion->jenis;

                    // Rollback batch quantities
                    if ($batchId) {
                        $batch = \App\Models\LivestockBatch::find($batchId);
                        if ($batch) {
                            switch ($depletionType) {
                                case 'mortality':
                                case 'culling':
                                    $batch->quantity_depletion = max(0, ($batch->quantity_depletion ?? 0) - $quantity);
                                    break;
                                case 'sales':
                                    $batch->quantity_sales = max(0, ($batch->quantity_sales ?? 0) - $quantity);
                                    break;
                                case 'mutation':
                                    $batch->quantity_mutated = max(0, ($batch->quantity_mutated ?? 0) - $quantity);
                                    break;
                            }
                            $batch->save();
                        }
                    }

                    // Rollback livestock totals
                    if ($this->livestock) {
                        $this->livestock->quantity_depletion = max(0, ($this->livestock->quantity_depletion ?? 0) - $quantity);
                    }

                    $totalQuantityRestored += $quantity;
                    $depletion->delete();
                    $deletedCount++;
                }
            }

            // Save livestock changes and update CurrentLivestock
            if ($this->livestock) {
                $this->livestock->save();

                $currentLivestock = \App\Models\CurrentLivestock::where('livestock_id', $this->livestockId)->first();
                if ($currentLivestock) {
                    $totalCurrentQuantity = $this->livestock->batches()
                        ->where('status', 'active')
                        ->get()
                        ->sum(function ($batch) {
                            return $batch->initial_quantity
                                - ($batch->quantity_depletion ?? 0)
                                - ($batch->quantity_sales ?? 0)
                                - ($batch->quantity_mutated ?? 0);
                        });

                    $currentLivestock->quantity = $totalCurrentQuantity;
                    $currentLivestock->save();
                }
            }

            // Reset edit mode
            $this->isEditMode = false;
            $this->existingDepletions = [];
            $this->customErrors = [];

            $this->successMessage = "Berhasil menghapus {$deletedCount} record FIFO depletion. Total quantity {$totalQuantityRestored} telah dikembalikan.";

            Log::info('All FIFO depletion records deleted for date', [
                'livestock_id' => $this->livestockId,
                'date' => $this->depletionDate,
                'deleted_count' => $deletedCount,
                'quantity_restored' => $totalQuantityRestored
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting all FIFO depletions', [
                'livestock_id' => $this->livestockId,
                'date' => $this->depletionDate,
                'error' => $e->getMessage()
            ]);

            $this->customErrors = ['delete_all' => 'Error deleting all depletions: ' . $e->getMessage()];
        }
    }

    /**
     * Create a fallback preview when the main service fails
     */
    private function createFallbackPreview()
    {
        // Ensure livestock is loaded
        if (!$this->livestock && $this->livestockId) {
            $this->livestock = Livestock::find($this->livestockId);
        }

        if (!$this->livestock) {
            throw new Exception('Livestock data not available for fallback preview');
        }

        $batches = $this->livestock->batches()
            ->where('status', 'active')
            ->orderBy('start_date', 'asc')
            ->get();

        if ($batches->isEmpty()) {
            throw new Exception('No active batches available for fallback preview');
        }

        $remainingQuantity = $this->totalQuantity;
        $fallbackDistribution = [];

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $currentQuantity = $batch->initial_quantity -
                ($batch->quantity_depletion ?? 0) -
                ($batch->quantity_sales ?? 0) -
                ($batch->quantity_mutated ?? 0);

            if ($currentQuantity > 0) {
                $takeQuantity = min($remainingQuantity, $currentQuantity);

                $fallbackDistribution[] = [
                    'batch_name' => $batch->name ?? 'Batch #' . $batch->id,
                    'start_date' => $batch->start_date ? $batch->start_date->format('Y-m-d') : 'Unknown',
                    'age_days' => $batch->start_date ? $batch->start_date->diffInDays(now()) : 0,
                    'available_quantity' => $currentQuantity,
                    'quantity_to_take' => $takeQuantity,
                    'remaining_after' => $currentQuantity - $takeQuantity
                ];

                $remainingQuantity -= $takeQuantity;
            }
        }

        $this->fifoDistribution = $fallbackDistribution;
        $this->canProcess = $remainingQuantity <= 0;
        $this->step = 2;

        if (!$this->canProcess) {
            $this->customErrors = ['preview' => "Fallback preview: Can only fulfill " . ($this->totalQuantity - $remainingQuantity) . " out of {$this->totalQuantity} requested. Shortfall: {$remainingQuantity} units."];
        }

        // Create basic preview data structure
        $this->previewData = [
            'livestock_id' => $this->livestockId,
            'method' => 'fallback',
            'distribution' => [
                'validation' => [
                    'total_distributed' => $this->totalQuantity - $remainingQuantity,
                    'remaining' => $remainingQuantity,
                    'is_complete' => $remainingQuantity <= 0
                ]
            ]
        ];

        Log::info('Fallback preview created', [
            'livestock_id' => $this->livestockId,
            'batches_used' => count($fallbackDistribution),
            'can_process' => $this->canProcess,
            'total_distributed' => $this->totalQuantity - $remainingQuantity
        ]);
    }

    /**
     * Get edit mode summary for display
     */
    public function getEditModeSummary()
    {
        if (!$this->isEditMode || empty($this->existingDepletions)) {
            return null;
        }

        $depletionCount = count($this->existingDepletions);
        $totalQuantity = array_sum(array_column($this->existingDepletions, 'jumlah'));
        $types = array_unique(array_column($this->existingDepletions, 'jenis'));

        return [
            'count' => $depletionCount,
            'total_quantity' => $totalQuantity,
            'types' => $types,
            'date' => $this->depletionDate,
            'details' => $this->existingDepletions
        ];
    }

    public function render()
    {
        return view('livewire.master-data.livestock.fifo-depletion', [
            'editModeSummary' => $this->getEditModeSummary()
        ]);
    }
}
