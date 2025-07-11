<?php

namespace App\Livewire\MasterData\Supply;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

// Models
use App\Models\Supply;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\CurrentSupply;
use App\Models\SupplyStock;
use App\Models\SupplyPurchase;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Livestock;
use App\Models\Unit;
use App\Services\Supply\SupplyStockService;
use App\Services\SupplyUsageService;
use App\Services\SupplyUsageStockService;
use App\Jobs\UpdateSupplyUsageStockJob;
// Import helper logging functions
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logErrorIfDebug;
use function App\Helpers\logWarningIfDebug;
use App\Helpers\SupplyUsageStatusHelper;

class Usage extends Component
{
    use WithFileUploads;

    // Form properties
    public $farm_id;
    public $coop_id;
    public $livestock_id;
    public $usage_date;
    public $notes;
    public $items = [];

    // Data properties
    public $farms = [];
    public $coops = [];
    public $livestocks = [];
    public $availableSupplies = [];
    public $selectedSupplyStocks = [];

    // UI properties
    public $showForm = false;
    public $edit_mode = false;
    public $usageId;
    public $showDebug = false;
    public $usage; // Tambahan properti publik untuk Blade

    // Validation properties
    public $stockValidationErrors = [];
    public $realTimeValidation = true;

    // DB properties
    public $withHistory = false;

    public $listeners = [
        'showUsageForm' => 'showCreateForm',
        'deleteSupplyUsage' => 'deleteSupplyUsage',
        'editSupplyUsage' => 'showEditForm',
        'cancelSupplyUsage' => 'cancelSupplyUsage',
        'submitForApproval' => 'submitForApproval',
        'approveUsage' => 'approveUsage',
        'completeUsage' => 'completeUsage',
        'updateStatusSupplyUsage' => 'updateStatusSupplyUsage',
        'viewSupplyUsage' => 'viewSupplyUsage',
    ];

    // Validation rules
    protected $rules = [
        'farm_id'      => 'required|exists:farms,id',
        'coop_id'      => 'required|exists:coops,id',
        'livestock_id' => 'nullable|exists:livestocks,id',
        'usage_date'   => 'required|date|before_or_equal:now',
        'notes'        => 'nullable|string|max:500',
        'items'        => 'required|array|min:1',
        'items.*.supply_stock_id'  => 'required|exists:supply_stocks,id',
        'items.*.supply_id'          => 'nullable|exists:supplies,id',
        'items.*.quantity_taken'     => 'required|numeric|min:0.01',
        'items.*.unit_id'            => 'required|exists:units,id',
        'items.*.converted_quantity' => 'required|numeric|min:0.01',
        'items.*.notes'              => 'nullable|string|max:255',
    ];

    protected $messages = [
        'farm_id.required'           => 'Farm wajib dipilih',
        'coop_id.required'           => 'Kandang wajib dipilih',
        'usage_date.required'        => 'Tanggal penggunaan wajib diisi',
        'usage_date.before_or_equal' => 'Tanggal penggunaan tidak boleh melebihi waktu saat ini.',
        'items.required'             => 'Minimal harus ada 1 item supply yang digunakan',
        'items.*.supply_stock_id.required' => 'Supply stock wajib dipilih',
        'items.*.quantity_taken.required'  => 'Quantity wajib diisi',
        'items.*.quantity_taken.min'       => 'Quantity minimal 0.01',
        'items.*.unit_id.required'         => 'Unit wajib dipilih',
        'items.*.converted_quantity.required' => 'Converted quantity wajib diisi',
        'items.*.converted_quantity.min'      => 'Converted quantity minimal 0.01',
    ];

    public function mount()
    {
        $this->usage_date = now()->format('Y-m-d H:i');
        $this->loadFarms();
        $this->initializeItems();
        $this->initializeStockValidation();
        // Set debug visibility
        $this->showDebug = (config('app.env') !== 'production') || (config('app.debug') === true);
    }

    public function render()
    {
        return view('livewire.master-data.supply.usage', [
            'farms' => $this->farms,
            'coops' => $this->coops,
            'livestocks' => $this->livestocks,
            'availableSupplies' => $this->availableSupplies,
            'stockValidationErrors' => $this->stockValidationErrors,
            'hasStockValidationErrors' => $this->getHasStockValidationErrors(),
            'canSave' => $this->getCanSaveProperty(),
            'totalValidationErrors' => $this->getTotalValidationErrorsProperty(),
            'showDebug' => $this->showDebug,
            'usage' => $this->usage, // pastikan dikirim ke Blade
        ]);
    }

    /**
     * Initialize stock validation tracking
     */
    private function initializeStockValidation()
    {
        $this->stockValidationErrors = [];
        foreach ($this->items as $index => $item) {
            $this->stockValidationErrors[$index] = [
                'hasError' => false,
                'message' => '',
                'availableStock' => 0,
                'requestedQuantity' => 0,
                'supplyName' => ''
            ];
        }
    }

    /**
     * Get computed property for stock validation errors
     */
    public function getHasStockValidationErrors()
    {
        return collect($this->stockValidationErrors)->contains('hasError', true);
    }

    /**
     * Get computed property for total validation errors count
     */
    public function getTotalValidationErrorsProperty()
    {
        return collect($this->stockValidationErrors)->where('hasError', true)->count();
    }

    /**
     * Get computed property for can save state
     */
    public function getCanSaveProperty()
    {
        // Basic validation first - farm and coop are required
        if (!$this->farm_id || !$this->coop_id || empty($this->items)) {
            return false;
        }

        // Check if any stock validation errors exist
        if ($this->getHasStockValidationErrors()) {
            return false;
        }

        // Check if all required fields are filled for at least one item
        $hasValidItem = collect($this->items)->contains(function ($item) {
            return !empty($item['supply_stock_id']) &&
                !empty($item['quantity_taken']) &&
                floatval($item['quantity_taken']) > 0 &&
                !empty($item['unit_id']);
        });

        return $hasValidItem;
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->edit_mode = false;
        $this->dispatch('hide-datatable');
    }

    public function showEditForm($id)
    {
        $usage = SupplyUsage::with(['details.supply', 'details.unit', 'details.supplyStock'])
            ->findOrFail($id);
        $this->usage = $usage;

        $this->usageId = $usage->id;
        $this->farm_id = $usage->farm_id;
        $this->coop_id = $usage->coop_id;
        $this->livestock_id = $usage->livestock_id;
        $this->usage_date = $usage->usage_date->format('Y-m-d H:i');
        $this->notes = $usage->notes;

        // Load coops and livestocks for selected farm
        $this->loadCoops();
        $this->loadLivestocks();

        // Load items from usage details
        $this->items = $usage->details->map(function ($detail) {
            return [
                'supply_stock_id' => $detail->supply_stock_id,
                'supply_id' => $detail->supply_id,
                'quantity_taken' => $detail->quantity_taken,
                'unit_id' => $detail->unit_id,
                'converted_quantity' => $detail->converted_quantity,
                'price_per_unit' => $detail->price_per_unit,
                'price_per_converted_unit' => $detail->price_per_converted_unit,
                'total_price' => $detail->total_price,
                'notes' => $detail->notes,
                'batch_number' => $detail->batch_number,
                'expiry_date' => $detail->expiry_date ? $detail->expiry_date->format('Y-m-d') : '',
                'available_stock' => $this->getAvailableStockFromSupplyStock($detail->supply_stock_id),
                'available_units' => $this->getSupplyUnits($detail->supply_id),
            ];
        })->toArray();

        $this->loadAvailableSupplies();
        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function viewSupplyUsage($id)
    {
        $usage = SupplyUsage::with(['details.supply', 'details.unit', 'details.supplyStock'])
            ->findOrFail($id);
        $this->usage = $usage;

        $this->usageId = $usage->id;
        $this->farm_id = $usage->farm_id;
        $this->coop_id = $usage->coop_id;
        $this->livestock_id = $usage->livestock_id;
        $this->usage_date = $usage->usage_date->format('Y-m-d H:i');
        $this->notes = $usage->notes;

        // Load coops and livestocks for selected farm
        $this->loadCoops();
        $this->loadLivestocks();

        // Load items from usage details
        $this->items = $usage->details->map(function ($detail) {
            return [
                'supply_stock_id' => $detail->supply_stock_id,
                'supply_id' => $detail->supply_id,
                'quantity_taken' => $detail->quantity_taken,
                'unit_id' => $detail->unit_id,
                'converted_quantity' => $detail->converted_quantity,
                'price_per_unit' => $detail->price_per_unit,
                'price_per_converted_unit' => $detail->price_per_converted_unit,
                'total_price' => $detail->total_price,
                'notes' => $detail->notes,
                'batch_number' => $detail->batch_number,
                'expiry_date' => $detail->expiry_date ? $detail->expiry_date->format('Y-m-d') : '',
                'available_stock' => $this->getAvailableStockFromSupplyStock($detail->supply_stock_id),
                'available_units' => $this->getSupplyUnits($detail->supply_id),
            ];
        })->toArray();

        $this->loadAvailableSupplies();
        $this->showForm = true;
        $this->edit_mode = false;
        $this->dispatch('hide-datatable');
    }

    public function close()
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('show-datatable');
    }

    public function resetForm()
    {
        $this->reset([
            'farm_id',
            'coop_id',
            'livestock_id',
            'usage_date',
            'notes',
            'usageId',
            'edit_mode'
        ]);
        $this->usage_date = now()->format('Y-m-d H:i');
        $this->initializeItems();
        $this->coops = [];
        $this->livestocks = [];
        $this->availableSupplies = [];
    }

    public function updatedFarmId($value)
    {
        $this->coop_id = null;
        $this->livestock_id = null;
        $this->livestocks = [];
        $this->availableSupplies = [];
        $this->resetItems();

        if ($value) {
            $this->loadCoops();
            $this->loadAvailableSupplies();
        } else {
            $this->coops = [];
        }
    }

    public function updatedCoopId($value)
    {
        $this->livestock_id = null;

        if ($value && $this->farm_id) {
            $this->loadLivestocks();
        } else {
            $this->livestocks = [];
        }
    }



    public function updatedUsageDate($value)
    {
        if ($this->farm_id) {
            $this->loadAvailableSupplies();
            $this->updateItemsAvailableStock();
        }
    }

    public function updatedLivestockId($value)
    {
        $this->availableSupplies = [];
        $this->resetItems();
        // Tidak perlu loadAvailableSupplies di sini
    }

    private function loadFarms()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $isSuperAdmin = $user->hasRole('SuperAdmin');
        $isCompanyRole = $user->hasAnyRole(['Supervisor', 'Manager', 'Administrator']);
        $isOperator = $user->hasRole('Operator');

        // Logging for debugging role-based data filtering
        logDebugIfDebug('Supply Usage loadFarms() called', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames(),
            'company_id' => $companyId,
            'isSuperAdmin' => $isSuperAdmin,
            'isCompanyRole' => $isCompanyRole,
            'isOperator' => $isOperator,
        ]);

        // Farms
        if ($isSuperAdmin) {
            $this->farms = Farm::where('status', 'active')->orderBy('name')->get();
        } elseif ($isCompanyRole) {
            $this->farms = Farm::where('status', 'active')
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get();
        } elseif ($isOperator) {
            // Operator hanya bisa melihat farm yang dioperasikan oleh user ini
            $this->farms = Farm::where('status', 'active')
                ->whereHas('farmOperators', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('name')
                ->get();
        } else {
            $this->farms = collect();
        }
    }

    private function loadCoops()
    {
        if (!$this->farm_id) {
            $this->coops = collect();
            return;
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        $isSuperAdmin = $user->hasRole('SuperAdmin');
        $isCompanyRole = $user->hasAnyRole(['Supervisor', 'Manager', 'Administrator']);
        $isOperator = $user->hasRole('Operator');

        // Coops
        if ($isSuperAdmin) {
            $this->coops = Coop::where('farm_id', $this->farm_id)
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get();
        } elseif ($isCompanyRole) {
            $this->coops = Coop::where('farm_id', $this->farm_id)
                ->where('status', '!=', 'inactive')
                ->whereHas('farm', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->orderBy('name')
                ->get();
        } elseif ($isOperator) {
            $this->coops = Coop::where('farm_id', $this->farm_id)
                ->where('status', '!=', 'inactive')
                ->whereHas('farm.farmOperators', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('name')
                ->get();
        } else {
            $this->coops = collect();
        }
    }

    private function loadLivestocks()
    {
        if (!$this->farm_id || !$this->coop_id) {
            $this->livestocks = collect();
            return;
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        $isSuperAdmin = $user->hasRole('SuperAdmin');
        $isCompanyRole = $user->hasAnyRole(['Supervisor', 'Manager', 'Administrator']);
        $isOperator = $user->hasRole('Operator');

        // Livestocks
        if ($isSuperAdmin) {
            $this->livestocks = Livestock::where('farm_id', $this->farm_id)
                ->where('coop_id', $this->coop_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        } elseif ($isCompanyRole) {
            $this->livestocks = Livestock::where('farm_id', $this->farm_id)
                ->where('coop_id', $this->coop_id)
                ->where('status', 'active')
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get();
        } elseif ($isOperator) {
            $this->livestocks = Livestock::where('farm_id', $this->farm_id)
                ->where('coop_id', $this->coop_id)
                ->where('status', 'active')
                ->whereHas('farm.farmOperators', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('name')
                ->get();
        } else {
            $this->livestocks = collect();
        }
    }

    private function loadAvailableSupplies()
    {
        if (!$this->farm_id) {
            $this->availableSupplies = [];
            return;
        }
        $user = Auth::user();
        $supplyStockService = new SupplyStockService();
        $suppliesWithStock = $supplyStockService->getAvailableSupplyStocks($this->farm_id, $user, $this->usage_date);
        $this->availableSupplies = $suppliesWithStock->map(function ($supplyStock) {
            $availableQty = $supplyStock->quantity_in - $supplyStock->quantity_used - $supplyStock->quantity_mutated;
            return [
                'id' => $supplyStock->id,
                'supply_id' => $supplyStock->supply_id,
                'supply_name' => $supplyStock->supply->name ?? '',
                'category' => $supplyStock->supply->supplyCategory->name ?? 'General',
                'available_stock' => $availableQty,
                'unit' => $supplyStock->supply->smallest_unit_name ?? 'pcs',
                'units' => $this->getSupplyUnits($supplyStock->supply_id),
                'batch_number' => $supplyStock->batch_number ?? '',
                'expiry_date' => $supplyStock->expiry_date ?? '',
            ];
        })->toArray();

        logDebugIfDebug('Usage@loadAvailableSupplies', [
            'farm_id' => $this->farm_id,
            'loaded_count' => count($this->availableSupplies),
            'supply_ids' => array_column($this->availableSupplies, 'id'),
        ]);
    }

    private function getAvailableStockFromSupplyStock($supplyStockId)
    {
        if (!$supplyStockId) {
            return 0;
        }

        $supplyStock = SupplyStock::find($supplyStockId);

        if (!$supplyStock) {
            return 0;
        }

        return $supplyStock->quantity_in - $supplyStock->quantity_used - $supplyStock->quantity_mutated;
    }

    private function getSupplyUnits($supplyId)
    {
        $supply = Supply::find($supplyId);

        if (!$supply || !isset($supply->data['conversion_units'])) {
            return Unit::orderBy('name')->get()->toArray();
        }

        $unitIds = collect($supply->data['conversion_units'])
            ->pluck('unit_id')
            ->filter()
            ->toArray();

        return Unit::whereIn('id', $unitIds)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function addItem()
    {
        $newIndex = count($this->items);
        $this->items[] = [
            'supply_stock_id' => '',
            'supply_id' => '',
            'quantity_taken' => '',
            'unit_id' => '',
            'converted_quantity' => '',
            'price_per_unit' => '',
            'price_per_converted_unit' => '',
            'total_price' => '',
            'notes' => '',
            'batch_number' => '',
            'expiry_date' => '',
            'available_stock' => 0,
            'available_units' => [],
        ];

        // Initialize validation for new item
        $this->initializeItemValidation($newIndex);
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            unset($this->stockValidationErrors[$index]);

            // Re-index arrays
            $this->items = array_values($this->items);
            $this->stockValidationErrors = array_values($this->stockValidationErrors);
        }
    }

    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'supply_stock_id' && $value) {
            $supplyStock = SupplyStock::find($value);
            if ($supplyStock) {
                $this->items[$index]['supply_id'] = $supplyStock->supply_id;
                $this->items[$index]['available_stock'] = $this->getAvailableStockFromSupplyStock($value);
                $this->items[$index]['available_units'] = $this->getSupplyUnits($supplyStock->supply_id);
                $this->items[$index]['unit_id'] = ''; // Reset unit selection
                $this->items[$index]['batch_number'] = $supplyStock->batch_number ?? '';
                $this->items[$index]['expiry_date'] = $supplyStock->expiry_date ? $supplyStock->expiry_date->format('Y-m-d') : '';

                // Initialize validation for this item
                $this->initializeItemValidation($index);

                // Re-validate if quantity exists
                if (!empty($this->items[$index]['quantity_taken'])) {
                    // Always recalculate converted_quantity
                    $this->items[$index]['converted_quantity'] = $this->convertToSmallestUnit($this->items[$index]);
                    $this->validateItemStock($index);
                }
            }
        }

        if ($field === 'quantity_taken') {
            // Always recalculate converted_quantity
            $this->items[$index]['converted_quantity'] = $this->convertToSmallestUnit($this->items[$index]);
            $this->validateItemStock($index);
            // Dispatch real-time feedback
            if ($this->stockValidationErrors[$index]['hasError']) {
                $this->dispatch('stockValidationError', [
                    'index' => $index,
                    'message' => $this->stockValidationErrors[$index]['message']
                ]);
            } else {
                $this->dispatch('stockValidationSuccess', [
                    'index' => $index
                ]);
            }
        }

        if ($field === 'unit_id' && $value) {
            // Always recalculate converted_quantity
            $this->items[$index]['converted_quantity'] = $this->convertToSmallestUnit($this->items[$index]);
            // Jika quantity_taken sudah diisi, langsung validasi
            if (!empty($this->items[$index]['quantity_taken'])) {
                $this->validateItemStock($index);
            } else {
                // Jika user baru pilih unit, auto-focus ke input quantity
                $this->dispatch('focusQuantityInput', ['index' => $index]);
            }
        }
    }

    /**
     * Initialize validation tracking for specific item
     */
    private function initializeItemValidation($index)
    {
        $supplyStock = SupplyStock::find($this->items[$index]['supply_stock_id'] ?? null);
        $supply = Supply::find($this->items[$index]['supply_id'] ?? null);

        $this->stockValidationErrors[$index] = [
            'hasError' => false,
            'message' => '',
            'availableStock' => $this->items[$index]['available_stock'] ?? 0,
            'requestedQuantity' => 0,
            'supplyName' => $supply->name ?? 'Unknown Supply'
        ];
    }

    /**
     * Validate stock for specific item with enhanced messaging
     */
    private function validateItemStock($index)
    {
        if (!isset($this->items[$index]) || !$this->realTimeValidation) {
            return;
        }

        $item = $this->items[$index];
        $availableStock = floatval($item['available_stock'] ?? 0);
        $requestedQuantity = floatval($item['converted_quantity'] ?? 0); // GANTI: gunakan converted_quantity
        $supply = Supply::find($item['supply_id'] ?? null);
        $supplyName = $supply->name ?? 'Unknown Supply';

        // Initialize validation state if not exists
        if (!isset($this->stockValidationErrors[$index])) {
            $this->initializeItemValidation($index);
        }

        $this->stockValidationErrors[$index]['availableStock'] = $availableStock;
        $this->stockValidationErrors[$index]['requestedQuantity'] = $requestedQuantity;
        $this->stockValidationErrors[$index]['supplyName'] = $supplyName;

        // Tambahan: validasi jika unit belum dipilih
        if (empty($item['unit_id'])) {
            $this->stockValidationErrors[$index]['hasError'] = true;
            $this->stockValidationErrors[$index]['message'] = 'Pilih unit terlebih dahulu';
            logWarningIfDebug('Supply Usage: Stock validation error - unit not selected', [
                'item_index' => $index,
                'supply_name' => $supplyName
            ]);
            return;
        }

        if ($requestedQuantity > $availableStock && $requestedQuantity > 0) {
            $shortage = $requestedQuantity - $availableStock;
            $this->stockValidationErrors[$index]['hasError'] = true;
            $this->stockValidationErrors[$index]['message'] = "Quantity melebihi stock! Tersedia: {$availableStock}, Diminta: {$requestedQuantity}, Kekurangan: {$shortage}";

            logWarningIfDebug('Supply Usage: Stock validation error', [
                'item_index' => $index,
                'supply_name' => $supplyName,
                'available_stock' => $availableStock,
                'requested_quantity' => $requestedQuantity,
                'shortage' => $shortage
            ]);
        } else {
            $this->stockValidationErrors[$index]['hasError'] = false;
            $this->stockValidationErrors[$index]['message'] = '';
        }
    }

    /**
     * Validate all items stock availability
     */
    private function validateAllItemsStock()
    {
        foreach ($this->items as $index => $item) {
            $this->validateItemStock($index);
        }
    }

    /**
     * Enhanced stock availability validation for save process
     */
    private function validateStockAvailability()
    {
        $validationErrors = [];
        $isEdit = $this->edit_mode;
        $oldDetails = [];
        if ($isEdit && $this->usageId) {
            // Ambil data lama dari DB untuk perbandingan
            $oldDetails = \App\Models\SupplyUsageDetail::where('supply_usage_id', $this->usageId)
                ->get()
                ->keyBy(function ($d) {
                    return $d->supply_stock_id . '-' . $d->supply_id . '-' . $d->unit_id;
                });
        }

        foreach ($this->items as $index => $item) {
            $availableStock = $this->getAvailableStockFromSupplyStock($item['supply_stock_id']);
            $requestedQuantity = floatval($item['converted_quantity']);
            $supply = Supply::find($item['supply_id']);
            $supplyName = $supply->name ?? 'Unknown Supply';

            $oldQty = 0;
            if ($isEdit && $oldDetails) {
                $key = $item['supply_stock_id'] . '-' . $item['supply_id'] . '-' . $item['unit_id'];
                $oldQty = isset($oldDetails[$key]) ? floatval($oldDetails[$key]->converted_quantity) : 0;
            }
            $delta = $requestedQuantity - $oldQty;

            // Log untuk debugging
            logDebugIfDebug('Stock validation (edit mode)', [
                'item_index' => $index,
                'supply_name' => $supplyName,
                'supply_stock_id' => $item['supply_stock_id'],
                'available_stock' => $availableStock,
                'requested_quantity' => $requestedQuantity,
                'old_quantity' => $oldQty,
                'delta' => $delta,
                'is_edit' => $isEdit
            ]);

            if ($isEdit) {
                if ($delta > 0 && $delta > $availableStock) {
                    $shortage = $delta - $availableStock;
                    $validationErrors["items.{$index}.converted_quantity"] =
                        "Penambahan quantity untuk {$supplyName} melebihi stock! Sisa: " . number_format($availableStock, 2) . ", Tambahan: " . number_format($delta, 2) . ", Kekurangan: " . number_format($shortage, 2);
                    logWarningIfDebug('Supply Usage Save: Stock validation failed (edit mode)', [
                        'item_index' => $index,
                        'supply_name' => $supplyName,
                        'supply_stock_id' => $item['supply_stock_id'],
                        'available_stock' => $availableStock,
                        'requested_quantity' => $requestedQuantity,
                        'old_quantity' => $oldQty,
                        'delta' => $delta,
                        'shortage' => $shortage
                    ]);
                }
                // Jika delta <= 0, validasi lolos (tidak perlu error)
            } else {
                if ($requestedQuantity > $availableStock) {
                    $shortage = $requestedQuantity - $availableStock;
                    $validationErrors["items.{$index}.converted_quantity"] =
                        "Quantity untuk {$supplyName} melebihi stock yang tersedia! Stock tersedia: " . number_format($availableStock, 2) . ", Diminta: " . number_format($requestedQuantity, 2) . ", Kekurangan: " . number_format($shortage, 2);
                    logWarningIfDebug('Supply Usage Save: Stock validation failed', [
                        'item_index' => $index,
                        'supply_name' => $supplyName,
                        'supply_stock_id' => $item['supply_stock_id'],
                        'available_stock' => $availableStock,
                        'requested_quantity' => $requestedQuantity,
                        'shortage' => $shortage
                    ]);
                }
            }
        }

        if (!empty($validationErrors)) {
            throw ValidationException::withMessages($validationErrors);
        }
    }

    private function initializeItems()
    {
        $this->items = [
            [
                'supply_stock_id' => '',
                'supply_id' => '',
                'quantity_taken' => '',
                'unit_id' => '',
                'converted_quantity' => '',
                'price_per_unit' => '',
                'price_per_converted_unit' => '',
                'total_price' => '',
                'notes' => '',
                'batch_number' => '',
                'expiry_date' => '',
                'available_stock' => 0,
                'available_units' => [],
            ]
        ];
        $this->initializeStockValidation();
    }

    private function resetItems()
    {
        foreach ($this->items as &$item) {
            $item['available_stock'] = 0;
            $item['available_units'] = [];
        }
        $this->initializeStockValidation();
    }

    private function updateItemsAvailableStock()
    {
        foreach ($this->items as $index => &$item) {
            if ($item['supply_stock_id']) {
                $item['available_stock'] = $this->getAvailableStockFromSupplyStock($item['supply_stock_id']);
                // Re-validate after stock update
                $this->validateItemStock($index);
            }
        }
    }

    /**
     * Force validate all stocks (public method for UI usage)
     */
    public function validateAllStocks()
    {
        $this->validateAllItemsStock();

        if ($this->getHasStockValidationErrors()) {
            $errorCount = $this->totalValidationErrors;
            $this->dispatch('stockValidationWarning', "Ditemukan {$errorCount} item dengan masalah stock!");
        } else {
            $this->dispatch('stockValidationSuccess', 'Semua stock valid!');
        }
    }

    /**
     * Convert quantity to smallest unit based on supply conversion units
     */
    private function convertToSmallestUnit($item)
    {
        $supply = Supply::find($item['supply_id']);
        if (!$supply || !isset($supply->data['conversion_units'])) {
            logDebugIfDebug('Supply Usage: No conversion units found, returning 0', [
                'supply_id' => $item['supply_id'],
                'quantity_taken' => $item['quantity_taken']
            ]);
            return 0;
        }
        if (empty($item['unit_id'])) {
            logDebugIfDebug('Supply Usage: Unit not selected, returning 0', [
                'supply_id' => $item['supply_id'],
                'quantity_taken' => $item['quantity_taken']
            ]);
            return 0;
        }
        $units = collect($supply->data['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
        $smallestUnit = $units->firstWhere('is_smallest', true);
        if (!$selectedUnit || !$smallestUnit || $smallestUnit['value'] == 0) {
            logDebugIfDebug('Supply Usage: Invalid conversion units, returning 0', [
                'supply_id' => $item['supply_id'],
                'unit_id' => $item['unit_id'],
                'selected_unit' => $selectedUnit,
                'smallest_unit' => $smallestUnit,
                'quantity_taken' => $item['quantity_taken']
            ]);
            return 0;
        }
        $convertedQuantity = ($item['quantity_taken'] * $selectedUnit['value']) / $smallestUnit['value'];
        logDebugIfDebug('Supply Usage: Quantity converted to smallest unit', [
            'supply_id' => $item['supply_id'],
            'unit_id' => $item['unit_id'],
            'original_quantity' => $item['quantity_taken'],
            'selected_unit_value' => $selectedUnit['value'],
            'smallest_unit_value' => $smallestUnit['value'],
            'converted_quantity' => $convertedQuantity
        ]);
        return $convertedQuantity;
    }

    /**
     * Clear all validation errors (useful for debugging)
     */
    public function clearValidationErrors()
    {
        $this->initializeStockValidation();
        $this->dispatch('stockValidationCleared', 'Validation errors cleared.');
    }

    /**
     * Toggle real-time validation
     */
    public function toggleRealTimeValidation()
    {
        $this->realTimeValidation = !$this->realTimeValidation;
        $status = $this->realTimeValidation ? 'enabled' : 'disabled';
        $this->dispatch('info', "Real-time validation {$status}");
    }

    /**
     * Debug method to check save conditions
     */
    public function debugSaveConditions()
    {
        $debugInfo = [
            'farm_id' => $this->farm_id,
            'coop_id' => $this->coop_id,
            'items_count' => count($this->items),
            'items' => $this->items,
            'has_stock_errors' => $this->getHasStockValidationErrors(),
            'stock_errors' => $this->stockValidationErrors,
            'can_save' => $this->getCanSaveProperty(),
        ];

        logDebugIfDebug('Supply Usage Debug Save Conditions', $debugInfo);
        $this->dispatch('info', 'Debug info logged. Check log files for details.');

        return $debugInfo;
    }

    public function save()
    {
        logDebugIfDebug('Usage@save: Method triggered.');

        try {
            $this->validate();
            logDebugIfDebug('Usage@save: Basic validation passed.');
            $this->validateStockAvailability();
            logDebugIfDebug('Usage@save: Stock availability validation passed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            logErrorIfDebug('Usage@save: Validation failed.', [
                'errors' => $e->validator->errors()->toArray()
            ]);
            $errorMessages = collect($e->validator->errors()->all())->implode(' ');
            $this->dispatch('error', 'Validasi gagal: ' . $errorMessages);
            throw $e;
        }

        $service = resolve(SupplyUsageService::class);
        $isEdit = $this->edit_mode;
        $result = null;
        DB::beginTransaction();
        logDebugIfDebug('Usage@save: DB transaction started.');
        try {
            if ($isEdit) {
                logDebugIfDebug('Usage@save: Entering edit mode.');
                $result = $this->updateUsageWithService($service);
            } else {
                logDebugIfDebug('Usage@save: Entering create mode.');
                $result = $this->createUsageWithService($service);
            }
            DB::commit();
            logDebugIfDebug('Usage@save: DB transaction committed.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            logErrorIfDebug('Usage@save: DB transaction rolled back due to error.', [
                'message' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Gagal menyimpan data: ' . $errorMessage);
            return;
        }

        $this->dispatch('success', 'Supply usage berhasil disimpan.');
        $this->close();
        logDebugIfDebug('Usage@save: Process finished successfully.');
    }

    private function buildSupplyUsagePayload()
    {
        // Map items to service format
        $items = collect($this->items)->map(function ($item) {
            // Debug log for mismatch
            if (isset($item['quantity_taken'], $item['converted_quantity']) && $item['quantity_taken'] > 0 && $item['converted_quantity'] <= 0) {
                logWarningIfDebug('Mismatch: quantity_taken > 0 but converted_quantity <= 0', $item);
            }
            return [
                'supply_id' => $item['supply_id'],
                'supply_name' => $item['supply_name'] ?? null,
                'quantity_taken' => $item['quantity_taken'], // asli
                'converted_quantity' => $item['converted_quantity'], // hasil konversi
                'unit_id' => $item['unit_id'],
                'unit_name' => $item['unit_name'] ?? null,
                'original_unit_id' => $item['unit_id'],
                'original_unit_name' => $item['unit_name'] ?? null,
                'conversion_factor' => 1, // TODO: support multi-unit conversion if needed
                'notes' => $item['notes'] ?? null,
            ];
        })->toArray();
        return $items;
    }

    private function syncCurrentSupply($farmId, $livestockId, $supplyId)
    {
        $total = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->sum(DB::raw('quantity_in - quantity_used - quantity_mutated'));

        \App\Models\CurrentSupply::updateOrCreate(
            [
                'farm_id' => $farmId,
                'livestock_id' => $livestockId,
                'item_id' => $supplyId,
                'type' => 'Supply'
            ],
            [
                'quantity' => $total,
                'updated_by' => Auth::id(),
            ]
        );
    }

    private function createUsageWithService($service)
    {
        $companyId = Auth::user()->company_id;
        $usage = SupplyUsage::create([
            'company_id'   => $companyId,
            'farm_id'      => $this->farm_id,
            'coop_id'      => $this->coop_id,
            'livestock_id' => $this->livestock_id,
            'total_quantity' => 0,
            'usage_date'   => $this->usage_date,
            'notes'        => $this->notes,
            'status'       => SupplyUsage::STATUS_DRAFT,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);
        logDebugIfDebug('Usage@createUsageWithService: Usage record created.', ['usage_id' => $usage->id]);

        // Create usage details without affecting stock (draft status)
        $this->createUsageDetails($usage);

        // Update total quantity from details
        $totalQuantity = collect($this->items)->sum('converted_quantity');
        $usage->update(['total_quantity' => $totalQuantity]);

        logDebugIfDebug('Usage@createUsageWithService: Usage details created in draft mode.', [
            'usage_id' => $usage->id,
            'total_quantity' => $totalQuantity,
            'details_count' => count($this->items)
        ]);

        // DO NOT sync CurrentSupply or recalculate SupplyStock for draft status
        // These will be handled when status changes to appropriate state (pending, in_process, completed)

        return [
            'success' => true,
            'usage_id' => $usage->id,
            'status' => $usage->status,
            'message' => 'Supply usage draft created successfully. Submit for approval to process stock.',
            'mode' => 'draft_creation'
        ];
    }

    private function updateUsageWithService($service)
    {
        $usage = SupplyUsage::findOrFail($this->usageId);
        logDebugIfDebug('Usage@updateUsageWithService: Usage record loaded.', ['usage_id' => $usage->id]);

        // Check if status allows stock modifications
        $canModifyStock = in_array($usage->status, [
            SupplyUsage::STATUS_PENDING,
            SupplyUsage::STATUS_IN_PROCESS,
            SupplyUsage::STATUS_COMPLETED
        ]);

        if ($canModifyStock) {
            // Restore previous stock usage (rollback) only if status allows stock modifications
            $this->restorePreviousStockUsage($usage);
            logDebugIfDebug('Usage@updateUsageWithService: Previous stock usage restored.', ['usage_id' => $usage->id]);
        }

        // Update header
        $usage->update([
            'farm_id'      => $this->farm_id,
            'coop_id'      => $this->coop_id,
            'livestock_id' => $this->livestock_id,
            'usage_date'   => $this->usage_date,
            'notes'        => $this->notes,
            'updated_by'   => Auth::id(),
        ]);
        logDebugIfDebug('Usage@updateUsageWithService: Usage record updated.', ['usage_id' => $usage->id]);

        if ($this->withHistory) {
            // Delete old details (history mode)
            $usage->details()->delete();
            logDebugIfDebug('Usage@updateUsageWithService: Old details deleted.', ['usage_id' => $usage->id]);

            // Create new details
            $this->createUsageDetails($usage);

            // Update total quantity
            $totalQuantity = collect($this->items)->sum('converted_quantity');
            $usage->update(['total_quantity' => $totalQuantity]);

            $result = [
                'success' => true,
                'mode' => 'history_update',
                'total_quantity' => $totalQuantity
            ];
        } else {
            // In-place update mode (no delete, no create new usage)
            $details = $usage->details()->get()->keyBy(function ($d) {
                return $d->supply_stock_id . '-' . $d->supply_id . '-' . $d->unit_id;
            });

            foreach ($this->items as $item) {
                $key = $item['supply_stock_id'] . '-' . $item['supply_id'] . '-' . $item['unit_id'];
                $detail = $details[$key] ?? null;

                if ($detail) {
                    // Update existing detail
                    $detail->update([
                        'quantity_taken' => $item['quantity_taken'],
                        'unit_id' => $item['unit_id'],
                        'converted_quantity' => $item['converted_quantity'],
                        'converted_unit_id' => $item['converted_unit_id'] ?? $item['unit_id'],
                        'price_per_unit' => $item['price_per_unit'] ?? null,
                        'price_per_converted_unit' => $item['price_per_converted_unit'] ?? null,
                        'total_price' => $item['total_price'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => !empty($item['expiry_date']) ? Carbon::parse($item['expiry_date']) : null,
                        'updated_by' => Auth::id(),
                    ]);
                    logDebugIfDebug('Usage@updateUsageWithService: Updated detail.', ['detail_id' => $detail->id]);
                } else {
                    // Create new detail if not exists
                    $usage->details()->create([
                        'supply_stock_id' => $item['supply_stock_id'],
                        'supply_id' => $item['supply_id'],
                        'quantity_taken' => $item['quantity_taken'],
                        'unit_id' => $item['unit_id'],
                        'converted_quantity' => $item['converted_quantity'],
                        'converted_unit_id' => $item['converted_unit_id'] ?? $item['unit_id'],
                        'price_per_unit' => $item['price_per_unit'] ?? null,
                        'price_per_converted_unit' => $item['price_per_converted_unit'] ?? null,
                        'total_price' => $item['total_price'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => !empty($item['expiry_date']) ? Carbon::parse($item['expiry_date']) : null,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    logDebugIfDebug('Usage@updateUsageWithService: Created new detail.', ['supply_stock_id' => $item['supply_stock_id']]);
                }
            }

            // Update total_quantity
            $totalQuantity = collect($this->items)->sum('converted_quantity');
            $usage->update(['total_quantity' => $totalQuantity]);

            $result = [
                'success' => true,
                'mode' => 'in-place-update',
                'total_quantity' => $totalQuantity
            ];
        }

        // Only sync CurrentSupply and recalculate SupplyStock if status allows stock modifications
        if ($canModifyStock) {
            foreach ($this->items as $item) {
                if (!empty($item['supply_id'])) {
                    $this->syncCurrentSupply($this->farm_id, $this->livestock_id, $item['supply_id']);
                }
                // Recalculate quantity_used pada stock terkait
                $stock = \App\Models\SupplyStock::find($item['supply_stock_id']);
                if ($stock) {
                    $stock->recalculateQuantityUsed();
                }
            }
            logDebugIfDebug('Usage@updateUsageWithService: Stock calculations completed.', ['usage_id' => $usage->id]);
        } else {
            logDebugIfDebug('Usage@updateUsageWithService: Stock calculations skipped due to status.', [
                'usage_id' => $usage->id,
                'status' => $usage->status
            ]);
        }

        return $result;
    }

    private function createUsageDetails($usage)
    {
        logDebugIfDebug('Usage@createUsageDetails: Creating details.', ['usage_id' => $usage->id, 'item_count' => count($this->items)]);
        foreach ($this->items as $index => $item) {
            // Ambil converted_unit_id dari unit terkecil pada model Supply
            $supply = \App\Models\Supply::find($item['supply_id']);
            $converted_unit_id = null;
            if ($supply && $supply->data) {
                $data = is_array($supply->data) ? $supply->data : json_decode($supply->data, true);
                if (isset($data['conversion_units']) && is_array($data['conversion_units'])) {
                    foreach ($data['conversion_units'] as $unit) {
                        if (isset($unit['is_smallest']) && $unit['is_smallest']) {
                            $converted_unit_id = $unit['unit_id'];
                            break;
                        }
                    }
                }
            }

            SupplyUsageDetail::create([
                'supply_usage_id' => $usage->id,
                'supply_stock_id' => $item['supply_stock_id'],
                'supply_id'       => $item['supply_id'],
                'quantity_taken'  => $item['quantity_taken'],
                'unit_id'         => $item['unit_id'],
                'converted_quantity' => $item['converted_quantity'],
                'converted_unit_id'  => $converted_unit_id,
                'price_per_unit'       => $item['price_per_unit'] ?: null,
                'price_per_converted_unit' => $item['price_per_converted_unit'] ?: null,
                'total_price'              => $item['total_price'] ?: null,
                'notes'                    => $item['notes'],
                'batch_number'             => $item['batch_number'],
                'expiry_date'              => $item['expiry_date'] ? Carbon::parse($item['expiry_date']) : null,
                'created_by'               => Auth::id(),
                'updated_by'               => Auth::id(),
            ]);
            logDebugIfDebug('Usage@createUsageDetails: Created detail item.', ['index' => $index, 'supply_stock_id' => $item['supply_stock_id'], 'converted_unit_id' => $converted_unit_id]);
        }
        logDebugIfDebug('Usage@createUsageDetails: All details created.');
    }

    private function updateSupplyStocks($usage)
    {
        logDebugIfDebug('Usage@updateSupplyStocks: Updating stock records.', ['usage_id' => $usage->id]);
        foreach ($usage->details as $detail) {
            $this->updateSupplyStock($detail);
            $this->updateCurrentSupply($detail);
        }
        logDebugIfDebug('Usage@updateSupplyStocks: Stock records updated.');
    }

    private function updateSupplyStock($detail)
    {
        $supplyStock = SupplyStock::find($detail->supply_stock_id);

        if ($supplyStock) {
            $supplyStock->increment('quantity_used', $detail->converted_quantity);
        }
    }

    private function updateCurrentSupply($detail)
    {
        $currentSupply = CurrentSupply::where('farm_id', $detail->supplyUsage->farm_id)
            ->where('item_id', $detail->supply_id)
            ->where('type', 'Supply')
            ->first();

        if ($currentSupply) {
            $currentSupply->decrement('quantity', $detail->converted_quantity);
        }
    }

    private function restorePreviousStockUsage($usage)
    {
        logDebugIfDebug('Usage@restorePreviousStockUsage: Restoring stock.', ['usage_id' => $usage->id, 'detail_count' => $usage->details->count()]);
        foreach ($usage->details as $detail) {
            // Restore SupplyStock
            $supplyStock = SupplyStock::find($detail->supply_stock_id);
            if ($supplyStock) {
                $supplyStock->decrement('quantity_used', $detail->converted_quantity); // Use converted quantity for accuracy
                logDebugIfDebug('Usage@restorePreviousStockUsage: Decremented supply_stock.', ['supply_stock_id' => $supplyStock->id, 'quantity' => $detail->converted_quantity]);
            }

            // Restore CurrentSupply
            $currentSupply = CurrentSupply::where('farm_id', $usage->farm_id)
                ->where('item_id', $detail->supply_id)
                ->where('type', 'Supply')
                ->first();

            if ($currentSupply) {
                $currentSupply->increment('quantity', $detail->converted_quantity);
                logDebugIfDebug('Usage@restorePreviousStockUsage: Incremented current_supply.', ['current_supply_id' => $currentSupply->id, 'quantity' => $detail->converted_quantity]);
            }
        }
        logDebugIfDebug('Usage@restorePreviousStockUsage: Stock restored.');
    }

    /**
     * Submit usage for approval (new method)
     */
    public function submitForApproval()
    {
        if (!$this->usageId) {
            $this->dispatch('error', 'Usage ID tidak ditemukan.');
            return;
        }

        try {
            $usage = SupplyUsage::findOrFail($this->usageId);

            if (!$usage->canBeSubmitted()) {
                $this->dispatch('error', 'Usage tidak dapat disubmit. Status saat ini: ' . $usage->getStatusLabel());
                return;
            }

            $service = resolve(SupplyUsageService::class);
            $result = $service->submitForApproval($usage);

            $this->dispatch('success', $result['message']);
            $this->close();
        } catch (\Exception $e) {
            logErrorIfDebug('Usage@submitForApproval: Error submitting usage.', [
                'usage_id' => $this->usageId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', 'Gagal submit usage: ' . $e->getMessage());
        }
    }

    /**
     * Approve usage (new method)
     */
    public function approveUsage()
    {
        if (!$this->usageId) {
            $this->dispatch('error', 'Usage ID tidak ditemukan.');
            return;
        }

        try {
            $usage = SupplyUsage::findOrFail($this->usageId);

            if (!$usage->canBeApproved()) {
                $this->dispatch('error', 'Usage tidak dapat diapprove. Status saat ini: ' . $usage->getStatusLabel());
                return;
            }

            $service = resolve(SupplyUsageService::class);
            $result = $service->approveUsage($usage);

            $this->dispatch('success', $result['message']);
            $this->close();
        } catch (\Exception $e) {
            logErrorIfDebug('Usage@approveUsage: Error approving usage.', [
                'usage_id' => $this->usageId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', 'Gagal approve usage: ' . $e->getMessage());
        }
    }

    /**
     * Complete usage (new method)
     */
    public function completeUsage()
    {
        if (!$this->usageId) {
            $this->dispatch('error', 'Usage ID tidak ditemukan.');
            return;
        }

        try {
            $usage = SupplyUsage::findOrFail($this->usageId);

            if (!$usage->isInProcess()) {
                $this->dispatch('error', 'Usage tidak dapat diselesaikan. Status saat ini: ' . $usage->getStatusLabel());
                return;
            }

            $service = resolve(SupplyUsageService::class);
            $result = $service->completeUsage($usage);

            $this->dispatch('success', $result['message']);
            $this->close();
        } catch (\Exception $e) {
            logErrorIfDebug('Usage@completeUsage: Error completing usage.', [
                'usage_id' => $this->usageId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', 'Gagal complete usage: ' . $e->getMessage());
        }
    }

    /**
     * Enhanced cancel usage method with proper stock restoration
     */
    public function cancelSupplyUsage($id)
    {
        try {
            $usage = SupplyUsage::findOrFail($id);

            if (!$usage->canBeCancelled()) {
                $this->dispatch('error', 'Usage tidak dapat dibatalkan. Status saat ini: ' . $usage->getStatusLabel());
                return;
            }

            $service = resolve(SupplyUsageService::class);
            $result = $service->cancelUsage($usage, 'Cancelled by user');

            $this->dispatch('success', $result['message']);
        } catch (\Exception $e) {
            logErrorIfDebug('Usage@cancelSupplyUsage: Error cancelling usage.', [
                'usage_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', 'Gagal cancel usage: ' . $e->getMessage());
        }
    }

    /**
     * Enhanced delete usage method with status check
     */
    public function deleteSupplyUsage($id)
    {
        try {
            $usage = SupplyUsage::findOrFail($id);
            $user = Auth::user();
            $isSuperAdmin = $user && $user->hasRole('SuperAdmin');
            logDebugIfDebug('deleteSupplyUsage: Entry', [
                'user_id' => $user ? $user->id : null,
                'user_roles' => $user ? $user->getRoleNames() : null,
                'isSuperAdmin' => $isSuperAdmin,
                'usage_id' => $usage->id,
                'usage_status' => $usage->status,
            ]);

            if (!$isSuperAdmin && !$usage->canBeDeleted()) {
                logDebugIfDebug('deleteSupplyUsage: Blocked by canBeDeleted', [
                    'user_id' => $user ? $user->id : null,
                    'isSuperAdmin' => $isSuperAdmin,
                    'usage_status' => $usage->status,
                    'canBeDeleted' => $usage->canBeDeleted(),
                ]);
                $this->dispatch('error', 'Usage tidak dapat dihapus. Status saat ini: ' . $usage->getStatusLabel());
                return;
            }

            logDebugIfDebug('deleteSupplyUsage: Proceeding with deletion', [
                'user_id' => $user ? $user->id : null,
                'isSuperAdmin' => $isSuperAdmin,
                'usage_status' => $usage->status,
            ]);

            DB::transaction(function () use ($usage, $isSuperAdmin) {
                $supplyIds = $usage->details->pluck('supply_id')->unique();

                // Always restore stock usage for SuperAdmin, or if status allows stock modifications
                $canModifyStock = $isSuperAdmin || in_array($usage->status, [
                    SupplyUsage::STATUS_PENDING,
                    SupplyUsage::STATUS_IN_PROCESS,
                    SupplyUsage::STATUS_COMPLETED
                ]);

                logDebugIfDebug('deleteSupplyUsage: canModifyStock', [
                    'canModifyStock' => $canModifyStock,
                    'isSuperAdmin' => $isSuperAdmin,
                    'usage_status' => $usage->status,
                ]);

                if ($canModifyStock) {
                    foreach ($usage->details as $detail) {
                        $supplyStock = SupplyStock::find($detail->supply_stock_id);
                        if ($supplyStock) {
                            $before = $supplyStock->quantity_used;
                            $supplyStock->decrement('quantity_used', $detail->converted_quantity);
                            $after = $supplyStock->fresh()->quantity_used;
                            logDebugIfDebug('deleteSupplyUsage: SupplyStock restored', [
                                'supply_stock_id' => $supplyStock->id,
                                'before' => $before,
                                'restored' => $detail->converted_quantity,
                                'after' => $after
                            ]);
                        }
                        $currentSupply = CurrentSupply::where('farm_id', $usage->farm_id)
                            ->where('item_id', $detail->supply_id)
                            ->where('type', 'Supply')
                            ->first();
                        if ($currentSupply) {
                            $before = $currentSupply->quantity;
                            $currentSupply->increment('quantity', $detail->converted_quantity);
                            $after = $currentSupply->fresh()->quantity;
                            logDebugIfDebug('deleteSupplyUsage: CurrentSupply restored', [
                                'current_supply_id' => $currentSupply->id,
                                'before' => $before,
                                'restored' => $detail->converted_quantity,
                                'after' => $after
                            ]);
                        }
                    }
                }

                // Delete tracking records for this usage
                $deleted = DB::table('supply_usage_stock_tracking')->where('supply_usage_id', $usage->id)->delete();
                logDebugIfDebug('deleteSupplyUsage: Tracking records deleted', [
                    'usage_id' => $usage->id,
                    'deleted_count' => $deleted
                ]);

                logDebugIfDebug('deleteSupplyUsage: Deleting usage and details', [
                    'usage_id' => $usage->id,
                ]);
                $usage->details()->delete();
                $usage->delete();

                foreach ($supplyIds as $supplyId) {
                    $this->syncCurrentSupply($usage->farm_id, $usage->livestock_id, $supplyId);
                }
            });

            logDebugIfDebug('deleteSupplyUsage: Deletion complete', [
                'usage_id' => $usage->id,
            ]);
            $this->dispatch('success', 'Supply usage berhasil dihapus.');
        } catch (\Exception $e) {
            logErrorIfDebug('Usage@deleteSupplyUsage: Error deleting usage.', [
                'usage_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', 'Gagal hapus usage: ' . $e->getMessage());
        }
    }

    /**
     * Validate status transition based on business rules
     */
    private function isValidStatusTransition($currentStatus, $newStatus): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $role = $user->getRoleNames()->first();
        $allowed = SupplyUsageStatusHelper::getAllowedStatusOptions($role, $currentStatus);
        return in_array($newStatus, $allowed);
    }

    /**
     * Update supply usage status from DataTable dropdown with bypass support
     */
    public function updateStatusSupplyUsage($usageId, $status, $notes = null)
    {
        if (empty($usageId) || empty($status)) {
            logWarningIfDebug('updateStatusSupplyUsage: usageId atau status kosong', [
                'usageId' => $usageId,
                'status' => $status
            ]);
            $this->dispatch('error', 'Usage ID atau status tidak valid.');
            return;
        }

        try {
            $usage = SupplyUsage::findOrFail($usageId);
            // dd($usage);

            // VALIDASI LIVESTOCK_ID PALING AWAL - SEBELUM PROSES APAPUN
            if ($status === SupplyUsage::STATUS_COMPLETED && empty($usage->livestock_id)) {
                logErrorIfDebug('updateStatusSupplyUsage: BLOCKED - Tidak bisa complete tanpa livestock_id', [
                    'usage_id' => $usage->id,
                    'status' => $status,
                    'livestock_id' => $usage->livestock_id,
                    'blocked_at' => 'ENTRY_VALIDATION'
                ]);
                $this->dispatch('error', 'Tidak bisa menyelesaikan usage: Livestock belum dipilih.');
                // Dispatch event untuk reset dropdown status
                $this->dispatch('supply-usage-error', usageId: $usage->id);
                $this->dispatch('reload-table');

                return;
            }

            $previousStatus = $usage->status;
            $notes = $notes ?? null;
            $user = Auth::user();
            $userRole = null;
            if ($user) {
                if (method_exists($user, 'getRoleNames')) {
                    $userRole = $user->getRoleNames()->first();
                } elseif (property_exists($user, 'roles')) {
                    $userRole = is_array($user->roles) ? ($user->roles[0] ?? null) : $user->roles;
                }
            }
            // Log detail setelah validasi livestock_id berhasil
            logDebugIfDebug('updateStatusSupplyUsage: ENTRY', [
                'input_usageId' => $usageId,
                'input_status' => $status,
                'input_notes' => $notes,
                'usage_id' => $usage->id,
                'usage_status' => $usage->status,
                'usage_livestock_id' => $usage->livestock_id,
                'user_id' => $user ? $user->id : null,
                'user_role' => $userRole,
                'livestock_validation' => 'PASSED'
            ]);
            // Hapus validasi livestock_id yang lama (sudah dipindah ke atas)
            // ... existing code ...

            logDebugIfDebug('updateStatusSupplyUsage: Starting status update', [
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $status,
                'user_role' => $userRole,
                'user_id' => $user->id
            ]);

            // Check if notes are required
            if (SupplyUsage::requiresNotesForStatusChange() && empty($notes)) {
                $this->dispatch('error', 'Notes wajib diisi untuk perubahan status.');
                return;
            }

            // Validate status transition using bypass configuration
            if (!$usage->canTransitionTo($status, $user)) {
                $this->dispatch('error', 'Transisi status tidak valid: ' . $previousStatus . ' → ' . $status);
                return;
            }

            // Check if user can bypass approval
            $canBypass = $usage->canBypassApproval($status, $user);
            $bypassesStockImpact = $usage->bypassesStockImpact($status);

            logDebugIfDebug('updateStatusSupplyUsage: Bypass checks', [
                'can_bypass' => $canBypass,
                'bypasses_stock_impact' => $bypassesStockImpact,
                'transition' => $previousStatus . '_to_' . $status
            ]);

            // Determine if stock should be updated based on status impact
            $shouldUpdateStock = $this->shouldUpdateStockForStatusChange($previousStatus, $status, $bypassesStockImpact);

            logDebugIfDebug('updateStatusSupplyUsage: Stock update decision', [
                'should_update_stock' => $shouldUpdateStock,
                'previous_status' => $previousStatus,
                'new_status' => $status,
                'bypasses_stock_impact' => $bypassesStockImpact,
                'transition' => $previousStatus . '_to_' . $status,
                'note' => 'Stock update is based on status transition rules, not bypass config'
            ]);

            // Use SupplyUsageService for status transition
            $service = resolve(SupplyUsageService::class);

            switch ($status) {
                case SupplyUsage::STATUS_PENDING:
                    if ($previousStatus === SupplyUsage::STATUS_DRAFT) {
                        if ($canBypass) {
                            // Direct transition without approval
                            $usage->status = $status;
                            $usage->save();
                            $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                        } else {
                            $result = $service->submitForApproval($usage);
                        }
                    } else {
                        $usage->status = $status;
                        $usage->save();
                        $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                    }
                    break;

                case SupplyUsage::STATUS_IN_PROCESS:
                    if ($previousStatus === SupplyUsage::STATUS_PENDING) {
                        if ($canBypass) {
                            // Direct transition without approval
                            $usage->status = $status;
                            $usage->save();
                            $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                        } else {
                            $result = $service->approveUsage($usage);
                        }
                    } else {
                        $usage->status = $status;
                        $usage->save();
                        $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                    }
                    break;

                case SupplyUsage::STATUS_COMPLETED:
                    if ($previousStatus === SupplyUsage::STATUS_IN_PROCESS) {
                        if ($canBypass) {
                            // Direct transition without approval
                            $usage->status = $status;
                            $usage->save();
                            $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                        } else {
                            $result = $service->completeUsage($usage);
                        }
                    } else {
                        $usage->status = $status;
                        $usage->save();
                        $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                    }
                    break;

                case SupplyUsage::STATUS_CANCELLED:
                    $result = $service->cancelUsage($usage, $notes ?? 'Cancelled via DataTable');
                    break;

                case SupplyUsage::STATUS_REJECTED:
                    // Direct status update for rejected
                    $usage->status = $status;
                    $usage->save();
                    $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                    break;

                default:
                    // Direct status update for other statuses
                    $usage->status = $status;
                    $usage->save();
                    $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                    break;
            }

            // Update stock if required based on status impact rules
            if ($shouldUpdateStock) {
                // Check if we should use background job (configurable)
                $useBackgroundJob = config('supply_usage.use_background_job_for_stock_update', false);

                if ($useBackgroundJob) {
                    // Dispatch background job for stock update
                    $this->dispatchStockUpdateJob($usage, $previousStatus, $status, $user->id);

                    logDebugIfDebug('updateStatusSupplyUsage: Background job dispatched', [
                        'usage_id' => $usage->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $status,
                        'user_id' => $user->id
                    ]);
                } else {
                    // Synchronous stock update
                    $stockService = resolve(SupplyUsageStockService::class);
                    $stockResult = $stockService->updateStockForStatusChange($usage, $previousStatus, $status);

                    logDebugIfDebug('updateStatusSupplyUsage: Stock service result', [
                        'usage_id' => $usage->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $status,
                        'stock_result' => $stockResult
                    ]);

                    if (!$stockResult['success']) {
                        $this->dispatch('error', 'Gagal update stock: ' . $stockResult['message']);
                        return;
                    }
                }
            }

            // Log audit trail if enabled
            if (SupplyUsage::isAuditTrailEnabled()) {
                logInfoIfDebug('Supply Usage Status Change', [
                    'usage_id' => $usage->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $status,
                    'user_id' => $user->id,
                    'user_role' => $userRole,
                    'bypass_used' => $canBypass,
                    'stock_updated' => $shouldUpdateStock,
                    'notes' => $notes
                ]);
            }

            logDebugIfDebug('updateStatusSupplyUsage: Status updated successfully', [
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $status,
                'bypass_used' => $canBypass,
                'stock_updated' => $shouldUpdateStock,
                'result' => $result
            ]);

            $this->dispatch('statusUpdated');
            $this->dispatch('success', $result['message'] ?? 'Status supply usage berhasil diperbarui.');
            // Dispatch event untuk update original status di dropdown
            $this->dispatch('supply-usage-success', usageId: $usage->id);
        } catch (\Exception $e) {
            logErrorIfDebug('updateStatusSupplyUsage: Error updating status', [
                'usage_id' => $usageId,
                'new_status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Gagal update status: ' . $e->getMessage());
            // Dispatch event untuk reset dropdown status jika ada error
            $this->dispatch('supply-usage-error', usageId: $usageId);
        }
    }

    /**
     * Determine if stock should be updated for status change
     */
    private function shouldUpdateStockForStatusChange($previousStatus, $newStatus, $bypassesStockImpact)
    {
        // NOTE: bypassesStockImpact is for approval workflow, not for stock calculation
        // Stock should always be updated based on status transition rules

        // Define status transitions that require stock updates
        $stockImpactTransitions = [
            // From draft to processing states - start stock reduction
            'draft_to_pending' => true,
            'draft_to_in_process' => true,
            'draft_to_completed' => true,

            // From pending to processing states - continue stock reduction
            'pending_to_in_process' => true,
            'pending_to_completed' => true,

            // From in_process to completed - finalize stock reduction
            'in_process_to_completed' => true,

            // Cancellation from any status - restore stock
            'pending_to_cancelled' => 'restore',
            'in_process_to_cancelled' => 'restore',
            'completed_to_cancelled' => 'restore',

            // Rejection - restore stock
            'pending_to_rejected' => 'restore',
            'in_process_to_rejected' => 'restore',
        ];

        $transition = $previousStatus . '_to_' . $newStatus;
        $stockAction = isset($stockImpactTransitions[$transition]) ? $stockImpactTransitions[$transition] : false;

        logDebugIfDebug('shouldUpdateStockForStatusChange: Decision', [
            'transition' => $transition,
            'stock_action' => $stockAction,
            'bypasses_stock_impact' => $bypassesStockImpact,
            'note' => 'Bypass only affects approval workflow, not stock calculation'
        ]);

        return $stockAction;
    }

    /**
     * Dispatch background job for stock update
     */
    private function dispatchStockUpdateJob(SupplyUsage $usage, string $previousStatus, string $newStatus, string $userId): void
    {
        try {
            UpdateSupplyUsageStockJob::dispatch(
                $usage->id,
                $previousStatus,
                $newStatus,
                $userId
            );

            logDebugIfDebug('dispatchStockUpdateJob: Job queued successfully', [
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'user_id' => $userId
            ]);
        } catch (\Exception $e) {
            logErrorIfDebug('dispatchStockUpdateJob: Failed to queue job', [
                'usage_id' => $usage->id,
                'error' => $e->getMessage()
            ]);

            // Fallback to synchronous processing
            $stockService = resolve(SupplyUsageStockService::class);
            $stockResult = $stockService->updateStockForStatusChange($usage, $previousStatus, $newStatus);

            if (!$stockResult['success']) {
                $this->dispatch('error', 'Gagal update stock: ' . $stockResult['message']);
            }
        }
    }
}
