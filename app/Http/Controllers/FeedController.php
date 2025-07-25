<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\FeedStock;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedPurchase;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Mutation;
use App\Models\MutationItem;

class FeedController extends Controller
{

    public function getFeedPurchaseBatchDetail($batchId)
    {
        $feedPurchases = FeedPurchase::with([
            'feedItem:id,code,name,data',
            'feedStocks',
            'unit'
        ])
            ->where('feed_purchase_batch_id', $batchId)
            ->get(['id', 'feed_purchase_batch_id', 'feed_id', 'quantity', 'price_per_unit', 'unit_id', 'converted_unit', 'price_per_converted_unit', 'converted_quantity']);

        $formatted = $feedPurchases->map(function ($item) {
            $feedItem = optional($item->feedItem);

            // Get proper conversion units from feed payload
            $conversionUnits = collect($feedItem->data['conversion_units'] ?? []);

            // Get the purchase unit and converted (smallest) unit information
            $purchaseUnitId = $item->unit_id;
            $convertedUnitId = $item->converted_unit;

            $purchaseUnit = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
            $smallestUnit = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
                $conversionUnits->firstWhere('is_smallest', true);

            // Original quantity in purchase units
            $quantity = floatval($item->quantity);

            // Separate stocks into direct and mutation-derived
            $directStocks = $item->feedStocks->filter(function ($stock) {
                return $stock->feed_purchase_id != null && $stock->source_type != 'mutation';
            });

            $mutationDerivedStocks = $item->feedStocks->filter(function ($stock) {
                return $stock->source_type == 'mutation';
            });

            // Calculate usage from direct stocks (for sisa calculation)
            $directUsedSmallestUnits = $directStocks->sum('quantity_used');
            $directMutatedSmallestUnits = $directStocks->sum('quantity_mutated');
            $directAvailableSmallestUnits = $directStocks->sum('available');

            // Calculate usage from mutation-derived stocks (for total usage reporting)
            $mutationUsedSmallestUnits = $mutationDerivedStocks->sum('quantity_used');
            $mutationMutatedSmallestUnits = $mutationDerivedStocks->sum('quantity_mutated');

            // Total usage (from both direct and mutation-derived)
            $totalUsedSmallestUnits = $directUsedSmallestUnits + $mutationUsedSmallestUnits;
            $totalMutatedSmallestUnits = $directMutatedSmallestUnits + $mutationMutatedSmallestUnits;

            // If we have proper conversion units in payload, use them
            if ($purchaseUnit && $smallestUnit) {
                // Get conversion values
                $purchaseUnitValue = floatval($purchaseUnit['value']);
                $smallestUnitValue = floatval($smallestUnit['value']);

                // Converted quantity in smallest units
                $convertedQuantity = ($quantity * $purchaseUnitValue) / $smallestUnitValue;

                // Convert direct usage to purchase units (for sisa calculation)
                $directUsedPurchaseUnits = ($directUsedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;
                $directMutatedPurchaseUnits = ($directMutatedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;
                $directAvailablePurchaseUnits = ($directAvailableSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;

                // Convert total usage to purchase units (for display)
                $totalUsedPurchaseUnits = ($totalUsedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;
                $totalMutatedPurchaseUnits = ($totalMutatedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;

                // Calculate remaining based on direct usage only (to avoid double counting)
                $sisaPurchaseUnits = max(0, $quantity - $directUsedPurchaseUnits - $directMutatedPurchaseUnits);

                return [
                    'id' => $item->id,
                    'kode' => $feedItem->code,
                    'name' => $feedItem->name,
                    'quantity' => $quantity,
                    'converted_quantity' => $convertedQuantity,
                    'sisa' => round($sisaPurchaseUnits, 2),
                    'unit' => $item->unit->name ?? '-',
                    'unit_conversion' => $smallestUnit['label'] ?? ($feedItem->payload['unit_details']['name'] ?? '-'),
                    'conversion' => $purchaseUnitValue / $smallestUnitValue,
                    'price_per_unit' => floatval($item->price_per_unit),
                    'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
                        ? floatval($quantity * $item->price_per_unit)
                        : intval($quantity * $item->price_per_unit),
                    'terpakai' => round($directUsedPurchaseUnits, 2),             // Direct usage only (for calculations)
                    'mutated' => round($directMutatedPurchaseUnits, 2),           // Direct mutations only (for calculations)
                    'available' => round($directAvailablePurchaseUnits, 2),       // Direct available only (for calculations)
                    'total_terpakai' => round($totalUsedPurchaseUnits, 2),        // Total usage including from mutations (for reporting)
                    'total_mutated' => round($totalMutatedPurchaseUnits, 2),      // Total mutations including from mutations (for reporting)
                    // A breakdown to help troubleshoot the calculations
                    'debug' => [
                        'direct_used_smallest' => $directUsedSmallestUnits,
                        'direct_mutated_smallest' => $directMutatedSmallestUnits,
                        'mutation_used_smallest' => $mutationUsedSmallestUnits,
                        'mutation_mutated_smallest' => $mutationMutatedSmallestUnits,
                        'purchase_unit_value' => $purchaseUnitValue,
                        'smallest_unit_value' => $smallestUnitValue,
                    ]
                ];
            } else {
                // Legacy fallback - simple conversion based on the old 'conversion' field
                $conversionFactor = floatval($feedItem->conversion) ?: 1;

                $convertedQuantity = $quantity * $conversionFactor;

                // Convert direct usage to purchase units (for sisa calculation)
                $directUsedPurchaseUnits = $directUsedSmallestUnits / $conversionFactor;
                $directMutatedPurchaseUnits = $directMutatedSmallestUnits / $conversionFactor;
                $directAvailablePurchaseUnits = $directAvailableSmallestUnits / $conversionFactor;

                // Convert total usage to purchase units (for display)
                $totalUsedPurchaseUnits = $totalUsedSmallestUnits / $conversionFactor;
                $totalMutatedPurchaseUnits = $totalMutatedSmallestUnits / $conversionFactor;

                // Calculate remaining based on direct usage only (to avoid double counting)
                $sisaPurchaseUnits = max(0, $quantity - $directUsedPurchaseUnits - $directMutatedPurchaseUnits);

                return [
                    'id' => $item->id,
                    'kode' => $feedItem->code,
                    'name' => $feedItem->name,
                    'quantity' => $quantity,
                    'converted_quantity' => $convertedQuantity,
                    'sisa' => round($sisaPurchaseUnits, 2),
                    'unit' => $item->unit->name ?? '-',
                    'unit_conversion' => $feedItem->payload['unit_details']['name'] ?? '-',
                    'conversion' => $conversionFactor,
                    'price_per_unit' => floatval($item->price_per_unit),
                    'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
                        ? floatval($quantity * $item->price_per_unit)
                        : intval($quantity * $item->price_per_unit),
                    'terpakai' => round($directUsedPurchaseUnits, 2),             // Direct usage only (for calculations)
                    'mutated' => round($directMutatedPurchaseUnits, 2),           // Direct mutations only (for calculations)
                    'available' => round($directAvailablePurchaseUnits, 2),       // Direct available only (for calculations)
                    'total_terpakai' => round($totalUsedPurchaseUnits, 2),        // Total usage including from mutations (for reporting)
                    'total_mutated' => round($totalMutatedPurchaseUnits, 2),      // Total mutations including from mutations (for reporting)
                    // A breakdown to help troubleshoot the calculations
                    'debug' => [
                        'direct_used_smallest' => $directUsedSmallestUnits,
                        'direct_mutated_smallest' => $directMutatedSmallestUnits,
                        'mutation_used_smallest' => $mutationUsedSmallestUnits,
                        'mutation_mutated_smallest' => $mutationMutatedSmallestUnits,
                        'conversion_factor' => $conversionFactor,
                    ]
                ];
            }
        });

        return response()->json(['data' => $formatted]);
    }

    // public function getFeedPurchaseBatchDetail($batchId)
    // {

    //     $feedPurchases = FeedPurchase::with([
    //         'feedItem:id,code,name,payload',
    //         'feedStocks' // <- relasi baru nanti ditambahkan
    //     ])
    //         ->where('feed_purchase_batch_id', $batchId)
    //         ->get(['id', 'feed_purchase_batch_id', 'feed_id', 'quantity', 'price_per_unit', 'unit_id', 'converted_unit', 'price_per_converted_unit']);

    //     $formatted = $feedPurchases->map(function ($item) {
    //         $feedItem = optional($item->feedItem);
    //         $konversi = floatval($feedItem->conversion) ?: 1;

    //         $quantity = floatval($item->quantity);
    //         $converted_quantity = $quantity * $konversi;

    //         // Summary dari semua FeedStock berdasarkan purchase
    //         $used = $item->feedStocks->sum('quantity_used');
    //         $mutated = $item->feedStocks->sum('quantity_mutated');
    //         $available = $item->feedStocks->sum('available');
    //         // dd($item);

    //         return [
    //             'id' => $item->id,
    //             'kode' => $feedItem->code,
    //             'name' => $feedItem->name,
    //             'quantity' => $quantity,
    //             'converted_quantity' => $converted_quantity,
    //             'sisa' => $quantity - $used,
    //             'unit' => $item->unit->name ?? '-',
    //             'unit_conversion' => $feedItem->payload['unit_details']['name'] ?? '-',
    //             'conversion' => $konversi,
    //             'price_per_unit' => floatval($item->price_per_unit),
    //             'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
    //                 ? floatval($quantity * $item->price_per_unit)
    //                 : intval($quantity * $item->price_per_unit),

    //             // Tambahan penggunaan dan mutasi
    //             'terpakai' => $used / $konversi,
    //             'mutated' => $mutated / $konversi,
    //             'available' => $available / $konversi,
    //         ];
    //     });

    //     return response()->json(['data' => $formatted]);
    // }

    public function stockEdit(Request $request)
    {
        $id = $request->input('id');
        $value = $request->input('value');
        $column = $request->input('column');
        $user_id = auth()->id();

        try {
            DB::beginTransaction();

            // Get feed purchase with all necessary relations including original unit and converted unit
            $feedPurchase = FeedPurchase::with([
                'feedItem',
                'unit'
            ])->findOrFail($id);

            // Get the feed item with its conversion units
            $feedItem = $feedPurchase->feedItem;

            if (!$feedItem || !isset($feedItem->payload['conversion_units'])) {
                throw new \Exception('Feed item or conversion units not found');
            }

            // Get the unit conversion data from the feed payload
            $conversionUnits = collect($feedItem->payload['conversion_units']);

            // Get the purchase unit and converted (smallest) unit
            $purchaseUnitId = $feedPurchase->unit_id;
            $convertedUnitId = $feedPurchase->converted_unit;

            $purchaseUnit = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
            $smallestUnit = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
                $conversionUnits->firstWhere('is_smallest', true);

            if (!$purchaseUnit || !$smallestUnit) {
                throw new \Exception('Unit conversion information not found');
            }

            // Get conversion values
            $purchaseUnitValue = floatval($purchaseUnit['value']);
            $smallestUnitValue = floatval($smallestUnit['value']);

            // Find all associated feed stocks for this purchase
            $feedStocks = FeedStock::where('feed_purchase_id', $feedPurchase->id)->get();

            if ($feedStocks->isEmpty()) {
                return response()->json([
                    'message' => 'Stock records not found for this purchase',
                    'status' => 'error'
                ], 404);
            }

            // Calculate total used and mutated quantities in CONVERTED (smallest) units
            $totalUsed = $feedStocks->sum('quantity_used');
            $totalMutated = $feedStocks->sum('quantity_mutated');
            $totalAllocated = $totalUsed + $totalMutated;



            // Convert the total allocated from smallest unit back to the purchase unit
            // Using the same conversion logic as in the Create component
            $totalAllocatedInPurchaseUnits = ($totalAllocated * $smallestUnitValue) / $purchaseUnitValue;

            // Get the unit name for display in error message
            $unitName = $feedPurchase->unit ? $feedPurchase->unit->name : '';

            if ($column === 'quantity') {
                $newQuantity = floatval($value);

                // Check if new quantity is less than what's already allocated (used + mutated), in purchase units
                if ($newQuantity < $totalAllocatedInPurchaseUnits) {
                    return response()->json([
                        'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi (' .
                            number_format($totalAllocatedInPurchaseUnits, 2) . ' ' . $unitName . ')',
                        'status' => 'error'
                    ], 422);
                }

                // if ($newQuantity < $totalAllocatedInPurchaseUnits) {
                //     dd('aa');
                // }

                // dd([
                //     'totalUsed' => $totalUsed,
                //     'totalMutated' => $totalMutated,
                //     'totalAllocated' => $totalAllocated,
                //     'totalAllocatedInPurchaseUnits' => $totalAllocatedInPurchaseUnits,
                //     'newQuantity' => $newQuantity
                // ]);

                // Convert new quantity from purchase unit to smallest unit
                // Using the same conversion logic as in the Create component
                $newQuantityInConvertedUnits = ($newQuantity * $purchaseUnitValue) / $smallestUnitValue;

                // Calculate new available amount in smallest units
                $newAvailableInConvertedUnits = $newQuantityInConvertedUnits - $totalAllocated;

                // Update FeedPurchase record with all necessary fields
                $feedPurchase->update([
                    'quantity' => $newQuantity,
                    'converted_quantity' => $newQuantityInConvertedUnits,
                    'price_per_converted_unit' => $feedPurchase->price_per_unit * ($smallestUnitValue / $purchaseUnitValue),
                    'updated_by' => $user_id,
                ]);

                // Update all related stock records
                foreach ($feedStocks as $stock) {
                    $stock->update([
                        'quantity_in' => $newQuantityInConvertedUnits,
                        'available' => $newAvailableInConvertedUnits,
                        'updated_by' => $user_id,
                    ]);
                }
            } else {
                // Handle price updates
                $newPrice = floatval($value);

                // Calculate the price per converted (smallest) unit
                $newPricePerConvertedUnit = $newPrice * ($smallestUnitValue / $purchaseUnitValue);

                // Update the purchase price and calculated converted price
                $feedPurchase->update([
                    'price_per_unit' => $newPrice,
                    'price_per_converted_unit' => $newPricePerConvertedUnit,
                    'updated_by' => $user_id,
                ]);

                // Update amounts in stock records
                $newAmount = $feedPurchase->quantity * $newPrice;
                foreach ($feedStocks as $stock) {
                    $stock->update([
                        'amount' => $newAmount,
                        'updated_by' => $user_id,
                    ]);
                }
            }

            // Recalculate batch totals
            $this->recalculateBatchTotals($feedPurchase->feed_purchase_batch_id, $user_id);

            DB::commit();

            return response()->json([
                'message' => 'Berhasil Update Data',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in stockEdit: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'message' => 'Gagal melakukan update: ' . $e->getMessage(),
                'status' => 'error'
            ], 400);
        }
    }

    /**
     * Helper method to recalculate batch totals
     */
    private function recalculateBatchTotals($batchId, $userId)
    {
        $batch = FeedPurchaseBatch::with('feedPurchases.feedItem')->findOrFail($batchId);

        $totalQuantity = $batch->feedPurchases->sum('quantity');
        $totalAmount = $batch->feedPurchases->sum(function ($purchase) {
            return $purchase->quantity * $purchase->price_per_unit;
        });

        $batch->update([
            'total_qty' => $totalQuantity,
            'total_amount' => $totalAmount,
            'updated_by' => $userId,
        ]);

        return $batch;
    }

    // public function stockEdit(Request $request)
    // {
    //     $id = $request->input('id');
    //     $value = $request->input('value');
    //     $column = $request->input('column');
    //     $user_id = auth()->id();

    //     try {
    //         DB::beginTransaction();

    //         // Get feed purchase with all necessary relations
    //         $feedPurchase = FeedPurchase::with([
    //             'feedItem',
    //             'unit'
    //         ])->findOrFail($id);

    //         $feedItem = $feedPurchase->feedItem;
    //         $konversi = floatval($feedItem->conversion) ?: 1;

    //         // Get the associated feed stock
    //         $feedStock = FeedStock::where('feed_purchase_id', $feedPurchase->id)->first();

    //         if (!$feedStock) {
    //             return response()->json([
    //                 'message' => 'Stock record not found for this purchase',
    //                 'status' => 'error'
    //             ], 404);
    //         }

    //         // Calculate used and mutated quantities
    //         $usedQty = $feedStock->quantity_used ?? 0;
    //         $mutatedQty = $feedStock->quantity_mutated ?? 0;
    //         $totalUsed = $usedQty + $mutatedQty;

    //         if ($column === 'quantity') {
    //             // Convert the new quantity value to converted units
    //             $convertedQuantity = $value * $konversi;

    //             // Check if new quantity is less than what's already used/mutated
    //             if ($convertedQuantity < $totalUsed) {
    //                 return response()->json([
    //                     'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
    //                     'status' => 'error'
    //                 ], 422);
    //             }

    //             // Calculate the available quantity after usage and mutation
    //             $available = $convertedQuantity - $totalUsed;

    //             // Update FeedStock
    //             $feedStock->update([
    //                 'quantity_in' => $convertedQuantity,
    //                 'available' => $available,
    //                 'updated_by' => $user_id,
    //             ]);

    //             // Update FeedPurchase with all necessary fields
    //             $feedPurchase->update([
    //                 'quantity' => $value,
    //                 'converted_quantity' => $convertedQuantity,
    //                 'price_per_converted_unit' => $feedPurchase->price_per_unit / $konversi,
    //                 'updated_by' => $user_id,
    //             ]);
    //         } else {
    //             // Update price fields (price_per_unit and price_per_converted_unit)
    //             $pricePerUnit = floatval($value);
    //             $pricePerConvertedUnit = $pricePerUnit / $konversi;

    //             // Update FeedPurchase with both price fields
    //             $feedPurchase->update([
    //                 'price_per_unit' => $pricePerUnit,
    //                 'price_per_converted_unit' => $pricePerConvertedUnit,
    //                 'updated_by' => $user_id,
    //             ]);

    //             // Update the amount in FeedStock
    //             $amount = $feedPurchase->quantity * $pricePerUnit;
    //             $feedStock->update([
    //                 'amount' => $amount,
    //                 'updated_by' => $user_id,
    //             ]);
    //         }

    //         // Recalculate sub_total and available quantity
    //         $subTotal = $feedPurchase->quantity * $feedPurchase->price_per_unit;
    //         $available = ($feedPurchase->quantity * $konversi) - $totalUsed;

    //         $feedStock->update([
    //             'available' => $available,
    //             'amount' => $subTotal,
    //             'updated_by' => $user_id,
    //         ]);

    //         // Update Batch total summary
    //         $batch = FeedPurchaseBatch::with('feedPurchases.feedItem')
    //             ->findOrFail($feedPurchase->feed_purchase_batch_id);

    //         $totalQty = $batch->feedPurchases->sum(function ($purchase) {
    //             return $purchase->quantity;
    //         });

    //         $totalAmount = $batch->feedPurchases->sum(function ($purchase) {
    //             return $purchase->price_per_unit * $purchase->quantity;
    //         });

    //         // Update the batch with new totals
    //         $batch->update([
    //             'total_qty' => $totalQty,
    //             'total_amount' => $totalAmount,
    //             'expedition_fee' => $batch->expedition_fee, // Preserve existing fee
    //             'updated_by' => $user_id,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Berhasil Update Data',
    //             'status' => 'success'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error("Error in stockEdit: " . $e->getMessage(), [
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'message' => 'Gagal melakukan update: ' . $e->getMessage(),
    //             'status' => 'error'
    //         ], 400);
    //     }
    // }

    // public function stockEdit(Request $request)
    // {
    //     $id = $request->input('id');
    //     $value = $request->input('value');
    //     $column = $request->input('column');
    //     $user_id = auth()->id();

    //     // dd($request->all());

    //     try {
    //         DB::beginTransaction();

    //         $feedPurchase = FeedPurchase::with('feedItem')->findOrFail($id);
    //         $feedItem = $feedPurchase->feedItem;
    //         $konversi = floatval($feedItem->conversion) ?: 1;

    //         $feedStock = FeedStock::where('feed_purchase_id', $feedPurchase->id)->first();

    //         if ($column === 'quantity') {
    //             $usedQty = $feedStock->quantity_used ?? 0;
    //             $mutatedQty = $feedStock->quantity_mutated ?? 0;
    //             $sisa = $usedQty + $mutatedQty;

    //             if (($value * $konversi) < $sisa) {
    //                 return response()->json([
    //                     'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
    //                     'status' => 'error'
    //                 ], 422);
    //             }

    //             // Update FeedStock
    //             $feedStock->update([
    //                 'quantity_in' => $value * $konversi,
    //                 'available' => ($value * $konversi) - $sisa,
    //                 'updated_by' => $user_id,
    //             ]);

    //             // Update FeedPurchase
    //             $feedPurchase->update([
    //                 'quantity' => $value,
    //                 'updated_by' => $user_id,
    //             ]);
    //         } else {
    //             // Update price
    //             $feedPurchase->update([
    //                 'price_per_unit' => $value,
    //                 'updated_by' => $user_id,
    //             ]);

    //             $feedStock->update([
    //                 'amount' => $feedPurchase->quantity * $value,
    //                 'updated_by' => $user_id,
    //             ]);
    //         }

    //         // Update sub_total dan sisa berdasarkan usage
    //         $subTotal = $feedPurchase->quantity * $feedPurchase->price_per_unit;
    //         $usedQty = $feedStock->quantity_used ?? 0;
    //         $mutatedQty = $feedStock->quantity_mutated ?? 0;
    //         $available = ($feedPurchase->quantity * $konversi) - $usedQty - $mutatedQty;

    //         $feedStock->update([
    //             'available' => $available,
    //             'amount' => $subTotal,
    //         ]);

    //         // Update Batch total summary
    //         $batch = \App\Models\FeedPurchaseBatch::with('feedPurchases.feedItem')->findOrFail($feedPurchase->feed_purchase_batch_id);

    //         $totalQty = $batch->feedPurchases->sum(function ($purchase) {
    //             $konversi = floatval(optional($purchase->feedItem)->konversi) ?: 1;
    //             return $purchase->quantity;
    //         });

    //         $totalHarga = $batch->feedPurchases->sum(function ($purchase) {
    //             return $purchase->price_per_unit * $purchase->quantity;
    //         });

    //         $batch->update([
    //             'expedition_fee' => $batch->expedition_fee,
    //             'updated_by' => $user_id,
    //         ]);

    //         DB::commit();

    //         return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success']);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['error' => $e->getMessage()], 400);
    //     }
    // }

    // public function getFeedCardByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'nullable|date',
    //         'end_date' => 'nullable|date',
    //     ]);

    //     $livestockId = $validated['livestock_id'];
    //     $feedId = $validated['feed_id'];
    //     $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
    //     $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

    //     try {
    //         // 1. Query all FeedStock for livestock_id & feed_id
    //         $stocks = \App\Models\FeedStock::with([
    //             'feed',
    //             'feedPurchaseItem.feedPurchase',
    //             'feedUsageDetails.feedUsage.livestock',
    //             'mutationDetails.mutation.toLivestock',
    //             'incomingMutation.mutation.fromLivestock',
    //         ])
    //             ->where('livestock_id', $livestockId)
    //             ->where('feed_id', $feedId)
    //             ->get();

    //         // 2. Group by feed_purchase_item_id (or 'no_purchase')
    //         $grouped = $stocks->groupBy(function ($stock) {
    //             return $stock->feed_purchase_item_id ?? 'no_purchase';
    //         });

    //         $result = [];
    //         foreach ($grouped as $purchaseItemId => $stockGroup) {
    //             $histories = [];
    //             $purchase = $purchaseItemId !== 'no_purchase'
    //                 ? \App\Models\FeedPurchaseItem::with('feed', 'unit', 'feedPurchase')->find($purchaseItemId)
    //                 : null;
    //             $purchaseDate = $purchase && $purchase->feedPurchase ? $purchase->feedPurchase->date : null;

    //             // Calculate saldo awal (stock before start_date) if no purchase in range
    //             $saldoAwal = 0;
    //             if ($startDate) {
    //                 // Sum all purchases before start_date
    //                 $allStocks = $stockGroup;
    //                 $allPurchase = 0;
    //                 $allUsage = 0;
    //                 $allMutation = 0;
    //                 foreach ($allStocks as $stock) {
    //                     // Pembelian (masuk)
    //                     if ($purchase && $purchase->feedPurchase && $purchase->feedPurchase->date < $startDate) {
    //                         $allPurchase += floatval($purchase->converted_quantity);
    //                     }
    //                     // Usage sebelum start_date
    //                     foreach ($stock->feedUsageDetails as $usageDetail) {
    //                         $usageDate = $usageDetail->feedUsage->usage_date;
    //                         if ($usageDate && $usageDate < $startDate) {
    //                             $allUsage += floatval($usageDetail->quantity_taken);
    //                         }
    //                     }
    //                     // Mutasi keluar sebelum start_date
    //                     foreach ($stock->mutationDetails as $mutation) {
    //                         $mutationDate = $mutation->mutation->date;
    //                         if ($mutationDate && $mutationDate < $startDate) {
    //                             $allMutation += floatval($mutation->quantity);
    //                         }
    //                     }
    //                 }
    //                 $saldoAwal = $allPurchase - $allUsage - $allMutation;
    //             }

    //             // 3. If there is a purchase in range, add purchase row
    //             if ($purchase && $purchase->feedPurchase && (!$startDate || $purchaseDate >= $startDate) && (!$endDate || $purchaseDate <= $endDate)) {
    //                 $histories[] = [
    //                     'tanggal' => $purchaseDate->format('Y-m-d'),
    //                     'keterangan' => 'Pembelian',
    //                     'masuk' => floatval($purchase->converted_quantity),
    //                     'keluar' => 0,
    //                     'stok_awal' => 0,
    //                     'stok_akhir' => floatval($purchase->converted_quantity),
    //                 ];
    //             }
    //             // 4. Always add usage/mutation rows in range
    //             foreach ($stockGroup as $stock) {
    //                 // Usage
    //                 foreach ($stock->feedUsageDetails as $usageDetail) {
    //                     $usageDate = $usageDetail->feedUsage->usage_date;
    //                     if ($usageDate && (!$startDate || $usageDate >= $startDate) && (!$endDate || $usageDate <= $endDate)) {
    //                         $histories[] = [
    //                             'tanggal' => $usageDate->format('Y-m-d'),
    //                             'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
    //                             'masuk' => 0,
    //                             'keluar' => $usageDetail->quantity_taken,
    //                         ];
    //                     }
    //                 }
    //                 // Mutasi keluar
    //                 foreach ($stock->mutationDetails as $mutation) {
    //                     $mutationDate = $mutation->mutation->date;
    //                     if ($mutationDate && (!$startDate || $mutationDate >= $startDate) && (!$endDate || $mutationDate <= $endDate)) {
    //                         $histories[] = [
    //                             'tanggal' => $mutationDate->format('Y-m-d'),
    //                             'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
    //                             'masuk' => 0,
    //                             'keluar' => $mutation->quantity,
    //                         ];
    //                     }
    //                 }
    //             }
    //             // 5. Sort and calculate stok_awal/stok_akhir
    //             usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
    //             foreach ($histories as $i => &$entry) {
    //                 if ($i === 0) {
    //                     $entry['stok_awal'] = ($purchase && $purchase->feedPurchase && (!$startDate || $purchaseDate >= $startDate)) ? 0 : $saldoAwal;
    //                     $entry['stok_akhir'] = $entry['stok_awal'] + ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
    //                 } else {
    //                     $entry['stok_awal'] = $histories[$i - 1]['stok_akhir'];
    //                     $entry['stok_akhir'] = $entry['stok_awal'] + ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
    //                 }
    //             }
    //             // 6. Build info
    //             $result[] = [
    //                 'feed_purchase_info' => [
    //                     'feed_name' => $purchase ? ($purchase->feed->name ?? '-') : ($stockGroup->first()->feed->name ?? '-'),
    //                     'no_batch' => '-',
    //                     'tanggal' => $purchase && $purchase->feedPurchase ? $purchase->feedPurchase->date->format('Y-m-d') : null,
    //                     'harga' => $purchase ? $purchase->price_per_unit : 0,
    //                     'tipe' => $purchase ? 'Pembelian' : 'Tanpa Pembelian',
    //                 ],
    //                 'histories' => $histories,
    //             ];
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $result,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('[FeedController@getFeedCardByLivestock] Error: ' . $e->getMessage(), [
    //             'livestock_id' => $livestockId,
    //             'feed_id' => $feedId,
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function getFeedCardByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'nullable|date',
    //         'end_date' => 'nullable|date',
    //     ]);

    //     $livestockId = $validated['livestock_id'];
    //     $feedId = $validated['feed_id'];
    //     $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
    //     $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

    //     try {
    //         $stocks = FeedStock::with([
    //             'feed',
    //             'feedPurchase.batch',
    //             'feedUsageDetails.feedUsage.livestock',
    //             'mutationDetails.mutation.toLivestock',
    //             'incomingMutation.mutation.fromLivestock',
    //         ])
    //             ->where('livestock_id', $livestockId)
    //             ->where('feed_id', $feedId)
    //             ->get();

    //         $result = [];


    //         // Group stocks by type (purchase or mutation)
    //         foreach ($stocks as $stock) {
    //             if ($stock->source_id) {
    //                 // Check if this is a mutation by looking up the source_id in Mutation model
    //                 $mutation = \App\Models\Mutation::where('id', $stock->source_id)->first();
    //                 if ($mutation) {
    //                     // This is a mutation
    //                     $result[] = $this->buildMutationInHistory([$stock], $startDate, $endDate);
    //                 } else {
    //                     // If source_id exists but not found in Mutation, treat as purchase
    //                     $result[] = $this->buildFeedPurchaseHistory([$stock], $startDate, $endDate);
    //                 }
    //             } else {
    //                 // No source_id, treat as purchase
    //                 $result[] = $this->buildFeedPurchaseHistory([$stock], $startDate, $endDate);
    //             }
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => array_filter($result),
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    private function buildFeedPurchaseHistory($items, $startDate, $endDate)
    {
        $first = collect($items)->first();
        if (!$first) {
            return null;
        }

        // Use FeedPurchase directly for date/invoice
        $purchaseDate = optional($first->feedPurchase)->date;
        $invoiceNumber = $first->feedPurchase->invoice_number ?? '-';

        $purchaseDateOnly = $purchaseDate ? $purchaseDate->format('Y-m-d') : null;
        $startDateOnly = $startDate ? $startDate->format('Y-m-d') : null;
        $endDateOnly = $endDate ? $endDate->format('Y-m-d') : null;

        if (
            !$purchaseDateOnly ||
            ($startDateOnly && $purchaseDateOnly < $startDateOnly) ||
            ($endDateOnly && $purchaseDateOnly > $endDateOnly)
        ) {
            return null;
        }

        $histories = [[
            'tanggal' => $purchaseDate->format('Y-m-d'),
            'keterangan' => 'Pembelian',
            'masuk' => collect($items)->sum('quantity_in'),
            'keluar' => 0,
        ]];

        $runningStock = collect($items)->sum('quantity_in');
        $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);
        // Use FeedPurchaseItem for price/unit, FeedPurchase for invoice/date
        return $this->formatResult(
            $first,
            $histories,
            $purchaseDate,
            $first->price_per_unit ?? 0,
            'Pembelian',
            $invoiceNumber
        );
    }

    /**
     * Build history for a FeedPurchaseItem (new structure)
     */
    protected function buildFeedPurchaseItemHistory($item, $startDate, $endDate)
    {
        $unit = $item->unit;
        $purchase = $item->feedPurchase;
        // Use the 'date' column from feed_purchases as the purchase date
        $purchaseDate = $purchase ? $purchase->date : null;
        $purchaseDateOnly = $purchaseDate ? $purchaseDate->format('Y-m-d') : null;
        $startDateOnly = $startDate ? $startDate->format('Y-m-d') : null;
        $endDateOnly = $endDate ? $endDate->format('Y-m-d') : null;
        $histories = [];
        // Always include the purchase event if within filter range (using 'date' column)
        if ($purchaseDate && (!$startDate || $purchaseDate >= $startDate) && (!$endDate || $purchaseDate <= $endDate)) {
            $histories[] = [
                'tanggal' => $purchaseDate->format('Y-m-d'),
                'keterangan' => 'Pembelian',
                'masuk' => floatval($item->converted_quantity),
                'keluar' => 0,
                'stok_awal' => 0,
                'stok_akhir' => floatval($item->converted_quantity),
            ];
        }
        $runningStock = floatval($item->converted_quantity);
        $histories = $this->processFeedPurchaseItemUsageAndMutation($item, $histories, $startDate, $endDate, $runningStock);
        return $this->formatFeedPurchaseItemResult($item, $histories, $purchaseDate, $item->price_per_unit ?? 0, 'Pembelian', '-');
    }

    /**
     * Process usage and mutation for a FeedPurchaseItem
     */
    private function processFeedPurchaseItemUsageAndMutation($item, array $histories, ?Carbon $startDate, ?Carbon $endDate, &$runningStock): array
    {
        foreach ($item->feedStocks as $stock) {
            // Pemakaian (usage)
            foreach ($stock->feedUsageDetails as $usageDetail) {
                $usageDate = $usageDetail->feedUsage->usage_date;
                if ($usageDate && (!$startDate || $usageDate >= $startDate) && (!$endDate || $usageDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $usageDate->format('Y-m-d'),
                        'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $usageDetail->quantity_taken,
                    ];
                }
            }
            // Mutasi keluar (mutation)
            foreach ($stock->mutationDetails as $mutation) {
                $mutationDate = $mutation->mutation->date;
                if ($mutationDate && (!$startDate || $mutationDate >= $startDate) && (!$endDate || $mutationDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutationDate->format('Y-m-d'),
                        'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $mutation->quantity,
                    ];
                }
            }
        }
        return $histories;
    }

    /**
     * Format result for FeedPurchaseItem history
     */
    private function formatFeedPurchaseItemResult($item, $histories, $tanggal, $harga, $tipe, $noBatch)
    {
        usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
        $runningStock = 0;
        foreach ($histories as &$entry) {
            $entry['stok_awal'] = $runningStock;
            $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
            $entry['stok_akhir'] = $runningStock;
        }

        return [
            'feed_purchase_info' => [
                'feed_name' => $item->feed->name ?? '-',
                'no_batch' => $noBatch,
                'tanggal' => $tanggal ? $tanggal->format('Y-m-d') : null,
                'harga' => $harga,
                'tipe' => $tipe,
            ],
            'histories' => $histories,
        ];
    }

    public function getFeedByFarm(Request $request)
    {

        // dd($request->all());
        $validated = $request->validate([
            'livestock_id' => 'required|uuid',
            'feed_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $livestockId = $validated['livestock_id'];
        $feedId = $validated['feed_id'];
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        try {
            $stocks = FeedStock::with([
                'feed',
                'feedPurchase.batch',
                'feedUsageDetails.feedUsage.feed',
                'mutationDetails.mutation.toFarm',
                'incomingMutation.mutation.fromFarm',
            ])
                ->where('livestock_id', $livestockId)
                ->where('feed_id', $feedId)
                ->get();

            $result = [];


            // Proses transaksi pembelian awal
            $purchaseStocks = $stocks->whereNotNull('feed_purchase_id')->groupBy('feed_purchase_id');
            // dd($purchaseStocks);

            foreach ($purchaseStocks as $purchaseId => $items) {
                $first = $items->first();

                $histories = [];
                // All purchase date logic now uses the 'date' column from feed_purchases
                $purchaseDate = optional($first->feedPurchase->batch)->date;

                // dd($purchaseDate);


                if ($purchaseDate && (!$startDate || $purchaseDate >= $startDate) && (!$endDate || $purchaseDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $purchaseDate->format('Y-m-d'),
                        'keterangan' => 'Pembelian',
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the purchase
                        'keluar' => 0,
                    ];

                    // dd($histories);



                    $runningStock = $items->sum('quantity_in'); // Initial stock after purchase
                    // dd($runningStock);

                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'feed_purchase_info' => [
                            'feed_name' => $first->feed->name ?? '-',
                            'no_batch' => optional($first->feedPurchase->batch)->invoice_number ?? '-',
                            'tanggal' => $purchaseDate->format('Y-m-d'),
                            'harga' => $first->feedPurchase->price_per_unit ?? 0,
                            'tipe' => 'Pembelian',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            // Proses transaksi mutasi masuk
            $mutationInStocks = $stocks->whereNotNull('source_id')->groupBy('source_id');
            // $mutationInStocks = $stocks->whereNotNull('source_id')->whereNull('feed_purchase_id')->groupBy('source_id');
            foreach ($mutationInStocks as $mutationId => $items) {
                $first = $items->first();
                $mutation = FeedMutation::find($mutationId);
                $histories = [];

                if ($mutation && (!$startDate || $mutation->date >= $startDate) && (!$endDate || $mutation->date <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutation->date->format('Y-m-d'),
                        'keterangan' => 'Mutasi dari ' . ($mutation->fromFarm->name ?? '-'),
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the mutation
                        'keluar' => 0,
                    ];

                    $runningStock = $items->sum('quantity_in'); // Initial stock after mutation
                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'feed_purchase_info' => [
                            'feed_name' => $first->feed->name ?? '-',
                            'no_batch' => '-',
                            'tanggal' => $mutation->date->format('Y-m-d'),
                            'harga' => 0,
                            'tipe' => 'Mutasi Masuk',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            // Proses transaksi mutasi masuk berdasarkan incomingMutation (jika source_id tidak ada)
            $mutationInByRelationStocks = $stocks->whereNull('source_id')->whereNotNull('incomingMutation')->groupBy('incomingMutation.feed_mutation_id');
            foreach ($mutationInByRelationStocks as $mutationId => $items) {
                $first = $items->first();
                $mutation = $first->incomingMutation->mutation;
                $histories = [];

                if ($mutation && (!$startDate || $mutation->date >= $startDate) && (!$endDate || $mutation->date <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutation->date->format('Y-m-d'),
                        'keterangan' => 'Mutasi dari ' . ($mutation->fromFarm->name ?? '-'),
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the mutation
                        'keluar' => 0,
                    ];

                    $runningStock = $items->sum('quantity_in'); // Initial stock after mutation
                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'feed_purchase_info' => [
                            'feed_name' => $first->feed->name ?? '-',
                            'no_batch' => '-',
                            'tanggal' => $mutation->date->format('Y-m-d'),
                            'harga' => 0,
                            'tipe' => 'Mutasi Masuk (Relasi)',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            // DB::rollBack(); // Rollback on any other exception
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';

            // Log detailed error for debugging
            Log::error(" Error: $message | Line: $line | File: $file");

            // Optionally: log stack trace
            Log::debug("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage(),
        //     ], 500);
        // }
    }

    public function exportPembelian(Request $request)
    {
        $purchases = FeedPurchase::with([
            'feedItem',
            'batch.vendor',
            'livestok',
        ])
            ->where('livestock_id', $request->periode)
            ->latest()->get();
        // $purchases = FeedPurchase::with(['feedItem'])->where('livestock_id',$request->periode)->latest()->get();
        // dd($purchases);

        if ($purchases->isNotEmpty()) {
            return view('pages.reports.feed.feed_purchase', compact('purchases'));
        } else {
            return response()->json([
                'error' => 'Data pembelian belum ada'
            ], 404);
        }
    }

    public function indexReportFeedPurchase()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $livestock->pluck('kandang_id'))->get();

        $livestock = $livestock->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return view('pages.reports.feed.index_feed_purchase', compact(['farms', 'kandangs', 'livestock']));
    }

    /**
     * Get simplified feed usage data for modal display
     * Shows only usage transactions without stock calculations
     */
    public function getFeedUsageData(Request $request)
    {
        $validated = $request->validate([
            'livestock_id' => 'required|uuid',
            'feed_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $livestockId = $validated['livestock_id'];
        $feedId = $validated['feed_id'];
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        try {
            // Log the input parameters for debugging
            Log::info('[FeedController@getFeedUsageData] Input parameters', [
                'livestock_id' => $livestockId,
                'feed_id' => $feedId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Get feed usage details for the specified livestock and feed
            $usageDetails = FeedUsageDetail::with([
                'feedUsage.livestock',
                'feed',
                'feedStock.feedPurchase.supplier',
                'feedStock.feedPurchase.expedition',
                'feedStock.feedPurchase.feedPurchaseItems.unit',
                'feedStock.feedPurchase.feedPurchaseItems.convertedUnit'
            ])
                ->whereHas('feedUsage', function ($query) use ($livestockId, $startDate, $endDate) {
                    $query->where('livestock_id', $livestockId)
                        ->when($startDate, function ($q) use ($startDate) {
                            return $q->where('usage_date', '>=', $startDate);
                        })
                        ->when($endDate, function ($q) use ($endDate) {
                            return $q->where('usage_date', '<=', $endDate);
                        });
                })
                ->where('feed_id', $feedId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Log the query results for debugging
            Log::info('[FeedController@getFeedUsageData] Query results', [
                'usage_details_count' => $usageDetails->count(),
                'usage_details' => $usageDetails->toArray(),
            ]);

            $usageData = [];
            foreach ($usageDetails as $detail) {
                // Skip if required relationships are missing
                if (!$detail->feedUsage || !$detail->feedUsage->livestock) {
                    Log::warning('[FeedController@getFeedUsageData] Skipping detail due to missing relationships', [
                        'detail_id' => $detail->id,
                        'feed_usage_id' => $detail->feed_usage_id,
                    ]);
                    continue;
                }

                // Initialize fallback values
                $unit = '-';
                $batchInfo = '-';
                $hargaSatuan = 0;

                // Try to get feed purchase information if available
                if ($detail->feedStock && $detail->feedStock->feedPurchase) {
                    $feedPurchaseItem = $detail->feedStock->feedPurchase->feedPurchaseItems()
                        ->where('feed_id', $detail->feed_id)
                        ->first();

                    if ($feedPurchaseItem) {
                        $unit = optional($feedPurchaseItem->unit)->name ?? '-';
                        $batchInfo = $detail->feedStock->feedPurchase->invoice_number ?? '-';
                        $hargaSatuan = optional($feedPurchaseItem)->price_per_unit ?? 0;
                    } else {
                        Log::warning('[FeedController@getFeedUsageData] Missing feed purchase item', [
                            'detail_id' => $detail->id,
                            'feed_stock_id' => $detail->feed_stock_id,
                            'feed_purchase_id' => $detail->feedStock->feedPurchase->id,
                            'feed_id' => $detail->feed_id,
                        ]);
                    }
                } else {
                    Log::warning('[FeedController@getFeedUsageData] Missing feedStock or feedPurchase', [
                        'detail_id' => $detail->id,
                        'feed_stock_id' => $detail->feed_stock_id,
                        'feed_stock_exists' => $detail->feedStock ? 'yes' : 'no',
                        'feed_purchase_exists' => $detail->feedStock && $detail->feedStock->feedPurchase ? 'yes' : 'no',
                    ]);
                }

                // Prepare usage data with fallback values
                $usageData[] = [
                    'tanggal' => $detail->feedUsage->usage_date->format('Y-m-d'),
                    'keterangan' => 'Pemakaian Ternak ' . ($detail->feedUsage->livestock->name ?? '-'),
                    'jumlah' => floatval($detail->quantity_taken),
                    'unit' => $unit,
                    'batch_info' => $batchInfo,
                    'harga_satuan' => $hargaSatuan,
                    'total_harga' => ($detail->quantity_taken * $hargaSatuan),
                ];
            }

            // Get feed information
            $feed = \App\Models\Feed::find($feedId);
            $livestock = Livestock::find($livestockId);

            // Validate that feed and livestock exist
            if (!$feed) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Feed tidak ditemukan',
                ], 404);
            }

            if (!$livestock) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ternak tidak ditemukan',
                ], 404);
            }

            $result = [
                'feed_info' => [
                    'feed_name' => $feed->name ?? '-',
                    'livestock_name' => $livestock->name ?? '-',
                    'livestock_code' => $livestock->code ?? '-',
                ],
                'usage_data' => $usageData,
                'summary' => [
                    'total_usage' => collect($usageData)->sum('jumlah'),
                    'total_cost' => collect($usageData)->sum('total_harga'),
                    'usage_count' => count($usageData),
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[FeedController@getFeedUsageData] Error: ' . $e->getMessage(), [
                'livestock_id' => $livestockId,
                'feed_id' => $feedId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data penggunaan pakan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function debugFeedCardByLivestock(Request $request)
    {
        $livestockId = $request->get('livestock_id');
        $feedId = $request->get('feed_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $livestockId = $livestockId ?? '9f64bfb5-6f25-48c5-9cb4-cc54f820ee4b';
        $startDate = $startDate ?? '2025-05-01';
        $endDate = $endDate ?? '2025-07-07';
        $date = $request->get('date', '2025-07-07');

        $stocks = \App\Models\FeedStock::with([
            'feed',
            'feedPurchase',
            'feedPurchaseItem',
            'feedUsageDetails.feedUsage.livestock',
            'mutationDetails.mutation.toLivestock',
            'incomingMutation.mutation.fromLivestock',
        ])
            ->where('livestock_id', $livestockId)
            ->when($feedId, function ($q) use ($feedId) {
                return $q->where('feed_id', $feedId);
            })
            ->get();

        $grouped = $stocks->groupBy(function ($stock) {
            if ($stock->feedPurchaseItem) {
                return 'FPI:' . $stock->feedPurchaseItem->id;
            } elseif ($stock->feedPurchase) {
                return 'FP:' . $stock->feedPurchase->id;
            } else {
                return 'NO_PURCHASE';
            }
        });

        echo "<div style='background:#1a1a1a;color:#00ff00;padding:20px;border-radius:8px;max-width:95vw;overflow:auto;font-family:monospace;font-size:12px;margin:20px;'>";
        echo "<h2 style='color:#ffff00;'>🔍 DEBUG MODE - FeedStock Data Dump</h2>";
        echo "<h3 style='color:#00ffff;'>📦 Stock Overview</h3>";
        echo "<pre>";
        echo "Total Stocks: " . $stocks->count() . "\n";
        echo "Grouped: " . $grouped->count() . " (by FeedPurchase/FeedPurchaseItem)\n";
        echo "</pre>";

        // --- HISTORIKAL AKUMULASI ---
        // Kumpulkan semua event dari seluruh stock
        $allEvents = [];
        foreach ($stocks as $stock) {
            // Stock Masuk
            $stockInDate = null;
            if ($stock->feedPurchaseItem && $stock->feedPurchaseItem->feedPurchase && $stock->feedPurchaseItem->feedPurchase->date) {
                $stockInDate = $stock->feedPurchaseItem->feedPurchase->date->format('Y-m-d');
            } elseif ($stock->feedPurchase && $stock->feedPurchase->date) {
                $stockInDate = $stock->feedPurchase->date->format('Y-m-d');
            } else {
                $stockInDate = $stock->created_at ? $stock->created_at->format('Y-m-d') : '-';
            }
            $allEvents[] = [
                'tanggal' => $stockInDate,
                'keterangan' => 'Stock Masuk',
                'masuk' => $stock->quantity_in,
                'keluar' => 0,
                'stock_id' => $stock->id,
                'purchase_info' => $stock->feedPurchaseItem ? 'FPI:' . $stock->feedPurchaseItem->id : ($stock->feedPurchase ? 'FP:' . $stock->feedPurchase->id : '-')
            ];
            // Usages
            $usages = $stock->feedUsageDetails->sortBy(function ($u) {
                return $u->feedUsage->usage_date ?? $u->created_at;
            });
            foreach ($usages as $usage) {
                $usageDate = $usage->feedUsage->usage_date ?? $usage->created_at;
                $qty = floatval($usage->quantity_taken);
                $allEvents[] = [
                    'tanggal' => $usageDate ? (is_string($usageDate) ? $usageDate : $usageDate->format('Y-m-d')) : '-',
                    'keterangan' => 'Pemakaian Ternak ' . ($usage->feedUsage->livestock->name ?? '-'),
                    'masuk' => 0,
                    'keluar' => $qty,
                    'stock_id' => $stock->id,
                    'purchase_info' => $stock->feedPurchaseItem ? 'FPI:' . $stock->feedPurchaseItem->id : ($stock->feedPurchase ? 'FP:' . $stock->feedPurchase->id : '-')
                ];
            }
            // Mutations
            $mutations = $stock->mutationDetails->sortBy(function ($m) {
                return $m->mutation->date ?? $m->created_at;
            });
            foreach ($mutations as $mutation) {
                $mutationDate = $mutation->mutation->date ?? $mutation->created_at;
                $qty = floatval($mutation->quantity);
                $allEvents[] = [
                    'tanggal' => $mutationDate ? (is_string($mutationDate) ? $mutationDate : $mutationDate->format('Y-m-d')) : '-',
                    'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
                    'masuk' => 0,
                    'keluar' => $qty,
                    'stock_id' => $stock->id,
                    'purchase_info' => $stock->feedPurchaseItem ? 'FPI:' . $stock->feedPurchaseItem->id : ($stock->feedPurchase ? 'FP:' . $stock->feedPurchase->id : '-')
                ];
            }
        }
        // Urutkan semua event berdasarkan tanggal
        usort($allEvents, function ($a, $b) {
            return strcmp($a['tanggal'], $b['tanggal']);
        });
        // Hitung saldo berjalan akumulasi
        $saldo = 0;
        foreach ($allEvents as $i => &$event) {
            if ($i === 0) {
                $event['stock_awal'] = 0;
            } else {
                $event['stock_awal'] = $saldo;
            }
            $saldo = $event['stock_awal'] + ($event['masuk'] ?? 0) - ($event['keluar'] ?? 0);
            $event['sisa'] = $saldo;
        }
        unset($event);
        // Filter by start_date and end_date if provided
        $filteredEvents = $allEvents;
        if ($startDate || $endDate) {
            $filteredEvents = array_filter($allEvents, function ($row) use ($startDate, $endDate) {
                $rowDate = $row['tanggal'];
                if (!$rowDate || $rowDate === '-') return false;
                if ($startDate && $rowDate < $startDate) return false;
                if ($endDate && $rowDate > $endDate) return false;
                return true;
            });
        }
        // Output tabel akumulasi
        echo "<h3 style='color:#ff00ff;'>📊 Akumulasi Historikal (All Stocks Combined)</h3>";
        echo "<table border='1' cellpadding='4' cellspacing='0' style='margin:10px 0 20px 0;background:#222;color:#fff;'>";
        echo "<tr style='background:#333;color:#ffff00;'><th>Tanggal</th><th>Keterangan</th><th>Stock Awal</th><th>Masuk</th><th>Keluar</th><th>Sisa</th><th>Stock ID</th><th>Purchase</th><th>Supplier</th></tr>";
        foreach ($filteredEvents as $row) {
            // Ambil supplier name jika Stock Masuk, selain itu '-'
            $supplierName = '-';
            if ($row['keterangan'] === 'Stock Masuk') {
                $stock = $stocks->firstWhere('id', $row['stock_id']);
                if ($stock) {
                    if ($stock->feedPurchaseItem && $stock->feedPurchaseItem->feedPurchase && $stock->feedPurchaseItem->feedPurchase->supplier) {
                        $supplierName = $stock->feedPurchaseItem->feedPurchase->supplier->name ?? '-';
                    } elseif ($stock->feedPurchase && $stock->feedPurchase->supplier) {
                        $supplierName = $stock->feedPurchase->supplier->name ?? '-';
                    }
                }
            }
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['tanggal']) . "</td>";
            echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['stock_awal'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['masuk'], 2) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['keluar'], 2) . "</td>";
            echo "<td style='text-align:right;font-weight:bold;color:#00ff00;'>" . number_format($row['sisa'], 2) . "</td>";
            echo "<td style='color:#00ffff;'>" . htmlspecialchars($row['stock_id']) . "</td>";
            echo "<td style='color:#ffcc00;'>" . htmlspecialchars($row['purchase_info']) . "</td>";
            echo "<td style='color:#00ffcc;'>" . htmlspecialchars($supplierName) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        // --- END HISTORIKAL ---

        // --- VERSI PER GROUP (EXISTING) ---
        foreach ($grouped as $groupKey => $stockGroup) {
            echo "<h3 style='color:#00ffff;'>Group: {$groupKey}</h3>";
            $first = $stockGroup->first();
            if ($first->feedPurchaseItem) {
                $fpi = $first->feedPurchaseItem;
                $fp = $fpi->feedPurchase;
                echo "<pre>FeedPurchaseItem: {$fpi->id}\n";
                echo "  Feed: " . ($fpi->feed->name ?? '-') . "\n";
                echo "  Qty: {$fpi->quantity}\n";
                echo "  Price/unit: {$fpi->price_per_unit}\n";
                echo "  Unit: " . ($fpi->unit->name ?? '-') . "\n";
                echo "  Purchase: " . ($fp->invoice_number ?? '-') . " (" . ($fp->date ? $fp->date->format('Y-m-d') : '-') . ")\n";
                echo "  Supplier: " . ($fp->supplier->name ?? '-') . "\n";
                echo "</pre>";
            } elseif ($first->feedPurchase) {
                $fp = $first->feedPurchase;
                echo "<pre>FeedPurchase: {$fp->id}\n";
                echo "  Invoice: " . ($fp->invoice_number ?? '-') . "\n";
                echo "  Date: " . ($fp->date ? $fp->date->format('Y-m-d') : '-') . "\n";
                echo "  Supplier: " . ($fp->supplier->name ?? '-') . "\n";
                echo "</pre>";
            } else {
                echo "<pre>No Purchase Info\n</pre>";
            }
            foreach ($stockGroup as $stock) {
                echo "<pre>Stock ID: {$stock->id}\n  Qty In: {$stock->quantity_in}\n  Qty Used: {$stock->quantity_used}\n  Qty Mutated: {$stock->quantity_mutated}\n  Qty Reserved: {$stock->quantity_reserved}\n";
                // Build historical table
                $historyRows = [];
                $running = floatval($stock->quantity_in);
                // Determine stock-in date
                $stockInDate = null;
                if ($stock->feedPurchaseItem && $stock->feedPurchaseItem->feedPurchase && $stock->feedPurchaseItem->feedPurchase->date) {
                    $stockInDate = $stock->feedPurchaseItem->feedPurchase->date->format('Y-m-d');
                } elseif ($stock->feedPurchase && $stock->feedPurchase->date) {
                    $stockInDate = $stock->feedPurchase->date->format('Y-m-d');
                } else {
                    $stockInDate = $stock->created_at ? $stock->created_at->format('Y-m-d') : '-';
                }
                $historyRows[] = [
                    'tanggal' => $stockInDate,
                    'keterangan' => 'Stock Masuk',
                    'masuk' => $stock->quantity_in,
                    'keluar' => 0,
                    'sisa' => $running,
                ];
                // Usages
                $usages = $stock->feedUsageDetails->sortBy(function ($u) {
                    return $u->feedUsage->usage_date ?? $u->created_at;
                });
                foreach ($usages as $usage) {
                    $usageDate = $usage->feedUsage->usage_date ?? $usage->created_at;
                    $qty = floatval($usage->quantity_taken);
                    $running -= $qty;
                    $historyRows[] = [
                        'tanggal' => $usageDate ? (is_string($usageDate) ? $usageDate : $usageDate->format('Y-m-d')) : '-',
                        'keterangan' => 'Pemakaian Ternak ' . ($usage->feedUsage->livestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $qty,
                        'sisa' => $running,
                    ];
                }
                // Mutations
                $mutations = $stock->mutationDetails->sortBy(function ($m) {
                    return $m->mutation->date ?? $m->created_at;
                });
                foreach ($mutations as $mutation) {
                    $mutationDate = $mutation->mutation->date ?? $mutation->created_at;
                    $qty = floatval($mutation->quantity);
                    $running -= $qty;
                    $historyRows[] = [
                        'tanggal' => $mutationDate ? (is_string($mutationDate) ? $mutationDate : $mutationDate->format('Y-m-d')) : '-',
                        'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $qty,
                        'sisa' => $running,
                    ];
                }
                // Sort by tanggal
                usort($historyRows, function ($a, $b) {
                    return strcmp($a['tanggal'], $b['tanggal']);
                });
                // Tambahkan kolom stock_awal
                for ($i = 0; $i < count($historyRows); $i++) {
                    if ($i === 0) {
                        $historyRows[$i]['stock_awal'] = 0;
                    } else {
                        $historyRows[$i]['stock_awal'] = $historyRows[$i - 1]['sisa'];
                    }
                }
                // Filter by start_date and end_date if provided
                $filteredRows = $historyRows;
                if ($startDate || $endDate) {
                    $filteredRows = array_filter($historyRows, function ($row) use ($startDate, $endDate) {
                        $rowDate = $row['tanggal'];
                        if (!$rowDate || $rowDate === '-') return false;
                        if ($startDate && $rowDate < $startDate) return false;
                        if ($endDate && $rowDate > $endDate) return false;
                        return true;
                    });
                }
                // Output table
                echo "<table border='1' cellpadding='4' cellspacing='0' style='margin:10px 0 20px 0;background:#222;color:#fff;'>";
                echo "<tr style='background:#333;color:#ffff00;'><th>Tanggal</th><th>Keterangan</th><th>Stock Awal</th><th>Masuk</th><th>Keluar</th><th>Sisa</th></tr>";
                foreach ($filteredRows as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['tanggal']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                    echo "<td style='text-align:right;'>" . number_format($row['stock_awal'], 2) . "</td>";
                    echo "<td style='text-align:right;'>" . number_format($row['masuk'], 2) . "</td>";
                    echo "<td style='text-align:right;'>" . number_format($row['keluar'], 2) . "</td>";
                    echo "<td style='text-align:right;font-weight:bold;color:#00ff00;'>" . number_format($row['sisa'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</pre>";
            }
        }
        echo "</div>";
        exit;
    }

    /**
     * Production endpoint: Akumulasi Historikal (All Stocks Combined)
     * POST: /api/v2/feed/usages/akumulasi
     * Params: livestock_id, feed_id, start_date, end_date (optional)
     */
    public function getFeedCardByLivestockAkumulasi(Request $request)
    {
        $validated = $request->validate([
            'livestock_id' => 'required|uuid',
            'feed_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $livestockId = $validated['livestock_id'];
        $feedId = $validated['feed_id'];
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        try {
            $stocks = \App\Models\FeedStock::with([
                'feed',
                'feedPurchase',
                'feedPurchaseItem',
                'feedPurchaseItem.feedPurchase.supplier',
                'feedPurchase.supplier',
                'feedUsageDetails.feedUsage.livestock',
                'mutationDetails.mutation.toLivestock',
                'incomingMutation.mutation.fromLivestock',
            ])
                ->where('livestock_id', $livestockId)
                ->where('feed_id', $feedId)
                ->get();

            $allEvents = [];
            foreach ($stocks as $stock) {
                // Stock Masuk
                $stockInDate = null;
                if ($stock->feedPurchaseItem && $stock->feedPurchaseItem->feedPurchase && $stock->feedPurchaseItem->feedPurchase->date) {
                    $stockInDate = $stock->feedPurchaseItem->feedPurchase->date->format('Y-m-d');
                } elseif ($stock->feedPurchase && $stock->feedPurchase->date) {
                    $stockInDate = $stock->feedPurchase->date->format('Y-m-d');
                } else {
                    $stockInDate = $stock->created_at ? $stock->created_at->format('Y-m-d') : '-';
                }
                $supplierName = '-';
                if ($stock->feedPurchaseItem && $stock->feedPurchaseItem->feedPurchase && $stock->feedPurchaseItem->feedPurchase->supplier) {
                    $supplierName = $stock->feedPurchaseItem->feedPurchase->supplier->name ?? '-';
                } elseif ($stock->feedPurchase && $stock->feedPurchase->supplier) {
                    $supplierName = $stock->feedPurchase->supplier->name ?? '-';
                }
                $allEvents[] = [
                    'tanggal' => $stockInDate,
                    'keterangan' => 'Stock Masuk',
                    'masuk' => $stock->quantity_in,
                    'keluar' => 0,
                    'stock_id' => $stock->id,
                    'purchase_info' => $stock->feedPurchaseItem ? 'FPI:' . $stock->feedPurchaseItem->id : ($stock->feedPurchase ? 'FP:' . $stock->feedPurchase->id : '-'),
                    'supplier' => $supplierName,
                ];
                // Usages
                $usages = $stock->feedUsageDetails->sortBy(function ($u) {
                    return $u->feedUsage->usage_date ?? $u->created_at;
                });
                foreach ($usages as $usage) {
                    $usageDate = $usage->feedUsage->usage_date ?? $usage->created_at;
                    $qty = floatval($usage->quantity_taken);
                    $allEvents[] = [
                        'tanggal' => $usageDate ? (is_string($usageDate) ? $usageDate : $usageDate->format('Y-m-d')) : '-',
                        'keterangan' => 'Pemakaian Ternak ' . ($usage->feedUsage->livestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $qty,
                        'stock_id' => $stock->id,
                        'purchase_info' => $stock->feedPurchaseItem ? 'FPI:' . $stock->feedPurchaseItem->id : ($stock->feedPurchase ? 'FP:' . $stock->feedPurchase->id : '-'),
                        'supplier' => '-',
                    ];
                }
                // Mutations
                $mutations = $stock->mutationDetails->sortBy(function ($m) {
                    return $m->mutation->date ?? $m->created_at;
                });
                foreach ($mutations as $mutation) {
                    $mutationDate = $mutation->mutation->date ?? $mutation->created_at;
                    $qty = floatval($mutation->quantity);
                    $allEvents[] = [
                        'tanggal' => $mutationDate ? (is_string($mutationDate) ? $mutationDate : $mutationDate->format('Y-m-d')) : '-',
                        'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $qty,
                        'stock_id' => $stock->id,
                        'purchase_info' => $stock->feedPurchaseItem ? 'FPI:' . $stock->feedPurchaseItem->id : ($stock->feedPurchase ? 'FP:' . $stock->feedPurchase->id : '-'),
                        'supplier' => '-',
                    ];
                }
            }
            // Urutkan semua event berdasarkan tanggal
            usort($allEvents, function ($a, $b) {
                return strcmp($a['tanggal'], $b['tanggal']);
            });
            // Hitung saldo berjalan akumulasi
            $saldo = 0;
            foreach ($allEvents as $i => &$event) {
                if ($i === 0) {
                    $event['stock_awal'] = 0;
                } else {
                    $event['stock_awal'] = $saldo;
                }
                $saldo = $event['stock_awal'] + ($event['masuk'] ?? 0) - ($event['keluar'] ?? 0);
                $event['sisa'] = $saldo;
            }
            unset($event);
            // Filter by start_date and end_date if provided
            if ($startDate || $endDate) {
                $allEvents = array_filter($allEvents, function ($row) use ($startDate, $endDate) {
                    $rowDate = $row['tanggal'];
                    if (!$rowDate || $rowDate === '-') return false;
                    if ($startDate && $rowDate < $startDate) return false;
                    if ($endDate && $rowDate > $endDate) return false;
                    return true;
                });
            }
            return response()->json([
                'status' => 'success',
                'data' => array_values($allEvents),
            ]);
        } catch (\Exception $e) {
            \Log::error('[FeedController@getFeedCardByLivestockAkumulasi] Error: ' . $e->getMessage(), [
                'livestock_id' => $livestockId,
                'feed_id' => $feedId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
