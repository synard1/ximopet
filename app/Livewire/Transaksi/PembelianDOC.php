<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use App\Models\Item;
use App\Models\Rekanan;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\Transaksi;
use App\Models\TernakHistory;

class PembelianDOC extends Component
{
    public $parent_id, $transaksi_id, $docs, $kode_doc, $suppliers, $kandangs, $periode, $faktur, $tanggal, $supplierSelect, $docSelect, $selectedKandang, $qty, $harga, $berat;
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
    // ];

    protected function rules()
    {
        $rules = [
            'tanggal' => 'required',
            'supplierSelect' => 'required',
            'docSelect' => 'required',
            'selectedKandang' => 'required',
            'qty' => 'required|integer',
            'berat' => 'required|integer',
            'harga' => 'required|integer',
        ];

        if (!$this->edit_mode) { // Only add the 'faktur' rule if NOT in edit mode
            $rules['faktur'] = 'required|unique:transaksis,faktur,NULL,id,deleted_at,NULL';

        }

        return $rules;
    }

    public function render()
    {
        $this->docs = Item::where('jenis','DOC')->get();
        $this->suppliers = Rekanan::where('jenis','Supplier')->get();
        // $this->kandangs = Kandang::where('status','Aktif')->get();
        $this->kandangs = Kandang::all();
        return view('livewire.transaksi.pembelian-d-o-c',[
            'docs' => $this->docs,
            'suppliers' => $this->suppliers,
            'kandangs' => $this->kandangs,
        ]);
    }

    public function storeDOC()
    {
            // $supplier = Rekanan::where('id', $this->supplierSelect)->first();
            // $kandang = Kandang::where('id', $this->selectedKandang)->first();
            // $doc = Item::where('id',$this->docSelect)->first();
        
            // // Prepare the data for creating/updating
            // $data = [
            //     'jenis' => 'Pembelian',
            //     // 'jenis_barang' => 'DOC',
            //     'faktur' => $this->faktur,
            //     'tanggal' => $this->tanggal,
            //     'rekanan_id' => $this->supplierSelect,
            //     'farm_id' => $kandang->farm_id,
            //     'kandang_id' => $this->selectedKandang,
            //     'harga' => $this->harga,
            //     'total_qty' => $this->qty,
            //     'sub_total' => $this->qty * $this->harga,
            //     'user_id' => auth()->user()->id,
            //     'status' => 'Aktif',
            // ];

            // $transaksi = Transaksi::updateOrCreate(['id' => $this->transaksi_id], $data);
            // $kandang->update(
            //     [
            //         'status' => 'Digunakan',
            //         'jumlah' => $transaksi->total_qty,
            //     ]
            // );
        try {
            // Validate the form input data
            $this->validate(); 
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            $supplier = Rekanan::where('id', $this->supplierSelect)->first();
            $kandang = Kandang::where('id', $this->selectedKandang)->first();
            $doc = Item::where('id',$this->docSelect)->first();

            // Validate that qty does not exceed kandang capacity
            if ($this->qty > $kandang->kapasitas) {
                throw ValidationException::withMessages([
                    'qty' => 'Jumlah DOC tidak boleh melebihi kapasitas kandang (' . $kandang->kapasitas . ').'
                ]);
            }
        
            // Prepare the data for creating/updating
            $data = [
                'jenis' => 'Pembelian',
                // 'jenis_barang' => 'DOC',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'rekanan_id' => $this->supplierSelect,
                'farm_id' => $kandang->farm_id,
                'kandang_id' => $this->selectedKandang,
                'harga' => $this->harga,
                'total_qty' => $this->qty,
                'total_berat' => $this->berat,
                'sub_total' => $this->qty * $this->harga,
                'terpakai'  => 0,
                'sisa'  => $this->qty,
                'kelompok_ternak_id' => null,
                'user_id' => auth()->user()->id,
                'status' => 'Aktif',
            ];

            // $transaksi = Transaksi::create($data);
            $transaksi = Transaksi::updateOrCreate(['id' => $this->transaksi_id], $data);
            $kandang->update(
                [
                    'status' => 'Digunakan',
                    'jumlah' => $transaksi->total_qty,
                    'berat' => $transaksi->total_berat,
                ]
            );

            if($transaksi->kelompokTernak()->exists()){
                $kelompokTernak = $transaksi->kelompokTernak;
            }else{
                $kelompokTernak = $transaksi->kelompokTernak()->create([
                    'transaksi_id' => $transaksi->id, // Ensure this is set
                    'name' => $this->periode,
                    'breed' => 'DOC',
                    'start_date' => $transaksi->tanggal,
                    'estimated_end_date' => $transaksi->tanggal->addMonths(6),
                    'stok_awal' => 0,
                    'stok_masuk' => $transaksi->total_qty,
                    'jumlah_mati' => 0,
                    'jumlah_dipotong' => 0,
                    'jumlah_dijual' => 0,
                    'stok_akhir' => $transaksi->total_qty,
                    'berat_beli' => $transaksi->total_berat,
                    'status' => 'Aktif',
                    'farm_id' => $transaksi->farm_id,
                    'kandang_id' => $transaksi->kandang_id,
                    'created_by' => auth()->user()->id,
                ]);

                $historyTernak = $kelompokTernak->historyTernaks()->create([
                    'transaksi_id' => $transaksi->id,
                    'kelompok_ternak_id' => $kelompokTernak->id,
                    'parent_id' => null,
                    'farm_id' => $transaksi->farm_id,
                    'kandang_id' => $transaksi->kandang_id,
                    'tanggal' => $transaksi->tanggal,
                    'jenis' => 'Masuk',
                    'perusahaan_nama' => $transaksi->rekanans->nama,
                    'hpp' => $transaksi->sub_total,
                    'stok_awal' => 0,
                    'stok_akhir' => $transaksi->total_qty,
                    'stok_masuk' => $transaksi->total_qty,
                    'stok_keluar' => 0,
                    'total_berat' => $kelompokTernak->berat_beli,
                    'status' => 'hidup',
                    'keterangan' => null,
                    'created_by' => auth()->user()->id,
                ]);

                $transaksi->kelompok_ternak_id = $kelompokTernak->id;
                $transaksi->save();
            }

            // Data yang akan disimpan atau diperbarui
            $transaksiDetailData = [
                'transaksi_id' => $transaksi->id,
                'jenis' => 'Pembelian',
                'jenis_barang' => 'DOC',
                'tanggal' => $transaksi->tanggal,
                'rekanan_id' => $transaksi->rekanan_id,
                'farm_id' => $transaksi->farm_id,
                'kandang_id' => $kandang->id,
                'item_id' => $doc->id,
                'item_name' => $doc->name,
                'harga' => $transaksi->harga,
                'qty' => $transaksi->total_qty,
                'berat' => $transaksi->total_berat,
                'terpakai' => 0,
                'sisa' => $transaksi->total_qty,
                'satuan_besar' => $doc->satuan_besar,
                'satuan_kecil' => $doc->satuan_kecil,
                'sub_total' => $transaksi->sub_total,
                'konversi' => $doc->konversi,
                'status' => 'Aktif',
                'user_id' => auth()->user()->id,
                'created_by' => auth()->user()->id,
            ];

            // Gunakan updateOrCreate untuk membuat atau memperbarui TransaksiDetail
            $transaksiDetail = $transaksi->transaksiDetail()->updateOrCreate(
                ['transaksi_id' => $transaksi->id, 'item_id' => $doc->id], // Kondisi untuk mencari record yang sudah ada
                $transaksiDetailData // Data yang akan disimpan atau diperbarui
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
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. '.$e->getMessage());
            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            // $this->reset();
        }
    }

    public function editDoc($id)
    {
        $pembelian = Transaksi::with('transaksiDetail')->where('id',$id)->first();
        // dd($pembelian->transaksiDetail[0]['item_id']);

        // Format the date using Carbon
        // $formattedTanggal = $this->formatDateTime($pembelian->tanggal);

        $this->transaksi_id = $id;
        $this->faktur = $pembelian->faktur;
        $this->tanggal = $pembelian->tanggal;
        $this->supplierSelect = $pembelian->rekanan_id;
        $this->docSelect = $pembelian->transaksiDetail[0]['item_id'];
        $this->selectedKandang = $pembelian->kandang_id;
        $this->qty = $pembelian->total_qty;
        $this->harga = $pembelian->harga;
        $this->periode = $pembelian->periode;

        $this->edit_mode = true;

        // dd($this->selectedKandang);
        // $this->openModal();
    }

    public function deleteTransaksiDoc($id)
    {
        try {
                // Wrap database operation in a transaction (if applicable)
                DB::beginTransaction();
    
                $transaksi = Transaksi::where('id', $id)->first();
                $transaksiDetail = $transaksi->transaksiDetail()->first();
                $kandang = Kandang::where('id', $transaksi->kandang_id)->first();

                if ($kandang) {
                    $kandang->update([
                        'status' => 'Aktif',
                        'jumlah' => '0',
                        'berat' => '0',
                        'updated_by' => auth()->user()->id,
                    ]);
                } else {
                    // Handle the case where the kandang is not found
                    // You might want to log this or throw an exception
                    $this->dispatch('error', "Kandang not found for id: {$transaksi->kandang_id}");

                }

                $kelompokTernak = KelompokTernak::where('id', $transaksi->kelompok_ternak_id)->first();

                $historyTernak = TernakHistory::where('kelompok_ternak_id', $kelompokTernak->id)->first();

                

                //Delete Kelompok Ternak & Ternak History
                $historyTernak->delete();
                $kelompokTernak->delete();

                

                // Delete the user record with the specified ID
                $transaksiDetail->delete();
                Transaksi::destroy($id);
    
                
    
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
