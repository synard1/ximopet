<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Stok;
use App\Models\Rekanan;
use App\Models\Kandang;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\Transaksi;

class PembelianStok extends Component
{
    public $transaksi_id, $docs, $kode_doc, $suppliers, $kandangs, $periode, $faktur, $tanggal, $supplierSelect, $docSelect, $selectedKandang, $qty, $harga;

    protected $rules = [
        'faktur' => 'required|unique:transaksis,faktur',
        'tanggal' => 'required',
        'supplierSelect' => 'required',
        'docSelect' => 'required',
        'selectedKandang' => 'required',
        'qty' => 'required|integer',
        'harga' => 'required|integer',
        'periode' => 'required|unique:transaksis,periode',
    ];

    public function render()
    {
        $this->docs = Stok::where('jenis','DOC')->get();
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        $this->kandangs = Kandang::where('status','Aktif')->get();
        return view('livewire.transaksi.pembelian-stok',[
            'docs' => $this->docs,
            'suppliers' => $this->suppliers,
            'kandangs' => $this->kandangs,
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
            $doc = Stok::where('id',$this->docSelect)->first();
        
            // Prepare the data for creating/updating
            $data = [
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'rekanan_id' => $this->supplierSelect,
                'farm_id' => $kandang->farm_id,
                'kandang_id' => $this->selectedKandang,
                'rekanan_nama' => $supplier->nama ?? '',
                'harga' => $this->harga,
                'jumlah' => $this->qty,
                'sub_total' => $this->qty * $this->harga,
                'periode' => $this->periode,
                'user_id' => auth()->user()->id,
                'payload'=> [
                    'doc' => $doc,
                ],
                'status' => 'Aktif',
            ];
        
            $transaksi = Transaksi::where('id', $this->transaksi_id)->first() ?? Transaksi::create($data);

            dd($transaksi);
        
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

    public function createPembelian($id)
    {
        $customer = Rekanan::where('id',$id)->first();
        $this->customer_id = $id;
        $this->kode = $customer->kode;
        $this->nama = $customer->nama;
        $this->alamat = $customer->alamat;
        $this->telp = $customer->telp;
        $this->pic = $customer->pic;
        $this->telp_pic = $customer->telp_pic;
        $this->email = $customer->email;

        // session()->flash('message', 'test edit');


        $this->openModal();
    }
}
