<?php

namespace App\Livewire\MasterData\Livestock;

use Livewire\Component;
use App\Models\Livestock;
use App\Models\Recording;
use App\Services\Livestock\BatchDepletionService;
use App\Config\LivestockDepletionConfig;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ManualDepletion extends Component
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
    public $errors = [];
    public $successMessage = '';

    // Validation rules (updated to use config)
    protected $rules = [
        'depletionType' => 'required|string',
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
     * Remove any parameters to avoid dependency injection issues
     */
    public function mount()
    {
        $this->depletionDate = now()->format('Y-m-d');
        $this->errors = [];
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

        Log::info('ManualDepletion component mounted successfully');
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

    public function handleShowModal($livestockId)
    {
        if ($livestockId) {
            $this->openModal($livestockId);
        }
    }

    public function openModal($livestockId)
    {
        try {
            $this->reset(['previewData', 'selectedBatches', 'successMessage']);
            $this->errors = []; // Reset errors as array
            $this->step = 1;
            $this->livestockId = $livestockId;
            $this->livestock = Livestock::findOrFail($livestockId);

            $this->loadAvailableBatches();
            $this->showModal = true;

            Log::info('Manual depletion modal opened', [
                'livestock_id' => $livestockId,
                'livestock_name' => $this->livestock->name
            ]);
        } catch (Exception $e) {
            Log::error('Error opening manual depletion modal', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['general' => 'Error loading livestock data: ' . $e->getMessage()];
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset();
        $this->errors = []; // Reset errors as array
    }

    private function loadAvailableBatches()
    {
        try {
            $service = new BatchDepletionService();
            $batchData = $service->getAvailableBatchesForManualSelection($this->livestockId);

            $this->availableBatches = $batchData['batches'];

            Log::info('Available batches loaded', [
                'livestock_id' => $this->livestockId,
                'batch_count' => count($this->availableBatches)
            ]);
        } catch (Exception $e) {
            Log::error('Error loading available batches', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['batches' => 'Error loading batch data: ' . $e->getMessage()];
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
                Log::info('Found existing recording for manual depletion', [
                    'livestock_id' => $this->livestockId,
                    'recording_id' => $recording->id,
                    'date' => $searchDate
                ]);
            } else {
                Log::info('No existing recording found for manual depletion', [
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

    public function previewDepletion()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errors = $e->validator->errors()->toArray();
            return;
        }

        if (empty($this->selectedBatches)) {
            $this->errors = ['selection' => 'Minimal pilih satu batch untuk depletion.'];
            return;
        }

        try {
            $this->isLoading = true;
            $this->errors = []; // Clear previous errors

            // Find existing recording for the selected date
            $existingRecording = $this->findExistingRecording();

            // Normalize depletion type using config
            $normalizedType = LivestockDepletionConfig::normalize($this->depletionType);

            $depletionData = [
                'livestock_id' => $this->livestockId,
                'type' => $normalizedType,
                'original_type' => $this->depletionType,
                'depletion_method' => 'manual',
                'recording_id' => $existingRecording ? $existingRecording->id : null,
                'manual_batches' => collect($this->selectedBatches)->map(function ($batch) {
                    return [
                        'batch_id' => $batch['batch_id'],
                        'quantity' => (int) $batch['quantity'],
                        'note' => $batch['note'] ?: null
                    ];
                })->toArray(),
                'config_metadata' => [
                    'original_type' => $this->depletionType,
                    'normalized_type' => $normalizedType,
                    'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                    'category' => LivestockDepletionConfig::getCategory($normalizedType),
                    'config_version' => '1.0'
                ]
            ];

            $service = new BatchDepletionService();
            $this->previewData = $service->previewManualBatchDepletion($depletionData);

            $this->canProcess = $this->previewData['can_fulfill'] && $this->previewData['validation_passed'];
            $this->step = 2;

            if (!$this->canProcess) {
                $this->errors = $this->previewData['errors'] ?? ['preview' => 'Cannot fulfill depletion request.'];
            }

            // Add recording information to preview data
            if ($existingRecording) {
                $this->previewData['recording_info'] = [
                    'recording_id' => $existingRecording->id,
                    'recording_date' => $existingRecording->tanggal->format('Y-m-d'),
                    'current_stock' => $existingRecording->final_stock ?? $existingRecording->stock_akhir,
                    'mortality' => $existingRecording->mortality ?? 0,
                    'culling' => $existingRecording->culling ?? 0
                ];
            }

            Log::info('Depletion preview generated', [
                'livestock_id' => $this->livestockId,
                'can_process' => $this->canProcess,
                'total_quantity' => $this->previewData['total_quantity'],
                'recording_id' => $existingRecording ? $existingRecording->id : null,
                'recording_found' => $existingRecording !== null
            ]);
        } catch (Exception $e) {
            Log::error('Error generating preview', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['preview' => 'Error generating preview: ' . $e->getMessage()];
        } finally {
            $this->isLoading = false;
        }
    }

    public function processDepletion()
    {
        if (!$this->canProcess) {
            $this->errors = ['process' => 'Cannot process depletion. Please check preview first.'];
            return;
        }

        try {
            $this->isLoading = true;
            $this->errors = []; // Clear previous errors

            // Find existing recording for the selected date
            $existingRecording = $this->findExistingRecording($this->depletionDate);

            // Normalize depletion type using config
            $normalizedType = LivestockDepletionConfig::normalize($this->depletionType);

            $depletionData = [
                'livestock_id' => $this->livestockId,
                'type' => $normalizedType,
                'original_type' => $this->depletionType,
                'date' => $this->depletionDate,
                'depletion_method' => 'manual',
                'recording_id' => $existingRecording ? $existingRecording->id : null,
                'manual_batches' => collect($this->selectedBatches)->map(function ($batch) {
                    return [
                        'batch_id' => $batch['batch_id'],
                        'quantity' => (int) $batch['quantity'],
                        'note' => $batch['note'] ?: null
                    ];
                })->toArray(),
                'reason' => $this->reason,
                'config_metadata' => [
                    'original_type' => $this->depletionType,
                    'normalized_type' => $normalizedType,
                    'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                    'category' => LivestockDepletionConfig::getCategory($normalizedType),
                    'requires_reason' => LivestockDepletionConfig::requiresField($normalizedType, 'reason'),
                    'config_version' => '1.0'
                ]
            ];

            $service = new BatchDepletionService();
            $result = $service->processDepletion($depletionData);

            if ($result['success']) {
                $this->successMessage = "Manual depletion berhasil diproses. Total depleted: {$result['total_depleted']} dari {$result['livestock_id']}.";
                $this->step = 3;

                Log::info('Manual depletion processed successfully', [
                    'livestock_id' => $this->livestockId,
                    'total_depleted' => $result['total_depleted'],
                    'processed_batches' => count($result['processed_batches']),
                    'recording_id' => $existingRecording ? $existingRecording->id : null,
                    'recording_found' => $existingRecording !== null
                ]);

                // Emit event to refresh parent components
                $this->dispatch('depletion-processed', [
                    'livestock_id' => $this->livestockId,
                    'type' => $this->depletionType,
                    'total_depleted' => $result['total_depleted']
                ]);
            } else {
                $this->errors = ['process' => 'Processing failed: ' . ($result['message'] ?? 'Unknown error')];
            }
        } catch (Exception $e) {
            Log::error('Error processing depletion', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['process' => 'Error processing depletion: ' . $e->getMessage()];
        } finally {
            $this->isLoading = false;
        }
    }

    public function backToSelection()
    {
        $this->step = 1;
        $this->previewData = null;
        $this->canProcess = false;
        $this->errors = []; // Clear errors as array
    }

    public function resetForm()
    {
        $this->reset(['selectedBatches', 'previewData', 'successMessage']);
        $this->errors = []; // Clear errors as array
        $this->step = 1;
        $this->canProcess = false;
        $this->depletionDate = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.master-data.livestock.manual-depletion');
    }
}
