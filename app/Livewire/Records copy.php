<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Exception;

// Core Models
use App\Models\Recording;
use App\Models\CurrentStock;
use App\Models\CurrentLivestock;
use App\Models\Feed;
use App\Models\LivestockDepletion;
use App\Models\LivestockSales;
use App\Models\TransaksiBeliDetail;
use App\Models\StockHistory;
use App\Models\Ternak;
use App\Models\TernakJual;
use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;

// Feed Models
use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\LivestockSalesItem;
use App\Models\CurrentSupply;

// Supply/OVK Models
use App\Models\Supply;
use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Unit;
use App\Models\Company;

// Services
use App\Services\StocksService;
use App\Services\FIFOService;
use App\Services\TernakService;
use App\Services\Livestock\LivestockCostService;
use App\Services\Livestock\FIFODepletionService;

// Modular Payload System
use App\Services\Recording\ModularPayloadBuilder;
use App\Events\RecordingSaving;

class Records extends Component
{
    // Core Properties
    public $recordings = [];
    public $livestockId;
    public $date;
    public $age;
    public $stock_start;
    public $stock_end;
    public $weight_yesterday;
    public $weight_today;
    public $weight_gain;
    public $items = [];
    public $itemQuantities = [];
    public $currentLivestockStock = null;
    public $mortality, $culling, $total_deplesi;
    public $recordingData = null;
    public $deplesiData = null;
    public $hasChanged = false;

    // Yesterday's data for better information
    public $yesterday_weight;
    public $yesterday_mortality;
    public $yesterday_culling;
    public $yesterday_feed_usage;
    public $yesterday_supply_usage;
    public $yesterday_stock_end;
    public $yesterday_data = null;

    public $initial_stock;
    public $final_stock;
    public $weight;
    public $sales_quantity;
    public $sales_weight;
    public $sales_price;
    public $total_sales;

    public $feedUsageId, $usages;

    // OVK/Supply properties
    public $supplyQuantities = [];
    public $availableSupplies = [];
    public $supplyUsageId = null;
    public $supplyUsages = [];
    public $hasSupplyChanged = false;

    public $isEditing = false;
    public $showForm = false;
    public $recordingMethod;

    // Configuration properties
    public $livestockConfig = [];
    public $isManualDepletionEnabled = false;
    public $isManualFeedUsageEnabled = false;

    protected $listeners = [
        'setRecords' => 'setRecords'
    ];

    protected $rules = [
        'date' => 'required|date',
        'mortality' => 'nullable|integer|min:0',
        'culling' => 'nullable|integer|min:0',
        'sales_quantity' => 'nullable|integer|min:0',
        'sales_price' => 'nullable|numeric|min:0',
        'total_sales' => 'nullable|numeric|min:0',
    ];

    protected $messages = [
        'recordingMethod.required' => 'Recording method must be selected.',
        'recordingMethod.in' => 'Invalid recording method selected.',
    ];

    // Services
    protected ?StocksService $stocksService = null;
    protected ?FIFOService $fifoService = null;
    protected ?FIFODepletionService $fifoDepletionService = null;

    public function mount(StocksService $stocksService, FIFOService $fifoService, FIFODepletionService $fifoDepletionService)
    {
        $this->stocksService = $stocksService;
        $this->fifoService = $fifoService;
        $this->fifoDepletionService = $fifoDepletionService;
        $this->initializeItemQuantities();
        $this->initializeSupplyItems();
        $this->loadRecordingData();
    }

    public function setRecords($livestockId)
    {
        $this->resetErrorBag();
        $this->livestockId = $livestockId;

        if ($this->livestockId) {
            $livestock = Livestock::findOrFail($this->livestockId);
            $company = $livestock->farm->company;

            // Load livestock configuration
            $this->loadLivestockConfiguration($livestock);

            // Auto-save config for single batch if not set in data column
            if ($livestock->getActiveBatchesCount() <= 1 && !$livestock->getDataColumn('config')) {
                $user = auth()->user();
                $recordingConfig = [
                    'recording_method' => 'total',
                    'depletion_method' => 'fifo',
                    'mutation_method' => 'fifo',
                    'feed_usage_method' => 'total',
                    'saved_at' => now()->toDateTimeString(),
                    'saved_by' => $user ? $user->id : null
                ];
                $livestock->updateDataColumn('config', $recordingConfig);

                // Reload configuration after auto-save
                $this->loadLivestockConfiguration($livestock);
            }

            // Validate configuration for multi-batch livestock
            if ($livestock->getActiveBatchesCount() > 1 && !\App\Models\Recording::where('livestock_id', $livestock->id)->exists()) {
                $config = $livestock->getDataColumn('config');
                if (empty($config) || !is_array($config) || empty($config['recording_method'])) {
                    $this->dispatch('error', 'Ternak ini memiliki lebih dari 1 batch aktif. Silakan atur metode pencatatan terlebih dahulu di menu setting pada data ini.');
                    Log::info('[Records] setRecords: Gagal lanjut, config belum diatur untuk livestock_id: ' . $livestock->id, [
                        'config' => $config,
                        'livestock_id' => $livestock->id,
                        'user_id' => auth()->id(),
                        'timestamp' => now()->toDateTimeString(),
                    ]);
                    return;
                }
                Log::info('[Records] setRecords: Config ditemukan, proses dilanjutkan untuk livestock_id: ' . $livestock->id, [
                    'config' => $config,
                    'livestock_id' => $livestock->id,
                    'user_id' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }

            // Load all necessary data
            $this->loadAllRecordingData();
            $this->showForm = true;
            $this->dispatch('show-records');
        }
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->dispatch('hide-records');
        $this->resetErrorBag();
    }

    /**
     * Load livestock configuration and set visibility flags
     */
    private function loadLivestockConfiguration(Livestock $livestock): void
    {
        $this->livestockConfig = $livestock->getConfiguration();
        $this->isManualDepletionEnabled = $livestock->isManualDepletionEnabled();
        $this->isManualFeedUsageEnabled = $livestock->isManualFeedUsageEnabled();

        Log::info('Records - Livestock Configuration Loaded', [
            'livestock_id' => $livestock->id,
            'config' => $this->livestockConfig,
            'manual_depletion_enabled' => $this->isManualDepletionEnabled,
            'manual_feed_usage_enabled' => $this->isManualFeedUsageEnabled,
        ]);
    }

    /**
     * Load all necessary data for recording
     */
    private function loadAllRecordingData(): void
    {
        $this->loadStockData();
        $this->initializeItemQuantities();
        $this->loadAvailableSupplies();
        $this->initializeSupplyItems();
        $this->checkCurrentLivestockStock();
        $this->loadRecordingData();
    }

    /**
     * Main save method with modular payload system
     */
    public function save()
    {
        Log::info('🚀 Records Save: Starting with modular payload system', [
            'livestock_id' => $this->livestockId,
            'date' => $this->date,
            'mortality' => $this->mortality,
            'culling' => $this->culling,
        ]);

        try {
            $this->validate();
            Log::info('✅ Records Save: Validation passed');
        } catch (ValidationException $e) {
            Log::error('❌ Records Save: Validation failed', [
                'errors' => $e->validator->errors()->all()
            ]);
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
            return;
        }

        $validatedData = $this->all();

        try {
            Log::info('🔄 Records Save: Starting database transaction');
            DB::beginTransaction();

            // --- Initialize Modular Payload Builder ---
            Log::info('📦 Records Save: Initializing modular payload builder');
            $payloadBuilder = ModularPayloadBuilder::create();

            // --- Validate livestock and prepare core data ---
            $livestock = $this->validateAndPrepareLivestock();
            $recordingInput = $this->prepareRecordingInput($livestock);

            // --- Set core data in payload builder ---
            $payloadBuilder->setCoreData([
                'mortality' => (int)($this->mortality ?? 0),
                'culling' => (int)($this->culling ?? 0),
                'sales_quantity' => (int)($this->sales_quantity ?? 0),
                'sales_price' => (float)($this->sales_price ?? 0),
                'sales_weight' => (float)($this->sales_weight ?? 0),
                'total_sales' => (float)($this->total_sales ?? 0),
                'weight_today' => (float)($this->weight_today ?? 0),
                'weight_yesterday' => (float)($this->weight_yesterday ?? 0),
                'weight_gain' => (float)($this->weight_gain ?? 0),
            ]);

            // --- Set livestock context ---
            $payloadBuilder->setLivestockContext($livestock, $recordingInput['age']);

            // --- Prepare calculated metrics ---
            $performanceMetrics = $this->calculatePerformanceMetrics($livestock, $recordingInput);
            $payloadBuilder->setCalculatedMetrics([
                'performance' => $performanceMetrics,
            ]);

            // --- Prepare historical data ---
            $historicalData = $this->prepareHistoricalData($livestock, $this->date);
            $payloadBuilder->setHistoricalData($historicalData);

            // --- Set environment data (placeholder for future expansion) ---
            $payloadBuilder->setEnvironmentData([
                'temperature' => null,
                'humidity' => null,
                'lighting' => null,
            ]);

            // --- Dispatch event for external components to add their data ---
            Log::info('📡 Records Save: Dispatching RecordingSaving event');
            Event::dispatch(new RecordingSaving(
                $this->livestockId,
                $this->date,
                $payloadBuilder,
                [
                    'recording_id' => null, // Will be set after recording is saved
                    'is_editing' => $this->isEditing,
                    'user_id' => auth()->id(),
                    'validated_data' => $validatedData,
                ]
            ));

            // --- Build the modular payload ---
            Log::info('🏗️ Records Save: Building modular payload');
            $modularPayload = $payloadBuilder->build();

            // Check for validation errors in payload building
            if ($payloadBuilder->hasValidationErrors()) {
                $errors = $payloadBuilder->getValidationErrors();
                Log::error('❌ Records Save: Payload validation errors', ['errors' => $errors]);
                throw new \Exception('Payload validation failed: ' . implode(', ', $errors));
            }

            // --- Update recording input with modular payload ---
            $recordingInput['payload'] = $modularPayload;

            // --- Save the recording ---
            Log::info('💾 Records Save: Saving recording with modular payload');
            $recording = $this->saveOrUpdateRecording($recordingInput);

            // --- Update event context with recording ID ---
            Event::dispatch(new RecordingSaving(
                $this->livestockId,
                $this->date,
                $payloadBuilder,
                [
                    'recording_id' => $recording->id,
                    'is_editing' => $this->isEditing,
                    'user_id' => auth()->id(),
                    'validated_data' => $validatedData,
                ]
            ));

            // --- Process feed usage ---
            if (!empty($this->usages) || $this->hasChanged === true) {
                Log::info('🍽️ Records Save: Processing feed usage');
                $this->processFeedUsage($validatedData, $recording->id);
            }

            // --- Process supply usage ---
            if (!empty($this->supplyUsages) || $this->hasSupplyChanged === true) {
                Log::info('🧪 Records Save: Processing supply usage');
                $this->processSupplyUsage($validatedData, $recording->id);
            }

            // --- Process depletion data ---
            Log::info('💀 Records Save: Processing depletion data');
            $this->processDepletionData($recording->id);

            // --- Update current livestock quantity ---
            Log::info('📊 Records Save: Updating current livestock quantity');
            $this->updateCurrentLivestockQuantityWithHistory();

            // --- Calculate and save cost data ---
            Log::info('💰 Records Save: Calculating costs');
            $costService = app(LivestockCostService::class);
            $livestockCost = $costService->calculateForDate($this->livestockId, $this->date);

            Log::info('💾 Records Save: Committing database transaction');
            DB::commit();

            // --- Reset form and reload data ---
            Log::info('🔄 Records Save: Resetting form and reloading data');
            $this->resetFormAfterSave();

            Log::info('🎉 Records Save: Process completed successfully with modular payload', [
                'recording_id' => $recording->id,
                'payload_version' => $modularPayload['version'] ?? 'unknown',
                'components_count' => count($modularPayload['component_data'] ?? []),
                'component_names' => array_keys($modularPayload['component_data'] ?? []),
            ]);

            $this->dispatch('success', 'Data berhasil disimpan dengan sistem payload modular');
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();

            $class = __CLASS__;
            $method = __FUNCTION__;
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());

            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ' . $message);
        }
    }

    /**
     * Validate livestock and prepare basic data
     */
    private function validateAndPrepareLivestock(): Livestock
    {
        $livestock = Livestock::with(['farm', 'kandang'])->find($this->livestockId);
        if (!$livestock) {
            throw new \Exception("Livestock record not found");
        }

        $livestockStartDate = Carbon::parse($livestock->start_date);
        $recordDate = Carbon::parse($this->date);

        if ($recordDate->lt($livestockStartDate)) {
            throw new \Exception("Recording date cannot be earlier than livestock start date ({$livestockStartDate->format('Y-m-d')})");
        }

        if ($recordDate->gt(Carbon::now()->addDays(1))) {
            throw new \Exception("Recording date cannot be in the future");
        }

        return $livestock;
    }

    /**
     * Prepare recording input data
     */
    private function prepareRecordingInput(Livestock $livestock): array
    {
        $livestockStartDate = Carbon::parse($livestock->start_date);
        $recordDate = Carbon::parse($this->date);
        $age = $livestockStartDate->diffInDays($recordDate);

        // Calculate stock values
        $previousDate = $recordDate->copy()->subDay()->format('Y-m-d');
        $previousRecording = Recording::where('livestock_id', $this->livestockId)
            ->whereDate('tanggal', $previousDate)
            ->first();

        $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $livestock->initial_quantity;
        $totalDeplesiHariIni = (int)($this->mortality ?? 0) + (int)($this->culling ?? 0) + (int)($this->sales_quantity ?? 0);
        $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

        // Weight calculations
        $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0;
        $weightToday = $this->weight_today ?? 0;
        $weightGain = $weightToday - $weightYesterday;

        return [
            'livestock_id' => $this->livestockId,
            'tanggal' => $this->date,
            'age' => $age,
            'stock_awal' => $stockAwalHariIni,
            'stock_akhir' => $stockAkhirHariIni,
            'berat_hari_ini' => $weightToday,
            'berat_semalam' => $weightYesterday,
            'kenaikan_berat' => $weightGain,
            'pakan_jenis' => implode(', ', array_column($this->usages, 'feed_name')),
            'pakan_harian' => array_sum(array_column($this->usages, 'quantity')),
            'feed_id' => implode(', ', array_column($this->usages, 'feed_id')),
        ];
    }

    /**
     * Calculate performance metrics
     */
    private function calculatePerformanceMetrics(Livestock $livestock, array $recordingInput): array
    {
        $age = $recordingInput['age'];
        $currentPopulation = $recordingInput['stock_akhir'];
        $initialPopulation = $livestock->initial_quantity;
        $currentWeight = $recordingInput['berat_hari_ini'];

        // Get total feed consumption
        $totalFeedConsumption = array_sum(array_column($this->usages, 'quantity'));

        // Calculate liveability
        $liveability = $initialPopulation > 0 ? ($currentPopulation / $initialPopulation) * 100 : 0;

        // Calculate mortality rate
        $mortalityRate = $initialPopulation > 0 ? (($initialPopulation - $currentPopulation) / $initialPopulation) * 100 : 0;

        // Calculate FCR
        $fcr = 0;
        if ($currentWeight > 0 && $currentPopulation > 0) {
            $totalWeight = $currentWeight * $currentPopulation;
            $fcr = $totalFeedConsumption > 0 ? $totalFeedConsumption / $totalWeight : 0;
        }

        // Calculate ADG
        $adg = $age > 0 ? $currentWeight / $age : 0;

        // Calculate IP
        $ip = 0;
        if ($age > 0 && $fcr > 0) {
            $ip = ($liveability * $currentWeight * 100) / ($age * $fcr);
        }

        return [
            'liveability' => round($liveability, 2),
            'mortality_rate' => round($mortalityRate, 2),
            'fcr' => round($fcr, 3),
            'feed_intake' => round($totalFeedConsumption / max($currentPopulation, 1), 2),
            'adg' => round($adg, 3),
            'ip' => round($ip, 2),
            'weight_per_age' => $age > 0 ? round($currentWeight / $age, 3) : 0,
            'feed_per_day' => $age > 0 ? round($totalFeedConsumption / $age, 2) : 0,
            'depletion_per_day' => $age > 0 ? round(($initialPopulation - $currentPopulation) / $age, 2) : 0,
        ];
    }

    /**
     * Prepare historical data
     */
    private function prepareHistoricalData(Livestock $livestock, string $currentDate): array
    {
        // Get population history
        $populationHistory = $this->getPopulationHistory($this->livestockId, Carbon::parse($currentDate));

        // Get weight history
        $weightHistory = $this->getWeightHistory($this->livestockId, Carbon::parse($currentDate));

        // Get feed history
        $feedHistory = $this->getFeedConsumptionHistory($this->livestockId, Carbon::parse($currentDate));

        // Get outflow history
        $outflowHistory = $this->getDetailedOutflowHistory($this->livestockId, $currentDate);

        return [
            'population_history' => $populationHistory,
            'weight_history' => $weightHistory,
            'feed_history' => $feedHistory,
            'outflow_history' => $outflowHistory,
        ];
    }

    /**
     * Process feed usage if any
     */
    private function processFeedUsage(array $validatedData, string $recordingId): void
    {
        // Prepare feed usage data
        $this->usages = collect($this->itemQuantities)
            ->filter(fn($qty) => $qty > 0)
            ->map(function ($qty, $itemId) {
                $feed = Feed::with('unit')->find($itemId);
                return [
                    'feed_id' => $itemId,
                    'quantity' => (float) $qty,
                    'feed_name' => $feed ? $feed->name : 'Unknown Feed',
                    'feed_code' => $feed ? $feed->code : 'Unknown Code',
                ];
            })
            ->values()
            ->toArray();

        if (empty($this->usages)) {
            return;
        }

        // Validate usage date
        $earliestStockDate = FeedStock::where('livestock_id', $this->livestockId)->min('date');
        if ($earliestStockDate && $this->date < $earliestStockDate) {
            throw new \Exception("Feed usage date must be after the earliest stock entry date ({$earliestStockDate})");
        }

        // Save feed usage
        $this->saveFeedUsageWithTracking($validatedData, $recordingId);
    }

    /**
     * Process supply usage if any
     */
    private function processSupplyUsage(array $validatedData, string $recordingId): void
    {
        // Prepare supply usage data
        $this->supplyUsages = collect($this->supplyQuantities)
            ->map(function ($quantity, $supplyId) {
                if (empty($quantity) || $quantity <= 0) {
                    return null;
                }

                $supply = Supply::with('unit')->find($supplyId);
                if (!$supply) {
                    return null;
                }

                return [
                    'supply_id' => $supplyId,
                    'quantity' => (float) $quantity,
                    'supply_name' => $supply->name,
                    'supply_code' => $supply->code,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        if (empty($this->supplyUsages)) {
            return;
        }

        // Validate usage date
        $livestock = Livestock::find($this->livestockId);
        $earliestSupplyStockDate = SupplyStock::where('farm_id', $livestock->farm_id)->min('date');
        if ($earliestSupplyStockDate && $this->date < $earliestSupplyStockDate) {
            throw new \Exception("Supply usage date must be after the earliest supply stock entry date ({$earliestSupplyStockDate})");
        }

        // Save supply usage
        $this->saveSupplyUsageWithTracking($validatedData, $recordingId);
    }

    /**
     * Process depletion data
     */
    private function processDepletionData(string $recordingId): void
    {
        if ($this->mortality > 0) {
            Log::info('🔴 Records Save: Processing mortality depletion');
            $this->storeDeplesiWithDetails('Mati', $this->mortality, $recordingId);
        }

        if ($this->culling > 0) {
            Log::info('🟡 Records Save: Processing culling depletion');
            $this->storeDeplesiWithDetails('Afkir', $this->culling, $recordingId);
        }
    }

    /**
     * Reset form after successful save
     */
    private function resetFormAfterSave(): void
    {
        $this->reset([
            'date',
            'age',
            'stock_start',
            'stock_end',
            'mortality',
            'culling',
            'weight_today',
            'weight_yesterday',
            'weight_gain',
            'sales_quantity',
            'sales_price',
            'total_sales'
        ]);
        $this->initializeItemQuantities();
        $this->loadStockData();
        $this->checkCurrentLivestockStock();
        $this->loadRecordingData();
    }

    // === EXISTING METHODS FROM ORIGINAL FILE ===
    // (Include all the existing methods from the original file that are still needed)
    // I'll include the essential ones here and indicate where others should be added

    public function render()
    {
        return view('livewire.records', [
            'recordings' => $this->recordings,
            'items' => $this->items,
            'supplyQuantities' => $this->supplyQuantities,
            'availableSupplies' => $this->availableSupplies,
            'yesterdayData' => $this->yesterday_data
        ]);
    }

    // === PLACEHOLDER METHODS - NEED TO BE COPIED FROM ORIGINAL FILE ===

    /**
     * Placeholder method - copy from original file
     */
    protected function hasUsageChanged(FeedUsage $usage, array $newUsages): bool
    {
        // TODO: Copy implementation from original file
        return false;
    }

    /**
     * Placeholder method - copy from original file
     */
    protected function hasSupplyUsageChanged(SupplyUsage $usage, array $newSupplyUsages): bool
    {
        // TODO: Copy implementation from original file
        return false;
    }

    /**
     * Placeholder method - copy from original file
     */
    public function checkStockByTernakId($livestockId)
    {
        // TODO: Copy implementation from original file
        return [];
    }

    /**
     * Placeholder method - copy from original file
     */
    private function loadStockData()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function initializeItemQuantities()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function initializeSupplyItems()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function loadAvailableSupplies()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function checkCurrentLivestockStock()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function loadRecordingData()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function getPopulationHistory($livestockId, $currentDate)
    {
        // TODO: Copy implementation from original file
        return [];
    }

    /**
     * Placeholder method - copy from original file
     */
    private function getWeightHistory($livestockId, $currentDate)
    {
        // TODO: Copy implementation from original file
        return [];
    }

    /**
     * Placeholder method - copy from original file
     */
    private function getFeedConsumptionHistory($livestockId, $currentDate)
    {
        // TODO: Copy implementation from original file
        return [];
    }

    /**
     * Placeholder method - copy from original file
     */
    private function getDetailedOutflowHistory($livestockId, $date)
    {
        // TODO: Copy implementation from original file
        return [];
    }

    /**
     * Placeholder method - copy from original file
     */
    private function saveFeedUsageWithTracking($validatedData, $recordingId)
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function saveSupplyUsageWithTracking($validatedData, $recordingId)
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function storeDeplesiWithDetails($jenis, $jumlah, $recordingId)
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function updateCurrentLivestockQuantityWithHistory()
    {
        // TODO: Copy implementation from original file
    }

    /**
     * Placeholder method - copy from original file
     */
    private function saveOrUpdateRecording($data)
    {
        // TODO: Copy implementation from original file
    }

    // Add this method to handle date changes
    public function updatedDate($value)
    {
        if (!$this->livestockId || !$value) {
            return;
        }

        $usage = FeedUsage::where('usage_date', $value)
            ->where('livestock_id', $this->livestockId)
            ->first();
        if ($usage) {
            $this->feedUsageId = $usage->id;
        } else {
            $this->feedUsageId = null;
        }

        // Check for supply usage
        $supplyUsage = SupplyUsage::where('usage_date', $value)
            ->where('livestock_id', $this->livestockId)
            ->first();
        if ($supplyUsage) {
            $this->supplyUsageId = $supplyUsage->id;
        } else {
            $this->supplyUsageId = null;
        }


        // --- Fetch Recording Data for the selected date ---
        $recordingData = Recording::where('livestock_id', $this->livestockId)
            ->whereDate('tanggal', $value)
            ->first();

        // --- Fetch Deplesi Data for the selected date ---
        $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->whereDate('tanggal', $value)
            ->get();

        // --- Fetch Item Usage Data for the selected date ---
        // $itemUsage = TransaksiHarianDetail::whereHas('transaksiHarian', function($query) use ($value) {
        //         $query->where('livestock_id', $this->livestockId)
        //               ->whereDate('tanggal', $value);
        //     })
        //     ->select('item_id', 'quantity') // Select only necessary columns
        //     ->get()
        //     ->pluck('quantity', 'item_id'); // Create an associative array [item_id => quantity]

        $itemUsage = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($value) {
            $query->where('livestock_id', $this->livestockId)
                ->whereDate('usage_date', $value);
        })
            ->select('feed_id as item_id', DB::raw('SUM(quantity_taken) as quantity'))
            ->groupBy('feed_id')
            ->get()
            ->pluck('quantity', 'item_id'); // hasil: [feed_id => total_quantity]
        // $usage = FeedUsage::where('usage_date', $this->date)
        //     ->where('livestock_id', $this->livestockId)
        //     ->first();

        // --- Fetch Supply Usage Data for the selected date ---
        $supplyUsage = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($value) {
            $query->where('livestock_id', $this->livestockId)
                ->whereDate('usage_date', $value);
        })
            ->select('supply_id', DB::raw('SUM(quantity_taken) as quantity'))
            ->groupBy('supply_id')
            ->get()
            ->keyBy('supply_id'); // hasil: [supply_id => total_quantity]

        // dd($itemUsage);

        // --- Update Component Properties ---

        // Reset item quantities based on current available items first
        $this->initializeItemQuantities();

        // Then, populate with usage data for the selected date
        foreach ($itemUsage as $itemId => $quantity) {
            // if (isset($this->itemQuantities[$itemId])) { // Ensure the item exists in the current list
            $this->itemQuantities[$itemId] = $quantity;
            // }
        }

        // Reset and populate supply quantities
        $this->initializeSupplyItems();
        if ($supplyUsage->isNotEmpty()) {
            $this->supplyQuantities = [];
            foreach ($supplyUsage as $supplyId => $usageData) {
                $this->supplyQuantities[$supplyId] = $usageData->quantity;
            }
        }

        // dd($this->itemQuantities);



        // Update Deplesi fields
        if ($deplesi->isNotEmpty()) {
            $this->deplesiData = [
                'mortality' => $deplesi->where('jenis', 'Mati')->sum('jumlah'),
                'culling' => $deplesi->where('jenis', 'Afkir')->sum('jumlah')
            ];
            $this->mortality = $this->deplesiData['mortality'];
            $this->culling = $this->deplesiData['culling'];
        } else {
            $this->deplesiData = null;
            $this->mortality = 0;
            $this->culling = 0;
        }

        // Update Total Deplesi (recalculate based on all-time data)
        $allDeplesi = LivestockDepletion::where('livestock_id', $this->livestockId)->get();
        $this->total_deplesi = $allDeplesi->sum('jumlah');
        // Also update the value in currentLivestockStock if needed (optional, depends on usage)
        if ($this->currentLivestockStock) {
            $this->currentLivestockStock['mortality'] = $allDeplesi->where('jenis', 'Mati')->sum('jumlah');
            $this->currentLivestockStock['culling'] = $allDeplesi->where('jenis', 'Afkir')->sum('jumlah');
            $this->currentLivestockStock['total_deplesi'] = $this->total_deplesi;
        }

        // dd($recordingData);

        // --- Fetch Yesterday's Data for Better Information ---
        $previousDate = Carbon::parse($value)->subDay()->format('Y-m-d');
        $this->loadYesterdayData($previousDate);

        // Update Weight fields
        if ($recordingData) {
            $this->weight_yesterday = $recordingData->berat_semalam ?? 0;
            $this->weight_today = $recordingData->berat_hari_ini ?? 0;
            $this->weight_gain = $recordingData->kenaikan_berat ?? 0;

            // Update Sales fields
            $this->sales_quantity = $recordingData->payload['sales_quantity'] ?? 0;
            $this->sales_weight = $recordingData->payload['sales_weight'] ?? 0;
            $this->sales_price = $recordingData->payload['sales_price'] ?? 0;
            $this->total_sales = $recordingData->payload['total_sales'] ?? 0;
            $this->isEditing = true;
        } else {
            // Use yesterday's weight as weight_yesterday if no recording for selected date
            $this->weight_yesterday = $this->yesterday_weight ?? 0;
            $this->weight_today = null; // Reset today's weight
            $this->weight_gain = 0;     // Reset gain

            // Reset Sales fields
            $this->sales_quantity = 0;
            $this->sales_price = 0;
            $this->total_sales = 0;

            $this->isEditing = false;
        }

        // Calculate age
        if ($this->currentLivestockStock && isset($this->currentLivestockStock['start_date'])) {
            $startDate = Carbon::parse($this->currentLivestockStock['start_date']);
            $selectedDate = Carbon::parse($value);
            $this->age = $startDate->diffInDays($selectedDate);
        }
    }

    private function loadYesterdayData($yesterdayDate)
    {
        if (!$this->livestockId || !$yesterdayDate) {
            $this->resetYesterdayData();
            return;
        }

        try {
            // --- Fetch Yesterday's Recording Data ---
            $yesterdayRecording = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $yesterdayDate)
                ->first();

            // --- Fetch Yesterday's Depletion Data ---
            $yesterdayDeplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $yesterdayDate)
                ->get();

            // --- Fetch Yesterday's Feed Usage Data ---
            $yesterdayFeedUsage = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($yesterdayDate) {
                $query->where('livestock_id', $this->livestockId)
                    ->whereDate('usage_date', $yesterdayDate);
            })
                ->with(['feedStock.feed'])
                ->get();

            // --- Fetch Yesterday's Supply Usage Data ---
            $yesterdaySupplyUsage = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($yesterdayDate) {
                $query->where('livestock_id', $this->livestockId)
                    ->whereDate('usage_date', $yesterdayDate);
            })
                ->with(['supplyStock.supply'])
                ->get();

            // --- Process and Store Yesterday's Data ---
            if ($yesterdayRecording) {
                $this->yesterday_weight = $yesterdayRecording->berat_hari_ini ?? 0;
                $this->yesterday_stock_end = $yesterdayRecording->stock_akhir ?? 0;
            } else {
                $this->yesterday_weight = 0;
                $this->yesterday_stock_end = 0;
            }

            // Process yesterday's depletion
            if ($yesterdayDeplesi->isNotEmpty()) {
                $this->yesterday_mortality = $yesterdayDeplesi->where('jenis', 'Mati')->sum('jumlah');
                $this->yesterday_culling = $yesterdayDeplesi->where('jenis', 'Afkir')->sum('jumlah');
            } else {
                $this->yesterday_mortality = 0;
                $this->yesterday_culling = 0;
            }

            // Process yesterday's feed usage
            if ($yesterdayFeedUsage->isNotEmpty()) {
                $feedUsageByType = $yesterdayFeedUsage->groupBy('feedStock.feed.name')
                    ->map(function ($group) {
                        return [
                            'name' => $group->first()->feedStock->feed->name ?? 'Unknown',
                            'code' => $group->first()->feedStock->feed->code ?? 'Unknown',
                            'total_quantity' => $group->sum('quantity_taken'),
                            'unit' => $group->first()->feedStock->feed->unit->name ?? 'Kg'
                        ];
                    })->values();

                $this->yesterday_feed_usage = [
                    'total_quantity' => $yesterdayFeedUsage->sum('quantity_taken'),
                    'by_type' => $feedUsageByType->toArray(),
                    'types_count' => $feedUsageByType->count()
                ];
            } else {
                $this->yesterday_feed_usage = [
                    'total_quantity' => 0,
                    'by_type' => [],
                    'types_count' => 0
                ];
            }

            // Process yesterday's supply usage
            if ($yesterdaySupplyUsage->isNotEmpty()) {
                $supplyUsageByType = $yesterdaySupplyUsage->groupBy('supplyStock.supply.name')
                    ->map(function ($group) {
                        return [
                            'name' => $group->first()->supplyStock->supply->name ?? 'Unknown',
                            'code' => $group->first()->supplyStock->supply->code ?? 'Unknown',
                            'total_quantity' => $group->sum('quantity_taken'),
                            'unit' => $group->first()->supplyStock->supply->unit->name ?? 'Unit'
                        ];
                    })->values();

                $this->yesterday_supply_usage = [
                    'total_quantity' => $yesterdaySupplyUsage->sum('quantity_taken'),
                    'by_type' => $supplyUsageByType->toArray(),
                    'types_count' => $supplyUsageByType->count()
                ];
            } else {
                $this->yesterday_supply_usage = [
                    'total_quantity' => 0,
                    'by_type' => [],
                    'types_count' => 0
                ];
            }

            // Create comprehensive yesterday data summary
            $this->yesterday_data = [
                'date' => $yesterdayDate,
                'formatted_date' => Carbon::parse($yesterdayDate)->format('d/m/Y'),
                'day_name' => Carbon::parse($yesterdayDate)->locale('id')->dayName,
                'weight' => $this->yesterday_weight,
                'stock_end' => $this->yesterday_stock_end,
                'mortality' => $this->yesterday_mortality,
                'culling' => $this->yesterday_culling,
                'total_depletion' => $this->yesterday_mortality + $this->yesterday_culling,
                'feed_usage' => $this->yesterday_feed_usage,
                'supply_usage' => $this->yesterday_supply_usage,
                'has_data' => $yesterdayRecording || $yesterdayDeplesi->isNotEmpty() ||
                    $yesterdayFeedUsage->isNotEmpty() || $yesterdaySupplyUsage->isNotEmpty(),
                'summary' => $this->generateYesterdaySummary()
            ];

            Log::info("Yesterday data loaded successfully", [
                'livestock_id' => $this->livestockId,
                'yesterday_date' => $yesterdayDate,
                'has_recording' => $yesterdayRecording ? 'yes' : 'no',
                'has_depletion' => $yesterdayDeplesi->isNotEmpty() ? 'yes' : 'no',
                'has_feed_usage' => $yesterdayFeedUsage->isNotEmpty() ? 'yes' : 'no',
                'has_supply_usage' => $yesterdaySupplyUsage->isNotEmpty() ? 'yes' : 'no',
            ]);
        } catch (\Exception $e) {
            Log::error("Error loading yesterday data: " . $e->getMessage(), [
                'livestock_id' => $this->livestockId,
                'yesterday_date' => $yesterdayDate,
                'error' => $e->getMessage()
            ]);

            $this->resetYesterdayData();
        }
    }

    /**
     * Reset yesterday's data to default values
     * 
     * @return void
     */
    private function resetYesterdayData()
    {
        $this->yesterday_weight = 0;
        $this->yesterday_mortality = 0;
        $this->yesterday_culling = 0;
        $this->yesterday_stock_end = 0;
        $this->yesterday_feed_usage = [
            'total_quantity' => 0,
            'by_type' => [],
            'types_count' => 0
        ];
        $this->yesterday_supply_usage = [
            'total_quantity' => 0,
            'by_type' => [],
            'types_count' => 0
        ];
        $this->yesterday_data = null;
    }
}
