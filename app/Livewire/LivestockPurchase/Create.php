<?php

namespace App\Livewire\LivestockPurchase;

use App\Services\AuditTrailService;

use App\Models\CurrentSupply;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ekspedisi;
use  App\Models\Expedition;
use App\Models\Coop;
use App\Models\Farm;
use App\Models\LivestockStrainStandard;
use App\Models\Supply;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyPurchase;
use App\Models\SupplyStock;
use App\Models\Rekanan;
use App\Models\Partner;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\LivestockPurchase;
use App\Models\CurrentLivestock;
use App\Models\LivestockBatch;
use App\Models\Livestock;
use App\Models\LivestockStrain;
use App\Models\Kandang;
use App\Models\LivestockPurchaseItem;

class Create extends Component
{
    use WithFileUploads;

    public $livestockId;
    public $invoice_number;
    public $date;
    public $supplier_id;
    public $expedition_id;
    public $expedition_fee = 0;
    public $items = [];
    public $livestock_id;
    public $batch_name;
    public $farm_id;
    public $coop_id;
    public $pembelianId;
    public $showForm = false;
    public $edit_mode = false;
    public $errorItems = [];
    public $availableKandangs = [];
    public $maxItems = 3;

    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    protected $listeners = [
        'deleteLivestockPurchase' => 'deleteLivestockPurchase',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',

    ];

    public function mount()
    {
        $this->authorize('read livestock purchasing');
    }

    public function updatedFarmId($value)
    {
        $this->coop_id = null;
        if ($value) {
            $this->availableKandangs = Coop::where('farm_id', $value)
                ->where('status', '!=', 'inactive')
                ->whereRaw('quantity < capacity')
                ->get();
        } else {
            $this->availableKandangs = [];
        }
    }

    public function loadCoop($value)
    {
        if ($value) {
            $this->availableKandangs = Coop::where('farm_id', $value)
                ->where('status', '!=', 'inactive')
                ->whereRaw('quantity < capacity')
                ->get();
        } else {
            $this->availableKandangs = [];
        }
    }



    public function addItem()
    {
        if (count($this->items) >= $this->maxItems) {
            throw ValidationException::withMessages([
                'items' => "Jumlah item tidak boleh lebih dari {$this->maxItems}."
            ]);
        }

        $this->validate([
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:partners,id',
            'expedition_fee' => 'numeric|min:0',
            'farm_id' => 'required|exists:farms,id',
            'coop_id' => 'required|exists:coops,id',
        ]);

        $this->items[] = [
            'livestock_strain_id' => null,
            'quantity' => null,
            'price_value' => null,
            'price_type' => 'per_unit',
            'weight_value' => null,
            'weight_type' => 'per_unit',
            'tax_percentage' => null,
            'notes' => null,
            'farm_id' => $this->farm_id,
            'coop_id' => $this->coop_id,
            'start_date' => $this->date,
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Calculate totals for a livestock record based on all its batches
     */
    private function calculateLivestockTotals($livestock, $farm, $kandang)
    {
        $allBatches = LivestockBatch::where([
            'livestock_id' => $livestock->id,
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
        ])->get();

        $totalPopulasi = $allBatches->sum('populasi_awal');
        $totalBerat = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->berat_awal;
        });
        $avgBerat = $totalPopulasi > 0 ? $totalBerat / $totalPopulasi : 0;
        $totalHarga = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->harga;
        });
        $avgHarga = $totalPopulasi > 0 ? $totalHarga / $totalPopulasi : 0;

        Log::info("Calculated livestock totals", [
            'livestock_id' => $livestock->id,
            'total_populasi' => $totalPopulasi,
            'total_berat' => $totalBerat,
            'avg_berat' => $avgBerat,
            'total_harga' => $totalHarga,
            'avg_harga' => $avgHarga,
            'batch_count' => $allBatches->count()
        ]);

        return [
            'total_populasi' => $totalPopulasi,
            'total_berat' => $totalBerat,
            'avg_berat' => $avgBerat,
            'total_harga' => $totalHarga,
            'avg_harga' => $avgHarga
        ];
    }

    /**
     * Update an existing livestock record with new totals
     */
    private function updateExistingLivestock($livestock, $breed, $farm, $kandang, $item)
    {
        $totals = $this->calculateLivestockTotals($livestock, $farm, $kandang);

        $livestock->update([
            'breed' => $breed->name,
            'populasi_awal' => $totals['total_populasi'],
            'berat_awal' => $totals['avg_berat'],
            'harga' => $totals['avg_harga'],
            'start_date' => $item['start_date'],
            'updated_by' => auth()->id(),
        ]);

        Log::info("Updated existing livestock record", [
            'livestock_id' => $livestock->id,
            'new_populasi' => $totals['total_populasi'],
            'new_berat' => $totals['avg_berat'],
            'new_harga' => $totals['avg_harga']
        ]);

        return $livestock;
    }

    /**
     * Create a new livestock record
     */
    private function createNewLivestock($breed, $farm, $kandang, $item)
    {
        $livestock = Livestock::create([
            'livestock_breed_id' => $breed->id,
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
            'breed' => $breed->name,
            'initial_quantity' => $item['quantity'],
            'initial_weight' => $item['weight_per_unit'],
            'initial_price' => $item['price_per_unit'],
            'start_date' => $item['start_date'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Log::info("Created new livestock record", [
            'livestock_id' => $livestock->id,
            'initial_quantity' => $item['quantity'],
            'initial_weight' => $item['weight_per_unit'],
            'initial_price' => $item['price_per_unit']
        ]);

        return $livestock;
    }

    /**
     * Calculate totals for CurrentLivestock based on all batches
     */
    private function calculateCurrentLivestockTotals($farm, $kandang, $livestock)
    {
        $allBatches = LivestockBatch::where([
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
            'livestock_id' => $livestock->id,
        ])->get();

        $totalQuantity = $allBatches->sum('populasi_awal');
        $totalWeight = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->berat_awal;
        });
        $avgWeight = $totalQuantity > 0 ? $totalWeight / $totalQuantity : 0;

        Log::info("Calculated CurrentLivestock totals", [
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
            'livestock_id' => $livestock->id,
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'avg_weight' => $avgWeight,
            'batch_count' => $allBatches->count()
        ]);

        return [
            'quantity' => $totalQuantity,
            'berat_total' => $totalWeight,
            'avg_berat' => $avgWeight
        ];
    }

    /**
     * Update or create CurrentLivestock record
     */
    private function updateCurrentLivestock($farm, $kandang, $livestock)
    {
        $totals = $this->calculateCurrentLivestockTotals($farm, $kandang, $livestock);

        $currentLivestock = CurrentLivestock::updateOrCreate(
            [
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'livestock_id' => $livestock->id,
            ],
            [
                'quantity' => $totals['quantity'],
                'berat_total' => $totals['berat_total'],
                'avg_berat' => $totals['avg_berat'],
                'age' => 0,
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        Log::info("Updated CurrentLivestock record", [
            'current_livestock_id' => $currentLivestock->id,
            'new_quantity' => $totals['quantity'],
            'new_berat_total' => $totals['berat_total'],
            'new_avg_berat' => $totals['avg_berat']
        ]);

        return $currentLivestock;
    }

    /**
     * Process a single livestock purchase item
     */
    private function processPurchaseItem($purchase, $item, $breed, $farm, $kandang)
    {
        // Find or create livestock record
        $livestock = Livestock::where([
            'livestock_breed_id' => $breed->id,
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
        ])->first();

        if (!$livestock) {
            // Create new livestock record with initial values
            $livestock = Livestock::create([
                'livestock_breed_id' => $breed->id,
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'breed' => $breed->name,
                'initial_quantity' => $item['quantity'],
                'initial_weight' => $item['weight_per_unit'],
                'initial_price' => $item['price_per_unit'],
                'start_date' => $this->date,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            Log::info("Created new livestock record", [
                'livestock_id' => $livestock->id,
                'initial_quantity' => $item['quantity'],
                'initial_weight' => $item['weight_per_unit'],
                'initial_price' => $item['price_per_unit']
            ]);
        }

        // Create or update purchase item
        $purchaseItem = LivestockPurchaseItem::updateOrCreate(
            [
                'livestock_purchase_id' => $purchase->id,
                'livestock_id' => $livestock->id,
            ],
            [
                'quantity' => $item['quantity'],
                'price_per_unit' => $item['price_per_unit'],
                'price_total' => $item['price_total'],
                'weight_per_unit' => $item['weight_per_unit'],
                'weight_total' => $item['weight_total'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        // Create or update livestock batch
        $batch = LivestockBatch::updateOrCreate(
            [
                'livestock_purchase_item_id' => $purchaseItem->id,
                'livestock_id' => $livestock->id,
            ],
            [
                'source_type' => 'purchase',
                'source_id' => $purchase->id,
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'initial_quantity' => $item['quantity'],
                'initial_weight' => $item['weight_per_unit'],
                'initial_price' => $item['price_per_unit'],
                'name' => $breed->name,
                'breed' => $breed->name,
                'start_date' => $item['start_date'],
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        Log::info("Created/Updated livestock batch", [
            'batch_id' => $batch->id,
            'initial_quantity' => $item['quantity'],
            'initial_weight' => $item['weight_per_unit'],
            'initial_price' => $item['price_per_unit']
        ]);

        // Recalculate Livestock values based on all batches
        $allBatches = LivestockBatch::where([
            'livestock_id' => $livestock->id,
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
        ])->get();

        $totalPopulasi = $allBatches->sum('populasi_awal');
        $totalBerat = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->berat_awal;
        });
        $avgBerat = $totalPopulasi > 0 ? $totalBerat / $totalPopulasi : 0;
        $totalHarga = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->harga;
        });
        $avgHarga = $totalPopulasi > 0 ? $totalHarga / $totalPopulasi : 0;

        // Update livestock record with recalculated values
        $livestock->update([
            'breed' => $breed->name,
            'populasi_awal' => $totalPopulasi,
            'berat_awal' => $avgBerat,
            'harga' => $avgHarga,
            'start_date' => $item['start_date'],
            'updated_by' => auth()->id(),
        ]);

        Log::info("Updated livestock record after batch changes", [
            'livestock_id' => $livestock->id,
            'total_populasi' => $totalPopulasi,
            'avg_berat' => $avgBerat,
            'avg_harga' => $avgHarga,
            'batch_count' => $allBatches->count()
        ]);

        // Update CurrentLivestock
        $this->updateCurrentLivestock($farm, $kandang, $livestock);

        return [
            'livestock' => $livestock,
            'purchaseItem' => $purchaseItem,
            'batch' => $batch
        ];
    }

    /**
     * Check if adding new batches would exceed kandang capacity
     */
    private function validateKandangCapacity($item, $kandang)
    {
        // Get current population in kandang from LivestockPurchaseItem
        $currentPopulation = LivestockPurchaseItem::join('livestocks', 'livestock_purchase_items.livestock_id', '=', 'livestocks.id')
            ->where('livestocks.farm_id', $this->farm_id)
            ->where('livestocks.coop_id', $this->coop_id)
            ->sum('livestock_purchase_items.quantity');

        // If this is an update, subtract the existing purchase item population
        if ($this->pembelianId) {
            $existingItem = LivestockPurchaseItem::join('livestocks', 'livestock_purchase_items.livestock_id', '=', 'livestocks.id')
                ->where('livestocks.farm_id', $this->farm_id)
                ->where('livestocks.coop_id', $this->coop_id)
                ->where('livestocks.livestock_strain_id', $item['livestock_strain_id'])
                ->where('livestock_purchase_items.livestock_purchase_id', $this->pembelianId)
                ->first();

            if ($existingItem) {
                $currentPopulation -= $existingItem->quantity;
            }
        }

        // Calculate total population after adding new batch
        $totalPopulation = $currentPopulation + $item['quantity'];

        Log::info("Validating kandang capacity", [
            'coop_id' => $kandang->id,
            'kandang_nama' => $kandang->nama,
            'current_population' => $currentPopulation,
            'new_population' => $item['quantity'],
            'total_population' => $totalPopulation,
            'capacity' => $kandang->kapasitas,
            'is_update' => (bool)$this->pembelianId
        ]);

        // Check if total exceeds capacity
        if ($totalPopulation > $kandang->capacity) {
            throw ValidationException::withMessages([
                'items' => "Total populasi ({$totalPopulation}) melebihi kapasitas kandang {$kandang->name} ({$kandang->capacity})"
            ]);
        }

        return true;
    }

    public function save()
    {
        // dd($this->items);
        $this->authorize($this->pembelianId ? 'update livestock purchasing' : 'create livestock purchasing');
        $this->errorItems = [];

        try {
            $validated = $this->validate([
                'invoice_number' => 'required|string',
                // 'batch_name' => 'required|string',
                'date' => 'required|date',
                'supplier_id' => 'required|exists:partners,id',
                'expedition_fee' => 'numeric|min:0',
                'farm_id' => 'required|exists:farms,id',
                'coop_id' => 'required|exists:coops,id',
                'items' => 'required|array|min:1',
                'items.*.livestock_strain_id' => 'required|exists:livestock_strains,id',
                'items.*.livestock_strain_standard_id' => 'nullable|exists:livestock_strain_standards,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.price_value' => 'required|numeric|min:0',
                'items.*.price_type' => 'required|in:per_unit,total',
                'items.*.weight_value' => 'required|numeric|min:0',
                'items.*.weight_type' => 'required|in:per_unit,total',
                // 'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
                'items.*.notes' => 'nullable|string',
                // 'items.*.farm_id' => 'required|exists:farms,id',
                // 'items.*.coop_id' => 'required|exists:coops,id',
                // 'items.*.start_date' => 'required|date',
            ]);
            $this->errorItems = [];
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
            foreach ($e->validator->errors()->getMessages() as $key => $messages) {
                if (preg_match('/items\\.(\\d+)\\./', $key, $m)) {
                    $idx = (int)$m[1];
                    $this->errorItems[$idx] = implode(' ', $messages);
                }
            }
            return;
        }

        DB::beginTransaction();

        try {

            // Validate kandang capacity for each item
            foreach ($this->items as $idx => $item) {
                try {
                    $kandang = Coop::findOrFail($this->coop_id);
                    $this->validateKandangCapacity($item, $kandang);
                } catch (ValidationException $e) {
                    $this->errorItems[$idx] = $e->validator->errors()->first();
                    $this->dispatch('validation-errors', ['errors' => array_values($this->errorItems)]);
                    DB::rollBack();
                    return;
                }
            }

            // Create or update the livestock purchase
            $purchaseData = [
                'invoice_number' => $this->invoice_number,
                'tanggal' => $this->date,
                'vendor_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id ?? null,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            $purchase = LivestockPurchase::updateOrCreate(
                ['id' => $this->pembelianId],
                $purchaseData
            );

            // Process each item
            foreach ($this->items as $item) {
                $breed = LivestockStrain::findOrFail($item['livestock_strain_id']);
                $farm = Farm::findOrFail($this->farm_id);
                $kandang = Coop::findOrFail($this->coop_id);

                // Initialize batch name
                $periodeFormat = 'PR-' . $farm->code . '-' . $kandang->code . '-' . Carbon::parse($purchase->tanggal)->format('dmY');
                $periode = $this->batch_name ?? $periodeFormat;

                // Find or create livestock record
                $livestock = Livestock::firstOrCreate(
                    [
                        'livestock_strain_id' => $breed->id,
                        'farm_id' => $farm->id,
                        'coop_id' => $kandang->id,
                    ],
                    [
                        'name' => $periode,
                        'livestock_strain_name' => $breed->name,
                        'start_date' => $this->date,
                        'initial_quantity' => $item['quantity'],
                        'initial_weight' => $item['weight_type'] === 'per_unit' ? $item['weight_value'] : ($item['weight_value'] / $item['quantity']),
                        'price' => $item['price_type'] === 'per_unit' ? $item['price_value'] : ($item['price_value'] / $item['quantity']),
                        'status' => 'active',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                // dd($livestock);

                // Create purchase item
                $purchaseItem = LivestockPurchaseItem::create([
                    'livestock_purchase_id' => $purchase->id,
                    'livestock_id' => $livestock->id,
                    'quantity' => $item['quantity'],
                    'price_value' => $item['price_value'],
                    'price_type' => $item['price_type'],
                    'tax_percentage' => optional($item)->tax_percentage,
                    'weight_value' => $item['weight_value'],
                    'weight_type' => $item['weight_type'],
                    'notes' => optional($item)->notes,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Create batch
                LivestockBatch::create([
                    'name' => $periode,
                    'livestock_strain_id' => $breed->id,
                    'livestock_strain_name' => $breed->name,
                    'livestock_id' => $livestock->id,
                    'start_date' => $this->date,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'livestock_purchase_item_id' => $purchaseItem->id,
                    'initial_quantity' => $item['quantity'],
                    'initial_weight' => $item['weight_type'] === 'per_unit' ? $item['weight_value'] : ($item['weight_value'] / $item['quantity']),
                    'weight' => $item['weight_type'] === 'per_unit' ? $item['weight_value'] : ($item['weight_value'] / $item['quantity']),
                    'weight_per_unit' => $item['weight_type'] === 'per_unit' ? $item['weight_value'] : ($item['weight_value'] / $item['quantity']),
                    'weight_total' => $item['weight_type'] === 'per_unit' ? $item['weight_value'] : ($item['weight_value'] / $item['quantity']),
                    'weight_type' => $item['weight_type'],
                    'weight_value' => $item['weight_value'],
                    'price_per_unit' => $item['price_type'] === 'per_unit' ? $item['price_value'] : ($item['price_value'] / $item['quantity']),
                    'price_total' => $item['price_type'] === 'per_unit' ? $item['price_value'] : ($item['price_value'] / $item['quantity']),
                    'price_type' => $item['price_type'],
                    'price_value' => $item['price_value'],
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Update CurrentLivestock
                $this->updateCurrentLivestock($farm, $kandang, $livestock);
            }

            DB::commit();
            $this->dispatch('success', 'Pembelian ternak berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan'));
            $this->close();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in LivestockPurchase save:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset();
        $this->items = [
            [
                'supply_id' => null,
                'quantity' => null,
                'unit' => null, // ← new: satuan yang dipilih user
                'price_per_unit' => null,
                'available_units' => [], // ← new: daftar satuan berdasarkan supply
            ],
        ];
    }

    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'supply_id') {
            $supply = Supply::find($value);

            if ($supply && isset($supply->payload['conversion_units'])) {
                $units = collect($supply->payload['conversion_units']);

                $this->items[$index]['available_units'] = $units->map(function ($unit) {
                    $unitModel = Unit::find($unit['unit_id']);
                    return [
                        'unit_id' => $unit['unit_id'],
                        'label' => $unitModel?->name ?? 'Unknown',
                        'value' => $unit['value'],
                        'is_smallest' => $unit['is_smallest'] ?? false,
                    ];
                })->toArray();

                // Set default unit based on is_default_purchase or first available unit
                $defaultUnit = $units->firstWhere('is_default_purchase', true) ?? $units->first();
                if ($defaultUnit) {
                    $this->items[$index]['unit_id'] = $defaultUnit['unit_id'];
                }
            } else {
                $this->items[$index]['available_units'] = [];
                $this->items[$index]['unit_id'] = null;
            }
        }
    }

    public function updateUnitConversion($index)
    {
        $unitId = $this->items[$index]['unit_id'] ?? null;
        $quantity = $this->items[$index]['quantity'] ?? null;
        $supplyId = $this->items[$index]['supply_id'] ?? null;

        if (!$unitId || !$quantity || !$supplyId) return;

        $supply = Supply::find($supplyId);
        if (!$supply || empty($supply->payload['conversion_units'])) return;

        $units = collect($supply->payload['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);

        if ($selectedUnit && $smallestUnit) {
            // Convert to smallest unit
            $this->items[$index]['converted_quantity'] = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];
        }
    }

    protected function convertToSmallestUnit($supply, $quantity, $unitId)
    {
        $units = collect($supply->payload['conversion_units'] ?? []);

        $from = $units->firstWhere('unit_id', $unitId);
        $smallest = $units->firstWhere('is_smallest', true);

        if (!$from || !$smallest || $from['value'] == 0 || $smallest['value'] == 0) {
            return $quantity; // fallback tanpa konversi
        }

        // dd([
        //     'quantity' => $quantity,
        //     'value' => $from['value'],
        //     'smallest' => $smallest['value'],
        // ]);
    }

    public function render()
    {
        $strains = LivestockStrain::active()->orderBy('name')->get();
        $standardStrains = LivestockStrainStandard::active()->orderBy('livestock_strain_name')->get();
        // Get farms based on user role
        $user = auth()->user();
        if ($user->hasRole('Operator')) {
            $farmIds = $user->farmOperators()->pluck('farm_id')->toArray();
            $farms = Farm::whereIn('id', $farmIds)->get(['id', 'name']);
        } else {
            $farms = Farm::where('status', 'active')->get(['id', 'name']);
        }

        return view('livewire.livestock-purchase.create', [
            'vendors' => Partner::where('type', 'Supplier')->get(),
            'expeditions' => Partner::where('type', 'Expedition')->get(),
            'strains' => $strains,
            'standardStrains' => $standardStrains,
            'farms' => $farms,
            'coops' => $this->availableKandangs,
        ]);
    }

    public function showCreateForm()
    {
        $this->authorize('create livestock purchasing');
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->resetErrorBag();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function close()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    /**
     * Update CurrentLivestock records based on batch deletions
     */
    private function updateCurrentLivestockRecords($adjustments, &$relatedRecordsForAudit)
    {
        Log::info("Updating CurrentLivestock records", ['adjustment_count' => count($adjustments)]);

        foreach ($adjustments as $key => $adjustment) {
            $currentLivestock = CurrentLivestock::where('farm_id', $adjustment['farm_id'])
                ->where('coop_id', $adjustment['coop_id'])
                ->first();

            if ($currentLivestock) {
                $newQuantity = max(0, $currentLivestock->quantity - $adjustment['quantity_to_deduct']);
                $newBeratTotal = max(0, $currentLivestock->berat_total - $adjustment['weight_to_deduct']);
                $newAvgBerat = $newQuantity > 0 ? $newBeratTotal / $newQuantity : 0;

                Log::info("Updating CurrentLivestock", [
                    'current_livestock_id' => $currentLivestock->id,
                    'old_quantity' => $currentLivestock->quantity,
                    'new_quantity' => $newQuantity,
                    'old_berat_total' => $currentLivestock->berat_total,
                    'new_berat_total' => $newBeratTotal
                ]);

                $currentLivestock->update([
                    'quantity' => $newQuantity,
                    'berat_total' => $newBeratTotal,
                    'avg_berat' => $newAvgBerat,
                    'updated_by' => auth()->id()
                ]);

                // Update audit trail data
                $auditIndex = array_search($currentLivestock->id, array_column($relatedRecordsForAudit['currentLivestock'], 'id'));
                if ($auditIndex !== false) {
                    $relatedRecordsForAudit['currentLivestock'][$auditIndex]['after_quantity'] = $newQuantity;
                    $relatedRecordsForAudit['currentLivestock'][$auditIndex]['after_berat_total'] = $newBeratTotal;
                    $relatedRecordsForAudit['currentLivestock'][$auditIndex]['deducted_quantity'] = $adjustment['quantity_to_deduct'];
                    $relatedRecordsForAudit['currentLivestock'][$auditIndex]['deducted_weight'] = $adjustment['weight_to_deduct'];
                }
            }
        }
    }

    /**
     * Update Livestock records based on batch deletions
     */
    private function updateLivestockRecords($livestock, $relatedBatches, &$relatedRecordsForAudit)
    {
        if (!$livestock) return;

        // Calculate total deductions
        $totalPopulasiDeducted = 0;
        $totalBeratDeducted = 0;
        $totalHargaDeducted = 0;

        foreach ($relatedBatches as $batch) {
            $totalPopulasiDeducted += $batch->populasi_awal;
            $totalBeratDeducted += ($batch->populasi_awal * $batch->berat_awal);
            $totalHargaDeducted += ($batch->populasi_awal * $batch->harga);
        }

        // Calculate new values
        $newPopulasi = max(0, $livestock->populasi_awal - $totalPopulasiDeducted);
        $newBeratAwal = $newPopulasi > 0 ? ($livestock->berat_awal * $livestock->populasi_awal - $totalBeratDeducted) / $newPopulasi : 0;
        $newHarga = $newPopulasi > 0 ? ($livestock->harga * $livestock->populasi_awal - $totalHargaDeducted) / $newPopulasi : 0;

        // Update livestock record
        $livestock->update([
            'populasi_awal' => $newPopulasi,
            'berat_awal' => $newBeratAwal,
            'harga' => $newHarga,
            'updated_by' => auth()->id()
        ]);

        // Update audit trail data
        $auditIndex = array_search($livestock->id, array_column($relatedRecordsForAudit['livestock'], 'id'));
        if ($auditIndex !== false) {
            $relatedRecordsForAudit['livestock'][$auditIndex]['after_populasi'] = $newPopulasi;
            $relatedRecordsForAudit['livestock'][$auditIndex]['after_berat'] = $newBeratAwal;
            $relatedRecordsForAudit['livestock'][$auditIndex]['after_harga'] = $newHarga;
            $relatedRecordsForAudit['livestock'][$auditIndex]['deducted_populasi'] = $totalPopulasiDeducted;
            $relatedRecordsForAudit['livestock'][$auditIndex]['deducted_berat'] = $totalBeratDeducted;
            $relatedRecordsForAudit['livestock'][$auditIndex]['deducted_harga'] = $totalHargaDeducted;
        }

        return $newPopulasi === 0; // Return true if livestock should be deleted
    }

    public function deleteLivestockPurchase($purchaseId)
    {
        $this->authorize('delete livestock purchasing');
        try {
            Log::info("Starting deletion process for livestock purchase", ['purchase_id' => $purchaseId]);
            DB::beginTransaction();

            // Load the purchase with necessary relationships
            $purchase = LivestockPurchase::with([
                'details',
                'details.livestockBatches',
                'details.livestock',
                'details.livestock.currentLivestock',
                'vendor'
            ])->findOrFail($purchaseId);


            // Check if any livestock has transactions that prevent deletion
            foreach ($purchase->details as $item) {
                $livestock = $item->livestock;
                if ($livestock && ($livestock->quantity_depletion > 0 || $livestock->quantity_sales > 0 || $livestock->quantity_mutated > 0)) {
                    $this->dispatch('error', 'Tidak bisa hapus karena sudah memiliki transaksi.');
                    return;
                }
            }

            // Track related records for the audit trail
            $relatedRecordsForAudit = [
                'livestockPurchases' => [],
                'livestockPurchaseItems' => [],
                'livestockBatches' => [],
                'livestock' => [],
                'currentLivestock' => []
            ];

            // Add purchase to related records for audit
            $purchaseData = $purchase->toArray();
            $purchaseData['vendor_name'] = $purchase->vendor->name ?? null;
            $relatedRecordsForAudit['livestockPurchases'][] = $purchaseData;

            // Track adjustments for CurrentLivestock by farm and kandang
            $adjustments = [];
            $livestockToDelete = [];

            // Collect data for purchase items and their related records for audit trail
            foreach ($purchase->details as $item) {
                // Add purchase item to audit trail
                $relatedRecordsForAudit['livestockPurchaseItems'][] = $item->toArray();

                // Get the livestock record
                $livestock = $item->livestock;
                if ($livestock) {
                    // Add livestock to audit trail
                    $relatedRecordsForAudit['livestock'][] = $livestock->toArray();

                    // Get current livestock record
                    $currentLivestock = $livestock->currentLivestock;
                    if ($currentLivestock) {
                        $currentLivestockData = $currentLivestock->toArray();
                        $currentLivestockData['after_quantity'] = null;
                        $currentLivestockData['after_berat_total'] = null;
                        $currentLivestockData['deducted_quantity'] = null;
                        $currentLivestockData['deducted_weight'] = null;
                        $relatedRecordsForAudit['currentLivestock'][] = $currentLivestockData;
                    }
                }

                // Collect related batches for this purchase item
                $relatedBatches = LivestockBatch::where('livestock_purchase_item_id', $item->id)->get();
                foreach ($relatedBatches as $batch) {
                    $relatedRecordsForAudit['livestockBatches'][] = $batch->toArray();

                    // Track adjustments for CurrentLivestock
                    $farmId = $batch->farm_id;
                    $kandangId = $batch->coop_id;
                    $key = "{$farmId}_{$kandangId}";

                    if (!isset($adjustments[$key])) {
                        $adjustments[$key] = [
                            'farm_id' => $farmId,
                            'coop_id' => $kandangId,
                            'quantity_to_deduct' => 0,
                            'weight_to_deduct' => 0
                        ];
                    }

                    $adjustments[$key]['quantity_to_deduct'] += $batch->populasi_awal;
                    $adjustments[$key]['weight_to_deduct'] += ($batch->populasi_awal * $batch->berat_awal);
                }
            }

            // First, process batches and update related records
            foreach ($purchase->details as $item) {
                // Get related batches before deletion
                $relatedBatches = LivestockBatch::where('livestock_purchase_item_id', $item->id)->get();

                // Update livestock record
                $shouldDeleteLivestock = $this->updateLivestockRecords($item->livestock, $relatedBatches, $relatedRecordsForAudit);

                // Delete batches for this purchase item
                LivestockBatch::where('livestock_purchase_item_id', $item->id)->delete();

                // If livestock should be deleted, add it to the list
                if ($shouldDeleteLivestock && $item->livestock) {
                    $livestockToDelete[] = $item->livestock;
                }
            }

            // Process livestock deletions
            foreach ($livestockToDelete as $livestock) {
                // Zero out and delete associated CurrentLivestock records
                $currentLivestockRecords = CurrentLivestock::where('livestock_id', $livestock->id)->get();
                foreach ($currentLivestockRecords as $currentLivestock) {
                    // Update audit trail data before zeroing out
                    $auditIndex = array_search($currentLivestock->id, array_column($relatedRecordsForAudit['currentLivestock'], 'id'));
                    if ($auditIndex !== false) {
                        $relatedRecordsForAudit['currentLivestock'][$auditIndex]['after_quantity'] = 0;
                        $relatedRecordsForAudit['currentLivestock'][$auditIndex]['after_berat_total'] = 0;
                        $relatedRecordsForAudit['currentLivestock'][$auditIndex]['deducted_quantity'] = $currentLivestock->quantity;
                        $relatedRecordsForAudit['currentLivestock'][$auditIndex]['deducted_weight'] = $currentLivestock->berat_total;
                    }

                    // Zero out the record
                    $currentLivestock->update([
                        'quantity' => 0,
                        'berat_total' => 0,
                        'avg_berat' => 0,
                        'updated_by' => auth()->id()
                    ]);
                }

                // Delete the zeroed out CurrentLivestock records
                CurrentLivestock::where('livestock_id', $livestock->id)->delete();

                // Then delete the livestock record
                $livestock->delete();
            }

            // Update CurrentLivestock records only if there are livestock records that weren't deleted
            if (empty($livestockToDelete)) {
                $this->updateCurrentLivestockRecords($adjustments, $relatedRecordsForAudit);
            }

            // Then delete the purchase items
            $purchase->details()->delete();

            // Finally delete the purchase record
            $purchase->delete();

            // Log to audit trail after deletion
            AuditTrailService::logCascadingDeletion(
                $purchase,
                $relatedRecordsForAudit,
                "User initiated deletion of livestock purchase (purchase, items, batches, and related records)"
            );

            DB::commit();
            $this->dispatch('success', 'Data pembelian, item terkait, batch, dan record terkait telah berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting livestock purchase", [
                'purchase_id' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data pembelian: ' . $e->getMessage());
        }
    }

    public function updateDoNumber($transaksiId, $newNoSj)
    {
        $this->authorize('edit livestock purchase');
        $transaksiDetail = SupplyPurchaseBatch::findOrFail($transaksiId);

        if ($transaksiDetail->exists()) {
            $transaksiDetail->do_number = $newNoSj;
            $transaksiDetail->save();
            $this->dispatch('noSjUpdated');
            $this->dispatch('success', 'Nomor Surat Jalan / Deliveri Order berhasil diperbarui.');
        } else {
            $this->dispatch('error', 'Tidak ada detail transaksi yang ditemukan.');
        }
    }

    public function showEditForm($id)
    {
        $this->authorize('update livestock purchasing');
        $this->pembelianId = $id;
        $pembelian = LivestockPurchase::with([
            'details',
            'details.livestock',
            'details.livestockBatches',
            'vendor'
        ])->findOrFail($id);

        // Check if any livestock has transactions that prevent editing
        foreach ($pembelian->details as $item) {
            $livestock = $item->livestock;
            if ($livestock && ($livestock->quantity_depletion > 0 || $livestock->quantity_sales > 0 || $livestock->quantity_mutated > 0)) {
                $this->dispatch('error', 'Tidak bisa edit karena sudah memiliki transaksi.');
                return;
            }
        }

        $this->items = [];
        if ($pembelian && $pembelian->details->isNotEmpty()) {
            $this->date = $pembelian->tanggal;
            $this->batch_name = $pembelian->details->first()->livestock->name;
            $this->invoice_number = $pembelian->invoice_number;
            $this->supplier_id = $pembelian->vendor_id;
            $this->expedition_id = $pembelian->expedition_id;
            $this->expedition_fee = $pembelian->expedition_fee;

            // Get farm_id and coop_id from the first livestock record
            $firstLivestock = $pembelian->details->first()->livestock;
            if ($firstLivestock) {
                $this->farm_id = $firstLivestock->farm_id;
                $this->coop_id = $firstLivestock->coop_id;
                $this->availableKandangs = Coop::where('farm_id', $this->farm_id)
                    ->where('status', '!=', 'inactive')
                    ->whereRaw('quantity < capacity')
                    ->get();
            }

            foreach ($pembelian->details as $item) {
                $livestock = $item->livestock;
                if ($livestock) {
                    $batch = $item->livestockBatches->first();
                    $this->items[] = [
                        'livestock_id' => $livestock->id,
                        'livestock_strain_id' => $livestock->livestock_strain_id,
                        'quantity' => $item->quantity,
                        'price_value' => $item->price_value,
                        'price_type' => $item->price_type,
                        'farm_id' => $livestock->farm_id,
                        'coop_id' => $livestock->coop_id,
                        'livestock_strain_standard_id' => $livestock->livestock_strain_standard_id,
                        'start_date' => $livestock->start_date,
                        'weight_type' => $item->weight_type,
                        'weight_value' => $item->weight_value,
                    ];
                }
            }
        }

        // dd($this->items);

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }
}
