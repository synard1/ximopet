<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockPurchase;
use App\Models\LivestockPurchaseItem;
use App\Models\LivestockStock;
use App\Models\CurrentLivestock;
use App\Models\LivestockPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LivestockPurchaseService
{
    public function updateDOC(array $data, $livestockId): void
    {
        DB::beginTransaction();

        $livestock = Livestock::findOrFail($livestockId);

        // Get the LivestockPurchase through the LivestockPurchaseItem
        $purchaseItem = LivestockPurchaseItem::where('livestock_id', $livestock->id)
        // ->with('livestockPurchase.items', 'livestockPurchase.livestock')
        ->firstOrFail();

        $purchase = $purchaseItem->livestockPurchase;

        // Now $purchase contains the LivestockPurchase model with its items and livestocks
        // dd($data);

        // dd([
        //     'purchase' => $purchase,
        //     'livestock' => $livestock,
        //     'purchaseItem' => $purchaseItem,
        // ]);

        // Validasi perubahan jumlah jika sudah ada transaksi
        if ($purchaseItem->jumlah != $data['qty']) {
            $hasDailyRecords = $livestock->recordings()->exists(); // misal recording sudah dibuat
            if ($hasDailyRecords) {
                throw new \Exception('Tidak dapat mengubah jumlah DOC karena sudah ada transaksi harian.');
            }
        }

        $totalBerat = $data['qty'] * $data['berat'];
        $subTotal = $data['qty'] * $data['harga'];

        // Update LivestockPurchase
        $purchase->update([
            'tanggal' => $data['tanggal'],
            // 'invoice_number' => $data['faktur'],
            'vendor_id' => $data['rekanan_id'],
            'updated_by' => auth()->id(),
        ]);

        // Update first item
        // $purchase->items->first()->update([
        //     'jumlah' => $data['qty'],
        //     'harga_per_ekor' => $data['harga'],
        //     'updated_by' => auth()->id(),
        // ]);

        // Update Livestock
        $purchaseItem->livestock->update([
            'standar_bobot_id' => $data['standar_bobot_id'],
            'berat_awal' => $data['berat'],
            // 'breed' => $data['docSelect'],
            'pic' => $data['pic'],
            'updated_by' => auth()->id(),
        ]);

        // Update LivestockStock
        Livestock::where('id', $livestock->id)->update([
            'start_date' => $data['tanggal'],
            'farm_id' => $data['farm_id'],
            'kandang_id' => $data['kandang_id'],
            'populasi_awal' => $data['qty'],
            'berat_awal' => $data['berat'],
            'harga' => $data['harga'],
            'updated_by' => auth()->id(),
        ]);

        // Update LivestockPosition
        CurrentLivestock::where('livestock_id', $livestock->id)->update([
            'quantity' => $data['qty'],
            'berat_total' => $totalBerat,
            'avg_berat' => $data['berat'],
            'updated_by' => auth()->id(),
        ]);

        DB::commit();
    }
}
