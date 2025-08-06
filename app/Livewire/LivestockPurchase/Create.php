<?php

namespace App\Livewire\LivestockPurchase;

use App\Services\AuditTrailService;
use App\Traits\HasTempAuthorization;
use App\Services\ValidationService;
use App\Traits\HasValidation;
use App\Services\VerificationService;
use App\Models\ModelVerification;

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
use Illuminate\Support\Facades\Auth;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Config\CompanyConfig;
use App\Models\CompanyUser;
use App\Services\Livestock\LivestockNumberGeneratorService;

class Create extends Component
{
    use WithFileUploads;

    public $livestockId;
    public $invoice_number;
    public $date;
    public $supplier_id;
    public $expedition_id = null;
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
    public $status = 'draft'; // FIX: Set default status to draft for create mode
    public $notes = null;
    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    protected $listeners = [
        'deleteLivestockPurchase' => 'deleteLivestockPurchase',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'updateStatusLivestockPurchase' => 'updateStatusLivestockPurchase',
        'tempAuthGranted' => 'onTempAuthGranted',
        'tempAuthRevoked' => 'onTempAuthRevoked',
        'echo:livestock-purchases,status-changed' => 'handleStatusChanged',
    ];

    public function getListeners()
    {
        return array_merge($this->listeners, [
            'echo-notification:App.Models.User.' . Auth::user()->id => 'handleUserNotification',
        ]);
    }

    public function mount()
    {
        // $this->initializeTempAuth();
    }

    /**
     * Normalize expedition_id to ensure it's either a valid UUID or null
     */
    private function normalizeExpeditionId($expeditionId)
    {
        // If it's null, return null
        if ($expeditionId === null) {
            return null;
        }

        // If it's an empty string, return null
        if ($expeditionId === '') {
            return null;
        }

        // If it's a valid UUID format, return it
        if (is_string($expeditionId) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $expeditionId)) {
            return $expeditionId;
        }

        // If it's not a valid UUID, return null
        Log::warning('Invalid expedition_id format detected', [
            'expedition_id' => $expeditionId,
            'type' => gettype($expeditionId)
        ]);
        return null;
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

    /**
     * Get the current user's company and livestock purchase config (reusable)
     */
    public function getLivestockPurchaseConfig()
    {
        $mapping = \App\Models\CompanyUser::getUserMapping();
        if (!$mapping || !$mapping->company) {
            \Illuminate\Support\Facades\Log::warning('User not mapped to any company or company not found', ['user_id' => Auth::user()->id]);
            return [null, \App\Config\CompanyConfig::getDefaultActiveConfig()['purchasing']['livestock_purchase'] ?? []];
        }

        $company = $mapping->company;

        // Use Company model's getConfig() method which includes fallback to defaults
        $companyConfig = $company->getConfig();
        $livestockConfig = $companyConfig['purchasing']['livestock_purchase'] ?? \App\Config\CompanyConfig::getDefaultActiveConfig()['purchasing']['livestock_purchase'];

        \Illuminate\Support\Facades\Log::info('Loaded company livestock config', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'config_source' => 'database_with_fallback',
            'config' => $livestockConfig
        ]);

        return [$company, $livestockConfig];
    }

    public function addItem()
    {
        // [$company, $livestockConfig] = $this->getLivestockPurchaseConfig();

        // // Config-based validation: enforce max items if set in config
        // $batchSettings = $livestockConfig['batch_settings'] ?? [];
        // $allowMultipleBatches = $batchSettings['allow_multiple_batches'] ?? [];

        // if (($allowMultipleBatches['enabled'] ?? false) === false && count($this->items) >= 1) {
        //     throw ValidationException::withMessages([
        //         'items' => 'Konfigurasi perusahaan hanya mengizinkan satu batch per pembelian.'
        //     ]);
        // }

        $maxBatches = $allowMultipleBatches['max_batches'] ?? 3;
        if (count($this->items) >= $maxBatches) {
            throw ValidationException::withMessages([
                'items' => "Jumlah item tidak boleh lebih dari {$maxBatches}."
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
            'sub_total' => 0,
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
            'updated_by' => Auth::user()->id,
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
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
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

        // dd($allBatches);

        $totalQuantity = $allBatches->sum('initial_quantity');
        $totalWeight = $allBatches->sum(function ($batch) {
            return $batch->initial_quantity * $batch->initial_weight;
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

        // dd($totalQuantity, $totalWeight, $avgWeight);

        return [
            'quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'avg_weight' => $avgWeight
        ];
    }

    /**
     * Update or create CurrentLivestock record
     */
    private function updateCurrentLivestock($farm, $kandang, $livestock)
    {
        $totals = $this->calculateCurrentLivestockTotals($farm, $kandang, $livestock);
        // dd($totals);

        $currentLivestock = CurrentLivestock::updateOrCreate(
            [
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'livestock_id' => $livestock->id,
            ],
            [
                'quantity' => $totals['quantity'],
                'weight_total' => $totals['total_weight'],
                'weight_avg' => $totals['avg_weight'],
                'age' => 0,
                'status' => 'active',
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]
        );

        Log::info("Updated CurrentLivestock record", [
            'current_livestock_id' => $currentLivestock->id,
            'new_quantity' => $totals['quantity'],
            'new_berat_total' => $totals['total_weight'],
            'new_avg_berat' => $totals['avg_weight']
        ]);

        return $currentLivestock;
    }

    /**
     * Process a single livestock purchase item
     */
    // private function processPurchaseItem($purchase, $item, $breed, $farm, $kandang)
    // {
    //     Log::info("Processing purchase item", [
    //         'purchase_id' => $purchase->id,
    //         'breed_id' => $breed->id,
    //         'farm_id' => $farm->id,
    //         'coop_id' => $kandang->id,
    //         'item_data' => $item
    //     ]);

    //     // Find or create livestock record
    //     $livestock = Livestock::where([
    //         'farm_id' => $farm->id,
    //         'coop_id' => $kandang->id,
    //     ])->first();

    //     if (!$livestock) {
    //         Log::info("Creating new livestock record", [
    //             'breed_id' => $breed->id,
    //             'farm_id' => $farm->id,
    //             'coop_id' => $kandang->id
    //         ]);

    //         // Create new livestock record with initial values
    //         $livestock = Livestock::create([
    //             'farm_id' => $farm->id,
    //             'coop_id' => $kandang->id,
    //             'breed' => $breed->name,
    //             'initial_quantity' => $item['quantity'],
    //             'initial_weight' => $item['weight_per_unit'],
    //             'initial_price' => $item['price_per_unit'],
    //             'start_date' => $this->date,
    //             'created_by' => Auth::user()->id,
    //             'updated_by' => Auth::user()->id,
    //         ]);

    //         Log::info("Created new livestock record", [
    //             'livestock_id' => $livestock->id,
    //             'initial_quantity' => $item['quantity'],
    //             'initial_weight' => $item['weight_per_unit'],
    //             'initial_price' => $item['price_per_unit']
    //         ]);
    //     } else {
    //         Log::info("Found existing livestock record", [
    //             'livestock_id' => $livestock->id
    //         ]);
    //     }

    //     // Create or update purchase item
    //     $purchaseItem = LivestockPurchaseItem::updateOrCreate(
    //         [
    //             'livestock_purchase_id' => $purchase->id,
    //             'livestock_id' => $livestock->id,
    //         ],
    //         [
    //             'quantity' => $item['quantity'],
    //             'price_per_unit' => $item['price_per_unit'],
    //             'price_total' => $item['price_total'],
    //             'weight_per_unit' => $item['weight_per_unit'],
    //             'weight_total' => $item['weight_total'],
    //             'created_by' => Auth::user()->id,
    //             'updated_by' => Auth::user()->id,
    //         ]
    //     );

    //     Log::info("Created/Updated purchase item", [
    //         'purchase_item_id' => $purchaseItem->id,
    //         'livestock_id' => $livestock->id,
    //         'quantity' => $item['quantity']
    //     ]);

    //     // Create or update livestock batch
    //     $batch = LivestockBatch::updateOrCreate(
    //         [
    //             'livestock_purchase_item_id' => $purchaseItem->id,
    //             'livestock_id' => $livestock->id,
    //         ],
    //         [
    //             'source_type' => 'purchase',
    //             'source_id' => $purchase->id,
    //             'farm_id' => $farm->id,
    //             'coop_id' => $kandang->id,
    //             'initial_quantity' => $item['quantity'],
    //             'initial_weight' => $item['weight_per_unit'],
    //             'initial_price' => $item['price_per_unit'],
    //             'name' => $breed->name,
    //             'breed' => $breed->name,
    //             'start_date' => $item['start_date'],
    //             'status' => 'active',
    //             'created_by' => Auth::user()->id,
    //             'updated_by' => Auth::user()->id,
    //         ]
    //     );

    //     Log::info("Created/Updated livestock batch", [
    //         'batch_id' => $batch->id,
    //         'initial_quantity' => $item['quantity'],
    //         'initial_weight' => $item['weight_per_unit'],
    //         'initial_price' => $item['price_per_unit']
    //     ]);

    //     // Recalculate Livestock values based on all batches
    //     $allBatches = LivestockBatch::where([
    //         'livestock_id' => $livestock->id,
    //         'farm_id' => $farm->id,
    //         'coop_id' => $kandang->id,
    //     ])->when(!$this->withHistory, function ($query) {
    //         return $query->where('status', '!=', 'inactive');
    //     })->get();

    //     Log::info("Recalculating livestock values", [
    //         'livestock_id' => $livestock->id,
    //         'batch_count' => $allBatches->count(),
    //         'with_history' => $this->withHistory
    //     ]);

    //     $totalPopulasi = $allBatches->sum('populasi_awal');
    //     $totalBerat = $allBatches->sum(function ($batch) {
    //         return $batch->populasi_awal * $batch->berat_awal;
    //     });
    //     $avgBerat = $totalPopulasi > 0 ? $totalBerat / $totalPopulasi : 0;
    //     $totalHarga = $allBatches->sum(function ($batch) {
    //         return $batch->populasi_awal * $batch->harga;
    //     });
    //     $avgHarga = $totalPopulasi > 0 ? $totalHarga / $totalPopulasi : 0;

    //     // Update livestock record with recalculated values
    //     $livestock->update([
    //         'breed' => $breed->name,
    //         'populasi_awal' => $totalPopulasi,
    //         'berat_awal' => $avgBerat,
    //         'harga' => $avgHarga,
    //         'start_date' => $item['start_date'],
    //         'updated_by' => Auth::user()->id,
    //     ]);

    //     Log::info("Updated livestock record", [
    //         'livestock_id' => $livestock->id,
    //         'total_populasi' => $totalPopulasi,
    //         'avg_berat' => $avgBerat,
    //         'avg_harga' => $avgHarga
    //     ]);

    //     // Update CurrentLivestock
    //     $this->updateCurrentLivestock($farm, $kandang, $livestock);

    //     return [
    //         'livestock' => $livestock,
    //         'purchaseItem' => $purchaseItem,
    //         'batch' => $batch
    //     ];
    // }

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

    /**
     * Validate the purchase data before saving
     *
     * @param array $data
     * @return array
     */
    private function validatePurchaseData(array $data): array
    {
        $errors = [];

        // Validate required fields
        if (empty($data['tanggal'])) {
            $errors['tanggal'] = 'Tanggal harus diisi';
        }

        if (empty($data['invoice_number'])) {
            $errors['invoice_number'] = 'Nomor invoice harus diisi';
        }

        if (empty($data['supplier_id'])) {
            $errors['supplier_id'] = 'Supplier harus dipilih';
        }

        if (empty($data['farm_id'])) {
            $errors['farm_id'] = 'Farm harus dipilih';
        }

        if (empty($data['coop_id'])) {
            $errors['coop_id'] = 'Kandang harus dipilih';
        }

        // FIX: Validasi items - izinkan penyimpanan tanpa item untuk draft status
        $currentStatus = $data['status'] ?? 'draft';

        // Hanya validasi items jika status bukan draft (untuk edit mode atau status lain)
        if ($currentStatus !== 'draft') {
            if (empty($data['items']) || count($data['items']) === 0) {
                $errors['items'] = 'Minimal satu item livestock harus ditambahkan';
            } else {
                foreach ($data['items'] as $index => $item) {
                    if (empty($item['livestock_strain_id'])) {
                        $errors["items.{$index}.livestock_strain_id"] = 'Strain harus dipilih';
                    }

                    if (empty($item['quantity']) || $item['quantity'] <= 0) {
                        $errors["items.{$index}.quantity"] = 'Jumlah harus lebih dari 0';
                    }

                    if (empty($item['price_value']) || $item['price_value'] <= 0) {
                        $errors["items.{$index}.price_value"] = 'Harga harus lebih dari 0';
                    }
                }
            }
        } else {
            // Untuk draft status, validasi items hanya jika ada items
            if (!empty($data['items']) && count($data['items']) > 0) {
                foreach ($data['items'] as $index => $item) {
                    if (empty($item['livestock_strain_id'])) {
                        $errors["items.{$index}.livestock_strain_id"] = 'Strain harus dipilih';
                    }

                    if (empty($item['quantity']) || $item['quantity'] <= 0) {
                        $errors["items.{$index}.quantity"] = 'Jumlah harus lebih dari 0';
                    }

                    if (empty($item['price_value']) || $item['price_value'] <= 0) {
                        $errors["items.{$index}.price_value"] = 'Harga harus lebih dari 0';
                    }
                }
            }
        }

        // FIX: Validasi ekspedisi untuk status complete
        if ($data['status'] === 'complete') {
            if (empty($data['expedition_id'])) {
                $errors['expedition_id'] = 'Ekspedisi harus dipilih untuk status complete';
            }

            if (empty($data['expedition_fee']) || $data['expedition_fee'] <= 0) {
                $errors['expedition_fee'] = 'Biaya ekspedisi harus diisi untuk status complete';
            }
        }

        // Validate batch name if provided
        if (!empty($data['batch_name'])) {
            if (strlen($data['batch_name']) < 3) {
                $errors['batch_name'] = 'Nama batch minimal 3 karakter';
            }
        }

        Log::info('validatePurchaseData: Validation result', [
            'status' => $currentStatus,
            'items_count' => count($data['items'] ?? []),
            'errors' => $errors,
            'user_id' => Auth::user()->id
        ]);

        return $errors;
    }

    /**
     * Save the livestock purchase with verification check
     */
    public function save()
    {
        Log::info('Save process started', [
            'farm_id' => $this->farm_id,
            'coop_id' => $this->coop_id,
            'status' => $this->status,
            'invoice_number' => $this->invoice_number,
            'date' => $this->date,
            'supplier_id' => $this->supplier_id,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
            'items' => $this->items,
        ]);

        [$company, $livestockConfig] = $this->getLivestockPurchaseConfig();
        $this->errorItems = [];
        Log::info('Config loaded', ['company' => $company?->id, 'livestockConfig' => $livestockConfig]);

        try {
            // FIX: Gunakan validasi custom yang baru
            $purchaseData = [
                'tanggal' => $this->date,
                'invoice_number' => $this->invoice_number,
                'supplier_id' => $this->supplier_id,
                'farm_id' => $this->farm_id,
                'coop_id' => $this->coop_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'batch_name' => $this->batch_name,
                'status' => $this->status ?? LivestockPurchase::STATUS_DRAFT,
                'items' => $this->items
            ];

            $validationErrors = $this->validatePurchaseData($purchaseData);

            if (!empty($validationErrors)) {
                Log::warning('Validation failed', ['errors' => $validationErrors]);
                foreach ($validationErrors as $field => $message) {
                    $this->addError($field, $message);
                }
                return;
            }

            Log::info('Validation passed');

            // FIX: Handle empty items for draft status
            $currentStatus = $this->status ?? 'draft';
            $hasItems = !empty($this->items) && count($this->items) > 0;

            Log::info('Save process - Items check', [
                'status' => $currentStatus,
                'has_items' => $hasItems,
                'items_count' => count($this->items),
                'can_save_without_items' => $this->canSaveWithoutItems(),
                'user_id' => Auth::user()->id
            ]);

            // Jika tidak ada items dan bukan draft status, tampilkan error
            if (!$hasItems && !$this->canSaveWithoutItems()) {
                $this->addError('items', 'Minimal satu item livestock harus ditambahkan untuk status ini');
                return;
            }

            if (!empty($this->errorItems)) {
                Log::warning('Config-based validation failed', ['errors' => $this->errorItems]);
                return;
            }
            Log::info('Config-based validation passed');

            $normalizedExpeditionId = $this->normalizeExpeditionId($this->expedition_id);

            // FIX: Pertahankan status yang ada saat edit mode, jangan selalu set ke draft
            $finalStatus = $this->getFinalStatusForSave();

            $purchaseData = [
                'invoice_number' => $this->invoice_number,
                'tanggal' => $this->date,
                'supplier_id' => $this->supplier_id,
                'farm_id' => $this->farm_id,
                'coop_id' => $this->coop_id,
                'expedition_id' => $normalizedExpeditionId,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'status' => $finalStatus,
                'updated_by' => Auth::user()->id,
                'data' => [
                    'batch_name' => $this->batch_name,
                    'total_quantity' => array_sum(array_column($this->items, 'quantity')),
                    'total_weight' => array_sum(array_map(function ($item) {
                        return $item['weight_type'] === 'per_unit' ?
                            ($item['weight_value'] * $item['quantity']) :
                            $item['weight_value'];
                    }, $this->items)),
                ]
            ];

            Log::info('Prepared purchase data', $purchaseData);
            Log::info('Status preservation info', [
                'edit_mode' => $this->edit_mode,
                'pembelian_id' => $this->pembelianId,
                'original_status' => $this->status,
                'final_status' => $finalStatus,
                'can_update_status' => $this->canUpdateStatus($finalStatus)
            ]);

            DB::beginTransaction();
            Log::info('DB transaction started');

            if ($this->edit_mode && $this->pembelianId) {
                // UPDATE MODE
                $purchase = LivestockPurchase::findOrFail($this->pembelianId);
                $purchase->update($purchaseData);
                // Hapus semua item lama, lalu insert ulang (atau bisa diupdate per item jika ingin lebih advance)
                LivestockPurchaseItem::where('livestock_purchase_id', $purchase->id)->delete();
            } else {
                // CREATE MODE
                $purchase = LivestockPurchase::create($purchaseData);

                // Generate automatic numbering for livestock purchase
                if (empty($purchase->number) || empty($purchase->number_full)) {
                    try {
                        $numbering = LivestockNumberGeneratorService::generateNumber('livestock_purchases', $purchase->tanggal ?? now(), []);
                        $purchase->number = $numbering['number'];
                        $purchase->number_full = $numbering['full_number'];
                        $purchase->save();

                        Log::info('Generated automatic numbering for LivestockPurchase', [
                            'purchase_id' => $purchase->id,
                            'number' => $purchase->number,
                            'number_full' => $purchase->number_full,
                            'date' => $purchase->tanggal
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to generate automatic numbering for LivestockPurchase', [
                            'purchase_id' => $purchase->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue without numbering - not critical
                    }
                }
            }
            Log::info('LivestockPurchase saved', ['id' => $purchase->id]);

            // FIX: Proses items hanya jika ada items atau bukan draft status
            if (!empty($this->items) && count($this->items) > 0) {
                // Proses setiap item
                foreach ($this->items as $item) {
                    $breed = LivestockStrain::findOrFail($item['livestock_strain_id']);
                    $farm = Farm::findOrFail($this->farm_id);
                    $kandang = Coop::findOrFail($this->coop_id);

                    $periodeFormat = 'PR-' . $farm->code . '-' . $kandang->code . '-' . Carbon::parse($purchase->tanggal)->format('dmY');
                    $periode = $this->batch_name ?? $periodeFormat;

                    $weightPerUnit = $item['weight_type'] === 'per_unit' ? $item['weight_value'] : ($item['weight_value'] / $item['quantity']);
                    $pricePerUnit = $item['price_type'] === 'per_unit' ? $item['price_value'] : ($item['price_value'] / $item['quantity']);
                    $weightTotal = $item['weight_type'] === 'per_unit' ? ($item['weight_value'] * $item['quantity']) : $item['weight_value'];
                    $priceTotal = $item['price_type'] === 'per_unit' ? ($item['price_value'] * $item['quantity']) : $item['price_value'];

                    $livestockData = [
                        'name' => $periode,
                        'farm_id' => $farm->id,
                        'coop_id' => $kandang->id,
                        'initial_quantity' => $item['quantity'],
                        'initial_weight' => $weightPerUnit,
                        'price' => $pricePerUnit,
                        'start_date' => $this->date,
                        'status' => 'active',
                    ];

                    $batchData = [
                        'name' => $periode,
                        'livestock_strain_id' => $breed->id,
                        'livestock_strain_name' => $breed->name,
                        'start_date' => $this->date,
                        'source_type' => 'purchase',
                        'source_id' => $purchase->id,
                        'farm_id' => $farm->id,
                        'coop_id' => $kandang->id,
                        'initial_quantity' => $item['quantity'],
                        'initial_weight' => $weightPerUnit,
                        'weight' => $weightPerUnit,
                        'weight_per_unit' => $weightPerUnit,
                        'weight_total' => $weightTotal,
                        'weight_type' => $item['weight_type'],
                        'weight_value' => $item['weight_value'],
                        'price_per_unit' => $pricePerUnit,
                        'price_total' => $priceTotal,
                        'price_type' => $item['price_type'],
                        'price_value' => $item['price_value'],
                        'status' => 'active',
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                    ];

                    LivestockPurchaseItem::create([
                        'tanggal' => $this->date,
                        'livestock_purchase_id' => $purchase->id,
                        'livestock_strain_id' => $item['livestock_strain_id'],
                        'livestock_strain_standard_id' => $item['livestock_strain_standard_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'price_value' => $item['price_value'],
                        'price_type' => $item['price_type'],
                        'weight_value' => $item['weight_value'],
                        'weight_type' => $item['weight_type'],
                        'weight_total' => $weightTotal,
                        'notes' => $item['notes'] ?? null,
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id,
                        'data' => [
                            'livestock' => $livestockData,
                            'batch' => $batchData,
                        ],
                    ]);

                    Log::info('LivestockPurchaseItem created', [
                        'purchase_id' => $purchase->id,
                        'strain_id' => $item['livestock_strain_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price_value']
                    ]);
                }
            } else {
                Log::info('No items to process for draft status', [
                    'purchase_id' => $purchase->id,
                    'status' => $currentStatus,
                    'items_count' => count($this->items)
                ]);
            }

            if (method_exists($this, 'verifyPurchase')) {
                Log::info('Verifying purchase', ['purchase_id' => $purchase->id]);
                $this->verifyPurchase([
                    'purchase_id' => $purchase->id,
                    'user_id' => Auth::user()->id,
                ], $this->notes ?? null);
                Log::info('Purchase verified, generating livestock and batch', ['purchase_id' => $purchase->id]);
                $this->generateLivestockAndBatch($purchase->id);
            }

            DB::commit();
            Log::info('DB transaction committed');
            $this->dispatch('success', 'Pembelian berhasil disimpan');
            $this->close();
            Log::info('Form reset after save');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in save()', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->addError('error', $e->getMessage());
        }
    }

    /**
     * Update purchase status with verification
     */
    /**
     * Update purchase status with verification
     * 
     * Refactored: 2024-06-11 10:45 WIB
     * - Jika update status ke in_coop gagal (generateLivestockAndBatch error), fallback ke status sebelumnya.
     * - Menambahkan log di setiap proses untuk debugging dan future proof.
     * 
     * Diagram:
     * [START] -> [Ambil purchase] -> [Cek status] -> [Jika in_coop: try generateLivestockAndBatch]
     *    |--(jika gagal)--> [Fallback ke status sebelumnya] -> [Log error & fallback] -> [Dispatch error]
     *    |--(jika sukses/selain in_coop)--> [Update status] -> [Dispatch success]
     */
    public function updateStatusLivestockPurchase($purchaseId, $status, $notes)
    {
        Log::info('updateStatusLivestockPurchase: Method called', [
            'purchaseId' => $purchaseId,
            'status' => $status,
            'notes' => $notes,
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name
        ]);

        if (empty($purchaseId) || empty($status)) {
            Log::warning('updateStatusLivestockPurchase: purchaseId atau status kosong', [
                'purchaseId' => $purchaseId,
                'status' => $status
            ]);
            return;
        }

        $purchase = \App\Models\LivestockPurchase::findOrFail($purchaseId);
        $notes = $notes ?? null;
        $previousStatus = $purchase->status;

        Log::info('updateStatusLivestockPurchase: Purchase found', [
            'purchase_id' => $purchase->id,
            'current_status' => $purchase->status,
            'new_status' => $status,
            'previous_status' => $previousStatus
        ]);

        // FIX: Validasi untuk status completed - harus ada data ekspedisi
        if ($status === 'completed' || $status === 'complete') {
            $validationErrors = $this->validateStatusForCompleted($purchase);
            if (!empty($validationErrors)) {
                Log::warning('updateStatusLivestockPurchase: Validasi gagal untuk status completed', [
                    'purchase_id' => $purchase->id,
                    'errors' => $validationErrors
                ]);
                $this->dispatch('error', 'Tidak bisa set status completed: ' . implode(', ', $validationErrors));
                return;
            }
        }

        // FIX: Validasi status transition untuk semua status
        $transitionErrors = $this->validateStatusTransition($purchase, $status);
        if (!empty($transitionErrors)) {
            Log::warning('updateStatusLivestockPurchase: Validasi status transition gagal', [
                'purchase_id' => $purchase->id,
                'current_status' => $purchase->status,
                'new_status' => $status,
                'errors' => $transitionErrors
            ]);
            $this->dispatch('error', 'Tidak bisa mengubah status: ' . implode(', ', $transitionErrors));
            return;
        }

        // If status is in_coop, try generating livestock and batch first
        if ($status === \App\Models\LivestockPurchase::STATUS_IN_COOP || $status === \App\Models\LivestockPurchase::STATUS_ARRIVED) {
            try {
                Log::info('updateStatusLivestockPurchase: Try generateLivestockAndBatch', [
                    'purchase_id' => $purchase->id
                ]);
                $this->generateLivestockAndBatch($purchase->id);
            } catch (\Exception $e) {
                Log::error('updateStatusLivestockPurchase: Gagal generate livestock, fallback ke status sebelumnya', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                    'previous_status' => $previousStatus
                ]);
                // Fallback ke status sebelumnya
                $purchase->updateStatus($previousStatus, $notes);
                $this->dispatch('error', 'Gagal generate livestock: ' . $e->getMessage() . '. Status dikembalikan ke sebelumnya.');
                $this->dispatch('statusUpdated');
                return;
            }
        }

        // Only update status if livestock generation was successful or not needed
        $purchase->updateStatus($status, $notes);

        Log::info('updateStatusLivestockPurchase: Status updated', [
            'purchase_id' => $purchase->id,
            'new_status' => $status
        ]);

        $this->dispatch('statusUpdated');
        $this->dispatch('success', 'Status pembelian berhasil diperbarui.');
    }

    /**
     * Validate purchase data for completed status
     * Ensure expedition data is available before setting status to completed
     */
    private function validateStatusForCompleted($purchase): array
    {
        $errors = [];

        // Check if expedition data is available
        if (empty($purchase->expedition_id)) {
            $errors[] = 'Ekspedisi harus dipilih untuk status completed';
        }

        if (empty($purchase->expedition_fee) || $purchase->expedition_fee <= 0) {
            $errors[] = 'Biaya ekspedisi harus diisi untuk status completed';
        }

        // Check if purchase has items
        if ($purchase->details()->count() === 0) {
            $errors[] = 'Pembelian harus memiliki minimal satu item untuk status completed';
        }

        // Check if all required fields are filled
        if (empty($purchase->invoice_number)) {
            $errors[] = 'Nomor invoice harus diisi untuk status completed';
        }

        if (empty($purchase->supplier_id)) {
            $errors[] = 'Supplier harus dipilih untuk status completed';
        }

        if (empty($purchase->farm_id)) {
            $errors[] = 'Farm harus dipilih untuk status completed';
        }

        if (empty($purchase->coop_id)) {
            $errors[] = 'Kandang harus dipilih untuk status completed';
        }

        Log::info('validateStatusForCompleted: Validation result', [
            'purchase_id' => $purchase->id,
            'expedition_id' => $purchase->expedition_id,
            'expedition_fee' => $purchase->expedition_fee,
            'items_count' => $purchase->details()->count(),
            'errors' => $errors
        ]);

        return $errors;
    }

    /**
     * Handle existing records when updating with history
     */
    private function handleExistingRecords($purchase)
    {
        // Get existing items
        $existingItems = $purchase->details()->with(['livestock', 'livestockBatches'])->get();

        // Update coop quantities
        foreach ($existingItems as $item) {
            $kandang = Coop::find($item->livestock->coop_id);
            if ($kandang) {
                $kandang->decrement('quantity', $item->quantity);
                $kandang->decrement('weight', $item->weight_total);
            }
        }

        // Delete existing batches
        foreach ($existingItems as $item) {
            $item->livestockBatches()->delete();
        }

        // Delete existing purchase items
        $purchase->details()->delete();
    }

    /**
     * Handle existing records when updating without history
     */
    private function handleExistingRecordsForEdit($purchase)
    {
        // Get existing items
        $existingItems = $purchase->details()->with(['livestock', 'livestockBatches'])->get();

        // Update coop quantities by subtracting old values
        foreach ($existingItems as $item) {
            $kandang = Coop::find($item->livestock->coop_id);
            if ($kandang) {
                $kandang->decrement('quantity', $item->quantity);
                $kandang->decrement('weight', $item->weight_total);
            }
        }

        // Update existing batches instead of deleting
        foreach ($existingItems as $item) {
            $batch = $item->livestockBatches->first();
            if ($batch) {
                if ($this->withHistory) {
                    $batch->update([
                        'status' => 'inactive',
                        'updated_by' => Auth::user()->id
                    ]);
                } else {
                    $batch->update([
                        'updated_by' => Auth::user()->id
                    ]);
                }
            }
        }

        // Mark existing purchase items as inactive instead of deleting
        if ($this->withHistory) {
            $purchase->details()->update([
                'status' => 'inactive',
                'updated_by' => Auth::user()->id
            ]);
        } else {
            $purchase->details()->update([
                'updated_by' => Auth::user()->id
            ]);
        }
    }

    public function resetForm()
    {
        $this->reset();
        $this->items = [];

        // FIX: Don't reset status and edit_mode if we're in edit mode
        if (!$this->edit_mode) {
            $this->status = 'draft'; // FIX: Ensure status is set to draft for create mode
            $this->edit_mode = false; // FIX: Ensure edit mode is false for create mode
        }
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

        // Trigger kalkulasi otomatis untuk field yang mempengaruhi sub total
        if (in_array($field, ['quantity', 'price_value', 'price_type'])) {
            $this->calculateItemSubTotal($index);
            $this->dispatch('item-updated', index: $index);
        }
    }

    /**
     * Kalkulasi sub total untuk item tertentu
     */
    public function calculateItemSubTotal($index)
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price_value'] ?? 0);
        $priceType = $item['price_type'] ?? 'per_unit';

        // Kalkulasi sub total berdasarkan tipe harga
        if ($priceType === 'per_unit') {
            $subTotal = $quantity * $price;
        } else {
            // Jika tipe harga adalah total, maka price_value sudah merupakan total
            $subTotal = $price;
        }

        $this->items[$index]['sub_total'] = $subTotal;
    }

    /**
     * Kalkulasi total keseluruhan
     */
    public function calculateTotal()
    {
        $total = 0;
        $discount = 0;

        foreach ($this->items as $index => $item) {
            $this->calculateItemSubTotal($index);
            $total += $this->items[$index]['sub_total'] ?? 0;
        }

        return [
            'sub_total' => $total,
            'discount' => $discount,
            'expedition_fee' => floatval($this->expedition_fee ?? 0),
            'total' => $total - $discount + floatval($this->expedition_fee ?? 0)
        ];
    }

    /**
     * Getter untuk total yang bisa diakses dari view
     */
    public function getTotalProperty()
    {
        return $this->calculateTotal();
    }

    /**
     * Getter untuk sub total item tertentu
     */
    public function getItemSubTotal($index)
    {
        if (!isset($this->items[$index])) {
            return 0;
        }

        $this->calculateItemSubTotal($index);
        return $this->items[$index]['sub_total'] ?? 0;
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
        // Check temp auth on every render
        // $this->checkTempAuth();

        $user = Auth::user();
        $companyId = $user->company_id;

        $strains = LivestockStrain::active()->where('company_id', $companyId)->orderBy('name')->get();
        $standardStrains = LivestockStrainStandard::active()->where('company_id', $companyId)->orderBy('livestock_strain_name')->get();

        // Get farms based on user role
        if ($user->hasRole('Operator')) {
            $farmIds = $user->farmOperators()->pluck('farm_id')->toArray();
            $farms = Farm::whereIn('id', $farmIds)->where('company_id', $companyId)->get(['id', 'name']);
        } else {
            $farms = Farm::where('status', 'active')->where('company_id', $companyId)->get(['id', 'name']);
        }

        return view('livewire.livestock-purchase.create', [
            'vendors' => Partner::where('type', 'Supplier')->where('company_id', $companyId)->get(),
            'expeditions' => Partner::where('type', 'Expedition')->where('company_id', $companyId)->get(),
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

    public function isReadonly()
    {
        // Log::info('Checking readonly status', [
        //     // 'tempAuthEnabled' => $this->tempAuthEnabled,
        //     'edit_mode' => $this->edit_mode,
        //     'status' => $this->status,
        // ]);

        // if ($this->tempAuthEnabled) {
        //     return false;
        // }

        return in_array($this->status, ['in_coop', 'arrived', 'complete']);
    }

    public function isDisabled()
    {
        // If temp auth is enabled, not disabled
        // if ($this->tempAuthEnabled) {
        //     return false;
        // }

        // Check local conditions - allow editing for arrived status
        return in_array($this->status, ['in_coop', 'arrived', 'complete']);
    }

    /**
     * Check if form can be saved
     * Allow saving for arrived status to update expedition details
     */
    public function canSave()
    {
        // FIX: Handle null status for create mode
        $currentStatus = $this->status ?? 'draft';

        // Allow saving for draft status (create mode) and other editable statuses
        $canSave = in_array($currentStatus, ['draft', 'confirmed', 'in_transit', 'arrived']);

        Log::info('canSave: Check if form can be saved', [
            'current_status' => $currentStatus,
            'original_status' => $this->status,
            'edit_mode' => $this->edit_mode,
            'can_save' => $canSave,
            'user_id' => Auth::user()->id
        ]);

        return $canSave;
    }

    /**
     * Check if invoice number field should be readonly
     * Invoice number should be editable for arrived status
     */
    public function isInvoiceNumberReadonly()
    {
        // Allow invoice number editing for arrived status
        if ($this->status === 'arrived') {
            return false;
        }

        return in_array($this->status, ['in_coop', 'complete']);
    }

    /**
     * Check if notes field should be readonly
     * Notes should be editable for arrived status
     */
    public function isNotesReadonly()
    {
        // Allow notes editing for arrived status
        if ($this->status === 'arrived') {
            return false;
        }

        return in_array($this->status, ['in_coop', 'complete']);
    }

    /**
     * Get current status for display and logic
     */
    public function getCurrentStatus()
    {
        // FIX: Handle null status for create mode
        $currentStatus = $this->status ?? 'draft';

        Log::info('getCurrentStatus: Current status', [
            'status' => $currentStatus,
            'edit_mode' => $this->edit_mode,
            'user_id' => Auth::user()->id
        ]);

        return $currentStatus;
    }

    /**
     * Check if expedition fields should be readonly
     * Expedition fields should be editable for in_coop and arrived status to add expedition details
     */
    public function isExpeditionReadonly()
    {
        // Allow expedition editing for in_coop and arrived status to add expedition details
        // Only readonly for complete status if expedition is already filled
        $isReadonly = false;
        $currentStatus = $this->getCurrentStatus();

        if ($currentStatus === 'complete' && !empty($this->expedition_id) && !empty($this->expedition_fee)) {
            $isReadonly = true;
        }

        Log::info('Expedition readonly check', [
            'status' => $currentStatus,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
            'is_readonly' => $isReadonly
        ]);

        return $isReadonly;
    }

    /**
     * Check if expedition fields should be disabled
     * Expedition fields should be editable for in_coop and arrived status
     */
    public function isExpeditionDisabled()
    {
        // Allow expedition editing for in_coop and arrived status
        // Only disabled for complete status if expedition is already filled
        $isDisabled = false;
        $currentStatus = $this->getCurrentStatus();

        if ($currentStatus === 'complete' && !empty($this->expedition_id) && !empty($this->expedition_fee)) {
            $isDisabled = true;
        }

        Log::info('Expedition disabled check', [
            'status' => $currentStatus,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
            'is_disabled' => $isDisabled
        ]);

        return $isDisabled;
    }

    /**
     * Check if batch name field should be readonly
     * Batch name should be editable in edit mode
     */
    public function isBatchNameReadonly()
    {
        // Allow batch name editing in edit mode
        if ($this->edit_mode) {
            return false;
        }

        return in_array($this->status, ['in_coop', 'arrived', 'complete']);
    }

    /**
     * Check if batch name field should be disabled
     */
    public function isBatchNameDisabled()
    {
        // Allow batch name editing in edit mode
        if ($this->edit_mode) {
            return false;
        }

        return in_array($this->status, ['in_coop', 'arrived', 'complete']);
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
                    'updated_by' => Auth::user()->id
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
            'updated_by' => Auth::user()->id
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
                'supplier'
            ])->findOrFail($purchaseId);

            // Check if the purchase status is in_coop or complete, preventing deletion
            if ($purchase->status == 'in_coop' || $purchase->status == 'complete') {
                $this->dispatch('error', 'Tidak bisa hapus karena status sudah dalam kandang atau selesai.');
                return;
            }

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
                        'updated_by' => Auth::user()->id
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

        Log::info('showEditForm: Starting edit form load', [
            'purchase_id' => $id,
            'user_id' => Auth::user()->id
        ]);

        $this->pembelianId = $id;
        $pembelian = LivestockPurchase::with([
            'details',
            'details.livestock',
            'details.livestockBatches',
            'supplier'
        ])->findOrFail($id);

        Log::info('showEditForm: Purchase found', [
            'purchase_id' => $pembelian->id,
            'status' => $pembelian->status,
            'invoice_number' => $pembelian->invoice_number,
            'supplier_id' => $pembelian->supplier_id,
            'expedition_id' => $pembelian->expedition_id,
            'expedition_fee' => $pembelian->expedition_fee,
            'details_count' => $pembelian->details->count(),
            'user_id' => Auth::user()->id
        ]);

        // Check if any livestock has transactions that prevent editing
        foreach ($pembelian->details as $item) {
            $livestock = $item->livestock;
            if ($livestock && ($livestock->quantity_depletion > 0 || $livestock->quantity_sales > 0 || $livestock->quantity_mutated > 0)) {
                $this->dispatch('error', 'Tidak bisa edit karena sudah memiliki transaksi.');
                return;
            }
        }

        // FIX: Reset form first to ensure clean state
        $this->resetForm();

        // FIX: Set edit mode first
        $this->edit_mode = true;
        $this->pembelianId = $id;

        // FIX: Load basic purchase data
        $this->date = $pembelian->tanggal;
        $this->invoice_number = $pembelian->invoice_number;
        $this->supplier_id = $pembelian->supplier_id;
        $this->expedition_id = $this->normalizeExpeditionId($pembelian->expedition_id);
        $this->expedition_fee = $pembelian->expedition_fee ?? 0;
        $this->status = $pembelian->status;
        $this->notes = $pembelian->notes ?? null;

        // FIX: Load batch name from multiple sources
        $this->batch_name = $pembelian->data['batch_name'] ??
            $pembelian->details->first()->livestock->name ??
            $this->generateBatchName($pembelian);

        Log::info('showEditForm: Basic data loaded', [
            'date' => $this->date,
            'invoice_number' => $this->invoice_number,
            'supplier_id' => $this->supplier_id,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
            'status' => $this->status,
            'batch_name' => $this->batch_name,
            'user_id' => Auth::user()->id
        ]);

        // FIX: Load farm and coop data
        // FIX: Load farm and coop data from purchase table first, then from details if available
        $this->farm_id = $pembelian->farm_id ?? null;
        $this->coop_id = $pembelian->coop_id ?? null;

        Log::info('showEditForm: Farm and coop data from purchase table', [
            'farm_id' => $this->farm_id,
            'coop_id' => $this->coop_id,
            'user_id' => Auth::user()->id
        ]);

        // If farm_id is set, load available kandangs
        if ($this->farm_id) {
            $this->availableKandangs = Coop::where('farm_id', $this->farm_id)
                ->where('status', '!=', 'inactive')
                ->whereRaw('quantity < capacity')
                ->get();

            Log::info('showEditForm: Available kandangs loaded', [
                'farm_id' => $this->farm_id,
                'kandangs_count' => $this->availableKandangs->count(),
                'user_id' => Auth::user()->id
            ]);
        }

        if ($pembelian->details->isNotEmpty()) {
            $firstItem = $pembelian->details->first();

            // FIX: Override farm_id and coop_id from details if available
            if (isset($firstItem->data['livestock']) && is_array($firstItem->data['livestock'])) {
                $livestockData = $firstItem->data['livestock'];
                if (!empty($livestockData['farm_id'])) {
                    $this->farm_id = $livestockData['farm_id'];
                }
                if (!empty($livestockData['coop_id'])) {
                    $this->coop_id = $livestockData['coop_id'];
                }
            }

            Log::info('showEditForm: Farm and coop data from details', [
                'farm_id' => $this->farm_id,
                'coop_id' => $this->coop_id,
                'user_id' => Auth::user()->id
            ]);

            // Reload available kandangs if farm_id changed
            if ($this->farm_id) {
                $this->availableKandangs = Coop::where('farm_id', $this->farm_id)
                    ->where('status', '!=', 'inactive')
                    ->whereRaw('quantity < capacity')
                    ->get();
            }

            // FIX: Load items data
            $this->items = [];
            foreach ($pembelian->details as $item) {
                $itemData = [
                    'livestock_id' => $item->livestock_id ?? null,
                    'livestock_strain_id' => $item->livestock_strain_id ?? null,
                    'quantity' => $item->quantity,
                    'price_value' => $item->price_value,
                    'price_type' => $item->price_type,
                    'farm_id' => $this->farm_id,
                    'coop_id' => $this->coop_id,
                    'livestock_strain_standard_id' => $item->livestock_strain_standard_id ?? null,
                    'start_date' => $this->date ?? null,
                    'weight_type' => $item->weight_type ?? 'per_unit',
                    'weight_value' => $item->weight_value ?? 0,
                ];

                // FIX: Try to get strain data from batch if not directly available
                if (empty($itemData['livestock_strain_id']) && !empty($item->data['batch'])) {
                    $itemData['livestock_strain_id'] = $item->data['batch']['livestock_strain_id'] ?? null;
                }

                $this->items[] = $itemData;
            }

            Log::info('showEditForm: Items loaded', [
                'items_count' => count($this->items),
                'items' => $this->items,
                'user_id' => Auth::user()->id
            ]);
        } else {
            Log::warning('showEditForm: No details found for purchase', [
                'purchase_id' => $pembelian->id,
                'user_id' => Auth::user()->id
            ]);
        }

        $this->showForm = true;
        $this->dispatch('hide-datatable');

        Log::info('showEditForm: Edit form loaded successfully', [
            'purchase_id' => $id,
            'edit_mode' => $this->edit_mode,
            'show_form' => $this->showForm,
            'user_id' => Auth::user()->id
        ]);

        // FIX: Debug loaded data
        $this->debugLoadedData();
    }

    /**
     * Generate batch name for edit mode if not available
     */
    private function generateBatchName($pembelian)
    {
        $farm = \App\Models\Farm::find($pembelian->farm_id);
        $coop = \App\Models\Coop::find($pembelian->coop_id);

        $farmCode = $farm->code ?? $farm->name ?? 'Farm';
        $coopCode = $coop->code ?? $coop->name ?? 'Coop';
        $date = $pembelian->tanggal ? $pembelian->tanggal->format('dmY') : now()->format('dmY');

        return "PR-{$farmCode}-{$coopCode}-{$date}";
    }

    /**
     * generateLivestockAndBatch
     * 
     * Rewritten: 2024-06-19 17:30 WIB
     * - Sederhanakan logic: hanya validasi jumlah batch yang akan dibuat harus sama dengan jumlah LivestockPurchaseItem.
     * - Tidak perlu validasi nilai batch, hanya jumlah data.
     * - Optimasi proses: batch creation dilakukan setelah validasi jumlah.
     * - Logging tetap detail untuk debugging.
     * 
     * Diagram:
     * [START] -> [Begin Transaction] -> [Simulasi & Validasi Jumlah Batch] -> [Create Batch] -> [Update Livestock, Kandang, CurrentLivestock] -> [Commit]
     */
    public function generateLivestockAndBatch($purchaseId)
    {
        Log::info('Starting generateLivestockAndBatch for purchase ID: ' . $purchaseId);

        DB::beginTransaction();
        try {
            $purchase = \App\Models\LivestockPurchase::with(['details'])->findOrFail($purchaseId);
            Log::info('Found purchase:', ['purchase' => $purchase->toArray()]);

            [$company, $livestockConfig] = $this->getLivestockPurchaseConfig();

            if ($company) {
                $recordingConfig = $company->getLivestockRecordingConfig();
                Log::info('Using company livestock recording config', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,

                    'config_source' => 'company_database'
                ]);
            } else {
                $recordingConfig = \App\Config\CompanyConfig::getDefaultActiveConfig()['livestock']['recording_method'];
                Log::info('Using default livestock recording config', [
                    'config_source' => 'default_fallback'
                ]);
            }

            // Log::info('Using livestock recording config:', ['config' => $recordingConfig]);

            // $validationResult = $this->validateRecordingMethodConfig($recordingConfig);
            // if (!$validationResult['is_valid']) {
            //     Log::error('Recording method configuration validation failed:', ['errors' => $validationResult['errors']]);
            //     throw new \Exception('Konfigurasi recording method tidak valid: ' . implode(', ', $validationResult['errors']));
            // }

            // Cek apakah sudah pernah dibuat batch untuk purchase ini
            $existingBatches = \App\Models\LivestockBatch::where('source_type', 'purchase')
                ->where('source_id', $purchase->id)
                ->count();

            Log::info('Checking existing batches for purchase:', [
                'purchase_id' => $purchase->id,
                'existing_batch_count' => $existingBatches
            ]);

            if ($existingBatches > 0) {
                Log::info('Batches already generated for this purchase, skipping...');
                DB::rollBack();
                return;
            }

            $farm = $purchase->farm;
            $kandang = $purchase->coop;

            // --- SIMULASI & VALIDASI: Hitung jumlah batch yang akan dibuat ---
            $expectedBatchCount = $purchase->details->count();
            Log::info('Simulated batch count:', ['expectedBatchCount' => $expectedBatchCount]);

            // --- LIVESTOCK RECORD ---
            $existingLivestock = Livestock::where([
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
            ])->first();

            if ($existingLivestock) {
                $livestock = $existingLivestock;
                Log::info('Using existing Livestock:', [
                    'livestock_id' => $livestock->id,
                    'current_initial_quantity' => $livestock->initial_quantity,
                    'current_initial_weight' => $livestock->initial_weight,
                    'current_price' => $livestock->price,
                    'current_number' => $livestock->number,
                    'current_number_full' => $livestock->number_full
                ]);

                // Generate automatic numbering for existing livestock if missing
                if (empty($livestock->number) || empty($livestock->number_full)) {
                    try {
                        $numbering = LivestockNumberGeneratorService::generateNumber('livestocks', $purchase->tanggal ?? now(), []);
                        $livestock->number = $numbering['number'];
                        $livestock->number_full = $numbering['full_number'];
                        $livestock->save();

                        Log::info('Generated automatic numbering for existing Livestock', [
                            'livestock_id' => $livestock->id,
                            'number' => $livestock->number,
                            'number_full' => $livestock->number_full,
                            'date' => $purchase->tanggal,
                            'reason' => 'Missing numbering on existing livestock'
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to generate automatic numbering for existing Livestock', [
                            'livestock_id' => $livestock->id,
                            'error' => $e->getMessage(),
                            'reason' => 'Missing numbering on existing livestock'
                        ]);
                        // Continue without numbering - not critical
                    }
                }
            } else {
                // Buat Livestock baru, nilai total akan diupdate setelah batch creation
                $livestock = \App\Models\Livestock::create([
                    'name' => $this->batch_name ?? 'PR-' . ($farm->code ?? $farm->name) . '-' . ($kandang->code ?? $kandang->name) . '-' . \Carbon\Carbon::parse($purchase->tanggal)->format('dmY'),
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'start_date' => $purchase->tanggal,
                    'initial_quantity' => 0, // Will be updated after batch creation
                    'initial_weight' => 0,   // Will be updated after batch creation
                    'price' => 0,           // Will be updated after batch creation
                    'status' => 'active',
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ]);

                // Generate automatic numbering for livestock
                if (empty($livestock->number) || empty($livestock->number_full)) {
                    try {
                        $numbering = LivestockNumberGeneratorService::generateNumber('livestocks', $purchase->tanggal ?? now(), []);
                        $livestock->number = $numbering['number'];
                        $livestock->number_full = $numbering['full_number'];
                        $livestock->save();

                        Log::info('Generated automatic numbering for Livestock', [
                            'livestock_id' => $livestock->id,
                            'number' => $livestock->number,
                            'number_full' => $livestock->number_full,
                            'date' => $purchase->tanggal
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to generate automatic numbering for Livestock', [
                            'livestock_id' => $livestock->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue without numbering - not critical
                    }
                }

                Log::info('Created new Livestock (will be updated after batch creation):', [
                    'livestock_id' => $livestock->id
                ]);
            }

            // --- VALIDASI JUMLAH BATCH EXISTING SEBELUM CREATE ---
            $batchCreation = $livestockConfig['batch_creation'] ?? [];
            $batchNamingFormat = $batchCreation['batch_naming_format'] ?? 'PR-{FARM}-{COOP}-{DATE}-{IDX}';

            $existingBatchCount = 0;
            foreach ($purchase->details as $idx => $item) {
                $periodeFormat = str_replace(
                    ['{FARM}', '{COOP}', '{DATE}', '{IDX}'],
                    [$farm->code ?? $farm->name, $kandang->code ?? $kandang->name, \Carbon\Carbon::parse($purchase->date)->format('dmY'), $idx + 1],
                    $batchNamingFormat
                );
                $periode = !empty($this->batch_name) ? $this->batch_name . '-' . ($idx + 1) : $periodeFormat;

                $existingBatch = \App\Models\LivestockBatch::where([
                    'name' => $periode,
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'start_date' => $purchase->tanggal,
                ])->first();

                if ($existingBatch) {
                    $existingBatchCount++;
                    // tetap update item->livestock_id
                    $item->update(['livestock_id' => $livestock->id]);
                }
            }

            $finalBatchCount = $existingBatchCount;
            $toCreateBatchCount = $expectedBatchCount - $existingBatchCount;

            if ($finalBatchCount + $toCreateBatchCount !== $expectedBatchCount) {
                Log::error('Jumlah batch yang dibuat tidak sesuai dengan jumlah LivestockPurchaseItem.', [
                    'createdBatchCount' => $finalBatchCount + $toCreateBatchCount,
                    'expectedBatchCount' => $expectedBatchCount,
                ]);
                DB::rollBack();
                throw new \Exception('Jumlah batch yang dibuat tidak sesuai dengan jumlah item.');
            }

            // --- CREATE BATCHES SETELAH SEMUA VALIDASI ---
            $actualCreatedBatches = 0;
            foreach ($purchase->details as $idx => $item) {
                Log::info('Processing purchase item for batch creation:', [
                    'item_id' => $item->id,
                    'idx' => $idx,
                    'item_data' => $item->toArray()
                ]);

                $itemData = $item->data ?? [];
                $batchData = $itemData['batch'] ?? null;

                $periodeFormat = str_replace(
                    ['{FARM}', '{COOP}', '{DATE}', '{IDX}'],
                    [$farm->code ?? $farm->name, $kandang->code ?? $kandang->name, \Carbon\Carbon::parse($purchase->tanggal)->format('dmY'), $idx + 1],
                    $batchNamingFormat
                );
                // Tambahkan penanda -0x di setiap batch
                $batchSuffix = sprintf('-0%02d', $idx + 1);
                $periode = (!empty($this->batch_name) ? $this->batch_name : $periodeFormat) . $batchSuffix;

                Log::info('Generated batch name:', [
                    'periode' => $periode,
                    'batch_suffix' => $batchSuffix,
                    'batch_name' => $this->batch_name
                ]);

                // Check existing batch dengan kriteria yang lebih spesifik
                $existingBatch = \App\Models\LivestockBatch::where([
                    'name' => $periode,
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                ])->first();

                Log::info('Checking existing batch:', [
                    'existing_batch_found' => $existingBatch ? true : false,
                    'existing_batch_id' => $existingBatch ? $existingBatch->id : null
                ]);

                if ($existingBatch) {
                    Log::info('Skipping batch creation - already exists:', [
                        'batch_id' => $existingBatch->id,
                        'batch_name' => $existingBatch->name
                    ]);
                    continue;
                }

                $strain = null;
                $strainStandard = null;
                if ($batchData) {
                    $strain = LivestockStrain::find($batchData['livestock_strain_id']);
                    if (isset($batchData['livestock_strain_standard_id'])) {
                        $strainStandard = LivestockStrainStandard::find($batchData['livestock_strain_standard_id']);
                    }
                }

                $quantity = $itemData['quantity'] ?? $item->quantity;
                $weight = $itemData['weight_total'] ?? $item->weight_total;
                $weight_value = $itemData['weight_value'] ?? $item->weight_value;
                $weight_type = $itemData['weight_type'] ?? $item->weight_type;
                $price = $itemData['price_total'] ?? $item->price_total;
                $price_value = $itemData['price_value'] ?? $item->price_value;
                $price_type = $itemData['price_type'] ?? $item->price_type;

                // Calculate price values dengan debugging
                $pricePerUnit = $quantity > 0 ? $price / $quantity : 0;
                $priceTotal = $price;

                Log::info('Creating new LivestockBatch with data:', [
                    'periode' => $periode,
                    'item_id' => $item->id,
                    'livestock_id' => $livestock->id,
                    'quantity' => $quantity,
                    'weight' => $weight,
                    'price' => $price,
                    'calculated_price_per_unit' => $pricePerUnit,
                    'calculated_price_total' => $priceTotal,
                    'price_value' => $price_value,
                    'price_type' => $price_type
                ]);

                $batchData = [
                    'name' => $periode,
                    'livestock_purchase_item_id' => $item->id,
                    'livestock_id' => $livestock->id,
                    'livestock_strain_id' => $strain ? $strain->id : null,
                    'livestock_strain_name' => $strain ? $strain->name : null,
                    'livestock_strain_standard_id' => $strainStandard ? $strainStandard->id : null,
                    'start_date' => $purchase->tanggal,
                    'source_type' => 'purchase',
                    'source_id' => $purchase->id,
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'initial_quantity' => $quantity,
                    'initial_weight' => $quantity > 0 ? $weight / $quantity : 0,
                    'weight' => $quantity > 0 ? $weight / $quantity : 0,
                    'weight_per_unit' => $quantity > 0 ? $weight / $quantity : 0,
                    'weight_total' => $weight,
                    'weight_type' => $weight_type,
                    'weight_value' => $weight_value,
                    'price_per_unit' => $pricePerUnit,
                    'price_total' => $priceTotal,
                    'price_type' => $price_type,
                    'price_value' => $price_value,
                    'status' => 'active',
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ];

                Log::info('Debug: Batch data to be saved:', [
                    'batch_data' => $batchData
                ]);

                $batch = \App\Models\LivestockBatch::create($batchData);

                // Generate automatic numbering for livestock batch
                if (empty($batch->number) || empty($batch->number_full)) {
                    try {
                        $numbering = LivestockNumberGeneratorService::generateNumber('livestock_batches', $purchase->tanggal ?? now(), []);
                        $batch->number = $numbering['number'];
                        $batch->number_full = $numbering['full_number'];
                        $batch->save();

                        Log::info('Generated automatic numbering for LivestockBatch', [
                            'batch_id' => $batch->id,
                            'number' => $batch->number,
                            'number_full' => $batch->number_full,
                            'date' => $purchase->tanggal
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to generate automatic numbering for LivestockBatch', [
                            'batch_id' => $batch->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue without numbering - not critical
                    }
                }

                $actualCreatedBatches++;
                Log::info('Successfully created LivestockBatch:', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'created_count' => $actualCreatedBatches
                ]);

                // Update livestock_id pada item
                $item->update([
                    'livestock_id' => $livestock->id,
                ]);

                Log::info('Updated purchase item with livestock_id:', [
                    'item_id' => $item->id,
                    'livestock_id' => $livestock->id
                ]);
            }

            Log::info('Batch creation completed:', [
                'expected_batches' => $expectedBatchCount,
                'actual_created_batches' => $actualCreatedBatches,
                'creation_successful' => $actualCreatedBatches === $expectedBatchCount
            ]);

            // Validasi final: pastikan batch benar-benar ter-create
            if ($actualCreatedBatches === 0) {
                Log::error('No batches were created, rolling back transaction', [
                    'purchase_id' => $purchaseId,
                    'expected_batches' => $expectedBatchCount,
                    'actual_created_batches' => $actualCreatedBatches
                ]);
                DB::rollBack();
                throw new \Exception('Tidak ada batch yang ter-create. Proses dibatalkan.');
            }

            if ($actualCreatedBatches !== $expectedBatchCount) {
                Log::warning('Batch creation count mismatch but continuing', [
                    'expected_batches' => $expectedBatchCount,
                    'actual_created_batches' => $actualCreatedBatches
                ]);
            }

            // Update Livestock dengan nilai total dari semua LivestockPurchaseItem yang terkait
            $allPurchaseItemsForLivestock = \App\Models\LivestockPurchaseItem::where([
                'livestock_id' => $livestock->id,
            ])->get();

            Log::info('Debug: Purchase items found for Livestock aggregation:', [
                'livestock_id' => $livestock->id,
                'purchase_item_count' => $allPurchaseItemsForLivestock->count(),
                'purchase_items' => $allPurchaseItemsForLivestock->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'weight_total' => $item->weight_total,
                        'price_total' => $item->price_total,
                        'price_per_unit' => $item->price_per_unit,
                    ];
                })->toArray()
            ]);

            $totalQuantity = $allPurchaseItemsForLivestock->sum('quantity');
            $totalWeightValue = $allPurchaseItemsForLivestock->sum('weight_total');
            $totalPriceValue = $allPurchaseItemsForLivestock->sum('price_total');

            // Calculate weighted averages
            $avgWeight = $totalQuantity > 0 ? $totalWeightValue / $totalQuantity : 0;
            $avgPrice = $totalQuantity > 0 ? $totalPriceValue / $totalQuantity : 0;

            Log::info('Debug: Using LivestockPurchaseItem as source for price calculation:', [
                'total_quantity' => $totalQuantity,
                'total_weight_value' => $totalWeightValue,
                'total_price_value' => $totalPriceValue,
                'avg_weight' => $avgWeight,
                'avg_price' => $avgPrice
            ]);

            Log::info('Debug: Calculated values before Livestock update:', [
                'total_quantity' => $totalQuantity,
                'total_weight_value' => $totalWeightValue,
                'total_price_value' => $totalPriceValue,
                'avg_weight' => $avgWeight,
                'avg_price' => $avgPrice,
                'calculation_method' => $avgPrice <= 0 ? 'alternative_per_unit' : 'weighted_average'
            ]);

            // Update Livestock dengan nilai yang benar
            $updateResult = $livestock->update([
                'initial_quantity' => $totalQuantity,
                'initial_weight' => $avgWeight,
                'price' => $avgPrice,
                'updated_by' => Auth::user()->id,
            ]);

            // Refresh model untuk memastikan data ter-update
            $livestock->refresh();

            // Validasi update berhasil, khususnya untuk price
            if ($livestock->price <= 0 && $avgPrice > 0) {
                Log::warning('Price update failed, attempting direct update:', [
                    'livestock_id' => $livestock->id,
                    'expected_price' => $avgPrice,
                    'actual_price' => $livestock->price
                ]);

                // Try direct database update
                DB::table('livestocks')
                    ->where('id', $livestock->id)
                    ->update([
                        'initial_quantity' => $totalQuantity,
                        'initial_weight' => $avgWeight,
                        'price' => $avgPrice,
                        'updated_by' => Auth::user()->id,
                        'updated_at' => now()
                    ]);

                $livestock->refresh();

                Log::info('Direct database update completed:', [
                    'livestock_id' => $livestock->id,
                    'final_price' => $livestock->price
                ]);
            }

            Log::info('Updated Livestock with totals from batches:', [
                'livestock_id' => $livestock->id,
                'update_success' => $updateResult,
                'old_values' => [
                    'initial_quantity' => $livestock->getOriginal('initial_quantity'),
                    'initial_weight' => $livestock->getOriginal('initial_weight'),
                    'price' => $livestock->getOriginal('price')
                ],
                'new_values' => [
                    'initial_quantity' => $livestock->initial_quantity,
                    'initial_weight' => $livestock->initial_weight,
                    'price' => $livestock->price
                ],
                'batch_count' => $allPurchaseItemsForLivestock->count(),
                'price_update_success' => $livestock->price > 0
            ]);

            // Update coop (jumlah total populasi dan berat diupdate dengan sum dari batch)
            $totalQuantityForCoop = \App\Models\LivestockBatch::where([
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'start_date' => $purchase->tanggal,
                'livestock_id' => $livestock->id,
                'source_type' => 'purchase',
                'source_id' => $purchase->id,
            ])->sum('initial_quantity');
            $totalWeightForCoop = \App\Models\LivestockBatch::where([
                'farm_id' => $farm->id,
                'coop_id' => $kandang->id,
                'start_date' => $purchase->tanggal,
                'livestock_id' => $livestock->id,
                'source_type' => 'purchase',
                'source_id' => $purchase->id,
            ])->sum('weight_total');

            $kandang->update([
                'quantity' => $kandang->quantity + $totalQuantityForCoop,
                'status' => 'in_use',
                'livestock_id' => $livestock->id,
                'weight' => $kandang->weight + $totalWeightForCoop,
            ]);

            Log::info('Updated Coop with new livestock data:', [
                'coop_id' => $kandang->id,
                'added_quantity' => $totalQuantityForCoop,
                'added_weight' => $totalWeightForCoop,
                'new_total_quantity' => $kandang->quantity,
                'new_total_weight' => $kandang->weight
            ]);

            // Update CurrentLivestock
            $currentLivestock = $this->updateCurrentLivestock($farm, $kandang, $livestock);
            if (!$currentLivestock) {
                Log::error('Gagal updateCurrentLivestock, melakukan rollback.', [
                    'farm_id' => $farm->id,
                    'coop_id' => $kandang->id,
                    'livestock_id' => $livestock->id,
                ]);
                DB::rollBack();
                throw new \Exception('Gagal updateCurrentLivestock, semua proses dibatalkan.');
            }

            DB::commit();
            Log::info('Finished generateLivestockAndBatch for purchase ID: ' . $purchaseId, [
                'createdBatchCount' => $actualCreatedBatches,
                'expectedBatchCount' => $expectedBatchCount,
                'success' => true,
                'livestock_info' => [
                    'livestock_id' => $livestock->id,
                    'livestock_name' => $livestock->name,
                    'number' => $livestock->number,
                    'number_full' => $livestock->number_full,
                    'final_initial_quantity' => $livestock->initial_quantity,
                    'final_initial_weight' => $livestock->initial_weight,
                    'final_price' => $livestock->price
                ],
                'numbering_status' => [
                    'has_number' => !empty($livestock->number),
                    'has_number_full' => !empty($livestock->number_full),
                    'numbering_generated' => !empty($livestock->number) && !empty($livestock->number_full)
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in generateLivestockAndBatch, rollback all changes.', [
                'purchase_id' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'livestock_info' => isset($livestock) ? [
                    'livestock_id' => $livestock->id ?? null,
                    'livestock_name' => $livestock->name ?? null,
                    'number' => $livestock->number ?? null,
                    'number_full' => $livestock->number_full ?? null,
                    'numbering_status' => [
                        'has_number' => !empty($livestock->number ?? null),
                        'has_number_full' => !empty($livestock->number_full ?? null)
                    ]
                ] : null,
                'rollback_reason' => 'Error during livestock and batch generation process'
            ]);
            throw $e;
        }
    }

    /**
     * Validate recording method configuration
     */
    private function validateRecordingMethodConfig(array $config): array
    {
        $errors = [];

        // Check if recording method type is set
        if (empty($config['type'])) {
            $errors[] = 'Recording method type is not configured';
        }

        // Check if batch settings are configured when using batch method
        if (($config['type'] ?? '') === 'batch') {
            if (empty($config['batch_settings'])) {
                $errors[] = 'Batch settings are not configured for batch recording method';
            } else {
                $batchSettings = $config['batch_settings'];

                // Check if depletion method is configured
                if (empty($batchSettings['depletion_method'])) {
                    $errors[] = 'Depletion method is not configured for batch recording';
                }

                // Check if depletion methods are configured
                if (empty($batchSettings['depletion_methods'])) {
                    $errors[] = 'Depletion methods configuration is missing';
                } else {
                    $depletionMethod = $batchSettings['depletion_method'] ?? 'fifo';
                    if (!isset($batchSettings['depletion_methods'][$depletionMethod])) {
                        $errors[] = "Configured depletion method '{$depletionMethod}' is not available";
                    }
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Update livestock totals based on all active batches
     */
    private function updateLivestockTotals($livestock, $farm, $kandang)
    {
        $allBatches = \App\Models\LivestockBatch::where([
            'livestock_id' => $livestock->id,
            'farm_id' => $farm->id,
            'coop_id' => $kandang->id,
            'status' => 'active'
        ])->get();

        $totalQuantity = $allBatches->sum('populasi_awal');
        $totalWeight = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->berat_awal;
        });
        $avgWeight = $totalQuantity > 0 ? $totalWeight / $totalQuantity : 0;
        $totalPrice = $allBatches->sum(function ($batch) {
            return $batch->populasi_awal * $batch->harga;
        });
        $avgPrice = $totalQuantity > 0 ? $totalPrice / $totalQuantity : 0;

        $livestock->update([
            'initial_quantity' => $totalQuantity,
            'initial_weight' => $avgWeight,
            'price' => $avgPrice,
            'updated_by' => Auth::user()->id,
        ]);

        Log::info('Updated livestock totals from batches:', [
            'livestock_id' => $livestock->id,
            'total_quantity' => $totalQuantity,
            'avg_weight' => $avgWeight,
            'avg_price' => $avgPrice,
            'batch_count' => $allBatches->count()
        ]);
    }

    /**
     * Handle real-time status change notifications from broadcasting
     */
    public function handleStatusChanged($event)
    {
        Log::info('Received real-time livestock purchase status change notification', [
            'batch_id' => $event['batch_id'] ?? 'unknown',
            'old_status' => $event['old_status'] ?? 'unknown',
            'new_status' => $event['new_status'] ?? 'unknown',
            'updated_by' => $event['updated_by'] ?? 'unknown',
            'current_user' => Auth::user()->id
        ]);

        try {
            // Check if this change affects current user's view
            if ($this->shouldRefreshData($event)) {
                $this->dispatch('notify-status-change', [
                    'type' => 'info',
                    'title' => 'Data Update Available',
                    'message' => $event['message'] ?? 'A livestock purchase status has been updated.',
                    'requires_refresh' => $event['metadata']['requires_refresh'] ?? false,
                    'priority' => $event['metadata']['priority'] ?? 'normal',
                    'batch_id' => $event['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);

                Log::info('Livestock purchase status change notification dispatched to user', [
                    'batch_id' => $event['batch_id'] ?? 'unknown',
                    'user_id' => Auth::user()->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling livestock purchase status change notification', [
                'error' => $e->getMessage(),
                'event' => $event,
                'user_id' => Auth::user()->id
            ]);
        }
    }

    /**
     * Handle user-specific notifications
     */
    public function handleUserNotification($notification)
    {
        Log::info('Received user-specific livestock purchase notification', [
            'notification_type' => $notification['type'] ?? 'unknown',
            'user_id' => Auth::user()->id
        ]);

        try {
            if (isset($notification['type']) && $notification['type'] === 'livestock_purchase_status_changed') {
                $this->dispatch('notify-status-change', [
                    'type' => $this->getNotificationType($notification['priority'] ?? 'normal'),
                    'title' => $notification['title'] ?? 'Livestock Purchase Update',
                    'message' => $notification['message'] ?? 'A livestock purchase has been updated.',
                    'requires_refresh' => in_array('refresh_data', $notification['action_required'] ?? []),
                    'priority' => $notification['priority'] ?? 'normal',
                    'batch_id' => $notification['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling livestock purchase user notification', [
                'error' => $e->getMessage(),
                'notification' => $notification,
                'user_id' => Auth::user()->id
            ]);
        }
    }

    /**
     * Determine if data should be refreshed based on event
     */
    private function shouldRefreshData($event): bool
    {
        // Always show notifications for high priority changes
        if (($event['metadata']['priority'] ?? 'normal') === 'high') {
            return true;
        }

        // Show if refresh is explicitly required
        if ($event['metadata']['requires_refresh'] ?? false) {
            return true;
        }

        // Show for current user's related batches (if we're in edit mode)
        if ($this->pembelianId && isset($event['batch_id']) && $event['batch_id'] == $this->pembelianId) {
            return true;
        }

        return false;
    }

    /**
     * Get notification type based on priority
     */
    private function getNotificationType(string $priority): string
    {
        return match ($priority) {
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'info'
        };
    }

    /**
     * Get notification type based on status
     */
    private function getNotificationTypeForStatus(string $status): string
    {
        switch ($status) {
            case 'in_coop':
                return 'success';
            case 'cancelled':
                return 'warning';
            case 'completed':
                return 'info';
            default:
                return 'info';
        }
    }

    /**
     * Get status change message
     */
    private function getStatusChangeMessage($purchase, $oldStatus, $newStatus): string
    {
        $statusLabels = \App\Models\LivestockPurchase::STATUS_LABELS;
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        return sprintf(
            'Livestock Purchase #%s status changed from %s to %s by %s',
            $purchase->invoice_number,
            $oldLabel,
            $newLabel,
            Auth::user()->name
        );
    }

    /**
     * Check if status change requires page refresh
     */
    private function requiresRefresh(string $oldStatus, string $newStatus): bool
    {
        $criticalChanges = [
            'draft' => ['in_coop', 'confirmed'],
            'confirmed' => ['in_coop', 'cancelled'],
            'in_coop' => ['completed', 'cancelled'],
            'pending' => ['in_coop', 'cancelled'],
        ];

        return isset($criticalChanges[$oldStatus]) &&
            in_array($newStatus, $criticalChanges[$oldStatus]);
    }

    /**
     * Get notification priority
     */
    private function getPriority(string $oldStatus, string $newStatus): string
    {
        if ($newStatus === 'in_coop') return 'high';
        if ($newStatus === 'cancelled') return 'medium';
        if ($newStatus === 'completed') return 'low';
        return 'normal';
    }

    /**
     * Send notification to SSE bridge with debounce mechanism
     */
    private function sendToSSENotificationBridge($notificationData, $purchase)
    {
        try {
            // Debounce mechanism: prevent duplicate notifications for same batch within 2 seconds
            $cacheKey = "sse_notification_debounce_livestock_{$purchase->id}_{$notificationData['new_status']}";

            if (Cache::has($cacheKey)) {
                Log::info('SSE livestock purchase notification debounced (too frequent)', [
                    'batch_id' => $purchase->id,
                    'status' => $notificationData['new_status'],
                    'cache_key' => $cacheKey
                ]);
                return;
            }

            // Set debounce cache for 2 seconds
            Cache::put($cacheKey, true, 2);

            // Prepare notification data for SSE storage
            $sseNotification = [
                'type' => 'livestock_purchase_status_changed',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'source' => 'livewire_sse',
                'priority' => $notificationData['priority'] ?? 'normal',
                'data' => [
                    'batch_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
                    'updated_by' => Auth::user()->id,
                    'updated_by_name' => Auth::user()->name,
                    'old_status' => $notificationData['old_status'],
                    'new_status' => $notificationData['new_status'],
                    'timestamp' => $notificationData['timestamp'],
                    'requires_refresh' => $notificationData['requires_refresh']
                ],
                'requires_refresh' => $notificationData['requires_refresh'],
                'timestamp' => time(),
                'debounce_key' => $cacheKey
            ];

            // Store notification for SSE clients (with retry mechanism)
            $result = $this->storeSSENotification($sseNotification);

            if ($result) {
                Log::info('Successfully stored livestock purchase notification for SSE bridge', [
                    'batch_id' => $purchase->id,
                    'notification_id' => $result['id'],
                    'updated_by' => Auth::user()->id,
                    'sse_system' => 'active'
                ]);
            } else {
                Log::warning('Failed to store SSE livestock purchase notification after retries', [
                    'batch_id' => $purchase->id,
                    'updated_by' => Auth::user()->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing livestock purchase notification for SSE bridge', [
                'batch_id' => $purchase->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Store notification for SSE clients with file locking and retry mechanism
     */
    private function storeSSENotification($notification)
    {
        $filePath = base_path('testing/sse-notifications.json');
        $maxRetries = 3;
        $retryDelay = 100000; // 100ms in microseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Create lock file to prevent race conditions
                $lockFile = $filePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');

                if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
                    if ($lockHandle) fclose($lockHandle);

                    // If this is not the last attempt, wait and retry
                    if ($attempt < $maxRetries) {
                        usleep($retryDelay * $attempt); // Exponential backoff
                        continue;
                    }

                    Log::warning('Could not acquire file lock for SSE livestock purchase notification', [
                        'attempt' => $attempt,
                        'file' => $filePath
                    ]);
                    return null;
                }

                // Initialize file if not exists
                if (!file_exists($filePath)) {
                    file_put_contents($filePath, json_encode([
                        'notifications' => [],
                        'last_update' => time(),
                        'stats' => [
                            'total_sent' => 0,
                            'clients_connected' => 0
                        ]
                    ]));
                }

                // Read existing data
                $content = file_get_contents($filePath);
                $data = $content ? json_decode($content, true) : [
                    'notifications' => [],
                    'last_update' => time(),
                    'stats' => ['total_sent' => 0, 'clients_connected' => 0]
                ];

                // Prepare notification with unique ID and timestamp
                $notification['id'] = uniqid() . '_' . microtime(true);
                $notification['timestamp'] = time();
                $notification['datetime'] = date('Y-m-d H:i:s');
                $notification['microseconds'] = microtime(true);

                // Add to beginning of array (newest first)
                array_unshift($data['notifications'], $notification);

                // Keep only last 50 notifications (reduced from 100 for performance)
                $data['notifications'] = array_slice($data['notifications'], 0, 50);

                $data['last_update'] = time();
                $data['stats']['total_sent']++;

                // Write atomically using temporary file
                $tempFile = $filePath . '.tmp';
                if (file_put_contents($tempFile, json_encode($data, JSON_PRETTY_PRINT)) !== false) {
                    rename($tempFile, $filePath);
                }

                // Release lock
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                unlink($lockFile);

                Log::info('SSE livestock purchase notification stored successfully', [
                    'notification_id' => $notification['id'],
                    'attempt' => $attempt,
                    'total_notifications' => count($data['notifications'])
                ]);

                return $notification;
            } catch (\Exception $e) {
                // Clean up lock if it exists
                if (isset($lockHandle) && $lockHandle) {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                }
                if (isset($lockFile) && file_exists($lockFile)) {
                    unlink($lockFile);
                }

                Log::error('Error storing SSE livestock purchase notification', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                // If this is not the last attempt, wait and retry
                if ($attempt < $maxRetries) {
                    usleep($retryDelay * $attempt);
                    continue;
                }

                // Last attempt failed
                return null;
            }
        }

        return null;
    }

    /**
     * Get notification bridge URL based on environment
     */
    private function getBridgeUrl()
    {
        // Check if we're in a web context
        if (request()->server('HTTP_HOST')) {
            $baseUrl = request()->getSchemeAndHttpHost();
            $bridgeUrl = $baseUrl . '/testing/notification_bridge.php';

            // Test if bridge is available
            try {
                $testResponse = Http::timeout(2)->get($bridgeUrl . '?action=status');
                if ($testResponse->successful()) {
                    $data = $testResponse->json();
                    if ($data['success'] ?? false) {
                        return $bridgeUrl;
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Bridge test failed', ['error' => $e->getMessage()]);
            }
        }

        return null; // Bridge not available
    }

    /**
     * Check if purchase can be modified
     */
    public function canModify($purchaseId)
    {
        $purchase = LivestockPurchase::findOrFail($purchaseId);
        $verificationService = app(VerificationService::class);
        return $verificationService->canModify($purchase);
    }

    /**
     * Get verification status
     */
    public function getVerificationStatus($purchaseId)
    {
        $purchase = LivestockPurchase::findOrFail($purchaseId);
        $verificationService = app(VerificationService::class);
        return $verificationService->getStatus($purchase);
    }

    /**
     * Generate automatic numbering for livestock purchase records
     * 
     * @param string $table Table name (livestock_purchases, livestocks, livestock_batches)
     * @param string $date Date for numbering context
     * @param array $context Additional context for placeholders
     * @return array|null Generated numbering data or null if failed
     */
    private function generateAutomaticNumbering(string $table, string $date, array $context = []): ?array
    {
        try {
            $numbering = LivestockNumberGeneratorService::generateNumber($table, $date, $context);

            Log::info("Generated automatic numbering for {$table}", [
                'table' => $table,
                'date' => $date,
                'number' => $numbering['number'],
                'number_full' => $numbering['full_number'],
                'context' => $context
            ]);

            return $numbering;
        } catch (\Exception $e) {
            Log::warning("Failed to generate automatic numbering for {$table}", [
                'table' => $table,
                'date' => $date,
                'error' => $e->getMessage(),
                'context' => $context
            ]);

            return null;
        }
    }

    /**
     * Apply automatic numbering to a model
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model to apply numbering to
     * @param string $table Table name for numbering context
     * @param string $date Date for numbering context
     * @param array $context Additional context for placeholders
     * @return bool Success status
     */
    private function applyAutomaticNumbering($model, string $table, string $date, array $context = []): bool
    {
        if (empty($model->number) || empty($model->number_full)) {
            $numbering = $this->generateAutomaticNumbering($table, $date, $context);

            if ($numbering) {
                $model->number = $numbering['number'];
                $model->number_full = $numbering['full_number'];
                $model->save();

                Log::info("Applied automatic numbering to {$table}", [
                    'model_id' => $model->id,
                    'number' => $model->number,
                    'number_full' => $model->number_full
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Trigger kalkulasi otomatis saat expedition_fee berubah
     */
    public function updatedExpeditionFee($value)
    {
        // Trigger re-render untuk update ringkasan
        $this->dispatch('expedition-fee-updated');
    }

    /**
     * Method khusus untuk update quantity dengan kalkulasi otomatis
     */
    public function updatedItemsQuantity($value, $key)
    {
        [$index] = explode('.', $key);
        $this->items[$index]['quantity'] = $value;
        $this->calculateItemSubTotal($index);
        $this->dispatch('item-updated', index: $index);
    }

    /**
     * Method khusus untuk update price dengan kalkulasi otomatis
     */
    public function updatedItemsPriceValue($value, $key)
    {
        [$index] = explode('.', $key);
        $this->items[$index]['price_value'] = $value;
        $this->calculateItemSubTotal($index);
        $this->dispatch('item-updated', index: $index);
    }

    /**
     * Method khusus untuk update price type dengan kalkulasi otomatis
     */
    public function updatedItemsPriceType($value, $key)
    {
        [$index] = explode('.', $key);
        $this->items[$index]['price_type'] = $value;
        $this->calculateItemSubTotal($index);
        $this->dispatch('item-updated', index: $index);
    }

    /**
     * Method untuk force update kalkulasi semua item
     */
    public function recalculateAllItems()
    {
        foreach ($this->items as $index => $item) {
            $this->calculateItemSubTotal($index);
        }
        $this->dispatch('all-items-recalculated');
    }

    /**
     * Method untuk handle event recalculate-totals dari JavaScript
     */
    public function recalculateTotals()
    {
        $this->recalculateAllItems();
        $this->dispatch('totals-recalculated');
    }

    /**
     * Check if current status allows updates
     * Some statuses should not be changed when updating expedition details
     */
    public function canUpdateStatus($currentStatus)
    {
        // Status yang diizinkan untuk diupdate (tidak berubah saat save)
        $allowedStatuses = ['draft', 'confirmed', 'in_transit', 'arrived'];

        return in_array($currentStatus, $allowedStatuses);
    }

    /**
     * Get final status for save operation
     * Preserve status for edit mode, use draft for create mode
     */
    private function getFinalStatusForSave()
    {
        if ($this->edit_mode && $this->pembelianId) {
            // Untuk edit mode, pertahankan status yang ada
            $existingPurchase = LivestockPurchase::find($this->pembelianId);
            if ($existingPurchase && $this->canUpdateStatus($existingPurchase->status)) {
                return $existingPurchase->status;
            }
            // Jika status tidak diizinkan untuk diupdate, kembalikan status asli
            return $this->status ?? LivestockPurchase::STATUS_DRAFT;
        } else {
            // Untuk create mode, gunakan status draft
            return LivestockPurchase::STATUS_DRAFT;
        }
    }

    /**
     * Check if status transition is allowed
     * Prevent invalid status transitions based on business rules
     */
    public function canTransitionToStatus($currentStatus, $newStatus): bool
    {
        // Define allowed status transitions
        $allowedTransitions = [
            'draft' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_transit', 'arrived', 'cancelled'], // Allow direct transition to arrived
            'in_transit' => ['arrived', 'cancelled'],
            'arrived' => ['in_coop', 'completed', 'cancelled'],
            'in_coop' => ['completed', 'cancelled'],
            'completed' => ['cancelled'], // Completed can only be cancelled
            'cancelled' => [] // Cancelled is final state
        ];

        // Check if transition is allowed
        if (isset($allowedTransitions[$currentStatus])) {
            return in_array($newStatus, $allowedTransitions[$currentStatus]);
        }

        return false;
    }

    /**
     * Validate status transition with business rules
     */
    private function validateStatusTransition($purchase, $newStatus): array
    {
        $errors = [];
        $currentStatus = $purchase->status;

        // Log the validation attempt
        Log::info('validateStatusTransition: Starting validation', [
            'purchase_id' => $purchase->id,
            'current_status' => $currentStatus,
            'new_status' => $newStatus,
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name
        ]);

        // Check if transition is allowed
        if (!$this->canTransitionToStatus($currentStatus, $newStatus)) {
            $errors[] = "Tidak bisa mengubah status dari '{$currentStatus}' ke '{$newStatus}'";

            // Log detailed transition info for debugging
            Log::warning('validateStatusTransition: Invalid transition detected', [
                'purchase_id' => $purchase->id,
                'current_status' => $currentStatus,
                'new_status' => $newStatus,
                'allowed_transitions' => $this->getAllowedTransitions($currentStatus),
                'user_id' => Auth::user()->id
            ]);
        }

        // Special validation for completed status
        if ($newStatus === 'completed' || $newStatus === 'complete') {
            $completedErrors = $this->validateStatusForCompleted($purchase);
            $errors = array_merge($errors, $completedErrors);
        }

        Log::info('validateStatusTransition: Validation result', [
            'purchase_id' => $purchase->id,
            'current_status' => $currentStatus,
            'new_status' => $newStatus,
            'can_transition' => $this->canTransitionToStatus($currentStatus, $newStatus),
            'errors' => $errors,
            'validation_passed' => empty($errors)
        ]);

        return $errors;
    }

    /**
     * Get allowed transitions for a given status (for debugging)
     */
    private function getAllowedTransitions($currentStatus): array
    {
        $allowedTransitions = [
            'draft' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_transit', 'arrived', 'cancelled'],
            'in_transit' => ['arrived', 'cancelled'],
            'arrived' => ['in_coop', 'completed', 'cancelled'],
            'in_coop' => ['completed', 'cancelled'],
            'completed' => ['cancelled'],
            'cancelled' => []
        ];

        return $allowedTransitions[$currentStatus] ?? [];
    }

    /**
     * Check if form can be saved without items (for draft status)
     */
    public function canSaveWithoutItems()
    {
        $currentStatus = $this->status ?? 'draft';

        // Untuk draft status, bisa disimpan tanpa items
        if ($currentStatus === 'draft') {
            return true;
        }

        // Untuk status lain, harus ada items
        return !empty($this->items) && count($this->items) > 0;
    }

    /**
     * Check if items are required for current status
     */
    public function isItemsRequired()
    {
        $currentStatus = $this->status ?? 'draft';

        // Items hanya wajib untuk status selain draft
        return $currentStatus !== 'draft';
    }

    /**
     * Debug method to check loaded data
     */
    public function debugLoadedData()
    {
        Log::info('debugLoadedData: Current form state', [
            'edit_mode' => $this->edit_mode,
            'pembelian_id' => $this->pembelianId,
            'date' => $this->date,
            'invoice_number' => $this->invoice_number,
            'supplier_id' => $this->supplier_id,
            'farm_id' => $this->farm_id,
            'coop_id' => $this->coop_id,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
            'batch_name' => $this->batch_name,
            'status' => $this->status,
            'notes' => $this->notes,
            'items_count' => count($this->items),
            'items' => $this->items,
            'user_id' => Auth::user()->id
        ]);
    }
}
