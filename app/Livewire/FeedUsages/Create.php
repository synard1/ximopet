<?php
namespace App\Livewire\FeedUsages;

use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\FeedRollback;
use App\Models\FeedRollbackItem;
use App\Models\FeedRollbackLog;
use App\Models\Ternak;
use App\Models\Item;
use Livewire\Component;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use App\Services\FeedUsageService;


class Create extends Component
{
    public $date, $ternak_id, $usages = [];
    public $feeds = [];
    public $feedUsageId = null;


    public function mount()
    {
        $this->date = now()->toDateString();
        $this->usages[] = ['feed_id' => '', 'quantity' => ''];
    }

    public function updatedTernakId()
    {
        $this->feeds = FeedStock::where('ternak_id', $this->ternak_id)
            ->with('feed') // pastikan relasi 'feed' di model FeedStock didefinisikan
            ->get()
            ->groupBy('feed_id')
            ->map(function ($stocks) {
                $feed = $stocks->first()->feed;
                $feed->available_quantity = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
                return $feed;
            })
            ->values()
            ->all();

        // dd($this->feeds); // sekarang hasil array-nya adalah model Feed dengan property tambahan 'available_quantity'
    }

    // public function updatedTernakId()
    // {
    //     $this->feeds = FeedStock::where('ternak_id', $this->ternak_id)
    //         ->with('feed') // relasi ke model Feed
    //         ->get()
    //         ->groupBy('feed_id') // group berdasarkan feed
    //         ->map(function ($stocks) {
    //             // ambil data stock pertama sebagai representasi, bisa ditambah stok total kalau mau
    //             $stock = $stocks->first();
    //             $stock->available_quantity = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
    //             return $stock;
    //         })
    //         ->values()
    //         ->all();

    //     dd($this->feeds); // sekarang berisi array FeedStock lengkap dengan relasi `feed`
    // }

    public function addUsageRow()
    {
        $this->usages[] = ['feed_id' => '', 'quantity' => ''];
        
        // Perbarui data stok
        $this->loadFeeds();
    }

    public function removeUsageRow($index)
    {
        unset($this->usages[$index]);
        $this->usages = array_values($this->usages);
        // Perbarui data stok
        $this->loadFeeds();
    }

    public function updatedDate()
    {
        $this->resetErrorBag();

        if (!$this->ternak_id || !$this->date) return;

        $usage = FeedUsage::where('usage_date', $this->date)
            ->where('ternak_id', $this->ternak_id)
            ->first();

        if ($usage) {
            $this->feedUsageId = $usage->id;

            // Group detail by feed_id dan jumlahkan quantity_taken
            $this->usages = FeedUsageDetail::where('feed_usage_id', $usage->id)
                ->select('feed_id', DB::raw('SUM(quantity_taken) as quantity'))
                ->groupBy('feed_id')
                ->get()
                ->map(function ($row) {
                    return [
                        'feed_id' => $row->feed_id,
                        'quantity' => $row->quantity,
                    ];
                })->toArray();

        } else {
            $this->feedUsageId = null;
            $this->usages = [['feed_id' => '', 'quantity' => '']];
        }

        $this->loadFeeds(); // Tetap refresh ketersediaan stok
    }


    public function save()
    {
        $this->validate([
            'date' => 'required|date',
            'ternak_id' => 'required|exists:ternaks,id',
            'usages' => 'required|array|min:1',
            'usages.*.feed_id' => 'required|exists:items,id',
            'usages.*.quantity' => 'required|numeric|min:0.01',
        ]);

        dd($this->all());

        DB::beginTransaction();

        try {
            if ($this->feedUsageId) {
                // UPDATE
                $usage = FeedUsage::findOrFail($this->feedUsageId);
                $hasChanged = $this->hasUsageChanged($usage, $this->usages);

                if (!$hasChanged) {
                    DB::rollBack(); // ga perlu simpan apa pun
                    $this->dispatch('info', 'Tidak ada perubahan data untuk disimpan.');
                    return;
                }

                $usage->update([
                    'usage_date' => $this->date,
                    'ternak_id' => $this->ternak_id,
                    'updated_by' => auth()->id(),
                ]);

                // Kembalikan stok dari detail lama
                $oldDetails = FeedUsageDetail::where('feed_usage_id', $usage->id)->get();

                foreach ($oldDetails as $detail) {
                    $stock = FeedStock::find($detail->feed_stock_id);
                    if ($stock) {
                        $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                        $stock->used = max(0, $stock->used - $detail->quantity_taken); // backward compat
                        $stock->save();
                    }

                    $detail->updated_by = auth()->id();
                    $detail->save();
                    $detail->delete();
                }
            } else {
                // CREATE
                $usage = FeedUsage::create([
                    'id' => Str::uuid(),
                    'usage_date' => $this->date,
                    'ternak_id' => $this->ternak_id,
                    'total_quantity' => 0,
                    'created_by' => auth()->id(),
                ]);
            }

            // Jalankan FIFO baru jika ada perubahan
            app(\App\Services\FeedUsageService::class)->process($usage, $this->usages);

            DB::commit();

            $this->dispatch('success', 'Penggunaan pakan berhasil disimpan.');
            $this->resetForm();

        } catch (ValidationException $e) {
            DB::rollBack();
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
            $this->dispatch('error', 'Validasi gagal.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }



    // public function save()
    // {
    //     $this->validate([
    //         'date' => 'required|date',
    //         'ternak_id' => 'required|exists:ternaks,id',
    //         'usages' => 'required|array|min:1',
    //         // 'usages.*.feed_id' => 'required|exists:items,id',
    //         'usages.*.quantity' => 'required|numeric|min:0.01',
    //     ]);

    //     // dd($this->all());

    //     DB::beginTransaction();

    //     try {
    //         // 1. Simpan master FeedUsage
    //         // $usage = FeedUsage::create([
    //         //     'id' => Str::uuid(),
    //         //     'usage_date' => $this->date,
    //         //     'ternak_id' => $this->ternak_id,
    //         //     'total_quantity' => 0,
    //         //     'created_by' => auth()->id(),
    //         // ]);

    //         if ($this->feedUsageId) {
    //             // UPDATE
    //             $usage = FeedUsage::findOrFail($this->feedUsageId);
    //             $usage->update([
    //                 'usage_date' => $this->date,
    //                 'ternak_id' => $this->ternak_id,
    //                 'updated_by' => auth()->id(),
    //             ]);
            
    //             // Ambil detail lama
    //             $oldDetails = FeedUsageDetail::where('feed_usage_id', $usage->id)->get();
            
    //             // Kembalikan stok
    //             foreach ($oldDetails as $detail) {
    //                 $stock = FeedStock::find($detail->feed_stock_id);
            
    //                 if ($stock) {
    //                     $stock->quantity_used -= $detail->quantity_taken;
    //                     $stock->used -= $detail->quantity_taken;
    //                     if ($stock->quantity_used < 0) $stock->quantity_used = 0; // jaga-jaga
            
    //                     $stock->save();
    //                 }
    //                 // Update updated_by sebelum delete
    //                 $detail->updated_by = auth()->id();
    //                 $detail->save();

    //                 $detail->delete(); // soft delete
    //             }
            
    //             // Hapus detail lama setelah stok dikembalikan tanpa log user yang hapus / update data
    //             // FeedUsageDetail::where('feed_usage_id', $usage->id)->delete();
    //         } else {
    //             // CREATE
    //             $usage = FeedUsage::create([
    //                 'id' => Str::uuid(),
    //                 'usage_date' => $this->date,
    //                 'ternak_id' => $this->ternak_id,
    //                 'total_quantity' => 0,
    //                 'created_by' => auth()->id(),
    //             ]);
    //         }

    //         // Proses FIFO dan simpan detail
    //         app(\App\Services\FeedUsageService::class)->process($usage, $this->usages);


    //         // foreach ($this->usages as $usageRow) {
                
    //         //     $feedId = $usageRow['feed_id'];
    //         //     $requiredQty = $usageRow['quantity'];

    //         //     // dd($this->all());

    //         //     $stocks = FeedStock::where('ternak_id', $this->ternak_id)
    //         //         ->where('feed_id', $feedId)
    //         //         // ->where('id', $feedId)
    //         //         // ->whereDate('date', '<=', $this->date)
    //         //         ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //         //         ->orderBy('date')
    //         //         ->orderBy('created_at')
    //         //         ->lockForUpdate()
    //         //         ->get();
    //         //     // dd($stocks);

    //         //     foreach ($stocks as $stock) {
    //         //         if ($requiredQty <= 0) break;

    //         //         $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
    //         //         $usedQty = min($requiredQty, $available);

    //         //         $stock->used += $usedQty;
    //         //         $stock->quantity_used += $usedQty;
    //         //         $stock->save();

    //         //         FeedUsageDetail::create([
    //         //             // 'id' => Str::uuid(),
    //         //             'feed_usage_id' => $usage->id,
    //         //             'feed_stock_id' => $stock->id,
    //         //             'feed_id' => $stock->feed_id,
    //         //             'quantity_taken' => $usedQty,
    //         //             'created_by' => auth()->id(),
    //         //         ]);

    //         //         $requiredQty -= $usedQty;
    //         //     }

    //         //     if ($requiredQty > 0) {
    //         //         throw new \Exception("Stok pakan tidak cukup untuk feed ID: $feedId");
    //         //     }
    //         // }

    //         DB::commit();
    //         $this->dispatch('success', 'Pembelian pakan berhasil disimpan');
    //         $this->resetForm();
            
    //     } catch (ValidationException $e) {
    //         $this->dispatch('error', 'error');

    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->dispatch('error', 'Terjadi kesalahan saat memperbarui data. ' . $e->getMessage());
    //     } finally {
    //         // $this->reset();
    //     }
    // }

    public function resetForm()
    {
        $this->reset();
        $this->usages = [
            ['feed_id' => '', 'quantity' => 0],
        ];
    }

    protected function hasUsageChanged(FeedUsage $usage, array $newUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('feed_id', DB::raw('SUM(quantity_taken) as total'))
            ->groupBy('feed_id')
            ->get()
            ->keyBy('feed_id');

        foreach ($newUsages as $row) {
            $feedId = $row['feed_id'];
            $qty = (float) $row['quantity'];

            if (!isset($existingDetails[$feedId]) || (float) $existingDetails[$feedId]->total !== $qty) {
                return true; // ada perubahan
            }
        }

        // Cek apakah ada item yang dihapus dari data baru
        if (count($existingDetails) !== count($newUsages)) {
            return true;
        }

        return false;
    }


    public function rollbackAndUpdate()
    {
        $usage = FeedUsage::with('details')->findOrFail($this->feedUsageId);

        // Rollback penggunaan sebelumnya
        foreach ($usage->details as $detail) {
            $stock = $detail->feedStock;
            $stock->quantity_used -= $detail->quantity_taken;
            $stock->used -= $detail->quantity_taken;
            $stock->save();

            FeedRollbackItem::create([
                'feed_rollback_id' => Str::uuid(),
                'feed_stock_id' => $detail->feed_stock_id,
                'feed_id' => $detail->feed_id,
                'quantity_rollback' => $detail->quantity_taken,
                'created_by' => auth()->id(),
            ]);

            FeedRollbackLog::create([
                'feed_rollback_id' => $usage->id, // atau ID rollback baru
                'before' => json_encode($detail->toArray()),
                'created_by' => auth()->id(),
            ]);
        }

        // Hapus detail lama
        $usage->details()->delete();
        $usage->delete();

        // Simpan ulang
        app(FeedUsageService::class)->create([
            'date' => $this->date,
            'ternak_id' => $this->ternak_id,
            'usages' => $this->usages,
        ]);
    }

    public function loadFeeds()
    {
        if (!$this->ternak_id) return;

        $this->feeds = FeedStock::where('ternak_id', $this->ternak_id)
            ->with('feed')
            ->get()
            ->groupBy('feed_id')
            ->map(function ($stocks) {
                $stock = $stocks->first();
                $stock->available_quantity = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);
                return $stock;
            })
            ->values()
            ->all();
    }

    // public function loadFeeds()
    // {
    //     if (!$this->ternak_id) return;

    //     $this->feeds = FeedStock::where('ternak_id', $this->ternak_id)
    //         ->with('feed')
    //         ->get()
    //         ->groupBy('feed_id')
    //         ->map(function ($stocks) {
    //             $stock = $stocks->first();
    //             $stock->available_quantity = $stocks->sum(fn($s) =>
    //                 $s->quantity_in - $s->quantity_used - $s->quantity_mutated
    //             );
    //             return $stock;
    //         })
    //         ->values()
    //         ->all();
    // }


    public function render()
    {
        return view('livewire.feed-usages.create', [
            'ternaks' => Ternak::all(),
            'feeds' => $this->feeds
        ]);
    }
}
