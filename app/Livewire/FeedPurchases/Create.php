<?php

namespace App\Livewire\FeedPurchases;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ekspedisi;
use  App\Models\Expedition;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedPurchase;
use App\Models\FeedStock;
use App\Models\Rekanan;
use App\Models\Item;
use App\Models\Livestock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    use WithFileUploads;

    public $livestockId;
    public $invoice_number;
    public $date;
    public $master_rekanan_id;
    public $expedition_id;
    public $expedition_fee = 0;
    public $items = [];
    public $livestock_id;
    public $pembelianId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;



    protected $listeners = [
        'deleteFeedPurchaseBatch' => 'deleteFeedPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        
    ];

    public function mount()
    {
        // $this->date = now()->toDateString();
        $this->items = [
            ['item_id' => '', 'quantity' => '', 'price_per_kg' => '']
        ];
    }

    public function addItem()
    {
        $this->items[] = ['item_id' => '', 'quantity' => '', 'price_per_kg' => ''];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        $this->validate([
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'master_rekanan_id' => 'required|exists:master_rekanans,id',
            'expedition_fee' => 'numeric|min:0',
            'livestock_id' => 'required|exists:livestocks,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price_per_kg' => 'required|numeric|min:0',
        ]);

        // dd($this->all());

        DB::beginTransaction();

        try {
            $batch = FeedPurchaseBatch::create([
                'id' => Str::uuid(),
                'invoice_number' => $this->invoice_number,
                'date' => $this->date,
                'master_rekanan_id' => $this->master_rekanan_id,
                'expedition_id' => $this->expedition_id ?? null,
                'expedition_fee' => $this->expedition_fee ?? 0,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->items as $item) {
                $purchase = FeedPurchase::create([
                    'id' => Str::uuid(),
                    'feed_purchase_batch_id' => $batch->id,
                    'livestock_id' => $this->livestock_id,
                    'feed_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'price_per_kg' => $item['price_per_kg'],
                    'created_by' => auth()->id(),
                ]);

                // Simpan ke feed_stocks
            FeedStock::create([
                'id' => Str::uuid(),
                'livestock_id' => $this->livestock_id,
                'feed_id' => $item['item_id'],
                'feed_purchase_id' => $purchase->id,
                'date' => $this->date,
                'source_id' => $purchase->id, // purchase sebagai sumber
                'amount' => $item['quantity'],
                'used' => 0,
                'available' => $item['quantity'],
                'quantity_in' => $item['quantity'],
                'quantity_used' => 0,
                'quantity_mutated' => 0,
                'created_by' => auth()->id(),
            ]);
            }

            DB::commit();

            // Optionally reset the input fields
            $this->resetForm();
            // $this->reset(['date', 'invoice_number', 'master_rekanan_id', 'expedition_id', 'livestock_id', 'expedition_fee', 'items']);

            // session()->flash('success', 'Pembelian pakan berhasil disimpan.');
            $this->dispatch('success', 'Pembelian pakan berhasil disimpan');
            $this->dispatch('closeForm');

            // return redirect()->route('feed-purchases.index');

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
        $this->reset();
        $this->items = [
            ['item_id' => '', 'quantity' => 1, 'price_per_kg' => 0],
        ];
    }

    public function render()
    {
        return view('livewire.feed-purchases.create', [
            'vendors' => Rekanan::all(),
            'expeditions' => Expedition::all(),
            'feedItems' => Item::all(),
            'livestocks' => Livestock::whereHas('farm.farmOperators', function ($query) {
                                $query->where('user_id', auth()->id());
                            })->get(),
        ]);
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function close()
    {
        $this->dispatch('closeForm');
    }

    public function deleteFeedPurchaseBatch($batchId)
    {
        try {
            DB::beginTransaction();

            $batch = FeedPurchaseBatch::with('feedPurchases')->findOrFail($batchId);

            // Loop semua FeedPurchase di dalam batch
            foreach ($batch->feedPurchases as $purchase) {
                $feedStock = FeedStock::where('feed_purchase_id', $purchase->id)->first();

                // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                if (($feedStock->quantity_used ?? 0) > 0 || ($feedStock->quantity_mutated ?? 0) > 0) {
                    $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                    return;
                }

                // Hapus FeedStock & FeedPurchase
                $feedStock?->delete();
                $purchase->delete();
            }

            // Hapus batch setelah semua anaknya aman
            $batch->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            // $this->dispatch('error', 'Terjadi kesalahan saat menghapus data. ' . $e->getMessage());
        }
    }

    public function updateDoNumber($transaksiId, $newNoSj)
    {
        $transaksiDetail = FeedPurchaseBatch::findOrFail($transaksiId);

        if ($transaksiDetail->exists()) {
            $transaksiDetail->do_number = $newNoSj;
            $transaksiDetail->save();
            $this->dispatch('noSjUpdated');
            $this->dispatch('success', 'Nomor Surat Jalan / Deliveri Order berhasil diperbarui.');
        } else {
            $this->dispatch('error', 'Tidak ada detail transaksi yang ditemukan.');
        }
    }

    public function showEditForm($id)
    {   
        $this->pembelianId = $id;
        $pembelian = FeedPurchaseBatch::with('feedPurchases')->find($id);

        if ($pembelian && $pembelian->feedPurchases->isNotEmpty()) {
            // dd($pembelian->feedPurchases->first()->livestock_id);
            $this->date = $pembelian->date;
            $this->livestockId = $pembelian->feedPurchases->first()->livestock_id;
            $this->invoice_number = $pembelian->invoice_number;
            $this->master_rekanan_id = $pembelian->master_rekanan_id;
            $this->expedition_id = $pembelian->expedition_id;
            $this->expedition_fee = $pembelian->expedition_fee;

            $this->items = [];
            foreach ($pembelian->feedPurchases as $item) {
                $this->items[] = [
                    'livestock_id' => $item->livestock_id,
                    'feed_id' => $item->feed_id,
                    'quantity' => $item->quantity,
                    'price_per_kg' => $item->price_per_kg,
                ];
            }
        }
        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

}
