<?php

namespace App\Http\Controllers;

use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplyController extends Controller
{
    public function getFeedPurchaseBatchDetail($batchId)
    {

        $supplyPurchases = SupplyPurchase::with([
            'supplyItem:id,code,name,unit,unit_conversion,conversion',
            'supplyStocks' // <- relasi baru nanti ditambahkan
        ])
        ->where('supply_purchase_batch_id', $batchId)
        ->get(['id', 'supply_purchase_batch_id', 'supply_id', 'quantity', 'price_per_unit']);

        $formatted = $supplyPurchases->map(function ($item) {
            $supplyItem = optional($item->supplyItem);
            $conversion = floatval($supplyItem->conversion) ?: 1;

            $quantity = floatval($item->quantity);
            $converted_quantity = $quantity / $conversion;

            // Summary dari semua FeedStock berdasarkan purchase
            $used = $item->supplyStocks->sum('quantity_used');
            $mutated = $item->supplyStocks->sum('quantity_mutated');
            $available = $item->supplyStocks->sum('available');

            return [
                'id' => $item->id,
                'code' => $supplyItem->code,
                'name' => $supplyItem->name,
                'quantity' => $quantity,
                'converted_quantity' => $converted_quantity,
                'qty' => $converted_quantity,
                'sisa' => $quantity - $used,
                'unit' => $supplyItem->unit,
                'conversion' => $conversion,
                'price_per_unit' => floatval($item->price_per_unit),
                'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
                    ? floatval($quantity * $item->price_per_unit)
                    : intval($quantity * $item->price_per_unit),

                // Tambahan penggunaan dan mutasi
                'terpakai' => $used / $conversion,
                'mutated' => $mutated / $conversion,
                'available' => $available / $conversion,
            ];
        });

        return response()->json(['data' => $formatted]);
    }

    public function stockEdit(Request $request)
    {
        $id = $request->input('id');
        $value = $request->input('value');
        $column = $request->input('column');
        $user_id = auth()->id();

        // dd($request->all());

        try {
            DB::beginTransaction();

            $supplyPurchase = SupplyPurchase::with('supplyItem')->findOrFail($id);
            $supplyItem = $supplyPurchase->supplyItem;
            $conversion = floatval($supplyItem->conversion) ?: 1;

            $supplyStock = SupplyStock::where('supply_purchase_id', $supplyPurchase->id)->first();

            if ($column === 'qty') {
                $usedQty = $supplyStock->quantity_used ?? 0;
                $mutatedQty = $supplyStock->quantity_mutated ?? 0;
                $sisa = $usedQty + $mutatedQty;
            
                if (($value * $conversion) < $sisa) {
                    return response()->json([
                        'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
                        'status' => 'error'
                    ], 422);
                }
            
                // Update FeedStock
                $supplyStock->update([
                    'quantity_in' => $value * $conversion,
                    'available' => ($value * $conversion) - $sisa,
                    'updated_by' => $user_id,
                ]);
            
                // Update FeedPurchase
                $supplyPurchase->update([
                    'quantity' => $value,
                    'updated_by' => $user_id,
                ]);
            } else {
                // Update price
                $supplyPurchase->update([
                    'price_per_kg' => $value,
                    'updated_by' => $user_id,
                ]);

                $supplyStock->update([
                    'amount' => $supplyPurchase->quantity * $value,
                    'updated_by' => $user_id,
                ]);
            }

            // Update sub_total dan sisa berdasarkan usage
            $subTotal = $supplyPurchase->quantity * $supplyPurchase->price_per_kg;
            $usedQty = $supplyStock->quantity_used ?? 0;
            $mutatedQty = $supplyStock->quantity_mutated ?? 0;
            $available = ($supplyPurchase->quantity * $conversion) - $usedQty - $mutatedQty;

            $supplyStock->update([
                'available' => $available,
                'amount' => $subTotal,
            ]);

            // Update Batch total summary
            $batch = SupplyPurchaseBatch::with('supplyPurchases.supplyItem')->findOrFail($supplyPurchase->supply_purchase_batch_id);

            $totalQty = $batch->supplyPurchases->sum(function ($purchase) {
                $conversion = floatval(optional($purchase->supplyItem)->conversion) ?: 1;
                return $purchase->quantity;
            });

            $totalHarga = $batch->supplyPurchases->sum(function ($purchase) {
                return $purchase->price_per_kg * $purchase->quantity;
            });

            $batch->update([
                'expedition_fee' => $batch->expedition_fee,
                'updated_by' => $user_id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
