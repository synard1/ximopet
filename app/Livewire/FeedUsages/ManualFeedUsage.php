<?php

namespace App\Livewire\FeedUsages;

use Livewire\Component;
use App\Models\Feed;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Recording;
use App\Services\Feed\ManualFeedUsageService;
use App\Services\Alert\FeedAlertService;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ManualFeedUsage extends Component
{
    // Component properties
    public $showModal = false;
    public $livestock;
    public $livestockId;
    public $feedFilter = null; // Optional filter by specific feed

    // Batch selection
    public $availableBatches = [];
    public $selectedBatch = null;
    public $selectedBatchId = null;

    // Form data
    public $usagePurpose = 'feeding';
    public $usageDate;
    public $notes = '';

    // Stock data
    public $availableFeeds = [];
    public $selectedStocks = [];

    // Preview data
    public $previewData = null;
    public $canProcess = false;

    // UI state
    public $step = 1; // 1: Batch Selection, 2: Stock Selection, 3: Preview, 4: Result
    public $isLoading = false;
    public $errors = [];
    public $successMessage = '';

    // Edit mode properties
    public $isEditMode = false;
    public $existingUsageIds = [];
    public $originalUsageDate = null;

    // Validation rules - will be populated from config
    protected $rules = [];

    protected $messages = [
        'selectedBatchId.required' => 'Batch livestock wajib dipilih.',
        'selectedBatchId.exists' => 'Batch livestock tidak valid.',
        'usagePurpose.required' => 'Tujuan penggunaan pakan wajib dipilih.',
        'usagePurpose.in' => 'Tujuan penggunaan pakan tidak valid.',
        'usageDate.required' => 'Tanggal penggunaan wajib diisi.',
        'usageDate.date' => 'Format tanggal tidak valid.',
        'selectedStocks.*.quantity.required' => 'Quantity stock wajib diisi.',
        'selectedStocks.*.quantity.numeric' => 'Quantity harus berupa angka.',
        'selectedStocks.*.quantity.min' => 'Quantity minimal 0.01.',
    ];

    protected $listeners = [
        'show-manual-feed-usage' => 'handleShowModal'
    ];

    /**
     * Initialize component
     */
    public function mount()
    {
        $this->usageDate = now()->format('Y-m-d');
        $this->errors = [];
        $this->availableFeeds = [];
        $this->selectedStocks = [];
        $this->availableBatches = [];
        $this->selectedBatch = null;
        $this->selectedBatchId = null;
        $this->livestock = null;
        $this->livestockId = null;
        $this->feedFilter = null;
        $this->previewData = null;
        $this->successMessage = '';
        $this->showModal = false;
        $this->step = 1;
        $this->isLoading = false;
        $this->canProcess = false;

        // Initialize validation rules from config
        $this->initializeValidationRules();

        Log::info('ManualFeedUsage component mounted successfully');
    }

    /**
     * Initialize validation rules from company config
     */
    private function initializeValidationRules()
    {
        $validationRules = CompanyConfig::getManualFeedUsageValidationRules();
        $this->rules = [];

        // Batch selection rules
        $this->rules['selectedBatchId'] = 'required|exists:livestock_batches,id';

        // Usage purpose rules
        if ($validationRules['require_usage_purpose'] ?? true) {
            $this->rules['usagePurpose'] = 'required|in:feeding,medication,supplement,treatment,other';
        }

        // Usage date rules
        if ($validationRules['require_usage_date'] ?? true) {
            $this->rules['usageDate'] = 'required|date';
        }

        // Notes rules
        if ($validationRules['require_notes'] ?? false) {
            $this->rules['notes'] = 'required|string|max:500';
        } else {
            $this->rules['notes'] = 'nullable|string|max:500';
        }

        // Stock quantity rules
        if ($validationRules['require_quantity'] ?? true) {
            $minQuantity = $validationRules['min_quantity'] ?? 0.01;
            $maxQuantity = $validationRules['max_quantity'] ?? 10000;

            if ($validationRules['allow_zero_quantity'] ?? false) {
                $this->rules['selectedStocks.*.quantity'] = "required|numeric|min:0|max:{$maxQuantity}";
            } else {
                $this->rules['selectedStocks.*.quantity'] = "required|numeric|min:{$minQuantity}|max:{$maxQuantity}";
            }
        }

        // Stock note rules
        $this->rules['selectedStocks.*.note'] = 'nullable|string|max:255';

        Log::info('🔧 Validation rules initialized from config', [
            'rules_count' => count($this->rules),
            'validation_config' => $validationRules
        ]);
    }

    /**
     * Get workflow settings from config
     */
    private function getWorkflowSettings(): array
    {
        return CompanyConfig::getManualFeedUsageWorkflowSettings();
    }

    /**
     * Get batch selection settings from config
     */
    private function getBatchSelectionSettings(): array
    {
        return CompanyConfig::getManualFeedUsageBatchSelectionSettings();
    }

    /**
     * Get stock selection settings from config
     */
    private function getStockSelectionSettings(): array
    {
        return CompanyConfig::getManualFeedUsageStockSelectionSettings();
    }

    public function handleShowModal($livestockId)
    {
        Log::info('🔥 handleShowModal called', [
            'livestock_id' => $livestockId,
            'livestock_id_type' => gettype($livestockId)
        ]);

        if ($livestockId) {
            $this->openModal($livestockId, null);
        } else {
            Log::warning('🔥 No livestock_id provided in handleShowModal', [
                'livestock_id_received' => $livestockId
            ]);
        }
    }

    public function openModal($livestockId, $feedId = null)
    {
        Log::info('🔥 openModal called', [
            'livestock_id' => $livestockId,
            'feed_id' => $feedId,
            'livestock_id_type' => gettype($livestockId)
        ]);

        try {
            // Validate parameters
            if (empty($livestockId)) {
                throw new Exception('Livestock ID is required');
            }

            $this->reset(['previewData', 'selectedStocks', 'successMessage', 'selectedBatch', 'selectedBatchId', 'availableBatches']);
            $this->errors = [];
            $this->step = 1;
            $this->livestockId = $livestockId;
            $this->feedFilter = $feedId;

            // Re-initialize validation rules after reset
            $this->initializeValidationRules();

            // Always show modal first, then load data
            $this->showModal = true;

            // Load livestock data with validation
            $this->livestock = Livestock::find($livestockId);
            if (!$this->livestock) {
                throw new Exception("Livestock with ID {$livestockId} not found");
            }

            Log::info('🔥 Livestock loaded successfully', [
                'livestock_id' => $this->livestock->id,
                'livestock_name' => $this->livestock->name
            ]);

            // Load available batches for selection
            $this->loadAvailableBatches();

            // Check for existing usage data on current usage date
            $this->checkAndLoadExistingUsageData();

            Log::info('Manual feed usage modal opened successfully', [
                'livestock_id' => $livestockId,
                'livestock_name' => $this->livestock->name,
                'feed_filter' => $feedId,
                'available_batches_count' => count($this->availableBatches),
                'usage_date' => $this->usageDate,
                'is_edit_mode' => $this->isEditMode
            ]);
        } catch (Exception $e) {
            Log::error('🔥 Error opening manual feed usage modal', [
                'livestock_id' => $livestockId,
                'feed_filter' => $feedId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Still show modal but with error message
            $this->showModal = true;
            $this->livestock = null; // Make sure livestock is null on error
            $this->availableBatches = [];
            $this->errors = ['general' => 'Error loading data: ' . $e->getMessage()];
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset();
        $this->errors = [];

        // Dispatch event to close modal via JavaScript
        $this->dispatch('close-manual-feed-usage-modal');

        Log::info('🔥 Manual feed usage modal closed via Livewire');
    }

    /**
     * Close modal without dispatching JavaScript event (called from Bootstrap events)
     */
    public function closeModalSilent()
    {
        $this->showModal = false;
        $this->reset();
        $this->errors = [];

        Log::info('🔥 Manual feed usage modal closed via Bootstrap event');
    }

    private function loadAvailableBatches()
    {
        try {
            $batchSettings = $this->getBatchSelectionSettings();

            // Build query based on config
            $query = LivestockBatch::with(['kandang', 'farm'])
                ->where('livestock_id', $this->livestockId);

            // Hide inactive batches if configured
            if ($batchSettings['hide_inactive_batches'] ?? true) {
                $query->where('status', 'active');
            }

            // Apply minimum batch quantity filter
            $minBatchQuantity = $batchSettings['min_batch_quantity'] ?? 1;
            $query->whereRaw("(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) >= {$minBatchQuantity}");

            // Apply sorting based on config
            $defaultSort = $batchSettings['default_sort'] ?? 'age_asc';
            switch ($defaultSort) {
                case 'age_desc':
                    $query->orderBy('start_date', 'asc'); // Older batches first
                    break;
                case 'quantity_desc':
                    $query->orderByRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) DESC');
                    break;
                case 'quantity_asc':
                    $query->orderByRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) ASC');
                    break;
                default: // age_asc
                    $query->orderBy('start_date', 'desc'); // Newer batches first
                    break;
            }

            $batches = $query->get();

            $this->availableBatches = $batches->map(function ($batch) use ($batchSettings) {
                $currentQuantity = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
                $ageDays = $batch->start_date->diffInDays(now());

                $batchData = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'livestockStrain' => $batch->livestock_strain_name,
                    'current_quantity' => $currentQuantity,
                    'initial_quantity' => $batch->initial_quantity,
                    'age_days' => $ageDays,
                    'start_date' => $batch->start_date->format('Y-m-d'),
                ];

                // Add optional fields based on config
                if ($batchSettings['show_coop_information'] ?? true) {
                    $batchData['coop_name'] = $batch->kandang->name ?? 'No Coop';
                }

                if ($batchSettings['show_strain_information'] ?? true) {
                    $batchData['farm_name'] = $batch->farm->name ?? 'No Farm';
                }

                return $batchData;
            })->toArray();

            Log::info('Available batches loaded with config', [
                'livestock_id' => $this->livestockId,
                'batch_count' => count($this->availableBatches),
                'config_applied' => $batchSettings
            ]);
        } catch (Exception $e) {
            Log::error('Error loading available batches', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['batches' => 'Error loading batch data: ' . $e->getMessage()];
        }
    }

    public function selectBatch($batchId)
    {
        try {
            $this->selectedBatchId = $batchId;
            $this->selectedBatch = collect($this->availableBatches)->firstWhere('batch_id', $batchId);

            if (!$this->selectedBatch) {
                throw new Exception('Batch not found');
            }

            // Reset stocks and move to next step
            $this->selectedStocks = [];
            $this->availableFeeds = [];

            // Load available feed stocks for the selected batch
            $this->loadAvailableFeedStocks();

            // Move to stock selection step
            $this->step = 2;

            Log::info('Batch selected', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $batchId,
                'batch_name' => $this->selectedBatch['batch_name']
            ]);
        } catch (Exception $e) {
            Log::error('Error selecting batch', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['batch_selection' => 'Error selecting batch: ' . $e->getMessage()];
        }
    }

    public function backToBatchSelection()
    {
        $this->step = 1;
        $this->selectedBatch = null;
        $this->selectedBatchId = null;
        $this->selectedStocks = [];
        $this->availableFeeds = [];
        $this->previewData = null;
        $this->canProcess = false;
        $this->errors = [];
    }

    private function loadAvailableFeedStocks()
    {
        try {
            $feedAlertService = new FeedAlertService();
            $service = new ManualFeedUsageService($feedAlertService);
            $stockData = $service->getAvailableFeedStocksForManualSelection($this->livestockId, $this->feedFilter);

            $this->availableFeeds = $stockData['feeds'];

            Log::info('Available feed stocks loaded', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->selectedBatchId,
                'feed_types_count' => count($this->availableFeeds),
                'total_stocks' => $stockData['total_stocks']
            ]);
        } catch (Exception $e) {
            Log::error('Error loading available feed stocks', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->selectedBatchId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['stocks' => 'Error loading stock data: ' . $e->getMessage()];
        }
    }

    public function addStock($stockId)
    {
        try {
            $restrictions = CompanyConfig::getManualFeedUsageInputRestrictions();

            // Check if stock already selected (prevent duplicates if configured)
            if ($restrictions['prevent_duplicate_stocks'] ?? true) {
                if (collect($this->selectedStocks)->contains('stock_id', $stockId)) {
                    $this->errors = ['stock_selection' => 'This stock is already selected.'];
                    return;
                }
            }

            // Check maximum entries per session
            $maxEntriesPerSession = $restrictions['max_entries_per_session'] ?? null;
            if ($maxEntriesPerSession && count($this->selectedStocks) >= $maxEntriesPerSession) {
                $this->errors = ['stock_selection' => "Maximum {$maxEntriesPerSession} stock entries allowed per session."];
                return;
            }

            // Find stock data from available feeds
            $stock = null;
            foreach ($this->availableFeeds as $feed) {
                $foundStock = collect($feed['stocks'])->firstWhere('stock_id', $stockId);
                if ($foundStock) {
                    $stock = $foundStock;
                    $stock['feed_name'] = $feed['feed_name'];
                    $stock['feed_id'] = $feed['feed_id'];
                    break;
                }
            }

            if (!$stock) {
                $this->errors = ['stock_selection' => 'Selected stock not found.'];
                return;
            }

            // Check stock age restrictions
            $maxStockAge = $restrictions['max_stock_age_days'] ?? null;
            $warnOnOldStock = $restrictions['warn_on_old_stock'] ?? false;
            $oldStockThreshold = $restrictions['old_stock_threshold_days'] ?? 90;

            if ($maxStockAge && $stock['age_days'] > $maxStockAge) {
                $this->errors = ['stock_selection' => "Stock {$stock['feed_name']} is too old ({$stock['age_days']} days). Maximum allowed age is {$maxStockAge} days."];
                return;
            }

            if ($warnOnOldStock && $stock['age_days'] > $oldStockThreshold) {
                $this->errors = ['stock_warning' => "Warning: Stock {$stock['feed_name']} is {$stock['age_days']} days old. Consider using fresher stock."];
            }

            // Get default quantity from validation rules
            $validationRules = CompanyConfig::getManualFeedUsageValidationRules();
            $defaultQuantity = max($validationRules['min_quantity'] ?? 0.1, 1);

            $this->selectedStocks[] = [
                'stock_id' => $stock['stock_id'],
                'feed_id' => $stock['feed_id'],
                'feed_name' => $stock['feed_name'],
                'stock_name' => $stock['stock_name'],
                'available_quantity' => $stock['available_quantity'],
                'unit' => $stock['unit'],
                'cost_per_unit' => $stock['cost_per_unit'],
                'age_days' => $stock['age_days'],
                'batch_info' => $stock['batch_info'] ?? null,
                'quantity' => $defaultQuantity,
                'note' => ''
            ];

            // Clear any previous stock selection errors
            if (isset($this->errors['stock_selection'])) {
                unset($this->errors['stock_selection']);
            }

            Log::info('Stock added successfully', [
                'stock_id' => $stockId,
                'feed_name' => $stock['feed_name'],
                'selected_stocks_count' => count($this->selectedStocks)
            ]);
        } catch (Exception $e) {
            Log::error('Error adding stock', [
                'stock_id' => $stockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['stock_selection' => 'Error adding stock: ' . $e->getMessage()];
        }
    }

    public function removeStock($index)
    {
        unset($this->selectedStocks[$index]);
        $this->selectedStocks = array_values($this->selectedStocks);
    }

    /**
     * Validate feed usage input restrictions based on company config
     */
    private function validateFeedUsageInputRestrictions()
    {
        $feedAlertService = new FeedAlertService();
        $service = new ManualFeedUsageService($feedAlertService);
        $validation = $service->validateFeedUsageInputRestrictions(
            $this->livestockId,
            $this->selectedStocks,
            $this->isEditMode
        );

        if (!$validation['valid']) {
            $this->errors = array_merge($this->errors, ['restrictions' => $validation['errors']]);
            return false;
        }

        return true;
    }

    /**
     * Handle usage date change - check for existing data
     */
    public function updatedUsageDate($value)
    {
        if (!$this->livestockId || !$value) {
            return;
        }

        $this->checkAndLoadExistingUsageData();
    }

    /**
     * Find existing recording for the selected date and livestock
     */
    private function findExistingRecording(): ?Recording
    {
        if (!$this->livestockId || !$this->usageDate) {
            return null;
        }

        try {
            $recording = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('date', $this->usageDate)
                ->first();

            if ($recording) {
                Log::info('📊 Found existing recording for manual feed usage', [
                    'recording_id' => $recording->id,
                    'livestock_id' => $this->livestockId,
                    'date' => $this->usageDate,
                    'recording_date' => $recording->date->format('Y-m-d'),
                ]);
            }

            return $recording;
        } catch (Exception $e) {
            Log::error('Error finding existing recording', [
                'livestock_id' => $this->livestockId,
                'usage_date' => $this->usageDate,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check and load existing usage data for current usage date
     */
    private function checkAndLoadExistingUsageData()
    {
        if (!$this->livestockId || !$this->usageDate) {
            return;
        }

        try {
            $feedAlertService = new FeedAlertService();
            $service = new ManualFeedUsageService($feedAlertService);

            // Check if usage exists on this date
            if ($service->hasUsageOnDate($this->livestockId, $this->usageDate)) {
                // Load existing data - pass selectedBatchId for batch-specific filtering
                $existingData = $service->getExistingUsageData(
                    $this->livestockId,
                    $this->usageDate,
                    $this->selectedBatchId
                );

                if ($existingData) {
                    $this->loadExistingUsageData($existingData);

                    // Show notification about edit mode
                    $this->dispatch('usage-edit-mode-enabled', [
                        'message' => 'Existing feed usage data loaded for editing',
                        'date' => $this->usageDate,
                        'total_stocks' => count($existingData['selected_stocks'])
                    ]);

                    Log::info('🔄 Existing usage data loaded automatically', [
                        'livestock_id' => $this->livestockId,
                        'usage_date' => $this->usageDate,
                        'stocks_count' => count($existingData['selected_stocks']),
                        'trigger' => 'auto-load'
                    ]);
                }
            } else {
                // Reset edit mode if no existing data
                $this->resetEditMode();
            }
        } catch (Exception $e) {
            Log::error('Error checking existing usage data', [
                'livestock_id' => $this->livestockId,
                'usage_date' => $this->usageDate,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['date_check' => 'Error checking existing data: ' . $e->getMessage()];
        }
    }

    /**
     * Load existing usage data into component
     */
    private function loadExistingUsageData(array $existingData)
    {
        try {
            // Set edit mode
            $this->isEditMode = true;
            $this->existingUsageIds = $existingData['existing_usage_ids'];
            $this->originalUsageDate = $existingData['usage_date'];

            // Load basic usage data
            $this->usagePurpose = $existingData['usage_purpose'];
            $this->notes = $existingData['notes'];

            // Load batch data if available
            if ($existingData['livestock_batch_id']) {
                $this->selectedBatchId = $existingData['livestock_batch_id'];

                // Load available batches first to get complete batch data
                $this->loadAvailableBatches();

                // Find the complete batch data from available batches
                $this->selectedBatch = collect($this->availableBatches)->firstWhere('batch_id', $existingData['livestock_batch_id']);

                // Fallback to minimal data if batch not found in available batches
                if (!$this->selectedBatch) {
                    $this->selectedBatch = [
                        'batch_id' => $existingData['livestock_batch_id'],
                        'batch_name' => $existingData['livestock_batch_name'] ?? 'Unknown Batch',
                        'livestockStrain' => 'Unknown Strain',
                        'current_quantity' => 0,
                        'age_days' => 0,
                    ];

                    Log::warning('Batch not found in available batches, using fallback data', [
                        'batch_id' => $existingData['livestock_batch_id'],
                        'batch_name' => $existingData['livestock_batch_name']
                    ]);
                }

                // Load available feed stocks for the selected batch
                $this->loadAvailableFeedStocks();
            }

            // Load selected stocks
            $this->selectedStocks = $existingData['selected_stocks'];

            // Move to stock selection step if batch is selected
            if ($this->selectedBatchId) {
                $this->step = 2;
            }

            Log::info('🔄 Loaded existing usage data for editing', [
                'livestock_id' => $this->livestockId,
                'usage_date' => $existingData['usage_date'],
                'stocks_count' => count($this->selectedStocks),
                'total_quantity' => $existingData['total_quantity'],
                'total_cost' => $existingData['total_cost']
            ]);
        } catch (Exception $e) {
            Log::error('Error loading existing usage data', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);

            $this->errors = ['data_load' => 'Error loading existing data: ' . $e->getMessage()];
            $this->resetEditMode();
        }
    }

    /**
     * Reset edit mode
     */
    private function resetEditMode()
    {
        $this->isEditMode = false;
        $this->existingUsageIds = [];
        $this->originalUsageDate = null;

        // Clear selected stocks if switching from edit mode
        if (!empty($this->selectedStocks)) {
            $this->selectedStocks = [];
        }
    }

    /**
     * Cancel edit mode and reset to new entry
     */
    public function cancelEditMode()
    {
        $this->resetEditMode();

        // Reset form to initial state
        $this->selectedStocks = [];
        $this->usagePurpose = 'feeding';
        $this->notes = '';

        // Reset to batch selection if needed
        if ($this->step > 1) {
            $this->step = 1;
        }

        $this->dispatch('usage-edit-mode-cancelled');

        Log::info('🚫 Edit mode cancelled', [
            'livestock_id' => $this->livestockId,
            'usage_date' => $this->usageDate
        ]);
    }

    public function previewUsage()
    {
        try {
            Log::info('🔥 previewUsage called', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->selectedBatchId,
                'usage_date' => $this->usageDate,
                'usage_purpose' => $this->usagePurpose,
                'selected_stocks_count' => count($this->selectedStocks),
                'step' => $this->step,
                'rules_count' => count($this->rules)
            ]);

            // Ensure validation rules are initialized
            if (empty($this->rules)) {
                Log::warning('🔥 Validation rules empty, re-initializing');
                $this->initializeValidationRules();
            }

            // Basic validation checks before Livewire validation
            if (empty($this->selectedStocks)) {
                $this->errors = ['stocks' => 'Please select at least one stock to use.'];
                Log::warning('🔥 No stocks selected for preview');
                return;
            }

            if (!$this->selectedBatchId) {
                $this->errors = ['batch' => 'Please select a batch first.'];
                Log::warning('🔥 No batch selected for preview');
                return;
            }

            if (!$this->usageDate) {
                $this->errors = ['usage_date' => 'Usage date is required.'];
                Log::warning('🔥 No usage date provided');
                return;
            }

            if (!$this->usagePurpose) {
                $this->errors = ['usage_purpose' => 'Usage purpose is required.'];
                Log::warning('🔥 No usage purpose provided');
                return;
            }

            // Validate selected stocks have quantities with enhanced validation
            $stockErrors = $this->validateSelectedStockQuantities();
            if (!empty($stockErrors)) {
                $this->errors = ['stocks' => $stockErrors];
                Log::warning('🔥 Stock quantity validation failed', ['errors' => $stockErrors]);
                return;
            }

            // Try Livewire validation if rules are available
            if (!empty($this->rules)) {
                try {
                    $this->validate();
                    Log::info('🔥 Livewire validation passed successfully');
                } catch (\Illuminate\Validation\ValidationException $e) {
                    Log::error('🔥 Livewire validation failed', [
                        'errors' => $e->errors(),
                        'message' => $e->getMessage()
                    ]);
                    $this->errors = $e->errors();
                    return;
                }
            } else {
                Log::warning('🔥 No validation rules available, skipping Livewire validation');
            }

            // Validate company restrictions
            if (!$this->validateFeedUsageInputRestrictions()) {
                Log::warning('🔥 Feed usage input restrictions validation failed');
                return;
            }

            $this->isLoading = true;

            // Check for existing recording
            $existingRecording = $this->findExistingRecording();

            $usageData = [
                'livestock_id' => $this->livestockId,
                'livestock_batch_id' => $this->selectedBatchId,
                'feed_id' => $this->feedFilter,
                'usage_date' => $this->usageDate,
                'usage_purpose' => $this->usagePurpose,
                'notes' => $this->notes,
                'manual_stocks' => $this->selectedStocks,
                'recording_id' => $existingRecording?->id
            ];

            $feedAlertService = new FeedAlertService();
            $service = new ManualFeedUsageService($feedAlertService);
            $this->previewData = $service->previewManualFeedUsage($usageData);

            $this->canProcess = $this->previewData['can_fulfill'];

            if (!$this->canProcess) {
                $this->errors = ['preview' => $this->previewData['issues']];
            } else {
                $this->step = 3;
                $this->errors = [];
            }

            Log::info('Feed usage preview generated', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->selectedBatchId,
                'can_fulfill' => $this->canProcess,
                'total_quantity' => $this->previewData['total_quantity'],
                'total_cost' => $this->previewData['total_cost']
            ]);
        } catch (Exception $e) {
            Log::error('Error generating feed usage preview', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->selectedBatchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->errors = ['preview' => 'Error generating preview: ' . $e->getMessage()];
        } finally {
            $this->isLoading = false;
        }
    }

    public function processUsage()
    {
        try {
            if (!$this->canProcess || !$this->previewData) {
                $this->errors = ['process' => 'Please generate preview first.'];
                return;
            }

            if (!$this->selectedBatchId) {
                $this->errors = ['batch' => 'Please select a batch first.'];
                return;
            }

            $this->isLoading = true;

            // Check for existing recording
            $existingRecording = $this->findExistingRecording();

            $usageData = [
                'livestock_id' => $this->livestockId,
                'livestock_batch_id' => $this->selectedBatchId,
                'feed_id' => $this->feedFilter,
                'usage_date' => $this->usageDate,
                'usage_purpose' => $this->usagePurpose,
                'notes' => $this->notes,
                'manual_stocks' => $this->selectedStocks,
                'recording_id' => $existingRecording?->id
            ];

            // Add edit mode data if applicable
            if ($this->isEditMode) {
                $usageData['is_edit_mode'] = true;
                $usageData['existing_usage_ids'] = $this->existingUsageIds;
            }

            $feedAlertService = new FeedAlertService();
            $service = new ManualFeedUsageService($feedAlertService);

            if ($this->isEditMode) {
                $result = $service->updateExistingFeedUsage($usageData);
                $this->successMessage = 'Feed usage updated successfully!';
            } else {
                $result = $service->processManualFeedUsage($usageData);
                $this->successMessage = 'Feed usage processed successfully!';
            }

            if ($result['success']) {
                $this->step = 4;
                $unit = $this->selectedStocks[0]['unit'] ?? 'kg';

                // Set appropriate success message
                if ($this->isEditMode) {
                    $this->successMessage = "Feed usage updated successfully! Batch: {$this->selectedBatch['batch_name']}, Total quantity: {$result['total_quantity']} {$unit}, Total cost: " . number_format($result['total_cost'], 2);
                } else {
                    $this->successMessage = "Feed usage processed successfully! Batch: {$this->selectedBatch['batch_name']}, Total quantity: {$result['total_quantity']} {$unit}, Total cost: " . number_format($result['total_cost'], 2);
                }

                // Reset edit mode after successful processing
                $wasEditMode = $this->isEditMode;
                if ($this->isEditMode) {
                    $this->resetEditMode();
                }

                // Emit event for parent components
                $this->dispatch('feed-usage-completed', [
                    'livestock_id' => $this->livestockId,
                    'livestock_batch_id' => $this->selectedBatchId,
                    'feed_usage_id' => $result['feed_usage_id'],
                    'total_quantity' => $result['total_quantity'],
                    'total_cost' => $result['total_cost'],
                    'is_update' => $wasEditMode
                ]);

                Log::info('Feed usage processed successfully', [
                    'livestock_id' => $this->livestockId,
                    'batch_id' => $this->selectedBatchId,
                    'feed_usage_id' => $result['feed_usage_id'],
                    'total_quantity' => $result['total_quantity'],
                    'total_cost' => $result['total_cost'],
                    'was_edit_mode' => $wasEditMode
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error processing feed usage', [
                'livestock_id' => $this->livestockId,
                'batch_id' => $this->selectedBatchId,
                'is_edit_mode' => $this->isEditMode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Provide more specific error messages for different types of errors
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'Non-numeric value')) {
                $errorMessage = 'Invalid quantity values detected. Please check all quantity inputs are valid numbers.';
            } elseif (str_contains($errorMessage, 'Insufficient stock')) {
                $errorMessage = 'Insufficient stock available. Please adjust quantities or refresh the data.';
            } elseif (str_contains($errorMessage, 'decrement method')) {
                $errorMessage = 'Error updating stock quantities. Please refresh and try again.';
            }

            $this->errors = ['process' => 'Error processing usage: ' . $errorMessage];
        } finally {
            $this->isLoading = false;
        }
    }

    public function backToSelection()
    {
        $this->step = 2;
        $this->previewData = null;
        $this->canProcess = false;
        $this->errors = [];
    }

    public function resetForm()
    {
        $this->step = 1;
        $this->selectedStocks = [];
        $this->selectedBatch = null;
        $this->selectedBatchId = null;
        $this->previewData = null;
        $this->canProcess = false;
        $this->errors = [];
        $this->successMessage = '';
    }

    public function render()
    {
        return view('livewire.feed-usages.manual-feed-usage');
    }

    /**
     * Enhanced validation for selected stock quantities
     */
    private function validateSelectedStockQuantities(): array
    {
        $errors = [];

        foreach ($this->selectedStocks as $index => $stock) {
            $stockLabel = "Stock " . ($index + 1) . " ({$stock['feed_name']})";

            // Check if quantity is set
            if (!isset($stock['quantity'])) {
                $errors[] = "{$stockLabel} - Quantity is required.";
                continue;
            }

            // Convert quantity to string and trim whitespace
            $quantity = is_string($stock['quantity']) ? trim($stock['quantity']) : strval($stock['quantity']);

            // Check if quantity is numeric
            if (!is_numeric($quantity)) {
                $errors[] = "{$stockLabel} - Quantity must be a valid number.";
                continue;
            }

            // Convert to float for further validation
            $numericQuantity = floatval($quantity);

            // Check if quantity is greater than 0
            if ($numericQuantity <= 0) {
                $errors[] = "{$stockLabel} - Quantity must be greater than 0.";
                continue;
            }

            // Check if quantity exceeds available stock
            $availableQuantity = floatval($stock['available_quantity']);
            if ($numericQuantity > $availableQuantity) {
                $errors[] = "{$stockLabel} - Requested {$numericQuantity} {$stock['unit']} exceeds available {$availableQuantity} {$stock['unit']}.";
                continue;
            }

            // Update the quantity to ensure it's properly formatted as float
            $this->selectedStocks[$index]['quantity'] = $numericQuantity;
        }

        return $errors;
    }
}
