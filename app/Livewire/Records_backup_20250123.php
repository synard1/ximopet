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

use App\Services\StocksService;
use App\Services\FIFOService;
use App\Services\TernakService;
use App\Services\Livestock\LivestockCostService;
use App\Services\Livestock\FIFODepletionService;


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

class Records extends Component
{
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
        // 'recordingMethod' => 'required|in:batch,total',
    ];

    protected $messages = [
        'recordingMethod.required' => 'Recording method must be selected.',
        'recordingMethod.in' => 'Invalid recording method selected.',
    ];

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

            // Rewritten: Validasi jika batch > 1 dan belum ada records, cek config di kolom data
            if ($livestock->getActiveBatchesCount() > 1 && !\App\Models\Recording::where('livestock_id', $livestock->id)->exists()) {
                $config = $livestock->getDataColumn('config');
                if (empty($config) || !is_array($config) || empty($config['recording_method'])) {
                    $this->dispatch('error', 'Ternak ini memiliki lebih dari 1 batch aktif. Silakan atur metode pencatatan terlebih dahulu di menu setting pada data ini.');
                    // Log untuk debugging
                    Log::info('[Records] setRecords: Gagal lanjut, config belum diatur untuk livestock_id: ' . $livestock->id, [
                        'config' => $config,
                        'livestock_id' => $livestock->id,
                        'user_id' => auth()->id(),
                        'timestamp' => now()->toDateTimeString(),
                    ]);
                    return;
                }
                // Jika config sudah ada, lanjutkan proses
                // Log untuk debugging
                Log::info('[Records] setRecords: Config ditemukan, proses dilanjutkan untuk livestock_id: ' . $livestock->id, [
                    'config' => $config,
                    'livestock_id' => $livestock->id,
                    'user_id' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
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

        Log::info('Records - Livestock Configuration Loaded', [
            'livestock_id' => $livestock->id,
            'config' => $this->livestockConfig,
            'manual_depletion_enabled' => $this->isManualDepletionEnabled,
            'manual_feed_usage_enabled' => $this->isManualFeedUsageEnabled,
        ]);
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
            // Calculate deplesi using the relationship
            $deplesi = $currentLivestock->livestock->livestockDepletion;
            $totalMati = $deplesi->where('jenis', 'Mati')->sum('jumlah');
            $totalAfkir = $deplesi->where('jenis', 'Afkir')->sum('jumlah');
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

            $mortality = $deplesi->where('jenis', 'Mati')->sum('jumlah');
            $culling = $deplesi->where('jenis', 'Afkir')->sum('jumlah');
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
            $populationHistory = $this->getPopulationHistory($this->livestockId, $recordDate);

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
            $detailedPayload = [
                // Depletion data
                'mortality' => (int)($this->mortality ?? 0),
                'culling' => (int)($this->culling ?? 0),
                'sales_quantity' => (int)($this->sales_quantity ?? 0),

                // Sales data
                'sales_price' => (float)($this->sales_price ?? 0),
                'sales_weight' => (float)($this->sales_weight ?? 0),
                'total_sales' => (float)($this->total_sales ?? 0),
                'sales_per_unit' => $this->sales_quantity > 0 ? $this->total_sales / $this->sales_quantity : 0,

                // Feed usage with detailed information
                'feed_usage' => $this->usages,
                'total_feed_usage' => array_sum(array_column($this->usages, 'quantity')),
                'feed_cost' => array_sum(array_map(function ($usage) {
                    $qty = $usage['quantity'] ?? 0;
                    $price = $usage['stock_prices']['average_price'] ?? 0;
                    return $qty * $price;
                }, $this->usages)),

                // Performance metrics
                'performance' => $performanceMetrics,

                // Historical data
                'weight_history' => $weightHistory,
                'feed_history' => $feedHistory,
                'population_history' => $populationHistory,
                'outflow_history' => $outflowHistory,

                // Environmental data (if available)
                'environment' => [
                    'temperature' => null, // Could be extended with actual temperature data
                    'humidity' => null,    // Could be extended with actual humidity data
                    'lighting' => null,    // Could be extended with lighting data
                ],

                // Farm and kandang information
                'farm_id' => $ternak->livestock->farm_id,
                'farm_name' => $ternak->livestock->farm->name ?? 'Unknown Farm',
                'coop_id' => $ternak->livestock->coop_id,
                'coop_name' => $ternak->livestock->coop->name ?? 'Unknown Coop',

                // Metadata
                'recorded_at' => now()->toIso8601String(),
                'recorded_by' => auth()->id(),
                'recorder_name' => auth()->user()->name ?? 'Unknown User',
            ];

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

            if ($this->mortality > 0) {
                Log::info('🔴 Records Save: Processing mortality depletion');
                $mortalityResult = $this->storeDeplesiWithDetails('Mati', $this->mortality, $recording->id);
                Log::info('✅ Records Save: Mortality depletion processed', [
                    'result' => $mortalityResult ? 'success' : 'failed'
                ]);
            }

            if ($this->culling > 0) {
                Log::info('🟡 Records Save: Processing culling depletion');
                $cullingResult = $this->storeDeplesiWithDetails('Afkir', $this->culling, $recording->id);
                Log::info('✅ Records Save: Culling depletion processed', [
                    'result' => $cullingResult ? 'success' : 'failed'
                ]);
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
     * Get detailed population history for a livestock
     * 
     * @param string $livestockId The livestock ID
     * @param Carbon $currentDate The current date
     * @return array Population history details
     */
    private function getPopulationHistory($livestockId, $currentDate)
    {
        $livestock = Livestock::findOrFail($livestockId);
        $initialPopulation = $livestock->populasi_awal;
        $startDate = Carbon::parse($livestock->start_date);

        // Get all recordings up to the current date
        $recordings = Recording::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->orderBy('tanggal')
            ->get();

        // Get all depletion records up to the current date
        $depletions = LivestockDepletion::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->orderBy('tanggal')
            ->get();

        // Get all sales records up to the current date
        $sales = LivestockSalesItem::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->orderBy('tanggal')
            ->get();

        // Calculate daily population changes
        $populationByDate = [];
        $currentPopulation = $initialPopulation;
        $totalMortality = 0;
        $totalCulling = 0;
        $totalSales = 0;

        // Process recordings with their depletion data
        foreach ($recordings as $recording) {
            $recordDate = $recording->tanggal->format('Y-m-d');
            $payload = $recording->payload ?? [];

            $dayMortality = $payload['mortality'] ?? 0;
            $dayCulling = $payload['culling'] ?? 0;
            $daySales = $payload['sales_quantity'] ?? 0;

            $totalMortality += $dayMortality;
            $totalCulling += $dayCulling;
            $totalSales += $daySales;

            $currentPopulation = $recording->stock_akhir;

            $populationByDate[$recordDate] = [
                'date' => $recordDate,
                'population' => $currentPopulation,
                'mortality' => $dayMortality,
                'culling' => $dayCulling,
                'sales' => $daySales,
                'age' => $recording->age,
            ];
        }

        return [
            'initial_population' => $initialPopulation,
            'current_population' => $currentPopulation,
            'total_mortality' => $totalMortality,
            'total_culling' => $totalCulling,
            'total_sales' => $totalSales,
            'mortality_rate' => $initialPopulation > 0 ? ($totalMortality / $initialPopulation) * 100 : 0,
            'culling_rate' => $initialPopulation > 0 ? ($totalCulling / $initialPopulation) * 100 : 0,
            'sales_rate' => $initialPopulation > 0 ? ($totalSales / $initialPopulation) * 100 : 0,
            'survival_rate' => $initialPopulation > 0 ? ($currentPopulation / $initialPopulation) * 100 : 0,
            'daily_changes' => array_values($populationByDate),
            'age_days' => $startDate->diffInDays($currentDate),
        ];
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
                    'updated_by' => auth()->id(),
                    'updated_by_name' => auth()->user()->name ?? 'Unknown User',
                ],
                'updated_by' => auth()->id(),
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
                        'reverted_by' => auth()->id(),
                        'reverted_by_name' => auth()->user()->name ?? 'Unknown User',
                        'reason' => 'Updated feed usage',
                    ],
                    'updated_by' => auth()->id(),
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
                    'created_by' => auth()->id(),
                    'created_by_name' => auth()->user()->name ?? 'Unknown User',
                ],
                'created_by' => auth()->id(),
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
                'updated_by' => auth()->id(),
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
                'created_by' => auth()->id(),
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
                    'created_by' => auth()->id(),
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
        Log::info('📝 StoreDeplesi: Method called', [
            'type' => $jenis,
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

        // Check if FIFO depletion should be used
        if ($this->shouldUseFifoDepletion($livestock, $jenis)) {
            $fifoResult = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock);

            // If FIFO succeeded, return the result
            if ($fifoResult && (is_array($fifoResult) ? ($fifoResult['success'] ?? false) : true)) {
                return $fifoResult;
            }

            // If FIFO failed, log and continue with traditional method
            Log::warning('🔄 FIFO depletion failed, falling back to traditional method', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'quantity' => $jumlah
            ]);
        }

        // Create or update depletion record with enhanced metadata (original method)
        $deplesi = LivestockDepletion::updateOrCreate(
            [
                'livestock_id' => $this->livestockId,
                'tanggal' => $this->date,
                'jenis' => $jenis,
            ],
            [
                'jumlah' => $jumlah,
                'recording_id' => $recordingId, // Link to recording for traceability
                'method' => 'traditional',
                'metadata' => [
                    'livestock_name' => $livestock->name ?? 'Unknown',
                    'farm_id' => $livestock->farm_id ?? null,
                    'farm_name' => $livestock->farm->name ?? 'Unknown',
                    'coop_id' => $livestock->coop_id ?? null,
                    'kandang_name' => $livestock->kandang->name ?? 'Unknown',
                    'age_days' => $age,
                    'updated_at' => now()->toIso8601String(),
                    'updated_by' => auth()->id(),
                    'updated_by_name' => auth()->user()->name ?? 'Unknown User',
                ],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
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

    /**
     * Check if FIFO depletion should be used for this livestock and depletion type
     *
     * @param Livestock $livestock
     * @param string $jenis
     * @return bool
     */
    private function shouldUseFifoDepletion(Livestock $livestock, string $jenis): bool
    {
        try {
            // Check if FIFODepletionService is available
            if (!$this->fifoDepletionService) {
                Log::info('🔍 FIFO Check: FIFODepletionService not available', [
                    'livestock_id' => $livestock->id
                ]);
                return false;
            }

            // Check if livestock has the required methods
            if (!method_exists($livestock, 'getRecordingMethodConfig') || !method_exists($livestock, 'getActiveBatchesCount')) {
                Log::info('🔍 FIFO Check: Required methods not available on Livestock model', [
                    'livestock_id' => $livestock->id
                ]);
                return false;
            }

            // Get livestock configuration
            $config = $livestock->getRecordingMethodConfig();
            if (!$config || !is_array($config)) {
                Log::info('🔍 FIFO Check: No valid configuration found', [
                    'livestock_id' => $livestock->id
                ]);
                return false;
            }

            // Check if livestock has multiple batches
            $batchCount = $livestock->getActiveBatchesCount();
            if ($batchCount <= 1) {
                Log::info('🔍 FIFO Check: Single batch detected, using traditional depletion', [
                    'livestock_id' => $livestock->id,
                    'batch_count' => $batchCount
                ]);
                return false;
            }

            // Check if batch recording is enabled
            $batchSettings = $config['batch_settings'] ?? [];
            if (!($batchSettings['enabled'] ?? false)) {
                Log::info('🔍 FIFO Check: Batch recording not enabled', [
                    'livestock_id' => $livestock->id
                ]);
                return false;
            }

            // Check if FIFO is enabled for depletion
            $depletionMethods = $batchSettings['depletion_methods'] ?? [];
            $fifoConfig = $depletionMethods['fifo'] ?? [];

            if (!($fifoConfig['enabled'] ?? false)) {
                Log::info('🔍 FIFO Check: FIFO depletion not enabled', [
                    'livestock_id' => $livestock->id
                ]);
                return false;
            }

            // Check if current depletion method is FIFO
            $currentDepletionMethod = $batchSettings['depletion_method'] ?? 'fifo';
            if ($currentDepletionMethod !== 'fifo') {
                Log::info('🔍 FIFO Check: Current depletion method is not FIFO', [
                    'livestock_id' => $livestock->id,
                    'current_method' => $currentDepletionMethod
                ]);
                return false;
            }

            Log::info('✅ FIFO Check: All conditions met, using FIFO depletion', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'batch_count' => $batchCount
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('❌ FIFO Check: Error checking FIFO conditions', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Store depletion using FIFO method
     *
     * @param string $jenis
     * @param int $jumlah
     * @param int $recordingId
     * @param Livestock $livestock
     * @return array|null
     */
    private function storeDeplesiWithFifo(string $jenis, int $jumlah, string $recordingId, Livestock $livestock): ?array
    {
        try {
            Log::info('🚀 FIFO Depletion: Starting FIFO depletion process', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'quantity' => $jumlah,
                'recording_id' => $recordingId
            ]);

            // Validate FIFODepletionService
            if (!$this->fifoDepletionService) {
                throw new Exception('FIFODepletionService not available');
            }

            // Map depletion types to FIFO service types
            $depletionTypeMap = [
                'Mati' => 'mortality',
                'Afkir' => 'culling',
                'Jual' => 'sales',
                'Mutasi' => 'mutation'
            ];

            $fifoDepletionType = $depletionTypeMap[$jenis] ?? 'mortality';

            // Prepare data for FIFO service
            $depletionData = [
                'livestock_id' => $livestock->id,
                'depletion_type' => $fifoDepletionType,
                'total_quantity' => $jumlah,
                'depletion_date' => $this->date,
                'recording_id' => $recordingId,
                'reason' => "Recording depletion via Records component",
                'notes' => "Depletion recorded on " . now()->format('Y-m-d H:i:s') . " by " . (auth()->user()->name ?? 'Unknown User')
            ];

            // Process FIFO depletion
            $result = $this->fifoDepletionService->processDepletion($depletionData);

            if ($result && isset($result['success']) && $result['success']) {
                Log::info('🎉 FIFO Depletion: Successfully processed', [
                    'livestock_id' => $livestock->id,
                    'total_quantity' => $result['total_quantity'] ?? $jumlah,
                    'batches_affected' => $result['batches_affected'] ?? 0,
                    'depletion_records' => $result['depletion_records'] ?? []
                ]);

                // Return summary for compatibility
                return [
                    'success' => true,
                    'method' => 'fifo',
                    'depletion_records' => $result['depletion_records'] ?? [],
                    'batches_affected' => $result['batches_affected'] ?? 0,
                    'total_quantity' => $result['total_quantity'] ?? $jumlah,
                    'distribution_summary' => $result['distribution_summary'] ?? []
                ];
            } else {
                $errorMsg = $result['error'] ?? 'FIFO depletion process failed';
                throw new Exception($errorMsg);
            }
        } catch (Exception $e) {
            Log::error('❌ FIFO Depletion: Failed to process FIFO depletion', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'quantity' => $jumlah,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return null to indicate failure, caller will handle fallback
            return null;
        }
    }

    /**
     * Preview FIFO depletion distribution for UI display
     *
     * @param string $jenis
     * @param int $jumlah
     * @return array|null
     */
    public function previewFifoDepletion(string $jenis, int $jumlah): ?array
    {
        try {
            if (!$this->livestockId || $jumlah <= 0) {
                return null;
            }

            $livestock = Livestock::find($this->livestockId);
            if (!$livestock || !$this->shouldUseFifoDepletion($livestock, $jenis)) {
                return null;
            }

            // Map depletion types
            $depletionTypeMap = [
                'Mati' => 'mortality',
                'Afkir' => 'culling',
                'Jual' => 'sales',
                'Mutasi' => 'mutation'
            ];

            $fifoDepletionType = $depletionTypeMap[$jenis] ?? 'mortality';

            $depletionData = [
                'livestock_id' => $livestock->id,
                'depletion_type' => $fifoDepletionType,
                'total_quantity' => $jumlah,
                'depletion_date' => $this->date ?? now()->format('Y-m-d')
            ];

            return $this->fifoDepletionService->previewFifoDepletion($depletionData);
        } catch (Exception $e) {
            Log::error('❌ FIFO Preview: Failed to preview FIFO depletion', [
                'livestock_id' => $this->livestockId,
                'depletion_type' => $jenis,
                'quantity' => $jumlah,
                'error' => $e->getMessage()
            ]);
            return null;
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
                'updated_by' => auth()->id()
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
                    'updated_by' => auth()->id(),
                    'updated_by_name' => auth()->user()->name ?? 'Unknown User',
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
                'updated_by' => auth()->id()
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
                'id' => auth()->id(),
                'name' => auth()->user()->name ?? 'Unknown User',
                'role' => auth()->user()->roles->first()->name ?? 'Unknown Role',
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
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        // Force update of updated_by/updated_at even if no changes
        if (!$recording->wasRecentlyCreated && !$recording->wasChanged()) {
            $recording->updated_by = auth()->id();
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
}
