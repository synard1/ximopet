<?php

namespace App\Http\Controllers;

use App\Models\CurrentSupply;
use App\Models\SupplyMutation;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SupplyController extends Controller
{

    public function getSupplyPurchaseBatchDetail($batchId)
    {
        $supplyPurchases = SupplyPurchase::with([
            'supplyItem:id,code,name,data',
            'supplyStocks',
            'unit'
        ])
            ->where('supply_purchase_batch_id', $batchId)
            ->get(['id', 'supply_purchase_batch_id', 'supply_id', 'quantity', 'price_per_unit', 'unit_id', 'converted_unit', 'price_per_converted_unit', 'converted_quantity']);

        // dd($supplyPurchases);

        $formatted = $supplyPurchases->map(function ($item) {
            $supplyItem = optional($item->supplyItem);

            // Get proper conversion units from supply payload
            $conversionUnits = collect($supplyItem->data['conversion_units'] ?? []);

            // Get the purchase unit and converted (smallest) unit information
            $purchaseUnitId = $item->unit_id;
            $convertedUnitId = $item->converted_unit;

            $purchaseUnit = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
            $smallestUnit = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
                $conversionUnits->firstWhere('is_smallest', true);

            // Original quantity in purchase units
            $quantity = floatval($item->quantity);

            // Separate stocks into direct and mutation-derived
            $directStocks = $item->supplyStocks->filter(function ($stock) {
                return $stock->supply_purchase_id != null && $stock->source_type != 'mutation';
            });

            $mutationDerivedStocks = $item->supplyStocks->filter(function ($stock) {
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
                    'kode' => $supplyItem->code,
                    'name' => $supplyItem->name,
                    'quantity' => $quantity,
                    'converted_quantity' => $convertedQuantity,
                    'sisa' => round($sisaPurchaseUnits, 2),
                    'unit' => $item->unit->name ?? '-',
                    'unit_conversion' => $smallestUnit['label'] ?? ($supplyItem->data['unit_details']['name'] ?? '-'),
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
                $conversionFactor = floatval($supplyItem->conversion) ?: 1;

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
                    'kode' => $supplyItem->code,
                    'name' => $supplyItem->name,
                    'quantity' => $quantity,
                    'converted_quantity' => $convertedQuantity,
                    'sisa' => round($sisaPurchaseUnits, 2),
                    'unit' => $item->unit->name ?? '-',
                    'unit_conversion' => $supplyItem->data['unit_details']['name'] ?? '-',
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

    // public function getSupplyPurchaseBatchDetail($batchId)
    // {
    //     $supplyPurchases = SupplyPurchase::with([
    //         'supplyItem:id,code,name,payload',
    //         'supplyStocks',
    //         'unit'
    //     ])
    //         ->where('supply_purchase_batch_id', $batchId)
    //         ->get(['id', 'supply_purchase_batch_id', 'supply_id', 'quantity', 'price_per_unit', 'unit_id', 'converted_unit', 'price_per_converted_unit', 'converted_quantity']);

    //     $formatted = $supplyPurchases->map(function ($item) {
    //         $supplyItem = optional($item->supplyItem);

    //         // Get proper conversion units from supply payload
    //         $conversionUnits = collect($supplyItem->payload['conversion_units'] ?? []);

    //         // Get the purchase unit and converted (smallest) unit information
    //         $purchaseUnitId = $item->unit_id;
    //         $convertedUnitId = $item->converted_unit;

    //         $purchaseUnit = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
    //         $smallestUnit = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
    //             $conversionUnits->firstWhere('is_smallest', true);

    //         // Original quantity in purchase units
    //         $quantity = floatval($item->quantity);

    //         // Separate stocks into direct and mutation-derived
    //         $directStocks = $item->supplyStocks->filter(function ($stock) {
    //             return $stock->supply_purchase_id != null && $stock->source_type != 'mutation';
    //         });

    //         $mutationDerivedStocks = $item->supplyStocks->filter(function ($stock) {
    //             return $stock->source_type == 'mutation';
    //         });

    //         // Calculate usage from direct stocks (for sisa calculation)
    //         $directUsedSmallestUnits = $directStocks->sum('quantity_used');
    //         $directMutatedSmallestUnits = $directStocks->sum('quantity_mutated');
    //         $directAvailableSmallestUnits = $directStocks->sum('available');

    //         // Calculate usage from mutation-derived stocks (for total usage reporting)
    //         $mutationUsedSmallestUnits = $mutationDerivedStocks->sum('quantity_used');
    //         $mutationMutatedSmallestUnits = $mutationDerivedStocks->sum('quantity_mutated');

    //         // Total usage (from both direct and mutation-derived)
    //         $totalUsedSmallestUnits = $directUsedSmallestUnits + $mutationUsedSmallestUnits;
    //         $totalMutatedSmallestUnits = $directMutatedSmallestUnits + $mutationMutatedSmallestUnits;

    //         // If we have proper conversion units in payload, use them
    //         if ($purchaseUnit && $smallestUnit) {
    //             // Get conversion values
    //             $purchaseUnitValue = floatval($purchaseUnit['value']);
    //             $smallestUnitValue = floatval($smallestUnit['value']);

    //             // Converted quantity in smallest units
    //             $convertedQuantity = ($quantity * $purchaseUnitValue) / $smallestUnitValue;

    //             // Convert direct usage to purchase units (for sisa calculation)
    //             $directUsedPurchaseUnits = ($directUsedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;
    //             $directMutatedPurchaseUnits = ($directMutatedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;
    //             $directAvailablePurchaseUnits = ($directAvailableSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;

    //             // Convert total usage to purchase units (for display)
    //             $totalUsedPurchaseUnits = ($totalUsedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;
    //             $totalMutatedPurchaseUnits = ($totalMutatedSmallestUnits * $smallestUnitValue) / $purchaseUnitValue;

    //             // Calculate remaining based on direct usage only (to avoid double counting)
    //             $sisaPurchaseUnits = max(0, $quantity - $directUsedPurchaseUnits - $directMutatedPurchaseUnits);

    //             return [
    //                 'id' => $item->id,
    //                 'code' => $supplyItem->code,
    //                 'name' => $supplyItem->name,
    //                 'quantity' => $quantity,
    //                 'converted_quantity' => $convertedQuantity,
    //                 'sisa' => round($sisaPurchaseUnits, 2),
    //                 'unit' => $item->unit->name ?? '-',
    //                 'unit_conversion' => $smallestUnit['label'] ?? ($supplyItem->payload['unit_details']['name'] ?? '-'),
    //                 'conversion' => $purchaseUnitValue / $smallestUnitValue,
    //                 'price_per_unit' => floatval($item->price_per_unit),
    //                 'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
    //                     ? floatval($quantity * $item->price_per_unit)
    //                     : intval($quantity * $item->price_per_unit),
    //                 'terpakai' => round($directUsedPurchaseUnits, 2),         // Direct usage only (for calculations)
    //                 'mutated' => round($directMutatedPurchaseUnits, 2),       // Direct mutations only (for calculations)
    //                 'available' => round($directAvailablePurchaseUnits, 2),   // Direct available only (for calculations)
    //                 'total_terpakai' => round($totalUsedPurchaseUnits, 2),    // Total usage including from mutations (for reporting)
    //                 'total_mutated' => round($totalMutatedPurchaseUnits, 2),  // Total mutations including from mutations (for reporting)
    //             ];
    //         } else {
    //             // Legacy fallback - simple conversion based on the old 'conversion' field
    //             $conversionFactor = floatval($supplyItem->conversion) ?: 1;

    //             $convertedQuantity = $quantity * $conversionFactor;

    //             // Convert direct usage to purchase units (for sisa calculation)
    //             $directUsedPurchaseUnits = $directUsedSmallestUnits / $conversionFactor;
    //             $directMutatedPurchaseUnits = $directMutatedSmallestUnits / $conversionFactor;
    //             $directAvailablePurchaseUnits = $directAvailableSmallestUnits / $conversionFactor;

    //             // Convert total usage to purchase units (for display)
    //             $totalUsedPurchaseUnits = $totalUsedSmallestUnits / $conversionFactor;
    //             $totalMutatedPurchaseUnits = $totalMutatedSmallestUnits / $conversionFactor;

    //             // Calculate remaining based on direct usage only (to avoid double counting)
    //             $sisaPurchaseUnits = max(0, $quantity - $directUsedPurchaseUnits - $directMutatedPurchaseUnits);

    //             return [
    //                 'id' => $item->id,
    //                 'code' => $supplyItem->code,
    //                 'name' => $supplyItem->name,
    //                 'quantity' => $quantity,
    //                 'converted_quantity' => $convertedQuantity,
    //                 'sisa' => round($sisaPurchaseUnits, 2),
    //                 'unit' => $item->unit->name ?? '-',
    //                 'unit_conversion' => $supplyItem->payload['unit_details']['name'] ?? '-',
    //                 'conversion' => $conversionFactor,
    //                 'price_per_unit' => floatval($item->price_per_unit),
    //                 'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
    //                     ? floatval($quantity * $item->price_per_unit)
    //                     : intval($quantity * $item->price_per_unit),
    //                 'terpakai' => round($directUsedPurchaseUnits, 2),         // Direct usage only (for calculations)
    //                 'mutated' => round($directMutatedPurchaseUnits, 2),       // Direct mutations only (for calculations)
    //                 'available' => round($directAvailablePurchaseUnits, 2),   // Direct available only (for calculations)
    //                 'total_terpakai' => round($totalUsedPurchaseUnits, 2),    // Total usage including from mutations (for reporting)
    //                 'total_mutated' => round($totalMutatedPurchaseUnits, 2),  // Total mutations including from mutations (for reporting)
    //             ];
    //         }
    //     });

    //     return response()->json(['data' => $formatted]);
    // }

    // public function getSupplyPurchaseBatchDetail($batchId)
    // {

    //     $supplyPurchases = SupplyPurchase::with([
    //         'supplyItem:id,code,name,payload',
    //         'supplyStocks' // <- relasi baru nanti ditambahkan
    //     ])
    //         ->where('supply_purchase_batch_id', $batchId)
    //         ->get(['id', 'supply_purchase_batch_id', 'supply_id', 'quantity', 'price_per_unit']);

    //     $formatted = $supplyPurchases->map(function ($item) {
    //         $supplyItem = optional($item->supplyItem);
    //         $conversion = floatval($supplyItem->conversion) ?: 1;

    //         $quantity = floatval($item->quantity);
    //         $converted_quantity = $quantity / $conversion;

    //         // Summary dari semua SupplyStock berdasarkan purchase
    //         $used = $item->supplyStocks->sum('quantity_used');
    //         $mutated = $item->supplyStocks->sum('quantity_mutated');
    //         $available = $item->supplyStocks->sum('available');

    //         // dd($supplyItem->payload);
    //         return [
    //             'id' => $item->id,
    //             'code' => $supplyItem->code,
    //             'name' => $supplyItem->name,
    //             'quantity' => $quantity,
    //             'converted_quantity' => $converted_quantity,
    //             'qty' => $converted_quantity,
    //             'sisa' => $quantity - $used,
    //             'unit' => $supplyItem->payload['unit_details']['name'] ?? '-',
    //             'conversion' => $conversion,
    //             'price_per_unit' => floatval($item->price_per_unit),
    //             'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
    //                 ? floatval($quantity * $item->price_per_unit)
    //                 : intval($quantity * $item->price_per_unit),

    //             // Tambahan penggunaan dan mutasi
    //             'terpakai' => $used / $conversion,
    //             'mutated' => $mutated / $conversion,
    //             'available' => $available / $conversion,
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

            // Get supply purchase with all necessary relations
            $supplyPurchase = SupplyPurchase::with([
                'supplyItem',
                'supplyStocks'
            ])->findOrFail($id);

            Log::info("Fetched SupplyPurchase", ['id' => $id, 'supplyPurchase' => $supplyPurchase]);

            $supplyItem = $supplyPurchase->supplyItem;
            $conversion = floatval($supplyItem->conversion) ?: 1;

            // Get the associated supply stock
            $supplyStock = SupplyStock::where('source_id', $supplyPurchase->id)->first();

            if (!$supplyStock) {
                Log::error("Stock record not found for this purchase", ['id' => $id]);
                return response()->json([
                    'message' => 'Stock record not found for this purchase',
                    'status' => 'error'
                ], 404);
            }

            // Calculate used and mutated quantities
            $usedQty = $supplyStock->quantity_used ?? 0;
            $mutatedQty = $supplyStock->quantity_mutated ?? 0;
            $totalUsed = $usedQty + $mutatedQty;

            Log::info("Calculated quantities", ['usedQty' => $usedQty, 'mutatedQty' => $mutatedQty, 'totalUsed' => $totalUsed]);

            $originalQuantity = $supplyPurchase->quantity;
            $originalPricePerUnit = $supplyPurchase->price_per_unit;

            if ($column === 'quantity') {
                // Convert the new quantity value using the conversion factor
                $convertedQuantity = $value * $conversion;

                // Check if new quantity is less than what's already used/mutated
                if ($convertedQuantity < $totalUsed) {
                    Log::warning("New quantity is less than used/mutated", ['convertedQuantity' => $convertedQuantity, 'totalUsed' => $totalUsed]);
                    return response()->json([
                        'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
                        'status' => 'error'
                    ], 422);
                }

                // Calculate the available quantity after usage and mutation
                $available = $convertedQuantity - $totalUsed;

                // Update SupplyStock
                $supplyStock->update([
                    'quantity_in' => $convertedQuantity,
                    'updated_by' => $user_id,
                ]);

                Log::info("Updated SupplyStock", ['supplyStock' => $supplyStock]);

                // Update SupplyPurchase with all necessary fields
                $supplyPurchase->update([
                    'quantity' => $value,
                    'converted_quantity' => $convertedQuantity,
                    'price_per_converted_unit' => $supplyPurchase->price_per_unit / $conversion,
                    'updated_by' => $user_id,
                ]);

                Log::info("Updated SupplyPurchase", ['supplyPurchase' => $supplyPurchase]);

                // Update CurrentSupply quantity
                $currentSupply = CurrentSupply::where('item_id', $supplyPurchase->supply_id)->first();
                if ($currentSupply) {
                    $currentSupply->update([
                        'quantity' => $available,
                        'updated_by' => $user_id,
                    ]);
                    Log::info("Updated CurrentSupply", ['currentSupply' => $currentSupply]);
                }
            } else if ($column === 'price_per_unit') {
                // Update price_per_unit and calculate price_per_converted_unit
                $pricePerUnit = floatval($value);
                $pricePerConvertedUnit = $pricePerUnit / $conversion;

                // Update SupplyPurchase with both price fields
                $supplyPurchase->update([
                    'price_per_unit' => $pricePerUnit,
                    'price_per_converted_unit' => $pricePerConvertedUnit,
                    'updated_by' => $user_id,
                ]);

                Log::info("Updated price fields in SupplyPurchase", ['pricePerUnit' => $pricePerUnit, 'pricePerConvertedUnit' => $pricePerConvertedUnit]);

                // Update the amount in SupplyStock
                $amount = $supplyPurchase->quantity * $pricePerUnit;
                $supplyStock->update([
                    'quantity_in' => $amount,
                    'updated_by' => $user_id,
                ]);

                Log::info("Updated SupplyStock amount", ['amount' => $amount]);
            }

            // Check if any actual changes were made
            $updateSuccessful = ($column === 'quantity' && $originalQuantity != $value) ||
                ($column === 'price_per_unit' && $originalPricePerUnit != $value);

            DB::commit();

            if ($updateSuccessful) {
                Log::info("Update successful", ['updateSuccessful' => $updateSuccessful]);
                return response()->json([
                    'message' => 'Berhasil Update Data',
                    'status' => 'success'
                ]);
            } else {
                Log::info("Update failed: No changes made", ['updateSuccessful' => $updateSuccessful]);
                return response()->json([
                    'message' => 'Gagal melakukan update: Tidak ada perubahan data',
                    'status' => 'error'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in stockEdit", ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal melakukan update: ' . $e->getMessage(),
                'status' => 'error'
            ], 400);
        }
    }

    // public function stockEdit(Request $request)
    // {
    //     $id = $request->input('id');
    //     $value = $request->input('value');
    //     $column = $request->input('column');
    //     $user_id = auth()->id();

    //     // dd($request->all());

    //     try {
    //         DB::beginTransaction();

    //         $supplyPurchase = SupplyPurchase::with('supplyItem')->findOrFail($id);
    //         $supplyItem = $supplyPurchase->supplyItem;
    //         $conversion = floatval($supplyItem->conversion) ?: 1;

    //         // dd($supplyPurchase->id);

    //         $supplyStock = SupplyStock::where('source_id', $supplyPurchase->id)->first();

    //         // dd($supplyStock);

    //         if ($column === 'qty') {
    //             $usedQty = $supplyStock->quantity_used ?? 0;
    //             $mutatedQty = $supplyStock->quantity_mutated ?? 0;
    //             $sisa = $usedQty + $mutatedQty;

    //             // dd([
    //             //     'usedQty' => $usedQty,
    //             //     'mutatedQty' => $mutatedQty,
    //             //     'sisa' => $sisa
    //             // ]);

    //             if (($value * $conversion) < $sisa) {
    //                 return response()->json([
    //                     'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
    //                     'status' => 'error'
    //                 ], 422);
    //             }

    //             // Update SupplyStock
    //             $supplyStock->update([
    //                 'quantity_in' => $value * $conversion,
    //                 'available' => ($value * $conversion) - $sisa,
    //                 'updated_by' => $user_id,
    //             ]);

    //             // Update SupplyPurchase
    //             $supplyPurchase->update([
    //                 'quantity' => $value,
    //                 'updated_by' => $user_id,
    //             ]);
    //         } else {
    //             // Update price
    //             $supplyPurchase->update([
    //                 'price_per_unit' => $value,
    //                 'updated_by' => $user_id,
    //             ]);

    //             $supplyStock->update([
    //                 'amount' => $supplyPurchase->quantity * $value,
    //                 'updated_by' => $user_id,
    //             ]);
    //         }

    //         // Update sub_total dan sisa berdasarkan usage
    //         $subTotal = $supplyPurchase->quantity * $supplyPurchase->price_per_unit;
    //         $usedQty = $supplyStock->quantity_used ?? 0;
    //         $mutatedQty = $supplyStock->quantity_mutated ?? 0;
    //         $available = ($supplyPurchase->quantity * $conversion) - $usedQty - $mutatedQty;

    //         $supplyStock->update([
    //             'available' => $available,
    //             'amount' => $subTotal,
    //         ]);

    //         // Update Batch total summary
    //         $batch = SupplyPurchaseBatch::with('supplyPurchases.supplyItem')->findOrFail($supplyPurchase->supply_purchase_batch_id);

    //         $totalQty = $batch->supplyPurchases->sum(function ($purchase) {
    //             $conversion = floatval(optional($purchase->supplyItem)->conversion) ?: 1;
    //             return $purchase->quantity;
    //         });

    //         $totalHarga = $batch->supplyPurchases->sum(function ($purchase) {
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

    public function getSupplyByFarm(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'required|uuid',
            'supply_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $farmId = $validated['farm_id'];
        $supplyId = $validated['supply_id'];
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        try {
            $stocks = SupplyStock::with([
                'supply',
                'supplyPurchase.batch',
                'supplyUsageDetails.supplyUsage.supply',
                'mutationDetails.mutation.toFarm',
                'incomingMutation.mutation.fromFarm',
            ])
                ->where('farm_id', $farmId)
                ->where('supply_id', $supplyId)
                ->get();

            $result = [];

            // Proses transaksi pembelian awal
            $purchaseStocks = $stocks->whereNotNull('supply_purchase_id')->groupBy('supply_purchase_id');
            foreach ($purchaseStocks as $purchaseId => $items) {
                $first = $items->first();
                $histories = [];
                $purchaseDate = optional($first->supplyPurchase->batch)->date;

                if ($purchaseDate && (!$startDate || $purchaseDate >= $startDate) && (!$endDate || $purchaseDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $purchaseDate->format('Y-m-d'),
                        'keterangan' => 'Pembelian',
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the purchase
                        'keluar' => 0,
                    ];

                    $runningStock = $items->sum('quantity_in'); // Initial stock after purchase
                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'supply_purchase_info' => [
                            'supply_name' => $first->supply->name ?? '-',
                            'no_batch' => optional($first->supplyPurchase->batch)->invoice_number ?? '-',
                            'tanggal' => $purchaseDate->format('Y-m-d'),
                            'harga' => $first->supplyPurchase->price_per_unit ?? 0,
                            'tipe' => 'Pembelian',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            // Proses transaksi mutasi masuk
            $mutationInStocks = $stocks->whereNotNull('source_id')->groupBy('source_id');
            foreach ($mutationInStocks as $mutationId => $items) {
                $first = $items->first();
                $mutation = SupplyMutation::find($mutationId);
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
                        'supply_purchase_info' => [
                            'supply_name' => $first->supply->name ?? '-',
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
            $mutationInByRelationStocks = $stocks->whereNull('source_id')->whereNotNull('incomingMutation')->groupBy('incomingMutation.supply_mutation_id');
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
                        'supply_purchase_info' => [
                            'supply_name' => $first->supply->name ?? '-',
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

    protected function processUsageAndMutation($items, array $histories, ?Carbon $startDate, ?Carbon $endDate, &$runningStock): array
    {
        foreach ($items as $stock) {
            // Pemakaian
            foreach ($stock->supplyUsageDetails as $usageDetail) {
                $usageDate = $usageDetail->supplyUsage->usage_date;
                if ($usageDate && (!$startDate || $usageDate >= $startDate) && (!$endDate || $usageDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $usageDate->format('Y-m-d'),
                        'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->supplyUsage->farm->name ?? '-'),
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
                        'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toFarm->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $mutation->quantity,
                    ];
                }
            }
        }
        return $histories;
    }
}
