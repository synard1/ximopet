<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\CurrentStock;

use App\Models\Rekanan;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemLocation;
use App\Models\StockHistory;
use App\Models\StockMovement;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;
use App\Models\TransaksiHarianDetail;
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
        $this->allItems = Item::whereHas('itemCategory', function ($query) {
            $query->where('name', '!=', 'DOC');
        })->where('status', 'Aktif')->get();
        // $this->allItems = Item::where('status', 'Aktif')->where('jenis','!=','DOC')->get();
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

//TODO Refractor fitur pembelian stok, dengan relasi ke data stok dan transaksi_beli_detail
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

            // Initial Data
            $suppliers = Rekanan::where('id', $this->selectedSupplier)->first();
            $batchNumber = $this->generateUniqueBatchNumber($this->tanggal);

            // dd($count .' - '. $batchNumber);

            // Create the TransaksiBeli
            $transaksiBeli = TransaksiBeli::create([
                'jenis' => 'Stock',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'rekanan_id' => $suppliers->id,
                'batch_number' => $batchNumber,
                'farm_id' => $this->selectedFarm,
                'kandang_id' => null,
                'rekanan_nama' => $suppliers->nama,
                'harga' => null,
                'total_qty' => null,
                'terpakai' => 0,
                'sisa' => null,
                'sub_total' => null,
                'kelompok_ternak_id' => null,
                'user_id' => auth()->user()->id,
                'status' => 'Aktif',
            ]);

            // Loop through each item
            foreach ($this->items as $itemData) {
                $item = Item::findOrFail($itemData['name']);
                // Get random item category (excluding DOC)
                // $category = ItemCategory::where('name', '!=', 'DOC')->inRandomOrder()->first();
                // Check if the item has a LocationMapping for the selected farm
                $locationMapping = ItemLocation::where('item_id', $item->id)->where('farm_id', $this->selectedFarm)->first();

                if (!$locationMapping) {
                // throw new \Exception("Item '{$item->name}' does not have a location mapping for the selected farm.");
                return $this->dispatch('error', "Item '{$item->name}' does not have a location mapping for the selected farm.");
                }

                // Create TransaksiBeliDetail
                $transaksiBeliDetail = TransaksiBeliDetail::create([
                    'transaksi_id' => $transaksiBeli->id,
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'quantity' => $itemData['qty'],
                    'jenis' => 'Pembelian',
                    'jenis_barang' => $item->category->name,
                    'tanggal' => $this->tanggal,
                    'rekanan_id' => $suppliers->id,
                    'farm_id' => $this->selectedFarm,
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
                    // Other fields for TransaksiBeliDetail
                ]);

                // Update CurrentStock
                $currentStock = CurrentStock::where('item_id', $item->id)
                                            ->where('location_id',$locationMapping->location_id)
                                            ->first();

                if ($currentStock) {
                    // Update existing stock
                    $currentStock->quantity += $itemData['qty'];
                    $currentStock->available_quantity += $itemData['qty'];
                    $currentStock->save();
                } else {
                    // Create new stock entry
                    $currentStock = CurrentStock::create([
                        'item_id' => $item->id,
                        'quantity' => $itemData['qty'],
                        'location_id' => $locationMapping->location_id,
                        'silo_id' => null,
                        'quantity' => $itemData['qty'],
                        'reserved_quantity' => 0,
                        'available_quantity' => $itemData['qty'],
                        'hpp' => $itemData['harga'],
                        'status' => 'Aktif',
                        'created_by' => auth()->user()->id,
                        // Other fields for CurrentStock
                    ]);
                }

                // Log the StockMovement
                StockMovement::create([
                    'transaksi_id' => $transaksiBeli->id,
                    'item_id' => $item->id,
                    'movement_type' => 'Purchase',
                    'quantity' => $itemData['qty'],
                    'tanggal' => $this->tanggal,
                    'destination_location_id' => $currentStock->location_id,
                    'destination_silo_id' => null,
                    'batch_number' => $batchNumber,
                    'satuan' => $item->satuan_besar,
                    'hpp' => $itemData['harga'],
                    'status' => 'In',
                    'keterangan' => 'Initial purchase of ' . $item->name,
                    'created_by' => auth()->user()->id,
                    // Other fields for StockMovement
                ]);

                // Log the StockHistory
                StockHistory::create([
                    'transaksi_id' => $transaksiBeli->id,
                    'jenis' => 'Pembelian',
                    'parent_id' => null,
                    'stock_id' => $currentStock ? $currentStock->id : null,
                    'item_id' => $item->id,
                    'location_id' => $currentStock->location_id,
                    'batch_number' => $batchNumber,
                    'expiry_date' => null,
                    'quantity' => $itemData['qty'],
                    'reserved_quantity' => 0,
                    'available_quantity' => $itemData['qty'],
                    'hpp' => $itemData['harga'],
                    'status' => 'In',
                    'created_by' => auth()->user()->id,
                    // Other fields for StockHistory
                ]);
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

    public function getTransaksiBeliCountByDate($date)
    {
        // Validate the date format (optional)
        $validatedDate = Carbon::parse($date)->format('Y-m-d');

        // Count the number of TransaksiBeli for the specified date, including soft-deleted records
        $count = TransaksiBeli::withTrashed()
            ->whereDate('tanggal', $validatedDate)
            ->count();

        return $count;
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

            $detail = TransaksiBeliDetail::where('transaksi_id', $id)->first();
            $stockHistory = StockHistory::where('transaksi_id', $id)->first();
            $stockMovement = StockMovement::where('transaksi_id', $id)->first();

            // Find related StokHistory records
            $relatedStokHistories = StockHistory::where('parent_id', $detail->id)->get();


            // Check if there are any related StokHistory records
            if ($relatedStokHistories->isNotEmpty() && $detail->terpakai > 0) {
                $this->dispatch('error', 'Tidak dapat menghapus transaksi. Terdapat riwayat stok terkait.');
                return;
            }else{
                // Delete the user record with the specified ID
                TransaksiBeli::destroy($id);
                $transaksiDetail = TransaksiBeliDetail::where('transaksi_id', $id)->first();
                StockHistory::where('transaksi_id', $id)->delete();
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

    private function generateUniqueBatchNumber($date)
    {
        $batchDate = Carbon::parse($date)->format('Ymd');
        $baseNumber = 'Pembelian-' . $batchDate . '-';

        $latestBatch = TransaksiBeli::withTrashed()
            ->whereDate('tanggal', $date)
            ->where('batch_number', 'like', $baseNumber . '%')
            ->orderByRaw('CAST(SUBSTRING(batch_number, -2) AS UNSIGNED) DESC')
            ->value('batch_number');

        if ($latestBatch) {
            $latestNumber = intval(substr($latestBatch, -2));
            $newNumber = $latestNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $baseNumber . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }

    public function calculateItemTotal($item)
    {
        $qty = is_numeric($item['qty']) ? $item['qty'] : 0;
        $harga = is_numeric($item['harga']) ? $item['harga'] : 0;
        return $qty * $harga;
    }
    

}
