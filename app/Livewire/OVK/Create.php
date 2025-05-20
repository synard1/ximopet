<?php

namespace App\Livewire\OVK;

use App\Services\AuditTrailService;
use App\Services\Livestock\LivestockCostService;

use App\Models\CurrentSupply;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ekspedisi;
use  App\Models\Expedition;
use App\Models\Farm;
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
use App\Models\Kandang;
use App\Models\Livestock;
use App\Models\OVKRecord;
use App\Models\OVKRecordItem;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use Carbon\Carbon;

class Create extends Component
{
    use WithFileUploads;

    public $livestockId;
    public $invoice_number;
    public $date;
    public $supplier_id;
    public $expedition_id;
    public $expedition_fee = 0;
    public $items = [], $availableItems = [];
    public $livestock_id;
    public $farm_id;
    public $recordId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;
    public $requiredFieldsFilled = false;
    public $errorItems = [];
    public $usage_date;
    public $kandang_id;
    public $notes;
    public $availableStock = 0;
    public $availableUnit = '';
    public $supplies = [];
    public $usages = [], $supplyUsageId = null;

    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    protected $listeners = [
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'deleteOVKRecord' => 'deleteOVKRecord',
        'cancel' => 'cancel',

    ];

    protected $rules = [
        'date' => 'required|date',
        'farm_id' => 'required|exists:farms,id',
    ];

    public function mount()
    {
        $this->usage_date = now()->format('Y-m-d');
        $this->date = now()->format('Y-m-d');
        // $this->livestocks = Livestock::where('status', 'active')->get();
        // dd($this->livestocks);

        $this->items = [
            [
                'supply_id' => '',
                'quantity' => '',
                'unit_id' => '',
                'notes' => '',
                'available_units' => [],
                'available_stock' => 0,
                'units' => []
            ]
        ];
    }

    public function addItem()
    {
        // Allow adding items even in edit mode
        $this->items[] = [
            'supply_id' => '',
            'quantity' => '',
            'unit_id' => '',
            'notes' => '',
            'available_units' => [],
            'available_stock' => 0,
            'units' => []
        ];
    }

    public function removeItem($index)
    {
        // In edit mode, only allow removing items if there's more than one
        if ($this->edit_mode && count($this->items) <= 1) {
            $this->dispatch('error', 'Tidak dapat menghapus item terakhir dalam mode edit');
            return;
        }

        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function save()
    {
        $this->errorItems = []; // Reset per-item errors

        // Basic validation for required fields and overall structure
        $this->validate([
            'date' => 'required|date',
            'livestock_id' => 'required|exists:livestocks,id',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.notes' => 'nullable|string',
        ]);

        // Additional per-item validation (duplicates, stock, and date validation)
        $inputDate = Carbon::parse($this->date)->startOfDay(); // Convert input date string to Carbon object at start of day
        $uniqueKeys = [];
        foreach ($this->items as $idx => $item) {
            // Validate duplicate combination of supply_id and unit_id
            $key = $item['supply_id'] . '-' . $item['unit_id'];
            if (in_array($key, $uniqueKeys)) {
                $this->errorItems[$idx] = 'Jenis supply dan satuan tidak boleh sama dengan baris lain.';
            } else {
                $uniqueKeys[] = $key;
            }

            // Validate quantity does not exceed available stock from CurrentSupply and check dates
            if (!empty($item['supply_id']) && $item['quantity'] !== null && $this->livestock_id) {
                $livestock = Livestock::findOrFail($this->livestock_id);
                $currentSupply = CurrentSupply::where('item_id', $item['supply_id'])
                    ->where('farm_id', $livestock->farm_id)
                    ->first();

                // Get unit conversion data for validation
                $supply = Supply::findOrFail($item['supply_id']);
                $units = collect($supply->payload['conversion_units'] ?? []);
                $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
                $smallestUnit = $units->firstWhere('is_smallest', true);

                if (!$selectedUnit || !$smallestUnit || $smallestUnit['value'] == 0) {
                    $this->errorItems[$idx] = "Informasi konversi unit tidak lengkap atau tidak valid untuk supply ini.";
                    continue;
                }

                $itemQuantityInSmallestUnit = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

                // Get available stock records ordered by date (FIFO)
                $availableStocks = SupplyStock::where('farm_id', $livestock->farm_id)
                    ->where('supply_id', $item['supply_id'])
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    // ->orderBy('created_at')
                    ->get();

                // Check if usage date is before earliest available stock date
                if ($availableStocks->isNotEmpty()) {
                    $earliestStockDate = $availableStocks->first()->date->startOfDay(); // Ensure earliestStockDate is also at start of day
                    // Allow same day usage but prevent usage before earliest stock date
                    if ($inputDate < $earliestStockDate) { // Compare Carbon objects
                        $this->errorItems[$idx] = "Tanggal penggunaan tidak boleh sebelum tanggal masuk supply terawal (" . $earliestStockDate->format('d/m/Y') . ").";
                        continue;
                    }
                }

                if ($currentSupply) {
                    // If in edit mode, add back the quantity of the existing item (in smallest unit) for comparison
                    $existingQuantityInSmallestUnit = 0;
                    if ($this->recordId) {
                        $existingItem = OVKRecordItem::where('ovk_record_id', $this->recordId)
                            ->where('supply_id', $item['supply_id'])
                            ->where('unit_id', $item['unit_id'])
                            ->first();
                        if ($existingItem) {
                            $existingItemUnit = $units->firstWhere('unit_id', $existingItem->unit_id);
                            if ($existingItemUnit && $smallestUnit['value'] > 0) {
                                $existingQuantityInSmallestUnit = ($existingItem->quantity * $existingItemUnit['value']) / $smallestUnit['value'];
                            }
                        }
                    }

                    $availableQuantityInSmallestUnit = $currentSupply->quantity + $existingQuantityInSmallestUnit;

                    if ($itemQuantityInSmallestUnit > $availableQuantityInSmallestUnit) {
                        // Convert available quantity back to the selected unit for the error message
                        $availableInSelectedUnit = ($availableQuantityInSmallestUnit * $smallestUnit['value']) / $selectedUnit['value'];
                        $unitName = Unit::find($item['unit_id'])?->name ?? 'Unit';
                        $this->errorItems[$idx] = "Jumlah melebihi stock yang tersedia. Tersedia: " . number_format($availableInSelectedUnit, 2) . " " . $unitName;
                    }
                } else {
                    // Handle case where CurrentSupply record is missing (treat as 0 stock for validation)
                    if ($itemQuantityInSmallestUnit > 0) {
                        $this->errorItems[$idx] = "Stock tidak tersedia untuk supply ini.";
                    }
                }
            }
        }

        if (!empty($this->errorItems)) {
            // Dispatch a single event with all item errors
            $this->dispatch('validation-errors', ['errors' => array_values($this->errorItems)]);
            return;
        }

        // If validation passes, proceed with saving/updating records
        try {
            DB::beginTransaction();

            $livestock = Livestock::findOrFail($this->livestock_id);

            // Get existing OVK record and its items if in edit mode
            $ovkRecord = null;
            $oldOvkItems = collect();
            $oldUsageDetails = collect();

            if ($this->recordId) {
                $ovkRecord = OVKRecord::with('items')->findOrFail($this->recordId);
                $oldOvkItems = $ovkRecord->items->keyBy(fn($item) => $item->supply_id . '-' . $item->unit_id);

                // Get existing SupplyUsage and its details
                $supplyUsage = SupplyUsage::where('usage_date', $ovkRecord->usage_date)
                    ->where('livestock_id', $ovkRecord->livestock_id)
                    ->first();

                if ($supplyUsage) {
                    $oldUsageDetails = $supplyUsage->details()->get();
                }
            }

            // Create or update OVK Record
            $ovkRecord = OVKRecord::updateOrCreate(
                ['id' => $this->recordId],
                [
                    'usage_date' => $this->date,
                    'farm_id' => $livestock->farm_id,
                    'kandang_id' => $livestock->kandang_id,
                    'livestock_id' => $this->livestock_id,
                    'notes' => $this->notes,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            // Find or create the related SupplyUsage record
            $supplyUsage = SupplyUsage::firstOrNew(
                [
                    'usage_date' => $this->date,
                    'livestock_id' => $this->livestock_id,
                ],
                [
                    'farm_id' => $livestock->farm_id,
                    'kandang_id' => $livestock->kandang_id,
                    'type' => 'ovk',
                    'notes' => $this->notes,
                    'created_by' => auth()->id(),
                ]
            );
            $supplyUsage->fill(['updated_by' => auth()->id()]);
            $supplyUsage->save();

            // Lock CurrentSupply records for the supplies in current items
            $currentSupplyIds = collect($this->items)->pluck('supply_id')->unique()->toArray();
            if (!empty($currentSupplyIds)) {
                CurrentSupply::where('farm_id', $livestock->farm_id)
                    ->whereIn('item_id', $currentSupplyIds)
                    ->lockForUpdate()
                    ->get();
            }

            // Process current items (OVKRecordItems and SupplyUsageDetails)
            foreach ($this->items as $itemData) {
                $supply = Supply::findOrFail($itemData['supply_id']);

                // Find the existing OVK item for the current itemData
                $existingOvkItem = $oldOvkItems->get($itemData['supply_id'] . '-' . $itemData['unit_id']);

                // Get unit conversion data and new quantity in smallest unit
                $units = collect($supply->payload['conversion_units'] ?? []);
                $selectedUnit = $units->firstWhere('unit_id', $itemData['unit_id']);
                $smallestUnit = $units->firstWhere('is_smallest', true);

                if (!$selectedUnit || !$smallestUnit || $smallestUnit['value'] == 0) {
                    throw new \Exception("Invalid unit conversion data for supply: {$supply->name}");
                }

                // Convert new quantity to smallest unit
                $newQuantityInSmallestUnit = ($itemData['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

                // Get CurrentSupply record (already locked above)
                $currentSupply = CurrentSupply::where('item_id', $itemData['supply_id'])
                    ->where('farm_id', $livestock->farm_id)
                    ->first();

                if (!$currentSupply) {
                    throw new \Exception("Current supply record not found for item: {$supply->name} on farm: {$livestock->farm->name}");
                }

                // Handle quantity updates for CurrentSupply
                if ($this->recordId) {
                    // In edit mode, first return the old quantity
                    if ($existingOvkItem) {
                        $oldOvkItemUnit = $units->firstWhere('unit_id', $existingOvkItem->unit_id);
                        if ($oldOvkItemUnit && $smallestUnit['value'] > 0) {
                            $oldQuantityInSmallestUnit = ($existingOvkItem->quantity * $oldOvkItemUnit['value']) / $smallestUnit['value'];
                            // Return the old quantity to CurrentSupply
                            $currentSupply->increment('quantity', $oldQuantityInSmallestUnit);
                        }
                    }
                }

                // Deduct the new quantity from CurrentSupply
                $currentSupply->decrement('quantity', $newQuantityInSmallestUnit);

                // --- Handle SupplyUsageDetail updates ---

                // If this is an update, first return all quantities to their original stock batches
                if ($this->recordId) {
                    $existingDetailsForSupply = $oldUsageDetails->where('supply_id', $itemData['supply_id']);

                    if ($existingDetailsForSupply->isNotEmpty()) {
                        // Get all affected stock records and lock them
                        $stockIds = $existingDetailsForSupply->pluck('supply_stock_id')->unique()->toArray();
                        $stocks = SupplyStock::whereIn('id', $stockIds)
                            ->lockForUpdate()
                            ->get()
                            ->keyBy('id');

                        // Force delete the old details
                        foreach ($existingDetailsForSupply as $detail) {
                            $detail->forceDelete();
                        }

                        // Recalculate quantity_used for affected stocks
                        foreach ($stocks as $stock) {
                            $stock->recalculateQuantityUsed();
                        }
                    }
                }

                // Allocate the new total quantity from available stock (FIFO)
                $remainingQuantityToAllocate = $newQuantityInSmallestUnit;
                $allocatedStocks = collect();

                if ($remainingQuantityToAllocate > 0) {
                    // Get available stock records for this supply, ordered by date (FIFO)
                    $stocks = SupplyStock::where('farm_id', $livestock->farm_id)
                        ->where('supply_id', $itemData['supply_id'])
                        ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                        ->orderBy('date')
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->get();

                    foreach ($stocks as $stock) {
                        $availableInStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

                        if ($availableInStock <= 0) continue;

                        $quantityToUseFromStock = min($remainingQuantityToAllocate, $availableInStock);

                        if ($quantityToUseFromStock > 0) {
                            // Create new SupplyUsageDetail for this stock batch
                            SupplyUsageDetail::create([
                                'supply_usage_id' => $supplyUsage->id,
                                'supply_id' => $itemData['supply_id'],
                                'supply_stock_id' => $stock->id,
                                'quantity_taken' => $quantityToUseFromStock,
                                'notes' => 'Original Input: ' . $itemData['quantity'] . ' ' . (Unit::find($itemData['unit_id'])?->name ?? 'Unit') . (
                                    isset($itemData['notes']) && !empty($itemData['notes']) ? '; ' . $itemData['notes'] : ''
                                ),
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);

                            $allocatedStocks->push($stock);
                            $remainingQuantityToAllocate -= $quantityToUseFromStock;
                            if ($remainingQuantityToAllocate <= 0) break;
                        }
                    }
                }

                // If we still have remaining quantity after allocation attempt, it means insufficient stock
                if ($remainingQuantityToAllocate > 0) {
                    throw new \Exception("Insufficient stock available to fulfill request for supply: {$supply->name}");
                }

                // Recalculate quantity_used for all affected stocks
                foreach ($allocatedStocks as $stock) {
                    $stock->recalculateQuantityUsed();
                }

                // --- Update or Create OVKRecordItem ---
                OVKRecordItem::updateOrCreate(
                    [
                        'ovk_record_id' => $ovkRecord->id,
                        'supply_id' => $itemData['supply_id'],
                        'unit_id' => $itemData['unit_id'],
                    ],
                    [
                        'quantity' => $itemData['quantity'],
                        'notes' => $itemData['notes'] ?? null,
                        'created_by' => $existingOvkItem ? $existingOvkItem->created_by : auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                // Remove processed OVK item from oldOvkItems to track items to delete
                if ($existingOvkItem) {
                    $oldOvkItems->forget($itemData['supply_id'] . '-' . $itemData['unit_id']);
                }
            }

            // --- Handle removed items ---
            foreach ($oldOvkItems as $ovkItemToDelete) {
                // Before deleting OVK item, return its quantity to CurrentSupply
                $supply = Supply::findOrFail($ovkItemToDelete->supply_id);
                $units = collect($supply->payload['conversion_units'] ?? []);
                $ovkItemUnit = $units->firstWhere('unit_id', $ovkItemToDelete->unit_id);
                $smallestUnit = $units->firstWhere('is_smallest', true);

                if ($ovkItemUnit && $smallestUnit && $smallestUnit['value'] > 0) {
                    $quantityToAddBackToCurrent = ($ovkItemToDelete->quantity * $ovkItemUnit['value']) / $smallestUnit['value'];

                    // Get existing details for this supply
                    $existingDetails = $oldUsageDetails->where('supply_id', $ovkItemToDelete->supply_id);

                    if ($existingDetails->isNotEmpty()) {
                        // Get and lock affected stock records
                        $stockIds = $existingDetails->pluck('supply_stock_id')->unique()->toArray();
                        $stocks = SupplyStock::whereIn('id', $stockIds)
                            ->lockForUpdate()
                            ->get();

                        // Force delete the details
                        foreach ($existingDetails as $detail) {
                            $detail->forceDelete();
                        }

                        // Recalculate quantity_used for affected stocks
                        foreach ($stocks as $stock) {
                            $stock->recalculateQuantityUsed();
                        }
                    }

                    // Update CurrentSupply
                    $currentSupply = CurrentSupply::where('item_id', $ovkItemToDelete->supply_id)
                        ->where('farm_id', $livestock->farm_id)
                        ->lockForUpdate()
                        ->first();

                    if ($currentSupply) {
                        $currentSupply->increment('quantity', $quantityToAddBackToCurrent);
                    }
                }

                // Soft delete the OVKRecordItem
                $ovkItemToDelete->delete();
            }

            DB::commit();

            // Recalculate LivestockCost for the current date - only once after all changes
            app(LivestockCostService::class)->calculateForDate($this->livestock_id, $this->date);

            $this->dispatch('success', 'Data OVK berhasil ' . ($this->recordId ? 'diperbarui' : 'disimpan'));
            $this->resetForm();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OVK Save Error: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset([
            'recordId',
            'date',
            'livestock_id',
            'notes',
            'showForm',
            'edit_mode',
            'requiredFieldsFilled',
            'errorItems',
            'availableStock',
            'availableUnit', // Reset these too
        ]);
        $this->usage_date = now()->format('Y-m-d'); // Re-initialize date fields
        $this->date = now()->format('Y-m-d');
        $this->items = [ // Initialize with one empty item
            [
                'supply_id' => '',
                'quantity' => '',
                'unit_id' => '',
                'notes' => '',
                'available_units' => [],
                'available_stock' => 0,
                'units' => [],
                'current_supply' => 0, // Reset this as well
            ],
        ];
        $this->loadAvailableItems(); // Reload available items after reset
    }

    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'supply_id') {
            $supply = Supply::find($value);
            $livestock = $this->livestock_id ? Livestock::findOrFail($this->livestock_id) : null;

            if ($supply) {
                // Handle unit conversion data
                if (isset($supply->payload['conversion_units'])) {
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

                // Handle stock availability if livestock is selected
                if ($livestock) {
                    $stocks = SupplyStock::where('farm_id', $livestock->farm_id)
                        ->where('supply_id', $value)
                        ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                        ->orderBy('date')
                        ->orderBy('created_at')
                        ->get();

                    $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
                    $this->items[$index]['available_stock'] = $totalAvailable;

                    // Get current supply for additional stock info
                    $currentSupply = CurrentSupply::where('item_id', $value)
                        ->where('farm_id', $livestock->farm_id)
                        ->first();

                    // dd($currentSupply);

                    if ($currentSupply) {
                        $this->items[$index]['current_supply'] = $currentSupply->quantity;
                    }
                }

                // Set item type and additional metadata
                $this->items[$index]['type'] = 'supply';
                $this->items[$index]['name'] = $supply->name;
            } else {
                // Reset item data if supply not found
                $this->items[$index]['available_units'] = [];
                $this->items[$index]['unit_id'] = null;
                $this->items[$index]['available_stock'] = 0;
                $this->items[$index]['current_supply'] = 0;
                $this->items[$index]['type'] = null;
                $this->items[$index]['name'] = null;
            }
        }
    }

    public function updated($propertyName)
    {

        // Dipanggil setiap kali properti Livewire berubah
        if ($propertyName === 'livestock_id') {
            $this->checkAvailableStock();
        }
    }

    public function checkAvailableStock()
    {
        // dd($this->livestock_id);
        if ($this->livestock_id) {
            $livestock = Livestock::findOrFail($this->livestock_id);
            $stock = SupplyStock::where('farm_id', $livestock->farm_id)->first();

            // dd($stock);

            if ($stock) {
                $this->availableStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $this->availableUnit = $stock->feed->payload['unit_details']['name'] ?? '';
            } else {
                $this->availableStock = 0;
                $this->availableUnit = '';
            }
        } else {
            $this->availableStock = 0;
            $this->availableUnit = '';
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

    // public function updateUnitConversion($index)
    // {
    //     if (!empty($this->items[$index]['supply_id'])) {
    //         $supply = Supply::with('units')->find($this->items[$index]['supply_id']);
    //         if ($supply) {
    //             $this->items[$index]['available_units'] = $supply->units->map(function ($unit) {
    //                 return [
    //                     'unit_id' => $unit->id,
    //                     'label' => $unit->name
    //                 ];
    //             })->toArray();
    //         }
    //     }
    // }

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

    // public function handleItemSelected($index)
    // {
    //     $itemId = $this->items[$index]['supply_id'] ?? null;

    //     $selected = collect($this->availableItems)->firstWhere('id', $itemId);
    //     $livestock = Livestock::findOrFail($this->livestock_id);

    //     // dd($selected);

    //     if (!$selected) return;

    //     $this->items[$index]['type'] = $selected['type'];

    //     if ($selected['type'] === 'supply') {
    //         $supply = Supply::find($itemId);
    //         $this->items[$index]['units'] = $supply?->conversionUnits?->map(fn($u) => [
    //             'id' => $u->conversion_unit_id,
    //             'name' => optional($u->conversionUnit)->name,
    //         ])->toArray() ?? [];

    //         $stocks = SupplyStock::where('farm_id', $livestock->farm_id)
    //             ->where('supply_id', $itemId)
    //             ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //             ->orderBy('date')
    //             ->orderBy('created_at')
    //             ->get();

    //         // dd($stocks);

    //         $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
    //         $this->items[$index]['available_stock'] = $totalAvailable;
    //     }

    //     dd($this->items[$index]['units']);
    // }

    // public function updatedFarmId()
    // {
    //     $this->loadAvailableItems();
    // }

    public function loadAvailableItems()
    {
        if (!$this->livestock_id) {
            $this->availableItems = [];
            return;
        }

        $livestock = Livestock::findOrFail($this->livestock_id);

        $feedItems = SupplyStock::where('farm_id', $livestock->farm_id)
            ->with('supply:id,name')
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->get()
            ->groupBy('supply_id')
            ->map(fn($group) => [
                'id' => $group->first()->supply_id,
                'type' => 'supply',
                'name' => optional($group->first()->supply)->name,
            ]);

        $this->availableItems = $feedItems->values()->toArray();
    }

    public function render()
    {
        $supply = Supply::where('status', 'active')->orderBy('name')->get();
        $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();
        $farms = Farm::whereIn('id', $farmIds)->get(['id', 'name']);
        $units = Unit::all();
        $livestocks = Livestock::where('status', 'active')->get();


        return view('livewire.ovk.create', [
            'farms' => Farm::all(),
            'kandangs' => $this->farm_id ? Kandang::where('farm_id', $this->farm_id)->get() : collect(),
            'supplies' => Supply::all(),
            'units' => Unit::all(),
            'supplyItems' => $supply,
            'livestocks' => Livestock::where('status', 'active')->get(),
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function close()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function checkRequiredFields()
    {
        $this->requiredFieldsFilled = !empty($this->date) &&
            !empty($this->livestock_id);
    }

    public function updatedDate($value)
    {
        $this->validateOnly('date');
        $this->checkRequiredFields();

        $this->resetErrorBag();

        if (!$this->livestock_id || !$this->date) return;

        $usage = SupplyUsage::where('usage_date', $this->date)
            ->where('livestock_id', $this->livestock_id)
            ->first();

        if ($usage) {
            $this->supplyUsageId = $usage->id;

            // Group detail by feed_id dan jumlahkan quantity_taken
            $this->usages = SupplyUsageDetail::where('supply_usage_id', $usage->id)
                ->select('supply_id', DB::raw('SUM(quantity_taken) as quantity'))
                ->groupBy('supply_id')
                ->get()
                ->map(function ($row) {
                    return [
                        'supply_id' => $row->supply_id,
                        'quantity' => $row->quantity,
                    ];
                })->toArray();

            $ovkRecord = OVKRecord::with(['items.supply', 'items.unit'])->where('livestock_id', $this->livestock_id)->whereDate('usage_date', $this->date)->first();

            // dd($ovkRecord);
            $this->showEditForm($ovkRecord->id);
        } else {
            $this->supplyUsageId = null;
            $this->usages = [['supply_id' => '', 'quantity' => '']];
        }

        $this->loadFeeds(); // Tetap refresh ketersediaan stok
    }

    public function updatedLivestockId($value)
    {
        $this->validateOnly('livestock_id');
        $this->checkRequiredFields();
        $this->loadAvailableItems();
    }

    public function loadFeeds()
    {
        if (!$this->livestock_id) return;

        $this->supplies = SupplyStock::where('farm_id', $this->farm_id)
            ->with('supply')
            ->get()
            ->groupBy('supply_id')
            ->map(function ($stocks) {
                $stock = $stocks->first();
                $stock->available_quantity = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
                return $stock;
            })
            ->values()
            ->all();
    }

    public function showEditForm($id)
    {
        $this->recordId = $id;
        $ovkRecord = OVKRecord::with(['items.supply', 'items.unit'])->findOrFail($id);

        // Set basic record data
        $this->date = $ovkRecord->usage_date;
        $this->livestock_id = $ovkRecord->livestock_id;
        $this->notes = $ovkRecord->notes;

        // Reset and populate items
        $this->items = [];
        foreach ($ovkRecord->items as $item) {
            $supply = $item->supply;
            $available_units = [];
            $currentSupply = CurrentSupply::where('item_id', $supply->id)
                ->where('farm_id', $ovkRecord->farm_id)
                ->first();

            if ($supply && isset($supply->payload['conversion_units'])) {
                $available_units = collect($supply->payload['conversion_units'])->map(function ($unit) {
                    $unitModel = Unit::find($unit['unit_id']);
                    return [
                        'unit_id' => (string)$unit['unit_id'],
                        'label' => $unitModel?->name ?? 'Unknown',
                        'value' => $unit['value'],
                        'is_smallest' => $unit['is_smallest'] ?? false,
                    ];
                })->toArray();
            }

            $this->items[] = [
                'supply_id' => $item->supply_id,
                'quantity' => $item->quantity,
                'unit_id' => $item->unit_id,
                'notes' => $item->notes,
                'available_units' => $available_units,
                'available_stock' => $currentSupply?->quantity ?? 0,
                'units' => $available_units
            ];
        }

        // If no items, add one empty item
        if (empty($this->items)) {
            $this->items[] = [
                'supply_id' => '',
                'quantity' => '',
                'unit_id' => '',
                'notes' => '',
                'available_units' => [],
                'available_stock' => 0,
                'units' => []
            ];
        }

        // Load available items for the livestock's farm
        $this->loadAvailableItems();

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
        $this->checkRequiredFields();
    }

    public function deleteOVKRecord($id)
    {
        try {
            DB::beginTransaction();

            // Get the OVK record with its items and related SupplyUsage
            $ovkRecord = OVKRecord::with(['items.supply', 'items.unit'])->findOrFail($id);
            $supplyUsage = SupplyUsage::where('usage_date', $ovkRecord->usage_date)
                ->where('livestock_id', $ovkRecord->livestock_id)
                ->first();

            if (!$supplyUsage) {
                throw new \Exception('Supply usage record not found');
            }

            // Lock CurrentSupply records for the supplies being returned
            $supplyIds = $ovkRecord->items->pluck('supply_id')->unique()->toArray();
            if (!empty($supplyIds)) {
                CurrentSupply::where('farm_id', $ovkRecord->farm_id)
                    ->whereIn('item_id', $supplyIds)
                    ->lockForUpdate()
                    ->get();
            }

            // Process each OVK item to return quantities
            foreach ($ovkRecord->items as $item) {
                $supply = $item->supply;
                $units = collect($supply->payload['conversion_units'] ?? []);
                $itemUnit = $units->firstWhere('unit_id', $item->unit_id);
                $smallestUnit = $units->firstWhere('is_smallest', true);

                if (!$itemUnit || !$smallestUnit || $smallestUnit['value'] == 0) {
                    throw new \Exception("Invalid unit conversion data for supply: {$supply->name}");
                }

                // Convert item quantity to smallest unit
                $quantityInSmallestUnit = ($item->quantity * $itemUnit['value']) / $smallestUnit['value'];

                // Return quantity to CurrentSupply
                $currentSupply = CurrentSupply::where('item_id', $item->supply_id)
                    ->where('farm_id', $ovkRecord->farm_id)
                    ->first();

                if ($currentSupply) {
                    $currentSupply->increment('quantity', $quantityInSmallestUnit);
                }

                // Get and lock SupplyUsageDetail records for this supply
                $usageDetails = $supplyUsage->details()
                    ->where('supply_id', $item->supply_id)
                    ->lockForUpdate()
                    ->get();

                // Get unique supply_stock_ids from the details
                $stockIds = $usageDetails->pluck('supply_stock_id')->unique()->toArray();

                // Lock the affected SupplyStock records
                if (!empty($stockIds)) {
                    $stocks = SupplyStock::whereIn('id', $stockIds)
                        ->lockForUpdate()
                        ->get();

                    // Force delete the usage details
                    foreach ($usageDetails as $detail) {
                        $detail->forceDelete();
                    }

                    // Recalculate quantity_used for each affected stock
                    foreach ($stocks as $stock) {
                        $stock->recalculateQuantityUsed();
                    }
                }
            }

            // Delete the SupplyUsage record
            $supplyUsage->delete();

            // Soft delete the OVK record and its items
            $ovkRecord->items()->delete();
            $ovkRecord->delete();

            DB::commit();

            // Recalculate LivestockCost for the deleted record's date
            app(LivestockCostService::class)->calculateForDate($ovkRecord->livestock_id, $ovkRecord->usage_date);

            $this->dispatch('success', 'Data OVK berhasil dihapus');
            $this->resetForm();
            $this->showForm = false;
            $this->dispatch('show-datatable');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OVK Delete Error: ' . $e->getMessage());
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
}
