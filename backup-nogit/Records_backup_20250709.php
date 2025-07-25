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
        'setRecords' => 'setRecords'
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

            Log::debug('⚙️ Initializing Feature Flags - Raw Config Values', [
                'config_use_modular_services' => $useModular,
                'config_use_legacy_fallback' => $useFallback,
                'config_enable_performance_monitoring' => $enableMonitoring,
                'config_is_null_modular' => is_null($useModular),
            ]);

            $this->useModularServices = $useModular ?? false;
            $this->enableLegacyFallback = $useFallback ?? true;
            $this->enablePerformanceMonitoring = $enableMonitoring ?? false;

            Log::info('✅ Feature flags initialized successfully', [
                'use_modular_services' => $this->useModularServices,
                'enable_legacy_fallback' => $this->enableLegacyFallback,
                'enable_performance_monitoring' => $this->enablePerformanceMonitoring,
                'livestock_id' => $this->livestockId,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to initialize feature flags', ['error' => $e->getMessage()]);
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
            Log::info('✅ Modular services initialized successfully.');
        } catch (Exception $e) {
            Log::critical('❌ CRITICAL: Failed to initialize modular services. Fallback will be used.', [
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
                        Log::info('[Records] setRecords: Gagal lanjut, config belum diatur untuk livestock_id: ' . $livestock->id, [
                            'config' => $config,
                            'livestock_id' => $livestock->id,
                            'user_id' => Auth::id(),
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                        return;
                    }
                    // Jika config sudah ada, lanjutkan proses
                    // Log untuk debugging
                    Log::info('[Records] setRecords: Config ditemukan, proses dilanjutkan untuk livestock_id: ' . $livestock->id, [
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
        Log::info('Records - Livestock Configuration Loaded', [
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

        Log::info('Records - Configuration refreshed manually', [
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
            Log::info('🔄 updatedDate: Using MODULAR services path.');
            try {
                if (!$this->recordingDataService) $this->initializeModularServices();

                // Load yesterday's data first to get weight_yesterday
                $yesterdayDate = Carbon::parse($value)->subDay()->format('Y-m-d');
                $this->loadYesterdayData($yesterdayDate);

                $serviceResult = $this->recordingDataService->loadCurrentDateData($this->livestockId, $value);

                if ($serviceResult->isSuccess()) {
                    Log::info('✅ updatedDate: Modular data loaded successfully.');
                    $data = $serviceResult->getData();
                    Log::debug('Modular data received in updatedDate', ['data' => $data]);

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
                } else {
                    Log::warning('⚠️ updatedDate: Modular service failed for current date, executing fallback.', [
                        'message' => $serviceResult->getMessage()
                    ]);
                    if (!$this->enableLegacyFallback) {
                        $this->dispatch('error', 'Gagal memuat data: ' . $serviceResult->getMessage());
                        return;
                    }
                    // Fall through to legacy if fallback is enabled
                }
            } catch (Exception $e) {
                Log::critical('❌ updatedDate: CRITICAL error in modular path.', ['error' => $e->getMessage()]);
                if (!$this->enableLegacyFallback) {
                    $this->dispatch('error', 'Terjadi kesalahan sistem saat memuat data.');
                    return;
                }
            }

            // If we are here, it means modular service failed and fallback is enabled
            if ($this->enableLegacyFallback) {
                Log::warning('⚠️ updatedDate: Modular path failed, executing fallback.');
                $this->updatedDateFallback($value);
            }
            return; // Exit after attempting modular/fallback path
        }

        // --- LEGACY PATH ---
        Log::info('🔄 updatedDate: Using LEGACY path (modular services disabled).');
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

        Log::info('🔄 updatedDate: Using FALLBACK (legacy service) path.');

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
            Log::info('🔄 loadYesterdayData: Using MODULAR services path.');
            try {
                if (!$this->recordingDataService) $this->initializeModularServices();
                $result = $this->recordingDataService->loadYesterdayData($this->livestockId, $yesterdayDate);

                if ($result->isSuccess()) {
                    Log::info('✅ loadYesterdayData: Modular data loaded successfully.');
                    $data = $result->getData();
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
                } else {
                    Log::warning('⚠️ loadYesterdayData: Modular path failed, executing fallback.', ['message' => $result->getMessage()]);
                    $this->loadYesterdayDataFallback($yesterdayDate);
                }
            } catch (Exception $e) {
                Log::critical('❌ loadYesterdayData: CRITICAL error in modular path.', ['error' => $e->getMessage()]);
                $this->loadYesterdayDataFallback($yesterdayDate);
            }
        } else {
            Log::info('🔄 loadYesterdayData: Using FALLBACK (legacy service) path.');
            $this->loadYesterdayDataFallback($yesterdayDate);
        }
    }

    /**
     * Fallback method for loading yesterday's data.
     */
    private function loadYesterdayDataFallback($yesterdayDate)
    {
        Log::info('🔄 loadYesterdayData: Using FALLBACK (legacy service) path.');

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
            Log::info('🔄 save: Using MODULAR services path.');
            try {
                if (!$this->recordingPersistenceService) $this->initializeModularServices();

                // Create a DTO to pass data to the service
                $recordingDTO = new RecordingDTO($this->all());

                $result = $this->recordingPersistenceService->saveRecording($recordingDTO);

                if ($result->isSuccess()) {
                    $this->dispatch('success', $result->getMessage());
                    // After a successful save, we should reset the form to its initial state
                    // for the next entry, but keep the livestockId and date.
                    $this->resetForm();
                    $this->dispatch('data-saved'); // To refresh table data if needed
                } else {
                    $this->dispatch('error', $result->getMessage());
                }
                return;
            } catch (Exception $e) {
                Log::critical('❌ save: CRITICAL error in modular path.', ['error' => $e->getMessage()]);
                if (!$this->enableLegacyFallback) {
                    $this->dispatch('error', 'Terjadi kesalahan sistem saat menyimpan data.');
                    return;
                }
            }

            // If we are here, it means modular service failed and fallback is enabled
            if ($this->enableLegacyFallback) {
                Log::warning('⚠️ save: Modular path failed, executing fallback.');
                $this->saveFallback();
            }
            return;
        }

        // --- LEGACY PATH ---
        Log::info('🔄 save: Using LEGACY path (modular services disabled).');
        $this->saveFallback();
    }

    private function saveFallback()
    {
        if (!$this->legacyRecordingService) {
            $this->legacyRecordingService = app(\App\Services\Recording\LegacyRecordingService::class);
        }
        $result = $this->legacyRecordingService->handleSave($this->all());

        if ($result['success']) {
            Log::info('🔄 Records Save: Resetting form and reloading data');
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
     * Get detailed unit information for a feed item
     * 
     * @param Feed $feed The feed item
     * @param float $quantity The quantity to convert
     * @return array Detailed unit information
     */
    private function getDetailedUnitInfo($feed, $quantity)
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'converted_quantity' => $quantity,
        ];

        if (!$feed) {
            return $result;
        }

        // Get unit information from feed payload
        if (isset($feed->payload['conversion_units']) && is_array($feed->payload['conversion_units'])) {
            $conversionUnits = collect($feed->payload['conversion_units']);

            // Get smallest unit (for storage)
            $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);
            if ($smallestUnit) {
                $result['smallest_unit_id'] = $smallestUnit['unit_id'];

                // Get unit name from the database
                $unit = \App\Models\Unit::find($smallestUnit['unit_id']);
                $result['smallest_unit_name'] = $unit ? $unit->name : 'Unknown';

                // Set conversion factor
                $result['conversion_factor'] = floatval($smallestUnit['value'] ?? 1);
            }

            // Get original unit (for purchase)
            $originalUnit = $conversionUnits->firstWhere('is_default_purchase', true);
            if ($originalUnit) {
                $result['original_unit_id'] = $originalUnit['unit_id'];

                // Get unit name from the database
                $unit = \App\Models\Unit::find($originalUnit['unit_id']);
                $result['original_unit_name'] = $unit ? $unit->name : 'Unknown';
            }

            // Get consumption unit (for usage)
            $consumptionUnit = $conversionUnits->firstWhere('is_default_mutation', true) ??
                $conversionUnits->firstWhere('is_smallest', true);
            if ($consumptionUnit) {
                $result['consumption_unit_id'] = $consumptionUnit['unit_id'];

                // Get unit name from the database
                $unit = \App\Models\Unit::find($consumptionUnit['unit_id']);
                $result['consumption_unit_name'] = $unit ? $unit->name : 'Unknown';

                // Calculate converted quantity
                if ($smallestUnit && $consumptionUnit) {
                    $smallestValue = floatval($smallestUnit['value'] ?? 1);
                    $consumptionValue = floatval($consumptionUnit['value'] ?? 1);

                    if ($smallestValue > 0 && $consumptionValue > 0) {
                        $result['converted_quantity'] = ($quantity * $consumptionValue) / $smallestValue;
                    }
                }
            }
        } else if ($feed->unit) {
            // Fallback to basic unit information if conversion_units not available
            $result['smallest_unit_id'] = $feed->unit->id;
            $result['smallest_unit_name'] = $feed->unit->name;
            $result['original_unit_id'] = $feed->unit->id;
            $result['original_unit_name'] = $feed->unit->name;
            $result['consumption_unit_id'] = $feed->unit->id;
            $result['consumption_unit_name'] = $feed->unit->name;
        }

        return $result;
    }

    /**
     * Get detailed stock information for a feed item
     * 
     * @param string $feedId The feed ID
     * @param string $livestockId The livestock ID
     * @return array Detailed stock information
     */
    private function getStockDetails($feedId, $livestockId)
    {
        $result = [
            'available_stocks' => [],
            'stock_origins' => [],
            'stock_purchase_dates' => [],
            'stock_prices' => [
                'min_price' => 0,
                'max_price' => 0,
                'average_price' => 0,
            ],
        ];

        // Get available stocks for the feed and livestock
        $stocks = FeedStock::with(['feedPurchase', 'feed'])
            ->where('feed_id', $feedId)
            ->where('livestock_id', $livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->get();

        if ($stocks->isEmpty()) {
            return $result;
        }

        // Prepare stock details
        $stockDetails = [];
        $prices = [];
        $origins = [];
        $purchaseDates = [];

        foreach ($stocks as $stock) {
            $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

            if ($available <= 0) {
                continue;
            }

            // Get price information
            $price = 0;
            if ($stock->feedPurchase) {
                $price = $stock->feedPurchase->price_per_converted_unit ??
                    ($stock->feedPurchase->price_per_unit ?? 0);

                $prices[] = $price;
            }

            // Get origin information
            $origin = 'Unknown';
            if ($stock->feedPurchase && $stock->feedPurchase->batch && $stock->feedPurchase->batch->supplier) {
                $origin = $stock->feedPurchase->batch->supplier->name ?? 'Unknown';
                $origins[$origin] = ($origins[$origin] ?? 0) + $available;
            }

            // Get purchase date
            $purchaseDate = $stock->date ?? ($stock->feedPurchase->batch->date ?? null);
            if ($purchaseDate) {
                $formattedDate = Carbon::parse($purchaseDate)->format('Y-m-d');
                $purchaseDates[$formattedDate] = ($purchaseDates[$formattedDate] ?? 0) + $available;
            }

            // Add stock detail
            $stockDetails[] = [
                'stock_id' => $stock->id,
                'available' => $available,
                'price' => $price,
                'origin' => $origin,
                'purchase_date' => $purchaseDate ? Carbon::parse($purchaseDate)->format('Y-m-d') : null,
                'batch_id' => $stock->feedPurchase->batch->id ?? null,
                'batch_number' => $stock->feedPurchase->batch->invoice_number ?? null,
            ];
        }

        // Calculate price statistics
        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }

        // Format stock origins and purchase dates
        foreach ($origins as $origin => $quantity) {
            $result['stock_origins'][] = [
                'origin' => $origin,
                'quantity' => $quantity,
            ];
        }

        foreach ($purchaseDates as $date => $quantity) {
            $result['stock_purchase_dates'][] = [
                'date' => $date,
                'quantity' => $quantity,
            ];
        }

        $result['available_stocks'] = $stockDetails;

        return $result;
    }

    /**
     * Get detailed unit information for a supply item
     * 
     * @param Supply $supply The supply item
     * @param float $quantity The quantity to convert
     * @return array Detailed unit information
     */
    private function getDetailedSupplyUnitInfo($supply, $quantity)
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'converted_quantity' => $quantity,
        ];

        if (!$supply) {
            return $result;
        }

        // Get unit information from supply data
        if (isset($supply->data['conversion_units']) && is_array($supply->data['conversion_units'])) {
            $conversionUnits = collect($supply->data['conversion_units']);

            // Get smallest unit (for storage)
            $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);
            if ($smallestUnit) {
                $result['smallest_unit_id'] = $smallestUnit['unit_id'];

                // Get unit name from the database
                $unit = Unit::find($smallestUnit['unit_id']);
                $result['smallest_unit_name'] = $unit ? $unit->name : 'Unknown';

                // Set conversion factor
                $result['conversion_factor'] = floatval($smallestUnit['value'] ?? 1);
            }

            // Get original unit (for purchase)
            $originalUnit = $conversionUnits->firstWhere('is_default_purchase', true);
            if ($originalUnit) {
                $result['original_unit_id'] = $originalUnit['unit_id'];

                // Get unit name from the database
                $unit = Unit::find($originalUnit['unit_id']);
                $result['original_unit_name'] = $unit ? $unit->name : 'Unknown';
            }

            // Get consumption unit (for usage)
            $consumptionUnit = $conversionUnits->firstWhere('is_default_mutation', true) ??
                $conversionUnits->firstWhere('is_smallest', true);
            if ($consumptionUnit) {
                $result['consumption_unit_id'] = $consumptionUnit['unit_id'];

                // Get unit name from the database
                $unit = Unit::find($consumptionUnit['unit_id']);
                $result['consumption_unit_name'] = $unit ? $unit->name : 'Unknown';

                // Calculate converted quantity
                if ($smallestUnit && $consumptionUnit) {
                    $smallestValue = floatval($smallestUnit['value'] ?? 1);
                    $consumptionValue = floatval($consumptionUnit['value'] ?? 1);

                    if ($smallestValue > 0 && $consumptionValue > 0) {
                        $result['converted_quantity'] = ($quantity * $consumptionValue) / $smallestValue;
                    }
                }
            }
        } else if ($supply->unit) {
            // Fallback to basic unit information if conversion_units not available
            $result['smallest_unit_id'] = $supply->unit->id;
            $result['smallest_unit_name'] = $supply->unit->name;
            $result['original_unit_id'] = $supply->unit->id;
            $result['original_unit_name'] = $supply->unit->name;
            $result['consumption_unit_id'] = $supply->unit->id;
            $result['consumption_unit_name'] = $supply->unit->name;
        }

        return $result;
    }

    /**
     * Get detailed stock information for a supply item
     * 
     * @param string $supplyId The supply ID
     * @param string $livestockId The livestock ID
     * @return array Detailed stock information
     */
    private function getSupplyStockDetails($supplyId, $livestockId)
    {
        $result = [
            'available_stocks' => [],
            'stock_origins' => [],
            'stock_purchase_dates' => [],
            'stock_prices' => [
                'min_price' => 0,
                'max_price' => 0,
                'average_price' => 0,
            ],
        ];

        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            return $result;
        }

        // Get available stocks for the supply and livestock
        $stocks = SupplyStock::with(['supplyPurchase', 'supply'])
            ->where('supply_id', $supplyId)
            ->where('farm_id', $livestock->farm_id)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->get();

        if ($stocks->isEmpty()) {
            return $result;
        }

        // Prepare stock details
        $stockDetails = [];
        $prices = [];
        $origins = [];
        $purchaseDates = [];

        foreach ($stocks as $stock) {
            $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

            if ($available <= 0) {
                continue;
            }

            // Get price information
            $price = 0;
            if ($stock->supplyPurchase) {
                $price = $stock->supplyPurchase->price_per_converted_unit ??
                    ($stock->supplyPurchase->price_per_unit ?? 0);

                $prices[] = $price;
            }

            // Get origin information
            $origin = 'Unknown';
            if ($stock->supplyPurchase && $stock->supplyPurchase->batch && $stock->supplyPurchase->batch->supplier) {
                $origin = $stock->supplyPurchase->batch->supplier->name ?? 'Unknown';
                $origins[$origin] = ($origins[$origin] ?? 0) + $available;
            }

            // Get purchase date
            $purchaseDate = $stock->date ?? ($stock->supplyPurchase->batch->date ?? null);
            if ($purchaseDate) {
                $formattedDate = Carbon::parse($purchaseDate)->format('Y-m-d');
                $purchaseDates[$formattedDate] = ($purchaseDates[$formattedDate] ?? 0) + $available;
            }

            // Add stock detail
            $stockDetails[] = [
                'stock_id' => $stock->id,
                'available' => $available,
                'price' => $price,
                'origin' => $origin,
                'purchase_date' => $purchaseDate ? Carbon::parse($purchaseDate)->format('Y-m-d') : null,
                'batch_id' => $stock->supplyPurchase->batch->id ?? null,
                'batch_number' => $stock->supplyPurchase->batch->invoice_number ?? null,
            ];
        }

        // Calculate price statistics
        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }

        // Format stock origins and purchase dates
        foreach ($origins as $origin => $quantity) {
            $result['stock_origins'][] = [
                'origin' => $origin,
                'quantity' => $quantity,
            ];
        }

        foreach ($purchaseDates as $date => $quantity) {
            $result['stock_purchase_dates'][] = [
                'date' => $date,
                'quantity' => $quantity,
            ];
        }

        $result['available_stocks'] = $stockDetails;

        return $result;
    }



    /**
     * Get detailed outflow history for a livestock
     * 
     * @param string $livestockId The livestock ID
     * @param string $date The current date
     * @return array Outflow history details
     */
    private function getDetailedOutflowHistory($livestockId, $date)
    {
        // Get all recordings except for the current date
        $recordings = Recording::where('livestock_id', $livestockId)
            ->where('tanggal', '!=', $date)
            ->get();

        $totalMortality = 0;
        $totalCulling = 0;
        $totalSales = 0;

        foreach ($recordings as $recording) {
            $payload = $recording->payload ?? [];
            $totalMortality += $payload['mortality'] ?? 0;
            $totalCulling += $payload['culling'] ?? 0;
            $totalSales += $payload['sales_quantity'] ?? 0;
        }

        $total = $totalMortality + $totalCulling + $totalSales;

        return [
            'mortality' => $totalMortality,
            'culling' => $totalCulling,
            'sales' => $totalSales,
            'total' => $total,
            'by_date' => $recordings->map(function ($recording) {
                $payload = $recording->payload ?? [];
                return [
                    'date' => $recording->tanggal,
                    'mortality' => $payload['mortality'] ?? 0,
                    'culling' => $payload['culling'] ?? 0,
                    'sales' => $payload['sales_quantity'] ?? 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get weight history for a livestock
     * 
     * @param string $livestockId The livestock ID
     * @param Carbon $currentDate The current date
     * @return array Weight history details
     */
    private function getWeightHistory($livestockId, $currentDate)
    {
        // Get all recordings up to the current date with weight data
        $recordings = Recording::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->whereNotNull('berat_hari_ini')
            ->orderBy('tanggal')
            ->get();

        $weightByDay = [];
        $weightGainByDay = [];
        $lastWeight = 0;
        $totalGain = 0;

        foreach ($recordings as $recording) {
            $date = $recording->tanggal;
            $weight = $recording->berat_hari_ini;
            $age = $recording->age;

            $gain = $weight - $lastWeight;
            if ($lastWeight > 0) {
                $totalGain += $gain;
                $weightGainByDay[] = [
                    'date' => $date,
                    'gain' => $gain,
                    'age' => $age,
                ];
            }

            $weightByDay[] = [
                'date' => $date,
                'weight' => $weight,
                'age' => $age,
            ];

            $lastWeight = $weight;
        }

        return [
            'initial_weight' => $recordings->first() ? $recordings->first()->berat_hari_ini : 0,
            'latest_weight' => $lastWeight,
            'total_gain' => $totalGain,
            'average_daily_gain' => count($weightGainByDay) > 0 ? $totalGain / count($weightGainByDay) : 0,
            'weights' => $weightByDay,
            'gains' => $weightGainByDay,
        ];
    }

    /**
     * Get feed consumption history for a livestock
     * 
     * @param string $livestockId The livestock ID
     * @param Carbon $currentDate The current date
     * @return array Feed consumption history
     */
    private function getFeedConsumptionHistory($livestockId, $currentDate)
    {
        // Get all feed usages up to the current date
        $feedUsages = FeedUsage::with('details')
            ->where('livestock_id', $livestockId)
            ->where('usage_date', '<', $currentDate->format('Y-m-d'))
            ->orderBy('usage_date')
            ->get();

        $feedByDay = [];
        $feedByType = [];
        $totalConsumption = 0;

        foreach ($feedUsages as $usage) {
            $date = $usage->usage_date->format('Y-m-d');
            $dailyConsumption = $usage->details->sum('quantity_taken');
            $totalConsumption += $dailyConsumption;

            // Group by day
            if (!isset($feedByDay[$date])) {
                $feedByDay[$date] = 0;
            }
            $feedByDay[$date] += $dailyConsumption;

            // Group by feed type
            foreach ($usage->details as $detail) {
                $feedId = $detail->feedStock->feed_id ?? null;
                if (!$feedId) continue;

                $feedName = $detail->feedStock->feed->name ?? 'Unknown';

                if (!isset($feedByType[$feedName])) {
                    $feedByType[$feedName] = 0;
                }
                $feedByType[$feedName] += $detail->quantity_taken;
            }
        }

        // Format for output
        $formattedFeedByDay = [];
        foreach ($feedByDay as $date => $amount) {
            $formattedFeedByDay[] = [
                'date' => $date,
                'amount' => $amount,
            ];
        }

        $formattedFeedByType = [];
        foreach ($feedByType as $type => $amount) {
            $formattedFeedByType[] = [
                'type' => $type,
                'amount' => $amount,
            ];
        }

        return [
            'cumulative_feed_consumption' => $totalConsumption,
            'feed_by_day' => $formattedFeedByDay,
            'feed_by_type' => $formattedFeedByType,
            'average_daily_consumption' => count($formattedFeedByDay) > 0 ? $totalConsumption / count($formattedFeedByDay) : 0,
        ];
    }

    /**
     * Calculate performance metrics for the livestock
     * 
     * @param int $age Current age in days
     * @param int $currentPopulation Current population
     * @param int $initialPopulation Initial population
     * @param float $currentWeight Current weight
     * @param float $totalFeedConsumption Total feed consumption
     * @param int $totalDepleted Total depleted birds
     * @return array Performance metrics
     */
    private function calculatePerformanceMetrics($age, $currentPopulation, $initialPopulation, $currentWeight, $totalFeedConsumption, $totalDepleted)
    {
        // Calculate liveability
        $liveability = $initialPopulation > 0 ? ($currentPopulation / $initialPopulation) * 100 : 0;

        // Calculate mortality rate
        $mortalityRate = $initialPopulation > 0 ? (($initialPopulation - $currentPopulation) / $initialPopulation) * 100 : 0;

        // Calculate FCR (Feed Conversion Ratio)
        $fcr = 0;
        if ($currentWeight > 0 && $currentPopulation > 0) {
            $totalWeight = $currentWeight * $currentPopulation;
            $fcr = $totalFeedConsumption > 0 ? $totalFeedConsumption / $totalWeight : 0;
        }

        // Calculate Feed Intake
        $feedIntake = $currentPopulation > 0 ? $totalFeedConsumption / $currentPopulation : 0;

        // Calculate ADG (Average Daily Gain)
        $adg = $age > 0 ? $currentWeight / $age : 0;

        // Calculate IP (Performance Index)
        $ip = 0;
        if ($age > 0 && $fcr > 0) {
            $ip = ($liveability * $currentWeight * 100) / ($age * $fcr);
        }

        return [
            'liveability' => round($liveability, 2),
            'mortality_rate' => round($mortalityRate, 2),
            'fcr' => round($fcr, 3),
            'feed_intake' => round($feedIntake, 2),
            'adg' => round($adg, 3),
            'ip' => round($ip, 2),
            'weight_per_age' => $age > 0 ? round($currentWeight / $age, 3) : 0,
            'feed_per_day' => $age > 0 ? round($totalFeedConsumption / $age, 2) : 0,
            'depletion_per_day' => $age > 0 ? round($totalDepleted / $age, 2) : 0,
        ];
    }

    /**
     * Save feed usage with enhanced tracking
     * 
     * @param array $data The validated data
     * @param string $recordingId The recording ID for relation
     * @return \App\Models\FeedUsage The feed usage record
     */
    private function saveFeedUsageWithTracking($data, $recordingId)
    {
        if ($this->feedUsageId) {
            // UPDATE - Handle existing feed usage
            $usage = FeedUsage::findOrFail($this->feedUsageId);
            $this->hasChanged = $this->hasUsageChanged($usage, $this->usages);

            if (!$this->hasChanged) {
                return $usage; // No changes, no need to update
            }

            // Ensure valid usage date
            $earliestStockDate = FeedStock::where('livestock_id', $this->livestockId)->min('date');
            if ($earliestStockDate && $this->date < $earliestStockDate) {
                throw new \Exception("Feed usage date must be after the earliest stock entry date ({$earliestStockDate})");
            }

            // Update usage record with enhanced tracking
            $usage->update([
                'usage_date' => $this->date,
                'livestock_id' => $this->livestockId,
                'recording_id' => $recordingId, // Link to recording for traceability
                'total_quantity' => array_sum(array_column($this->usages, 'quantity')),
                'metadata' => [
                    'feed_types' => array_column($this->usages, 'feed_name'),
                    'feed_codes' => array_column($this->usages, 'feed_code'),
                    'unit_details' => array_map(function ($item) {
                        return [
                            'unit_id' => $item['unit_id'],
                            'unit_name' => $item['unit_name'],
                            'original_unit_id' => $item['original_unit_id'],
                            'original_unit_name' => $item['original_unit_name'],
                        ];
                    }, $this->usages),
                    'updated_at' => now()->toIso8601String(),
                    'updated_by' => Auth::id(),
                    'updated_by_name' => Auth::user()->name ?? 'Unknown User',
                ],
                'updated_by' => Auth::id(),
            ]);

            // Revert old details with detailed tracking
            $oldDetails = FeedUsageDetail::where('feed_usage_id', $usage->id)->get();

            Log::info("Reverting {$oldDetails->count()} feed usage details for usage ID {$usage->id}");

            // Track changes for CurrentSupply update
            $currentSupplyChanges = [];

            foreach ($oldDetails as $detail) {
                $stock = FeedStock::find($detail->feed_stock_id);
                if ($stock) {
                    // Store reversion details for audit trail
                    Log::info("Reverting feed stock usage", [
                        'stock_id' => $stock->id,
                        'feed_id' => $stock->feed_id,
                        'old_quantity_used' => $stock->quantity_used,
                        'quantity_to_revert' => $detail->quantity_taken,
                        'new_quantity_used' => max(0, $stock->quantity_used - $detail->quantity_taken),
                        'detail_id' => $detail->id,
                    ]);

                    // Revert the used quantity
                    $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                    $stock->save();

                    // Track changes for CurrentSupply
                    if (!isset($currentSupplyChanges[$stock->feed_id])) {
                        $currentSupplyChanges[$stock->feed_id] = 0;
                    }
                    $currentSupplyChanges[$stock->feed_id] += $detail->quantity_taken;
                }

                // Archive the detail instead of hard deleting
                $detail->update([
                    'status' => 'reverted',
                    'metadata' => [
                        'reverted_at' => now()->toIso8601String(),
                        'reverted_by' => Auth::id(),
                        'reverted_by_name' => Auth::user()->name ?? 'Unknown User',
                        'reason' => 'Updated feed usage',
                    ],
                    'updated_by' => Auth::id(),
                ]);

                // Then delete
                $detail->delete();
            }

            // Update CurrentSupply for reverted quantities
            foreach ($currentSupplyChanges as $feedId => $quantity) {
                $currentSupply = CurrentSupply::where('livestock_id', $this->livestockId)
                    ->where('item_id', $feedId)
                    ->first();

                if ($currentSupply) {
                    $oldQuantity = $currentSupply->quantity;
                    $currentSupply->quantity += $quantity;
                    $currentSupply->save();

                    Log::info("Updated CurrentSupply after reversion", [
                        'livestock_id' => $this->livestockId,
                        'feed_id' => $feedId,
                        'old_quantity' => $oldQuantity,
                        'added_quantity' => $quantity,
                        'new_quantity' => $currentSupply->quantity
                    ]);
                }
            }
        } else {
            // CREATE - Create new feed usage with enhanced tracking
            $earliestStockDate = FeedStock::where('livestock_id', $this->livestockId)->min('date');
            if ($earliestStockDate && $this->date < $earliestStockDate) {
                throw new \Exception("Feed usage date must be after the earliest stock entry date ({$earliestStockDate})");
            }

            // Create new usage record with enhanced metadata
            $usage = FeedUsage::create([
                'usage_date' => $this->date,
                'livestock_id' => $this->livestockId,
                'recording_id' => $recordingId, // Link to recording for traceability
                'total_quantity' => array_sum(array_column($this->usages, 'quantity')),
                'metadata' => [
                    'feed_types' => array_column($this->usages, 'feed_name'),
                    'feed_codes' => array_column($this->usages, 'feed_code'),
                    'unit_details' => array_map(function ($item) {
                        return [
                            'unit_id' => $item['unit_id'],
                            'unit_name' => $item['unit_name'],
                            'original_unit_id' => $item['original_unit_id'],
                            'original_unit_name' => $item['original_unit_name'],
                        ];
                    }, $this->usages),
                    'created_at' => now()->toIso8601String(),
                    'created_by' => Auth::id(),
                    'created_by_name' => Auth::user()->name ?? 'Unknown User',
                ],
                'created_by' => Auth::id(),
            ]);
        }

        // Process the feed usage using FIFO with enhanced metadata
        $processResult = app(\App\Services\FeedUsageService::class)->processWithMetadata($usage, $this->usages);

        // Update CurrentSupply for new usage
        foreach ($this->usages as $usageData) {
            $currentSupply = CurrentSupply::where('livestock_id', $this->livestockId)
                ->where('item_id', $usageData['feed_id'])
                ->first();

            if ($currentSupply) {
                $oldQuantity = $currentSupply->quantity;
                $currentSupply->quantity -= $usageData['quantity'];
                $currentSupply->save();

                Log::info("Updated CurrentSupply for new usage", [
                    'livestock_id' => $this->livestockId,
                    'feed_id' => $usageData['feed_id'],
                    'old_quantity' => $oldQuantity,
                    'used_quantity' => $usageData['quantity'],
                    'new_quantity' => $currentSupply->quantity
                ]);
            }
        }

        Log::info("Feed usage processed", [
            'usage_id' => $usage->id,
            'livestock_id' => $usage->livestock_id,
            'date' => $usage->usage_date,
            'total_quantity' => $usage->total_quantity,
            'details_count' => $processResult['details_count'] ?? 0,
            'feeds_processed' => $processResult['feeds_processed'] ?? [],
        ]);

        // Return the usage record for further processing
        return $usage;
    }

    /**
     * Save supply usage with proper model structure
     * 
     * @param array $data The validated data
     * @param string $recordingId The recording ID for relation
     * @return \App\Models\SupplyUsage The supply usage record
     */
    private function saveSupplyUsageWithTracking($data, $recordingId)
    {
        if ($this->supplyUsageId) {
            // UPDATE - Handle existing supply usage
            $usage = SupplyUsage::findOrFail($this->supplyUsageId);
            $this->hasSupplyChanged = $this->hasSupplyUsageChanged($usage, $this->supplyUsages);

            if (!$this->hasSupplyChanged) {
                return $usage; // No changes, no need to update
            }

            // Ensure valid usage date
            $livestock = Livestock::find($this->livestockId);
            $earliestStockDate = SupplyStock::where('farm_id', $livestock->farm_id)->min('date');
            if ($earliestStockDate && $this->date < $earliestStockDate) {
                throw new \Exception("Supply usage date must be after the earliest stock entry date ({$earliestStockDate})");
            }

            // Update usage record according to model structure
            $usage->update([
                'usage_date' => $this->date,
                'livestock_id' => $this->livestockId,
                'total_quantity' => array_sum(array_column($this->supplyUsages, 'quantity')),
                'updated_by' => Auth::id(),
            ]);

            // Revert old details
            $oldDetails = SupplyUsageDetail::where('supply_usage_id', $usage->id)->get();

            Log::info("Reverting {$oldDetails->count()} supply usage details for usage ID {$usage->id}");

            // Track changes for CurrentSupply update
            $currentSupplyChanges = [];

            foreach ($oldDetails as $detail) {
                $stock = SupplyStock::find($detail->supply_stock_id);
                if ($stock) {
                    // Store reversion details for audit trail
                    Log::info("Reverting supply stock usage", [
                        'stock_id' => $stock->id,
                        'supply_id' => $stock->supply_id,
                        'old_quantity_used' => $stock->quantity_used,
                        'quantity_to_revert' => $detail->quantity_taken,
                        'new_quantity_used' => max(0, $stock->quantity_used - $detail->quantity_taken),
                        'detail_id' => $detail->id,
                    ]);

                    // Revert the used quantity
                    $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                    $stock->save();

                    // Track changes for CurrentSupply
                    if (!isset($currentSupplyChanges[$stock->supply_id])) {
                        $currentSupplyChanges[$stock->supply_id] = 0;
                    }
                    $currentSupplyChanges[$stock->supply_id] += $detail->quantity_taken;
                }

                // Delete the detail
                $detail->delete();
            }

            // Update CurrentSupply for reverted quantities
            foreach ($currentSupplyChanges as $supplyId => $quantity) {
                $currentSupply = CurrentSupply::where('livestock_id', $this->livestockId)
                    ->where('item_id', $supplyId)
                    ->first();

                if ($currentSupply) {
                    $oldQuantity = $currentSupply->quantity;
                    $currentSupply->quantity += $quantity;
                    $currentSupply->save();

                    Log::info("Updated CurrentSupply after supply reversion", [
                        'livestock_id' => $this->livestockId,
                        'supply_id' => $supplyId,
                        'old_quantity' => $oldQuantity,
                        'added_quantity' => $quantity,
                        'new_quantity' => $currentSupply->quantity
                    ]);
                }
            }
        } else {
            // CREATE - Create new supply usage according to model structure
            $livestock = Livestock::find($this->livestockId);
            $earliestStockDate = SupplyStock::where('farm_id', $livestock->farm_id)->min('date');
            if ($earliestStockDate && $this->date < $earliestStockDate) {
                throw new \Exception("Supply usage date must be after the earliest stock entry date ({$earliestStockDate})");
            }

            // Create new usage record with correct fields
            $usage = SupplyUsage::create([
                'usage_date' => $this->date,
                'livestock_id' => $this->livestockId,
                'total_quantity' => array_sum(array_column($this->supplyUsages, 'quantity')),
                'created_by' => Auth::id(),
            ]);
        }

        // Process each supply usage and create details
        foreach ($this->supplyUsages as $usageData) {
            $this->processSupplyUsageDetail($usage, $usageData);
        }

        Log::info("Supply usage processed", [
            'usage_id' => $usage->id,
            'livestock_id' => $usage->livestock_id,
            'date' => $usage->usage_date,
            'total_quantity' => $usage->total_quantity,
            'supplies_count' => count($this->supplyUsages),
        ]);

        // Return the usage record for further processing
        return $usage;
    }

    /**
     * Process individual supply usage detail with FIFO
     */
    private function processSupplyUsageDetail($usage, $usageData)
    {
        $livestock = Livestock::find($this->livestockId);
        $quantityNeeded = $usageData['quantity'];

        // Get available stocks using FIFO (oldest first)
        $availableStocks = SupplyStock::where('farm_id', $livestock->farm_id)
            ->where('supply_id', $usageData['supply_id'])
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        foreach ($availableStocks as $stock) {
            if ($quantityNeeded <= 0) break;

            $availableInStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $quantityToTake = min($quantityNeeded, $availableInStock);

            if ($quantityToTake > 0) {
                // Create supply usage detail
                SupplyUsageDetail::create([
                    'supply_usage_id' => $usage->id,
                    'supply_id' => $usageData['supply_id'],
                    'supply_stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'created_by' => Auth::id(),
                ]);

                // Update stock quantity used
                $stock->quantity_used += $quantityToTake;
                $stock->save();

                // Update CurrentSupply
                $currentSupply = CurrentSupply::where('livestock_id', $this->livestockId)
                    ->where('item_id', $usageData['supply_id'])
                    ->first();

                if ($currentSupply) {
                    $currentSupply->quantity -= $quantityToTake;
                    $currentSupply->save();
                }

                $quantityNeeded -= $quantityToTake;

                Log::info("Supply usage detail created", [
                    'usage_id' => $usage->id,
                    'supply_id' => $usageData['supply_id'],
                    'stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'remaining_needed' => $quantityNeeded
                ]);
            }
        }

        if ($quantityNeeded > 0) {
            Log::warning("Insufficient stock for supply usage", [
                'supply_id' => $usageData['supply_id'],
                'requested' => $usageData['quantity'],
                'shortage' => $quantityNeeded
            ]);
        }
    }

    /**
     * Store depletion with detailed tracking and proper delta calculation
     * User input is treated as absolute values, system calculates delta
     * 
     * @param string $jenis Type of depletion ('mortality' or 'culling')
     * @param int $jumlah The NEW TOTAL quantity for the day (absolute value)
     * @param string $recordingId Recording ID for relation
     * @return \App\Models\LivestockDepletion
     */
    private function storeDeplesiWithDetailsFallback($jenis, $jumlah, $recordingId)
    {
        // Normalize depletion type using config
        $normalizedType = LivestockDepletionConfig::normalize($jenis);
        $legacyType = LivestockDepletionConfig::toLegacy($normalizedType);

        Log::info('📝 StoreDeplesi: Method called with delta calculation', [
            'original_type' => $jenis,
            'normalized_type' => $normalizedType,
            'legacy_type' => $legacyType,
            'new_total_quantity' => $jumlah,
            'recording_id' => $recordingId,
            'livestock_id' => $this->livestockId
        ]);

        if ($jumlah < 0) {
            Log::error('❌ StoreDeplesi: Negative quantity not allowed', ['quantity' => $jumlah]);
            throw new \Exception("Jumlah deplesi tidak boleh negatif: {$jumlah}");
        }

        $livestock = Livestock::find($this->livestockId);
        if (!$livestock) {
            Log::error('❌ StoreDeplesi: Livestock not found', ['livestock_id' => $this->livestockId]);
            return null;
        }

        Log::info('✅ StoreDeplesi: Livestock found', [
            'livestock_name' => $livestock->name ?? 'Unknown',
            'farm_id' => $livestock->farm_id
        ]);

        $currentDate = Carbon::parse($this->date);
        $age = $livestock ? $currentDate->diffInDays(Carbon::parse($livestock->start_date)) : null;

        // --- DELTA CALCULATION ---
        // Find the current total depletion for this type and date
        $oldJumlah = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->where('tanggal', $this->date)
            ->where('jenis', $normalizedType)
            ->sum('jumlah');

        $delta = $jumlah - $oldJumlah;

        Log::info("🔢 Calculating depletion delta", [
            'new_total' => $jumlah,
            'old_total' => $oldJumlah,
            'delta' => $delta,
            'livestock_id' => $this->livestockId,
            'date' => $this->date,
            'type' => $normalizedType
        ]);

        // --- UPDATE DATABASE RECORD ---
        // Always update/create the record with the new absolute value
        $deplesi = LivestockDepletion::updateOrCreate(
            [
                'livestock_id' => $this->livestockId,
                'tanggal' => $this->date,
                'jenis' => $normalizedType, // Use normalized type for consistency
            ],
            [
                'jumlah' => $jumlah, // Set the NEW ABSOLUTE total
                'recording_id' => $recordingId, // Link to recording for traceability
                'method' => 'traditional',
                'metadata' => [
                    // Basic livestock information
                    'livestock_name' => $livestock->name ?? 'Unknown',
                    'farm_id' => $livestock->farm_id ?? null,
                    'farm_name' => $livestock->farm->name ?? 'Unknown',
                    'coop_id' => $livestock->coop_id ?? null,
                    'kandang_name' => $livestock->kandang->name ?? 'Unknown',
                    'age_days' => $age,

                    // Recording information
                    'recording_id' => $recordingId,
                    'updated_at' => now()->toIso8601String(),
                    'updated_by' => Auth::id(),
                    'updated_by_name' => Auth::user()->name ?? 'Unknown User',

                    // Method information
                    'depletion_method' => 'traditional',
                    'processing_method' => 'records_component',
                    'source_component' => 'Records',

                    // Delta calculation metadata
                    'delta_calculation' => [
                        'old_value' => $oldJumlah,
                        'new_value' => $jumlah,
                        'delta' => $delta,
                        'user_input_type' => 'absolute',
                        'calculation_method' => 'delta_based_v2'
                    ],

                    // Config-related metadata
                    'depletion_config' => [
                        'original_type' => $jenis,
                        'normalized_type' => $normalizedType,
                        'legacy_type' => $legacyType,
                        'config_version' => '1.0',
                        'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                        'category' => LivestockDepletionConfig::getCategory($normalizedType)
                    ]
                ],
                'data' => [
                    'depletion_method' => 'traditional',
                    'original_request' => $jumlah,
                    'processing_source' => 'Records Component',
                    'batch_processing' => false,
                    'single_record' => true,
                    'delta_info' => [
                        'old_value' => $oldJumlah,
                        'new_value' => $jumlah,
                        'delta' => $delta,
                        'calculation_timestamp' => now()->toIso8601String()
                    ]
                ],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]
        );

        Log::info("📝 Recorded livestock depletion with delta calculation", [
            'livestock_id' => $this->livestockId,
            'date' => $this->date,
            'type' => $normalizedType,
            'old_quantity' => $oldJumlah,
            'new_quantity' => $jumlah,
            'delta' => $delta,
            'recording_id' => $recordingId,
            'method' => 'traditional',
            'depletion_record_id' => $deplesi->id
        ]);

        // Note: The Observer (LivestockDepletionObserver) will handle the quantity updates
        // based on the new formula, so we don't need to manually update quantities here.
        // The Observer will detect the model changes and recalculate everything automatically.

        return $deplesi;
    }

    /**
     * Standardize FIFO depletion records to match traditional format
     * This ensures consistent metadata and data structure across all depletion methods
     *
     * @param array $fifoResult The FIFO depletion result
     * @param Livestock $livestock The livestock instance
     * @param string $jenis The depletion type
     * @param string $recordingId The recording ID
     * @param int $age The livestock age in days
     * @return void
     */
    private function standardizeFifoDepletionRecords(array $fifoResult, Livestock $livestock, string $jenis, string $recordingId, int $age): void
    {
        try {
            // Normalize depletion type using config
            $normalizedType = LivestockDepletionConfig::normalize($jenis);
            $legacyType = LivestockDepletionConfig::toLegacy($normalizedType);

            // Get depletion records from FIFO result
            $depletionRecords = $fifoResult['depletion_records'] ?? [];

            foreach ($depletionRecords as $recordData) {
                // Find the actual depletion record
                $depletionRecord = null;

                if (isset($recordData['depletion_id'])) {
                    $depletionRecord = LivestockDepletion::find($recordData['depletion_id']);
                } elseif (isset($recordData['livestock_depletion_id'])) {
                    $depletionRecord = LivestockDepletion::find($recordData['livestock_depletion_id']);
                }

                if (!$depletionRecord) {
                    continue;
                }

                // Prepare standardized metadata that matches traditional format
                $standardizedMetadata = [
                    // Basic livestock information
                    'livestock_name' => $livestock->name ?? 'Unknown',
                    'farm_id' => $livestock->farm_id ?? null,
                    'farm_name' => $livestock->farm->name ?? 'Unknown',
                    'coop_id' => $livestock->coop_id ?? null,
                    'kandang_name' => $livestock->kandang->name ?? 'Unknown',
                    'age_days' => $age,

                    // Recording information
                    'recording_id' => $recordingId,
                    'updated_at' => now()->toIso8601String(),
                    'updated_by' => Auth::id(),
                    'updated_by_name' => Auth::user()->name ?? 'Unknown User',

                    // Method information
                    'depletion_method' => 'fifo',
                    'processing_method' => 'fifo_depletion_service',
                    'source_component' => 'Records',

                    // Config-related metadata (consistent with traditional)
                    'depletion_config' => [
                        'original_type' => $jenis,
                        'normalized_type' => $normalizedType,
                        'legacy_type' => $legacyType,
                        'config_version' => '1.0',
                        'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                        'category' => LivestockDepletionConfig::getCategory($normalizedType)
                    ],

                    // FIFO-specific metadata
                    'fifo_metadata' => [
                        'batch_id' => $recordData['batch_id'] ?? null,
                        'batch_name' => $recordData['batch_name'] ?? null,
                        'batch_start_date' => $recordData['batch_start_date'] ?? null,
                        'quantity_depleted' => $recordData['quantity'] ?? 0,
                        'remaining_in_batch' => $recordData['remaining_quantity'] ?? 0,
                        'batch_sequence' => $recordData['sequence'] ?? 1
                    ]
                ];

                // Prepare standardized data that matches traditional format
                $standardizedData = [
                    'batch_id' => $recordData['batch_id'] ?? null,
                    'batch_name' => $recordData['batch_name'] ?? null,
                    'batch_start_date' => $recordData['batch_start_date'] ?? null,
                    'depletion_method' => 'fifo',
                    'original_request' => $recordData['quantity'] ?? 0,
                    'available_in_batch' => $recordData['remaining_quantity'] ?? 0,
                    'fifo_sequence' => $recordData['sequence'] ?? 1,
                    'total_batches_affected' => $fifoResult['batches_affected'] ?? 1,
                    'distribution_summary' => $fifoResult['distribution_summary'] ?? []
                ];

                // Update the depletion record with standardized format
                $depletionRecord->update([
                    'jenis' => $normalizedType, // Use normalized type for consistency
                    'recording_id' => $recordingId,
                    'method' => 'fifo',
                    'metadata' => $standardizedMetadata,
                    'data' => $standardizedData,
                    'updated_by' => Auth::id()
                ]);

                Log::info('📝 Standardized FIFO depletion record', [
                    'depletion_id' => $depletionRecord->id,
                    'livestock_id' => $livestock->id,
                    'jenis' => $normalizedType,
                    'quantity' => $depletionRecord->jumlah,
                    'batch_id' => $recordData['batch_id'] ?? null,
                    'method' => 'fifo'
                ]);
            }

            Log::info('✅ FIFO depletion records standardized successfully', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'records_processed' => count($depletionRecords),
                'batches_affected' => $fifoResult['batches_affected'] ?? 0
            ]);
        } catch (Exception $e) {
            Log::error('❌ Failed to standardize FIFO depletion records', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            Log::error('❌ FIFO Stats: Failed to get FIFO depletion statistics', [
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
            Log::warning('⚠️ Livestock or CurrentLivestock not found', [
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

            Log::info("📊 Updated livestock quantities (consistent formula)", [
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
        Log::info('Recording saved', [
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

        Log::info('📝 Form reset after successful save.');
    }
}
