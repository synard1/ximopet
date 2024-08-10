<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Stok;
use App\Models\Rekanan;
use App\Models\Kandang;

class PembelianDOC extends Component
{
    public $docs, $kode_doc, $suppliers, $kandangs, $periode, $faktur, $tanggal, $supplierSelect, $docSelect, $selectedKandang, $qty, $harga;

    protected $rules = [
        'faktur' => 'required|unique:transaksis,faktur',
        'tanggal_pembelian' => 'required',
        'supplierSelect' => 'required',
        'docSelect' => 'required',
        'selectedKandang' => 'required',
        'qty' => 'required|integer',
        'periode' => 'required',
    ];

    public function render()
    {
        $this->docs = Stok::where('jenis','DOC')->get();
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        $this->kandangs = Kandang::where('status','Aktif')->get();
        return view('livewire.transaksi.pembelian-d-o-c',[
            'docs' => $this->docs,
            'suppliers' => $this->suppliers,
            'kandangs' => $this->kandangs,
        ]);
    }

    public function storeDOC()
    {
        // $this->validate(); 

        $supplier = Rekanan::where('id', $this->supplierSelect)->first();

        // Prepare the data for creating/updating the kandang
        $data = [
            'jenis' => 'Pembelian',
            'jenis_barang' => 'DOC',
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'rekanan_id' => $this->supplierSelect,
            'farm_id' => $this->farm_id ?? '',
            'kandang_id' => $this->selectedKandang ?? '',
            'rekanan_nama' => $$supplier->nama ?? '',
            'harga' => $this->harga,
            'jumlah' => $this->qty,
            'periode' => $this->periode,
            'user_id' => auth()->user()->id,
        ];

        dd($data);

        // try {
        //     // Validate the form input data
        //     $this->validate(); 
        
        //     // Wrap database operation in a transaction (if applicable)
        //     DB::beginTransaction();
        
        //     // Prepare the data for creating/updating the stok
        //     $data = [
        //         'jenis' => $this->jenis,
        //         'kode' => $this->kode_stok,
        //         'nama' => $this->nama,
        //         'satuan_besar' => $this->satuan_besar,
        //         'satuan_kecil' => $this->satuan_kecil,
        //         'konversi' => $this->konversi,
        //         'status' => 'Aktif',
        //         'user_id' => auth()->user()->id,
        //     ];
        
        //     $stok = Stok::where('id', $this->stok_id)->first() ?? Stok::create($data);
        
        //     DB::commit();
    
        //     // Emit success event if no errors occurred
        //     $this->dispatch('success', 'Stok '. $stok->nama .' berhasil ditambahkan');
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
}
