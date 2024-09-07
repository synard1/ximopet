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

class PembelianDOC extends Component
{
    public $parent_id, $transaksi_id, $docs, $kode_doc, $suppliers, $kandangs, $periode, $faktur, $tanggal, $supplierSelect, $docSelect, $selectedKandang, $qty, $harga;
    public $edit_mode=0;

    protected $listeners = [
        'delete_transaksi_doc' => 'deleteTransaksiDoc',
        'editDoc' => 'editDoc',
        'resetFormAndErrors' => 'resetFormAndErrors',
    ];

    // protected $rules = [
    //     'faktur' => 'required|unique:transaksis,faktur,NULL,id,deleted_at,NULL',
    //     'tanggal' => 'required',
    //     'supplierSelect' => 'required',
    //     'docSelect' => 'required',
    //     'selectedKandang' => 'required',
    //     'qty' => 'required|integer',
    //     'harga' => 'required|integer',
    //     'periode' => 'required|unique:transaksis,periode,NULL,id,deleted_at,NULL',
    // ];

    protected function rules()
    {
        $rules = [
            'tanggal' => 'required',
            'supplierSelect' => 'required',
            'docSelect' => 'required',
            'selectedKandang' => 'required',
            'qty' => 'required|integer',
            'harga' => 'required|integer',
        ];

        if (!$this->edit_mode) { // Only add the 'faktur' rule if NOT in edit mode
            $rules['faktur'] = 'required|unique:transaksis,faktur,NULL,id,deleted_at,NULL';
            $rules['periode'] = 'required|unique:transaksis,periode,NULL,id,deleted_at,NULL';
        }

        return $rules;
    }

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
        try {
        //     // Validate the form input data
            $this->validate(); 
        
        //     // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            $supplier = Rekanan::where('id', $this->supplierSelect)->first();
            $kandang = Kandang::where('id', $this->selectedKandang)->first();
            $doc = Stok::where('id',$this->docSelect)->first();
        
            // Prepare the data for creating/updating
            $data = [
                'parent_id' => null,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'rekanan_id' => $this->supplierSelect,
                'farm_id' => $kandang->farm_id,
                'kandang_id' => $this->selectedKandang,
                'rekanan_nama' => $supplier->nama ?? '',
                'harga' => $this->harga,
                'qty' => $this->qty,
                'sub_total' => $this->qty * $this->harga,
                'periode' => $this->periode,
                'user_id' => auth()->user()->id,
                'payload'=> [
                    'doc' => $doc,
                ],
                'status' => 'Aktif',
            ];

            $transaksi = Transaksi::updateOrCreate(['id' => $this->transaksi_id], $data);
            $kandang->update(
                [
                    'status' => 'Digunakan',
                    'jumlah' => $transaksi->qty,
                ]
            );

        
            // $transaksi = Transaksi::where('id', $this->transaksi_id)->first() ?? Transaksi::create($data);

            // dd($transaksi);
        
            DB::commit();
            if($this->transaksi_id){
                $this->dispatch('success', 'Data Pembelian DOC '. $transaksi->faktur .' berhasil diubah');
            }else{
                $this->dispatch('success', 'Data Pembelian DOC '. $transaksi->faktur .' berhasil ditambahkan');

            }
    
            // Emit success event if no errors occurred
            $this->reset();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
    
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            // $this->reset();
        }
    }

    public function editDoc($id)
    {
        $pembelian = Transaksi::where('id',$id)->first();

        // Format the date using Carbon
        // $formattedTanggal = $this->formatDateTime($pembelian->tanggal);

        $this->transaksi_id = $id;
        $this->faktur = $pembelian->faktur;
        $this->tanggal = $pembelian->tanggal;
        $this->supplierSelect = $pembelian->rekanan_id;
        $this->docSelect = $pembelian->payload['doc']['id'];
        $this->selectedKandang = $pembelian->kandang_id;
        $this->qty = $pembelian->qty;
        $this->harga = $pembelian->harga;
        $this->periode = $pembelian->periode;

        $this->edit_mode = true;
        // $this->openModal();
    }

    public function deleteTransaksiDoc($id)
    {
        try {
                // Wrap database operation in a transaction (if applicable)
                DB::beginTransaction();
    
                $transaksi = Transaksi::where('id', $id)->first();
                $kandang = Kandang::where('id', $transaksi->kandang_id)->first();

                // Delete the user record with the specified ID
                Transaksi::destroy($id);
    
                $kandang->update(
                    [
                        'status' => 'Aktif',
                        'jumlah' => '0',
                    ]
                );
    
                DB::commit();
                // Emit a success event with a message
                $this->dispatch('success', 'Data berhasil dihapus');
        
                // Emit success event if no errors occurred
                $this->reset();
            } catch (ValidationException $e) {
                $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
                $this->setErrorBag($e->validator->errors());
            } catch (\Exception $e) {
                DB::rollBack();
        
                // Handle validation and general errors
                $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
                // Optionally log the error: Log::error($e->getMessage());
            } finally {
                // Reset the form in all cases to prepare for new data
                // $this->reset();
            }
    }

    public function resetFormAndErrors()
    {
        $this->reset(); // Reset component data
        $this->resetErrorBag(); // Clear validation errors
        $this->resetValidation(); // Additional step to clear validation state
    }

    public function mount()
    {
        $this->resetFormAndErrors();
    }

    public function closeModalDOC()
    {
        $this->dispatch('closeFormPembelian');
        $this->resetFormAndErrors();
    }
}
