<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembelianList extends Component
{
    public $faktur, $tanggal, $suppliers, $supplier, $name =[], $quantity=[], $allItems;
    public $selectedSupplier = null;
    public $items = [['name' => '', 'qty' => 1, 'price' => 0]]; // Initial empty item

    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
    ];

    public function mount()
    {
        // Fetch items from the database and add the initial empty item
        // $this->items = Item::all()->toArray();
        // $this->addItem();
        $this->faktur = "000000";
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reindex the array
    }

    public function render()
    {

        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        $this->allItems = Stok::where('status', 'Aktif')->get();
        // $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        return view('livewire.transaksi.pembelian-list', [
            'suppliers' => $this->suppliers,
            'allItems' => $this->allItems,
        ]);
    }

    public function addItem()
    {
        $this->items[] = ['name' => '', 'qty' => 1, 'price' => 0];
        $this->dispatch('reinitialize-select2'); // Trigger Select2 initialization
    }

    public function createPembelian()
    {
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->dispatch('closeForm');

    }

    public function store()
    {
        try {
            $this->validate(); 

            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            foreach ($this->items as $itemData) {
                $items = Stok::where('id', $itemData['name'])->first();
                $itemsToStore[] = [
                    'qty' => $itemData['qty'],
                    'terpakai' => 0,
                    'harga' => $itemData['price'],
                    'total' => $itemData['qty'] * $itemData['price'],
                    'nama' => $items->nama,
                    'jenis' => $items->jenis,
                    'item_id' => $items->id,
                ];
            }

            $sumQty = array_sum(array_column($itemsToStore, 'qty'));
            $sumPrice = array_sum(array_column($itemsToStore, 'harga'));
            $sumTotal = array_sum(array_column($itemsToStore, 'total'));
        
            dd($itemsToStore); 

            $suppliers = Rekanan::where('id', $this->selectedSupplier)->first();

            // Prepare the data for creating/updating
            $data = [
                'jenis' => 'Pembelian',
                'jenis_barang' => 'Stok',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'rekanan_id' => $suppliers->id,
                'farm_id' => null,
                'kandang_id' => null,
                'rekanan_nama' => $suppliers->nama,
                'harga' => $sumPrice,
                'jumlah' => $sumQty,
                'sub_total' => $sumTotal,
                'periode' => null,
                'user_id' => auth()->user()->id,
                'payload' => ['items' => $itemsToStore],
                'status' => 'Aktif',
            ];

            $transaksi = Transaksi::create($data);

            foreach ($this->items as $itemData) {
                $item = Stok::where('id', $itemData['name'])->first();
                // Prepare the data for creating/updating
                $data_details = [
                    'transaksi_id' => $transaksi->id,
                    'parent_id' => null,
                    'jenis' => 'Pembelian',
                    'jenis_barang' => $item->jenis,
                    'tanggal' => $this->tanggal,
                    'rekanan_id' => $transaksi->rekanan_id,
                    'farm_id' => null,
                    'kandang_id' => null,
                    'item_id' => $item->id,
                    'nama' => $item->nama,
                    'harga' => $itemData['price'],
                    'jumlah' => $itemData['qty'],
                    'terpakai' => 0,
                    'sisa' => 0,
                    'sub_total' => $itemData['qty'] * $itemData['price'],
                    'periode' => null,
                    'status' => 'Aktif',
                    'user_id' => auth()->user()->id,
                ];

                $transaksiDetail = TransaksiDetail::create($data_details);

            }

            DB::commit();

            // Emit success event if no errors occurred
            $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            $this->reset();
        }
        
    }
}
