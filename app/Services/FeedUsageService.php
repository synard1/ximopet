<?php
namespace App\Services;

use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeedUsageService
{
    public function process(FeedUsage $usage, array $usages)
    {
        foreach ($usages as $usageRow) {
            $feedId = $usageRow['feed_id'];
            $requiredQty = $usageRow['quantity'];

            // Ambil stok FIFO (stok yang masih ada dan belum dipakai/mutasi penuh)
            $stocks = FeedStock::where('livestock_id', $usage->livestock_id)
                ->where('feed_id', $feedId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($stocks as $stock) {
                if ($requiredQty <= 0) break;

                $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $usedQty = min($requiredQty, $available);

                // dd($usedQty);

                // Update stok
                $stock->used += $usedQty;
                $stock->quantity_used += $usedQty;
                $stock->save();

                // Simpan detail pemakaian
                FeedUsageDetail::create([
                    'feed_usage_id' => $usage->id,
                    'feed_stock_id' => $stock->id,
                    'feed_id' => $feedId,
                    'quantity_taken' => $usedQty,
                    'created_by' => auth()->id(),
                ]);

                $requiredQty -= $usedQty;
            }

            if ($requiredQty > 0) {
                throw new \Exception("Stok pakan tidak cukup untuk feed ID: $feedId");
            }
        }
    }
}

// class FeedUsageService
// {
//     public function create(array $data)
//     {
//         return DB::transaction(function () use ($data) {
//             $usage = FeedUsage::create([
//                 'id' => Str::uuid(),
//                 'usage_date' => $data['date'],
//                 'livestock_id' => $data['livestock_id'],
//                 'total_quantity' => 0,
//                 'created_by' => auth()->id(),
//             ]);

//             foreach ($data['usages'] as $usageRow) {
//                 $feedId = $usageRow['feed_id'];
//                 $requiredQty = $usageRow['quantity'];

//                 $stocks = FeedStock::where('livestock_id', $data['livestock_id'])
//                     ->where('feed_id', $feedId)
//                     ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
//                     ->orderBy('date')
//                     ->orderBy('created_at')
//                     ->lockForUpdate()
//                     ->get();

//                 foreach ($stocks as $stock) {
//                     if ($requiredQty <= 0) break;

//                     $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
//                     $usedQty = min($requiredQty, $available);

//                     $stock->used += $usedQty;
//                     $stock->quantity_used += $usedQty;
//                     $stock->save();

//                     FeedUsageDetail::create([
//                         'feed_usage_id' => $usage->id,
//                         'feed_stock_id' => $stock->id,
//                         'feed_id' => $stock->feed_id,
//                         'quantity_taken' => $usedQty,
//                         'created_by' => auth()->id(),
//                     ]);

//                     $requiredQty -= $usedQty;
//                 }

//                 if ($requiredQty > 0) {
//                     throw new \Exception("Stok pakan tidak cukup untuk feed ID: $feedId");
//                 }
//             }

//             return $usage;
//         });
//     }
// }

