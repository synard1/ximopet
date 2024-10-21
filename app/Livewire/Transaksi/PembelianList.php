<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Item;
use App\Models\StokHistory;
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

            $itemsToStore = [];
            $sumQty = 0;
            $sumPrice = 0;
            $sumTotal = 0;

            foreach ($this->items as $itemData) {
                $item = Item::findOrFail($itemData['name']); // Menggunakan find untuk efisiensi

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
                'kelompok_ternak_id' => null,
                'user_id' => auth()->user()->id,
                // 'payload' => ['items' => $itemsToStore],
                'status' => 'Aktif',
            ];

            $transaksi = Transaksi::create($data);

            foreach ($this->items as $itemData) {
                $item = Item::findOrFail($itemData['name']);

                $transaksiDetail = $this->createStokTransaksiAndDetail($transaksi, $item, $itemData, $suppliers);
                // $stokMutasi = $this->createStokMutasi($item, $itemData);

                // $this->createTransaksiDetail($transaksi, $item, $itemData);
            }

            // Add these methods to the class:
            
            

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

    private function createStokTransaksiAndDetail($transaksi, $item, $itemData, $supplier)
    {
        // $transaksiData = [
        //     'jenis' => 'Pembelian',
        //     'jenis_barang' => 'Stok',
        //     'faktur' => $this->faktur,
        //     'tanggal' => $this->tanggal,
        //     'rekanan_id' => $supplier->id,
        //     'farm_id' => $this->selectedFarm,
        //     'rekanan_nama' => $supplier->nama,
        //     'harga' => $itemData['harga'],
        //     'total_qty' => $itemData['qty'],
        //     'sub_total' => $itemData['qty'] * $itemData['harga'],
        //     'terpakai' => 0,
        //     'sisa' => $itemData['qty'],
        //     'kelompok_ternak_id' => null,
        //     'user_id' => auth()->user()->id,
        //     'status' => 'Aktif',
        // ];

        // $transaksi = Transaksi::create($transaksiData);

        $transaksiDetailData = [
            'jenis' => 'Pembelian',
            'jenis_barang' => $item->jenis,
            'tanggal' => $this->tanggal,
            'rekanan_id' => $supplier->id,
            'farm_id' => $this->selectedFarm,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'qty' => $itemData['qty'],
            'harga' => $itemData['harga'],
            'sub_total' => $itemData['qty'] * $itemData['harga'],
            'terpakai' => 0,
            'sisa' => $itemData['qty'],
            'satuan_besar' => $item->satuan_besar,
            'satuan_kecil' => $item->satuan_kecil,
            'konversi' => $item->konversi,
            'kelompok_ternak_id' => null,
            'status' => 'Aktif',
            'user_id' => auth()->user()->id,
        ];

        $transaksiDetail = $transaksi->transaksiDetail()->create($transaksiDetailData);

        $stokMutasiData = [
            'transaksi_id' => $transaksi->id,
            'parent_id' => null,
            'farm_id' => $transaksi->farm_id,
            'kandang_id' => $transaksi->kandang_id,
            'tanggal' => $this->tanggal,
            'jenis' => 'Masuk',
            'item_id' => $transaksiDetail->item_id,
            'item_name' => $transaksiDetail->item_name,
            'satuan' => $transaksiDetail->satuan_besar,
            'jenis_barang' => $transaksiDetail->items->jenis,
            'kadaluarsa' => $transaksiDetail->kadaluarsa ?? $transaksiDetail->tanggal->addMonths(18),
            'perusahaan_nama' => $transaksi->rekanans->nama,
            'qty' => $transaksiDetail->qty,
            'hpp' => $transaksiDetail->harga,
            'stok_awal' => 0,
            'stok_masuk' => $transaksiDetail->qty,
            'stok_keluar' => 0,
            'stok_akhir' => $transaksiDetail->qty,
            'status' => 'Aktif',
            'user_id' => auth()->user()->id,
        ];

        $stokHistory = $transaksi->stokHistory()->create($stokMutasiData);


        // $transaksiDetail->stokMutasi()->create($stokMutasiData);

        return $transaksi;
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
            $stokHistory = StokHistory::where('transaksi_id', $id)->first();

            // Find related StokHistory records
            $relatedStokHistories = StokHistory::where('parent_id', $stokHistory->id)->get();

            // Check if there are any related StokHistory records
            if ($relatedStokHistories->isNotEmpty() && $detail->terpakai > 0) {
                $this->dispatch('error', 'Tidak dapat menghapus transaksi. Terdapat riwayat stok terkait.');
                return;
            }else{
                // Delete the user record with the specified ID
                Transaksi::destroy($id);
                $transaksiDetail = TransaksiDetail::where('transaksi_id', $id)->first();
                StokHistory::where('transaksi_id', $id)->delete();
                $transaksiDetail->delete();

                DB::commit();
                // Emit a success event with a message
                $this->dispatch('success', 'Data berhasil dihapus');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data.' . $e->getMessage());
        }
    }

    

}
