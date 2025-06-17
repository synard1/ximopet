<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Feed;
use App\Models\Supply;
use App\Models\CurrentFeed;
use App\Models\CurrentSupply;
use App\Models\Farm;
use App\Models\FeedMutationItem;
use App\Models\FeedStock;
use App\Models\Livestock;
use App\Models\SupplyMutationItem;
use App\Models\Mutation;
use App\Models\MutationItem;
use App\Models\SupplyStock;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\ItemConversionService;
use Exception;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FeedPurchase;
use App\Models\CurrentLivestock;
use App\Models\LivestockBatch;
use App\Events\LivestockMutated;

class MutationService
{

    public static function feedMutation(array $data, array $items, ?string $mutationId = null, bool $withHistory = false): Mutation
    {
        return DB::transaction(function () use ($data, $items, $mutationId, $withHistory) {
            Log::info('Starting feed mutation process', ['mutationId' => $mutationId, 'data' => $data, 'items' => $items]);

            $isUpdate = !empty($mutationId);

            // Cek apakah mutation sudah ada
            $mutation = $isUpdate
                ? Mutation::findOrFail($mutationId)
                : new Mutation();

            Log::info('Mutation found or created', ['mutationId' => $mutation->id, 'isUpdate' => $isUpdate]);

            // Update atau isi data mutation
            $mutation->fill([
                'id' => $mutationId ?? Str::uuid(),
                'type' => 'feed',
                'mutation_scope' => 'internal', // Default scope for feed mutations
                'date' => $data['date'],
                'from_livestock_id' => $data['source_livestock_id'],
                'to_livestock_id' => $data['destination_livestock_id'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if (!$isUpdate) {
                $mutation->save();
                Log::info('New mutation saved', ['mutationId' => $mutation->id]);
            } else {
                $mutation->update();
                Log::info('Mutation updated', ['mutationId' => $mutation->id]);

                // Jika update: hapus item lama dan stock lama di tujuan
                $oldMutationItems = MutationItem::where('mutation_id', $mutation->id)->get();

                Log::info('Fetching old mutation items', ['mutationId' => $mutation->id, 'oldMutationItemsCount' => $oldMutationItems->count()]);

                // Hapus stock yang dibuat dari mutasi ini di tujuan
                foreach ($oldMutationItems as $oldItem) {
                    FeedStock::where('source_id', $mutation->id)
                        ->where('source_type', 'mutation')
                        ->delete();
                    Log::info('Deleted old stock from mutation', ['mutationId' => $mutation->id, 'oldItemId' => $oldItem->id]);
                }

                // Kembalikan quantity_mutated di source
                foreach ($oldMutationItems as $oldItem) {
                    $sourceStock = FeedStock::find($oldItem->stock_id);
                    if ($sourceStock) {
                        $sourceStock->quantity_mutated -= $oldItem->quantity;
                        $sourceStock->save();
                        Log::info('Adjusted source stock quantity_mutated', ['sourceStockId' => $sourceStock->id, 'mutationId' => $mutation->id]);

                        // Update CurrentSupply untuk source
                        self::updateCurrentFeed(
                            livestockId: $sourceStock->livestock_id,
                            itemId: $sourceStock->feed_id,
                            type: 'feed'
                        );
                        Log::info('Updated CurrentSupply for source', ['livestockId' => $sourceStock->livestock_id, 'itemId' => $sourceStock->feed_id]);
                    }
                }

                if ($withHistory) {
                    // Hapus mutation items
                    MutationItem::where('mutation_id', $mutation->id)->delete();
                    Log::info('Deleted old mutation items', ['mutationId' => $mutation->id]);
                } else {
                    // Hapus mutation items
                    MutationItem::where('mutation_id', $mutation->id)->forceDelete();
                    Log::info('Deleted old mutation items no history', ['mutationId' => $mutation->id]);
                }
            }

            // Tambahkan metadata tentang unit-unit yang digunakan di payload
            $itemsMetadata = [];
            foreach ($items as $index => $item) {
                // Get feed item with its conversion information
                $feed = Feed::with('unit')->findOrFail($item['item_id']);
                $unitId = $item['unit_id'];
                $unit = Unit::find($unitId);

                // Get conversion units from feed payload
                $conversionUnits = collect($feed->payload['conversion_units'] ?? []);

                // Find the specific unit conversion data
                $unitConversion = $conversionUnits->firstWhere('unit_id', $unitId);

                // Store metadata including unit and conversion info
                $itemsMetadata[] = [
                    'item_id' => $item['item_id'],
                    'item_name' => $feed->name,
                    'quantity' => $item['quantity'],
                    'type' => $item['type'],
                    'unit_id' => $unitId,
                    'unit_name' => $unit ? $unit->name : null,
                    'conversion_details' => $unitConversion,
                    'original_input' => [
                        'quantity' => $item['quantity'],
                        'unit_id' => $unitId,
                    ],
                ];
                Log::info('Processed item metadata', ['itemId' => $item['item_id'], 'unitId' => $unitId]);
            }

            // Update mutation payload with metadata
            $mutation->payload = [
                'items_metadata' => $itemsMetadata,
                'source_livestock_name' => Livestock::find($data['source_livestock_id'])?->name,
                'destination_livestock_name' => Livestock::find($data['destination_livestock_id'])?->name,
            ];
            $mutation->save();
            Log::info('Mutation payload updated with metadata', ['mutationId' => $mutation->id]);

            // Mutasi item dengan menyimpan data unit lengkap
            self::mutateFeedItems(
                items: $items,
                sourceLivestockId: $mutation->from_livestock_id,
                targetLivestockId: $mutation->to_livestock_id,
                mutationId: $mutation->id,
                date: $mutation->date,
                dryRun: false
            );
            Log::info('Feed items mutation process started', ['mutationId' => $mutation->id, 'sourceLivestockId' => $mutation->from_livestock_id, 'targetLivestockId' => $mutation->to_livestock_id]);

            return $mutation;
        });
    }

    /**
     * Update CurrentFeed record for a specific item
     */
    private static function updateCurrentFeed(string $livestockId, string $itemId, string $type = 'feed'): void
    {

        // dd($livestockId);
        // $livestock = Livestock::findOrFail($livestockId);
        $model = $type === 'feed' ? FeedStock::class : SupplyStock::class;
        $itemField = $type === 'feed' ? 'feed_id' : 'supply_id';

        // Calculate current quantity
        $totalQuantity = $model::where('livestock_id', $livestockId)
            ->where($itemField, $itemId)
            ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
            ->value('total');

        // Get item details
        $item = $type === 'feed' ? Feed::find($itemId) : Supply::find($itemId);
        if (!$item) return;

        // Update or create CurrentSupply record
        CurrentSupply::updateOrCreate(
            [
                'livestock_id' => $livestockId,
                'item_id' => $itemId,
                'type' => $type
            ],
            [
                'unit_id' => $item->payload['unit_id'] ?? null,
                'quantity' => $totalQuantity,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );
    }

    /**
     * Update CurrentSupply record for a specific item
     */
    private static function updateCurrentSupply(string $livestockId, string $itemId, string $type = 'supply'): void
    {

        // dd($livestockId);
        // $livestock = Livestock::findOrFail($livestockId);
        $model = $type === 'feed' ? FeedStock::class : SupplyStock::class;
        $itemField = $type === 'feed' ? 'feed_id' : 'supply_id';

        // Calculate current quantity
        $totalQuantity = $model::where('farm_id', $livestockId)
            ->where($itemField, $itemId)
            ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
            ->value('total');

        // dd([
        //     'model' => $model,
        //     'itemField' => $itemField,
        //     'totalQuantity' => $totalQuantity,
        //     'livestockId' => $livestockId,
        //     'itemId' => $itemId,
        //     'type' => $type,
        //     'farm_id' => $livestockId,

        // ]);

        // Get item details
        $item = $type === 'feed' ? Feed::find($itemId) : Supply::find($itemId);
        if (!$item) return;

        // Update or create CurrentSupply record
        CurrentSupply::updateOrCreate(
            [
                // 'livestock_id' => $livestockId,
                'farm_id' => $livestockId,
                // 'kandang_id' => $livestock->kandang_id,
                'item_id' => $itemId,
                'type' => $type
            ],
            [
                'unit_id' => $item->payload['unit_id'] ?? null,
                'quantity' => $totalQuantity,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );
    }

    public static function mutateFeedItems(array $items, string $sourceLivestockId, string $targetLivestockId, string $mutationId, string $date, bool $dryRun = false): void
    {
        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $type = $item['type']; // e.g., feed, supply, vitamin, medicine
                $itemId = $item['item_id'];
                $unitId = $item['unit_id'];
                $inputQty = $item['quantity'];
                $farm = Livestock::find($sourceLivestockId);
                $targetFarm = Livestock::find($targetLivestockId);

                if (!$farm || !$targetFarm) {
                    throw new \Exception("Farm asal atau tujuan tidak ditemukan.");
                }

                // Log the start of processing for each item
                Log::info('Starting processing for item', ['type' => $type, 'item_id' => $itemId, 'unit_id' => $unitId, 'inputQty' => $inputQty]);

                // Get feed item for conversion information
                $feed = Feed::findOrFail($itemId);
                $conversionUnits = collect($feed->payload['conversion_units'] ?? []);

                // Log the retrieval of feed item conversion units
                Log::info('Retrieved conversion units for feed item', ['feed_id' => $itemId, 'conversion_units_count' => $conversionUnits->count()]);

                // Get the conversion unit details
                $inputUnit = $conversionUnits->firstWhere('unit_id', $unitId);
                $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

                if (!$inputUnit || !$smallestUnit) {
                    throw new \Exception("Unit conversion information not found for feed: {$feed->name}");
                }

                // Calculate conversion values
                $inputUnitValue = floatval($inputUnit['value']);
                $smallestUnitValue = floatval($smallestUnit['value']);

                // Convert from input unit to smallest unit
                $requiredQtySmallest = ($inputQty * $inputUnitValue) / $smallestUnitValue;

                // Ambil model stok (Current*) dari farm asal
                $stockQuerySource = FeedStock::where('livestock_id', $sourceLivestockId)
                    ->where('feed_id', $itemId);

                $stocksSource = $stockQuerySource->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                // Log the retrieval of source stocks
                Log::info('Retrieved source stocks for feed item', ['feed_id' => $itemId, 'source_stocks_count' => $stocksSource->count()]);

                $remainingRequiredQtySmallest = $requiredQtySmallest;

                foreach ($stocksSource as $stockSource) {
                    if ($remainingRequiredQtySmallest <= 0) {
                        break;
                    }

                    $availableSource = $stockSource->quantity_in - $stockSource->quantity_used - $stockSource->quantity_mutated;
                    $takeQtySmallest = min($availableSource, $remainingRequiredQtySmallest);

                    if (!$dryRun) {
                        // Mutasi stok asal
                        $stockSource->quantity_mutated += $takeQtySmallest;
                        $stockSource->save();

                        // Log the mutation of source stock
                        Log::info('Mutated source stock', ['stock_id' => $stockSource->id, 'quantity_mutated' => $takeQtySmallest]);

                        // Get source purchase details for accurate unit tracking
                        $sourcePurchase = null;
                        if ($stockSource->feed_purchase_id) {
                            $sourcePurchase = FeedPurchase::find($stockSource->feed_purchase_id);
                        }

                        // Create target stock with unit information
                        $stockData = [
                            'id' => Str::uuid(),
                            'livestock_id' => $targetLivestockId,
                            'feed_id' => $itemId,
                            'feed_purchase_id' => $stockSource->feed_purchase_id,
                            'date' => $date,
                            'source_type' => 'mutation',
                            'source_id' => $mutationId,
                            'quantity_in' => $takeQtySmallest,
                            'quantity_used' => 0,
                            'quantity_mutated' => 0,
                            'available' => $takeQtySmallest,
                            'created_by' => auth()->id(),
                        ];

                        // If we have purchase information, add accurate unit and amount data
                        if ($sourcePurchase) {
                            $proportionalAmount = ($takeQtySmallest / $stockSource->quantity_in) * $stockSource->amount;
                            $stockData['amount'] = $proportionalAmount;

                            // Store unit information from the original purchase
                            $stockData['original_unit_id'] = $sourcePurchase->unit_id;
                            $stockData['converted_unit_id'] = $sourcePurchase->converted_unit;
                            $stockData['unit_metadata'] = [
                                'purchase_unit_id' => $sourcePurchase->unit_id,
                                'purchase_unit_name' => Unit::find($sourcePurchase->unit_id)?->name,
                                'converted_unit_id' => $sourcePurchase->converted_unit,
                                'converted_unit_name' => Unit::find($sourcePurchase->converted_unit)?->name,
                                'input_unit_id' => $unitId,
                                'input_unit_name' => Unit::find($unitId)?->name,
                                'conversion_rate' => $inputUnitValue / $smallestUnitValue,
                            ];
                        } else {
                            // If no purchase info, estimate based on source stock
                            $stockData['amount'] = ($takeQtySmallest / $stockSource->quantity_in) * ($stockSource->amount ?? 0);
                        }

                        FeedStock::create($stockData);

                        // Log the creation of target stock
                        Log::info('Created target stock', ['stock_id' => $stockData['id'], 'quantity_in' => $takeQtySmallest]);

                        // Catat mutation_items dengan detail unit
                        MutationItem::create([
                            'id' => Str::uuid(),
                            'mutation_id' => $mutationId,
                            'item_type' => $type,
                            'item_id' => $itemId,
                            'stock_id' => $stockSource->id,
                            'quantity' => $takeQtySmallest,
                            'unit_id' => $smallestUnit['unit_id'], // Store in smallest unit
                            'source_unit_id' => $unitId, // Original input unit
                            'conversion_rate' => $inputUnitValue / $smallestUnitValue, // Store conversion rate
                            'unit_metadata' => [
                                'input_unit_id' => $unitId,
                                'input_quantity' => $inputQty,
                                'smallest_unit_id' => $smallestUnit['unit_id'],
                                'smallest_quantity' => $takeQtySmallest,
                                'conversion_rate' => $inputUnitValue / $smallestUnitValue,
                            ],
                            'created_by' => auth()->id(),
                        ]);

                        // Log the creation of mutation item
                        Log::info('Created mutation item', ['mutation_item_id' => $stockData['id'], 'quantity' => $takeQtySmallest]);

                        // Update CurrentSupply untuk source dan target
                        self::updateCurrentFeed(
                            livestockId: $sourceLivestockId,
                            itemId: $itemId,
                            type: 'feed'
                        );

                        // Log the update of CurrentSupply for source and target
                        Log::info('Updated CurrentFeed for source and target', ['source_livestock_id' => $sourceLivestockId, 'target_livestock_id' => $targetLivestockId, 'item_id' => $itemId]);
                    }

                    $remainingRequiredQtySmallest -= $takeQtySmallest;
                }

                if ($remainingRequiredQtySmallest > 0) {
                    // Convert back to input units for user-friendly message
                    $remainingInputQty = ($remainingRequiredQtySmallest * $smallestUnitValue) / $inputUnitValue;
                    $unitName = Unit::find($unitId)?->name ?? '';

                    throw new \Exception("Stok tidak cukup untuk {$type}: {$feed->name}. Kekurangan: " .
                        number_format($remainingInputQty, 2) . " {$unitName}");
                }
            }

            if (!$dryRun) {
                DB::commit();
            } else {
                DB::rollBack(); // Rollback jika dry run
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function supplyMutation(array $data, array $items, ?string $mutationId = null): Mutation
    {
        // dd($data);

        return DB::transaction(function () use ($data, $items, $mutationId) {
            $isUpdate = !empty($mutationId);

            // Cek apakah mutation sudah ada
            $mutation = $isUpdate
                ? Mutation::findOrFail($mutationId)
                : new Mutation();

            // Update atau isi data mutation
            $mutation->fill([
                'id' => $mutationId ?? Str::uuid(),
                'type' => 'supply',
                'date' => $data['date'],
                'from_farm_id' => $data['source_farm_id'],
                'to_farm_id' => $data['destination_farm_id'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);


            if (!$isUpdate) {
                $mutation->save();
            } else {
                $mutation->update();
                // Jika update: hapus item lama
                MutationItem::where('mutation_id', $mutation->id)->delete();
            }

            // dd($mutation);

            // Mutasi item
            self::mutateSupplyItems(
                items: $items,
                sourceFarmId: $mutation->from_farm_id,
                targetFarmId: $mutation->to_farm_id,
                mutationId: $mutation->id,
                date: $mutation->date,
                dryRun: false
            );

            return $mutation;
        });
    }

    /**
     * Mutasi item supply dari satu farm ke farm lain.
     *
     * @param array  $items        Array detail item mutasi.
     * @param string $sourceFarmId ID farm asal.
     * @param string $targetFarmId ID farm tujuan.
     * @param string $mutationId   ID mutasi.
     * @param string $date         Tanggal mutasi.
     * @param bool   $dryRun       Apakah ini percobaan (tidak menyimpan perubahan).
     * @return void
     * @throws \Exception Jika stok tidak mencukupi.
     */
    public static function mutateSupplyItems(array $items, string $sourceFarmId, string $targetFarmId, string $mutationId, string $date, bool $dryRun = false): void
    {
        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $type = $item['type']; // e.g., feed, supply, vitamin, medicine
                $itemId = $item['item_id'];
                $unitId = $item['unit_id'];
                $inputQty = $item['quantity'];
                $farm = Farm::find($sourceFarmId);
                $targetFarm = Farm::find($targetFarmId);

                if (!$farm || !$targetFarm) {
                    throw new \Exception("Farm asal atau tujuan tidak ditemukan.");
                }

                // Konversi ke satuan terkecil
                $requiredQtySmallest = ItemConversionService::toSmallest($type, $itemId, $unitId, $inputQty);

                // Ambil model stok (Current*) dari farm asal
                $stockQuerySource = match ($type) {
                    'feed' => FeedStock::where('farm_id', $sourceFarmId)->where('feed_id', $itemId),
                    default => SupplyStock::where('farm_id', $sourceFarmId)->where('supply_id', $itemId),
                };

                $stocksSource = $stockQuerySource->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                $remainingRequiredQtySmallest = $requiredQtySmallest;

                foreach ($stocksSource as $stockSource) {
                    if ($remainingRequiredQtySmallest <= 0) {
                        break;
                    }

                    $availableSource = $stockSource->quantity_in - $stockSource->quantity_used - $stockSource->quantity_mutated;
                    $takeQtySmallest = min($availableSource, $remainingRequiredQtySmallest);

                    if (!$dryRun) {
                        // Mutasi stok asal
                        $stockSource->quantity_mutated += $takeQtySmallest;
                        $stockSource->save();

                        // Tambah stok ke farm tujuan
                        $targetModel = $type === 'feed' ? FeedStock::class : SupplyStock::class;
                        $stockField = $type === 'feed' ? 'feed_id' : 'supply_id';
                        $purchaseField = $type === 'feed' ? 'feed_purchase_id' : 'supply_purchase_id';
                        $purchaseValue = $type === 'feed' ? $stockSource->feed_purchase_id : $stockSource->supply_purchase_id;

                        // Get supply and unit information for metadata
                        $supply = Supply::findOrFail($itemId);
                        $unit = Unit::find($unitId);
                        $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                        $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

                        $targetModel::create([
                            'id' => Str::uuid(),
                            'farm_id' => $targetFarmId,
                            $stockField => $itemId,
                            $purchaseField => $purchaseValue,
                            'date' => $date,
                            'source_type' => 'mutation',
                            'source_id' => $mutationId,
                            'quantity_in' => $takeQtySmallest,
                            'quantity_used' => 0,
                            'quantity_mutated' => 0,
                            'unit_metadata' => [
                                'input_unit_id' => $unitId,
                                'input_quantity' => $inputQty,
                                'smallest_unit_id' => $smallestUnit['unit_id'],
                                'smallest_quantity' => $takeQtySmallest,
                                'conversion_rate' => $unit ? $unit->value / $smallestUnit['value'] : 1,
                            ],
                            'created_by' => auth()->id(),
                        ]);

                        // Catat mutation_items
                        MutationItem::create([
                            'id' => Str::uuid(),
                            'mutation_id' => $mutationId,
                            'item_type' => $type,
                            'item_id' => $itemId,
                            'stock_id' => $stockSource->id,
                            'quantity' => $takeQtySmallest,
                            'unit_id' => $smallestUnit['unit_id'],
                            'unit_metadata' => [
                                'input_unit_id' => $unitId,
                                'input_quantity' => $inputQty,
                                'smallest_unit_id' => $smallestUnit['unit_id'],
                                'smallest_quantity' => $takeQtySmallest,
                                'conversion_rate' => $unit ? $unit->value / $smallestUnit['value'] : 1,
                            ],
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $remainingRequiredQtySmallest -= $takeQtySmallest;
                }

                if ($remainingRequiredQtySmallest > 0) {
                    // Fix: Use supply's conversion units to convert remaining quantity back to input unit
                    $supply = Supply::findOrFail($itemId);
                    $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                    $inputUnit = $conversionUnits->firstWhere('unit_id', $unitId);
                    $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

                    if ($inputUnit && $smallestUnit && $smallestUnit['value'] > 0) {
                        $remainingInputQty = ($remainingRequiredQtySmallest * $smallestUnit['value']) / $inputUnit['value'];
                        $unitName = Unit::find($unitId)?->name ?? '';

                        throw new \Exception("Stok tidak cukup untuk {$type}: {$supply->name}. Kekurangan: " . number_format($remainingInputQty, 2) . " {$unitName}");
                    } else {
                        // Fallback error if conversion data is missing
                        throw new \Exception("Stok tidak cukup untuk {$type}: {$supply->name}. Informasi konversi unit tidak lengkap.");
                    }
                }

                // Update CurrentSupply for source and target farms after processing the item
                self::updateCurrentSupply(
                    livestockId: $sourceFarmId, // Note: updateCurrentSupply uses livestock_id, but supply mutations are farm-based. Need to clarify or adjust.
                    itemId: $itemId,
                    type: 'supply'
                );

                self::updateCurrentSupply(
                    livestockId: $targetFarmId, // Note: updateCurrentSupply uses livestock_id, but supply mutations are farm-based. Need to clarify or adjust.
                    itemId: $itemId,
                    type: 'supply'
                );
            }

            if (!$dryRun) {
                DB::commit();
            } else {
                DB::rollBack(); // Rollback jika dry run
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function delete_mutation($mutationId)
    {
        DB::beginTransaction();
        try {
            // Load mutation with related data
            $mutation = Mutation::with(['mutationItems.item', 'fromLivestock', 'toLivestock'])
                ->findOrFail($mutationId);

            // Validate if mutation can be deleted
            foreach ($mutation->mutationItems as $item) {
                if (!$item->item) continue;

                // Check source livestock stock
                $sourceStock = FeedStock::where('livestock_id', $mutation->from_livestock_id)
                    ->where('feed_id', $item->item_id)
                    ->first();

                if ($sourceStock) {
                    // Check if stock has been used
                    if ($sourceStock->quantity_used > 0) {
                        throw new \Exception("Stok di {$mutation->fromLivestock->name} sudah digunakan");
                    }
                    // Check if stock has been mutated
                    if ($sourceStock->quantity_mutated < $item->quantity) {
                        throw new \Exception("Stok di {$mutation->fromLivestock->name} sudah dimutasi");
                    }
                }

                // Check destination livestock stock
                $destStock = FeedStock::where('livestock_id', $mutation->to_livestock_id)
                    ->where('feed_id', $item->item_id)
                    ->first();

                if ($destStock) {
                    // Check if stock has been used
                    if ($destStock->quantity_used > 0) {
                        throw new \Exception("Stok di {$mutation->toLivestock->name} sudah digunakan");
                    }
                    // Check if stock has been mutated
                    if ($destStock->quantity_mutated > 0) {
                        throw new \Exception("Stok di {$mutation->toLivestock->name} sudah dimutasi");
                    }
                }
            }

            // Update source stock
            foreach ($mutation->mutationItems as $item) {
                if (!$item->item) continue;

                $sourceStock = FeedStock::where('livestock_id', $mutation->from_livestock_id)
                    ->where('feed_id', $item->item_id)
                    ->first();

                if ($sourceStock) {
                    $sourceStock->quantity_mutated -= $item->quantity;
                    $sourceStock->available += $item->quantity;
                    $sourceStock->save();

                    // Update CurrentSupply for source
                    self::updateCurrentSupply(
                        livestockId: $mutation->from_livestock_id,
                        itemId: $item->item_id,
                        type: 'feed'
                    );
                }

                // Delete destination stock
                FeedStock::where('livestock_id', $mutation->to_livestock_id)
                    ->where('feed_id', $item->item_id)
                    ->delete();

                // Delete CurrentSupply for destination
                CurrentSupply::where('livestock_id', $mutation->to_livestock_id)
                    ->where('item_id', $item->item_id)
                    ->where('type', 'feed')
                    ->delete();
            }

            // Delete mutation items
            $mutation->mutationItems()->delete();

            // Delete mutation
            $mutation->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convert a quantity from input unit to smallest unit using feed's conversion units.
     * 
     * @param string $feedId The ID of the feed item
     * @param string $inputUnitId The ID of the input unit
     * @param float $inputQuantity The quantity in input unit
     * @return array Returns an array containing:
     *               - smallest_quantity: The converted quantity in smallest unit
     *               - smallest_unit_id: The ID of the smallest unit
     *               - conversion_rate: The rate used for conversion
     * @throws \Exception If conversion information is not found or invalid
     */
    public static function convertToSmallestUnit(string $feedId, string $inputUnitId, float $inputQuantity): array
    {
        // Get feed item for conversion information
        $feed = Feed::findOrFail($feedId);
        $conversionUnits = collect($feed->payload['conversion_units'] ?? []);

        // Get the conversion unit details
        $inputUnit = $conversionUnits->firstWhere('unit_id', $inputUnitId);
        $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

        if (!$inputUnit || !$smallestUnit) {
            throw new \Exception("Unit conversion information not found for feed: {$feed->name}");
        }

        // Calculate conversion values
        $inputUnitValue = floatval($inputUnit['value']);
        $smallestUnitValue = floatval($smallestUnit['value']);

        if ($smallestUnitValue <= 0) {
            throw new \Exception("Invalid smallest unit value for feed: {$feed->name}");
        }

        // Convert from input unit to smallest unit
        $smallestQuantity = ($inputQuantity * $inputUnitValue) / $smallestUnitValue;

        return [
            'smallest_quantity' => $smallestQuantity,
            'smallest_unit_id' => $smallestUnit['unit_id'],
            'conversion_rate' => $inputUnitValue / $smallestUnitValue,
            'input_unit_value' => $inputUnitValue,
            'smallest_unit_value' => $smallestUnitValue,
        ];
    }

    /**
     * Create or update a feed mutation with history control.
     * If withHistory is true: Soft delete old data and create new data
     * If withHistory is false: Update existing mutation items without deleting
     */
    public static function feedMutationWithHistoryControl(array $data, array $items, ?string $mutationId = null, bool $withHistory = true): Mutation
    {
        return DB::transaction(function () use ($data, $items, $mutationId, $withHistory) {
            Log::info('Starting feed mutation with history control process', [
                'mutationId' => $mutationId,
                'withHistory' => $withHistory,
                'data' => $data,
                'items' => $items
            ]);

            $isUpdate = !empty($mutationId);

            // Find or create mutation
            $mutation = $isUpdate
                ? Mutation::findOrFail($mutationId)
                : new Mutation();

            // Fill mutation data
            $mutation->fill([
                'id' => $mutationId ?? Str::uuid(),
                'type' => 'feed',
                'mutation_scope' => 'internal',
                'date' => $data['date'],
                'from_livestock_id' => $data['source_livestock_id'],
                'to_livestock_id' => $data['destination_livestock_id'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if (!$isUpdate) {
                $mutation->save();
                Log::info('New mutation created', ['mutationId' => $mutation->id]);
            } else {
                $mutation->update();
                Log::info('Existing mutation updated', ['mutationId' => $mutation->id]);

                // Handle old mutation items based on history preference
                $oldMutationItems = MutationItem::where('mutation_id', $mutation->id)->get();

                if ($withHistory) {
                    // Soft delete old items and create new ones
                    foreach ($oldMutationItems as $oldItem) {
                        // Clean up old stocks
                        FeedStock::where('source_id', $mutation->id)
                            ->where('source_type', 'mutation')
                            ->delete();

                        // Update source stock
                        $sourceStock = FeedStock::find($oldItem->stock_id);
                        if ($sourceStock) {
                            $sourceStock->quantity_mutated -= $oldItem->quantity;
                            $sourceStock->save();
                            self::updateCurrentFeed(
                                livestockId: $sourceStock->livestock_id,
                                itemId: $sourceStock->feed_id,
                                type: 'feed'
                            );
                        }

                        // Soft delete the mutation item
                        $oldItem->delete();
                        Log::info('Soft deleted mutation item', ['itemId' => $oldItem->id]);
                    }
                } else {
                    // Update existing items without deleting
                    foreach ($oldMutationItems as $oldItem) {
                        // Find matching new item data
                        $newItemData = collect($items)->first(function ($item) use ($oldItem) {
                            return $item['item_id'] === $oldItem->item_id;
                        });

                        if ($newItemData) {
                            try {
                                // Convert to smallest unit
                                $conversion = self::convertToSmallestUnit(
                                    $newItemData['item_id'],
                                    $newItemData['unit_id'],
                                    $newItemData['quantity']
                                );

                                // Update existing mutation item
                                $oldItem->update([
                                    'quantity' => $conversion['smallest_quantity'],
                                    'unit_id' => $conversion['smallest_unit_id'],
                                    'unit_metadata' => [
                                        'input_unit_id' => $newItemData['unit_id'],
                                        'input_quantity' => $newItemData['quantity'],
                                        'smallest_unit_id' => $conversion['smallest_unit_id'],
                                        'smallest_quantity' => $conversion['smallest_quantity'],
                                        'conversion_rate' => $conversion['conversion_rate'],
                                        'updated_at' => now(),
                                    ],
                                    'updated_by' => auth()->id(),
                                ]);

                                // Update source stock
                                $sourceStock = FeedStock::find($oldItem->stock_id);
                                if ($sourceStock) {
                                    $sourceStock->quantity_mutated = $sourceStock->quantity_mutated - $oldItem->getOriginal('quantity') + $conversion['smallest_quantity'];
                                    $sourceStock->save();
                                    self::updateCurrentFeed(
                                        livestockId: $sourceStock->livestock_id,
                                        itemId: $sourceStock->feed_id,
                                        type: 'feed'
                                    );
                                }

                                Log::info('Updated existing mutation item', [
                                    'itemId' => $oldItem->id,
                                    'conversion' => $conversion
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Error updating mutation item', [
                                    'itemId' => $oldItem->id,
                                    'error' => $e->getMessage()
                                ]);
                                throw $e;
                            }
                        }
                    }
                }
            }

            // Add metadata to payload
            $itemsMetadata = [];
            foreach ($items as $index => $item) {
                try {
                    $feed = Feed::with('unit')->findOrFail($item['item_id']);
                    $unitId = $item['unit_id'];
                    $unit = Unit::find($unitId);

                    // Convert to smallest unit for metadata
                    $conversion = self::convertToSmallestUnit(
                        $item['item_id'],
                        $unitId,
                        $item['quantity']
                    );

                    $itemsMetadata[] = [
                        'item_id' => $item['item_id'],
                        'item_name' => $feed->name,
                        'quantity' => $item['quantity'],
                        'type' => $item['type'],
                        'unit_id' => $unitId,
                        'unit_name' => $unit ? $unit->name : null,
                        'conversion_details' => [
                            'input_unit' => [
                                'id' => $unitId,
                                'name' => $unit ? $unit->name : null,
                                'value' => $conversion['input_unit_value'],
                            ],
                            'smallest_unit' => [
                                'id' => $conversion['smallest_unit_id'],
                                'value' => $conversion['smallest_unit_value'],
                            ],
                            'conversion_rate' => $conversion['conversion_rate'],
                            'smallest_quantity' => $conversion['smallest_quantity'],
                        ],
                        'original_input' => [
                            'quantity' => $item['quantity'],
                            'unit_id' => $unitId,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing item metadata', [
                        'itemId' => $item['item_id'],
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            $mutation->payload = [
                'items_metadata' => $itemsMetadata,
                'source_livestock_name' => Livestock::find($data['source_livestock_id'])?->name,
                'destination_livestock_name' => Livestock::find($data['destination_livestock_id'])?->name,
            ];
            $mutation->save();

            // Only mutate feed items if we're creating new ones or if withHistory is true
            if (!$isUpdate || $withHistory) {
                self::mutateFeedItems(
                    items: $items,
                    sourceLivestockId: $mutation->from_livestock_id,
                    targetLivestockId: $mutation->to_livestock_id,
                    mutationId: $mutation->id,
                    date: $mutation->date,
                    dryRun: false
                );
            }

            return $mutation;
        });
    }

    /**
     * Create or update a supply mutation with history control.
     * If withHistory is true: Soft delete old data and create new data
     * If withHistory is false: Update existing mutation items without deleting
     */
    public static function supplyMutationWithHistoryControl(array $data, array $items, ?string $mutationId = null, bool $withHistory = true): Mutation
    {
        return DB::transaction(function () use ($data, $items, $mutationId, $withHistory) {
            Log::info('Starting supply mutation with history control process', [
                'mutationId' => $mutationId,
                'withHistory' => $withHistory,
                'data' => $data,
                'items' => $items
            ]);

            $isUpdate = !empty($mutationId);

            // Find or create mutation
            $mutation = $isUpdate
                ? Mutation::findOrFail($mutationId)
                : new Mutation();

            // Fill mutation data
            $mutation->fill([
                'id' => $mutationId ?? Str::uuid(),
                'type' => 'supply',
                'mutation_scope' => 'internal',
                'date' => $data['date'],
                'from_farm_id' => $data['source_farm_id'],
                'to_farm_id' => $data['destination_farm_id'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if (!$isUpdate) {
                $mutation->save();
                Log::info('New supply mutation created', ['mutationId' => $mutation->id]);
            } else {
                $mutation->update();
                Log::info('Existing supply mutation updated', ['mutationId' => $mutation->id]);

                // Handle old mutation items based on history preference
                $oldMutationItems = MutationItem::where('mutation_id', $mutation->id)->get();

                if ($withHistory) {
                    // Soft delete old items and create new ones
                    foreach ($oldMutationItems as $oldItem) {
                        // Clean up old stocks
                        SupplyStock::where('source_id', $mutation->id)
                            ->where('source_type', 'mutation')
                            ->delete();

                        // Update source stock
                        $sourceStock = SupplyStock::find($oldItem->stock_id);
                        if ($sourceStock) {
                            $sourceStock->quantity_mutated -= $oldItem->quantity;
                            $sourceStock->save();
                            self::updateCurrentSupply(
                                livestockId: $sourceStock->farm_id,
                                itemId: $sourceStock->supply_id,
                                type: 'supply'
                            );
                        }

                        // Soft delete the mutation item
                        $oldItem->delete();
                        Log::info('Soft deleted supply mutation item', ['itemId' => $oldItem->id]);
                    }
                } else {
                    // Update existing items without deleting
                    foreach ($oldMutationItems as $oldItem) {
                        // Find matching new item data
                        $newItemData = collect($items)->first(function ($item) use ($oldItem) {
                            return $item['item_id'] === $oldItem->item_id;
                        });

                        if ($newItemData) {
                            try {
                                // Convert to smallest unit using ItemConversionService
                                $smallestQuantity = ItemConversionService::toSmallest(
                                    'supply',
                                    $newItemData['item_id'],
                                    $newItemData['unit_id'],
                                    $newItemData['quantity']
                                );

                                // Get supply and unit information for metadata
                                $supply = Supply::findOrFail($newItemData['item_id']);
                                $unit = Unit::find($newItemData['unit_id']);
                                $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                                $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

                                // Update existing mutation item
                                $oldItem->update([
                                    'quantity' => $smallestQuantity,
                                    'unit_id' => $smallestUnit['unit_id'],
                                    'unit_metadata' => [
                                        'input_unit_id' => $newItemData['unit_id'],
                                        'input_quantity' => $newItemData['quantity'],
                                        'smallest_unit_id' => $smallestUnit['unit_id'],
                                        'smallest_quantity' => $smallestQuantity,
                                        'conversion_rate' => $unit ? $unit->value / $smallestUnit['value'] : 1,
                                        'updated_at' => now(),
                                    ],
                                    'updated_by' => auth()->id(),
                                ]);

                                // Update source stock
                                $sourceStock = SupplyStock::find($oldItem->stock_id);
                                if ($sourceStock) {
                                    $sourceStock->quantity_mutated = $sourceStock->quantity_mutated - $oldItem->getOriginal('quantity') + $smallestQuantity;
                                    $sourceStock->save();
                                    self::updateCurrentSupply(
                                        livestockId: $sourceStock->farm_id,
                                        itemId: $sourceStock->supply_id,
                                        type: 'supply'
                                    );
                                }

                                Log::info('Updated existing supply mutation item', [
                                    'itemId' => $oldItem->id,
                                    'smallestQuantity' => $smallestQuantity
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Error updating supply mutation item', [
                                    'itemId' => $oldItem->id,
                                    'error' => $e->getMessage()
                                ]);
                                throw $e;
                            }
                        }
                    }
                }
            }

            // Add metadata to payload
            $itemsMetadata = [];
            foreach ($items as $index => $item) {
                try {
                    $supply = Supply::with('unit')->findOrFail($item['item_id']);
                    $unitId = $item['unit_id'];
                    $unit = Unit::find($unitId);

                    // Convert to smallest unit using ItemConversionService
                    $smallestQuantity = ItemConversionService::toSmallest(
                        'supply',
                        $item['item_id'],
                        $unitId,
                        $item['quantity']
                    );

                    // Get conversion details
                    $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                    $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);

                    $itemsMetadata[] = [
                        'item_id' => $item['item_id'],
                        'item_name' => $supply->name,
                        'quantity' => $item['quantity'],
                        'type' => $item['type'],
                        'unit_id' => $unitId,
                        'unit_name' => $unit ? $unit->name : null,
                        'conversion_details' => [
                            'input_unit' => [
                                'id' => $unitId,
                                'name' => $unit ? $unit->name : null,
                                'value' => $unit ? $unit->value : 1,
                            ],
                            'smallest_unit' => [
                                'id' => $smallestUnit['unit_id'],
                                'value' => $smallestUnit['value'],
                            ],
                            'conversion_rate' => $unit ? $unit->value / $smallestUnit['value'] : 1,
                            'smallest_quantity' => $smallestQuantity,
                        ],
                        'original_input' => [
                            'quantity' => $item['quantity'],
                            'unit_id' => $unitId,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing supply item metadata', [
                        'itemId' => $item['item_id'],
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            $mutation->payload = [
                'items_metadata' => $itemsMetadata,
                'source_farm_name' => Farm::find($data['source_farm_id'])?->name,
                'destination_farm_name' => Farm::find($data['destination_farm_id'])?->name,
            ];
            $mutation->save();

            // Only mutate supply items if we're creating new ones or if withHistory is true
            if (!$isUpdate || $withHistory) {
                self::mutateSupplyItems(
                    items: $items,
                    sourceFarmId: $mutation->from_farm_id,
                    targetFarmId: $mutation->to_farm_id,
                    mutationId: $mutation->id,
                    date: $mutation->date,
                    dryRun: false
                );
            }

            return $mutation;
        });
    }

    public static function livestockMutation(array $data, array $items, ?string $mutationId = null, bool $withHistory = true): Mutation
    {
        return DB::transaction(function () use ($data, $items, $mutationId, $withHistory) {
            // Get company mutation config
            $company = auth()->user()->company;
            $mutationConfig = $company->getMutationConfig();
            $livestockConfig = $mutationConfig['livestock_mutation'];
            $notificationConfig = $company->getNotificationConfig();

            Log::info('Starting livestock mutation process', [
                'mutationId' => $mutationId,
                'withHistory' => $withHistory,
                'data' => $data,
                'items' => $items,
                'config' => [
                    'mutation' => $livestockConfig,
                    'notification' => $notificationConfig
                ]
            ]);

            $isUpdate = !empty($mutationId);

            // Find or create mutation
            $mutation = $isUpdate
                ? Mutation::findOrFail($mutationId)
                : new Mutation();

            // Fill mutation data
            $mutation->fill([
                'id' => $mutationId ?? Str::uuid(),
                'type' => 'livestock',
                'mutation_scope' => 'internal',
                'date' => $data['date'],
                'from_livestock_id' => $data['source_livestock_id'],
                'to_livestock_id' => $data['destination_livestock_id'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if (!$isUpdate) {
                $mutation->save();
                Log::info('New livestock mutation created', ['mutationId' => $mutation->id]);
            } else {
                $mutation->update();
                Log::info('Existing livestock mutation updated', ['mutationId' => $mutation->id]);

                // Handle old mutation items based on history preference
                $oldMutationItems = MutationItem::where('mutation_id', $mutation->id)->get();

                if ($withHistory) {
                    // Soft delete old items
                    foreach ($oldMutationItems as $oldItem) {
                        // Update source livestock
                        $sourceLivestock = Livestock::find($oldItem->item_id);
                        if ($sourceLivestock) {
                            $sourceLivestock->quantity_mutated -= $oldItem->quantity;
                            $sourceLivestock->save();
                        }

                        // Update destination livestock
                        $destLivestock = Livestock::find($mutation->to_livestock_id);
                        if ($destLivestock) {
                            $destLivestock->quantity_mutated -= $oldItem->quantity;
                            $destLivestock->save();
                        }

                        // Soft delete the mutation item
                        $oldItem->delete();
                        Log::info('Soft deleted livestock mutation item', ['itemId' => $oldItem->id]);
                    }
                }
            }

            // Process items based on configuration
            foreach ($items as $item) {
                // Validate based on config
                if ($livestockConfig['validation_rules']['require_weight'] && empty($item['weight'])) {
                    throw new \Exception('Berat harus diisi');
                }

                if ($livestockConfig['validation_rules']['require_quantity'] && empty($item['quantity'])) {
                    throw new \Exception('Jumlah harus diisi');
                }

                // Create mutation item
                $mutationItem = MutationItem::create([
                    'id' => Str::uuid(),
                    'mutation_id' => $mutation->id,
                    'item_type' => 'livestock',
                    'item_id' => $item['livestock_id'],
                    'quantity' => $item['quantity'],
                    'weight' => $item['weight'],
                    'created_by' => auth()->id(),
                ]);

                // Handle batch if enabled
                if ($livestockConfig['type'] === 'batch' && $livestockConfig['batch_settings']['tracking_enabled']) {
                    $mutationItem->batch_id = $item['batch_id'] ?? null;
                    $mutationItem->batch_metadata = $item['batch_metadata'] ?? null;
                    $mutationItem->save();

                    // Update batch records
                    if ($item['batch_id']) {
                        $sourceBatch = LivestockBatch::find($item['batch_id']);
                        if ($sourceBatch) {
                            $sourceBatch->quantity_mutated += $item['quantity'];
                            $sourceBatch->save();
                        }
                    }
                }

                // Handle FIFO if enabled
                if ($livestockConfig['type'] === 'fifo' && $livestockConfig['fifo_settings']['enabled']) {
                    // Get oldest batch first
                    $oldestBatch = LivestockBatch::where('livestock_id', $item['livestock_id'])
                        ->where('status', 'active')
                        ->orderBy('entry_date', 'asc')
                        ->first();

                    if ($oldestBatch) {
                        $mutationItem->batch_id = $oldestBatch->id;
                        $mutationItem->batch_metadata = [
                            'entry_date' => $oldestBatch->entry_date,
                            'age' => $oldestBatch->age,
                            'batch_number' => $oldestBatch->batch_number
                        ];
                        $mutationItem->save();

                        $oldestBatch->quantity_mutated += $item['quantity'];
                        $oldestBatch->save();
                    }
                }

                // Update CurrentLivestock records
                $sourceCurrent = CurrentLivestock::where('livestock_id', $item['livestock_id'])
                    ->where('status', 'active')
                    ->first();
                if ($sourceCurrent) {
                    $sourceCurrent->quantity -= $item['quantity'];
                    $sourceCurrent->weight_total -= $item['weight'];
                    $sourceCurrent->save();
                }

                $destCurrent = CurrentLivestock::where('livestock_id', $mutation->to_livestock_id)
                    ->where('status', 'active')
                    ->first();
                if ($destCurrent) {
                    $destCurrent->quantity += $item['quantity'];
                    $destCurrent->weight_total += $item['weight'];
                    $destCurrent->save();
                }

                // Handle notifications if enabled
                if ($notificationConfig['events']['mutation']['enabled']) {
                    $channels = $notificationConfig['events']['mutation']['channels'];
                    if (in_array('broadcast', $channels)) {
                        event(new LivestockMutated($mutation, $mutationItem));
                    }
                    // Handle other notification channels...
                }
            }

            return $mutation;
        });
    }
}
