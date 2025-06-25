<?php

namespace App\Livewire\MasterData\Livestock;

use Livewire\Component;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\LivestockBatch;
use App\Services\Livestock\BatchDepletionService;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\ValidationException;

class ManualBatchDepletion extends Component
{
    // Component properties
    public $showModal = false;
    public $livestock;
    public $livestockId;

    // Form data
    public $depletionType = 'mortality';
    public $depletionDate;
    public $reason = '';

    // Batch data
    public $availableBatches = [];
    public $selectedBatches = [];

    // Preview data
    public $previewData = null;
    public $canProcess = false;

    // UI state
    public $step = 1; // 1: Selection, 2: Preview, 3: Result
    public $isLoading = false;
    public $successMessage = '';
    public $isEditing = false;
    public $existingDepletionId = null;
    public $existingDepletionIds = []; // Store all depletion IDs for edit mode
    public $errorMessage = '';

    // Validation rules
    protected $rules = [
        'depletionType' => 'required|in:mortality,sales,mutation,culling,other',
        'depletionDate' => 'required|date',
        'reason' => 'nullable|string|max:500',
        'selectedBatches.*.quantity' => 'required|integer|min:1',
        'selectedBatches.*.note' => 'nullable|string|max:255'
    ];

    protected $messages = [
        'depletionType.required' => 'Tipe depletion wajib dipilih.',
        'depletionType.in' => 'Tipe depletion tidak valid.',
        'depletionDate.required' => 'Tanggal depletion wajib diisi.',
        'depletionDate.date' => 'Format tanggal tidak valid.',
        'selectedBatches.*.quantity.required' => 'Quantity batch wajib diisi.',
        'selectedBatches.*.quantity.integer' => 'Quantity harus berupa angka.',
        'selectedBatches.*.quantity.min' => 'Quantity minimal 1.',
    ];

    protected $listeners = [
        'show-manual-depletion' => 'handleShowModal'
    ];

    /**
     * Initialize component
     */
    public function mount()
    {
        $this->depletionDate = now()->format('Y-m-d');
        $this->availableBatches = [];
        $this->selectedBatches = [];
        $this->livestock = null;
        $this->livestockId = null;
        $this->previewData = null;
        $this->successMessage = '';
        $this->showModal = false;
        $this->step = 1;
        $this->isLoading = false;
        $this->canProcess = false;
        $this->isEditing = false;
        $this->existingDepletionId = null;
        $this->existingDepletionIds = [];
        $this->errorMessage = '';

        Log::info('ManualBatchDepletion component mounted successfully');
    }

    public function updatedDepletionDate($value)
    {
        Log::info('🔄 Depletion date updated', [
            'old_date' => $this->depletionDate,
            'new_date' => $value,
            'livestock_id' => $this->livestockId
        ]);

        $this->depletionDate = $value;
        $this->loadAvailableBatches();
        $this->selectedBatches = [];
        $this->checkForExistingDepletion();
    }

    private function checkForExistingDepletion()
    {
        if (!$this->livestockId || !$this->depletionDate) {
            return;
        }

        $this->resetFormForCreate();
        $this->errorMessage = '';
        $this->isLoading = true;

        try {
            $depletions = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $this->depletionDate)
                ->get();

            Log::info('🔍 Searching for existing depletions', [
                'livestock_id' => $this->livestockId,
                'depletion_date' => $this->depletionDate,
                'found_depletions' => $depletions->count()
            ]);

            $manualDepletion = null;
            $allManualDepletions = [];

            foreach ($depletions as $depletion) {
                $data = $depletion->data ?? [];
                $metadata = $depletion->metadata ?? [];

                // Check multiple possible indicators for manual depletion
                $isManualDepletion = false;

                // Method 1: Check for 'method' field in data
                if (isset($data['method']) && $data['method'] === 'manual') {
                    $isManualDepletion = true;
                }

                // Method 2: Check for 'depletion_method' field in data
                if (isset($data['depletion_method']) && $data['depletion_method'] === 'manual') {
                    $isManualDepletion = true;
                }

                // Method 3: Check for manual batches in data
                if (isset($data['manual_batches']) && is_array($data['manual_batches'])) {
                    $isManualDepletion = true;
                }

                // Method 4: Check metadata for manual indicator
                if (isset($metadata['method']) && $metadata['method'] === 'manual') {
                    $isManualDepletion = true;
                }

                // Method 5: Check if data contains 'batches' array (our expected structure)
                if (isset($data['batches']) && is_array($data['batches'])) {
                    $isManualDepletion = true;
                }

                // Method 6: Fallback - if no specific manual indicators but has basic depletion data
                // This allows editing of other types of depletions by converting them to manual format
                if (!$isManualDepletion && $depletion->jumlah > 0) {
                    // Check if this looks like a simple depletion that can be edited manually
                    $hasBasicData = !empty($depletion->jenis) && !empty($depletion->jumlah);

                    // Check if this is not a complex FIFO depletion
                    $isNotComplexFifo = !isset($data['fifo_result']) &&
                        !isset($data['depleted_batches']) &&
                        !isset($metadata['fifo_processing']);

                    if ($hasBasicData && $isNotComplexFifo) {
                        $isManualDepletion = true;
                        Log::info('🔄 Fallback: Treating simple depletion as editable manually', [
                            'depletion_id' => $depletion->id,
                            'jenis' => $depletion->jenis,
                            'jumlah' => $depletion->jumlah
                        ]);
                    }
                }

                // Log the depletion data for debugging
                Log::info('🔍 Checking depletion for manual type', [
                    'depletion_id' => $depletion->id,
                    'jenis' => $depletion->jenis,
                    'jumlah' => $depletion->jumlah,
                    'data_keys' => array_keys($data),
                    'metadata_keys' => array_keys($metadata),
                    'is_manual' => $isManualDepletion,
                    'data_method' => $data['method'] ?? 'not_set',
                    'data_depletion_method' => $data['depletion_method'] ?? 'not_set',
                    'has_manual_batches' => isset($data['manual_batches']),
                    'has_batches' => isset($data['batches']),
                ]);

                if ($isManualDepletion) {
                    $allManualDepletions[] = $depletion;
                    if (!$manualDepletion) {
                        $manualDepletion = $depletion; // Keep first one as primary
                    }
                }
            }

            if ($manualDepletion && !empty($allManualDepletions)) {
                $this->loadExistingDepletionData($manualDepletion, $allManualDepletions);

                // Show notification about edit mode
                $this->dispatch('depletion-edit-mode-enabled', [
                    'message' => 'Existing manual depletion data loaded for editing',
                    'date' => $this->depletionDate,
                    'total_batches' => count($this->selectedBatches),
                    'total_depletions' => count($allManualDepletions)
                ]);

                Log::info('🔄 Existing manual depletion loaded automatically', [
                    'livestock_id' => $this->livestockId,
                    'depletion_date' => $this->depletionDate,
                    'batches_count' => count($this->selectedBatches),
                    'depletions_count' => count($allManualDepletions),
                    'trigger' => 'auto-load'
                ]);
            } else {
                // Mode create, form kosong
                $this->resetEditMode();
                Log::info('ManualBatchDepletion: No existing manual depletion, create mode', [
                    'date' => $this->depletionDate,
                    'depletions_found' => $depletions->count(),
                    'all_depletions_data' => $depletions->map(function ($d) {
                        return [
                            'id' => $d->id,
                            'jenis' => $d->jenis,
                            'data_keys' => array_keys($d->data ?? []),
                            'metadata_keys' => array_keys($d->metadata ?? [])
                        ];
                    })->toArray()
                ]);
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Gagal memuat data deplesi: ' . $e->getMessage();
            Log::error('ManualBatchDepletion: Error loading depletion', [
                'error' => $e->getMessage(),
                'date' => $this->depletionDate,
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Load existing depletion data into component
     */
    private function loadExistingDepletionData($primaryDepletion, $allManualDepletions)
    {
        try {
            // Set edit mode
            $this->isEditing = true;
            $this->existingDepletionId = $primaryDepletion->id;
            $this->existingDepletionIds = collect($allManualDepletions)->pluck('id')->toArray();

            $primaryData = $primaryDepletion->data ?? [];
            $primaryMetadata = $primaryDepletion->metadata ?? [];

            Log::info('🔍 Setting up edit mode', [
                'primary_depletion_id' => $this->existingDepletionId,
                'all_depletion_ids' => $this->existingDepletionIds,
                'total_depletions' => count($allManualDepletions)
            ]);

            // Extract depletion type from primary depletion
            $this->depletionType = $primaryData['depletion_type'] ??
                $primaryData['type'] ??
                $primaryDepletion->jenis ??
                $this->depletionType;

            // Extract reason from primary depletion
            $this->reason = $primaryData['reason'] ??
                $primaryData['notes'] ??
                $primaryMetadata['reason'] ??
                '';

            $this->selectedBatches = [];
            $batchQuantities = []; // Track quantities per batch

            Log::info('🔍 Processing multiple depletions for edit mode', [
                'total_depletions' => count($allManualDepletions),
                'primary_depletion_id' => $primaryDepletion->id
            ]);

            // Process all manual depletions to combine quantities
            foreach ($allManualDepletions as $depletion) {
                $data = $depletion->data ?? [];
                $metadata = $depletion->metadata ?? [];

                Log::info('🔍 Processing depletion', [
                    'depletion_id' => $depletion->id,
                    'jumlah' => $depletion->jumlah,
                    'jenis' => $depletion->jenis,
                    'data_keys' => array_keys($data),
                    'metadata_keys' => array_keys($metadata)
                ]);

                // Try to extract batch data from different possible structures
                $batchesData = [];

                // Method 1: Check for 'batches' array in data
                if (isset($data['batches']) && is_array($data['batches'])) {
                    $batchesData = $data['batches'];
                    Log::info('🔍 Found batches in data.batches', ['count' => count($batchesData)]);
                }
                // Method 2: Check for 'manual_batches' array in data
                elseif (isset($data['manual_batches']) && is_array($data['manual_batches'])) {
                    $batchesData = $data['manual_batches'];
                    Log::info('🔍 Found batches in data.manual_batches', ['count' => count($batchesData)]);
                }
                // Method 3: Check for batch data in metadata
                elseif (isset($metadata['batches']) && is_array($metadata['batches'])) {
                    $batchesData = $metadata['batches'];
                    Log::info('🔍 Found batches in metadata.batches', ['count' => count($batchesData)]);
                }
                // Method 4: If no batch data found, create a single batch entry from the main depletion data
                else {
                    Log::info('🔍 No batch array found, creating single batch from main data', [
                        'depletion_jumlah' => $depletion->jumlah
                    ]);

                    // Try to find a suitable batch for this quantity
                    $suitableBatch = null;
                    if (!empty($this->availableBatches)) {
                        // Find the batch with highest available quantity
                        $suitableBatch = collect($this->availableBatches)->sortByDesc('available_quantity')->first();
                    }

                    $batchesData = [[
                        'batch_id' => $data['batch_id'] ?? $suitableBatch['batch_id'] ?? null,
                        'batch_name' => $data['batch_name'] ?? $suitableBatch['batch_name'] ?? 'Auto-Assigned Batch',
                        'quantity' => $depletion->jumlah ?? 0,
                        'note' => $data['note'] ?? $this->reason ?? 'Converted from simple depletion',
                        'available_quantity' => $data['available_quantity'] ?? $suitableBatch['available_quantity'] ?? 0,
                        'age_days' => $data['age_days'] ?? $suitableBatch['age_days'] ?? 0,
                    ]];
                }

                // Process batch data and accumulate quantities
                foreach ($batchesData as $batch) {
                    $batchId = $batch['batch_id'] ?? $batch['id'] ?? null;
                    $batchName = $batch['batch_name'] ?? $batch['name'] ?? 'Unknown Batch';
                    $quantity = $batch['quantity'] ?? $batch['jumlah'] ?? 0;
                    $note = $batch['note'] ?? $batch['notes'] ?? '';

                    // If we don't have batch_id, try to find it from available batches
                    if (!$batchId && !empty($this->availableBatches)) {
                        $matchedBatch = collect($this->availableBatches)->first(function ($availableBatch) use ($batchName) {
                            return $availableBatch['batch_name'] === $batchName;
                        });

                        if ($matchedBatch) {
                            $batchId = $matchedBatch['batch_id'];
                            $batchName = $matchedBatch['batch_name'];
                        }
                    }

                    // Use batch_id as key, or batch_name if no ID
                    $batchKey = $batchId ?: $batchName;

                    // Accumulate quantities for the same batch
                    if (isset($batchQuantities[$batchKey])) {
                        $batchQuantities[$batchKey]['quantity'] += $quantity;
                        $batchQuantities[$batchKey]['note'] .= ($batchQuantities[$batchKey]['note'] ? '; ' : '') . $note;
                    } else {
                        // Find available batch info
                        $availableBatch = collect($this->availableBatches)->firstWhere('batch_id', $batchId) ??
                            collect($this->availableBatches)->firstWhere('batch_name', $batchName);

                        $batchQuantities[$batchKey] = [
                            'batch_id' => $batchId,
                            'batch_name' => $batchName,
                            'available_quantity' => $batch['available_quantity'] ?? $availableBatch['available_quantity'] ?? 0,
                            'age_days' => $batch['age_days'] ?? $availableBatch['age_days'] ?? 0,
                            'quantity' => $quantity,
                            'note' => $note,
                        ];
                    }
                }
            }

            // Convert accumulated quantities to selectedBatches
            foreach ($batchQuantities as $batchData) {
                $this->selectedBatches[] = $batchData;
            }

            Log::info('🔄 Loaded existing depletion data for editing', [
                'livestock_id' => $this->livestockId,
                'depletion_date' => $this->depletionDate,
                'batches_count' => count($this->selectedBatches),
                'total_depletions_processed' => count($allManualDepletions),
                'depletion_type' => $this->depletionType,
                'reason' => $this->reason,
                'selected_batches' => $this->selectedBatches,
                'batch_quantities_accumulated' => $batchQuantities
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading existing depletion data', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->errorMessage = 'Error loading existing data: ' . $e->getMessage();
            $this->resetEditMode();
        }
    }

    public function handleShowModal($livestockId)
    {
        if ($livestockId) {
            $this->openModal($livestockId);
        }
        // dd($livestockId);
    }

    public function openModal($livestockId)
    {
        $this->reset(['previewData', 'selectedBatches', 'successMessage', 'errorMessage']);
        $this->isLoading = true;
        try {
            $this->livestockId = $livestockId;
            $this->livestock = Livestock::find($livestockId);
            $rawBatches = LivestockBatch::where('livestock_id', $livestockId)->get()->toArray();
            $this->availableBatches = collect($rawBatches)->map(function ($batch) {
                return [
                    'batch_id' => $batch['batch_id'] ?? $batch['id'] ?? null,
                    'batch_name' => $batch['batch_name'] ?? $batch['name'] ?? '-',
                    'age_days' => $batch['age_days'] ?? 0,
                    'available_quantity' => $batch['available_quantity'] ?? 0,
                    'utilization_rate' => $batch['utilization_rate'] ?? 0,
                ];
            })->toArray();
            $this->depletionDate = now()->format('Y-m-d');
            $this->loadAvailableBatches();
            $this->checkForExistingDepletion();
            $this->showModal = true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error loading livestock data: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset();
        $this->resetErrorBag();

        // Dispatch event to close modal via JavaScript
        $this->dispatch('close-manual-depletion-modal');

        Log::info('🔥 Manual batch depletion modal closed via Livewire');
    }

    /**
     * Close modal without dispatching JavaScript event (called from Bootstrap events)
     */
    public function closeModalSilent()
    {
        $this->showModal = false;
        $this->reset();
        $this->resetErrorBag();

        Log::info('🔥 Manual batch depletion modal closed via Bootstrap event');
    }

    private function loadAvailableBatches()
    {
        try {
            $service = new BatchDepletionService();
            $batchData = $service->getAvailableBatchesForManualSelection($this->livestockId);
            $this->availableBatches = collect($batchData['batches'])->map(function ($batch) {
                return [
                    'batch_id' => $batch['batch_id'] ?? $batch['id'] ?? null,
                    'batch_name' => $batch['batch_name'] ?? $batch['name'] ?? '-',
                    'age_days' => $batch['age_days'] ?? 0,
                    'available_quantity' => $batch['available_quantity'] ?? 0,
                    'utilization_rate' => $batch['utilization_rate'] ?? 0,
                    // tambahkan key lain yang dibutuhkan view di sini
                ];
            })->toArray();
            Log::info('Available batches loaded', [
                'livestock_id' => $this->livestockId,
                'batch_count' => count($this->availableBatches)
            ]);
        } catch (Exception $e) {
            Log::error('Error loading available batches', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);
            $this->addError('batches', 'Error loading batch data: ' . $e->getMessage());
        }
    }

    public function addBatch($batchId)
    {
        // Check if batch already selected
        if (collect($this->selectedBatches)->contains('batch_id', $batchId)) {
            return;
        }

        // Find batch data
        $batch = collect($this->availableBatches)->firstWhere('batch_id', $batchId);

        if ($batch) {
            $this->selectedBatches[] = [
                'batch_id' => $batch['batch_id'],
                'batch_name' => $batch['batch_name'],
                'available_quantity' => $batch['available_quantity'],
                'age_days' => $batch['age_days'],
                'quantity' => 1,
                'note' => ''
            ];
        }
    }

    public function removeBatch($index)
    {
        unset($this->selectedBatches[$index]);
        $this->selectedBatches = array_values($this->selectedBatches);
    }

    /**
     * Validate depletion input restrictions based on company config
     */
    private function validateDepletionInputRestrictions()
    {
        $config = CompanyConfig::getActiveConfigSection('livestock', 'depletion_tracking');
        $restrictions = $config['input_restrictions'] ?? [];

        // Skip validation if restrictions are not configured
        if (empty($restrictions)) {
            Log::info('Depletion input restrictions not configured, skipping validation', [
                'livestock_id' => $this->livestockId
            ]);
            return true;
        }

        Log::info('Validating depletion input restrictions', [
            'livestock_id' => $this->livestockId,
            'restrictions' => $restrictions,
            'selected_batches_count' => count($this->selectedBatches)
        ]);

        $validationErrors = [];

        // Check if same day repeated input is allowed
        if (!($restrictions['allow_same_day_repeated_input'] ?? true)) {
            if ($this->hasDepletionToday()) {
                $validationErrors['same_day'] = 'Input deplesi berulang dalam hari yang sama tidak diizinkan.';
                Log::warning('Same day repeated input blocked', ['livestock_id' => $this->livestockId]);
            }
        }

        // Check if same batch repeated input is allowed
        if (!($restrictions['allow_same_batch_repeated_input'] ?? true)) {
            $conflictingBatches = $this->getConflictingBatchesToday();
            if (!empty($conflictingBatches)) {
                $batchNames = implode(', ', $conflictingBatches);
                $validationErrors['same_batch'] = "Input deplesi untuk batch berikut sudah ada hari ini: {$batchNames}";
                Log::warning('Same batch repeated input blocked', [
                    'livestock_id' => $this->livestockId,
                    'conflicting_batches' => $conflictingBatches
                ]);
            }
        }

        // Check maximum depletion per day per batch
        $maxPerDay = $restrictions['max_depletion_per_day_per_batch'] ?? 10;
        $batchCounts = $this->getBatchDepletionCountsToday();
        foreach ($this->selectedBatches as $batch) {
            $currentCount = $batchCounts[$batch['batch_id']] ?? 0;
            if ($currentCount >= $maxPerDay) {
                $validationErrors['max_per_day'] = "Batch {$batch['batch_name']} sudah mencapai batas maksimal {$maxPerDay} input deplesi per hari.";
                Log::warning('Maximum depletion per day exceeded', [
                    'livestock_id' => $this->livestockId,
                    'batch_id' => $batch['batch_id'],
                    'current_count' => $currentCount,
                    'max_per_day' => $maxPerDay
                ]);
                break;
            }
        }

        // Check zero quantity
        if (!($restrictions['allow_zero_quantity'] ?? false)) {
            foreach ($this->selectedBatches as $batch) {
                if (($batch['quantity'] ?? 0) <= 0) {
                    $validationErrors['zero_quantity'] = 'Quantity tidak boleh kosong atau nol.';
                    Log::warning('Zero quantity blocked', [
                        'livestock_id' => $this->livestockId,
                        'batch_id' => $batch['batch_id']
                    ]);
                    break;
                }
            }
        }

        // Check minimum interval
        $minInterval = $restrictions['min_interval_minutes'] ?? 0;
        if ($minInterval > 0) {
            $lastDepletion = $this->getLastDepletionTime();
            if ($lastDepletion && now()->diffInMinutes($lastDepletion) < $minInterval) {
                $validationErrors['min_interval'] = "Harus menunggu minimal {$minInterval} menit dari input deplesi terakhir.";
                Log::warning('Minimum interval not met', [
                    'livestock_id' => $this->livestockId,
                    'last_depletion' => $lastDepletion,
                    'minutes_since_last' => now()->diffInMinutes($lastDepletion),
                    'min_interval' => $minInterval
                ]);
            }
        }

        if (!empty($validationErrors)) {
            foreach ($validationErrors as $key => $message) {
                $this->addError($key, $message);
            }
            Log::warning('Depletion input validation failed', [
                'livestock_id' => $this->livestockId,
                'validation_errors' => $validationErrors
            ]);
            return false;
        }

        Log::info('Depletion input validation passed', [
            'livestock_id' => $this->livestockId
        ]);
        return true;
    }

    /**
     * Check if there's any depletion for this livestock today
     */
    private function hasDepletionToday()
    {
        return DB::table('livestock_depletions')
            ->where('livestock_id', $this->livestockId)
            ->whereDate('created_at', now()->toDateString())
            ->exists();
    }

    /**
     * Get conflicting batches that already have depletion today
     */
    private function getConflictingBatchesToday()
    {
        $selectedBatchIds = collect($this->selectedBatches)->pluck('batch_id')->toArray();

        // Get today's depletions for this livestock
        $todayDepletions = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $existingBatches = [];

        foreach ($todayDepletions as $depletion) {
            // Check if depletion has batch data in data column
            if (isset($depletion->data['manual_batches']) && is_array($depletion->data['manual_batches'])) {
                foreach ($depletion->data['manual_batches'] as $batchData) {
                    if (in_array($batchData['batch_id'], $selectedBatchIds)) {
                        // Get batch name from livestock_batches table
                        $batch = LivestockBatch::find($batchData['batch_id']);
                        if ($batch) {
                            $existingBatches[] = $batch->batch_name;
                        }
                    }
                }
            }
        }

        return array_unique($existingBatches);
    }

    /**
     * Get depletion counts per batch for today
     */
    private function getBatchDepletionCountsToday()
    {
        $selectedBatchIds = collect($this->selectedBatches)->pluck('batch_id')->toArray();

        // Get today's depletions for this livestock
        $todayDepletions = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $counts = [];

        foreach ($todayDepletions as $depletion) {
            // Check if depletion has batch data in data column
            if (isset($depletion->data['manual_batches']) && is_array($depletion->data['manual_batches'])) {
                foreach ($depletion->data['manual_batches'] as $batchData) {
                    $batchId = $batchData['batch_id'];
                    if (in_array($batchId, $selectedBatchIds)) {
                        $quantity = $batchData['quantity'] ?? 1;
                        $counts[$batchId] = ($counts[$batchId] ?? 0) + $quantity;
                    }
                }
            }
        }

        return $counts;
    }

    /**
     * Get last depletion time for this livestock
     */
    private function getLastDepletionTime()
    {
        $lastDepletion = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastDepletion ? Carbon::parse($lastDepletion->created_at) : null;
    }

    public function previewDepletion()
    {
        $this->validate();

        if (empty($this->selectedBatches)) {
            $this->addError('selection', 'Minimal pilih satu batch untuk depletion.');
            return;
        }

        // Validate depletion input restrictions
        if (!$this->validateDepletionInputRestrictions()) {
            return; // Errors are already set in the validation method
        }

        try {
            $this->isLoading = true;

            $depletionData = [
                'livestock_id' => $this->livestockId,
                'type' => $this->depletionType,
                'depletion_method' => 'manual',
                'manual_batches' => collect($this->selectedBatches)->map(function ($batch) {
                    return [
                        'batch_id' => $batch['batch_id'],
                        'quantity' => (int) $batch['quantity'],
                        'note' => $batch['note'] ?: null
                    ];
                })->toArray()
            ];

            $service = new BatchDepletionService();
            $this->previewData = $service->previewManualBatchDepletion($depletionData);

            $this->canProcess = $this->previewData['can_fulfill'] && $this->previewData['validation_passed'];
            $this->step = 2;

            if (!$this->canProcess) {
                $this->addError('preview', $this->previewData['errors'] ?? ['preview' => 'Cannot fulfill depletion request.']);
            }

            Log::info('Depletion preview generated', [
                'livestock_id' => $this->livestockId,
                'can_process' => $this->canProcess,
                'total_quantity' => $this->previewData['total_quantity']
            ]);
        } catch (Exception $e) {
            Log::error('Error generating preview', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->addError('preview', 'Error generating preview: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function processDepletion()
    {
        if (!$this->canProcess) {
            $this->addError('process', 'Cannot process depletion. Please check preview first.');
            return;
        }

        // Re-validate depletion input restrictions before processing
        if (!$this->validateDepletionInputRestrictions()) {
            return; // Errors are already set in the validation method
        }

        try {
            $this->isLoading = true;

            $depletionData = [
                'livestock_id' => $this->livestockId,
                'type' => $this->depletionType,
                'date' => $this->depletionDate,
                'depletion_method' => 'manual',
                'manual_batches' => collect($this->selectedBatches)->map(function ($batch) {
                    return [
                        'batch_id' => $batch['batch_id'],
                        'quantity' => (int) $batch['quantity'],
                        'note' => $batch['note'] ?: null
                    ];
                })->toArray(),
                'reason' => $this->reason,
                'is_editing' => $this->isEditing,
                'existing_depletion_id' => $this->existingDepletionId,
                'existing_depletion_ids' => $this->existingDepletionIds
            ];

            Log::info('🔄 Processing manual batch depletion', [
                'livestock_id' => $this->livestockId,
                'mode' => $this->isEditing ? 'UPDATE' : 'CREATE',
                'existing_depletion_id' => $this->existingDepletionId,
                'existing_depletion_ids' => $this->existingDepletionIds,
                'total_existing_ids' => count($this->existingDepletionIds),
                'selected_batches_count' => count($this->selectedBatches),
                'depletion_type' => $this->depletionType
            ]);

            $service = new BatchDepletionService();
            $result = $service->processDepletion($depletionData);

            if ($result['success']) {
                $updateStrategy = $result['update_strategy'] ?? 'CREATE_NEW';

                if ($this->isEditing) {
                    switch ($updateStrategy) {
                        case 'UPDATE_EXISTING':
                            $message = "Data deplesi berhasil diperbarui (record existing di-update).";
                            break;
                        case 'DELETE_AND_CREATE':
                            $message = "Data deplesi berhasil diperbarui (record lama dihapus, record baru dibuat).";
                            break;
                        default:
                            $message = "Data deplesi berhasil diperbarui.";
                    }
                } else {
                    $message = "Manual batch depletion berhasil diproses.";
                }

                $this->successMessage = "{$message} Total depleted: {$result['total_depleted']} dari {$result['livestock_id']}.";
                $this->step = 3;

                Log::info('Manual batch depletion processed successfully', [
                    'livestock_id' => $this->livestockId,
                    'total_depleted' => $result['total_depleted'],
                    'processed_batches' => count($result['processed_batches']),
                    'config_validation' => 'passed',
                    'mode' => $this->isEditing ? 'update' : 'create',
                    'update_strategy' => $updateStrategy
                ]);

                // Emit event to refresh parent components
                $this->dispatch('depletion-processed', [
                    'livestock_id' => $this->livestockId,
                    'type' => $this->depletionType,
                    'total_depleted' => $result['total_depleted']
                ]);
            } else {
                $this->addError('process', 'Processing failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            Log::error('Error processing depletion', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->addError('process', 'Error processing depletion: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function backToSelection()
    {
        $this->step = 1;
        $this->previewData = null;
        $this->canProcess = false;
        $this->resetErrorBag();
    }

    private function resetFormForCreate()
    {
        $this->isEditing = false;
        $this->existingDepletionId = null;
        $this->reset(['selectedBatches', 'previewData', 'successMessage', 'reason', 'depletionType']);
        $this->depletionType = 'mortality'; // default value
        $this->resetErrorBag();
        $this->step = 1;
        $this->canProcess = false;
    }

    public function resetForm()
    {
        $this->reset(['selectedBatches', 'previewData', 'successMessage']);
        $this->resetErrorBag();
        $this->step = 1;
        $this->canProcess = false;
        $this->depletionDate = now()->format('Y-m-d');
        $this->checkForExistingDepletion();
    }

    /**
     * Cancel edit mode and reset to new entry
     */
    public function cancelEditMode()
    {
        $this->resetEditMode();

        // Reset form to initial state
        $this->selectedBatches = [];
        $this->depletionType = 'mortality';
        $this->reason = '';

        // Reset to selection step
        $this->step = 1;
        $this->previewData = null;
        $this->canProcess = false;

        $this->dispatch('depletion-edit-mode-cancelled');

        Log::info('🚫 Edit mode cancelled', [
            'livestock_id' => $this->livestockId,
            'depletion_date' => $this->depletionDate
        ]);
    }

    /**
     * Reset edit mode
     */
    private function resetEditMode()
    {
        $this->isEditing = false;
        $this->existingDepletionId = null;
        $this->existingDepletionIds = [];

        // Clear selected batches if switching from edit mode
        if (!empty($this->selectedBatches)) {
            $this->selectedBatches = [];
        }
    }

    public function render()
    {
        return view('livewire.master-data.livestock.manual-batch-depletion');
    }
}
