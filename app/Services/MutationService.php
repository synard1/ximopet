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

use App\Models\FeedPurchase;

class MutationService
{

    public static function feedMutation(array $data, array $items, ?string $mutationId = null): Mutation
    {
        return DB::transaction(function () use ($data, $items, $mutationId) {
            $isUpdate = !empty($mutationId);

            // Cek apakah mutation sudah ada
            $mutation = $isUpdate
                ? Mutation::findOrFail($mutationId)
                : new Mutation();

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
            } else {
                $mutation->update();
                // Jika update: hapus item lama dan stock lama di tujuan
                $oldMutationItems = MutationItem::where('mutation_id', $mutation->id)->get();

                // Hapus stock yang dibuat dari mutasi ini di tujuan
                foreach ($oldMutationItems as $oldItem) {
                    FeedStock::where('source_id', $mutation->id)
                        ->where('source_type', 'mutation')
                        ->delete();
                }

                // Kembalikan quantity_mutated di source
                foreach ($oldMutationItems as $oldItem) {
                    $sourceStock = FeedStock::find($oldItem->stock_id);
                    if ($sourceStock) {
                        $sourceStock->quantity_mutated -= $oldItem->quantity;
                        $sourceStock->save();

                        // Update CurrentSupply untuk source
                        self::updateCurrentSupply(
                            livestockId: $sourceStock->livestock_id,
                            itemId: $sourceStock->feed_id,
                            type: 'feed'
                        );
                    }
                }

                // Hapus mutation items
                MutationItem::where('mutation_id', $mutation->id)->delete();
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
            }

            // Update mutation payload with metadata
            $mutation->payload = [
                'items_metadata' => $itemsMetadata,
                'source_livestock_name' => Livestock::find($data['source_livestock_id'])?->name,
                'destination_livestock_name' => Livestock::find($data['destination_livestock_id'])?->name,
            ];
            $mutation->save();

            // Mutasi item dengan menyimpan data unit lengkap
            self::mutateFeedItems(
                items: $items,
                sourceLivestockId: $mutation->from_livestock_id,
                targetLivestockId: $mutation->to_livestock_id,
                mutationId: $mutation->id,
                date: $mutation->date,
                dryRun: false
            );

            return $mutation;
        });
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

                // Get feed item for conversion information
                $feed = Feed::findOrFail($itemId);
                $conversionUnits = collect($feed->payload['conversion_units'] ?? []);

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

                        // Update CurrentSupply untuk source dan target
                        self::updateCurrentSupply(
                            livestockId: $sourceLivestockId,
                            itemId: $itemId,
                            type: 'feed'
                        );

                        self::updateCurrentSupply(
                            livestockId: $targetLivestockId,
                            itemId: $itemId,
                            type: 'feed'
                        );
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
                            // 'available' => $takeQtySmallest,
                            // 'amount' => $takeQtySmallest * ($stockSource->amount / $stockSource->quantity_in),
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
}
