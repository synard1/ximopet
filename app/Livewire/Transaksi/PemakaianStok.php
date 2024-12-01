<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Rekanan;
use App\Models\Item;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\Kandang;
use App\Models\StokHistory;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;

use App\Models\KematianTernak;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
class PemakaianStok extends Component
{
    public $isOpenPemakaian = 0;
    public $faktur, $tanggal, $suppliers, $supplier, $name =[], $quantity=[], $harga =[], $allItems, $farms, $kandangs, $selectedFarm, $selectedSupplier, $selectedKandang;
    public $items = [['name' => '', 'qty' => 1]]; // Initial empty item


    protected $listeners = [
        'deletePemakaianStok' => 'deletePemakaianStok',
        // 'createPemakaianStok' => 'createPemakaianStok',
    ];

    public function render()
    {
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        $this->allItems = Item::whereHas('itemCategory', function ($query) {
            $query->where('name', '!=', 'DOC');
        })->where('status', 'Aktif')->get();
        // $this->farms = FarmOperator::where('user_id', auth()->user()->id)->where('status','Aktif')->get();
        $this->farms = Farm::whereHas('farmOperators', function ($query) {
            $query->where('user_id', auth()->user()->id);
        })->get();
        // $this->kandangs = Kandang::where('deleted_at',null)->get();
        $this->kandangs = [];


        return view('livewire.transaksi.pemakaian-stok', [
            'suppliers'=> $this->suppliers,
            'allItems' => $this->allItems,
            'farms' => $this->farms,
            'kandangs' => $this->kandangs,
        ]);
    }

    public function createPemakaianStok()
    {
        $this->isOpenPemakaian = true;
    }

    public function close()
    {
        $this->isOpenPemakaian = false;
        $this->dispatch('closeFormPemakaian');
    }

    public function store()
    {
        $this->dispatch('success', 'Data Pemakaian Stok berhasil ditambahkan');
        $this->dispatch('closeFormPemakaian');

    }

    public function addItem()
    {
        $this->dispatch('reinitialize-select2-pemakaianStok'); // Trigger Select2 initialization

        $this->items[] = ['name' => '', 'qty' => 1, 'harga' => 0];
    }

    public function deletePemakaianStok($id)
    {

        dd($id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . auth()->user()->api_token,
            'X-CSRF-TOKEN' => csrf_token(),
        ])->post(route('api.v1.stoks'), [
            'type' => 'reverse',
            'id' => $id
        ]);

        return $response;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . auth()->user()->api_token,
                'X-CSRF-TOKEN' => csrf_token(),
            ])->post(route('api.v1.stoks'), [
                'type' => 'reverse',
                'id' => $id
            ]);

            return $response;

            if ($response->successful()) {
                $this->dispatch('success', 'Transaksi pembelian berhasil dihapus');
            } else {
                $this->dispatch('error', 'Gagal menghapus transaksi: ' . $response->json('message'));
            }
        } catch (\Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        // dd($id);
        // DB::beginTransaction();
        // try {
        //     $transaksi = Transaksi::findOrFail($id);
        //     $stokHistories = StokHistory::where('transaksi_id', $id)->get();
        //     $transaksiDetails = TransaksiDetail::where('transaksi_id', $id)->get();

        //     foreach ($stokHistories as $stokHistory) {
        //         $transaksiDetail = TransaksiDetail::where('jenis','Pemakaian')
        //                         ->where('transaksi_id', $stokHistory->transaksi_id)
        //                         ->first();

        //         $detail = TransaksiDetail::where('id', $transaksiDetail->parent_id)->first();

        //         // dd($transaksiDetail, $detail, $stokHistory->id);
                
        //         if ($detail) {
        //             // Update TransaksiDetail
        //             $detail->terpakai -= $stokHistory->qty;
        //             $detail->sisa += $stokHistory->qty;
        //             $detail->save();

        //             // Update Transaksi
        //             $transaksi->terpakai -= $stokHistory->qty;
        //             $transaksi->sisa += $stokHistory->qty;
        //         }

        //         // Delete StokHistory
        //         $stokHistory->delete();
        //     }

        //     // Save Transaksi after all updates
        //     $transaksi->save();

        //     // Delete TransaksiDetails
        //     foreach ($transaksiDetails as $detail) {
        //         $detail->delete();
        //     }

        //     // Finally, delete the Transaksi
        //     $transaksi->delete();

        //     DB::commit();
        //     $this->dispatch('success', 'Data Pemakaian Stok berhasil dihapus');
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     $this->dispatch('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        // }
    }

    public function storeTernakMati()
    {
        $this->validate([
            'tanggal' => 'required|date',
            'selectedFarm' => 'required',
            'selectedKandang' => 'required',
            'quantity' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $ternakMati = new KematianTernak();
            $ternakMati->tanggal = $this->tanggal;
            $ternakMati->farm_id = $this->selectedFarm;
            $ternakMati->kandang_id = $this->selectedKandang;
            $ternakMati->jumlah = $this->quantity;
            $ternakMati->keterangan = $this->keterangan;
            $ternakMati->save();

            // Update stok
            $this->updateStok($this->selectedFarm, $this->selectedKandang, $this->quantity, 'kurang');

            DB::commit();
            $this->dispatch('success', 'Data Ternak Mati berhasil ditambahkan');
            $this->resetForm();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
