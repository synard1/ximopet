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
use Exception;

use App\Models\Recording; // Assuming this is the model for the recordings
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

use App\Services\Recording\RecordingService;
use App\Services\StocksService;
use App\Services\FIFOService;
use App\Services\TernakService;
use App\Services\Livestock\LivestockCostService;
use App\Services\Livestock\FIFODepletionService;
use App\Services\Recording\RecordingMethodValidationService;
use App\Services\Recording\RecordingMethodTransitionHelper;
use App\Config\LivestockDepletionConfig;


use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\LivestockSalesItem;
use App\Models\CurrentSupply;

// OVK/Supply related imports
use App\Models\Supply;
use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Unit;
use App\Models\Company;

use App\Traits\HasFifoDepletion;

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
    protected ?RecordingService $recordingService = null;

    public function mount(
        StocksService $stocksService,
        FIFOService $fifoService,
        FIFODepletionService $fifoDepletionService,
        RecordingMethodValidationService $validationService,
        RecordingMethodTransitionHelper $transitionHelper,
        RecordingService $recordingService
    ) {
        $this->stocksService = $stocksService;
        $this->fifoService = $fifoService;
        $this->fifoDepletionService = $fifoDepletionService;
        $this->validationService = $validationService;
        $this->transitionHelper = $transitionHelper;
        $this->recordingService = $recordingService;
        $this->initializeItemQuantities();
        $this->initializeSupplyItems();
        $this->loadRecordingData();
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
        $this->loadStockData();
        $this->initializeItemQuantities();
        $this->loadAvailableSupplies();
        $this->initializeSupplyItems();
        $this->checkCurrentLivestockStock();
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

    /**
     * Store sales data for a recording
     *
     * @param int $livestockId The ID of the ternak
     * @param string $date The date of the recording
     * @param int $salesQuantity The quantity of sales
     * @param float $salesPrice The price per unit
     * @param float $totalSales The total sales amount
     * @return bool Whether the operation was successful
     */
    // private function storePenjualan($livestockId, $date, $salesQuantity, $salesPrice, $totalSales)
    // {
    //     try {
    //         // Find the recording for the given ternak and date
    //         $recording = Recording::where('livestock_id', $livestockId)
    //                             ->whereDate('tanggal', $date)
    //                             ->first();

    //         if (!$recording) {
    //             // If no recording exists, create a new one with just the sales data
    //             $recording = new Recording();
    //             $recording->livestock_id = $livestockId;
    //             $recording->tanggal = $date;
    //             $recording->created_by = auth()->id();
    //         }

    //         // Update the sales-related fields
    //         $recording->sales_quantity = $salesQuantity;
    //         $recording->sales_price = $salesPrice;
    //         $recording->total_sales = $totalSales;
    //         $recording->updated_by = auth()->id();

    //         // Save the recording
    //         $recording->save();

    //         // Update the current ternak quantity to reflect the sales
    //         $this->updateCurrentLivestockQuantityAfterSales($livestockId, $salesQuantity);

    //         return true;
    //     } catch (\Exception $e) {
    //         Log::error('Error storing sales data: ' . $e->getMessage());
    //         return false;
    //     }
    // }

    /**
     * Update the current ternak quantity after sales
     *
     * @param int $livestockId The ID of the ternak
     * @param int $salesQuantity The quantity of sales
     * @return void
     */
    // private function updateCurrentLivestockQuantityAfterSales($livestockId, $salesQuantity)
    // {
    //     $currentLivestock = CurrentLivestock::where('livestock_id', $livestockId)->first();

    //     if ($currentLivestock) {
    //         $currentLivestock->quantity -= $salesQuantity;
    //         $currentLivestock->save();
    //     }
    // }

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





    // public function checkStockByTernakId($livestockId)
    // {
    //     $currentStocks = CurrentStock::where('livestock_id', $livestockId)->get();

    //     if ($currentStocks->isEmpty()) {
    //         return ;
    //         // return collect([
    //         //     [
    //         //         'livestock_id' => $livestockId,
    //         //         'stock' => 0,
    //         //         'message' => 'No stock found for this livestock_id.',
    //         //     ],
    //         // ]);
    //     }

    //     return $currentStocks->map(function ($currentStock) use ($livestockId) {
    //         return [
    //             'livestock_id' => $livestockId,
    //             'item_id' => $currentStock->item_id,
    //             'item_name' => $currentStock->item->name,
    //             'stock' => $currentStock->quantity
    //         ];
    //     });
    // }

    // private function loadStockData()
    // {
    //     $stockCheck = $this->checkStockByTernakId($this->livestockId);

    //     // dd($stockCheck);
    //     // collect() handles null input, returning an empty collection
    //     if (collect($stockCheck)->isEmpty()) {
    //         // The items array is null or empty
    //         // logger('Items collection is empty.');
    //         $this->dispatch('noSubmit');
    //         $this->dispatch('error', 'Batch Ayam belum memiliki data stok');

    //         return;
    //     }

    //     if (empty($stockCheck)) {
    //         $this->items = [];
    //         $this->itemQuantities = [];
    //         return;
    //     }

    //     $this->items = $stockCheck;

    //     // Initialize quantities
    //     foreach ($this->items as $item) {
    //         $this->itemQuantities[$item['item_id']] = 0;
    //     }
    // }

    // private function initializeItemQuantities()
    // {
    //     foreach ($this->items as $item) {
    //         if (!isset($this->itemQuantities[$item['item_id']])) {
    //             $this->itemQuantities[$item['item_id']] = 0;
    //         }
    //     }
    // }

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

        // $currentLivestock = CurrentLivestock::where('livestock_id', $this->livestockId)
        //     ->select('quantity as stock_akhir', 'livestock_id')
        //     ->with(['livestock' => function ($query) {
        //         $query->select('id', 'name', 'start_date', 'populasi_awal');
        //     }])
        //     ->first();

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

        // --- Fetch Deplesi Data for the selected date (with config normalization) ---
        $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->whereDate('tanggal', $value)
            ->get()
            ->map(function ($item) {
                // Normalize depletion types for consistency using the new config system
                $item->normalized_type = LivestockDepletionConfig::normalize($item->jenis);
                $item->display_name = LivestockDepletionConfig::getDisplayName($item->jenis, true);
                $item->category = LivestockDepletionConfig::getCategory($item->normalized_type);
                return $item;
            });

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



        // Update Deplesi fields (with config-based normalization)
        if ($deplesi->isNotEmpty()) {
            // Use config-based normalization for backward compatibility
            $mortalityTypes = [
                LivestockDepletionConfig::LEGACY_TYPE_MATI,
                LivestockDepletionConfig::TYPE_MORTALITY
            ];
            $cullingTypes = [
                LivestockDepletionConfig::LEGACY_TYPE_AFKIR,
                LivestockDepletionConfig::TYPE_CULLING
            ];

            // Calculate using both legacy and standard types for full compatibility
            $this->deplesiData = [
                'mortality' => $deplesi->filter(function ($item) use ($mortalityTypes) {
                    return in_array($item->jenis, $mortalityTypes) ||
                        in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_MORTALITY]);
                })->sum('jumlah'),
                'culling' => $deplesi->filter(function ($item) use ($cullingTypes) {
                    return in_array($item->jenis, $cullingTypes) ||
                        in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_CULLING]);
                })->sum('jumlah')
            ];
            $this->mortality = $this->deplesiData['mortality'];
            $this->culling = $this->deplesiData['culling'];

            Log::info('Current date depletion processed with config system', [
                'livestock_id' => $this->livestockId,
                'selected_date' => $value,
                'total_records' => $deplesi->count(),
                'mortality_found' => $this->mortality,
                'culling_found' => $this->culling,
                'types_found' => $deplesi->pluck('jenis')->unique()->toArray(),
                'normalized_types' => $deplesi->pluck('normalized_type')->unique()->toArray()
            ]);
        } else {
            $this->deplesiData = null;
            $this->mortality = 0;
            $this->culling = 0;

            Log::info('No current date depletion data found', [
                'livestock_id' => $this->livestockId,
                'selected_date' => $value
            ]);
        }

        // Update Total Deplesi (recalculate based on all-time data with config normalization)
        $allDeplesi = LivestockDepletion::where('livestock_id', $this->livestockId)->get();
        $this->total_deplesi = $allDeplesi->sum('jumlah');

        // Also update the value in currentLivestockStock if needed (with config normalization)
        if ($this->currentLivestockStock) {
            // Use config system for backward compatibility
            $allMortalityTypes = [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY];
            $allCullingTypes = [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING];

            $this->currentLivestockStock['mortality'] = $allDeplesi->whereIn('jenis', $allMortalityTypes)->sum('jumlah');
            $this->currentLivestockStock['culling'] = $allDeplesi->whereIn('jenis', $allCullingTypes)->sum('jumlah');
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

        try {
            // --- Fetch Yesterday's Recording Data ---
            $yesterdayRecording = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $yesterdayDate)
                ->first();

            // --- Fetch Yesterday's Depletion Data (with config normalization) ---
            $yesterdayDeplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $yesterdayDate)
                ->get()
                ->map(function ($item) {
                    // Normalize depletion types for consistency using the new config system
                    $item->normalized_type = LivestockDepletionConfig::normalize($item->jenis);
                    $item->display_name = LivestockDepletionConfig::getDisplayName($item->jenis, true);
                    $item->category = LivestockDepletionConfig::getCategory($item->normalized_type);
                    return $item;
                });

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

            // Process yesterday's depletion using the new config system
            if ($yesterdayDeplesi->isNotEmpty()) {
                // Use config-based normalization for backward compatibility
                $mortalityTypes = [
                    LivestockDepletionConfig::LEGACY_TYPE_MATI,
                    LivestockDepletionConfig::TYPE_MORTALITY
                ];
                $cullingTypes = [
                    LivestockDepletionConfig::LEGACY_TYPE_AFKIR,
                    LivestockDepletionConfig::TYPE_CULLING
                ];

                // Calculate using both legacy and standard types for full compatibility
                $this->yesterday_mortality = $yesterdayDeplesi->filter(function ($item) use ($mortalityTypes) {
                    return in_array($item->jenis, $mortalityTypes) ||
                        in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_MORTALITY]);
                })->sum('jumlah');

                $this->yesterday_culling = $yesterdayDeplesi->filter(function ($item) use ($cullingTypes) {
                    return in_array($item->jenis, $cullingTypes) ||
                        in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_CULLING]);
                })->sum('jumlah');

                Log::info('Yesterday depletion processed with config system', [
                    'livestock_id' => $this->livestockId,
                    'yesterday_date' => $yesterdayDate,
                    'total_records' => $yesterdayDeplesi->count(),
                    'mortality_found' => $this->yesterday_mortality,
                    'culling_found' => $this->yesterday_culling,
                    'types_found' => $yesterdayDeplesi->pluck('jenis')->unique()->toArray(),
                    'normalized_types' => $yesterdayDeplesi->pluck('normalized_type')->unique()->toArray()
                ]);
            } else {
                $this->yesterday_mortality = 0;
                $this->yesterday_culling = 0;

                Log::info('No yesterday depletion data found', [
                    'livestock_id' => $this->livestockId,
                    'yesterday_date' => $yesterdayDate
                ]);
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

            // Determine if yesterday's depletion was managed manually
            $yesterdayManualDepletion = false;
            if ($yesterdayDeplesi->isNotEmpty()) {
                // Check if any depletion record has manual depletion metadata
                $yesterdayManualDepletion = $yesterdayDeplesi->contains(function ($item) {
                    $metadata = is_array($item->metadata) ? $item->metadata : json_decode($item->metadata ?? '{}', true);
                    return isset($metadata['depletion_method']) && $metadata['depletion_method'] === 'manual';
                });
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
                'summary' => $this->generateYesterdaySummary(),
                'is_manual_depletion' => $yesterdayManualDepletion,
                'depletion_method' => $yesterdayManualDepletion ? 'manual' : 'recording'
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
        // Add debugging log at the start
        Log::info('🚀 Records Save: Method called', [
            'livestock_id' => $this->livestockId,
            'mortality' => $this->mortality,
            'culling' => $this->culling,
            'date' => $this->date,
            'fifo_service_available' => $this->fifoDepletionService ? 'yes' : 'no'
        ]);

        // Add permission check
        // if ($this->isEditing) {
        //     if (!Auth::user()->can('update records management')) {
        //         $this->dispatch('error', 'You do not have permission to update records management.');
        //         return;
        //     }
        // } else {
        //     if (!Auth::user()->can('create records management')) {
        //         $this->dispatch('error', 'You do not have permission to create records management.');
        //         return;
        //     }
        // }

        Log::info('🔍 Records Save: Starting validation');

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
            DB::beginTransaction(); // Start a database transaction for data integrity

            // --- Prepare feed usage data with comprehensive details ---
            Log::info('📊 Records Save: Preparing feed usage data');
            $this->usages = collect($this->itemQuantities)
                ->filter(fn($qty) => $qty > 0)
                ->map(function ($qty, $itemId) {
                    $feed = Feed::with('unit')->find($itemId);

                    // Get detailed unit conversion information
                    $unitInfo = $this->getDetailedUnitInfo($feed, $qty);

                    // Get stock details for traceability
                    $stockInfo = $this->getStockDetails($itemId, $this->livestockId);

                    return [
                        'feed_id' => $itemId,
                        'quantity' => (float) $qty,
                        'feed_name' => $feed ? $feed->name : 'Unknown Feed',
                        'feed_code' => $feed ? $feed->code : 'Unknown Code',

                        // Unit information
                        'unit_id' => $unitInfo['smallest_unit_id'],
                        'unit_name' => $unitInfo['smallest_unit_name'],
                        'original_unit_id' => $unitInfo['original_unit_id'],
                        'original_unit_name' => $unitInfo['original_unit_name'],
                        'consumption_unit_id' => $unitInfo['consumption_unit_id'],
                        'consumption_unit_name' => $unitInfo['consumption_unit_name'],

                        // Conversion factors
                        'conversion_factor' => $unitInfo['conversion_factor'],
                        'converted_quantity' => $unitInfo['converted_quantity'],

                        // Stock information for audit trail
                        'available_stocks' => $stockInfo['available_stocks'],
                        'stock_origins' => $stockInfo['stock_origins'],
                        'stock_purchase_dates' => $stockInfo['stock_purchase_dates'],
                        'stock_prices' => $stockInfo['stock_prices'],

                        // Metadata
                        'category' => $feed ? $feed->category->name ?? 'Uncategorized' : 'Unknown',
                        'timestamp' => now()->toIso8601String(),
                    ];
                })
                ->values()
                ->toArray();

            // --- Prepare supply usage data ---
            Log::info('🧪 Records Save: Preparing supply usage data');
            $this->supplyUsages = collect($this->supplyQuantities)
                ->map(function ($quantity, $supplyId) {
                    if (empty($quantity) || $quantity <= 0) {
                        return null;
                    }

                    $supply = Supply::with('unit')->find($supplyId);
                    if (!$supply) {
                        return null;
                    }

                    // Get detailed unit conversion information for supply
                    $unitInfo = $this->getDetailedSupplyUnitInfo($supply, floatval($quantity));

                    // Get supply stock details for traceability
                    $stockInfo = $this->getSupplyStockDetails($supplyId, $this->livestockId);

                    return [
                        'supply_id' => $supplyId,
                        'quantity' => (float) $quantity,
                        'supply_name' => $supply->name,
                        'supply_code' => $supply->code,
                        'notes' => '', // Simple form doesn't have notes

                        // Unit information
                        'unit_id' => $unitInfo['smallest_unit_id'],
                        'unit_name' => $unitInfo['smallest_unit_name'],
                        'original_unit_id' => $unitInfo['original_unit_id'],
                        'original_unit_name' => $unitInfo['original_unit_name'],
                        'consumption_unit_id' => $unitInfo['consumption_unit_id'],
                        'consumption_unit_name' => $unitInfo['consumption_unit_name'],

                        // Conversion factors
                        'conversion_factor' => $unitInfo['conversion_factor'],
                        'converted_quantity' => $unitInfo['converted_quantity'],

                        // Stock information for audit trail
                        'available_stocks' => $stockInfo['available_stocks'],
                        'stock_origins' => $stockInfo['stock_origins'],
                        'stock_purchase_dates' => $stockInfo['stock_purchase_dates'],
                        'stock_prices' => $stockInfo['stock_prices'],

                        // Metadata
                        'category' => $supply->supplyCategory->name ?? 'Uncategorized',
                        'timestamp' => now()->toIso8601String(),
                    ];
                })
                ->filter() // Remove null values
                ->values()
                ->toArray();

            // --- Validate livestock and data structure with enhanced checks ---
            Log::info('🐄 Records Save: Validating livestock data');
            $ternak = CurrentLivestock::with(['livestock.coop', 'livestock.farm'])->where('livestock_id', $this->livestockId)->first();
            if (!$ternak || !$ternak->livestock) {
                throw new \Exception("Livestock record not found or invalid");
            }

            $populasiAwal = $ternak->livestock->initial_quantity;
            $livestockStartDate = Carbon::parse($ternak->livestock->start_date);
            $recordDate = Carbon::parse($this->date);

            // Enhanced date validation
            if ($recordDate->lt($livestockStartDate)) {
                throw new \Exception("Recording date cannot be earlier than livestock start date ({$livestockStartDate->format('Y-m-d')})");
            }

            // Additional validations for extreme future dates
            if ($recordDate->gt(Carbon::now()->addDays(1))) {
                throw new \Exception("Recording date cannot be in the future");
            }

            // --- Get detailed population history for the livestock ---
            Log::info('📈 Records Save: Getting population history');
            // dd($this->livestockId, $recordDate);
            if (!$this->recordingService) {
                // Fallback: instantiate manually
                $this->recordingService = app(\App\Services\Recording\RecordingService::class);
                if (!$this->recordingService) {
                    DB::rollBack();
                    $this->dispatch('error', 'RecordingService is not available. Please reload the page or contact admin.');
                    return;
                }
            }
            $populationHistory = $this->recordingService->getPopulationHistory($this->livestockId, $recordDate);

            // --- Validate total outflows don't exceed initial population with detailed breakdown ---
            Log::info('🔢 Records Save: Validating outflows');
            $existingRecord = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $this->date)
                ->first();

            $newOut = (int) $this->mortality + (int) $this->culling + (int) $this->sales_quantity;

            $outflowHistory = $this->getDetailedOutflowHistory($this->livestockId, $this->date);
            $totalOutExceptToday = $outflowHistory['total'];
            $totalOut = $totalOutExceptToday + $newOut;

            if ($totalOut > $populasiAwal) {
                $this->dispatch('error', "Total outflow ({$totalOut}) exceeds initial population ({$populasiAwal}). Breakdown: Mortality: {$outflowHistory['mortality']}, Culling: {$outflowHistory['culling']}, Sales: {$outflowHistory['sales']}");
                DB::rollBack();
                return;
            }

            // --- Calculate age and stock values with enriched metadata ---
            Log::info('⏰ Records Save: Calculating age and stock values');
            $age = $livestockStartDate->diffInDays($recordDate);

            // Calculate stock_awal based on previous day's record
            $previousDate = $recordDate->copy()->subDay()->format('Y-m-d');
            $previousRecording = Recording::with(['deplesiData', 'feedUsages'])
                ->where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $previousDate)
                ->first();

            $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->initial_quantity;

            // Calculate stock_akhir and depletion totals
            $totalDeplesiHariIni = (int)($this->mortality ?? 0) + (int)($this->culling ?? 0) + (int)($this->sales_quantity ?? 0);
            $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

            // Get previous weight data with full history
            $weightHistory = $this->getWeightHistory($this->livestockId, $recordDate);
            $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0;
            $weightToday = $this->weight_today ?? 0;
            $weightGain = $weightToday - $weightYesterday;

            // --- Get feed consumption history for advanced metrics ---
            $feedHistory = $this->getFeedConsumptionHistory($this->livestockId, $recordDate);

            // --- Calculate FCR, IP, and other performance metrics ---
            $performanceMetrics = $this->calculatePerformanceMetrics(
                $age,
                $stockAkhirHariIni,
                $populasiAwal,
                $weightToday,
                $feedHistory['cumulative_feed_consumption'],
                $totalOut
            );

            // --- Prepare detailed payload with enhanced data structure ---
            Log::info('📦 Records Save: Preparing detailed payload');
            $detailedPayload = $this->buildStructuredPayload(
                $ternak,
                $age,
                $stockAwalHariIni,
                $stockAkhirHariIni,
                $weightToday,
                $weightYesterday,
                $weightGain,
                $performanceMetrics,
                $weightHistory,
                $feedHistory,
                $populationHistory,
                $outflowHistory
            );

            // --- Record daily data with comprehensive payload ---
            $recordingInput = [
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
                'payload' => $detailedPayload,
            ];

            // dd($recordingInput);

            // Save recording data
            try {
                $recording = $this->saveOrUpdateRecording($recordingInput);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('error', $e->getMessage());
                return;
            }

            // dd($this->feedUsageId);

            if ($this->feedUsageId) {
                $usage = FeedUsage::findOrFail($this->feedUsageId);
                $this->hasChanged = $this->hasUsageChanged($usage, $this->usages);
            }

            if ($this->supplyUsageId) {
                $supplyUsage = SupplyUsage::findOrFail($this->supplyUsageId);
                $this->hasSupplyChanged = $this->hasSupplyUsageChanged($supplyUsage, $this->supplyUsages);
            }

            // dd($hasChanged);

            // --- Process feed usage with enhanced traceability ---
            if (!empty($this->usages) || $this->hasChanged === true) {
                try {
                    // Validate the usage date against stock entry dates
                    $earliestStockDate = FeedStock::where('livestock_id', $this->livestockId)->min('date');

                    if ($earliestStockDate && $this->date < $earliestStockDate) {
                        throw new \Exception("Feed usage date must be after the earliest stock entry date ({$earliestStockDate}) for this livestock");
                    }
                    // dd('ada');

                    // Save feed usage with enhanced tracking
                    $feedUsage = $this->saveFeedUsageWithTracking($validatedData, $recording->id);

                    // Verify stock availability
                    $stockCheck = $this->checkStockByTernakId($this->livestockId);
                    if (empty($stockCheck)) {
                        throw new \Exception("No available feed stock for this livestock");
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->dispatch('error', $e->getMessage());
                    return;
                }
            } else {

                // dd('kosong');
            }

            // --- Process supply usage with proper model structure ---
            // Check if there's existing supply usage for this date
            $existingSupplyUsage = SupplyUsage::where('livestock_id', $this->livestockId)
                ->whereDate('usage_date', $this->date)
                ->first();

            if ($existingSupplyUsage) {
                $this->supplyUsageId = $existingSupplyUsage->id;
            }

            // Prepare supply usage data from supplyQuantities
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
                ->filter() // Remove null values
                ->values()
                ->toArray();

            if (!empty($this->supplyUsages) || $this->hasSupplyChanged === true) {
                try {
                    // Validate the usage date against supply stock entry dates
                    $livestock = Livestock::find($this->livestockId);
                    $earliestSupplyStockDate = SupplyStock::where('farm_id', $livestock->farm_id)->min('date');

                    if ($earliestSupplyStockDate && $this->date < $earliestSupplyStockDate) {
                        throw new \Exception("Supply usage date must be after the earliest supply stock entry date ({$earliestSupplyStockDate}) for this livestock");
                    }

                    // Save supply usage with proper model structure
                    $supplyUsage = $this->saveSupplyUsageWithTracking($validatedData, $recording->id);

                    Log::info("Supply usage processed successfully", [
                        'usage_id' => $supplyUsage->id,
                        'livestock_id' => $supplyUsage->livestock_id,
                        'date' => $supplyUsage->usage_date,
                        'total_quantity' => $supplyUsage->total_quantity ?? 0,
                        'supplies_count' => count($this->supplyUsages),
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->dispatch('error', $e->getMessage());
                    return;
                }
            }

            // --- Record depletion data with cause tracking ---
            Log::info('💀 Records Save: Processing depletion data', [
                'mortality' => $this->mortality,
                'culling' => $this->culling,
                'recording_id' => $recording->id
            ]);

            // --- Refactored and consolidated depletion processing ---
            $depletionsToProcess = [
                LivestockDepletionConfig::TYPE_MORTALITY => $this->mortality,
                LivestockDepletionConfig::TYPE_CULLING   => $this->culling,
            ];

            $livestockInstance = null; // Lazy load the livestock instance

            foreach ($depletionsToProcess as $depletionType => $quantity) {
                if ($quantity > 0) {
                    // Load livestock instance only when a depletion needs to be processed
                    if (!$livestockInstance) {
                        $livestockInstance = Livestock::find($this->livestockId);
                    }

                    Log::info("Records Save: Processing depletion.", [
                        'type'         => $depletionType,
                        'quantity'     => $quantity,
                        'recording_id' => $recording->id,
                    ]);

                    // Prepare options for the FIFO service
                    $options = [
                        'date'          => $this->date,
                        'reason'        => "Depletion via Records component",
                        'notes'         => "Recorded by " . (Auth::user()->name ?? 'System'),
                        'original_type' => $depletionType,
                    ];

                    // Use the unified FIFO depletion method for all types
                    $result = $this->storeDeplesiWithFifo(
                        $depletionType,
                        (int) $quantity,
                        $recording->id,
                        $livestockInstance,
                        $options
                    );

                    $isSuccess = $result && ($result['success'] ?? false);

                    Log::info("Records Save: Depletion for type '{$depletionType}' processed.", [
                        'status'         => $isSuccess ? 'success' : 'failed',
                        'result_payload' => $result,
                    ]);

                    if (!$isSuccess) {
                        // Log error for potential debugging without halting the entire save process
                        Log::error("Failed to process depletion for type '{$depletionType}'", [
                            'livestock_id' => $this->livestockId,
                            'recording_id' => $recording->id,
                            'response'     => $result,
                        ]);
                    }
                }
            }

            // --- Update current livestock quantity with detailed tracking ---
            $this->updateCurrentLivestockQuantityWithHistory();

            // --- Calculate and save cost data with comprehensive breakdown ---
            $costService = app(LivestockCostService::class);
            $livestockCost = $costService->calculateForDate($this->livestockId, $this->date);
            Log::info($livestockCost);

            // --- Recalculate historical data if needed ---
            // This ensures that any changes propagate to future days
            // $futureRecords = Recording::where('livestock_id', $this->livestockId)
            //     ->where('tanggal', '>', $this->date)
            //     ->orderBy('tanggal')
            //     ->get();

            // if ($futureRecords->isNotEmpty()) {
            //     foreach ($futureRecords as $futureRecord) {
            //         $costService->calculateForDate($this->livestockId, $futureRecord->tanggal);
            //     }
            // }

            Log::info('💾 Records Save: Committing database transaction');
            DB::commit(); // Commit all database changes

            // --- Reset form and reload data ---
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
            $this->loadRecordingData();

            Log::info('🎉 Records Save: Process completed successfully');
            // $this->dispatch('success', 'Data berhasil disimpan dengan ' . count($this->usages) . ' tipe pakan yang berbeda');
            $this->dispatch('success', 'Data berhasil disimpan');
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

            // Log detailed error for debugging
            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());

            // User-friendly error message
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ' . $message);
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
     * Store depletion with detailed tracking
     * 
     * @param string $jenis Type of depletion ('Mati' or 'Afkir')
     * @param int $jumlah Quantity
     * @param string $recordingId Recording ID for relation
     * @return \App\Models\LivestockDepletion
     */
    private function storeDeplesiWithDetails($jenis, $jumlah, $recordingId)
    {
        // dd($jenis, $jumlah, $recordingId);
        // Normalize depletion type using config
        $normalizedType = LivestockDepletionConfig::normalize($jenis);
        $legacyType = LivestockDepletionConfig::toLegacy($normalizedType);

        Log::info('📝 StoreDeplesi: Method called with config normalization', [
            'original_type' => $jenis,
            'normalized_type' => $normalizedType,
            'legacy_type' => $legacyType,
            'quantity' => $jumlah,
            'recording_id' => $recordingId,
            'livestock_id' => $this->livestockId
        ]);

        if ($jumlah <= 0) {
            Log::info('⚠️ StoreDeplesi: Zero quantity, skipping');
            return null;
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



        // Create or update depletion record with enhanced metadata and config normalization
        $deplesi = LivestockDepletion::updateOrCreate(
            [
                'livestock_id' => $this->livestockId,
                'tanggal' => $this->date,
                'jenis' => $normalizedType, // Use normalized type for consistency
            ],
            [
                'jumlah' => $jumlah,
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
                    'single_record' => true
                ],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]
        );

        Log::info("📝 Recorded livestock depletion", [
            'livestock_id' => $this->livestockId,
            'date' => $this->date,
            'type' => $jenis,
            'quantity' => $jumlah,
            'recording_id' => $recordingId,
            'method' => 'traditional'
        ]);

        return $deplesi;
    }

    // private function storeDeplesiWithDetails($jenis, $jumlah, $recordingId)
    // {
    //     // Normalize depletion type using config
    //     $normalizedType = LivestockDepletionConfig::normalize($jenis);
    //     $legacyType = LivestockDepletionConfig::toLegacy($normalizedType);

    //     Log::info('📝 StoreDeplesi: Method called with config normalization', [
    //         'original_type' => $jenis,
    //         'normalized_type' => $normalizedType,
    //         'legacy_type' => $legacyType,
    //         'quantity' => $jumlah,
    //         'recording_id' => $recordingId,
    //         'livestock_id' => $this->livestockId
    //     ]);

    //     if ($jumlah <= 0) {
    //         Log::info('⚠️ StoreDeplesi: Zero quantity, skipping');
    //         return null;
    //     }

    //     $livestock = Livestock::find($this->livestockId);
    //     if (!$livestock) {
    //         Log::error('❌ StoreDeplesi: Livestock not found', ['livestock_id' => $this->livestockId]);
    //         return null;
    //     }

    //     Log::info('✅ StoreDeplesi: Livestock found', [
    //         'livestock_name' => $livestock->name ?? 'Unknown',
    //         'farm_id' => $livestock->farm_id
    //     ]);

    //     $currentDate = Carbon::parse($this->date);
    //     $age = $livestock ? $currentDate->diffInDays(Carbon::parse($livestock->start_date)) : null;

    //     // Check if FIFO depletion should be used
    //     if ($this->shouldUseFifoDepletion($livestock, $jenis)) {
    //         $fifoResult = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock);

    //         // If FIFO succeeded, standardize the result and return
    //         if ($fifoResult && (is_array($fifoResult) ? ($fifoResult['success'] ?? false) : true)) {
    //             // Standardize FIFO depletion records to match traditional format
    //             $this->standardizeFifoDepletionRecords($fifoResult, $livestock, $jenis, $recordingId, $age);
    //             return $fifoResult;
    //         }

    //         // If FIFO failed, log and continue with traditional method
    //         Log::warning('🔄 FIFO depletion failed, falling back to traditional method', [
    //             'livestock_id' => $livestock->id,
    //             'depletion_type' => $jenis,
    //             'quantity' => $jumlah
    //         ]);
    //     }

    //     // Create or update depletion record with enhanced metadata and config normalization
    //     $deplesi = LivestockDepletion::updateOrCreate(
    //         [
    //             'livestock_id' => $this->livestockId,
    //             'tanggal' => $this->date,
    //             'jenis' => $normalizedType, // Use normalized type for consistency
    //         ],
    //         [
    //             'jumlah' => $jumlah,
    //             'recording_id' => $recordingId, // Link to recording for traceability
    //             'method' => 'traditional',
    //             'metadata' => [
    //                 // Basic livestock information
    //                 'livestock_name' => $livestock->name ?? 'Unknown',
    //                 'farm_id' => $livestock->farm_id ?? null,
    //                 'farm_name' => $livestock->farm->name ?? 'Unknown',
    //                 'coop_id' => $livestock->coop_id ?? null,
    //                 'kandang_name' => $livestock->kandang->name ?? 'Unknown',
    //                 'age_days' => $age,

    //                 // Recording information
    //                 'recording_id' => $recordingId,
    //                 'updated_at' => now()->toIso8601String(),
    //                 'updated_by' => auth()->id(),
    //                 'updated_by_name' => auth()->user()->name ?? 'Unknown User',

    //                 // Method information
    //                 'depletion_method' => 'traditional',
    //                 'processing_method' => 'records_component',
    //                 'source_component' => 'Records',

    //                 // Config-related metadata
    //                 'depletion_config' => [
    //                     'original_type' => $jenis,
    //                     'normalized_type' => $normalizedType,
    //                     'legacy_type' => $legacyType,
    //                     'config_version' => '1.0',
    //                     'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
    //                     'category' => LivestockDepletionConfig::getCategory($normalizedType)
    //                 ]
    //             ],
    //             'data' => [
    //                 'depletion_method' => 'traditional',
    //                 'original_request' => $jumlah,
    //                 'processing_source' => 'Records Component',
    //                 'batch_processing' => false,
    //                 'single_record' => true
    //             ],
    //             'created_by' => auth()->id(),
    //             'updated_by' => auth()->id()
    //         ]
    //     );

    //     Log::info("📝 Recorded livestock depletion", [
    //         'livestock_id' => $this->livestockId,
    //         'date' => $this->date,
    //         'type' => $jenis,
    //         'quantity' => $jumlah,
    //         'recording_id' => $recordingId,
    //         'method' => 'traditional'
    //     ]);

    //     return $deplesi;
    // }

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
     * Build structured payload with organized sections for future-proof data storage
     * 
     * @param mixed $ternak CurrentLivestock instance
     * @param int $age Age in days
     * @param int $stockAwal Initial stock for the day
     * @param int $stockAkhir Final stock for the day
     * @param float $weightToday Today's weight
     * @param float $weightYesterday Yesterday's weight
     * @param float $weightGain Weight gain
     * @param array $performanceMetrics Performance calculations
     * @param array $weightHistory Weight history data
     * @param array $feedHistory Feed consumption history
     * @param array $populationHistory Population changes
     * @param array $outflowHistory Outflow tracking
     * @return array Structured payload
     */
    private function buildStructuredPayload(
        $ternak,
        int $age,
        int $stockAwal,
        int $stockAkhir,
        float $weightToday,
        float $weightYesterday,
        float $weightGain,
        array $performanceMetrics,
        array $weightHistory,
        array $feedHistory,
        array $populationHistory,
        array $outflowHistory
    ): array {
        // Calculate feed-related data
        $totalFeedUsage = array_sum(array_column($this->usages, 'quantity'));
        $feedCost = array_sum(array_map(function ($usage) {
            $qty = $usage['quantity'] ?? 0;
            $price = $usage['stock_prices']['average_price'] ?? 0;
            return $qty * $price;
        }, $this->usages));

        // Calculate supply-related data
        $totalSupplyUsage = array_sum(array_column($this->supplyUsages, 'quantity'));
        $supplyCost = array_sum(array_map(function ($usage) {
            $qty = $usage['quantity'] ?? 0;
            $price = $usage['stock_prices']['average_price'] ?? 0;
            return $qty * $price;
        }, $this->supplyUsages));

        return [
            // === METADATA SECTION ===
            'schema' => [
                'version' => '3.0',
                'schema_date' => '2025-01-23',
                'compatibility' => ['2.0', '3.0'],
                'structure' => 'hierarchical_organized'
            ],

            'recording' => [
                'timestamp' => now()->toIso8601String(),
                'date' => $this->date,
                'age_days' => $age,
                'user' => [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name ?? 'Unknown User',
                    'role' => Auth::user()->roles->first()->name ?? 'Unknown Role',
                    'company_id' => Auth::user()->company_id ?? null,
                ],
                'source' => [
                    'application' => 'livewire_records',
                    'component' => 'Records',
                    'method' => 'save',
                    'version' => '3.0'
                ]
            ],

            // === BUSINESS DATA SECTION ===
            'livestock' => [
                'basic_info' => [
                    'id' => $ternak->livestock->id,
                    'name' => $ternak->livestock->name,
                    'strain' => $ternak->livestock->strain ?? 'Unknown Strain',
                    'start_date' => $ternak->livestock->start_date,
                    'age_days' => $age
                ],
                'location' => [
                    'farm_id' => $ternak->livestock->farm_id,
                    'farm_name' => $ternak->livestock->farm->name ?? 'Unknown Farm',
                    'coop_id' => $ternak->livestock->coop_id,
                    'coop_name' => $ternak->livestock->coop->name ?? 'Unknown Coop'
                ],
                'population' => [
                    'initial' => $ternak->livestock->initial_quantity,
                    'stock_start' => $stockAwal,
                    'stock_end' => $stockAkhir,
                    'change' => $stockAkhir - $stockAwal
                ]
            ],

            'production' => [
                'weight' => [
                    'yesterday' => $weightYesterday,
                    'today' => $weightToday,
                    'gain' => $weightGain,
                    'unit' => 'grams'
                ],
                'depletion' => [
                    'mortality' => (int)($this->mortality ?? 0),
                    'culling' => (int)($this->culling ?? 0),
                    'total' => (int)($this->mortality ?? 0) + (int)($this->culling ?? 0)
                ],
                'sales' => [
                    'quantity' => (int)($this->sales_quantity ?? 0),
                    'weight' => (float)($this->sales_weight ?? 0),
                    'price_per_unit' => (float)($this->sales_price ?? 0),
                    'total_value' => (float)($this->total_sales ?? 0),
                    'average_weight' => $this->sales_quantity > 0 ? $this->sales_weight / $this->sales_quantity : 0
                ]
            ],

            'consumption' => [
                'feed' => [
                    'total_quantity' => $totalFeedUsage,
                    'total_cost' => $feedCost,
                    'items' => $this->usages,
                    'types_count' => count($this->usages),
                    'cost_per_kg' => $totalFeedUsage > 0 ? $feedCost / $totalFeedUsage : 0
                ],
                'supply' => [
                    'total_quantity' => $totalSupplyUsage,
                    'total_cost' => $supplyCost,
                    'items' => $this->supplyUsages,
                    'types_count' => count($this->supplyUsages),
                    'cost_per_unit' => $totalSupplyUsage > 0 ? $supplyCost / $totalSupplyUsage : 0
                ]
            ],

            // === PERFORMANCE SECTION ===
            'performance' => array_merge($performanceMetrics, [
                'calculated_at' => now()->toIso8601String(),
                'calculation_method' => 'standard_poultry_metrics'
            ]),

            // === HISTORICAL DATA SECTION ===
            'history' => [
                'weight' => $weightHistory,
                'feed' => $feedHistory,
                'population' => $populationHistory,
                'outflow' => $outflowHistory
            ],

            // === ENVIRONMENT SECTION (Extensible) ===
            'environment' => [
                'climate' => [
                    'temperature' => null,
                    'humidity' => null,
                    'pressure' => null
                ],
                'housing' => [
                    'lighting' => null,
                    'ventilation' => null,
                    'density' => null
                ],
                'water' => [
                    'consumption' => null,
                    'quality' => null,
                    'temperature' => null
                ]
            ],

            // === CONFIGURATION SECTION ===
            'config' => [
                'manual_depletion_enabled' => $this->isManualDepletionEnabled,
                'manual_feed_usage_enabled' => $this->isManualFeedUsageEnabled,
                'recording_method' => $this->recordingMethod ?? 'total',
                'livestock_config' => $this->livestockConfig
            ],

            // === VALIDATION SECTION ===
            'validation' => [
                'data_quality' => [
                    'weight_logical' => $weightToday >= 0 && $weightGain >= -100,
                    'population_logical' => $stockAkhir >= 0 && $stockAwal >= $stockAkhir,
                    'feed_consumption_logical' => $totalFeedUsage >= 0,
                    'depletion_logical' => ($this->mortality ?? 0) >= 0 && ($this->culling ?? 0) >= 0
                ],
                'completeness' => [
                    'has_weight_data' => $weightToday > 0,
                    'has_feed_data' => $totalFeedUsage > 0,
                    'has_depletion_data' => ($this->mortality ?? 0) > 0 || ($this->culling ?? 0) > 0,
                    'has_supply_data' => $totalSupplyUsage > 0
                ]
            ]
        ];
    }
}
