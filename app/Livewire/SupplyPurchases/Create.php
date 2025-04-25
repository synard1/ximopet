<?php

namespace App\Livewire\SupplyPurchases;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;


use App\Models\Farm;
use App\Models\Rekanan;
use App\Models\Expedition;
use App\Models\Item;
use App\Models\Livestock;
use App\Models\Partner;
use App\Models\Supply;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyStock;

class Create extends Component
{
    public $livestockId;
    public $invoice_number;
    public $date;
    public $supplier_id;
    public $master_rekanan_id;
    public $expedition_id;
    public $expedition_fee = 0;
    public $items = [];
    public $livestock_id;
    public $pembelianId; // To hold the ID when editing
    public $showForm = false;
    public $edit_mode = false;
    public $farmId, $farm_id;


    protected $listeners = [
        'deleteSupplyPurchaseBatch' => 'deleteSupplyPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        
    ];

    public function mount()
    {
        // $this->date = now()->toDateString();
        $this->items = [
            ['item_id' => '', 'quantity' => '', 'price_per_unit' => '']
        ];
    }

    public function addItem()
    {
        $this->items[] = ['item_id' => '', 'quantity' => '', 'price_per_unit' => ''];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function render()
    {

        $farms = Farm::all();

        // dd($farms);

        return view('livewire.supply-purchases.create', [
            'farms' => $farms,
            'vendors' => Partner::where('type','Supplier')->get(),
            'expeditions' => Expedition::all(),
            'supplyItems' => Supply::all(),
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
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function resetForm()
    {
        $this->reset();
        // $this->items = [
        //     ['item_id' => '', 'quantity' => 1, 'price_per_kg' => 0],
        // ];
    }

    public function showEditForm($id)
    {   
        $this->pembelianId = $id;
        $pembelian = SupplyPurchaseBatch::with('supplyPurchases')->find($id);

        if ($pembelian && $pembelian->supplyPurchases->isNotEmpty()) {
            // dd($pembelian->supplyPurchases->first()->livestock_id);
            $this->date = $pembelian->date;
            $this->farmId = $pembelian->supplyPurchases->first()->farm_id;
            $this->farm_id = $pembelian->supplyPurchases->first()->farm_id;
            $this->invoice_number = $pembelian->invoice_number;
            $this->supplier_id = $pembelian->supplier_id;
            $this->expedition_id = $pembelian->expedition_id;
            $this->expedition_fee = $pembelian->expedition_fee;

            $this->items = [];
            foreach ($pembelian->supplyPurchases as $item) {
                $this->items[] = [
                    // 'farm_id' => $item->farm_id,
                    'item_id' => $item->supply_id,
                    'quantity' => $item->quantity,
                    'price_per_unit' => round($item->price_per_unit, 0),
                ];
            }
        }
        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function deleteSupplyPurchaseBatch($batchId)
    {
        try {
            DB::beginTransaction();

            $batch = SupplyPurchaseBatch::with('supplyPurchases')->findOrFail($batchId);

            // Loop semua FeedPurchase di dalam batch
            foreach ($batch->supplyPurchases as $purchase) {
                $supplyStock = SupplyStock::where('supply_purchase_id', $purchase->id)->first();

                // Validasi: tidak bisa hapus jika sudah dipakai atau dimutasi
                if (($supplyStock->quantity_used ?? 0) > 0 || ($supplyStock->quantity_mutated ?? 0) > 0) {
                    $this->dispatch('error', 'Tidak dapat menghapus batch. Beberapa stok sudah digunakan atau dimutasi.');
                    return;
                }

                // Hapus FeedStock & FeedPurchase
                $supplyStock?->delete();
                $purchase->delete();
            }

            // Hapus batch setelah semua anaknya aman
            $batch->delete();

            DB::commit();
            $this->dispatch('success', 'Data berhasil dihapus');
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
        } finally {
            // $this->reset();
        }
    }

    public function save()
    {
        $rules = [
            'invoice_number' => 'required|string',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:partners,id',
            'expedition_fee' => 'numeric|min:0',
            'farm_id' => 'required|exists:farms,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:supplies,id',
            'items.*.quantity' => 'required|numeric',
            'items.*.price_per_unit' => 'required|numeric|min:0',
        ];

        // if ($this->pembelianId) {
        //     $rules['invoice_number'] = 'sometimes|required|string|unique:supply_purchase_batches,invoice_number,' . $this->pembelianId;
        //     // dd('Updating with ID: ' . $this->pembelianId . ', Invoice Number: ' . $this->invoice_number); // Debug
        // } else {
        //     // dd('Creating with Invoice Number: ' . $this->invoice_number); // Debug
        //     $rules['invoice_number'] = 'required|string|unique:supply_purchase_batches,invoice_number';
        // }

        $this->validate($rules);

        // dd($this->all());

        DB::beginTransaction();

        try {
            if ($this->pembelianId) {
                // Update existing SupplyPurchaseBatch
                $batch = SupplyPurchaseBatch::findOrFail($this->pembelianId);
                $batch->update([
                    'invoice_number' => $this->invoice_number,
                    'date' => $this->date,
                    'supplier_id' => $this->supplier_id,
                    'expedition_id' => $this->expedition_id ?? null,
                    'expedition_fee' => $this->expedition_fee ?? 0,
                    'updated_by' => auth()->id(),
                ]);

                // Sync SupplyPurchase items (remove old, add new/updated)
                $existingItemIds = $batch->supplyPurchases ? $batch->supplyPurchases->pluck('supply_id')->toArray() : [];
                $newItemIds = collect($this->items)->pluck('item_id')->toArray();

                // Delete items that are no longer present
                $itemsToDelete = $batch->supplyPurchases instanceof Collection ? $batch->supplyPurchases->whereNotIn('supply_id', $newItemIds) : collect();
                foreach ($itemsToDelete as $purchase) {
                    // Optionally handle related SupplyStock entries (e.g., mark as inactive or adjust)
                    SupplyStock::where('supply_purchase_id', $purchase->id)->delete();
                    $purchase->delete();
                }

                foreach ($this->items as $item) {
                    $existingPurchase = $batch->supplyPurchases()->where('supply_id', $item['item_id'])->first();

                    if ($existingPurchase) {
                        // Update existing SupplyPurchase item
                        $existingPurchase->update([
                            'farm_id' => $this->farm_id,
                            'quantity' => $item['quantity'],
                            'price_per_unit' => $item['price_per_unit'],
                            'total' => $item['price_per_unit'] * $item['quantity'],
                            'updated_by' => auth()->id(),
                        ]);

                        // Update related SupplyStock entry
                        SupplyStock::where('supply_purchase_id', $existingPurchase->id)->update([
                            // 'date' => $this->date,
                            // 'quantity_in' => $item['quantity'],
                            // 'available' => DB::raw('available + (' . $item['quantity'] . ' - ' . $existingPurchase->quantity . ')'),
                            'quantity_in' => DB::raw('quantity_in + (' . $item['quantity'] . ' - ' . $existingPurchase->quantity . ')'),
                            'updated_by' => auth()->id(),
                        ]);
                    } else {
                        // Create new SupplyPurchase item
                        $purchase = SupplyPurchase::create([
                            'id' => Str::uuid(),
                            'supply_purchase_batch_id' => $batch->id,
                            'farm_id' => $this->farm_id,
                            'supply_id' => $item['item_id'],
                            'quantity' => $item['quantity'],
                            'price_per_unit' => $item['price_per_unit'],
                            'total' => $item['price_per_unit'] * $item['quantity'],
                            'created_by' => auth()->id(),
                        ]);

                        // Create new SupplyStock entry
                        SupplyStock::create([
                            'farm_id' => $this->farm_id,
                            'supply_id' => $item['item_id'],
                            'supply_purchase_id' => $purchase->id,
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
                }

                $this->dispatch('success', 'Pembelian pakan berhasil diperbarui');
            } else {
                // Create new SupplyPurchaseBatch
                $batch = SupplyPurchaseBatch::create([
                    'id' => Str::uuid(),
                    'invoice_number' => $this->invoice_number,
                    'date' => $this->date,
                    'supplier_id' => $this->supplier_id,
                    'expedition_id' => $this->expedition_id ?? null,
                    'expedition_fee' => $this->expedition_fee ?? 0,
                    'created_by' => auth()->id(),
                ]);

                foreach ($this->items as $item) {
                    $purchase = SupplyPurchase::create([
                        'id' => Str::uuid(),
                        'supply_purchase_batch_id' => $batch->id,
                        'farm_id' => $this->farm_id,
                        'supply_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'price_per_unit' => $item['price_per_unit'],
                        'total' => $item['price_per_unit'] * $item['quantity'],
                        'created_by' => auth()->id(),
                    ]);

                    // Simpan ke feed_stocks
                    SupplyStock::create([
                        'farm_id' => $this->farm_id,
                        'supply_id' => $item['item_id'],
                        'supply_purchase_id' => $purchase->id,
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

                $this->dispatch('success', 'Pembelian pakan berhasil disimpan');
            }

            DB::commit();

            $this->resetForm();
            $this->close();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat ' . ($this->pembelianId ? 'memperbarui' : 'menyimpan') . ' data. ' . $e->getMessage());
        } finally {
            // $this->reset();
        }
    }
}
