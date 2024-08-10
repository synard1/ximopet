<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\FarmOperator;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class PembelianList extends Component
{
    public $faktur, $tanggal, $suppliers, $supplier, $name =[], $quantity=[], $allItems, $farms, $selectedFarm;
    public $selectedSupplier = null;
    public $items = [['name' => '', 'qty' => 1, 'harga' => 0]]; // Initial empty item
    public $transaksi_id;

    public $isOpen = 0;

    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
        'selectedSupplier' => 'required',
        'selectedFarm' => 'required',
    ];

    protected $listeners = [
        'delete_transaksi_stok' => 'deleteTransaksi',
        'editPembelian' => 'editPembelian',
    ];

    public function mount()
    {
        // Fetch items from the database and add the initial empty item
        // $this->items = Item::all()->toArray();
        // $this->addItem();
        // $this->faktur = "000000";
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reindex the array
    }

    public function render()
    {

        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        $this->farms = FarmOperator::where('user_id', auth()->user()->id)->where('status','Aktif')->get();
        $this->allItems = Stok::where('status', 'Aktif')->where('jenis','!=','DOC')->get();
        // $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        return view('livewire.transaksi.pembelian-list', [
            'suppliers' => $this->suppliers,
            'allItems' => $this->allItems,
        ]);
    }

    public function addItem()
    {
        $this->items[] = ['name' => '', 'qty' => 1, 'harga' => 0];
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
                    'harga' => $itemData['harga'],
                    'total' => $itemData['qty'] * $itemData['harga'],
                    'nama' => $items->name,
                    'jenis' => $items->jenis,
                    'item_id' => $items->id,
                ];
            }

            $sumQty = array_sum(array_column($itemsToStore, 'qty'));
            $sumPrice = array_sum(array_column($itemsToStore, 'harga'));
            $sumTotal = array_sum(array_column($itemsToStore, 'total'));
        
            // dd($itemsToStore); 

            $suppliers = Rekanan::where('id', $this->selectedSupplier)->first();

            // Prepare the data for creating/updating
            $data = [
                'jenis' => 'Pembelian',
                'jenis_barang' => 'Stok',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'rekanan_id' => $suppliers->id,
                'farm_id' => $this->selectedFarm,
                'kandang_id' => null,
                'rekanan_nama' => $suppliers->nama,
                'harga' => $sumPrice,
                'qty' => $sumQty,
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
                    'farm_id' => $this,
                    'kandang_id' => null,
                    'item_id' => $item->id,
                    'nama' => $item->nama,
                    'harga' => $itemData['harga'],
                    'qty' => $itemData['qty'],
                    'terpakai' => 0,
                    'sisa' => 0,
                    'sub_total' => $itemData['qty'] * $itemData['harga'],
                    'periode' => null,
                    'status' => 'Aktif',
                    'user_id' => auth()->user()->id,
                ];

                $transaksiDetail = TransaksiDetail::create($data_details);

            }

            DB::commit();

            // Emit success event if no errors occurred
            $this->dispatch('success', 'Data Pembelian Stok berhasil ditambahkan');
            $this->dispatch('closeForm');
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data.'. $e);
            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            // $this->reset();
        }
        
    }

    public function formatDateTime($dateTimeString)
    {
        return Carbon::parse($dateTimeString)->format('Y-m-d H:i'); // Or your desired format
    }


    public function editPembelian($id)
    {
        $pembelian = Transaksi::where('id',$id)->first();
        $items = TransaksiDetail::where('transaksi_id',$id)->get();

        // Format the date using Carbon
        $formattedTanggal = $this->formatDateTime($pembelian->tanggal);

        $this->transaksi_id = $id;
        $this->tanggal = $formattedTanggal;
        $this->faktur = $pembelian->faktur;
        $this->selectedSupplier = $pembelian->rekanan_id;
        $this->items = $items;

        $this->openModal();
    }

    public function deleteTransaksi($id)
    {
        try {
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            // Delete the user record with the specified ID
            Transaksi::destroy($id);
            $deleted = TransaksiDetail::where('transaksi_id', $id)->delete();

            DB::commit();
            // Emit a success event with a message
            $this->dispatch('success', 'Data berhasil dihapus');

        } catch (\Throwable $th) {
            DB::rollBack();
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    

}
