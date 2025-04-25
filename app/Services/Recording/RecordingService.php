<?
namespace App\Services\Recording;

use App\Models\FeedStock;
use App\Models\Recording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordingService
{
    public function process(Recording $recording, array $itemQuantities)
    {
        foreach ($itemQuantities as $itemId => $quantityUsed) {
            if ($quantityUsed <= 0) continue;

            $remainingQty = $quantityUsed;

            // Ambil stok FIFO
            $stocks = FeedStock::where('item_id', $itemId)
                ->where('ternak_id', $recording->ternak_id)
                ->where('date', '<=', $recording->recording_date)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->get();

            if ($stocks->sum(fn($s) => $s->available_quantity) < $quantityUsed) {
                throw new \Exception("Stok tidak cukup untuk item ID: $itemId");
            }

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $usedQty = min($remainingQty, $available);

                // Update FeedStock
                $stock->increment('quantity_used', $usedQty);

                // Simpan detail ke RecordingItem (mirip FeedUsageDetail)
                // RecordingItem::create([
                //     'recording_id'   => $recording->id,
                //     'feed_stock_id'  => $stock->id,
                //     'item_id'        => $itemId,
                //     'quantity_used'  => $usedQty,
                // ]);

                $remainingQty -= $usedQty;
            }
        }
    }

}
