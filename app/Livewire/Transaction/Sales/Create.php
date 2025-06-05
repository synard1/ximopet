<?php

namespace App\Livewire\Transaction\Sales;

use Livewire\Component;
use App\Models\TransaksiJual;
use Illuminate\Support\Facades\DB;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\Kandang;
use App\Models\Livestock;
use App\Models\Partner;
use App\Services\TernakService;
use App\Models\LivestockSales;
use Carbon\Carbon;
use App\Models\LivestockCost;
use App\Models\SalesTransaction;

class Create extends Component
{

    protected $ternakService;

    public function boot()
    {
        $this->ternakService = app(TernakService::class);
    }

    public $salesId = null;
    public $dataTernak = null;
    public $transaksiHarian = null;
    public $transaksiJual = null;
    public $ternakJual = null;
    public $transaksiBeli = null;
    public $transaksiJualDetail = null;
    public $isEdit = false;
    public $modalTitle = 'Tambah Penjualan';
    public $isOpen = false;
    public $mode = 'create';
    public $penjualanId;
    public $transaksiId;
    public $data;
    public $kt_daterangepicker_1;


    // Form fields
    public $tanggal_beli, $invoice, $tanggal, $tanggal_harian, $tipe_transaksi, $ternak_jual, $harga, $status;
    public $pelanggan_id, $farm_id, $coop_id, $harga_beli, $harga_jual, $qty, $total_berat, $umur, $hpp, $estimated_cost;

    // Dynamic data
    public $partners = [];
    public $farms = [];
    public $kandangs = [];
    public $livestocks = [];
    public $selectedFarm = null;
    public $selectedKandang = null;
    public $showForm = false;
    public $edit_mode = false;
    public $formReady = false;
    public $requiredFieldsFilled = false;

    // protected $listeners = [
    //     'refreshComponent' => '$refresh',
    //     'openPenjualanModal' => 'openPenjualanModal',
    //     'editPenjualan' => 'editPenjualan',
    //     'resetForm' => 'resetForm',
    // ];

    protected $listeners = [
        'deleteSupplyPurchaseBatch' => 'deleteSupplyPurchaseBatch',
        'updateDoNumber' => 'updateDoNumber',
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',

    ];

    protected $rules = [
        'tanggal' => 'required|date',
        'pelanggan_id' => 'required|exists:partners,id',
        'invoice' => 'required|string',
        'ternak_jual' => 'required|numeric',
        'harga_jual' => 'required|numeric',
        'total_berat' => 'required|numeric|between:0,9999999.99',
        'farm_id' => 'required|exists:farms,id',
        'coop_id' => 'required|exists:coops,id',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        $this->checkRequiredFields();
    }

    public function checkRequiredFields()
    {
        $this->requiredFieldsFilled = !empty($this->tanggal) &&
            !empty($this->pelanggan_id) &&
            !empty($this->invoice);
    }

    public function updatedTanggal($value)
    {
        $this->validateOnly('tanggal');
        $this->checkRequiredFields();
    }

    public function updatedPelangganId($value)
    {
        $this->validateOnly('pelanggan_id');
        $this->checkRequiredFields();
    }

    public function updatedInvoice($value)
    {
        $this->validateOnly('invoice');
        $this->checkRequiredFields();
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
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function mount($data = null)
    {
        $this->data = $data;
        $this->loadPartners();
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

        return view('livewire.transaction.sales.create');
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

        DB::beginTransaction();
        try {
            // Check if livestock exists and has sufficient quantity
            $livestock = Livestock::where('farm_id', $this->farm_id)
                ->where('coop_id', $this->coop_id)
                ->where('status', 'active')
                ->whereRaw('(populasi_awal - quantity_depletion - quantity_sales - quantity_mutated) > 0')
                ->lockForUpdate()
                ->first();

            $totalAvailable = $livestock
                ? ($livestock->populasi_awal - $livestock->quantity_depletion - $livestock->quantity_sales - $livestock->quantity_mutated)
                : 0;

            if (!$livestock) {
                throw new \Exception('Data ternak tidak ditemukan');
            }

            // Check quantity only for new transactions
            if (!$this->salesId && $totalAvailable < $this->ternak_jual) {
                throw new \Exception('Stok ternak tidak mencukupi. Stok saat ini: ' . $totalAvailable);
            }

            $payload = [
                'age' => $this->umur,
                'harga_beli' => $this->harga_beli,
                'estimated_cost' => $this->estimated_cost,
            ];

            $salesData = [
                'transaction_date' => $this->tanggal,
                'customer_id' => $this->pelanggan_id,
                'invoice_number' => $this->invoice,
                'livestock_id' => $livestock->id,
                'quantity' => $this->ternak_jual,
                'price' => $this->harga_jual,
                'total_price' => $this->harga_jual * $this->total_berat,
                'weight' => $this->total_berat,
                'payload' => $payload,
                'status' => 'active',
                'updated_by' => auth()->id()
            ];

            if ($this->salesId) {
                // Update existing transaction
                $salesTransaction = SalesTransaction::findOrFail($this->salesId);

                // If quantity changed, update livestock quantity
                if ($salesTransaction->quantity != $this->ternak_jual) {
                    $quantityDiff = $salesTransaction->quantity - $this->ternak_jual;
                    $livestock->update([
                        'quantity_sales' => $livestock->quantity_sales - $quantityDiff,
                        'updated_by' => auth()->id()
                    ]);
                }

                $salesTransaction->update($salesData);
                $message = 'Penjualan berhasil diperbarui';
            } else {
                // Create new transaction
                $salesData['created_by'] = auth()->id();
                $salesTransaction = SalesTransaction::create($salesData);

                // Update livestock quantity
                $livestock->update([
                    'quantity_sales' => $livestock->quantity_sales + $this->ternak_jual,
                    'updated_by' => auth()->id()
                ]);
                $message = 'Penjualan berhasil ditambahkan';
            }

            DB::commit();
            $this->dispatch('success', $message);
            $this->resetForm();
            $this->showForm = false;
            $this->dispatch('show-datatable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Gagal ' . ($this->salesId ? 'mengubah' : 'menambahkan') . ' penjualan: ' . $e->getMessage());
        }
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

            $this->ternakJual->update([
                'transaksi_jual_id' => $this->transaksiJual->id,
                'tanggal' => $this->tanggal,
                'quantity' => $this->ternak_jual,
                'total_berat' => $this->total_berat,

            ]);

            // Update each TransaksiJualDetail
            $this->transaksiJualDetail->update([
                'partner_id' => $this->partner_id,
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
        $this->harga = null;
        $this->status = null;
        $this->farm_id = null;
        $this->coop_id = null;
        $this->harga_beli = null;
        $this->harga_jual = null;
        $this->qty = null;
        $this->total_berat = null;
        $this->umur = null;
        $this->hpp = null;
    }

    public function loadPartners()
    {
        $this->partners = Partner::where('type', 'Customer')->where('status', 'active')->get();
    }

    public function loadFarms()
    {
        $farmIds = FarmOperator::where('user_id', auth()->id())->pluck('farm_id')->toArray();
        $this->farms = Farm::whereIn('id', $farmIds)->get(['id', 'name']);
    }

    public function loadKandangs()
    {
        $farmIds = FarmOperator::where('user_id', auth()->id())->pluck('farm_id')->toArray();
        $this->kandangs = Kandang::whereIn('farm_id', $farmIds)->where('status', 'Digunakan')->get(['id', 'farm_id', 'kode', 'nama', 'jumlah', 'kapasitas', 'livestock_id', 'status']);

        $this->livestocks  = Livestock::whereIn('farm_id', $farmIds)->get(['id', 'farm_id', 'coop_id', 'name', 'start_date', 'populasi_awal', 'harga', 'status']);
    }

    public function updatedKandangId($value)
    {
        if (!$value) {
            return;
        }

        $selectedLivestock = $this->livestocks->firstWhere('coop_id', $value);

        if ($selectedLivestock) {
            $this->tanggal_beli = $selectedLivestock->start_date->format('Y-m-d');
            $this->harga_beli = formatRupiah($selectedLivestock->harga, 0);

            // Calculate age in days
            $startDate = Carbon::parse($selectedLivestock->start_date);
            $this->umur = $startDate->diffInDays(Carbon::parse($this->tanggal));

            // Get total cost_per_ayam from LivestockCost
            $totalCost = LivestockCost::where('livestock_id', $selectedLivestock->id)
                ->sum('cost_per_ayam');

            $this->estimated_cost = formatRupiah($totalCost, 0);
        }
    }

    public function resetForm()
    {
        $this->reset([
            'tanggal',
            'pelanggan_id',
            'invoice',
            'ternak_jual',
            'harga_jual',
            'total_berat',
            'farm_id',
            'coop_id',
            'tanggal_beli',
            'harga_beli',
            'umur'
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->requiredFieldsFilled = false;
    }

    public function showEditForm($id)
    {
        $sales = SalesTransaction::with('livestock')->findOrFail($id);
        $selectedLivestock = $this->livestocks->firstWhere('coop_id', $sales->livestock->coop_id);

        $this->salesId = $sales->id;
        $this->invoice = $sales->invoice_number;
        $this->tanggal = $sales->transaction_date->format('Y-m-d');
        $this->pelanggan_id = $sales->customer_id;
        $this->farm_id = $sales->livestock->farm_id;
        $this->coop_id = $sales->livestock->coop_id;
        $this->ternak_jual = $sales->quantity;
        $this->total_berat = $sales->weight;
        $this->harga_jual = $sales->price;
        // $this->umur = $feed->payload['conversion_units'] ?? [];


        if ($selectedLivestock) {
            $this->tanggal_beli = $selectedLivestock->start_date->format('Y-m-d');
            $this->harga_beli = formatRupiah($selectedLivestock->harga, 0);

            // Calculate age in days
            $startDate = Carbon::parse($selectedLivestock->start_date);
            $this->umur = $startDate->diffInDays(Carbon::parse($this->tanggal));

            // Get total cost_per_ayam from LivestockCost
            $totalCost = LivestockCost::where('livestock_id', $selectedLivestock->id)
                ->sum('cost_per_ayam');

            $this->estimated_cost = formatRupiah($totalCost, 0);
        }

        $this->checkRequiredFields();


        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }
}
