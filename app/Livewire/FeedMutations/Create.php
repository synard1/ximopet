<?php
namespace App\Livewire\FeedMutations;

use App\Models\Item as Feed;
use App\Models\Ternak;
use App\Models\Livestock;
use App\Models\FeedStock;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Create extends Component
{
    public $date;
    public $from_ternak_id;
    public $to_ternak_id;
    public $items = [];

    public function mount()
    {
        $this->date = now()->toDateString();
        $this->items[] = ['feed_id' => '', 'quantity' => ''];
    }

    public function addItem()
    {
        $this->items[] = ['feed_id' => '', 'quantity' => ''];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        // dd($this->all());
        // $this->validate([
        //     'date' => 'required|date',
        //     'from_ternak_id' => 'required|exists:ternaks,id|different:to_ternak_id',
        //     'to_ternak_id' => 'required|exists:ternaks,id',
        //     'items' => 'required|array|min:1',
        //     'items.*.feed_id' => 'required|exists:items,id',
        //     'items.*.quantity' => 'required|numeric|min:0.01',
        // ]);

        DB::beginTransaction();

        try {
            // VALIDASI stok pakan untuk setiap feed
            foreach ($this->items as $item) {
                $feedId = $item['feed_id'];
                $feed = Feed::findOrFail($feedId);
                $requiredQty = $item['quantity'];

                $stocks = FeedStock::where('livestock_id', $this->from_ternak_id)
                    ->where('feed_id', $feedId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

                if ($totalAvailable < $requiredQty) {
                    throw new \Exception("Stok tidak cukup untuk feed: $feed->name. Dibutuhkan: $requiredQty, Tersedia: $totalAvailable");
                }
            }

            // BUAT MUTASI UTAMA
            $mutation = FeedMutation::create([
                'id' => Str::uuid(),
                'date' => $this->date,
                'from_livestock_id' => $this->from_ternak_id,
                'to_livestock_id' => $this->to_ternak_id,
                'created_by' => auth()->id(),
            ]);

            // PROSES PENGURANGAN DAN PENAMBAHAN STOK SECARA FIFO
            foreach ($this->items as $item) {
                $feedId = $item['feed_id'];
                $requiredQty = $item['quantity'];

                $stocks = FeedStock::where('livestock_id', $this->from_ternak_id)
                    ->where('feed_id', $feedId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($stocks as $stock) {
                    if ($requiredQty <= 0) break;

                    $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                    $takeQty = min($available, $requiredQty);

                    // Kurangi stok dari ternak asal
                    $stock->quantity_mutated += $takeQty;
                    $stock->save();

                    // Tambahkan ke stok ternak tujuan
                    FeedStock::create([
                        'id' => Str::uuid(),
                        'livestock_id' => $this->to_ternak_id,
                        'feed_id' => $feedId,
                        'feed_purchase_id' => $stock->feed_purchase_id,
                        'date' => $this->date,
                        'source_id' => $mutation->id,
                        'amount' => $takeQty * ($stock->amount / $stock->quantity_in),
                        'available' => $takeQty,
                        'quantity_in' => $takeQty,
                        'quantity_used' => 0,
                        'quantity_mutated' => 0,
                        'created_by' => auth()->id(),
                    ]);

                    // Simpan detail mutasi
                    FeedMutationItem::create([
                        'feed_mutation_id' => $mutation->id,
                        'feed_stock_id' => $stock->id,
                        'feed_id' => $feedId,
                        'quantity' => $takeQty,
                        'created_by' => auth()->id(),
                    ]);

                    $requiredQty -= $takeQty;
                }
            }

            DB::commit();

            $this->resetForm();

            $this->dispatch('success', 'Mutasi pakan berhasil disimpan');
            $this->dispatch('closeForm');

            // session()->flash('success', 'Mutasi pakan berhasil disimpan.');
            // return redirect()->route('feed-mutations.index');

        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat memperbarui data. ' . $e->getMessage());
        } finally {
            // $this->reset();
        }
    }

    public function resetForm()
    {
        // $this->reset();
        // Reset hanya form input, bukan flash/session
        $this->reset(['date', 'from_ternak_id', 'to_ternak_id', 'items']);

        // Set ulang defaults jika perlu
        $this->date = now()->toDateString();
        $this->items = [['feed_id' => '', 'quantity' => '']];
    }

    // public function save()
    // {
    //     $this->validate([
    //         'date' => 'required|date',
    //         'from_ternak_id' => 'required|exists:ternaks,id|different:to_ternak_id',
    //         'to_ternak_id' => 'required|exists:ternaks,id|different:from_ternak_id',
    //         'items.*.feed_id' => 'required|exists:items,id',
    //         'items.*.quantity' => 'required|numeric|min:0.01',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // Validasi ketersediaan stok dari ternak asal
    //         foreach ($this->items as $item) {
    //             $feedId = $item['feed_id'];
    //             $requiredQty = $item['quantity'];

    //             $stocks = FeedStock::where('ternak_id', $this->from_ternak_id)
    //                 ->where('feed_id', $feedId)
    //                 ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //                 ->orderBy('date')
    //                 ->orderBy('created_at')
    //                 ->lockForUpdate()
    //                 ->get();

    //             $totalAvailable = $stocks->sum(function ($s) {
    //                 return $s->quantity_in - $s->quantity_used - $s->quantity_mutated;
    //             });

    //             if ($totalAvailable < $requiredQty) {
    //                 throw new \Exception("Stok tidak cukup untuk feed ID: $feedId. Dibutuhkan: $requiredQty, Tersedia: $totalAvailable");
    //             }
    //         }

    //         dd($totalAvailable);

    //         $mutation = FeedMutation::create([
    //             'id' => Str::uuid(),
    //             'date' => $this->date,
    //             'from_ternak_id' => $this->from_ternak_id,
    //             'to_ternak_id' => $this->to_ternak_id,
    //             'created_by' => auth()->id(),
    //         ]);

    //         foreach ($this->items as $item) {
    //             $feedId = $item['feed_id'];
    //             $requiredQty = $item['quantity'];
    //             $totalMutated = 0;

    //             // Ambil stok dari ternak asal (FIFO)
    //             $stocks = FeedStock::where('ternak_id', $this->from_ternak_id)
    //                 ->where('feed_id', $feedId)
    //                 ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
    //                 ->orderBy('date')
    //                 ->orderBy('created_at')
    //                 ->lockForUpdate()
    //                 ->get();

    //             FeedMutationItem::create([
    //                 'feed_mutation_id' => $mutation->id,
    //                 'feed_stock_id' => $feedStock->id,
    //                 'feed_id' => $item['feed_id'],
    //                 'quantity' => $item['quantity'],
    //                 'created_by' => auth()->id(),
    //             ]);
    //         }

    //         DB::commit();
    //         // session()->flash('success', 'Mutasi pakan berhasil disimpan.');
    //         $this->dispatch('success', 'Mutasi pakan berhasil disimpan');

    //         // return redirect()->route('feed-mutations.index');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         $this->addError('save_error', $e->getMessage());
    //     }
    // }

    public function close()
    {
        $this->dispatch('closeForm');
    }

    public function render()
    {
        return view('livewire.feed-mutations.create', [
            'livestocks' => Livestock::whereHas('farm.farmOperators', function ($query) {
                                $query->where('user_id', auth()->id());
                            })->get(),
            'feeds' => Feed::all(),
        ]);
    }
}
