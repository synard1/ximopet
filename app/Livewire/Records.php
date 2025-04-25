<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Recording; // Assuming this is the model for the recordings
use App\Models\CurrentStock;
use App\Models\CurrentLivestock;
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


use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\LivestockSalesItem;

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

    public $initial_stock;
    public $final_stock;
    public $weight;
    public $sales_quantity;
    public $sales_weight;
    public $sales_price;
    public $total_sales;

    public $feedUsageId, $usages;

    public $isEditing = false;


    protected $listeners = [
        'setTernakId' => 'setTernak'
    ];

    protected $rules = [
        'date' => 'required|date',
        'mortality' => 'nullable|integer|min:0',
        'culling' => 'nullable|integer|min:0',
        'sales_quantity' => 'nullable|integer|min:0',
        'sales_price' => 'nullable|numeric|min:0',
        'total_sales' => 'nullable|numeric|min:0',
    ];

    protected ?StocksService $stocksService = null;
    protected ?FIFOService $fifoService = null;

    public function mount(StocksService $stocksService, FIFOService $fifoService)
    {
        $this->stocksService = $stocksService;
        $this->fifoService = $fifoService;
        $this->initializeItemQuantities();
        $this->loadRecordingData();


    }

     // Fix the method signature to receive the ID directly
    public function setTernak($livestockId)
    {
        $this->livestockId = $livestockId;
        if ($this->livestockId) {
            $this->loadStockData();
            $this->initializeItemQuantities();
            $this->checkCurrentLivestockStock();
            $this->loadRecordingData();


        }
        // $this->dispatch('success', $livestockId);
    }

    public function save()
    {
        $this->validate();

        $validatedData = $this->all();

        try {
            DB::beginTransaction(); // Start a database transaction

            $this->usages = collect($this->itemQuantities)
                ->filter(fn ($qty) => $qty > 0)
                ->map(function ($qty, $itemId) {
                    return [
                        'feed_id' => $itemId,
                        'quantity' => (float) $qty,
                    ];
                })
                ->values()
                ->toArray();

            // --- Prepare data for Recording ---
            $ternak = CurrentLivestock::with('livestock')->where('livestock_id', $this->livestockId)->first();
            $populasiAwal = $ternak->livestock->populasi_awal;

            $existingRecord = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $this->date)
                ->first();

            $previousOut = 0;
            if ($existingRecord) {
                $prevPayload = $existingRecord->payload ?? [];
                $previousOut = ($prevPayload['mortality'] ?? 0)
                    + ($prevPayload['culling'] ?? 0)
                    + ($prevPayload['sales_quantity'] ?? 0);
            }

            $newOut = (int) $this->mortality + (int) $this->culling + (int) $this->sales_quantity;

            $totalOutExceptToday = Recording::where('livestock_id', $this->livestockId)
                ->where('tanggal', '!=', $this->date)
                ->get()
                ->sum(function ($record) {
                    $payload = $record->payload ?? [];
                    return ($payload['mortality'] ?? 0)
                        + ($payload['culling'] ?? 0)
                        + ($payload['sales_quantity'] ?? 0);
                });

            $totalOut = $totalOutExceptToday + $newOut;

            if ($totalOut > $populasiAwal) {
                $this->dispatch('error', 'Total pengeluaran melebihi populasi awal!');
                DB::rollBack(); // Rollback if total out exceeds initial population
                return;
            }


            $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $this->date)
                ->get();

            $startDate = Carbon::parse($ternak->livestock->start_date);
            $selectedDate = Carbon::parse($this->date);
            $age = $startDate->diffInDays($selectedDate);
            // Calculate stock_awal for the selected date
            $previousDate = $selectedDate->copy()->subDay()->format('Y-m-d');
            $previousRecording = Recording::where('livestock_id', $this->livestockId)
                ->whereDate('tanggal', $previousDate)
                ->first();

            $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->populasi_awal;

            // Calculate stock_akhir for the selected date
            $totalDeplesiHariIni = (int)($this->mortality ?? 0) + (int)($this->culling ?? 0) + (int)($this->sales_quantity ?? 0);
            $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

            $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0; // Assuming berat_awal exists
            $weightGain = ($this->weight_today ?? 0) - $weightYesterday;

            $recordingInput = [
                'livestock_id' => $this->livestockId,
                'tanggal' => $this->date,
                'age' => $age,
                'stock_awal' => $stockAwalHariIni,
                'stock_akhir' => $stockAkhirHariIni,
                'berat_hari_ini' => $this->weight_today ?? null,
                'berat_semalam' => $weightYesterday,
                'kenaikan_berat' => $weightGain,
                'pakan_jenis' => $weightGain,
                'pakan_harian' => $weightGain,
            ];

            try {
                $this->saveOrUpdateRecording($recordingInput);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('error', $e->getMessage()); // Dispatch the exception message
                return;
            }


            // Format item quantities for stock reduction
            $stockData = [];
            foreach ($this->itemQuantities as $itemId => $quantity) {

                if ($quantity > 0) {
                    $stockData[] = [
                        'item_id' => $itemId,
                        'qty' => $quantity
                    ];
                }
            }

            // Lakukan pengurangan stok dengan struktur FIFO seperti di Feed*
            if (!empty($stockData)) {
                $fifoData = [
                    'tanggal' => $this->date,
                    'livestock_id' => $this->livestockId,
                    'stock' => array_map(function ($item) {
                        return [
                            'item_id' => $item['item_id'],
                            'qty' => $item['qty'],
                        ];
                    }, $stockData)
                ];

                $this->saveFeedUsage($this->all()); // This is where the error might occur
                // app(FIFOService::class)->reduceStockBaru($fifoData);

            }

            //check stocks
            $stockCheck = $this->checkStockByTernakId($this->livestockId);

            // Store deplesi if any mortality or culling exists
            if ($this->mortality > 0) {
                $this->storeDeplesi('Mati', $this->mortality);
            }

            if ($this->culling > 0) {
                $this->storeDeplesi('Afkir', $this->culling);
            }

            // Update current ternak quantity (This calculates total depletion over time)
            $this->updateCurrentLivestockQuantity();

            // Reload data displayed in the table
            $this->loadRecordingData();
            // --- End Recording ---

            app(LivestockCostService::class)->calculateForDate($this->livestockId, $this->date);

            DB::commit(); // Commit the transaction if everything is successful
            // Optionally reset the input fields
            $this->reset(['date', 'age', 'stock_start', 'stock_end', 'mortality', 'culling', 'weight_today', 'weight_yesterday', 'weight_gain', 'sales_quantity', 'sales_price', 'total_sales']);
            $this->initializeItemQuantities(); // Reset item quantities inputs
            $this->loadStockData(); // Reload stock data for inputs
            $this->checkCurrentLivestockStock(); // Reload ternak stock info

            $this->dispatch('success', 'Data berhasil disimpan');

        } catch (ValidationException $e) {
            DB::rollBack(); // Rollback on validation exception
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on any other exception

            $class = __CLASS__;
            $method = __FUNCTION__;
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';

            // Dispatch user-friendly error
            $this->dispatch('error', $errorMessage);

            // Log detailed error for debugging
            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");

            // Optionally: log stack trace
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
        } finally {
            // Optional: Code that runs whether success or failure
        }

    }

    // public function save()
    // {
    //     $this->validate();

    //     $validatedData = $this->all();

    //     // dd($this->all());

    //     try {

    //         $this->usages = collect($this->itemQuantities)
    //             ->filter(fn ($qty) => $qty > 0)
    //             ->map(function ($qty, $itemId) {
    //                 return [
    //                     'feed_id' => $itemId,
    //                     'quantity' => (float) $qty,
    //                 ];
    //             })
    //             ->values()
    //             ->toArray();

    //         // --- Prepare data for Recording ---
    //         $ternak = CurrentLivestock::with('livestock')->where('livestock_id', $this->livestockId)->first();
    //         $populasiAwal = $ternak->livestock->populasi_awal;

    //         $existingRecord = Recording::where('livestock_id', $this->livestockId)
    //                                 ->whereDate('tanggal', $this->date)
    //                                 ->first();

    //         $previousOut = 0;
    //         if ($existingRecord) {
    //             $prevPayload = $existingRecord->payload ?? [];
    //             $previousOut = ($prevPayload['mortality'] ?? 0)
    //                          + ($prevPayload['culling'] ?? 0)
    //                          + ($prevPayload['sales_quantity'] ?? 0);
    //         }

    //         $newOut = (int) $this->mortality + (int) $this->culling + (int) $this->sales_quantity;

    //         $totalOutExceptToday = Recording::where('livestock_id', $this->livestockId)
    //             ->where('tanggal', '!=', $this->date)
    //             ->get()
    //             ->sum(function ($record) {
    //                 $payload = $record->payload ?? [];
    //                 return ($payload['mortality'] ?? 0)
    //                     + ($payload['culling'] ?? 0)
    //                     + ($payload['sales_quantity'] ?? 0);
    //             });

    //         $totalOut = $totalOutExceptToday + $newOut;

    //         if ($totalOut > $populasiAwal) {
    //             $this->dispatch('error', 'Total pengeluaran melebihi populasi awal!');
    //             return;
    //         }


    //         $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
    //                                 ->whereDate('tanggal', $this->date)
    //                                 ->get();

    //         $startDate = Carbon::parse($ternak->livestock->start_date);
    //         $selectedDate = Carbon::parse($this->date);
    //         $age = $startDate->diffInDays($selectedDate);
    //         // Calculate stock_awal for the selected date
    //         $previousDate = $selectedDate->copy()->subDay()->format('Y-m-d');
    //         $previousRecording = Recording::where('livestock_id', $this->livestockId)
    //                                     ->whereDate('tanggal', $previousDate)
    //                                     ->first();

    //         $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->populasi_awal;

    //         // Calculate stock_akhir for the selected date
    //         $totalDeplesiHariIni = (int)($this->mortality ?? 0) + (int)($this->culling ?? 0) + (int)($this->sales_quantity ?? 0);
    //         $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

    //         $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0; // Assuming berat_awal exists
    //         $weightGain = ($this->weight_today ?? 0) - $weightYesterday;

    //         $recordingInput = [
    //             'livestock_id' => $this->livestockId,
    //             'tanggal' => $this->date,
    //             'age' => $age,
    //             'stock_awal' => $stockAwalHariIni,
    //             'stock_akhir' => $stockAkhirHariIni,
    //             'berat_hari_ini' => $this->weight_today ?? null,
    //             'berat_semalam' => $weightYesterday,
    //             'kenaikan_berat' => $weightGain,
    //             'pakan_jenis' => $weightGain,
    //             'pakan_harian' => $weightGain,
    //         ];

    //         // Format item quantities for stock reduction
    //         $stockData = [];
    //         foreach ($this->itemQuantities as $itemId => $quantity) {

    //             if ($quantity > 0) {
    //                 $stockData[] = [
    //                     'item_id' => $itemId,
    //                     'qty' => $quantity
    //                 ];
    //             }
    //         }

    //         // Wrap database operation in a transaction (if applicable)
    //         DB::beginTransaction();

    //         $this->saveOrUpdateRecording($recordingInput);
    //         // $transaksi = $this->saveOrUpdateRecording($recordingInput);
    //         // dd($transaksi);

    //         // Lakukan pengurangan stok dengan struktur FIFO seperti di Feed*
    //         if (!empty($stockData)) {
    //             $fifoData = [
    //                 'tanggal' => $this->date,
    //                 'livestock_id' => $this->livestockId,
    //                 'stock' => array_map(function ($item) {
    //                     return [
    //                         'item_id' => $item['item_id'],
    //                         'qty' => $item['qty'],
    //                     ];
    //                 }, $stockData)
    //             ];

    //             try {
    //                 // if ($this->date < $ternak->livestock->start_date) {
    //                 //     $this->dispatch('error', 'Tanggal transaksi tidak boleh lebih awal dari tanggal mulai ternak.');
    //                 //     return;
    //                 // }

    //                 $this->saveFeedUsage($this->all());

    //                 // app(FIFOService::class)->reduceStockBaru($fifoData);
    //             } catch (\Throwable $fifoError) {
    //                 Log::error('[FIFO Error] ' . $fifoError->getMessage() . ' | File: ' . $fifoError->getFile() . ' | Line: ' . $fifoError->getLine());
    //                 throw $fifoError; // penting: biar DB rollback jalan
    //                 DB::rollBack();
    //                 return;
    //             }
    //         }

            
    //         //check stocks
    //         $stockCheck = $this->checkStockByTernakId($this->livestockId);

    //         // Store deplesi if any mortality or culling exists
    //         if ($this->mortality > 0) {
    //             $this->storeDeplesi('Mati', $this->mortality);
    //         }

    //         if ($this->culling > 0) {
    //             $this->storeDeplesi('Afkir', $this->culling);
    //         }
            
    //         // Update current ternak quantity (This calculates total depletion over time)
    //         $this->updateCurrentLivestockQuantity();
            
    //         // Reload data displayed in the table
    //         $this->loadRecordingData(); 
    //         // --- End Recording ---

    //         app(LivestockCostService::class)->calculateForDate($this->livestockId, $this->date);


    //         // app(LivestockCostService::class)->calculateAndSave([
    //         //     'livestock_id' => $this->livestockId,
    //         //     'tanggal' => $this->date,
    //         //     'stock_akhir' => $stockAkhirHariIni,
    //         //     'mortality' => $this->mortality,
    //         //     'culling' => $this->culling,
    //         //     'sales_quantity' => $this->sales_quantity,
    //         //     'stock' => $stockData, // dari itemQuantities
    //         // ]);


    //         DB::commit();


    //         // Optionally reset the input fields
    //         $this->reset(['date', 'age', 'stock_start', 'stock_end', 'mortality', 'culling', 'weight_today', 'weight_yesterday', 'weight_gain', 'sales_quantity', 'sales_price', 'total_sales']);
    //         $this->initializeItemQuantities(); // Reset item quantities inputs
    //         $this->loadStockData(); // Reload stock data for inputs
    //         $this->checkCurrentLivestockStock(); // Reload ternak stock info

    //         $this->dispatch('success', 'Data berhasil disimpan 11');

    //     } catch (ValidationException $e) {
    //         DB::rollBack(); // Rollback on validation exception too
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
        
    //         $class = __CLASS__;
    //         $method = __FUNCTION__;
    //         $line = $e->getLine();
    //         $file = $e->getFile();
    //         $message = $e->getMessage();
        
    //         // Human-readable error message
    //         $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        
    //         // Dispatch user-friendly error
    //         $this->dispatch('error', $errorMessage);
        
    //         // Log detailed error for debugging
    //         Log::error("[$class::$method] Error: $message | Line: $line | File: $file");
        
    //         // Optionally: log stack trace
    //         Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
    //     } finally {
    //         // Optional: Code that runs whether success or failure
    //     }
        
    // }

    private function saveFeedUsage($data){
        if ($this->feedUsageId) {
            // UPDATE
            $usage = FeedUsage::findOrFail($this->feedUsageId);
            $hasChanged = $this->hasUsageChanged($usage, $this->usages);

            // dd($hasChanged);

            if (!$hasChanged) {
                // DB::rollBack(); // ga perlu simpan apa pun
                // $this->dispatch('info', 'Tidak ada perubahan data untuk disimpan.');
                return;
            }

            // VALIDATION: Ensure usage_date is after the latest stock entry date for the livestock
            $latestStockDate = FeedStock::where('livestock_id', $this->livestockId)->max('date');

            if ($latestStockDate && $this->date < $latestStockDate) {
                // throw new \Exception("Tanggal Pemakaian Stock harus setelah tanggal stock masuk ($latestStockDate) untuk livestock ini.");
                // or
                $this->dispatch('error', "Tanggal Pemakaian Stock harus setelah tanggal stock masuk ($latestStockDate) untuk livestock ini.");
                // return response()->json(['error' => 'Tanggal Pemakaian Stock harus setelah tanggal stock masuk'], 422);
                return;
            }

            $usage->update([
                'usage_date' => $this->date,
                'livestock_id' => $this->livestockId,
                'updated_by' => auth()->id(),
            ]);

            // Kembalikan stok dari detail lama
            $oldDetails = FeedUsageDetail::where('feed_usage_id', $usage->id)->get();

            foreach ($oldDetails as $detail) {
                $stock = FeedStock::find($detail->feed_stock_id);
                if ($stock) {
                    $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                    $stock->used = max(0, $stock->used - $detail->quantity_taken); // backward compat
                    $stock->save();
                }

                $detail->updated_by = auth()->id();
                $detail->save();
                $detail->delete();
            }
        } else {
            // CREATE

            // VALIDATION: Ensure usage_date is after the latest stock entry date for the livestock
            $latestStockDate = FeedStock::where('livestock_id', $this->livestockId)->max('date');
            if ($latestStockDate && $this->date < $latestStockDate) {
                $this->dispatch('error', "Tanggal Pemakaian Stock harus setelah tanggal stock masuk ($latestStockDate) untuk livestock ini.");
                return;

                // throw new \Exception("Tanggal Pemakaian Stock harus setelah tanggal stock masuk ($latestStockDate) untuk livestock ini.");
                // or
                // return response()->json(['error' => 'Tanggal Pemakaian Stock harus setelah tanggal stock masuk'], 422);
            }

            $usage = FeedUsage::create([
                // 'id' => Str::uuid(),
                'usage_date' => $this->date,
                'livestock_id' => $this->livestockId,
                'total_quantity' => 0,
                'created_by' => auth()->id(),
            ]);
        }

        // Jalankan FIFO baru jika ada perubahan
        app(\App\Services\FeedUsageService::class)->process($usage, $this->usages);
    }

    // private function saveFeedUsage($data){
    //     // dd($data[0]['feedUsageId']);
    //     // dd($this->feedUsageId);
    //     // dd($data);

    //     if ($this->feedUsageId) {
    //         // UPDATE
    //         $usage = FeedUsage::findOrFail($this->feedUsageId);
    //         $hasChanged = $this->hasUsageChanged($usage, $this->usages);

    //         // dd($hasChanged);

    //         if (!$hasChanged) {
    //             // DB::rollBack(); // ga perlu simpan apa pun
    //             // $this->dispatch('info', 'Tidak ada perubahan data untuk disimpan.');
    //             return;
    //         }

    //         $usage->update([
    //             'usage_date' => $this->date,
    //             'livestock_id' => $this->livestockId,
    //             'updated_by' => auth()->id(),
    //         ]);

    //         // Kembalikan stok dari detail lama
    //         $oldDetails = FeedUsageDetail::where('feed_usage_id', $usage->id)->get();

    //         foreach ($oldDetails as $detail) {
    //             $stock = FeedStock::find($detail->feed_stock_id);
    //             if ($stock) {
    //                 $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
    //                 $stock->used = max(0, $stock->used - $detail->quantity_taken); // backward compat
    //                 $stock->save();
    //             }

    //             $detail->updated_by = auth()->id();
    //             $detail->save();
    //             $detail->delete();
    //         }
    //     } else {
    //         // CREATE
    //         $usage = FeedUsage::create([
    //             // 'id' => Str::uuid(),
    //             'usage_date' => $this->date,
    //             'livestock_id' => $this->livestockId,
    //             'total_quantity' => 0,
    //             'created_by' => auth()->id(),
    //         ]);
    //     }

    //     // Jalankan FIFO baru jika ada perubahan
    //     app(\App\Services\FeedUsageService::class)->process($usage, $this->usages);
    // }

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
            return true;
        }

        return false;
    }

    private function saveOrUpdateRecording($data)
    {
        // Get the livestock masuk date
        $livestock = Livestock::find($data['livestock_id']);
        if (!$livestock) {
            throw new \Exception("Livestock not found!"); // Or handle this error as appropriate for your app
        }
        $livestockMasukDate = \Carbon\Carbon::parse($livestock->start_date); // Assuming start_date is the correct field

        // Validate the recording date
        $recordingDate = \Carbon\Carbon::parse($data['tanggal']);
        if ($recordingDate->lt($livestockMasukDate)) {
            throw new \Exception("Tanggal Recording tidak boleh lebih kecil dari Tanggal Ayam Masuk!"); 
            // Or, you might want to return false, or redirect with an error message, 
            // depending on how you want to handle this validation
        }

        $payloadData = [
            'mortality' => $this->mortality ?? 0,
            'culling' => $this->culling ?? 0,
            'sales_quantity' => $this->sales_quantity ?? 0,
            'sales_price' => $this->sales_price ?? 0,
            'sales_weight' => $this->sales_weight ?? 0,
            'total_sales' => $this->total_sales ?? 0,
        ];

        $transaksi = Recording::updateOrCreate(
            [
                'livestock_id' => $data['livestock_id'],
                'tanggal' => $data['tanggal']
            ],
            [
                'age' => $data['age'],
                'stock_awal' => $data['stock_awal'],
                'stock_akhir' => $data['stock_akhir'],
                'berat_hari_ini' => $data['berat_hari_ini'] ?? null,
                'berat_semalam' => $data['berat_semalam'] ?? null,
                'kenaikan_berat' => $data['kenaikan_berat'] ?? null,
                'pakan_jenis' => $data['pakan_jenis'] ?? null,
                'pakan_harian' => $data['pakan_harian'] ?? 0,
                'payload' => $payloadData ?? [],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        // Paksa update `updated_by` jika tidak ada perubahan di data lain
        if (!$transaksi->wasRecentlyCreated && !$transaksi->wasChanged()) {
            $transaksi->updated_by = auth()->id();
            $transaksi->touch(); // update updated_at
        }

        logger('Changed: ', $transaksi->getChanges());

        return $transaksi;
    }

    // public function save()
    // {
    //     $this->validate();

    //     $validatedData = $this->all();



    //     // dd($validatedData);
    //     // $validatedData['rekanan_id'] = $this->supplierSelect;
    //     // $validatedData['kandang_id'] = $this->selectedKandang;

    //     // Validate that final stock matches the calculation
    //     // $calculatedFinalStock = $this->stock_end - $this->mortality - $this->culling - $this->sales_quantity;
    //     // if ($calculatedFinalStock > $this->stock_end || $calculatedFinalStock < 0) {
    //     //     $this->addError('final_stock', 'Final stock tidak sesuai dengan perhitungan');
    //     //     $this->dispatch('error', 'Final stock tidak sesuai dengan perhitungan');
    //     //     return;
    //     // }

    //     // dd($calculatedFinalStock);

    //     // dd($this->all());
    //     try {

    //         // --- Prepare data for Recording ---
    //         $ternak = CurrentLivestock::with('livestock')->where('livestock_id', $this->livestockId)->first();
    //         $populasiAwal = $ternak->livestock->populasi_awal;

    //         $existingRecord = Recording::where('livestock_id', $this->livestockId)
    //                                 ->whereDate('tanggal', $this->date)
    //                                 ->first();

    //         $previousOut = 0;
    //         if ($existingRecord) {
    //             $prevPayload = $existingRecord->payload ?? [];
    //             $previousOut = ($prevPayload['mortality'] ?? 0)
    //                          + ($prevPayload['culling'] ?? 0)
    //                          + ($prevPayload['sales_quantity'] ?? 0);
    //         }

    //         $newOut = (int) $this->mortality + (int) $this->culling + (int) $this->sales_quantity;

    //         $totalOutExceptToday = Recording::where('livestock_id', $this->livestockId)
    //             ->where('tanggal', '!=', $this->date)
    //             ->get()
    //             ->sum(function ($record) {
    //                 $payload = $record->payload ?? [];
    //                 return ($payload['mortality'] ?? 0)
    //                     + ($payload['culling'] ?? 0)
    //                     + ($payload['sales_quantity'] ?? 0);
    //             });

    //         $totalOut = $totalOutExceptToday + $newOut;

    //         if ($totalOut > $populasiAwal) {
    //             $this->dispatch('error', 'Total pengeluaran melebihi populasi awal!');
    //             return;
    //         }


    //         // dd($totalOut);

    //         $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
    //                                 ->whereDate('tanggal', $this->date)
    //                                 ->get();

    //         $startDate = Carbon::parse($ternak->livestock->start_date);
    //         $selectedDate = Carbon::parse($this->date);
    //         $age = $startDate->diffInDays($selectedDate);
    //         // Calculate stock_awal for the selected date
    //         $previousDate = $selectedDate->copy()->subDay()->format('Y-m-d');
    //         $previousRecording = Recording::where('livestock_id', $this->livestockId)
    //                                     ->whereDate('tanggal', $previousDate)
    //                                     ->first();

    //         $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->populasi_awal;

    //         // Calculate stock_akhir for the selected date
    //         $totalDeplesiHariIni = (int)($this->mortality ?? 0) + (int)($this->culling ?? 0) + (int)($this->sales_quantity ?? 0);
    //         $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

    //         $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0; // Assuming berat_awal exists
    //         $weightGain = ($this->weight_today ?? 0) - $weightYesterday;

    //         $recordingInput = [
    //             'livestock_id' => $this->livestockId,
    //             'tanggal' => $this->date,
    //             'age' => $age,
    //             'stock_awal' => $stockAwalHariIni,
    //             'stock_akhir' => $stockAkhirHariIni,
    //             'berat_hari_ini' => $this->weight_today ?? null,
    //             'berat_semalam' => $weightYesterday,
    //             'kenaikan_berat' => $weightGain,
    //             'pakan_jenis' => $weightGain,
    //             'pakan_harian' => $weightGain,
    //         ];

    //         // Format item quantities for stock reduction
    //         $stockData = [];
    //         foreach ($this->itemQuantities as $itemId => $quantity) {
    //             // $originalQuantity = $this->originalItemQuantities[$itemId] ?? 0;

    //             if ($quantity > 0) {
    //                 $stockData[] = [
    //                     'item_id' => $itemId,
    //                     'qty' => $quantity
    //                 ];
    //             }
    //         }

    //         // Save or Update Recording data

    //         // $sales = TernakJual::where('livestock_id', $this->livestockId)
    //         //                         ->where('transaksi_id', $transaksi->id)
    //         //                         ->whereDate('tanggal', $this->date)
    //         //                         ->get();
    //         //  if (!$ternak) {
    //         //      throw new \Exception("Data Ternak tidak ditemukan.");
    //         //  }

    //         // if($this->isEditing){
    //         //     if($this->mortality > 0 || $this->culling > 0 || $this->sales_quantity > 0){
    //         //         // Get existing deplesi records for this date
    //         //         $existingMati = $deplesi->where('jenis', 'Mati')->first();
    //         //         $existingAfkir = $deplesi->where('jenis', 'Afkir')->first();
    //         //         $existingSales = $sales->first();
                    
    //         //         // Update or create Mati deplesi
    //         //         if($this->mortality > 0){
    //         //             if($existingMati) {
    //         //                 // Update existing record
    //         //                 $existingMati->jumlah = $this->mortality;
    //         //                 $existingMati->updated_by = auth()->id();
    //         //                 $existingMati->save();
    //         //             } else {
    //         //                 // Create new record
    //         //                 $this->storeDeplesi('Mati', $this->mortality);
    //         //             }
    //         //         } else if($existingMati) {
    //         //             // If mortality is 0 but record exists, delete it
    //         //             $existingMati->delete();
    //         //         }
                    
    //         //         // Update or create Afkir deplesi
    //         //         if($this->culling > 0){
    //         //             if($existingAfkir) {
    //         //                 // Update existing record
    //         //                 $existingAfkir->jumlah = $this->culling;
    //         //                 $existingAfkir->updated_by = auth()->id();
    //         //                 $existingAfkir->save();
    //         //             } else {
    //         //                 // Create new record
    //         //                 $this->storeDeplesi('Afkir', $this->culling);
    //         //             }
    //         //         } else if($existingAfkir) {
    //         //             // If culling is 0 but record exists, delete it
    //         //             $existingAfkir->delete();
    //         //         }

    //         //         // Update or create Mati deplesi
    //         //         if($this->sales_quantity > 0){
    //         //             if($existingSales) {
    //         //                 // Update existing record
    //         //                 $existingSales->quantity = $this->sales_quantity;
    //         //                 $existingSales->updated_by = auth()->id();
    //         //                 $existingSales->save();
    //         //             } else {
    //         //                 // Create new record
    //         //                 $this->storeDeplesi('Mati', $this->sales_quantity);
    //         //             }
    //         //         } else if($existingSales) {
    //         //             // If sales_quantity is 0 but record exists, delete it
    //         //             $existingSales->delete();
    //         //         }
    //         //     }
    //         // }

            

    //         // Wrap database operation in a transaction (if applicable)
    //         DB::beginTransaction();

    //         $transaksi = $this->saveOrUpdateRecording($recordingInput);

    //         // Perform stock reduction using FIFO service if data exists
    //         if (!empty($stockData)) {
    //             $fifoData = [
    //                 'tanggal' => $this->date,
    //                 'livestock_id' => $this->livestockId,
    //                 'weight' => $this->weight_today, // Assuming weight_today is relevant here
    //                 'stock' => $stockData
    //             ];
    //             app(FIFOService::class)->reduceStockBaru($fifoData);
    //         }

            
    //         //check stocks
    //         $stockCheck = $this->checkStockByTernakId($this->livestockId);

    //         // Store deplesi if any mortality or culling exists
    //         if ($this->mortality > 0) {
    //             $this->storeDeplesi('Mati', $this->mortality);
    //         }

    //         if ($this->culling > 0) {
    //             $this->storeDeplesi('Afkir', $this->culling);
    //         }
            
    //         // Update current ternak quantity (This calculates total depletion over time)
    //         $this->updateCurrentLivestockQuantity();


    //         // Format item quantities for stock reduction
    //         // $stockData = [];
    //         // foreach ($this->itemQuantities as $itemId => $quantity) {
    //         //     // $originalQuantity = $this->originalItemQuantities[$itemId] ?? 0;

    //         //     if ($quantity > 0) {
    //         //         $stockData[] = [
    //         //             'item_id' => $itemId,
    //         //             'qty' => $quantity
    //         //         ];
    //         //     }
    //         // }

    //         // If editing previous data, revert the original stock reduction
    //         // if ($this->isEditing && !empty($this->originalItemQuantities)) {
    //         //     $revertData = [
    //         //         'livestock_id' => $this->livestockId,
    //         //         'tanggal' => $this->date,
    //         //         'stock' => []
    //         //     ];
                
    //         //     foreach ($this->originalItemQuantities as $itemId => $quantity) {
    //         //         if ($quantity > 0) {
    //         //             $revertData['stock'][] = [
    //         //                 'item_id' => $itemId,
    //         //                 'qty' => $quantity
    //         //             ];
    //         //         }
    //         //     }
                
    //         //     app(FIFOService::class)->revertStockReduction($revertData);
    //         // }

    //         // Perform stock reduction using FIFO service if data exists
    //         // if (!empty($stockData)) {
    //         //     $fifoData = [
    //         //         'tanggal' => $this->date,
    //         //         'livestock_id' => $this->livestockId,
    //         //         'weight' => $this->weight_today, // Assuming weight_today is relevant here
    //         //         'stock' => $stockData
    //         //     ];
    //         //     app(FIFOService::class)->reduceStockBaru($fifoData);
    //         // }

    //         // --- Prepare data for Recording ---
    //         // $ternak = CurrentLivestock::with('livestock')->where('livestock_id', $this->livestockId)->first();
    //         // if (!$ternak) {
    //         //     throw new \Exception("Data Ternak tidak ditemukan.");
    //         // }
    //         // $startDate = Carbon::parse($ternak->livestock->start_date);
    //         // $selectedDate = Carbon::parse($this->date);
    //         // $age = $startDate->diffInDays($selectedDate);

    //         // // Calculate stock_awal for the selected date
    //         // $previousDate = $selectedDate->copy()->subDay()->format('Y-m-d');
    //         // $previousRecording = Recording::where('livestock_id', $this->livestockId)
    //         //                             ->whereDate('tanggal', $previousDate)
    //         //                             ->first();

    //         // $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->populasi_awal;

    //         // // Calculate stock_akhir for the selected date
    //         // $totalDeplesiHariIni = (int)($this->mortality ?? 0) + (int)($this->culling ?? 0);
    //         // $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

    //         // // Calculate weight gain
    //         // // $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : ($ternak->livestock->berat_awal ?? 0); // Assuming berat_awal exists
    //         // $weightYesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0; // Assuming berat_awal exists
    //         // $weightGain = ($this->weight_today ?? 0) - $weightYesterday;

    //         // // dd($weightGain);


    //         // $recordingInput = [
    //         //     'livestock_id' => $this->livestockId,
    //         //     'tanggal' => $this->date,
    //         //     'age' => $age,
    //         //     'stock_awal' => $stockAwalHariIni,
    //         //     'stock_akhir' => $stockAkhirHariIni,
    //         //     'berat_hari_ini' => $this->weight_today ?? null,
    //         //     'berat_semalam' => $weightYesterday,
    //         //     'kenaikan_berat' => $weightGain,
    //         //     'pakan_jenis' => $weightGain,
    //         //     'pakan_harian' => $weightGain,
    //         // ];

    //         // // Save or Update Recording data
    //         // $transaksi = $this->saveOrUpdateRecording($recordingInput);

    //         // // Store sales data if any sales exist
    //         // if ($this->sales_quantity > 0) {
    //         //     // $this->storePenjualan(
    //         //     //     $this->livestockId, 
    //         //     //     $this->date, 
    //         //     //     $this->sales_quantity, 
    //         //     //     $this->sales_price, 
    //         //     //     $this->total_sales
    //         //     // );
    //         //     app(TernakService::class)->ternakJualBaru($validatedData, $transaksi);
    //         // }
            
    //         // Reload data displayed in the table
    //         $this->loadRecordingData(); 
    //         // --- End Recording ---


    //         DB::commit();


    //         // Optionally reset the input fields
    //         $this->reset(['date', 'age', 'stock_start', 'stock_end', 'mortality', 'culling', 'weight_today', 'weight_yesterday', 'weight_gain', 'sales_quantity', 'sales_price', 'total_sales']);
    //         $this->initializeItemQuantities(); // Reset item quantities inputs
    //         $this->loadStockData(); // Reload stock data for inputs
    //         $this->checkCurrentLivestockStock(); // Reload ternak stock info

    //         $this->dispatch('success', 'Data berhasil disimpan');

    //     } catch (ValidationException $e) {
    //         DB::rollBack(); // Rollback on validation exception too
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
        
    //         // Human-readable error message
    //         $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        
    //         // Detailed error message for logging (optional)
    //         $detailedErrorMessage = 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ', File: ' . $e->getFile() . ')';
        
    //         // Dispatch a user-friendly error event
    //         $this->dispatch('error', $errorMessage);
        
    //         // Log the detailed error for debugging
    //         Log::error($detailedErrorMessage);
    //         // DB::rollBack();
    //         // Log::error('Error saving record: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString()); // Log detailed error
    //         // $this->dispatch('error', 'Terjadi kesalahan saat memperbarui data. Silakan cek log.'); // More generic user message
    //     } finally {
    //         // Optional: Code that runs whether success or failure
    //     }
        
    // }

    // Add the new private method here
    // private function saveOrUpdateRecording($data)
    // {
    //     // dd($data['berat_hari_ini']);
    //     $payloadData = [
    //         'mortality' => $this->mortality ?? 0,
    //         'culling' => $this->culling ?? 0,
    //         'sales_quantity' => $this->sales_quantity ?? 0,
    //         'sales_price' => $this->sales_price ?? 0,
    //         'sales_weight' => $this->sales_weight ?? 0,
    //         'total_sales' => $this->total_sales ?? 0,

    //     ];

    //     $transaksi = Recording::updateOrCreate(
    //         [
    //             'livestock_id' => $data['livestock_id'],
    //             'tanggal' => $data['tanggal']
    //         ],
    //         [
    //             'age' => $data['age'],
    //             'stock_awal' => $data['stock_awal'],
    //             'stock_akhir' => $data['stock_akhir'],
    //             'berat_hari_ini' => $data['berat_hari_ini'] ?? null, // Use null if not provided
    //             'berat_semalam' => $data['berat_semalam'] ?? null, // Use null if not provided
    //             'kenaikan_berat' => $data['kenaikan_berat'] ?? null, // Use null if not provided
    //             'pakan_jenis' => $data['pakan_jenis'] ?? null, // Use null if not provided
    //             'pakan_harian' => $data['pakan_harian'] ?? 0, // Use null if not provided
    //             'payload' => $payloadData ?? [], // Use null if not provided
    //             'created_by' => auth()->id(), // Assuming you want to track creator
    //             'updated_by' => auth()->id(), // Assuming you want to track updater
    //         ]
    //     );

    //      // Paksa update `updated_by` jika tidak ada perubahan di data lain
    //     if (!$transaksi->wasRecentlyCreated && !$transaksi->wasChanged()) {
    //         $transaksi->updated_by = auth()->id();
    //         $transaksi->touch(); // update updated_at
    //     }

    //     logger('Changed: ', $transaksi->getChanges());


    //     return $transaksi;
    // }

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
            'items' => $this->items
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
                $query->select('id', 'name', 'start_date', 'populasi_awal')
                    ->with(['livestockDepletion' => function($q) {
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
                'stock_awal' => $currentLivestock->livestock->populasi_awal ?? 0,
                'stock_akhir' => $currentLivestock->stock_akhir ?? 0,
                'start_date' => $currentLivestock->livestock->start_date ?? null,
                'name' => $currentLivestock->livestock->name ?? 'Unknown',
                'mortality' => $totalMati,
                'culling' => $totalAfkir,
                'total_deplesi' => $totalDeplesi
            ];

            // Auto-fill the stock fields
            $this->stock_start = $currentLivestock->livestock->populasi_awal ?? 0;
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

    private function storeDeplesi($jenis, $jumlah)
    {
        if ($jumlah <= 0) {
            return;
        }

        return LivestockDepletion::updateOrCreate(
            [
                'livestock_id' => $this->livestockId,
                'tanggal' => $this->date,
                'jenis' => $jenis,
            ],
            [
                'jumlah' => $jumlah,
                // 'keterangan' => $this->keterangan ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]
        );
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
             // Fetch previous day's recording to get weight_yesterday if no recording for selected date
            $previousDate = Carbon::parse($value)->subDay()->format('Y-m-d');
            $previousRecording = Recording::where('livestock_id', $this->livestockId)
                                        ->whereDate('tanggal', $previousDate)
                                        ->first();
            $this->weight_yesterday = $previousRecording ? $previousRecording->berat_hari_ini : 0;
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

    private function updateCurrentLivestockQuantity()
    {
        if (!$this->livestockId) {
            return;
        }

        // $currentLivestock = CurrentLivestock::where('livestock_id', $this->livestockId)->first();
        $currentLivestock = CurrentLivestock::where('livestock_id', $this->livestockId)->first();

        // Get deplesi data for the selected date
        $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
            ->get();

        $sales = LivestockSalesItem::where('livestock_id', $this->livestockId)
            ->get();
        
        if ($currentLivestock) {
            DB::transaction(function () use ($currentLivestock, $deplesi, $sales) {
                // Calculate total deplesi for the day
                $totalDeplesi = $deplesi->sum('jumlah');
                $totalSales = $sales->sum('quantity');

                // dd($currentLivestock->livestock->populasi_awal);
                
                // Update current quantity
                $currentLivestock->quantity = $currentLivestock->livestock->populasi_awal - $totalDeplesi - $totalSales;
                $currentLivestock->save();

                // dd($currentLivestock->quantity);
                

                // Update parent ternak record
                // $ternak = $currentLivestock->livestock;
                // if ($ternak) {
                //     $ternak->current_quantity = $currentLivestock->quantity;
                //     $ternak->death_quantity += $this->mortality;
                //     $ternak->slaughter_quantity += $this->culling;
                //     $ternak->save();
                // }
            });
        }
    }

    // private function reduceItemStock($itemId, $quantityToReduce)
    // {
    //     if ($quantityToReduce <= 0) {
    //         return;
    //     }

    //     DB::transaction(function () use ($itemId, $quantityToReduce) {
    //         // Get available stock from TransaksiBeliDetail ordered by date (FIFO)
    //         $availableStocks = TransaksiBeliDetail::where('item_id', $itemId)
    //                 ->where('jenis', 'Pembelian')
    //                 ->where('sisa', '>', 0)
    //                 ->whereNotIn('jenis_barang', ['DOC'])
    //                 ->orderBy('tanggal', 'asc')
    //                 ->lockForUpdate() // Prevent race conditions
    //                 ->get();

    //         $remainingQty = $quantityToReduce;

    //         foreach ($availableStocks as $stock) {
    //             if ($remainingQty <= 0) break;

    //             $qtyToReduce = min($remainingQty, $stock->sisa);
                
    //             // Update the stock
    //             $stock->terpakai += $qtyToReduce;
    //             $stock->sisa -= $qtyToReduce;
    //             $stock->save();

    //             // Create stock history
    //             StockHistory::create([
    //                 'transaksi_id' => $stock->transaksi_id,
    //                 'item_id' => $itemId,
    //                 'qty_masuk' => 0,
    //                 'qty_keluar' => $qtyToReduce,
    //                 'qty_sisa' => $stock->sisa,
    //                 'keterangan' => 'Penggunaan Harian',
    //                 'created_by' => auth()->id()
    //             ]);

    //             $remainingQty -= $qtyToReduce;
    //         }

    //         // Update current stock
    //         $currentStock = CurrentStock::where('livestock_id', $this->livestockId)
    //             ->where('item_id', $itemId)
    //             ->first();

    //         if ($currentStock) {
    //             $currentStock->quantity -= $quantityToReduce;
    //             $currentStock->save();
    //         }
    //     });
    // }


    private function loadRecordingData()
    {
        if (!$this->livestockId) {
            return;
        }

        $ternak = CurrentLivestock::where('livestock_id', $this->livestockId)->first();
        if (!$ternak) {
            return;
        }

        $startDate = Carbon::parse($ternak->livestock->start_date);
        $today = Carbon::today();

        $records = collect();
        $currentDate = $startDate->copy();
        $stockAwal = $ternak->livestock->populasi_awal;
        $totalPakanUsage = 0;
        // $standarData = $ternak->livestock->data ? $ternak->livestock->data[0]['livestock_breed_standard'] : [];
        $data = json_decode($ternak->livestock->data, true); // Ubah string JSON ke array
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


    // private function loadRecordingData()
    // {
    //     if (!$this->livestockId) {
    //         return;
    //     }

    //     $ternak = CurrentLivestock::where('livestock_id', $this->livestockId)->first();
    //     $recordingData = Recording::where('livestock_id', $this->livestockId);
        
    //     if (!$ternak) {
    //         return;
    //     }

    //     // dd($ternak->livestock->data);

    //     // Get start date from kelompok ternak
    //     $startDate = Carbon::parse($ternak->livestock->start_date);
    //     $today = Carbon::today();

    //     // Generate daily records
    //     $records = collect();
    //     $currentDate = $startDate->copy();
    //     $stockAwal = $ternak->livestock->populasi_awal;
    //     $totalPakanUsage = 0; // Initialize cumulative pakan usage
    //     $standarData = $ternak->livestock->data ? $ternak->livestock->data[0]['livestock_breed_standard'] : [];

    //     // dd($standarData['data'][0]);



    //     while ($currentDate <= $today) {
    //         $dateStr = $currentDate->format('Y-m-d');
            
    //         // Get deplesi for this date
    //         $deplesi = LivestockDepletion::where('livestock_id', $this->livestockId)
    //             ->whereDate('tanggal', $dateStr)
    //             ->get();
                
    //         $mortality = $deplesi->where('jenis', 'Mati')->sum('jumlah');
    //         $culling = $deplesi->where('jenis', 'Afkir')->sum('jumlah');
    //         $totalDeplesi = $mortality + $culling;
            
    //         // Calculate age
    //         $age = $startDate->diffInDays($currentDate);
            
    //         // // Get pakan usage for this date
    //         // $pakanUsage = TransaksiHarianDetail::whereHas('transaksiHarian', function($query) use ($dateStr) {
    //         //         $query->where('livestock_id', $this->livestockId)
    //         //             ->whereDate('tanggal', $dateStr);
    //         //     })->get();

    //         // Get pakan usage for this date
    //         $pakanUsage = TransaksiHarianDetail::whereHas('transaksiHarian', function($query) use ($dateStr) {
    //             $query->where('livestock_id', $this->livestockId)
    //                 ->whereDate('tanggal', $dateStr);
    //         })
    //         ->whereHas('item.category', function($query) {
    //             $query->where('name', 'Pakan');
    //         })
    //         ->get();

    //         $pakanHarian = $pakanUsage->sum('quantity');
    //         $totalPakanUsage += $pakanHarian; // Add today's usage to cumulative total

    //         $record = [
    //             'tanggal' => $dateStr,
    //             'age' => $age,
    //             'fcr_target' => isset($standarData['data'][$age]) ? $standarData['data'][$age]['fcr']['target'] : 0,
    //             'stock_awal' => $stockAwal,
    //             'mati' => $mortality,
    //             'afkir' => $culling,
    //             'total_deplesi' => $totalDeplesi,
    //             'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
    //             'stock_akhir' => $stockAwal - $totalDeplesi,
    //             'pakan_jenis' => $pakanUsage->pluck('item.name')->first() ?? '-',
    //             'pakan_harian' => $pakanHarian,
    //             'pakan_total' => $totalPakanUsage, // Use cumulative total
    //         ];

    //         // $record = [
    //         //     'tanggal' => $dateStr,
    //         //     'age' => $age,
    //         //     'stock_awal' => $stockAwal,
    //         //     'mati' => $mortality,
    //         //     'afkir' => $culling,
    //         //     'total_deplesi' => $totalDeplesi,
    //         //     'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
    //         //     'stock_akhir' => $stockAwal - $totalDeplesi,
    //         //     'pakan_jenis' => $pakanUsage->pluck('item.name')->first(),
    //         //     'pakan_harian' => $pakanUsage->sum('quantity'),
    //         //     'pakan_total' => $pakanUsage->sum('total_berat'),
    //         // ];

    //         $records->push($record);
            
    //         // Update stock for next iteration
    //         $stockAwal = $record['stock_akhir'];
    //         $currentDate->addDay();
    //     }

    //     $this->recordings = $records;

    //     // dd($records);
    // }

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
            
            // Create a stock history entry
            // StockHistory::create([
            //     'transaksi_id' => null, // Or generate a unique ID
            //     'item_id' => $itemId,
            //     'qty_masuk' => $quantity,
            //     'qty_keluar' => 0,
            //     'qty_sisa' => $currentStock->quantity,
            //     'keterangan' => 'Koreksi Penggunaan Harian',
            //     'created_by' => auth()->id()
            // ]);
        }
    }


}