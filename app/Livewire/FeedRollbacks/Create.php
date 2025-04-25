<?php

namespace App\Livewire\FeedRollbacks;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ekspedisi;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\FeedRollback;
use App\Models\FeedRollbackItem;
use App\Models\FeedRollbackLog;
use App\Models\Rekanan;
use App\Models\Item;
use App\Models\Ternak;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    public $date, $ternak_id, $reason, $usages = [];

    public function mount()
    {
        $this->date = now()->toDateString();
    }

    public function loadUsages()
    {
        $this->usages = FeedUsage::where('ternak_id', $this->ternak_id)
            ->whereDate('usage_date', $this->date)
            ->with('details.feedStock')
            ->get()
            ->flatMap(function ($usage) {
                return $usage->details->map(function ($detail) {
                    return [
                        'feed_usage_detail_id' => $detail->id,
                        'feed_id' => $detail->feed_id,
                        'quantity_taken' => $detail->quantity_taken,
                        'quantity_to_rollback' => $detail->quantity_taken,
                    ];
                });
            })->toArray();
    }

    public function save()
    {
        $this->validate([
            'date' => 'required|date',
            'ternak_id' => 'required|exists:ternaks,id',
            'usages' => 'array|min:1',
        ]);

        DB::beginTransaction();

        // dd($this->usages);

        try {
            $rollback = FeedRollback::create([
                'id' => Str::uuid(),
                'performed_by' => auth()->id(),
                'rollback_type' => 'usage',
                'notes' => $this->reason,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->usages as $item) {
                if ($item['quantity_to_rollback'] <= 0) continue;

                $detail = FeedUsageDetail::find($item['feed_usage_detail_id']);
                $stock = $detail->feedStock;

                // Update feed_usage_details data
                $detail->quantity_taken -= $item['quantity_to_rollback'];
                $detail->save();

                // Update feed stock
                $stock->quantity_used -= $item['quantity_to_rollback'];
                $stock->save();

                FeedRollbackItem::create([
                    'feed_rollback_id' => $rollback->id,
                    'target_id' => $item['feed_usage_detail_id'],
                    'target_type' => 'stock',
                    'feed_usage_detail_id' => $detail->id,
                    'quantity_restored' => $item['quantity_to_rollback'],
                    'created_by' => auth()->id(),
                ]);

                FeedRollbackLog::create([
                    'feed_rollback_id' => $rollback->id,
                    'before' => json_encode([
                        'feed_usage_detail_id' => $detail->id,
                        'feed_usage_id' => $detail->feed_usage_id,
                        'feed_stock_id' => $detail->feed_stock_id,
                        'feed_id' => $detail->feed_id,
                        'quantity_taken' => $detail->quantity_taken,
                        'taken_at' => $detail->created_at,
                    ]),
                    'after' => null, // kosong karena data dihapus
                    'created_by' => auth()->id(),
                ]);

                // // Update rollback data
                // $rollback->target_id = $item['feed_usage_detail_id'];
                // $stock->save();
            }

            DB::commit();
            session()->flash('success', 'Rollback berhasil disimpan.');
            // return redirect()->route('feed-rollback.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('rollback_error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.feed-rollbacks.create', [
            'ternaks' => Ternak::all(),
        ]);
    }
}

