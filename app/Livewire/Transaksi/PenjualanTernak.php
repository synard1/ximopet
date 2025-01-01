<?php

namespace App\Livewire\Transaksi;

use App\Models\TransaksiBeli;
use Livewire\Component;
use App\Models\TransaksiJual;
use App\Models\TransaksiJualDetail;
use Illuminate\Support\Facades\DB;

use App\Models\CurrentTernak;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use App\Models\Rekanan;
use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;
use App\Models\TernakJual;

use App\Services\TernakService;

use Carbon\Carbon;

class PenjualanTernak extends Component
{

    protected $ternakService;

    public function boot()
    {
        $this->ternakService = app(TernakService::class);
    }

    public $dataTernak;
    public $transaksiHarian;
    public $transaksiJual;
    public $transaksiBeli;
    public $transaksiJualDetail;
    public $isEdit = false;
    public $modalTitle = 'Tambah Penjualan';
    public $isOpen = false;
    public $mode = 'create';
    public $penjualanId;
    public $transaksiId;
    public $data;
    public $kt_daterangepicker_1;

    
    // Form fields
    public $tanggal_beli, $faktur, $tanggal, $tanggal_harian, $tipe_transaksi, $ternak_jual, $harga, $status;
    public $rekanan_id, $farm_id, $kandang_id, $harga_beli, $harga_jual, $qty, $total_berat, $umur, $hpp;

    // Dynamic data
    public $rekanans = [];
    public $farms = [];
    public $kandangs = [];
    public $ternaks = [];
    public $selectedFarm = null;
    public $selectedKandang = null;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'openPenjualanModal' => 'openPenjualanModal',
        'editPenjualan' => 'editPenjualan',
        'resetForm' => 'resetForm',
    ];

    protected $rules = [
        'tanggal' => 'required|date',
        'tanggal_harian' => 'required|date',
        'faktur' => 'required',
        // 'tipe_transaksi' => 'required|string',
        'ternak_jual' => 'required|numeric',
        // 'harga' => 'required|numeric',
        // 'status' => 'required|string',
        'rekanan_id' => 'required|exists:master_rekanan,id',
        'farm_id' => 'required|exists:master_farms,id',
        'kandang_id' => 'required|exists:master_kandangs,id',
        // 'harga_beli' => 'required|numeric',
        'harga_jual' => 'required|numeric',
        // 'qty' => 'required|numeric',
        'total_berat' => 'required|numeric|between:0,9999999.99',
        // 'umur' => 'required|numeric',
        // 'hpp' => 'required|numeric',
    ];

    public function mount($data = null)
    {
        $this->data = $data;
        $this->loadRekanans();
        $this->loadFarms();
        $this->loadKandangs();

        if ($data) {
            $this->initializeData($data);
        }
    }

    private function initializeData($data)
    {
        // Initialize component properties based on $data
        $this->isEdit = $data['mode'] === 'edit';
        $this->mode = $data['mode'];
        $this->penjualanId = $data['penjualanId'] ?? null;
        $this->transaksiId = $data['transaksiId'] ?? null;

        if ($this->isEdit && $this->transaksiId) {
            $this->loadPenjualanData();
        }
    }

    public function render()
    {

        return view('livewire.transaksi.penjualan-ternak');
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->modalTitle = 'Tambah Penjualan';
        $this->emit('showModal');
    }

    public function store()
    {
        $validatedData = $this->validate();

        // $this->validate();

        DB::beginTransaction();
        try {
            // $kelompokTernak = KelompokTernak::where('kandang_id',$this->kandang_id)->where('status','Digunakan')->first();
            // $transaksiBeli = TransaksiBeli::where('kelompok_ternak_id',$kelompokTernak->id)->first();
            // $transaksiHarian = TransaksiHarian::where('kelompok_ternak_id',$kelompokTernak->id)->where('tanggal',$this->tanggal_harian)->first();
            
            // $transaksiJual = TransaksiJual::create([
            //     'transaksi_id' => $transaksiHarian->id,
            //     'tipe_transaksi' => 'Harian',
            //     'faktur' => $this->faktur,
            //     'tanggal' => $this->tanggal,
            //     'transaksi_beli_id' => $transaksiBeli->id,
            //     'kelompok_ternak_id' => $kelompokTernak->id,
            //     'jumlah' => $this->ternak_jual,
            //     'harga' => $this->harga,
            //     'status' => $this->status,
            // ]);

            // TransaksiJualDetail::create([
            //     'transaksi_jual_id' => $transaksiJual->id,
            //     'rekanan_id' => $this->rekanan_id,
            //     'farm_id' => $this->farm_id,
            //     'kandang_id' => $this->kandang_id,
            //     'harga_beli' => $this->harga_beli,
            //     'harga_jual' => $this->harga_jual,
            //     'qty' => $this->ternak_jual,
            //     'berat' => $this->total_berat,
            //     'umur' => $this->umur,
            //     'hpp' => $this->hpp,
            // ]);

            $ternak = CurrentTernak::where('farm_id',$this->farm_id)->where('kandang_id',$this->kandang_id)->where('status','Aktif')->first();
            $transaksiHarian = TransaksiHarian::where('kelompok_ternak_id',$ternak->kelompok_ternak_id)->where('tanggal',$this->tanggal_harian)->first();

            // Check If Ternak quantity is sufficient
            if($ternak->quantity - $validatedData['ternak_jual'] < 0){
                DB::rollback();
                $this->dispatch('error', 'Stok ternak terbatas. Stok Saat ini : '. $ternak->quantity);
                $this->dispatch('hideModal');
                return;
            }

            // $dataTernakJual = $this->ternakService->ternakJual($validatedData, $transaksiHarian);
            // DB::commit();
            // $this->dispatch('hideModal');
            // $this->dispatch('refreshDatatable');
            // $this->dispatch('success', 'Penjualan berhasil ditambahkan');

            if($transaksiHarian){
                $dataTernakJual = $this->ternakService->ternakJual($validatedData, $transaksiHarian);
                DB::commit();
                $this->dispatch('hideModal');
                $this->dispatch('refreshDatatable');
                $this->dispatch('success', 'Penjualan berhasil ditambahkan');
             }else{
                DB::rollback();
                $this->dispatch('error', 'Belum ada data transaksi harian pada tanggal tersebut');
                $this->dispatch('hideModal');
             }
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Gagal mengubah transaksi: ' . $e->getMessage());

        }
    }

    public function editPenjualan($id)
    {
        $this->isEdit = true;
        $this->modalTitle = 'Edit Penjualan';
        $this->transaksiJual = TransaksiJual::findOrFail($id);
        $this->transaksiBeli = TransaksiBeli::findOrFail($this->transaksiJual->transaksi_beli_id);
        $this->transaksiHarian = TransaksiHarian::findOrFail($this->transaksiJual->transaksi_id);
        $this->transaksiJualDetail = $this->transaksiJual->detail; // Assuming there's a 'detail' relationship
        $this->dataTernak = KelompokTernak::findOrFail($this->transaksiJual->kelompok_ternak_id);

        // dd($this->transaksiHarian);

        if ($this->transaksiJualDetail) {
            $this->umur = $this->transaksiJualDetail->umur;
            $this->harga_beli = $this->transaksiJualDetail->harga_beli;
            $this->rekanan_id = $this->transaksiJualDetail->rekanan_id;
            $this->total_berat = $this->transaksiJualDetail->berat;
        } else {
            $this->dispatchBrowserEvent('alert', ['type' => 'warning', 'message' => 'No detail found for this transaction']);
            return;
        }

        $this->tanggal = $this->transaksiJual->tanggal->format('Y-m-d');
        $this->faktur = $this->transaksiJual->faktur;
        $this->ternak_jual = $this->transaksiJual->jumlah;
        $this->farm_id = $this->transaksiBeli->farm_id;
        $this->kandang_id = $this->transaksiBeli->kandang_id;
        $this->harga_jual = $this->transaksiJual->harga;
        $this->tanggal_beli = $this->dataTernak->start_date->format('d-m-Y');
        $this->tanggal_harian = $this->transaksiHarian->tanggal->format('Y-m-d');

        // dd($this->tanggal_harian);

        $this->dispatch('open-modal-penjualan');
    }

    public function update()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $this->transaksiJual->update([
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'jumlah' => $this->ternak_jual,
                'harga' => $this->harga_jual,
                'status' => 'OK',
            ]);

            // Update each TransaksiJualDetail
            $this->transaksiJualDetail->update([
                'rekanan_id' => $this->rekanan_id,
                'harga_jual' => $this->harga_jual,
                'berat' => $this->total_berat,
                'qty' => $this->ternak_jual,
                // Add other fields you want to update
            ]);

            // Update each Transaksi Harian
            $this->transaksiHarian->update([
                'tanggal' => $this->tanggal_harian,
                // Add other fields you want to update
            ]);

            DB::commit();
            $this->dispatch('hideModal');
            $this->dispatch('refreshDatatable');
            $this->dispatch('success', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Gagal mengubah transaksi: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $transaksiJual = TransaksiJual::findOrFail($id);
            $transaksiJual->detail()->delete();
            $transaksiJual->delete();

            $this->emit('refreshDatatable');
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Penjualan berhasil dihapus.']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    private function resetFields()
    {
        $this->tanggal = null;
        $this->tipe_transaksi = null;
        $this->jumlah = null;
        $this->harga = null;
        $this->status = null;
        $this->rekanan_id = null;
        $this->farm_id = null;
        $this->kandang_id = null;
        $this->harga_beli = null;
        $this->harga_jual = null;
        $this->qty = null;
        $this->total_berat = null;
        $this->umur = null;
        $this->hpp = null;
    }

    public function loadRekanans()
    {
        $this->rekanans = Rekanan::where('jenis','Customer')->where('status','Aktif')->get();
    }

    public function loadFarms()
    {
        $farmIds = FarmOperator::where('user_id', auth()->id())->pluck('farm_id')->toArray();
        $this->farms = Farm::whereIn('id', $farmIds)->get(['id', 'nama']);
    }

    public function loadKandangs()
    {
        $farmIds = FarmOperator::where('user_id', auth()->id())->pluck('farm_id')->toArray();
        $this->kandangs = Kandang::whereIn('farm_id', $farmIds)->where('status', 'Digunakan')->get(['id', 'farm_id','kode','nama','jumlah','kapasitas','kelompok_ternak_id','status']);

        $this->ternaks  = KelompokTernak::whereIn('farm_id', $farmIds)->get(['id','farm_id','kandang_id','name','start_date','populasi_awal','hpp','status','transaksi_id']);
    }

    public function updatedKandangId($value)
    {
        if (!$value) {
            return;
        }

        $selectedTernak = $this->ternaks->firstWhere('kandang_id', $value);

        if ($selectedTernak) {
            $this->tanggal_beli = $selectedTernak->start_date->format('Y-m-d');
            $this->harga_beli = $selectedTernak->hpp;
            
            // Calculate age in days
            $startDate = Carbon::parse($selectedTernak->start_date);
            $this->umur = $startDate->diffInDays(Carbon::now());
        }
    }

    // public function updatedFarmId($farmId)
    // {
    //     $this->loadKandangs($farmId);
    // }

    // public function loadKandangs($farmId)
    // {
    //     $this->kandangs = Kandang::where('farm_id', $farmId)->get(['id', 'nama']);
    //     $this->kandang_id = null; // Reset the selected kandang
    // }

    public function openPenjualanModal($data)
    {
        $this->initializeData($data);
        $this->isOpen = true;
        $this->emit('showModal');
    }

    public function loadPenjualanData()
    {
        $penjualan = TransaksiJual::findOrFail($this->penjualanId);
        $this->farm_id = $penjualan->farm_id;
        $this->kandang_id = $penjualan->kandang_id;
        // Load other fields
    }

    public function loadTransaksi()
    {
        $transaksi = TransaksiJual::findOrFail($this->transaksiId);
        $this->tanggal = $transaksi->tanggal;
        $this->tipe_transaksi = $transaksi->tipe_transaksi;
        $this->jumlah = $transaksi->jumlah;
        $this->harga = $transaksi->harga;
        $this->status = $transaksi->status;
        $this->rekanan_id = $transaksi->rekanan_id;
        $this->farm_id = $transaksi->farm_id;
        $this->kandang_id = $transaksi->kandang_id;
        $this->harga_beli = $transaksi->harga_beli;
        $this->harga_jual = $transaksi->harga_jual;
        $this->qty = $transaksi->qty;
        $this->total_berat = $transaksi->berat;
        $this->umur = $transaksi->umur;
    }

    private function getTransaksiData()
    {
        return [
            'tanggal' => $this->tanggal,
            'tipe_transaksi' => $this->tipe_transaksi,
            'jumlah' => $this->jumlah,
            'harga' => $this->harga,
            'status' => $this->status,
            'rekanan_id' => $this->rekanan_id,
            'farm_id' => $this->farm_id,
            'kandang_id' => $this->kandang_id,
            'harga_beli' => $this->harga_beli,
            'harga_jual' => $this->harga_jual,
            'qty' => $this->qty,
            'berat' => $this->total_berat,
            'umur' => $this->umur,
        ];
    }

    public function resetForm()
    {
        $this->reset([
            'tanggal', 'tanggal_harian', 'rekanan_id', 'faktur', 'ternak_jual', 'harga_jual', 'total_berat',
            'farm_id', 'kandang_id', 'tanggal_beli', 'harga_beli', 'umur'
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}