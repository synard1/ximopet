<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mutation;
use App\Models\Unit;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class MutationController extends Controller
{
    public function getMutationDetails($mutationId)
    {
        try {
            // Eager load only the relationships that definitely exist
            $mutation = Mutation::with([
                'mutationItems',
                'fromLivestock',
                'toLivestock',
                'fromFarm',
                'toFarm',
            ])->findOrFail($mutationId);

            // Determine source and destination based on mutation type
            $from = '—';
            $to = '—';

            if ($mutation->type === 'supply') {
                // For supply mutations, use farm names
                $from = $mutation->fromFarm->name ?? '—';
                $to = $mutation->toFarm->name ?? '—';
            } else {
                // For other types (like feed), use livestock names
                $from = $mutation->fromLivestock->name ?? '—';
                $to = $mutation->toLivestock->name ?? '—';
            }

            // Get mutation metadata from payload if available
            $payload = $mutation->payload ?? [];

            // Process items with careful handling of relationships
            $items = $mutation->mutationItems->map(function ($item) {
                $itemName = '-';
                $itemCode = '-';
                $sourceUnitName = '-';
                $targetUnitName = '-';
                $conversionRate = 1;
                $originalQuantity = $item->quantity;
                $convertedQuantity = $item->quantity;
                $purchaseDate = now()->format('Y-m-d');
                $purchasePrice = 0;

                // Get item details based on type
                if ($item->item_type === 'feed') {
                    // Find the item directly - don't rely on relationship that might not be properly defined
                    $feed = \App\Models\Feed::find($item->item_id);
                    if ($feed) {
                        $itemName = $feed->name ?? '-';
                        $itemCode = $feed->code ?? '-';
                    }

                    // Get stock record directly
                    $stockRecord = \App\Models\FeedStock::find($item->stock_id);
                    if ($stockRecord) {
                        $purchaseDate = $stockRecord->date ?? now()->format('Y-m-d');

                        // Get purchase record directly
                        $purchaseId = $stockRecord->feed_purchase_id;
                        if ($purchaseId) {
                            $purchase = \App\Models\FeedPurchase::find($purchaseId);
                            if ($purchase) {
                                $purchasePrice = $purchase->price_per_converted_unit ?? 0;

                                // Get unit information
                                if ($purchase->unit_id) {
                                    $sourceUnit = \App\Models\Unit::find($purchase->unit_id);
                                    $sourceUnitName = $sourceUnit->name ?? '-';
                                }

                                if ($purchase->converted_unit) {
                                    $targetUnit = \App\Models\Unit::find($purchase->converted_unit);
                                    $targetUnitName = $targetUnit->name ?? '-';
                                }
                            }
                        }
                    }
                } elseif ($item->item_type === 'supply') {
                    // Find the item directly
                    $supply = \App\Models\Supply::find($item->item_id);
                    if ($supply) {
                        $itemName = $supply->name ?? '-';
                        $itemCode = $supply->code ?? '-';
                    }

                    // Get stock record directly
                    $stockRecord = \App\Models\SupplyStock::find($item->stock_id);
                    if ($stockRecord) {
                        $purchaseDate = $stockRecord->date ?? now()->format('Y-m-d');

                        // Get purchase record directly
                        $purchaseId = $stockRecord->supply_purchase_id;
                        if ($purchaseId) {
                            $purchase = \App\Models\SupplyPurchase::find($purchaseId);
                            if ($purchase) {
                                $purchasePrice = $purchase->price_per_converted_unit ?? 0;

                                // Get unit information
                                if ($purchase->unit_id) {
                                    $sourceUnit = \App\Models\Unit::find($purchase->unit_id);
                                    $sourceUnitName = $sourceUnit->name ?? '-';
                                }

                                if ($purchase->converted_unit) {
                                    $targetUnit = \App\Models\Unit::find($purchase->converted_unit);
                                    $targetUnitName = $targetUnit->name ?? '-';
                                }
                            }
                        }
                    }
                }

                // Get unit information from the item itself
                if (empty($targetUnitName) && $item->unit_id) {
                    $unit = \App\Models\Unit::find($item->unit_id);
                    $targetUnitName = $unit->name ?? '-';
                }

                // Get conversion rate
                $conversionRate = $item->conversion_rate ?? 1;

                // Get original quantity from metadata
                $unitMetadata = $item->unit_metadata ?? [];
                if (!empty($unitMetadata) && isset($unitMetadata['input_quantity'])) {
                    $originalQuantity = $unitMetadata['input_quantity'];
                }

                return [
                    'purchase_date' => $purchaseDate,
                    'purchase_price' => $purchasePrice,
                    'type' => $item->item_type,
                    'item_name' => $itemName,
                    'item_code' => $itemCode,
                    'quantity' => $convertedQuantity, // The quantity in smallest/converted unit
                    'original_quantity' => $originalQuantity, // The quantity in original input unit
                    'source_unit' => $sourceUnitName, // Original input unit
                    'target_unit' => $targetUnitName, // Converted/smallest unit
                    'conversion_rate' => $conversionRate,
                    'unit_metadata' => $unitMetadata, // Raw metadata for advanced usage
                ];
            });

            return [
                'id' => $mutation->id,
                'type' => $mutation->type,
                'scope' => $mutation->mutation_scope ?? 'internal',
                'date' => $mutation->date->format('Y-m-d'),
                'from' => $from,
                'to' => $to,
                'notes' => $mutation->notes,
                'payload' => $payload,
                'items' => $items,
            ];
        } catch (ModelNotFoundException $e) {
            Log::warning("Mutation not found: $mutationId");
            return response()->json(['error' => 'Mutation not found'], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching mutation details: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to retrieve mutation details: ' . $e->getMessage()], 500);
        }
    }
    // public function getMutationDetails($mutationId)
    // {
    //     try {
    //         $mutation = Mutation::with([
    //             'mutationItems',
    //             'fromLivestock',
    //             'toLivestock',
    //             'fromFarm',
    //             'toFarm',
    //         ])->findOrFail($mutationId);

    //         // Determine source and destination based on mutation type
    //         $from = '—';
    //         $to = '—';

    //         if ($mutation->type === 'supply') {
    //             // For supply mutations, use farm names
    //             $from = $mutation->fromFarm->name ?? '—';
    //             $to = $mutation->toFarm->name ?? '—';
    //         } else {
    //             // For other types (like feed), use livestock names
    //             $from = $mutation->fromLivestock->name ?? '—';
    //             $to = $mutation->toLivestock->name ?? '—';
    //         }

    //         return [
    //             'id' => $mutation->id,
    //             'type' => $mutation->type,
    //             'scope' => $mutation->mutation_scope,
    //             'date' => $mutation->date->format('Y-m-d'),
    //             'from' => $from,
    //             'to' => $to,
    //             'notes' => $mutation->notes,
    //             'items' => $mutation->mutationItems->map(function ($item) {
    //                 $itemName = '-';

    //                 if ($item->item_type === 'feed') {
    //                     $itemName = $item->item->name;
    //                 } elseif ($item->item_type === 'supply') {
    //                     $itemName = $item->item->name;
    //                 }
    //                 // Tambahkan kondisi lain untuk jenis item lainnya

    //                 // dd($item->stocks->supplyPurchase);

    //                 return [
    //                     'purchase_date' => $item->stocks->date,
    //                     'purchase_price' => $item->stocks->feedPurchase->price_per_converted_unit ??
    //                         ($item->stocks->supplyPurchase->price_per_converted_unit ?? 0),
    //                     'type' => $item->item_type,
    //                     'item_name' => $itemName,
    //                     'quantity' => $item->quantity,
    //                 ];
    //             }),
    //         ];
    //     } catch (ModelNotFoundException $e) {
    //         Log::warning("Mutation not found: $mutationId");
    //         return response()->json(['error' => 'Mutation not found'], 404);
    //     } catch (\Exception $e) {
    //         Log::error("Error fetching mutation details: " . $e->getMessage());
    //         return response()->json(['error' => 'Failed to retrieve mutation details'], 500);
    //     }
    // }
}
