<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Item;
use App\Models\Rekanan;
use App\Models\Kandang;
use App\Models\FarmOperator;
use App\Models\Ternak;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;

class PembelianStok extends Component
{
    public $transaksi_id, $docs, $kode_doc, $suppliers, $kandangs, $periode, $faktur, $tanggal, $supplierSelect, $docSelect, $selectedKandang, $qty, $harga, $selectedFarm, $farms, $ternaks, $selectedTernak;

    protected $rules = [
        'faktur' => 'required|unique:transaksis,faktur',
        'tanggal' => 'required',
        'supplierSelect' => 'required',
        'docSelect' => 'required',
        'selectedKandang' => 'required',
        'qty' => 'required|numeric',
        'harga' => 'required|numeric',
        'periode' => 'required|unique:transaksis,periode',
    ];

    protected $listeners = [
        'delete_transaksi_stok' => 'deleteTransaksi',
    ];

    public function render()
    {
        $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

        $this->docs = Item::where('jenis', 'DOC')->get();
        $this->suppliers = Rekanan::where('jenis', 'Supplier')->get();
        $this->farms = FarmOperator::where('user_id', auth()->user()->id)->get();
        $this->kandangs = Kandang::where('status', 'Aktif')->get();
        $this->ternaks = Ternak::whereIn('id', $farmIds)->where('status', 'Aktif')->get(['id', 'nama']);

        return view('livewire.transaksi.pembelian-stok', [
            'docs' => $this->docs,
            'suppliers' => $this->suppliers,
            'kandangs' => $this->kandangs,
            'ternaks' => $this->ternaks,
        ]);
    }

    public function storeStok()
    {
        // try {
        //     // Validate the form input data
        //     $this->validate(); 

        //     // Wrap database operation in a transaction (if applicable)
        //     DB::beginTransaction();

        $supplier = Rekanan::where('id', $this->supplierSelect)->first();
        $kandang = Kandang::where('id', $this->selectedKandang)->first();
        $doc = Item::where('id', $this->docSelect)->first();

        // Prepare the data for creating/updating
        $data = [
            'jenis' => 'Pembelian',
            'jenis_barang' => 'DOC',
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'rekanan_id' => $this->supplierSelect,
            'farm_id' => $kandang->farm_id,
            'coop_id' => $this->selectedKandang,
            'rekanan_nama' => $supplier->nama ?? '',
            'harga' => $this->harga,
            'qty' => $this->qty * $doc->konversi,
            'sub_total' => $this->qty * $this->harga,
            'periode' => $this->periode,
            'user_id' => auth()->user()->id,
            'payload' => [
                'doc' => $doc,
            ],
            'status' => 'Aktif',
        ];

        $transaksi = Transaksi::where('id', $this->transaksi_id)->first() ?? Transaksi::create($data);

        // dd($transaksi);

        //     DB::commit();

        //     // Emit success event if no errors occurred
        //     $this->dispatch('success', 'Data Pembelian DOC '. $transaksi->faktur .' berhasil ditambahkan');
        // } catch (ValidationException $e) {
        //     $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
        //     $this->setErrorBag($e->validator->errors());
        // } catch (\Exception $e) {
        //     DB::rollBack();

        //     // Handle validation and general errors
        //     $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
        //     // Optionally log the error: Log::error($e->getMessage());
        // } finally {
        //     // Reset the form in all cases to prepare for new data
        //     $this->reset();
        // }
    }

    public function deleteTransaksi($id)
    {
        // Delete the user record with the specified ID
        Transaksi::destroy($id);
        TransaksiDetail::destroy('transaksi_id', $id);

        // Emit a success event with a message
        $this->dispatch('success', 'Data berhasil dihapus');
    }
}
