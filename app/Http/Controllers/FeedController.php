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
            'feedItem:id,code,name,payload',
            'feedStocks',
            'unit'
        ])
            ->where('feed_purchase_batch_id', $batchId)
            ->get(['id', 'feed_purchase_batch_id', 'feed_id', 'quantity', 'price_per_unit', 'unit_id', 'converted_unit', 'price_per_converted_unit', 'converted_quantity']);

        $formatted = $feedPurchases->map(function ($item) {
            $feedItem = optional($item->feedItem);

            // Get proper conversion units from feed payload
            $conversionUnits = collect($feedItem->payload['conversion_units'] ?? []);

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

    public function getFeedCardByLivestock(Request $request)
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
            $stocks = FeedStock::with([
                'feed',
                'feedPurchase.batch',
                'feedUsageDetails.feedUsage.livestock',
                'mutationDetails.mutation.toLivestock',
                'incomingMutation.mutation.fromLivestock',
            ])
                ->where('livestock_id', $livestockId)
                ->where('feed_id', $feedId)
                ->get();

            $result = [];

            // Group stocks by type (purchase or mutation)
            foreach ($stocks as $stock) {
                if ($stock->source_id) {
                    // Check if this is a mutation by looking up the source_id in Mutation model
                    $mutation = \App\Models\Mutation::where('id', $stock->source_id)->first();
                    if ($mutation) {
                        // This is a mutation
                        $result[] = $this->buildMutationInHistory([$stock], $startDate, $endDate);
                    } else {
                        // If source_id exists but not found in Mutation, treat as purchase
                        $result[] = $this->buildFeedPurchaseHistory([$stock], $startDate, $endDate);
                    }
                } else {
                    // No source_id, treat as purchase
                    $result[] = $this->buildFeedPurchaseHistory([$stock], $startDate, $endDate);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => array_filter($result),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function buildFeedPurchaseHistory($items, $startDate, $endDate)
    {
        $first = collect($items)->first();
        if (!$first) {
            return null;
        }

        $purchaseDate = optional($first->feedPurchase->batch)->date;

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
        return $this->formatResult($first, $histories, $purchaseDate, $first->feedPurchase->price_per_unit ?? 0, 'Pembelian', optional($first->feedPurchase->batch)->invoice_number ?? '-');
    }

    private function buildMutationInHistory($items, $startDate, $endDate)
    {
        $first = collect($items)->first();
        if (!$first) {
            return null;
        }

        $mutation = Mutation::find($first->source_id);
        if (!$mutation) {
            return null;
        }

        $mutationDateOnly = $mutation->date ? $mutation->date->format('Y-m-d') : null;
        $startDateOnly = $startDate ? $startDate->format('Y-m-d') : null;
        $endDateOnly = $endDate ? $endDate->format('Y-m-d') : null;

        if (
            !$mutationDateOnly ||
            ($startDateOnly && $mutationDateOnly < $startDateOnly) ||
            ($endDateOnly && $mutationDateOnly > $endDateOnly)
        ) {
            return null;
        }

        $histories = [[
            'tanggal' => $mutationDateOnly,
            'keterangan' => 'Mutasi dari ' . ($mutation->fromLivestock->name ?? '-'),
            'masuk' => collect($items)->sum('quantity_in'),
            'keluar' => 0,
        ]];

        $runningStock = collect($items)->sum('quantity_in');
        $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);
        return $this->formatResult($first, $histories, $mutation->date, 0, 'Mutasi Masuk', '-');
    }

    private function buildMutationInRelationHistory($items, $startDate, $endDate)
    {
        $first = collect($items)->first();
        if (!$first) {
            return null;
        }

        $mutation = $first->incomingMutation->mutation;
        if (!$mutation || ($startDate && $mutation->date < $startDate) || ($endDate && $mutation->date > $endDate)) {
            return null;
        }

        $histories = [[
            'tanggal' => $mutation->date->format('Y-m-d'),
            'keterangan' => 'Mutasi dari ' . ($mutation->fromLivestock->name ?? '-'),
            'masuk' => collect($items)->sum('quantity_in'),
            'keluar' => 0,
        ]];

        $runningStock = collect($items)->sum('quantity_in');
        $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);
        return $this->formatResult($first, $histories, $mutation->date, 0, 'Mutasi Masuk (Relasi)', '-');
    }

    private function formatResult($first, $histories, $tanggal, $harga, $tipe, $noBatch)
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
                'feed_name' => $first->feed->name ?? '-',
                'no_batch' => $noBatch,
                'tanggal' => $tanggal->format('Y-m-d'),
                'harga' => $harga,
                'tipe' => $tipe,
            ],
            'histories' => $histories,
        ];
    }

    protected function processUsageAndMutation($items, array $histories, ?Carbon $startDate, ?Carbon $endDate, &$runningStock): array
    {
        foreach ($items as $stock) {
            // Pemakaian
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
            // Mutasi keluar
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
}
