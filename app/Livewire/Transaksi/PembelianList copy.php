<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Item;
use App\Models\StokMutasi;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\FarmOperator;
use App\Models\Farm;

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
    public $editMode = false;
    public $fakturPlaceholder = '000000'; 



    protected $rules = [
        'faktur' => 'required',
        'tanggal' => 'required',
        'selectedSupplier' => 'required',
        'selectedFarm' => 'required',
    ];

    protected $listeners = [
        'delete_transaksi_pembelian' => 'deleteTransaksiPembelian',
        'editPembelian' => 'editPembelian',
    ];

    public function mount()
    {
        // Fetch items from the database and add the initial empty item
        // $this->items = Item::all()->toArray();
        // $this->addItem();
        // $this->faktur = "000000";
        $this->fakturPlaceholder = $this->fakturPlaceholder;

    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reindex the array
    }

    public function render()
    {

        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        // $this->farms = FarmOperator::where('user_id', auth()->user()->id)->get();
        // $this->farms =[];
        $this->farms = Farm::whereHas('farmOperators', function ($query) {
            $query->where('user_id', auth()->user()->id);
        })->get();
        $this->allItems = Item::where('status', 'Aktif')->where('jenis','!=','DOC')->get();
        // $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        return view('livewire.transaksi.pembelian-list', [
            'suppliers' => $this->suppliers,
            'allItems' => $this->allItems,
        ]);
    }

    public function addItem()
    {
        $this->items[] = ['name' => '', 'qty' => 1, 'harga' => 0];
        // $this->dispatch('reinitialize-select2'); // Trigger Select2 initialization
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

            // foreach ($this->items as $itemData) {
            //     $items = Item::where('id', $itemData['name'])->first();
            //     $itemsToStore[] = [
            //         'qty' => $itemData['qty'],
            //         'terpakai' => 0,
            //         'harga' => $itemData['harga'],
            //         'total' => $itemData['qty'] * $itemData['harga'],
            //         'nama' => $items->name,
            //         'jenis' => $items->jenis,
            //         'item_id' => $items->id,
            //         'item_nama' => $items->nama,
            //         'konversi' => $items->konversi,
            //     ];
            // }

            // $sumQty = array_sum(array_column($itemsToStore, 'qty'));
            // $sumPrice = array_sum(array_column($itemsToStore, 'harga'));
            // $sumTotal = array_sum(array_column($itemsToStore, 'total'));

            $itemsToStore = [];
            $sumQty = 0;
            $sumPrice = 0;
            $sumTotal = 0;

            foreach ($this->items as $itemData) {
                $item = Item::find($itemData['name']); // Menggunakan find untuk efisiensi

                if ($item) { // Memastikan item ditemukan
                    $qty = $itemData['qty'];
                    $harga = $itemData['harga'];
                    $total = $qty * $harga;
                    $konversi = $item->konversi; // Mengambil konversi dari item

                    $itemsToStore[] = [
                        'qty' => $qty,
                        'terpakai' => 0,
                        'harga' => $harga,
                        'total' => $total,
                        'name' => $item->name,
                        'jenis' => $item->jenis,
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'konversi' => $konversi,
                    ];

                    // Menghitung total qty dengan mempertimbangkan konversi
                    $sumQty += $qty * $konversi;
                    $sumPrice += $harga;
                    $sumTotal += $total;
                } 
            }
        
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
                'total_qty' => $sumQty,
                'terpakai' => 0,
                'sisa' => $sumQty,
                'sub_total' => $sumTotal,
                'periode' => null,
                'user_id' => auth()->user()->id,
                // 'payload' => ['items' => $itemsToStore],
                'status' => 'Aktif',
            ];

            // $transaksi = Transaksi::create($data);

            foreach ($this->items as $itemData) {
                $item = Item::where('id', $itemData['name'])->first();

                $stokMutasi = StokMutasi::create([
                    'farm_id' => $this->selectedFarm,
                    'kandang_id' => null,
                    // 'rekanan_id' => $suppliers->id,
                    'item_id' => $item->id,
                    // 'item_name' => $item->name,
                    // 'harga' => $sumPrice,
                    'qty' => $itemData['qty'],
                    'stok_awal' => 0,
                    'stok_akhir' => $itemData['qty'],
                    'status' => 'Masuk',
                ]);

                $transaksi= $stokMutasi->stokTransaksi()->create([
                    'faktur' => $this->faktur,
                    'tanggal' => $this->tanggal,
                    'jenis' => 'Pembelian',
                    'farm_id' => $this->selectedFarm,
                    'kandang_id' => null,
                    'rekanan_id' => $suppliers->id,
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'harga' => $sumPrice,
                    'total_qty' => $sumQty,
                    'terpakai' => 0,
                    'sisa' => $itemData['qty'],
                    'sub_total' => $itemData['qty'] * $itemData['harga'],
                    'periode' => null,
                    'status' => 'Masuk',
                    'user_id' => auth()->user()->id,
                ]);

                // Prepare the data for creating/updating
                $data_details = [
                    'transaksi_id' => $transaksi->id,
                    // 'parent_id' => null,
                    'jenis' => 'Pembelian',
                    'jenis_barang' => $item->jenis,
                    'tanggal' => $this->tanggal,
                    'rekanan_id' => $transaksi->rekanan_id,
                    'farm_id' => $this->selectedFarm,
                    'kandang_id' => null,
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'name' => $item->name,
                    'harga' => $itemData['harga'],
                    'qty' => $itemData['qty'] * $item->konversi,
                    'terpakai' => 0,
                    'sisa' => $itemData['qty'] * $item->konversi,
                    'sub_total' => $itemData['qty'] * $itemData['harga'],
                    'konversi' => $item->konversi,
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
            $this->reset();
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
        $this->selectedFarm = $pembelian->farm_id;
        $this->items = $items;

        $this->editMode = true;
        $this->openModal();
    }

    public function deleteTransaksiPembelian($id)
    {
        try {
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            $detail = TransaksiDetail::where('transaksi_id', $id)->first();

            if($detail->terpakai > 0){
                $this->dispatch('error', 'Sudah ada data transaksi yang terpakai.');
            }else{
                // Delete the user record with the specified ID
                Transaksi::destroy($id);
                $deleted = TransaksiDetail::where('transaksi_id', $id)->delete();

                DB::commit();
                // Emit a success event with a message
                $this->dispatch('success', 'Data berhasil dihapus');
            }

            

        } catch (\Throwable $th) {
            DB::rollBack();
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    

}
