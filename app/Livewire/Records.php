<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;

use App\Models\Recording; // Assuming this is the model for the recordings
use App\Models\CurrentStock;
use App\Models\CurrentLivestock;
use App\Models\Feed;
use App\Models\LivestockDepletion;

use App\Services\StocksService;
use App\Services\FIFOService;
use App\Services\Livestock\FIFODepletionService;
use App\Services\Recording\RecordingMethodValidationService;
use App\Services\Recording\RecordingMethodTransitionHelper;
use App\Config\LivestockDepletionConfig;

// Modular services (loaded conditionally)
use App\Services\Recording\Contracts\RecordingDataServiceInterface;
use App\Services\Recording\Contracts\RecordingPersistenceServiceInterface;
use App\Services\Recording\DTOs\RecordingDTO;

use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\CurrentSupply;

// OVK/Supply related imports
use App\Models\Supply;
use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Unit;
use App\Models\Company;

use App\Traits\HasFifoDepletion;
use App\Services\Recording\LegacyRecordingService;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logWarningIfDebug;
use function App\Helpers\logErrorIfDebug;

class Records extends Component
{
    use HasFifoDepletion;

    public $recordings = [];
    public $livestockId; // Changed to a single value (integer)
    public $date;
    public $age;
    public $stock_start;
    public $stock_end;
    public $weight_yesterday;
    public $weight_today;
    public $weight_gain;
    public $items = []; // Initialize as empty array
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
    private bool $useModularServices = true;
    private bool $enableLegacyFallback = true;
    private bool $enablePerformanceMonitoring = false;
    public $livestockConfig = [];
    public $isManualDepletionEnabled = false;
    public $isFifoDepletionEnabled = false;
    public $isManualFeedUsageEnabled = false;
    public $isFifoMutationEnabled = false;
    public $isFifoFeedUsageEnabled = false;


    //Condition for recording method
    public $skipConfigMultipleBatch = true;
    public $skipConfigSingleBatch = false;


    protected $listeners = [
        'setRecords' => 'setRecords',
        'refreshData' => 'refreshData'
    ];

    protected $rules = [
        'date' => 'required|date',
        'mortality' => 'nullable|integer|min:0',
        'culling' => 'nullable|integer|min:0',
        'sales_quantity' => 'nullable|integer|min:0',
        'sales_price' => 'nullable|numeric|min:0',
        'total_sales' => 'nullable|numeric|min:0',
        // 'recordingMethod' => 'required|in:batch,total',
    ];

    protected $messages = [
        'recordingMethod.required' => 'Recording method must be selected.',
        'recordingMethod.in' => 'Invalid recording method selected.',
    ];

    protected ?StocksService $stocksService = null;
    protected ?FIFOService $fifoService = null;
    protected ?FIFODepletionService $fifoDepletionService = null;
    protected ?RecordingMethodValidationService $validationService = null;
    protected ?RecordingMethodTransitionHelper $transitionHelper = null;

    // --- Modular Service Properties ---
    protected ?RecordingDataServiceInterface $recordingDataService = null;
    protected ?RecordingPersistenceServiceInterface $recordingPersistenceService = null;

    protected ?LegacyRecordingService $legacyRecordingService = null;

    public function mount()
    {
        $this->initializeFeatureFlags();

        if ($this->useModularServices) {
            $this->initializeModularServices();
        } else {
            // Legacy services are now initialized on-demand to reduce initial load
        }
        $this->legacyRecordingService = app(LegacyRecordingService::class);

        $this->initializeItemQuantities();
        $this->initializeSupplyItems();
    }

    private function initializeFeatureFlags(): void
    {
        try {
            // Explicitly get config values to avoid caching issues
            $useModular = config('recording.features.use_modular_services');
            $useFallback = config('recording.features.use_legacy_fallback');
            $enableMonitoring = config('recording.features.enable_performance_monitoring');

            logDebugIfDebug('⚙️ Initializing Feature Flags - Raw Config Values', [
                'config_use_modular_services' => $useModular,
                'config_use_legacy_fallback' => $useFallback,
                'config_enable_performance_monitoring' => $enableMonitoring,
                'config_is_null_modular' => is_null($useModular),
            ]);

            $this->useModularServices = $useModular ?? false;
            $this->enableLegacyFallback = $useFallback ?? true;
            $this->enablePerformanceMonitoring = $enableMonitoring ?? false;

            logInfoIfDebug('✅ Feature flags initialized successfully', [
                'use_modular_services' => $this->useModularServices,
                'enable_legacy_fallback' => $this->enableLegacyFallback,
                'enable_performance_monitoring' => $this->enablePerformanceMonitoring,
                'livestock_id' => $this->livestockId,
            ]);
        } catch (Exception $e) {
            logErrorIfDebug('❌ Failed to initialize feature flags', ['error' => $e->getMessage()]);
            // Fallback to safe defaults
            $this->useModularServices = false;
            $this->enableLegacyFallback = true;
            $this->enablePerformanceMonitoring = false;
        }
    }

    private function initializeModularServices(): void
    {
        try {
            $this->recordingDataService = app(RecordingDataServiceInterface::class);
            $this->recordingPersistenceService = app(RecordingPersistenceServiceInterface::class);
            // Initialize other modular services as needed...
            logInfoIfDebug('✅ Modular services initialized successfully.');
        } catch (Exception $e) {
            logErrorIfDebug('❌ CRITICAL: Failed to initialize modular services. Fallback will be used.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Force fallback if modular services fail to load
            $this->useModularServices = false;
            $this->enableLegacyFallback = true;
        }
    }

    /**
     * Validate recording method configuration
     */
    private function validateRecordingMethod(Livestock $livestock, Company $company): bool
    {
        $validation = $livestock->validateRecordingMethod();

        if (!$validation['valid']) {
            $this->addError('recording_method', $validation['message']);
            return false;
        }

        return true;
    }

    /**
     * Check and set recording method based on configuration and batch count
     */
    private function checkAndSetRecordingMethod(Livestock $livestock, Company $company): bool
    {
        $validation = $livestock->validateRecordingMethod();

        if (!$validation['valid']) {
            $this->addError('recording_method', $validation['message']);
            return false;
        }

        // Set recording method based on validation result
        $this->recordingMethod = $validation['method'];

        return true;
    }

    /**
     * Load all necessary data for recording
     */
    private function loadAllRecordingData(): void
    {
        // Ensure service is initialized before use
        if (!$this->legacyRecordingService) {
            $this->legacyRecordingService = app(LegacyRecordingService::class);
        }

        $this->loadStockData();
        $this->initializeItemQuantities();
        $this->loadAvailableSupplies();
        $this->initializeSupplyItems();
        $this->checkCurrentLivestockStock();
        $this->recordings = $this->legacyRecordingService->loadRecordingDataForTable($this->livestockId);
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
                $user = Auth::user();
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

            if ($this->skipConfigMultipleBatch) {
                $user = Auth::user();
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
            } else {
                // Rewritten: Validasi jika batch > 1 dan belum ada records, cek config di kolom data
                if ($livestock->getActiveBatchesCount() > 1 && !\App\Models\Recording::where('livestock_id', $livestock->id)->exists()) {
                    $config = $livestock->getDataColumn('config');
                    if (empty($config) || !is_array($config) || empty($config['recording_method'])) {
                        $this->dispatch('error', 'Ternak ini memiliki lebih dari 1 batch aktif. Silakan atur metode pencatatan terlebih dahulu di menu setting pada data ini.');
                        // Log untuk debugging
                        logInfoIfDebug('[Records] setRecords: Gagal lanjut, config belum diatur untuk livestock_id: ' . $livestock->id, [
                            'config' => $config,
                            'livestock_id' => $livestock->id,
                            'user_id' => Auth::id(),
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                        return;
                    }
                    // Jika config sudah ada, lanjutkan proses
                    // Log untuk debugging
                    logInfoIfDebug('[Records] setRecords: Config ditemukan, proses dilanjutkan untuk livestock_id: ' . $livestock->id, [
                        'config' => $config,
                        'livestock_id' => $livestock->id,
                        'user_id' => Auth::id(),
                        'timestamp' => now()->toDateTimeString(),
                    ]);
                }
            }


            // // Validate recording method configuration
            // if (!$this->validateRecordingMethod($livestock, $company)) {
            //     return;
            // }

            // // Check and set recording method
            // if (!$this->checkAndSetRecordingMethod($livestock, $company)) {
            //     return;
            // }

            // Load all necessary data
            $this->loadAllRecordingData();

            $this->showForm = true;
            $this->dispatch('show-records');
        }
        logInfoIfDebug('✅ setRecords completed', [
            'livestock_id' => $this->livestockId,
            'date' => $this->date,
            'recordings_count' => count($this->recordings)
        ]);
    }

    public function refreshData()
    {
        logInfoIfDebug('🔄 refreshData called', [
            'livestock_id' => $this->livestockId
        ]);

        if (!$this->livestockId) {
            logWarningIfDebug('⚠️ refreshData: No livestock ID available');
            return;
        }

        // Clear all caches
        $performanceService = app(\App\Services\Recording\RecordingPerformanceService::class);
        $performanceService->clearRecordingCache($this->livestockId);
        \Illuminate\Support\Facades\Cache::flush();

        // Force reload all data
        $this->loadAllRecordingData();

        logInfoIfDebug('✅ refreshData completed', [
            'livestock_id' => $this->livestockId,
            'recordings_count' => count($this->recordings)
        ]);
    }

    /**
     * Load livestock configuration and set visibility flags
     */
    private function loadLivestockConfiguration(Livestock $livestock): void
    {
        $this->livestockConfig = $livestock->getConfiguration();
        $this->isManualDepletionEnabled = $livestock->isManualDepletionEnabled();
        $this->isManualFeedUsageEnabled = $livestock->isManualFeedUsageEnabled();
        $this->isFifoDepletionEnabled = $livestock->isFifoDepletionEnabled();
        $this->isFifoMutationEnabled = $livestock->isFifoMutationEnabled();
        $this->isFifoFeedUsageEnabled = $livestock->isFifoFeedUsageEnabled();
        logInfoIfDebug('Records - Livestock Configuration Loaded', [
            'livestock_id' => $livestock->id,
            'config' => $this->livestockConfig,
            'manual_depletion_enabled' => $this->isManualDepletionEnabled,
            'manual_feed_usage_enabled' => $this->isManualFeedUsageEnabled,
            'fifo_depletion_enabled' => $this->isFifoDepletionEnabled,
            'depletion_method' => $this->isFifoDepletionEnabled ? 'fifo' : 'manual'
        ]);
    }

    /**
     * Refresh livestock configuration - useful when configuration is changed externally
     */
    public function refreshConfiguration()
    {
        if (!$this->livestockId) {
            return;
        }

        $livestock = Livestock::find($this->livestockId);
        if (!$livestock) {
            return;
        }

        // Reload configuration
        $this->loadLivestockConfiguration($livestock);

        // Refresh current data if date is set
        if ($this->date) {
            $this->updatedDate($this->date);
        }

        // Dispatch success message
        $this->dispatch('success', 'Konfigurasi berhasil disegarkan');

        logInfoIfDebug('Records - Configuration refreshed manually', [
            'livestock_id' => $this->livestockId,
            'manual_depletion_enabled' => $this->isManualDepletionEnabled,
            'manual_feed_usage_enabled' => $this->isManualFeedUsageEnabled,
            'date' => $this->date
        ]);
    }

    /**
     * Check if depletion inputs should be editable
     */
    public function getCanEditDepletionProperty()
    {
        return !$this->isManualDepletionEnabled;
    }

    /**
     * Check if feed usage inputs should be editable  
     */
    public function getCanEditFeedUsageProperty()
    {
        return !$this->isManualFeedUsageEnabled;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->dispatch('hide-records');
        $this->resetErrorBag();
    }


    protected function hasUsageChanged(FeedUsage $usage, array $newUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('feed_id', DB::raw('SUM(quantity_taken) as total'))
            ->groupBy('feed_id')
            ->get()
            ->keyBy('feed_id');

        foreach ($newUsages as $row) {
            $feedId = $row['feed_id'];
            $qty = (float) $row['quantity'];

            if (!isset($existingDetails[$feedId]) || (float) $existingDetails[$feedId]->total !== $qty) {
                return true; // ada perubahan
            }
        }

        // Cek apakah ada item yang dihapus dari data baru
        if (count($existingDetails) !== count($newUsages)) {
            // dd('true');
            return true;
        }
        // dd('false');

        return false;
    }

    /**
     * Check if supply usage has changed
     */
    protected function hasSupplyUsageChanged(SupplyUsage $usage, array $newSupplyUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('supply_id', DB::raw('SUM(quantity_taken) as total'))
            ->groupBy('supply_id')
            ->get()
            ->keyBy('supply_id');

        foreach ($newSupplyUsages as $row) {
            $supplyId = $row['supply_id'];
            $qty = (float) $row['quantity'];

            if (!isset($existingDetails[$supplyId]) || (float) $existingDetails[$supplyId]->total !== $qty) {
                return true; // ada perubahan
            }
        }

        // Cek apakah ada item yang dihapus dari data baru
        if (count($existingDetails) !== count($newSupplyUsages)) {
            return true;
        }

        return false;
    }

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

    public function updatedWeightToday()
    {
        $this->weight_gain = $this->weight_today - ($this->weight_yesterday ?? 0);
    }

    private function formatNumber($number, $decimals = 2)
    {
        return number_format($number, $decimals, '.', ',');
    }

    private function calculateFCR($feedUsage, $weight)
    {
        if ($weight <= 0) return 0;
        return $this->formatNumber($feedUsage / $weight);
    }

    private function calculateIP($liveability, $age, $weight, $fcr)
    {
        if ($age <= 0 || $fcr <= 0) return 0;
        return $this->formatNumber(($liveability * $weight * 100) / ($age * $fcr));
    }

    private function loadRecordings()
    {
        if ($this->livestockId) {
            $this->recordings = Recording::where('livestock_id', $this->livestockId)->get();
        } else {
            $this->recordings = [];
        }
    }

    public function checkStockByTernakId($livestockId)
    {
        $stocks = FeedStock::with('feed')
            ->where('livestock_id', $livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->get();

        // dd($livestockId);

        if ($stocks->isEmpty()) {
            return;
        }

        // Gabungkan berdasarkan feed_id
        $grouped = $stocks->groupBy('feed_id')->map(function ($group, $feedId) use ($livestockId) {
            $totalAvailable = $group->sum(function ($s) {
                return $s->quantity_in - $s->quantity_used - $s->quantity_mutated;
            });

            return [
                'livestock_id' => $livestockId,
                'item_id' => $feedId, // feed_id as item_id
                'item_name' => optional($group->first()->feed)->name ?? 'Item tidak diketahui',
                'stock' => $totalAvailable,
            ];
        })->values(); // Reset keys

        return $grouped;
    }


    private function loadStockData()
    {
        $stockData = $this->checkStockByTernakId($this->livestockId);

        if (collect($stockData)->isEmpty()) {
            $this->dispatch('noSubmit');
            $this->dispatch('error', 'Batch ayam belum memiliki data stok');
            $this->items = [];
            $this->itemQuantities = [];
            return;
        }

        $this->items = $stockData;

        foreach ($this->items as $item) {
            $this->itemQuantities[$item['item_id']] = 0;
        }
    }

    private function initializeItemQuantities()
    {
        $stocks = FeedStock::with('feed')
            ->where('livestock_id', $this->livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->get();

        $this->itemQuantities = [];

        foreach ($stocks as $stock) {
            $itemId = $stock->item_id;

            // Abaikan jika item_id kosong (untuk mencegah error)
            if (empty($itemId)) {
                continue;
            }

            if (!isset($this->itemQuantities[$itemId])) {
                $this->itemQuantities[$itemId] = 0;
            }
        }
    }

    /**
     * Initialize supply items with empty default entry
     */
    private function initializeSupplyItems()
    {
        $this->loadAvailableSupplies();

        // Initialize empty quantities array for all available supplies
        if (empty($this->supplyQuantities)) {
            $this->supplyQuantities = [];
        }
    }

    /**
     * Load available supplies for the livestock
     */
    private function loadAvailableSupplies()
    {
        if (!$this->livestockId) {
            $this->availableSupplies = [];
            return;
        }

        $livestock = Livestock::find($this->livestockId);
        if (!$livestock) {
            $this->availableSupplies = [];
            return;
        }

        // Get supplies that have stock in this farm
        $this->availableSupplies = Supply::whereHas('supplyStocks', function ($query) use ($livestock) {
            $query->where('farm_id', $livestock->farm_id)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0');
        })
            ->whereHas('supplyCategory', function ($query) {
                $query->where('name', 'OVK');
            })
            ->with(['supplyCategory', 'unit'])
            ->get();

        // dd($this->availableSupplies, $this->livestockId, $livestock->farm_id);

        // Supplies are now available for use in the form
        // Available supplies loaded for the simple form
    }

    /**
     * Check available stock for a specific supply
     */
    public function checkSupplyStock($supplyId, $livestockId)
    {
        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            return [];
        }

        $stocks = SupplyStock::where('farm_id', $livestock->farm_id)
            ->where('supply_id', $supplyId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

        return [
            'livestock_id' => $livestockId,
            'supply_id' => $supplyId,
            'stock' => $totalAvailable,
        ];
    }

    public function updatedItemQuantities($value, $key)
    {
        $itemId = explode('.', $key)[0];
        foreach ($this->items as $item) {
            if ($item['item_id'] == $itemId) {
                // Calculate available stock by subtracting previous entries
                $availableStock = $item['stock'] - ($this->previousItemQuantities[$itemId] ?? 0);

                if ($value > $availableStock) {
                    $this->itemQuantities[$itemId] = $availableStock;
                } elseif ($value < 0) {
                    $this->itemQuantities[$itemId] = 0;
                }
                break;
            }
        }
    }

    private function checkCurrentLivestockStock()
    {
        if (!$this->livestockId) {
            return;
        }

        $currentLivestock = CurrentLivestock::where('livestock_id', $this->livestockId)
            ->select('quantity as stock_akhir', 'livestock_id')
            ->with([
                'livestock' => function ($query) {
                    $query->select('id', 'name', 'start_date', 'initial_quantity')
                        ->with(['livestockDepletion' => function ($q) {
                            $q->where('tanggal', '<=', now())
                                ->select('livestock_id', 'jenis', 'jumlah', 'tanggal');
                        }]);
                }
            ])
            ->first();

        if ($currentLivestock) {
            // Calculate deplesi using the relationship with config normalization
            $deplesi = $currentLivestock->livestock->livestockDepletion;

            // Use config system for backward compatibility
            $mortalityTypes = [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY];
            $cullingTypes = [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING];

            $totalMati = $deplesi->whereIn('jenis', $mortalityTypes)->sum('jumlah');
            $totalAfkir = $deplesi->whereIn('jenis', $cullingTypes)->sum('jumlah');
            $totalDeplesi = $totalMati + $totalAfkir;

            $this->currentLivestockStock = [
                'stock_awal' => $currentLivestock->livestock->initial_quantity ?? 0,
                'stock_akhir' => $currentLivestock->stock_akhir ?? 0,
                'start_date' => $currentLivestock->livestock->start_date ?? null,
                'name' => $currentLivestock->livestock->name ?? 'Unknown',
                'mortality' => $totalMati,
                'culling' => $totalAfkir,
                'total_deplesi' => $totalDeplesi
            ];

            // Auto-fill the stock fields
            $this->stock_start = $currentLivestock->livestock->initial_quantity ?? 0;
            $this->stock_end = $currentLivestock->stock_akhir ?? 0;

            // Set depletion values with proper terminology
            // $this->mortality = $totalMati;    // Jenis Mati
            // $this->culling = $totalAfkir;     // Jenis Afkir
            $this->total_deplesi = $totalDeplesi;

            // Calculate age if start_date is available
            if ($this->currentLivestockStock['start_date']) {
                $startDate = \Carbon\Carbon::parse($this->currentLivestockStock['start_date']);
                $currentDate = \Carbon\Carbon::now();
                $this->age = $startDate->diffInDays($currentDate);
            }
        } else {
            $this->currentLivestockStock = null;
            $this->stock_start = 0;
            $this->stock_end = 0;
            $this->age = null;
        }
    }


    // Add this method to handle date changes
    public function updatedDate($value)
    {
        if (!$this->livestockId || !$value) {
            return;
        }

        $this->date = $value; // Ensure the date property is set

        // --- MODULAR PATH ---
        if ($this->useModularServices) {
            logInfoIfDebug('🔄 updatedDate: Using MODULAR services path.');
            try {
                if (!$this->recordingDataService) $this->initializeModularServices();

                // Load yesterday's data first to get weight_yesterday
                $yesterdayDate = Carbon::parse($value)->subDay()->format('Y-m-d');
                $this->loadYesterdayData($yesterdayDate);

                $serviceResult = $this->recordingDataService->loadCurrentDateData($this->livestockId, $value);

                if ($serviceResult->isSuccess()) {
                    logInfoIfDebug('✅ updatedDate: Modular data loaded successfully.');
                    $data = $serviceResult->getData();
                    logDebugIfDebug('Modular data received in updatedDate', ['data' => $data]);

                    // Validate that we have the essential data
                    $hasEssentialData = isset($data['itemQuantities']) &&
                        isset($data['supplyQuantities']) &&
                        isset($data['mortality']) &&
                        isset($data['culling']);

                    if ($hasEssentialData) {
                        // Populate all component properties from the service data
                        $this->itemQuantities = $data['itemQuantities'] ?? [];
                        $this->supplyQuantities = $data['supplyQuantities'] ?? [];
                        $this->feedUsageId = $data['feedUsageId'] ?? null;
                        $this->supplyUsageId = $data['supplyUsageId'] ?? null;
                        $this->weight_today = $data['weight_today'] ?? null;
                        $this->mortality = $data['mortality'] ?? 0;
                        $this->culling = $data['culling'] ?? 0;
                        $this->sales_quantity = $data['sales_quantity'] ?? null;
                        $this->sales_price = $data['sales_price'] ?? null;
                        $this->total_sales = $data['total_sales'] ?? null;
                        $this->isEditing = $data['recording_exists'] ?? false;

                        logInfoIfDebug('✅ updatedDate: Modular data populated successfully, skipping fallback.');
                        return; // Exit successfully without fallback
                    } else {
                        logWarningIfDebug('⚠️ updatedDate: Modular service returned incomplete data, executing fallback.', [
                            'missing_keys' => array_diff(['itemQuantities', 'supplyQuantities', 'mortality', 'culling'], array_keys($data))
                        ]);
                    }
                } else {
                    logWarningIfDebug('⚠️ updatedDate: Modular service failed for current date, executing fallback.', [
                        'message' => $serviceResult->getMessage()
                    ]);
                }
            } catch (Exception $e) {
                logErrorIfDebug('❌ updatedDate: CRITICAL error in modular path.', ['error' => $e->getMessage()]);
            }

            // Only execute fallback if we haven't returned successfully above
            if ($this->enableLegacyFallback) {
                logWarningIfDebug('⚠️ updatedDate: Modular path failed, executing fallback.');
                $this->updatedDateFallback($value);
            } else {
                $this->dispatch('error', 'Gagal memuat data dari modular service.');
            }
            return; // Exit after attempting modular/fallback path
        }

        // --- LEGACY PATH ---
        logInfoIfDebug('🔄 updatedDate: Using LEGACY path (modular services disabled).');
        $this->updatedDateFallback($value);
    }

    /**
     * Fallback method containing the original logic from Records_backup.php
     */
    private function updatedDateFallback($value)
    {
        // Delegasi ke LegacyRecordingService agar logic terpusat di service
        if (!$this->legacyRecordingService) {
            $this->legacyRecordingService = app(\App\Services\Recording\LegacyRecordingService::class);
        }

        logInfoIfDebug('🔄 updatedDate: Using FALLBACK (legacy service) path.');

        $data = $this->legacyRecordingService->loadDateDataFallback($this->livestockId, $value);

        if (isset($data['error'])) {
            $this->dispatch('error', $data['error']);
            return;
        }

        // Assign data to component properties
        $this->feedUsageId   = $data['feedUsageId'];
        $this->supplyUsageId = $data['supplyUsageId'];

        // Feed item quantities
        $this->initializeItemQuantities();
        foreach ($data['itemQuantities'] as $itemId => $qty) {
            $this->itemQuantities[$itemId] = $qty;
        }

        // Supply quantities
        $this->initializeSupplyItems();
        $this->supplyQuantities = $data['supplyQuantities'];

        // Depletion data
        $this->deplesiData = $data['deplesiData'];
        $this->mortality   = $data['mortality'];
        $this->culling     = $data['culling'];
        $this->total_deplesi = $data['total_deplesi'];

        // Weight & sales
        $this->weight_yesterday = $data['weight_yesterday'];
        $this->weight_today     = $data['weight_today'];
        $this->weight_gain      = $data['weight_gain'];

        $this->sales_quantity = $data['sales_quantity'];
        $this->sales_weight   = $data['sales_weight'];
        $this->sales_price    = $data['sales_price'];
        $this->total_sales    = $data['total_sales'];

        // Misc flags & info
        $this->isEditing     = $data['isEditing'];
        $this->yesterday_data = $data['yesterday_data'];
        $this->age           = $data['age'];
    }

    /**
     * Load yesterday's data for better information display
     * 
     * @param string $yesterdayDate Yesterday's date in Y-m-d format
     * @return void
     */
    private function loadYesterdayData($yesterdayDate)
    {
        if (!$this->livestockId || !$yesterdayDate) {
            $this->resetYesterdayData();
            return;
        }

        // --- MODULAR PATH ---
        if ($this->useModularServices) {
            logInfoIfDebug('🔄 loadYesterdayData: Using MODULAR services path.');
            try {
                if (!$this->recordingDataService) $this->initializeModularServices();
                $result = $this->recordingDataService->loadYesterdayData($this->livestockId, $yesterdayDate);

                if ($result->isSuccess()) {
                    logInfoIfDebug('✅ loadYesterdayData: Modular data loaded successfully.');
                    $data = $result->getData();

                    // Validate that we have the essential data
                    $hasEssentialData = isset($data['weight']) &&
                        isset($data['mortality']) &&
                        isset($data['culling']) &&
                        isset($data['feed_usage']) &&
                        isset($data['supply_usage']);

                    if ($hasEssentialData) {
                        // Fully populate yesterday's data from the service result, mirroring the fallback logic
                        $this->yesterday_data = $data;
                        $this->yesterday_weight = $data['weight'] ?? 0;
                        $this->yesterday_stock_end = $data['stock_end'] ?? 0;
                        $this->yesterday_mortality = $data['mortality'] ?? 0;
                        $this->yesterday_culling = $data['culling'] ?? 0;
                        $this->yesterday_feed_usage = $data['feed_usage']['total_quantity'] ?? 0;
                        $this->yesterday_supply_usage = $data['supply_usage']['total_quantity'] ?? 0;

                        // Also populate the main weight_yesterday property used for calculations
                        $this->weight_yesterday = $data['weight'] ?? null;

                        logInfoIfDebug('✅ loadYesterdayData: Modular data populated successfully, skipping fallback.');
                        return; // Exit successfully without fallback
                    } else {
                        logWarningIfDebug('⚠️ loadYesterdayData: Modular service returned incomplete data, executing fallback.', [
                            'missing_keys' => array_diff(['weight', 'mortality', 'culling', 'feed_usage', 'supply_usage'], array_keys($data))
                        ]);
                    }
                } else {
                    logWarningIfDebug('⚠️ loadYesterdayData: Modular path failed, executing fallback.', ['message' => $result->getMessage()]);
                }
            } catch (Exception $e) {
                logErrorIfDebug('❌ loadYesterdayData: CRITICAL error in modular path.', ['error' => $e->getMessage()]);
            }

            // Only execute fallback if we haven't returned successfully above
            $this->loadYesterdayDataFallback($yesterdayDate);
        } else {
            logInfoIfDebug('🔄 loadYesterdayData: Using FALLBACK (legacy service) path.');
            $this->loadYesterdayDataFallback($yesterdayDate);
        }
    }

    /**
     * Fallback method for loading yesterday's data.
     */
    private function loadYesterdayDataFallback($yesterdayDate)
    {
        logInfoIfDebug('🔄 loadYesterdayData: Using FALLBACK (legacy service) path.');

        // Ensure service is initialized
        if (!$this->legacyRecordingService) {
            $this->legacyRecordingService = app(LegacyRecordingService::class);
        }

        $data = $this->legacyRecordingService->loadYesterdayDataFallback($this->livestockId, $yesterdayDate);

        if ($data) {
            $this->yesterday_data = $data;
            $this->yesterday_weight = $data['weight'];
            $this->yesterday_stock_end = $data['stock_end'];
            $this->yesterday_mortality = $data['mortality'];
            $this->yesterday_culling = $data['culling'];
            $this->yesterday_feed_usage = $data['feed_usage'];
            $this->yesterday_supply_usage = $data['supply_usage'];
        } else {
            $this->resetYesterdayData();
        }
    }

    /**
     * Generate a summary of yesterday's activities
     * 
     * @return string
     */
    private function generateYesterdaySummary()
    {
        $summary = [];

        if ($this->yesterday_weight > 0) {
            $summary[] = "Berat: " . number_format($this->yesterday_weight, 0) . "gr";
        }

        if ($this->yesterday_mortality > 0) {
            $summary[] = "Mati: " . $this->yesterday_mortality . " ekor";
        }

        if ($this->yesterday_culling > 0) {
            $summary[] = "Afkir: " . $this->yesterday_culling . " ekor";
        }

        if ($this->yesterday_feed_usage['total_quantity'] > 0) {
            $summary[] = "Pakan: " . number_format($this->yesterday_feed_usage['total_quantity'], 1) . "kg";
        }

        if ($this->yesterday_supply_usage['total_quantity'] > 0) {
            $summary[] = "OVK: " . $this->yesterday_supply_usage['types_count'] . " jenis";
        }

        return empty($summary) ? "Tidak ada data" : implode(", ", $summary);
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

    private function loadRecordingData()
    {
        if (!$this->livestockId) {
            return;
        }

        $ternak = CurrentLivestock::with('livestock')->where('livestock_id', $this->livestockId)->first();
        if (!$ternak) {
            return;
        }

        // dd($ternak);

        $startDate = Carbon::parse($ternak->livestock->start_date);
        $today = Carbon::today();

        $records = collect();
        $currentDate = $startDate->copy();
        $stockAwal = $ternak->livestock->initial_quantity;

        // dd($stockAwal);

        $totalPakanUsage = 0;
        // $standarData = $ternak->livestock->data ? $ternak->livestock->data[0]['livestock_breed_standard'] : [];
        $data = json_decode(json_encode($ternak->livestock->data), true); // Ubah string JSON ke array
        if (is_array($data) && isset($data[0]['livestock_breed_standard'])) {
            // dd($data[0]['livestock_breed_standard']);
            $standarData = $data[0]['livestock_breed_standard'];
        } else {
            // dd("Data tidak valid atau 'livestock_breed_standard' tidak ditemukan.");
        }

        while ($currentDate <= $today) {
            $dateStr = $currentDate->format('Y-m-d');

            // Deplesi
            $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $dateStr)
                ->get();

            // Use config normalization for backward compatibility
            $mortalityTypes = [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY];
            $cullingTypes = [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING];

            $mortality = $deplesi->whereIn('jenis', $mortalityTypes)->sum('jumlah');
            $culling = $deplesi->whereIn('jenis', $cullingTypes)->sum('jumlah');
            $totalDeplesi = $mortality + $culling;

            $age = $startDate->diffInDays($currentDate);

            // Feed usage via FeedUsageDetail
            $pakanUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($dateStr) {
                $query->whereDate('usage_date', $dateStr);
            })
                ->whereHas('feedStock', function ($query) {
                    $query->where('livestock_id', $this->livestockId);
                })
                ->with('feedStock.feed') // get feed name
                ->get();

            $pakanHarian = $pakanUsageDetails->sum('quantity');
            $totalPakanUsage += $pakanHarian;

            $record = [
                'tanggal' => $dateStr,
                'age' => $age,
                'fcr_target' => isset($standarData['data'][$age]) ? $standarData['data'][$age]['fcr']['target'] : 0,
                'stock_awal' => $stockAwal,
                'mati' => $mortality,
                'afkir' => $culling,
                'total_deplesi' => $totalDeplesi,
                'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
                'stock_akhir' => $stockAwal - $totalDeplesi,
                'pakan_jenis' => $pakanUsageDetails->pluck('feedStock.feed.name')->first() ?? '-',
                'pakan_harian' => $pakanHarian,
                'pakan_total' => $totalPakanUsage,
            ];

            $records->push($record);
            $stockAwal = $record['stock_akhir'];
            $currentDate->addDay();
        }

        $this->recordings = $records;
    }

    public function updatedSalesQuantity()
    {
        $this->calculateTotalSales();
    }

    public function updatedSalesPrice()
    {
        $this->calculateTotalSales();
    }

    /**
     * Calculate the total sales based on quantity and price
     *
     * @return void
     */
    private function calculateTotalSales()
    {
        if ($this->sales_quantity && $this->sales_price) {
            $this->total_sales = $this->sales_quantity * $this->sales_price;
        } else {
            $this->total_sales = 0;
        }
    }

    private function addBackToStock($itemId, $quantity)
    {
        // Find the current stock record
        $currentStock = CurrentStock::where('livestock_id', $this->livestockId)
            ->where('item_id', $itemId)
            ->first();

        if ($currentStock) {
            // Add back the quantity
            $currentStock->quantity += $quantity;
            $currentStock->save();
        }
    }

    public function save()
    {
        // --- MODULAR PATH ---
        if ($this->useModularServices) {
            logInfoIfDebug('🔄 save: Using MODULAR services path.');
            try {
                if (!$this->recordingPersistenceService) $this->initializeModularServices();

                // Create a DTO to pass data to the service
                $recordingDTO = new RecordingDTO($this->all());

                $result = $this->recordingPersistenceService->saveRecording($recordingDTO);

                if ($result->isSuccess()) {
                    // Clear cache aggressively after successful save
                    logInfoIfDebug('🔄 Clearing cache after successful save', [
                        'livestock_id' => $this->livestockId
                    ]);

                    // Clear recording cache
                    $performanceService = app(\App\Services\Recording\RecordingPerformanceService::class);
                    $performanceService->clearRecordingCache($this->livestockId);

                    // Clear application cache
                    \Illuminate\Support\Facades\Cache::flush();

                    // Force reload all data
                    $this->resetForm();
                    $this->loadStockData(); // Refresh feed stock after save
                    $this->loadRecordingData(); // Force reload recording data
                    $this->loadYesterdayData(Carbon::parse($this->date)->subDay()); // Reload yesterday data

                    logInfoIfDebug('✅ Data reloaded after save', [
                        'livestock_id' => $this->livestockId,
                        'recordings_count' => count($this->recordings)
                    ]);

                    $this->dispatch('success', $result->getMessage());
                    $this->dispatch('data-saved'); // To refresh table data if needed
                    $this->dispatch('refreshData'); // Force refresh all data
                } else {
                    $this->dispatch('error', $result->getMessage());
                }
                return;
            } catch (Exception $e) {
                logErrorIfDebug('❌ save: CRITICAL error in modular path.', ['error' => $e->getMessage()]);
                if (!$this->enableLegacyFallback) {
                    $this->dispatch('error', 'Terjadi kesalahan sistem saat menyimpan data.');
                    return;
                }
            }

            // If we are here, it means modular service failed and fallback is enabled
            if ($this->enableLegacyFallback) {
                logWarningIfDebug('⚠️ save: Modular path failed, executing fallback.');
                $this->saveFallback();
            }
            return;
        }

        // --- LEGACY PATH ---
        logInfoIfDebug('🔄 save: Using LEGACY path (modular services disabled).');
        $this->saveFallback();
    }

    private function saveFallback()
    {
        if (!$this->legacyRecordingService) {
            $this->legacyRecordingService = app(\App\Services\Recording\LegacyRecordingService::class);
        }
        $result = $this->legacyRecordingService->handleSave($this->all());

        if ($result['success']) {
            logInfoIfDebug('🔄 Records Save: Resetting form and reloading data');
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
            $this->recordings = $this->legacyRecordingService->loadRecordingDataForTable($this->livestockId);

            $this->dispatch('success', $result['message']);
        } else {
            $this->dispatch('error', $result['message']);
        }
    }

    /**
     * Get FIFO depletion statistics for display
     *
     * @param string $period
     * @return array|null
     */
    public function getFifoDepletionStats(string $period = '30_days'): ?array
    {
        try {
            if (!$this->livestockId) {
                return null;
            }

            $livestock = Livestock::find($this->livestockId);
            if (!$livestock) {
                return null;
            }

            return $this->fifoDepletionService->getFifoDepletionStats($livestock, $period);
        } catch (Exception $e) {
            logErrorIfDebug('❌ FIFO Stats: Failed to get FIFO depletion statistics', [
                'livestock_id' => $this->livestockId,
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update current livestock quantity with historical tracking
     * This method now follows the consistent formula and updates Livestock quantity_depletion
     * 
     * @return void
     */
    private function updateCurrentLivestockQuantityWithHistory()
    {
        if (!$this->livestockId) {
            return;
        }

        $livestock = Livestock::find($this->livestockId);
        $currentLivestock = CurrentLivestock::where('livestock_id', $this->livestockId)->first();

        if (!$livestock || !$currentLivestock) {
            logWarningIfDebug('⚠️ Livestock or CurrentLivestock not found', [
                'livestock_id' => $this->livestockId,
                'livestock_exists' => $livestock ? 'yes' : 'no',
                'current_livestock_exists' => $currentLivestock ? 'yes' : 'no'
            ]);
            return;
        }

        DB::transaction(function () use ($livestock, $currentLivestock) {
            // Calculate total depletion from LivestockDepletion records
            $totalDeplesi = LivestockDepletion::where('livestock_id', $this->livestockId)->sum('jumlah');

            // Get all sales records (if LivestockSalesItem exists)
            $totalSales = 0;
            if (class_exists('App\Models\LivestockSalesItem')) {
                $totalSales = \App\Models\LivestockSalesItem::where('livestock_id', $this->livestockId)->sum('quantity');
            }

            // Update quantity_depletion in Livestock table first
            $oldLivestockQuantityDepletion = $livestock->quantity_depletion ?? 0;
            $livestock->update([
                'quantity_depletion' => $totalDeplesi,
                'quantity_sales' => $totalSales,
                'updated_by' => Auth::id()
            ]);

            // Calculate real-time quantity using consistent formula
            // Formula: initial_quantity - quantity_depletion - quantity_sales - quantity_mutated
            $calculatedQuantity = $livestock->initial_quantity
                - $totalDeplesi
                - $totalSales
                - ($livestock->quantity_mutated ?? 0);

            // Ensure quantity doesn't go negative
            $calculatedQuantity = max(0, $calculatedQuantity);

            // Store the old quantity for history
            $oldQuantity = $currentLivestock->quantity;

            // Update CurrentLivestock with comprehensive metadata
            $currentLivestock->update([
                'quantity' => $calculatedQuantity,
                'metadata' => array_merge($currentLivestock->metadata ?? [], [
                    'last_updated' => now()->toIso8601String(),
                    'updated_by' => Auth::id(),
                    'updated_by_name' => Auth::user()->name ?? 'Unknown User',
                    'previous_quantity' => $oldQuantity,
                    'quantity_change' => $calculatedQuantity - $oldQuantity,
                    'calculation_source' => 'livewire_records_consistent_formula',
                    'formula_breakdown' => [
                        'initial_quantity' => $livestock->initial_quantity,
                        'quantity_depletion' => $totalDeplesi,
                        'quantity_sales' => $totalSales,
                        'quantity_mutated' => $livestock->quantity_mutated ?? 0,
                        'calculated_quantity' => $calculatedQuantity
                    ],
                    'percentages' => [
                        'depletion_percentage' => $livestock->initial_quantity > 0
                            ? round(($totalDeplesi / $livestock->initial_quantity) * 100, 2)
                            : 0,
                        'sales_percentage' => $livestock->initial_quantity > 0
                            ? round(($totalSales / $livestock->initial_quantity) * 100, 2)
                            : 0,
                        'remaining_percentage' => $livestock->initial_quantity > 0
                            ? round(($calculatedQuantity / $livestock->initial_quantity) * 100, 2)
                            : 0
                    ]
                ]),
                'updated_by' => Auth::id()
            ]);

            logInfoIfDebug("📊 Updated livestock quantities (consistent formula)", [
                'livestock_id' => $this->livestockId,
                'livestock_name' => $livestock->name,
                'old_livestock_quantity_depletion' => $oldLivestockQuantityDepletion,
                'new_livestock_quantity_depletion' => $totalDeplesi,
                'old_current_quantity' => $oldQuantity,
                'new_current_quantity' => $calculatedQuantity,
                'quantity_change' => $calculatedQuantity - $oldQuantity,
                'formula' => sprintf(
                    '%d - %d - %d - %d = %d',
                    $livestock->initial_quantity,
                    $totalDeplesi,
                    $totalSales,
                    $livestock->quantity_mutated ?? 0,
                    $calculatedQuantity
                )
            ]);
        });
    }

    /**
     * Save or update recording with enhanced metadata
     * 
     * @param array $data Recording data
     * @return \App\Models\Recording
     */
    private function saveOrUpdateRecording($data)
    {
        // Validate livestock exists
        $livestock = Livestock::find($data['livestock_id']);
        if (!$livestock) {
            throw new \Exception("Livestock not found");
        }

        // Validate recording date
        $livestockMasukDate = Carbon::parse($livestock->start_date);
        $recordingDate = Carbon::parse($data['tanggal']);

        if ($recordingDate->lt($livestockMasukDate)) {
            throw new \Exception("Recording date ({$recordingDate->format('Y-m-d')}) cannot be earlier than livestock entry date ({$livestockMasukDate->format('Y-m-d')})");
        }

        // Prepare enhanced metadata
        $enhancedMetadata = [
            'version' => '2.0',
            'recorded_at' => now()->toIso8601String(),
            'recorded_by' => [
                'id' => Auth::id(),
                'name' => Auth::user()->name ?? 'Unknown User',
                'role' => Auth::user()->roles->first()->name ?? 'Unknown Role',
            ],
            'livestock_details' => [
                'id' => $livestock->id,
                'name' => $livestock->name,
                'farm_id' => $livestock->farm_id,
                'farm_name' => $livestock->farm->name ?? 'Unknown Farm',
                'coop_id' => $livestock->coop_id,
                'kandang_name' => $livestock->kandang->name ?? 'Unknown Kandang',
                'strain' => $livestock->strain ?? 'Unknown Strain',
                'start_date' => $livestock->start_date,
                'initial_population' => $livestock->populasi_awal,
            ],
        ];

        // Merge with payload data
        $fullPayload = array_merge($data['payload'] ?? [], $enhancedMetadata);

        // Create or update the recording with enhanced data
        $recording = Recording::updateOrCreate(
            [
                'livestock_id' => $data['livestock_id'],
                'tanggal' => $data['tanggal']
            ],
            [
                'feed_id' => $data['feed_id'],
                'age' => $data['age'],
                'stock_awal' => $data['stock_awal'],
                'stock_akhir' => $data['stock_akhir'],
                'berat_hari_ini' => $data['berat_hari_ini'],
                'berat_semalam' => $data['berat_semalam'],
                'kenaikan_berat' => $data['kenaikan_berat'],
                'pakan_jenis' => $data['pakan_jenis'],
                'pakan_harian' => $data['pakan_harian'],
                'payload' => $fullPayload,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        // Force update of updated_by/updated_at even if no changes
        if (!$recording->wasRecentlyCreated && !$recording->wasChanged()) {
            $recording->updated_by = Auth::id();
            $recording->touch();
        }

        // Log changes for debugging and audit trail
        logInfoIfDebug('Recording saved', [
            'id' => $recording->id,
            'livestock_id' => $recording->livestock_id,
            'tanggal' => $recording->tanggal,
            'changes' => $recording->getChanges(),
            'is_new' => $recording->wasRecentlyCreated,
        ]);

        return $recording;
    }

    /**
     * Resets form fields to their default states after a successful save.
     * This keeps the context (livestockId, date) but clears the input values.
     */
    private function resetForm(): void
    {
        // Reset all input fields
        $this->reset(
            'date',
            'age',
            'stock_start',
            'stock_end',
            'mortality',
            'culling',
            'weight_today',
            'sales_quantity',
            'sales_weight',
            'sales_price',
            'total_sales',
            'weight_gain',
            'total_deplesi'
        );
        $this->isEditing = false;

        // Re-initialize the item and supply quantities to their default empty state
        $this->initializeItemQuantities();
        $this->initializeSupplyItems();

        logInfoIfDebug('📝 Form reset after successful save.');
    }
}
