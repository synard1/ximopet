<?php

namespace App\Livewire\FeedPurchases;

use App\Models\CurrentFeed;
use App\Services\AuditTrailService;

use App\Models\CurrentSupply;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Feed;
use App\Models\FeedPurchase;
use App\Models\FeedPurchaseItem;
use App\Models\FeedStock;
use App\Models\Rekanan;
use App\Models\Partner;
use App\Models\Supply;
use App\Models\Item;
use App\Models\Livestock;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Traits\HasTempAuthorization;
use Illuminate\Support\Facades\Auth;
use App\Services\Feed\FeedNumberingService;
use App\Services\Feed\CurrentFeedService;
use App\Services\ExpeditionService;

class Create extends Component
{
    use WithFileUploads, HasTempAuthorization;

    public $invoice_number;
    public $date;
    public $supplier_id;
    public $expedition_id;
    public $expedition_fee = 0;
    public $items = [];
    public $livestock_id;
    public $pembelianId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;
    public $errorItems = [];
    public $status = null;


    public bool $withHistory = false; // ← Tambahkan ini di atas class Livewire

    protected ExpeditionService $expeditionService;

    protected $listeners = [
        'deleteFeedPurchaseBatch' => 'deleteFeedPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'updateStatusFeedPurchase' => 'updateStatusFeedPurchase',
        'echo:feed-purchases,status-changed' => 'handleStatusChanged',
        'status-validation-failed' => 'handleStatusValidationFailed',
        'status-processing-failed' => 'handleStatusProcessingFailed',
        'status-unexpected-error' => 'handleStatusUnexpectedError',
    ];

    public function getListeners()
    {
        return array_merge($this->listeners, [
            'echo-notification:App.Models.User.' . (Auth::check() ? Auth::id() : 'guest') => 'handleUserNotification',
        ]);
    }

    /**
     * @var CurrentFeedService|null
     */
    protected $currentFeedService = null;

    public function mount()
    {
        $this->currentFeedService = app(\App\Services\Feed\CurrentFeedService::class);
        // $this->date = now()->toDateString();
        $this->items = [
            [
                'feed_id' => null,
                'quantity' => null,
                'unit_id' => null, // ← changed from 'unit' to 'unit_id' for consistency
                'price_per_unit' => null,
                'available_units' => [], // ← new: daftar satuan berdasarkan feed
            ]
        ];
        // $this->items = [
        //     ['feed_id' => '', 'quantity' => '', 'price_per_unit' => '']
        // ];
    }

    public function boot(ExpeditionService $expeditionService)
    {
        $this->expeditionService = $expeditionService;
    }

    /**
     * Validate expedition input using ExpeditionService
     * 
     * @return array - Array of validation errors (empty if valid)
     */
    public function validateExpeditionInput(): array
    {
        $inputData = [
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee
        ];

        // Expedition tidak wajib untuk draft, tapi jika diisi harus valid
        $context = [
            'require_expedition' => $this->status === 'complete'
        ];

        return $this->expeditionService->validateLivewireInput($inputData, 'feed_purchase', $context);
    }

    /**
     * Create expedition transaction using ExpeditionService
     * 
     * @param string $transactionId - ID of the feed purchase transaction
     * @return bool - Success status
     */
    public function createExpeditionTransaction(string $transactionId): bool
    {
        Log::info('=== EXPEDITION TRANSACTION CREATION/UPDATE START ===', [
            'transaction_id' => $transactionId,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
            'expedition_id_type' => gettype($this->expedition_id),
            'expedition_fee_type' => gettype($this->expedition_fee),
            'user_company_id' => Auth::user()->company_id ?? 'NULL',
            'shipping_date' => $this->date,
            'notes' => null
        ]);

        try {
            // Validate input data before processing
            if (empty($this->expedition_id)) {
                Log::warning('Expedition ID is empty, cannot create expedition transaction', [
                    'transaction_id' => $transactionId
                ]);
                return false;
            }

            if (empty($this->expedition_fee) || $this->expedition_fee <= 0) {
                Log::warning('Expedition fee is invalid, cannot create expedition transaction', [
                    'transaction_id' => $transactionId,
                    'expedition_fee' => $this->expedition_fee
                ]);
                return false;
            }

            $inputData = [
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee
            ];

            $context = [
                'shipping_date' => $this->date,
                'company_id' => Auth::user()->company_id ?? null,
                'notes' => null,
                'total_weight' => $this->calculateTotalWeight()
            ];

            Log::info('Calling ExpeditionService::createFromLivewireInput', [
                'input_data' => $inputData,
                'transaction_type' => 'feed_purchase',
                'transaction_id' => $transactionId,
                'context' => $context
            ]);

            $expeditionTransaction = $this->expeditionService->createFromLivewireInput(
                $inputData,
                'feed_purchase',
                $transactionId,
                $context
            );

            if ($expeditionTransaction) {
                Log::info('=== EXPEDITION TRANSACTION CREATED/UPDATED SUCCESSFULLY ===', [
                    'feed_purchase_id' => $transactionId,
                    'expedition_transaction_id' => $expeditionTransaction->id,
                    'expedition_id' => $expeditionTransaction->expedition_id,
                    'expedition_cost' => $expeditionTransaction->expedition_cost,
                    'status' => $expeditionTransaction->status,
                    'action' => 'created_or_updated'
                ]);
                return true;
            }

            Log::warning('=== EXPEDITION TRANSACTION CREATION/UPDATE FAILED ===', [
                'feed_purchase_id' => $transactionId,
                'input_data' => $inputData,
                'context' => $context,
                'reason' => 'ExpeditionService::createFromLivewireInput returned null'
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('=== EXPEDITION TRANSACTION CREATION/UPDATE ERROR ===', [
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'feed_purchase_id' => $transactionId,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_timestamp' => now()
            ]);
            return false;
        }
    }

    /**
     * Get estimated expedition cost using ExpeditionService
     * 
     * @param string $expeditionId - Expedition ID
     * @param float $weight - Total weight
     * @param string $destinationZone - Destination zone
     * @return array|null - Estimated cost data or null if failed
     */
    public function getEstimatedExpeditionCost(string $expeditionId, float $weight, string $destinationZone = ''): ?array
    {
        try {
            $context = [
                'company_id' => Auth::user()->company_id ?? null,
                'transaction_type' => 'feed_purchase'
            ];

            return $this->expeditionService->getEstimatedCostForLivewire(
                $expeditionId,
                $weight,
                $destinationZone,
                $context
            );
        } catch (\Exception $e) {
            Log::error('Error getting estimated expedition cost', [
                'error' => $e->getMessage(),
                'expedition_id' => $expeditionId,
                'weight' => $weight
            ]);
            return null;
        }
    }

    /**
     * Calculate total weight from all items
     * 
     * @return float - Total weight
     */
    private function calculateTotalWeight(): float
    {
        $totalWeight = 0;
        foreach ($this->items as $item) {
            if (!empty($item['quantity']) && !empty($item['feed_id'])) {
                $feed = Feed::find($item['feed_id']);
                if ($feed && isset($feed->data['weight_per_unit'])) {
                    $totalWeight += ($item['quantity'] * $feed->data['weight_per_unit']);
                }
            }
        }
        return $totalWeight;
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

    public function addItem()
    {
        // $this->items[] = ['feed_id' => '', 'quantity' => '', 'price_per_unit' => ''];
        $this->items[] = [
            'feed_id' => null,
            'quantity' => null,
            'unit_id' => null, // ← changed from 'unit' to 'unit_id' for consistency
            'price_per_unit' => null,
            'available_units' => [], // ← new: daftar satuan berdasarkan feed
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }


    public function save()
    {
        // dd($this->all());
        $this->errorItems = [];

        // --- FAST PATH: BYPASS SAVE IF NO CHANGES ---
        $hasChanges = $this->checkDataChanges();
        if (!$hasChanges) {
            Log::info('🟡 FeedPurchases.save: No data changes detected, bypassing save.', [
                'pembelian_id' => $this->pembelianId,
                'user_id' => Auth::check() ? Auth::id() : null,
            ]);
            $this->dispatch('success', 'Tidak ada perubahan data. Proses simpan dibypass.');
            return;
        }

        // Debug: Log items before validation
        Log::debug('FeedPurchase Save: items before duplicate check', [
            'items' => $this->items,
            'pembelianId' => $this->pembelianId,
            'user_id' => Auth::check() ? Auth::id() : null,
        ]);

        // Filter out empty items (items without feed_id)
        $validItems = array_filter($this->items, function ($item) {
            return !empty($item['feed_id']);
        });

        // Allow saving drafts with no items, but validate if items exist
        if (!empty($validItems)) {
            // Validasi kombinasi feed_id dan unit_id tidak boleh duplikat hanya untuk item yang valid
            $uniqueKeys = [];
            foreach ($validItems as $idx => $item) {
                // Check if unit_id exists, if not skip duplicate validation for this item
                if (!isset($item['unit_id']) || empty($item['unit_id'])) {
                    $this->errorItems[$idx] = 'Satuan harus dipilih untuk pakan ini.';
                    continue;
                }

                $key = $item['feed_id'] . '-' . $item['unit_id'];
                if (in_array($key, $uniqueKeys)) {
                    $this->errorItems[$idx] = 'Jenis pakan dan satuan tidak boleh sama dengan baris lain.';
                    Log::warning('FeedPurchase Save: Duplicate feed_id-unit_id found', [
                        'index' => $idx,
                        'key' => $key,
                        'item' => $item,
                    ]);
                }
                $uniqueKeys[] = $key;
            }

            if (!empty($this->errorItems)) {
                Log::info('FeedPurchase Save: Validation error - duplicate feed_id-unit_id or missing unit_id', [
                    'errorItems' => $this->errorItems,
                ]);
                $this->dispatch('validation-errors', ['errors' => array_values($this->errorItems)]);
                return;
            }
        }

        Log::debug('FeedPurchase Save: Passed duplicate validation, start form validation', [
            'invoice_number' => $this->invoice_number,
            'date' => $this->date,
            'supplier_id' => $this->supplier_id,
            'expedition_fee' => $this->expedition_fee,
            'livestock_id' => $this->livestock_id,
            'items' => $this->items,
            'valid_items_count' => count($validItems),
        ]);

        // Validate expedition input if expedition data is provided
        if (!empty($this->expedition_id) || !empty($this->expedition_fee)) {
            $expeditionValidationErrors = $this->validateExpeditionInput();
            if (!empty($expeditionValidationErrors)) {
                foreach ($expeditionValidationErrors as $field => $message) {
                    $this->addError($field, $message);
                }
                return;
            }
        }

        // Prepare validation rules - make items optional for drafts
        $validationRules = [
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:partners,id',
            'expedition_fee' => 'numeric|min:0',
            'livestock_id' => 'required|exists:livestocks,id',
        ];

        // Only validate items if we have valid items
        if (!empty($validItems)) {
            $validationRules = array_merge($validationRules, [
                'items' => 'required|array|min:1',
                'items.*.feed_id' => 'required|exists:feeds,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.price_per_unit' => 'required|numeric|min:0',
                'items.*.unit_id' => 'required|exists:units,id',
            ]);
        }

        $this->validate($validationRules);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            Log::debug('FeedPurchase Save: Authenticated user', ['user_id' => $user->id]);
            $livestock = Livestock::findOrFail($this->livestock_id);
            Log::debug('FeedPurchase Save: Livestock found', ['livestock_id' => $livestock->id]);

            $purchase = FeedPurchase::updateOrCreate(
                [
                    'id' => $this->pembelianId,
                    'company_id' => $user->company_id,
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id,
                    'livestock_id' => $livestock->id,
                ],
                [
                    'invoice_number' => $this->invoice_number,
                    'date' => $this->date,
                    'supplier_id' => $this->supplier_id,
                    'expedition_id' => (!empty($this->expedition_id) && $this->expedition_id !== '') ? $this->expedition_id : null,
                    'expedition_fee' => $this->expedition_fee ?? 0,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            Log::info('FeedPurchase Save: FeedPurchase created/updated', [
                'purchase_id' => $purchase->id,
                'data' => $purchase->toArray(),
            ]);

            // Generate automatic numbering for feed purchase if not set
            if (empty($purchase->number) || empty($purchase->number_full)) {
                try {
                    $numbering = FeedNumberingService::generateNumber('feed_purchases', $purchase->date ?? now(), []);
                    $purchase->number = $numbering['number'];
                    $purchase->number_full = $numbering['full_number'];
                    $purchase->save();

                    Log::info('Generated automatic numbering for FeedPurchase', [
                        'purchase_id' => $purchase->id,
                        'number' => $purchase->number,
                        'number_full' => $purchase->number_full,
                        'date' => $purchase->date
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to generate automatic numbering for FeedPurchase', [
                        'purchase_id' => $purchase->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without numbering - not critical
                }
            }

            // Only process items if we have valid items
            if (!empty($validItems)) {
                // Buat key yang digunakan untuk cek data yang tidak lagi dipakai
                $newItemKeys = collect($validItems)->map(function ($item) {
                    return $item['feed_id'] . '-' . ($item['unit_id'] ?? '');
                })->toArray();

                Log::debug('FeedPurchase Save: New item keys for deletion check', [
                    'newItemKeys' => $newItemKeys,
                ]);

                foreach ($purchase->feedPurchaseItems as $purchaseItem) {
                    $key = $purchaseItem->feed_id . '-' . $purchaseItem->unit_id;

                    if (!in_array($key, $newItemKeys)) {
                        Log::info('FeedPurchase Save: Deleting FeedStock and FeedPurchaseItem', [
                            'feed_purchase_item_id' => $purchaseItem->id,
                            'key' => $key,
                        ]);
                        FeedStock::where('feed_purchase_id', $purchaseItem->id)->delete();

                        if ($this->withHistory) {
                            $purchaseItem->delete(); // soft delete
                        } else {
                            $purchaseItem->forceDelete(); // hard delete
                        }
                    }
                }

                // Process each valid item
                foreach ($validItems as $item) {
                    $user = Auth::user();
                    $feed = Feed::findOrFail($item['feed_id']);
                    Log::debug('FeedPurchase Save: Processing item', [
                        'feed_id' => $item['feed_id'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'quantity' => $item['quantity'],
                    ]);

                    $units = collect($feed->data['conversion_units']);
                    $selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
                    $smallestUnit = $units->firstWhere('is_smallest', true);

                    if (!$selectedUnit || !$smallestUnit) {
                        Log::error('FeedPurchase Save: Invalid unit conversion', [
                            'feed_id' => $feed->id,
                            'feed_name' => $feed->name,
                            'selected_unit' => $item['unit_id'] ?? 'null',
                            'units' => $feed->data['conversion_units'],
                        ]);
                        throw new \Exception("Invalid unit conversion for feed: {$feed->name}");
                    }

                    $convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];

                    $purchaseItem = FeedPurchaseItem::updateOrCreate(
                        [
                            'feed_purchase_id' => $purchase->id,
                            'feed_id' => $feed->id,
                        ],
                        [
                            'quantity' => $item['quantity'],
                            'converted_quantity' => $convertedQuantity,
                            'converted_unit' => $smallestUnit['unit_id'],
                            'price_per_unit' => $item['price_per_unit'],
                            'price_per_converted_unit' => $item['unit_id'] !== $smallestUnit['unit_id']
                                ? round($item['price_per_unit'] * ($smallestUnit['value'] / $selectedUnit['value']), 2)
                                : $item['price_per_unit'],
                            'created_by' => $user->id,
                            'feed_id' => $feed->id,
                            'unit_id' => $item['unit_id'],
                        ]
                    );

                    Log::info('FeedPurchase Save: FeedPurchaseItem created/updated', [
                        'feed_purchase_item_id' => $purchaseItem->id,
                        'feed_id' => $feed->id,
                        'unit_id' => $item['unit_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            } else {
                Log::info('FeedPurchase Save: No valid items to process, saving as draft', [
                    'purchase_id' => $purchase->id,
                ]);
            }

            // Save data payload including all items (valid and empty)
            $purchase->data = [
                'items' => collect($this->items)->map(fn($item) => [
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ])->toArray(),
                'livestock_id' => $this->livestock_id,
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'date' => $this->date,
            ];
            $purchase->save();

            Log::info('FeedPurchase Save: Final purchase data saved', [
                'purchase_id' => $purchase->id,
                'data' => $purchase->data,
            ]);

            // Create expedition transaction if expedition data exists (regardless of status)
            if (!empty($this->expedition_id) && !empty($this->expedition_fee)) {
                Log::info('=== EXPEDITION DATA CHECK IN SAVE METHOD ===', [
                    'purchase_id' => $purchase->id,
                    'expedition_id' => $this->expedition_id,
                    'expedition_fee' => $this->expedition_fee,
                    'expedition_id_empty' => empty($this->expedition_id),
                    'expedition_fee_empty' => empty($this->expedition_fee),
                    'expedition_id_type' => gettype($this->expedition_id),
                    'expedition_fee_type' => gettype($this->expedition_fee),
                    'status' => $this->status ?? 'draft',
                    'user_id' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id ?? 'NULL'
                ]);

                $expeditionSuccess = $this->createExpeditionTransaction($purchase->id);

                if ($expeditionSuccess) {
                    Log::info('=== EXPEDITION TRANSACTION CREATED SUCCESSFULLY ===', [
                        'purchase_id' => $purchase->id,
                        'expedition_id' => $this->expedition_id,
                        'expedition_fee' => $this->expedition_fee
                    ]);
                } else {
                    Log::error('=== EXPEDITION TRANSACTION CREATION FAILED - ROLLBACK REQUIRED ===', [
                        'purchase_id' => $purchase->id,
                        'expedition_id' => $this->expedition_id,
                        'expedition_fee' => $this->expedition_fee,
                        'status' => $this->status ?? 'draft',
                        'reason' => 'Expedition transaction creation failed - rolling back entire transaction'
                    ]);

                    // Rollback database transaction karena expedition gagal
                    DB::rollBack();

                    // Set error message untuk user
                    $this->addError('expedition_error', 'Gagal membuat expedition transaction. Mohon periksa data ekspedisi dan biaya. Jika masalah berlanjut, hubungi administrator.');

                    // Log error detail untuk debugging
                    Log::error('=== FEED PURCHASE SAVE ROLLBACK DUE TO EXPEDITION FAILURE ===', [
                        'purchase_id' => $purchase->id,
                        'expedition_id' => $this->expedition_id,
                        'expedition_fee' => $this->expedition_fee,
                        'user_id' => Auth::user()->id,
                        'company_id' => Auth::user()->company_id ?? 'NULL',
                        'rollback_reason' => 'Expedition transaction creation failed',
                        'rollback_timestamp' => now()
                    ]);

                    return; // Stop execution, tidak lanjut ke commit
                }
            } else {
                Log::info('=== NO EXPEDITION DATA TO PROCESS ===', [
                    'purchase_id' => $purchase->id,
                    'expedition_id' => $this->expedition_id,
                    'expedition_fee' => $this->expedition_fee,
                    'reason' => 'Either expedition_id or expedition_fee is empty'
                ]);
            }

            DB::commit();

            $message = empty($validItems)
                ? 'Draft pembelian pakan berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan')
                : 'Pembelian pakan berhasil ' . ($this->pembelianId ? 'diperbarui' : 'disimpan');

            $this->dispatch('success', $message);
            $this->close();
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('FeedPurchase Save: ValidationException', [
                'errors' => $e->validator->errors()->all(),
            ]);
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FeedPurchase Save: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset();
        $this->items = [
            [
                'feed_id' => null,
                'quantity' => null,
                'unit_id' => null, // ← changed from 'unit' to 'unit_id' for consistency
                'price_per_unit' => null,
                'available_units' => [], // ← new: daftar satuan berdasarkan feed
            ],
            // ['feed_id' => '', 'quantity' => 1, 'price_per_unit' => 0],
        ];
    }

    public function updatedItems($value, $key)
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'feed_id') {
            $feed = Feed::find($value);

            if ($feed && isset($feed->data['conversion_units'])) {
                $units = collect($feed->data['conversion_units']);

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
        if (in_array($field, ['quantity', 'price_per_unit'])) {
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
        $price = floatval($item['price_per_unit'] ?? 0);

        $subTotal = $quantity * $price;
        $this->items[$index]['sub_total'] = $subTotal;
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
    public function updatedItemsPricePerUnit($value, $key)
    {
        [$index] = explode('.', $key);
        $this->items[$index]['price_per_unit'] = $value;
        $this->calculateItemSubTotal($index);
        $this->dispatch('item-updated', index: $index);
    }

    public function updateUnitConversion($index)
    {
        $unitId = $this->items[$index]['unit_id'] ?? null;
        $quantity = $this->items[$index]['quantity'] ?? null;
        $feedId = $this->items[$index]['feed_id'] ?? null;

        if (!$unitId || !$quantity || !$feedId) return;

        $feed = Feed::find($feedId);
        if (!$feed || empty($feed->data['conversion_units'])) return;

        $units = collect($feed->data['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);

        if ($selectedUnit && $smallestUnit) {
            // Convert to smallest unit
            $this->items[$index]['converted_quantity'] = ($quantity * $selectedUnit['value']) / $smallestUnit['value'];
        }
    }

    protected function convertToSmallestUnit($feed, $quantity, $unitId)
    {
        $units = collect($feed->data['conversion_units'] ?? []);

        $from = $units->firstWhere('unit_id', $unitId);
        $smallest = $units->firstWhere('is_smallest', true);

        if (!$from || !$smallest || $from['value'] == 0 || $smallest['value'] == 0) {
            return $quantity; // fallback tanpa konversi
        }

        // return ($quantity * $from['value']) / $smallest['value'];
        dd([
            'quantity' => $quantity,
            'value' => $from['value'],
            'smallest' => $smallest['value'],
        ]);
        // dd(($quantity * $from['value']) / $smallest['value']);
    }

    public function render()
    {
        $user = Auth::user();
        $companyId = $user ? $user->company_id : null;

        $roles = [];
        if ($user && method_exists($user, 'getRoleNames')) {
            try {
                $roleNames = call_user_func([$user, 'getRoleNames']);
                $roles = is_object($roleNames) && method_exists($roleNames, 'toArray') ? $roleNames->toArray() : (array) $roleNames;
            } catch (\Throwable $e) {
                $roles = [];
            }
        }
        $isSuperAdmin = in_array('SuperAdmin', $roles, true);
        $isCompanyRole = !empty(array_intersect($roles, ['Supervisor', 'Manager', 'Administrator']));
        $isOperator = in_array('Operator', $roles, true);

        // Logging for debugging role-based data filtering
        Log::debug('FeedPurchases/Create render() called', [
            'user_id' => $user ? $user->id : null,
            'roles' => $roles,
            'company_id' => $companyId,
            'isSuperAdmin' => $isSuperAdmin,
            'isCompanyRole' => $isCompanyRole,
            'isOperator' => $isOperator,
        ]);

        // Vendors (Partners)
        if ($isSuperAdmin) {
            $vendors = Partner::where('type', 'Supplier')->get();
        } elseif ($isCompanyRole) {
            $vendors = Partner::where('type', 'Supplier')->where('company_id', $companyId)->get();
        } elseif ($isOperator) {
            $vendors = Partner::where('type', 'Supplier')
                ->where('company_id', $companyId)
                ->get();
        } else {
            $vendors = collect();
        }

        // Expeditions (from partners)
        if ($isSuperAdmin) {
            $expeditions = Partner::where('type', 'Expedition')->get();
        } elseif ($isCompanyRole) {
            $expeditions = Partner::where('type', 'Expedition')->where('company_id', $companyId)->get();
        } elseif ($isOperator) {
            $expeditions = Partner::where('type', 'Expedition')->where('company_id', $companyId)->get();
        } else {
            $expeditions = collect();
        }

        // Feed Items
        if ($isSuperAdmin) {
            $feedItems = Feed::all();
        } elseif ($isCompanyRole) {
            $feedItems = Feed::where('company_id', $companyId)->get();
        } elseif ($isOperator) {
            $feedItems = Feed::where('company_id', $companyId)->get();
        } else {
            $feedItems = collect();
        }

        // Livestocks
        if ($isSuperAdmin) {
            $livestocks = Livestock::all();
        } elseif ($isCompanyRole) {
            $livestocks = Livestock::where('company_id', $companyId)->get();
        } elseif ($isOperator) {
            // Operator hanya bisa melihat livestock yang farm-nya dioperasikan oleh user ini
            $livestocks = Livestock::whereHas('farm.farmOperators', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
        } else {
            $livestocks = collect();
        }

        // Debug logging for edit mode
        if ($this->edit_mode) {
            Log::debug('FeedPurchases/Create render() in edit mode', [
                'livestock_id' => $this->livestock_id,
                'expedition_id' => $this->expedition_id,
                'supplier_id' => $this->supplier_id,
                'available_expeditions_count' => $expeditions->count(),
                'available_livestocks_count' => $livestocks->count(),
                'expedition_exists' => $expeditions->where('id', $this->expedition_id)->count() > 0,
                'livestock_exists' => $livestocks->where('id', $this->livestock_id)->count() > 0,
            ]);
        }

        return view('livewire.feed-purchases.create', [
            'vendors' => $vendors,
            'expeditions' => $expeditions,
            'feedItems' => $feedItems,
            'livestocks' => $livestocks,
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();

        // // Auto-generate invoice_number if not set
        // if (empty($this->invoice_number)) {
        //     try {
        //         $numbering = FeedNumberingService::generateNumber('feed_purchases', $this->date ?? now(), [
        //             // context tambahan jika perlu
        //         ]);
        //         $this->invoice_number = $numbering['full_number'];

        //         Log::info('Auto-generated invoice number for FeedPurchase', [
        //             'invoice_number' => $this->invoice_number,
        //             'date' => $this->date
        //         ]);
        //     } catch (\Exception $e) {
        //         Log::warning('Failed to auto-generate invoice number for FeedPurchase', [
        //             'error' => $e->getMessage(),
        //             'date' => $this->date
        //         ]);
        //         // Continue without auto-generation - not critical
        //     }
        // }

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
        // $this->dispatch('closeForm');
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function deleteFeedPurchase($purchaseId)
    {
        try {
            DB::beginTransaction();

            $purchase = FeedPurchase::with(['feedPurchaseItems.feedStocks', 'feedPurchaseItems.feed'])->findOrFail($purchaseId);

            // Track quantities to adjust in CurrentFeed by farm and feed
            $adjustments = [];

            // Track all related records for the audit trail
            $relatedRecords = [
                'feedPurchases' => [],
                'feedStocks' => [],
                'currentSupplies' => []
            ];

            // Loop semua FeedPurchase di dalam batch
            foreach ($purchase->feedPurchaseItems as $purchase) {
                // Add purchase to related records
                $relatedRecords['feedPurchases'][] = [
                    'id' => $purchase->id,
                    'feed_id' => $purchase->feed_id,
                    'farm_id' => $purchase->farm_id,
                    'quantity' => $purchase->quantity,
                    'price_per_unit' => $purchase->price_per_unit
                ];

                // Get all related stocks for this purchase
                $feedStocks = $purchase->feedStocks;

                if ($feedStocks->isEmpty()) {
                    // If no stocks found, try to find directly
                    $feedStocks = FeedStock::where('feed_purchase_id', $purchase->id)->get();
                }

                foreach ($feedStocks as $feedStock) {
                    // Add stock to related records
                    $relatedRecords['feedStocks'][] = [
                        'id' => $feedStock->id,
                        'farm_id' => $feedStock->farm_id,
                        'feed_id' => $feedStock->feed_id,
                        'quantity_in' => $feedStock->quantity_in,
                        'quantity_used' => $feedStock->quantity_used,
                        'quantity_mutated' => $feedStock->quantity_mutated
                    ];

                    // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                    if (($feedStock->quantity_used ?? 0) > 0 || ($feedStock->quantity_mutated ?? 0) > 0) {
                        $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                        DB::rollBack();
                        return;
                    }

                    // dd($feedStock);

                    // Get the farm and feed IDs for this stock
                    $farmId = $feedStock->livestock->farm_id;
                    $feedId = $feedStock->feed_id;

                    // Get the feed with its conversion data
                    $feed = $purchase->feed;
                    if (!$feed) {
                        $feed = Feed::find($feedId);
                    }

                    if (!$feed) {
                        Log::error("Feed not found for ID: {$feedId} when deleting purchase batch");
                        continue;
                    }

                    // Get unit conversion information
                    $conversionUnits = collect($feed->data['conversion_units'] ?? []);
                    $smallestUnitId = $conversionUnits->firstWhere('is_smallest', true)['unit_id'] ?? null;

                    // Create a unique key for this farm+feed combination
                    $key = "{$farmId}_{$feedId}";

                    // Calculate the quantity to deduct in the smallest unit
                    $quantityToDeduct = $feedStock->quantity_in;

                    // Store the adjustment information
                    if (!isset($adjustments[$key])) {
                        $adjustments[$key] = [
                            'farm_id' => $farmId,
                            'feed_id' => $feedId,
                            'smallest_unit_id' => $smallestUnitId,
                            'quantity_to_deduct' => 0,
                            'feed' => $feed
                        ];
                    }

                    // Add this stock's quantity to the total to deduct
                    $adjustments[$key]['quantity_to_deduct'] += $quantityToDeduct;

                    // Delete the stock
                    $feedStock->delete();
                }

                // Delete the purchase
                $purchase->delete();
            }

            // Update CurrentFeed records for each affected farm+feed combination
            foreach ($adjustments as $key => $adjustment) {
                $farmId = $adjustment['farm_id'];
                $feedId = $adjustment['feed_id'];
                // Use CurrentFeedService for recalculation
                $service = $this->currentFeedService ?? app(\App\Services\Feed\CurrentFeedService::class);
                $result = $service->updateQuantity([
                    'farm_id' => $farmId,
                    'feed_id' => $feedId,
                ]);
                Log::info("[CurrentFeedService] Batch delete update result", $result);
            }

            // Log to audit trail before final deletion
            AuditTrailService::logCascadingDeletion(
                $purchase,
                $relatedRecords,
                "User initiated deletion of feed purchase batch"
            );

            // Hapus batch setelah semua data terkait dihapus dan tercatat
            $purchase->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus dan stok telah diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting feed purchase batch: " . $e->getMessage(), [
                'purchase_id' => $purchaseId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }

    public function updateDoNumber($transaksiId, $newNoSj)
    {
        $transaksiDetail = \App\Models\FeedPurchase::findOrFail($transaksiId);

        if ($transaksiDetail->exists()) {
            $transaksiDetail->do_number = $newNoSj;
            $transaksiDetail->save();
            $this->dispatch('noSjUpdated');
            $this->dispatch('success', 'Nomor Surat Jalan / Deliveri Order berhasil diperbarui.');
        } else {
            $this->dispatch('error', 'Tidak ada detail transaksi yang ditemukan.');
        }
    }

    public function isReadonly()
    {
        if ($this->tempAuthEnabled) {
            return false;
        }

        return in_array($this->status, ['arrived', 'completed']);
    }

    public function isDisabled()
    {
        // If temp auth is enabled, not disabled
        if ($this->tempAuthEnabled) {
            return false;
        }

        // Check local conditions
        return in_array($this->status, ['arrived', 'completed']);
    }


    public function showEditForm($id)
    {
        $this->pembelianId = $id;
        $pembelian = FeedPurchase::with(['feedPurchaseItems.feed', 'livestock', 'supplier', 'expedition'])->find($id);

        if (!$pembelian) {
            $this->dispatch('error', 'Data pembelian tidak ditemukan');
            return;
        }

        // Initialize basic data directly from FeedPurchase model
        $this->date = $pembelian->date;
        $this->invoice_number = $pembelian->invoice_number;
        $this->supplier_id = $pembelian->supplier_id;
        $this->expedition_id = $this->normalizeExpeditionId($pembelian->expedition_id);
        $this->expedition_fee = $pembelian->expedition_fee ?? 0;
        $this->livestock_id = $pembelian->livestock_id; // ← Fix: Get from main record
        $this->status = $pembelian->status;

        Log::info('FeedPurchase Edit: Loading form data', [
            'purchase_id' => $pembelian->id,
            'livestock_id' => $this->livestock_id,
            'supplier_id' => $this->supplier_id,
            'expedition_id' => $this->expedition_id,
            'expedition_fee' => $this->expedition_fee,
        ]);

        // Get data from feedPurchaseItems
        $feedPurchaseData = $pembelian->feedPurchaseItems->map(function ($purchaseItem) {
            $feed = $purchaseItem->feed;
            $available_units = [];
            $unit_id = $purchaseItem->unit_id;

            if ($feed && isset($feed->data['conversion_units'])) {
                $available_units = collect($feed->data['conversion_units'])->map(function ($unit) {
                    $unitModel = \App\Models\Unit::find($unit['unit_id']);
                    return [
                        'unit_id' => (string)$unit['unit_id'],
                        'label' => $unitModel?->name ?? 'Unknown',
                        'value' => $unit['value'],
                        'is_smallest' => $unit['is_smallest'] ?? false,
                    ];
                })->toArray();
            }

            return [
                'feed_id' => $purchaseItem->feed_id,
                'quantity' => $purchaseItem->quantity,
                'price_per_unit' => $purchaseItem->price_per_unit,
                'unit_id' => $unit_id,
                'available_units' => $available_units,
            ];
        })->toArray();

        // Get data from payload if exists
        $payloadData = $pembelian->data['items'] ?? [];

        // Set items and livestock_id
        if (!empty($payloadData)) {
            // Check if payload data is newer than feedPurchaseItems
            $payloadItems = collect($payloadData)->map(function ($item) {
                return [
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                    'available_units' => $item['available_units'] ?? [],
                ];
            })->toArray();

            $feedPurchaseItems = collect($feedPurchaseData)->map(function ($item) {
                return [
                    'feed_id' => $item['feed_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'price_per_unit' => $item['price_per_unit'],
                ];
            })->toArray();

            // If payload data is different, update it
            if ($this->isDataDifferent($payloadItems, $feedPurchaseItems)) {
                $pembelian->data = array_merge($pembelian->data ?? [], [
                    'items' => $feedPurchaseData,
                    'livestock_id' => $this->livestock_id, // ← Fix: Use main record livestock_id
                    'supplier_id' => $this->supplier_id,
                    'expedition_id' => $this->expedition_id,
                    'expedition_fee' => $this->expedition_fee,
                    'date' => $this->date,
                ]);
                $pembelian->save();

                $this->items = $feedPurchaseData;
            } else {
                $this->items = $payloadItems;

                // Ensure livestock_id from payload matches main record, fallback to main record
                $payloadLivestockId = $pembelian->data['livestock_id'] ?? null;
                if ($payloadLivestockId && $payloadLivestockId !== $this->livestock_id) {
                    Log::warning('FeedPurchase Edit: Payload livestock_id differs from main record', [
                        'purchase_id' => $pembelian->id,
                        'main_livestock_id' => $this->livestock_id,
                        'payload_livestock_id' => $payloadLivestockId,
                    ]);
                    // Keep the main record value, update payload
                    $pembelian->data = array_merge($pembelian->data ?? [], [
                        'livestock_id' => $this->livestock_id,
                    ]);
                    $pembelian->save();
                }
            }
        } else {
            // If no payload data, use feedPurchaseItems data
            $this->items = $feedPurchaseData;

            // Update payload with current data
            $pembelian->data = [
                'items' => $feedPurchaseData,
                'livestock_id' => $this->livestock_id, // ← Fix: Use main record livestock_id
                'supplier_id' => $this->supplier_id,
                'expedition_id' => $this->expedition_id,
                'expedition_fee' => $this->expedition_fee,
                'date' => $this->date,
            ];
            $pembelian->save();
        }

        Log::info('FeedPurchase Edit: Form data loaded successfully', [
            'purchase_id' => $pembelian->id,
            'livestock_id' => $this->livestock_id,
            'supplier_id' => $this->supplier_id,
            'expedition_id' => $this->expedition_id,
            'items_count' => count($this->items),
        ]);

        // Add debugging for frontend values
        Log::info('FeedPurchase Edit: Final values before showing form', [
            'livestock_id' => $this->livestock_id,
            'expedition_id' => $this->expedition_id,
            'supplier_id' => $this->supplier_id,
            'expedition_fee' => $this->expedition_fee,
            'date' => $this->date,
            'invoice_number' => $this->invoice_number,
        ]);

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');

        // Force Livewire to re-render the form with updated data
        $this->dispatch('form-data-loaded', [
            'livestock_id' => $this->livestock_id,
            'expedition_id' => $this->expedition_id,
            'supplier_id' => $this->supplier_id,
        ]);
    }

    private function isDataDifferent($payloadItems, $feedPurchaseItems)
    {
        if (count($payloadItems) !== count($feedPurchaseItems)) {
            return true;
        }

        foreach ($payloadItems as $index => $payloadItem) {
            $feedPurchaseItem = $feedPurchaseItems[$index] ?? null;

            if (!$feedPurchaseItem) {
                return true;
            }

            if (
                $payloadItem['feed_id'] !== $feedPurchaseItem['feed_id'] ||
                $payloadItem['quantity'] != $feedPurchaseItem['quantity'] ||
                $payloadItem['unit_id'] !== $feedPurchaseItem['unit_id'] ||
                $payloadItem['price_per_unit'] != $feedPurchaseItem['price_per_unit']
            ) {
                return true;
            }
        }

        return false;
    }

    public function updateStatusFeedPurchase($purchaseId, $status, $notes)
    {
        if (empty($purchaseId) || empty($status)) {
            return;
        }

        $purchase = FeedPurchase::findOrFail($purchaseId)->load('feedPurchaseItems');
        $notes = $notes ?? null;
        $oldStatus = $purchase->status;

        // If status is arrived, validate and process stock arrival
        if ($status === FeedPurchase::STATUS_ARRIVED) {
            try {
                Log::info('Starting validation for feed stock arrival processing', [
                    'purchase_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
                    'livestock_id' => $purchase->livestock_id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ]);

                // Step 1: Pre-validation checks
                $validationResult = $this->validateStockArrival($purchase);
                if (!$validationResult['success']) {
                    Log::warning('Stock arrival validation failed', [
                        'purchase_id' => $purchase->id,
                        'validation_errors' => $validationResult['errors'],
                    ]);

                    $this->dispatch('warning', 'Validasi gagal: ' . implode(', ', $validationResult['errors']));
                    $this->dispatch('status-validation-failed', [
                        'purchase_id' => $purchase->id,
                        'old_status' => $oldStatus,
                        'errors' => $validationResult['errors']
                    ]);
                    return;
                }

                // Step 2: Process stock arrival with transaction
                $processingResult = $this->processStockArrivalWithValidation($purchase);
                if (!$processingResult['success']) {
                    Log::error('Stock arrival processing failed', [
                        'purchase_id' => $purchase->id,
                        'processing_errors' => $processingResult['errors'],
                    ]);

                    $this->dispatch('warning', 'Proses stock arrival gagal: ' . implode(', ', $processingResult['errors']));
                    $this->dispatch('status-processing-failed', [
                        'purchase_id' => $purchase->id,
                        'old_status' => $oldStatus,
                        'errors' => $processingResult['errors']
                    ]);
                    return;
                }

                Log::info('Feed stock arrival processing completed successfully', [
                    'purchase_id' => $purchase->id,
                    'items_processed' => $processingResult['items_processed'],
                    'processing_details' => $processingResult['details'],
                ]);

                // Step 3: Dispatch success notification
                $this->dispatch('success', 'Stock arrival berhasil diproses: ' . $processingResult['items_processed'] . ' item(s)');
            } catch (\Exception $e) {
                Log::error('Unexpected error during stock arrival processing', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->dispatch('warning', 'Terjadi kesalahan tidak terduga: ' . $e->getMessage());
                $this->dispatch('status-unexpected-error', [
                    'purchase_id' => $purchase->id,
                    'old_status' => $oldStatus,
                    'error' => $e->getMessage()
                ]);
                return;
            }
        }

        // Only update status if livestock generation was successful or not needed
        $purchase->updateFeedStatus($status, $notes, ['ip' => request()->ip(), 'user_agent' => request()->userAgent()]);

        // 🎯 DISPATCH REAL-TIME NOTIFICATION
        if ($oldStatus !== $status) {
            $notificationData = [
                'type' => $this->getNotificationTypeForStatus($status),
                'title' => 'Feed Purchase Status Updated',
                'message' => $this->getStatusChangeMessage($purchase, $oldStatus, $status),
                'batch_id' => $purchase->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_by' => Auth::check() ? Auth::id() : null,
                'updated_by_name' => Auth::check() && Auth::user() ? Auth::user()->name : null,
                'invoice_number' => $purchase->invoice_number,
                'requires_refresh' => $this->requiresRefresh($oldStatus, $status),
                'priority' => $this->getPriority($oldStatus, $status),
                'show_refresh_button' => true,
                'timestamp' => now()->toISOString()
            ];

            // 🎯 BROADCAST TO ALL FEED PURCHASE LIVEWIRE COMPONENTS IMMEDIATELY
            $this->dispatch('notify-status-change', $notificationData)->to('feed-purchases.create');

            Log::info('IMMEDIATE feed purchase notification dispatched to Livewire components', [
                'batch_id' => $purchase->id,
                'notification_data' => $notificationData
            ]);

            // ✅ SEND TO SSE NOTIFICATION BRIDGE FOR REAL-TIME UPDATES (NO MORE POLLING!)
            // TODO: Uncomment this when the notification bridge is ready
            // $this->sendToSSENotificationBridge($notificationData, $purchase);

            // Fire event for external systems and broadcasting (secondary)
            // try {
            //     \App\Events\FeedPurchaseStatusChanged::dispatch(
            //         $purchase,
            //         $oldStatus,
            //         $status,
            //         auth()->id(),
            //         $notes,
            //         $notificationData
            //     );

            //     Log::info('FeedPurchaseStatusChanged event fired', [
            //         'batch_id' => $purchase->id,
            //         'old_status' => $oldStatus,
            //         'new_status' => $status,
            //         'updated_by' => auth()->id()
            //     ]);
            // } catch (\Exception $e) {
            //     Log::error('Failed to fire FeedPurchaseStatusChanged event', [
            //         'batch_id' => $purchase->id,
            //         'error' => $e->getMessage(),
            //         'file' => $e->getFile(),
            //         'line' => $e->getLine()
            //     ]);
            // }
        }

        $this->dispatch('statusUpdated');
        $this->dispatch('success', 'Status pembelian berhasil diperbarui.');
    }

    /**
     * Process FeedStock creation/update for a feed purchase
     * This method creates or updates FeedStock records when feed arrives
     */
    private function processFeedStock($feedPurchaseItem, $feed, $livestock, $convertedQuantity)
    {
        // dd($feedPurchaseItem, $feed, $livestock, $convertedQuantity);
        Log::info("Processing FeedStock", [
            'feed_purchase_id' => $feedPurchaseItem->id, // gunakan id dari feed_purchase_items
            'feed_id' => $feed->id,
            'livestock_id' => $livestock->id,
            'converted_quantity' => $convertedQuantity
        ]);

        // Validate that the FeedPurchase exists and has the required ID
        if (!$feedPurchaseItem || !$feedPurchaseItem->exists) {
            throw new \Exception("FeedPurchase dengan ID {$feedPurchaseItem->id} tidak ditemukan.");
        }

        // Validate foreign key constraint - ensure FeedPurchaseItem record exists in database
        $existingFeedPurchaseItem = \App\Models\FeedPurchaseItem::find($feedPurchaseItem->id);
        if (!$existingFeedPurchaseItem) {
            Log::error("FeedPurchaseItem tidak ditemukan di database", [
                'feed_purchase_item_id' => $feedPurchaseItem->id,
                'feed_id' => $feed->id,
                'livestock_id' => $livestock->id
            ]);
            throw new \Exception("FeedPurchaseItem dengan ID {$feedPurchaseItem->id} tidak ditemukan di database.");
        }

        $data = [
            'feed_purchase_id' => $feedPurchaseItem->feedPurchase->id,
            'feed_purchase_number' => $feedPurchaseItem->feedPurchase->number_full,
            'supplier_id' => $feedPurchaseItem->feedPurchase->supplier_id,
            'expedition_id' => $feedPurchaseItem->feedPurchase->expedition_id,
            'expedition_fee' => $feedPurchaseItem->feedPurchase->expedition_fee,
            'date' => $feedPurchaseItem->feedPurchase->date,
            'invoice_number' => $feedPurchaseItem->feedPurchase->invoice_number,
        ];
        FeedStock::updateOrCreate(
            [
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
                'feed_purchase_id' => $feedPurchaseItem->feedPurchase->id, // gunakan id dari Feed Purchase
            ],
            [
                'date' => $feedPurchaseItem->feedPurchase->date,
                'source_type' => 'purchase',
                'source_id' => $feedPurchaseItem->id, // gunakan id dari FeedPurchaseItem
                'quantity_in' => $convertedQuantity,
                'data' => $data,
                'created_by' => Auth::check() ? Auth::id() : null,
                'updated_by' => Auth::check() ? Auth::id() : null,
            ]
        );

        Log::info("FeedStock processed successfully", [
            'feed_purchase_id' => $feedPurchaseItem->id,
            'feed_stock_created' => true
        ]);
    }

    /**
     * Validate stock arrival process before execution
     * This method performs comprehensive validation to ensure all prerequisites are met
     */
    private function validateStockArrival(FeedPurchase $purchase): array
    {
        $errors = [];
        $warnings = [];

        Log::info('Starting comprehensive stock arrival validation', [
            'purchase_id' => $purchase->id,
            'invoice_number' => $purchase->invoice_number,
        ]);

        try {
            // 1. Validate purchase data integrity
            if (empty($purchase->livestock_id)) {
                $errors[] = 'Livestock ID tidak ditemukan pada purchase';
            }

            if (empty($purchase->date)) {
                $errors[] = 'Tanggal purchase tidak ditemukan';
            }

            if (empty($purchase->supplier_id)) {
                $errors[] = 'Supplier ID tidak ditemukan';
            }

            // 2. Validate FeedPurchaseItems
            $feedPurchaseItems = $purchase->feedPurchaseItems;
            if ($feedPurchaseItems->isEmpty()) {
                $errors[] = 'Tidak ada data FeedPurchaseItem yang ditemukan untuk purchase ini';
            } else {
                foreach ($feedPurchaseItems as $index => $item) {
                    // Validate each item
                    if (empty($item->feed_id)) {
                        $errors[] = "Feed ID tidak ditemukan pada item #" . ($index + 1);
                    }

                    if (empty($item->converted_quantity) || $item->converted_quantity <= 0) {
                        $errors[] = "Quantity tidak valid pada item #" . ($index + 1) . " (Feed ID: {$item->feed_id})";
                    }

                    if (empty($item->unit_id)) {
                        $errors[] = "Unit ID tidak ditemukan pada item #" . ($index + 1) . " (Feed ID: {$item->feed_id})";
                    }

                    // Validate feed exists and is active
                    try {
                        $feed = Feed::findOrFail($item->feed_id);
                        if (!$feed->is_active) {
                            $warnings[] = "Feed '{$feed->name}' tidak aktif pada item #" . ($index + 1);
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Feed dengan ID {$item->feed_id} tidak ditemukan pada item #" . ($index + 1);
                    }
                }
            }

            // 3. Validate livestock exists and is active
            try {
                $livestock = Livestock::findOrFail($purchase->livestock_id);
                if (!$livestock->is_active) {
                    $warnings[] = "Livestock '{$livestock->name}' tidak aktif";
                }
            } catch (\Exception $e) {
                $errors[] = "Livestock dengan ID {$purchase->livestock_id} tidak ditemukan";
            }

            // 4. Validate supplier exists
            try {
                $supplier = Partner::findOrFail($purchase->supplier_id);
                if ($supplier->type !== 'Supplier') {
                    $warnings[] = "Partner '{$supplier->name}' bukan supplier";
                }
            } catch (\Exception $e) {
                $errors[] = "Supplier dengan ID {$purchase->supplier_id} tidak ditemukan";
            }

            // 5. Validate expedition if exists
            if (!empty($purchase->expedition_id)) {
                try {
                    $expedition = Partner::findOrFail($purchase->expedition_id);
                } catch (\Exception $e) {
                    $warnings[] = "Expedition dengan ID {$purchase->expedition_id} tidak ditemukan";
                }
            }

            // 6. Check for potential conflicts
            foreach ($feedPurchaseItems as $item) {
                // Check if there's already a FeedStock with same purchase
                $existingStock = FeedStock::where('feed_purchase_id', $purchase->id)
                    ->where('feed_id', $item->feed_id)
                    ->where('livestock_id', $purchase->livestock_id)
                    ->first();

                if ($existingStock) {
                    $warnings[] = "FeedStock sudah ada untuk Feed ID {$item->feed_id} pada purchase ini";
                }
            }

            // 7. Validate CurrentFeedService availability
            try {
                $service = $this->currentFeedService ?? app(\App\Services\Feed\CurrentFeedService::class);
                // Test service availability by calling a simple method
                if (!method_exists($service, 'updateQuantity')) {
                    $errors[] = 'CurrentFeedService tidak tersedia atau tidak memiliki method updateQuantity';
                }
            } catch (\Exception $e) {
                $errors[] = 'CurrentFeedService tidak dapat diakses: ' . $e->getMessage();
            }

            // 8. Database connection validation
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $errors[] = 'Koneksi database tidak tersedia: ' . $e->getMessage();
            }
        } catch (\Exception $e) {
            Log::error('Error during stock arrival validation', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $errors[] = 'Terjadi kesalahan saat validasi: ' . $e->getMessage();
        }

        $result = [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'validation_details' => [
                'purchase_id' => $purchase->id,
                'items_count' => $feedPurchaseItems->count(),
                'validation_timestamp' => now()->toISOString(),
            ]
        ];

        Log::info('Stock arrival validation completed', [
            'purchase_id' => $purchase->id,
            'success' => $result['success'],
            'error_count' => count($errors),
            'warning_count' => count($warnings),
        ]);

        return $result;
    }

    /**
     * Process stock arrival with comprehensive validation and transaction safety
     * This method ensures all operations are atomic and can be rolled back if needed
     */
    private function processStockArrivalWithValidation(FeedPurchase $purchase): array
    {
        Log::info('Starting stock arrival processing with validation', [
            'purchase_id' => $purchase->id,
            'invoice_number' => $purchase->invoice_number,
        ]);

        DB::beginTransaction();

        try {
            $feedPurchaseItems = $purchase->feedPurchaseItems;
            $processedItems = [];
            $processingErrors = [];

            foreach ($feedPurchaseItems as $feedPurchaseItem) {
                try {
                    Log::info('Processing individual feed purchase item', [
                        'feed_purchase_item_id' => $feedPurchaseItem->id,
                        'feed_id' => $feedPurchaseItem->feed_id,
                        'livestock_id' => $purchase->livestock_id,
                        'converted_quantity' => $feedPurchaseItem->converted_quantity,
                    ]);

                    $feed = Feed::findOrFail($feedPurchaseItem->feed_id);
                    $livestock = Livestock::findOrFail($purchase->livestock_id);
                    $convertedQuantity = $feedPurchaseItem->converted_quantity;

                    // Validate individual item before processing
                    $itemValidation = $this->validateIndividualItem($feedPurchaseItem, $feed, $livestock);
                    if (!$itemValidation['success']) {
                        $processingErrors[] = "Item #{$feedPurchaseItem->id}: " . implode(', ', $itemValidation['errors']);
                        continue;
                    }

                    // Process FeedStock creation/update
                    $feedStockResult = $this->processFeedStockWithValidation($feedPurchaseItem, $feed, $livestock, $convertedQuantity);
                    if (!$feedStockResult['success']) {
                        $processingErrors[] = "FeedStock processing failed for item #{$feedPurchaseItem->id}: " . $feedStockResult['error'];
                        continue;
                    }

                    // Update CurrentFeed
                    $currentFeedResult = $this->updateCurrentFeedWithValidation($livestock, $feed);
                    if (!$currentFeedResult['success']) {
                        $processingErrors[] = "CurrentFeed update failed for item #{$feedPurchaseItem->id}: " . $currentFeedResult['error'];
                        continue;
                    }

                    $processedItems[] = [
                        'feed_purchase_item_id' => $feedPurchaseItem->id,
                        'feed_id' => $feed->id,
                        'feed_name' => $feed->name,
                        'quantity' => $convertedQuantity,
                        'feed_stock_id' => $feedStockResult['feed_stock_id'] ?? null,
                        'current_feed_id' => $currentFeedResult['current_feed_id'] ?? null,
                    ];

                    Log::info('Successfully processed feed purchase item', [
                        'feed_purchase_item_id' => $feedPurchaseItem->id,
                        'feed_id' => $feed->id,
                        'feed_name' => $feed->name,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error processing individual feed purchase item', [
                        'feed_purchase_item_id' => $feedPurchaseItem->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $processingErrors[] = "Item #{$feedPurchaseItem->id}: " . $e->getMessage();
                }
            }

            // If there are any processing errors, rollback everything
            if (!empty($processingErrors)) {
                DB::rollBack();
                Log::error('Stock arrival processing failed - rolling back all changes', [
                    'purchase_id' => $purchase->id,
                    'processing_errors' => $processingErrors,
                    'items_attempted' => $feedPurchaseItems->count(),
                    'items_successful' => count($processedItems),
                ]);

                return [
                    'success' => false,
                    'errors' => $processingErrors,
                    'items_processed' => 0,
                    'details' => [
                        'purchase_id' => $purchase->id,
                        'rollback_performed' => true,
                        'error_count' => count($processingErrors),
                    ]
                ];
            }

            // All processing successful - commit transaction
            DB::commit();

            Log::info('Stock arrival processing completed successfully', [
                'purchase_id' => $purchase->id,
                'items_processed' => count($processedItems),
                'processed_items' => $processedItems,
            ]);

            return [
                'success' => true,
                'errors' => [],
                'items_processed' => count($processedItems),
                'details' => [
                    'purchase_id' => $purchase->id,
                    'processed_items' => $processedItems,
                    'processing_timestamp' => now()->toISOString(),
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unexpected error during stock arrival processing', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['Unexpected error: ' . $e->getMessage()],
                'items_processed' => 0,
                'details' => [
                    'purchase_id' => $purchase->id,
                    'rollback_performed' => true,
                    'error_type' => 'unexpected',
                ]
            ];
        }
    }

    /**
     * Validate individual feed purchase item before processing
     */
    private function validateIndividualItem($feedPurchaseItem, $feed, $livestock): array
    {
        $errors = [];

        // Validate feed purchase item data
        if (empty($feedPurchaseItem->converted_quantity) || $feedPurchaseItem->converted_quantity <= 0) {
            $errors[] = 'Converted quantity tidak valid';
        }

        if (empty($feedPurchaseItem->unit_id)) {
            $errors[] = 'Unit ID tidak ditemukan';
        }

        // Validate feed data
        if (empty($feed->data['conversion_units'])) {
            $errors[] = 'Feed conversion units tidak ditemukan';
        }

        // Validate livestock data
        if (empty($livestock->farm_id)) {
            $errors[] = 'Livestock farm ID tidak ditemukan';
        }

        if (empty($livestock->coop_id)) {
            $errors[] = 'Livestock coop ID tidak ditemukan';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Process FeedStock with validation and error handling
     */
    private function processFeedStockWithValidation($feedPurchaseItem, $feed, $livestock, $convertedQuantity): array
    {
        try {
            Log::info("Processing FeedStock with validation", [
                'feed_purchase_item_id' => $feedPurchaseItem->id,
                'feed_id' => $feed->id,
                'livestock_id' => $livestock->id,
                'converted_quantity' => $convertedQuantity
            ]);

            // Validate that the FeedPurchaseItem exists
            if (!$feedPurchaseItem || !$feedPurchaseItem->exists) {
                throw new \Exception("FeedPurchaseItem dengan ID {$feedPurchaseItem->id} tidak ditemukan.");
            }

            // Validate foreign key constraint
            $existingFeedPurchaseItem = \App\Models\FeedPurchaseItem::find($feedPurchaseItem->id);
            if (!$existingFeedPurchaseItem) {
                throw new \Exception("FeedPurchaseItem dengan ID {$feedPurchaseItem->id} tidak ditemukan di database.");
            }

            $data = [
                'feed_purchase_id' => $feedPurchaseItem->feedPurchase->id,
                'feed_purchase_number' => $feedPurchaseItem->feedPurchase->number_full,
                'supplier_id' => $feedPurchaseItem->feedPurchase->supplier_id,
                'expedition_id' => $feedPurchaseItem->feedPurchase->expedition_id,
                'expedition_fee' => $feedPurchaseItem->feedPurchase->expedition_fee,
                'date' => $feedPurchaseItem->feedPurchase->date,
                'invoice_number' => $feedPurchaseItem->feedPurchase->invoice_number,
            ];

            $feedStock = FeedStock::updateOrCreate(
                [
                    'livestock_id' => $livestock->id,
                    'feed_id' => $feed->id,
                    'feed_purchase_id' => $feedPurchaseItem->feedPurchase->id,
                ],
                [
                    'date' => $feedPurchaseItem->feedPurchase->date,
                    'source_type' => 'purchase',
                    'source_id' => $feedPurchaseItem->id,
                    'quantity_in' => $convertedQuantity,
                    'data' => $data,
                    'created_by' => Auth::check() ? Auth::id() : null,
                    'updated_by' => Auth::check() ? Auth::id() : null,
                ]
            );

            Log::info("FeedStock processed successfully", [
                'feed_stock_id' => $feedStock->id,
                'feed_purchase_item_id' => $feedPurchaseItem->id,
            ]);

            return [
                'success' => true,
                'feed_stock_id' => $feedStock->id,
            ];
        } catch (\Exception $e) {
            Log::error("FeedStock processing failed", [
                'feed_purchase_item_id' => $feedPurchaseItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update CurrentFeed with validation and error handling
     */
    private function updateCurrentFeedWithValidation($livestock, $feed): array
    {
        try {
            Log::info("[CurrentFeedService] Updating CurrentFeed with validation", [
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->coop_id
            ]);

            // First, ensure CurrentFeed record exists
            $currentFeed = CurrentFeed::firstOrCreate(
                [
                    'livestock_id' => $livestock->id,
                    'feed_id' => $feed->id,
                ],
                [
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id,
                    'unit_id' => $feed->data['conversion_units'][0]['unit_id'] ?? null,
                    'quantity' => 0,
                    'status' => 'active',
                    'created_by' => Auth::check() ? Auth::id() : null,
                    'updated_by' => Auth::check() ? Auth::id() : null,
                ]
            );

            Log::info("[CurrentFeedService] CurrentFeed record ensured", [
                'current_feed_id' => $currentFeed->id,
                'existing_quantity' => $currentFeed->quantity,
            ]);

            // Use service to update quantity
            $service = $this->currentFeedService ?? app(\App\Services\Feed\CurrentFeedService::class);
            $result = $service->updateQuantity([
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
            ]);

            Log::info("[CurrentFeedService] Update result", $result);

            return [
                'success' => true,
                'current_feed_id' => $currentFeed->id,
                'service_result' => $result,
            ];
        } catch (\Exception $e) {
            Log::error("[CurrentFeedService] Error updating CurrentFeed", [
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Convert technical database errors to user-friendly messages
            $userFriendlyError = $this->convertDatabaseErrorToUserMessage($e->getMessage(), $feed, $livestock);

            return [
                'success' => false,
                'error' => $userFriendlyError,
            ];
        }
    }

    /**
     * Process complete stock arrival for a feed purchase
     * This method handles the entire stock arrival process including validation
     */
    private function processStockArrival(FeedPurchase $purchase)
    {
        Log::info('Starting complete stock arrival processing for purchase', [
            'purchase_id' => $purchase->id,
            'invoice_number' => $purchase->invoice_number,
            'livestock_id' => $purchase->livestock_id,
        ]);

        DB::beginTransaction();

        try {
            $feedPurchaseItems = $purchase->feedPurchaseItems;

            if ($feedPurchaseItems->isEmpty()) {
                throw new \Exception('Tidak ada data FeedPurchaseItem yang ditemukan untuk purchase ini.');
            }

            $processedItems = [];

            foreach ($feedPurchaseItems as $feedPurchaseItem) {
                $feed = Feed::findOrFail($feedPurchaseItem->feed_id);
                $livestock = Livestock::findOrFail($purchase->livestock_id);
                $convertedQuantity = $feedPurchaseItem->converted_quantity;

                Log::info('Processing stock arrival for item', [
                    'feed_purchase_item_id' => $feedPurchaseItem->id,
                    'feed_id' => $feed->id,
                    'feed_name' => $feed->name,
                    'livestock_id' => $livestock->id,
                    'converted_quantity' => $convertedQuantity,
                ]);

                // Process FeedStock creation/update
                $this->processFeedStock($feedPurchaseItem, $feed, $livestock, $convertedQuantity);

                // Update CurrentFeed
                $this->updateCurrentFeed($livestock, $feed);

                $processedItems[] = [
                    'feed_purchase_item_id' => $feedPurchaseItem->id,
                    'feed_id' => $feed->id,
                    'feed_name' => $feed->name,
                    'quantity' => $convertedQuantity,
                ];
            }

            DB::commit();

            Log::info('Complete stock arrival processing completed successfully', [
                'purchase_id' => $purchase->id,
                'items_processed' => count($processedItems),
                'processed_items' => $processedItems,
            ]);

            return [
                'success' => true,
                'message' => 'Stock arrival processed successfully',
                'items_processed' => count($processedItems),
                'processed_items' => $processedItems,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process complete stock arrival', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update CurrentFeed for livestock and feed using modular service
     * This method ensures CurrentFeed record exists and is updated with latest quantity
     */
    private function updateCurrentFeed($livestock, $feed)
    {
        Log::info("[CurrentFeedService] Updating CurrentFeed via service", [
            'livestock_id' => $livestock->id,
            'feed_id' => $feed->id,
            'farm_id' => $livestock->farm_id,
            'coop_id' => $livestock->coop_id
        ]);

        try {
            // First, ensure CurrentFeed record exists
            $currentFeed = CurrentFeed::firstOrCreate(
                [
                    'livestock_id' => $livestock->id,
                    'feed_id' => $feed->id,
                ],
                [
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id,
                    'unit_id' => $feed->data['conversion_units'][0]['unit_id'] ?? null,
                    'quantity' => 0,
                    'status' => 'active',
                    'created_by' => Auth::check() ? Auth::id() : null,
                    'updated_by' => Auth::check() ? Auth::id() : null,
                ]
            );

            Log::info("[CurrentFeedService] CurrentFeed record ensured", [
                'current_feed_id' => $currentFeed->id,
                'existing_quantity' => $currentFeed->quantity,
            ]);

            // Use service to update quantity
            $service = $this->currentFeedService ?? app(\App\Services\Feed\CurrentFeedService::class);
            $result = $service->updateQuantity([
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
            ]);

            Log::info("[CurrentFeedService] Update result", $result);
            return $result;
        } catch (\Exception $e) {
            Log::error("[CurrentFeedService] Error updating CurrentFeed", [
                'livestock_id' => $livestock->id,
                'feed_id' => $feed->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Convert technical database errors to user-friendly messages
            $userFriendlyError = $this->convertDatabaseErrorToUserMessage($e->getMessage(), $feed, $livestock);

            // Create a new exception with user-friendly message
            throw new \Exception($userFriendlyError, 0, $e);
        }
    }

    /**
     * Handle real-time status change notifications from broadcasting
     */
    public function handleStatusChanged($event)
    {
        Log::info('Received real-time feed purchase status change notification', [
            'batch_id' => $event['batch_id'] ?? 'unknown',
            'old_status' => $event['old_status'] ?? 'unknown',
            'new_status' => $event['new_status'] ?? 'unknown',
            'updated_by' => $event['updated_by'] ?? 'unknown',
            'current_user' => Auth::check() ? Auth::id() : null
        ]);

        try {
            // Check if this change affects current user's view
            if ($this->shouldRefreshData($event)) {
                $this->dispatch('notify-status-change', [
                    'type' => 'info',
                    'title' => 'Data Update Available',
                    'message' => $event['message'] ?? 'A feed purchase status has been updated.',
                    'requires_refresh' => $event['metadata']['requires_refresh'] ?? false,
                    'priority' => $event['metadata']['priority'] ?? 'normal',
                    'batch_id' => $event['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);

                Log::info('Feed purchase status change notification dispatched to user', [
                    'batch_id' => $event['batch_id'] ?? 'unknown',
                    'user_id' => Auth::check() ? Auth::id() : null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling feed purchase status change notification', [
                'error' => $e->getMessage(),
                'event' => $event,
                'user_id' => Auth::check() ? Auth::id() : null
            ]);
        }
    }

    /**
     * Handle status validation failure - rollback status to previous state
     */
    public function handleStatusValidationFailed($data)
    {
        Log::warning('Status validation failed - rolling back to previous status', [
            'purchase_id' => $data['purchase_id'] ?? 'unknown',
            'old_status' => $data['old_status'] ?? 'unknown',
            'errors' => $data['errors'] ?? [],
            'user_id' => Auth::check() ? Auth::id() : null,
        ]);

        try {
            // Dispatch JavaScript event to rollback status in UI
            $this->dispatch('rollback-status-to-ui', [
                'purchase_id' => $data['purchase_id'],
                'old_status' => $data['old_status'],
                'errors' => $data['errors'],
                'message' => 'Status tidak dapat diubah karena validasi gagal: ' . implode(', ', $data['errors']),
            ]);

            // Show warning notification to user
            $this->dispatch('warning', 'Status tidak dapat diubah: ' . implode(', ', $data['errors']));
        } catch (\Exception $e) {
            Log::error('Error handling status validation failure', [
                'purchase_id' => $data['purchase_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle status processing failure - rollback status to previous state
     */
    public function handleStatusProcessingFailed($data)
    {
        Log::error('Status processing failed - rolling back to previous status', [
            'purchase_id' => $data['purchase_id'] ?? 'unknown',
            'old_status' => $data['old_status'] ?? 'unknown',
            'errors' => $data['errors'] ?? [],
            'user_id' => Auth::check() ? Auth::id() : null,
        ]);

        try {
            // Dispatch JavaScript event to rollback status in UI
            $this->dispatch('rollback-status-to-ui', [
                'purchase_id' => $data['purchase_id'],
                'old_status' => $data['old_status'],
                'errors' => $data['errors'],
                'message' => 'Status tidak dapat diubah karena proses gagal: ' . implode(', ', $data['errors']),
            ]);

            // Show warning notification to user
            $this->dispatch('warning', 'Proses stock arrival gagal: ' . implode(', ', $data['errors']));
        } catch (\Exception $e) {
            Log::error('Error handling status processing failure', [
                'purchase_id' => $data['purchase_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle unexpected error during status change - rollback status to previous state
     */
    public function handleStatusUnexpectedError($data)
    {
        Log::error('Unexpected error during status change - rolling back to previous status', [
            'purchase_id' => $data['purchase_id'] ?? 'unknown',
            'old_status' => $data['old_status'] ?? 'unknown',
            'error' => $data['error'] ?? 'unknown',
            'user_id' => Auth::check() ? Auth::id() : null,
        ]);

        try {
            // Dispatch JavaScript event to rollback status in UI
            $this->dispatch('rollback-status-to-ui', [
                'purchase_id' => $data['purchase_id'],
                'old_status' => $data['old_status'],
                'errors' => [$data['error']],
                'message' => 'Terjadi kesalahan tidak terduga: ' . $data['error'],
            ]);

            // Show warning notification to user
            $this->dispatch('warning', 'Terjadi kesalahan tidak terduga: ' . $data['error']);
        } catch (\Exception $e) {
            Log::error('Error handling unexpected error during status change', [
                'purchase_id' => $data['purchase_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Convert technical database errors to user-friendly messages
     */
    private function convertDatabaseErrorToUserMessage(string $errorMessage, $feed, $livestock): string
    {
        // Log the original error for debugging
        Log::debug('Converting database error to user message', [
            'original_error' => $errorMessage,
            'feed_id' => $feed->id ?? 'unknown',
            'feed_name' => $feed->name ?? 'unknown',
            'livestock_id' => $livestock->id ?? 'unknown',
            'livestock_name' => $livestock->name ?? 'unknown',
        ]);

        // Check for specific database errors and convert them
        if (str_contains($errorMessage, "Field 'farm_id' doesn't have a default value")) {
            return "Data farm tidak ditemukan untuk batch ayam '{$livestock->name}'. Silakan periksa data batch ayam atau hubungi administrator.";
        }

        if (str_contains($errorMessage, "Field 'coop_id' doesn't have a default value")) {
            return "Data kandang tidak ditemukan untuk batch ayam '{$livestock->name}'. Silakan periksa data batch ayam atau hubungi administrator.";
        }

        if (str_contains($errorMessage, "Field 'unit_id' doesn't have a default value")) {
            return "Data satuan tidak ditemukan untuk pakan '{$feed->name}'. Silakan periksa data pakan atau hubungi administrator.";
        }

        if (str_contains($errorMessage, "foreign key constraint fails")) {
            return "Data referensi tidak valid. Silakan periksa data batch ayam, pakan, atau supplier yang terkait.";
        }

        if (str_contains($errorMessage, "duplicate entry")) {
            return "Data sudah ada dalam sistem. Silakan periksa data yang dimasukkan.";
        }

        if (str_contains($errorMessage, "cannot be null")) {
            return "Beberapa data wajib tidak boleh kosong. Silakan periksa kembali data yang dimasukkan.";
        }

        if (str_contains($errorMessage, "SQLSTATE[HY000]: General error: 1364")) {
            return "Data tidak lengkap untuk pakan '{$feed->name}' dan batch ayam '{$livestock->name}'. Silakan periksa data atau hubungi administrator.";
        }

        // Default user-friendly message for unknown errors
        return "Terjadi kesalahan saat memproses data pakan '{$feed->name}' untuk batch ayam '{$livestock->name}'. Silakan coba lagi atau hubungi administrator jika masalah berlanjut.";
    }

    /**
     * Handle user-specific notifications
     */
    public function handleUserNotification($notification)
    {
        Log::info('Received user-specific feed purchase notification', [
            'notification_type' => $notification['type'] ?? 'unknown',
            'user_id' => Auth::check() ? Auth::id() : null
        ]);

        try {
            if (isset($notification['type']) && $notification['type'] === 'feed_purchase_status_changed') {
                $this->dispatch('notify-status-change', [
                    'type' => $this->getNotificationType($notification['priority'] ?? 'normal'),
                    'title' => $notification['title'] ?? 'Feed Purchase Update',
                    'message' => $notification['message'] ?? 'A feed purchase has been updated.',
                    'requires_refresh' => in_array('refresh_data', $notification['action_required'] ?? []),
                    'priority' => $notification['priority'] ?? 'normal',
                    'batch_id' => $notification['batch_id'] ?? null,
                    'show_refresh_button' => true
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling feed purchase user notification', [
                'error' => $e->getMessage(),
                'notification' => $notification,
                'user_id' => Auth::check() ? Auth::id() : null
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
            case 'arrived':
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
        $statusLabels = FeedPurchase::STATUS_LABELS;
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        return sprintf(
            'Feed Purchase #%s status changed from %s to %s by %s',
            $purchase->invoice_number,
            $oldLabel,
            $newLabel,
            (Auth::check() && Auth::user() ? Auth::user()->name : 'Unknown')
        );
    }

    /**
     * Check if status change requires page refresh
     */
    private function requiresRefresh(string $oldStatus, string $newStatus): bool
    {
        $criticalChanges = [
            'draft' => ['arrived', 'confirmed'],
            'confirmed' => ['arrived', 'cancelled'],
            'arrived' => ['completed', 'cancelled'],
            'pending' => ['arrived', 'cancelled'],
        ];

        return isset($criticalChanges[$oldStatus]) &&
            in_array($newStatus, $criticalChanges[$oldStatus]);
    }

    /**
     * Get notification priority
     */
    private function getPriority(string $oldStatus, string $newStatus): string
    {
        if ($newStatus === 'arrived') return 'high';
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
            $cacheKey = "sse_notification_debounce_feed_{$purchase->id}_{$notificationData['new_status']}";

            if (Cache::has($cacheKey)) {
                Log::info('SSE feed purchase notification debounced (too frequent)', [
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
                'type' => 'feed_purchase_status_changed',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'source' => 'livewire_sse',
                'priority' => $notificationData['priority'] ?? 'normal',
                'data' => [
                    'batch_id' => $purchase->id,
                    'invoice_number' => $purchase->invoice_number,
                    'updated_by' => Auth::check() ? Auth::id() : null,
                    'updated_by_name' => Auth::check() && Auth::user() ? Auth::user()->name : null,
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
                Log::info('Successfully stored feed purchase notification for SSE bridge', [
                    'batch_id' => $purchase->id,
                    'notification_id' => $result['id'],
                    'updated_by' => Auth::check() ? Auth::id() : null,
                    'sse_system' => 'active'
                ]);
            } else {
                Log::warning('Failed to store SSE feed purchase notification after retries', [
                    'batch_id' => $purchase->id,
                    'updated_by' => Auth::check() ? Auth::id() : null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing feed purchase notification for SSE bridge', [
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

                    Log::warning('Could not acquire file lock for SSE feed purchase notification', [
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

                Log::info('SSE feed purchase notification stored successfully', [
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

                Log::error('Error storing SSE feed purchase notification', [
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
     * Change detection for FeedPurchases
     */
    private function checkDataChanges(): bool
    {
        if (empty($this->pembelianId)) {
            return true;
        }
        $current = $this->getFormDataForComparison();
        $original = $this->getOriginalDataForComparison();
        if (class_exists('App\\Helpers\\DataChangeDetector')) {
            return \App\Helpers\DataChangeDetector::formHasChanges($current, $original, []);
        }
        return json_encode($current) !== json_encode($original);
    }

    private function getFormDataForComparison(): array
    {
        $normalizedItems = $this->normalizeFeedItems($this->items ?? []);
        // Normalize date to date-only (Y-m-d) to avoid false changes on time component differences
        $normalizedDate = null;
        try {
            if ($this->date instanceof \Carbon\Carbon) {
                $normalizedDate = $this->date->format('Y-m-d');
            } elseif (is_string($this->date) && trim($this->date) !== '') {
                $normalizedDate = \Carbon\Carbon::parse($this->date)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            $normalizedDate = $this->date; // fallback
        }

        return [
            'invoice_number' => $this->invoice_number,
            'date' => $normalizedDate ?? $this->date,
            'supplier_id' => $this->supplier_id,
            'expedition_id' => $this->expedition_id ?? null,
            'expedition_fee' => (float) ($this->expedition_fee ?? 0),
            'livestock_id' => $this->livestock_id,
            'items' => $normalizedItems,
        ];
    }

    private function getOriginalDataForComparison(): array
    {
        if (empty($this->pembelianId)) {
            return [];
        }
        $purchase = \App\Models\FeedPurchase::with('feedPurchaseItems')
            ->find($this->pembelianId);
        if (!$purchase) {
            return [];
        }
        $items = [];
        foreach ($purchase->feedPurchaseItems as $pi) {
            $items[] = [
                'feed_id' => $pi->feed_id,
                'unit_id' => $pi->unit_id,
                'quantity' => (float) $pi->quantity,
                'price_per_unit' => (float) $pi->price_per_unit,
            ];
        }
        return [
            'invoice_number' => $purchase->invoice_number,
            'date' => optional($purchase->date)->format('Y-m-d') ?? $purchase->date,
            'supplier_id' => $purchase->supplier_id,
            'expedition_id' => $purchase->expedition_id,
            'expedition_fee' => (float) ($purchase->expedition_fee ?? 0),
            'livestock_id' => $purchase->livestock_id,
            'items' => $this->normalizeFeedItems($items),
        ];
    }

    private function normalizeFeedItems(array $items): array
    {
        $normalized = array_map(function ($item) {
            return [
                'feed_id' => $item['feed_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? ($item['unit'] ?? null),
                'quantity' => isset($item['quantity']) ? (float) $item['quantity'] : null,
                'price_per_unit' => isset($item['price_per_unit']) ? (float) $item['price_per_unit'] : null,
            ];
        }, $items);
        usort($normalized, function ($a, $b) {
            return strcmp(($a['feed_id'] . '-' . $a['unit_id']), ($b['feed_id'] . '-' . $b['unit_id']));
        });
        return $normalized;
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
     * Method untuk force update kalkulasi semua item
     */
    public function recalculateAllItems()
    {
        foreach ($this->items as $index => $item) {
            if (isset($item['quantity']) && isset($item['price_per_unit'])) {
                $this->items[$index]['sub_total'] = $item['quantity'] * $item['price_per_unit'];
            }
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
     * Kalkulasi total keseluruhan
     */
    public function calculateTotal()
    {
        $total = 0;
        $discount = 0;

        foreach ($this->items as $index => $item) {
            if (isset($item['quantity']) && isset($item['price_per_unit'])) {
                $this->items[$index]['sub_total'] = $item['quantity'] * $item['price_per_unit'];
                $total += $this->items[$index]['sub_total'];
            }
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

        if (isset($this->items[$index]['quantity']) && isset($this->items[$index]['price_per_unit'])) {
            $this->items[$index]['sub_total'] = $this->items[$index]['quantity'] * $this->items[$index]['price_per_unit'];
        }

        return $this->items[$index]['sub_total'] ?? 0;
    }
}
