<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

use App\Models\Item;
use App\Models\Rekanan;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Ternak;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\StandarBobot;
use App\Models\Transaksi;
use App\Models\TernakHistory;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;
use App\Models\TransaksiHarian;
use App\Models\TransaksiTernak;
use App\Models\CurrentTernak;
use App\Models\InventoryLocation;
use App\Models\Livestock;
use App\Models\LivestockStrain;
use App\Models\LivestockStrainStandard;
use App\Models\LivestockPurchaseItem;
use App\Services\TernakService;
use App\Services\Livestock\LivestockPurchaseService;
use Carbon\Carbon;

use App\Models\Partner;

class PembelianDOC extends Component
{
    public $parent_id, $transaksi_id, $docs, $kode_doc, $suppliers, $kandangs, $periode, $faktur, $tanggal, $supplierSelect, $docSelect, $selectedKandang, $qty, $harga, $berat, $pic, $standarDocSelect, $standarDocs = [];
    public $edit_mode = 0;
    public Ternak $kelompokTernak; // Public property holding the model instance
    public $livestockId = null;


    protected $listeners = [
        'delete_transaksi_doc' => 'deleteTransaksiDoc',
        'lock_doc' => 'lockDoc',
        'unlock_doc' => 'unlockDoc',
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
            'qty' => 'required|numeric',
            'berat' => 'required|numeric',
            'harga' => 'required|numeric',
            'pic' => 'required',
            'faktur' => 'required',
            'standarDocSelect' => 'nullable',
        ];

        if (!$this->edit_mode) { // Only add the 'faktur' rule if NOT in edit mode
            $rules['faktur'] = 'required|unique:transaksi_beli,faktur,NULL,id,deleted_at,NULL';
        }

        return $rules;
    }

    public function render()
    {
        $this->docs = LivestockStrain::all();
        // $this->docs = Item::whereHas('itemCategory', function($query) {
        //     $query->where('name', 'DOC');
        // })->with('itemCategory')->get();

        $this->standarDocs = LivestockStrainStandard::all();
        $this->suppliers = Partner::where('type', 'Supplier')->get();
        $this->kandangs = Kandang::all();
        // $this->kandangs = Kandang::all();
        return view('livewire.transaksi.pembelian-d-o-c', [
            'docs' => $this->docs,
            'standarDocs' => $this->standarDocs,
            'suppliers' => $this->suppliers,
            'kandangs' => $this->kandangs,
        ]);
    }

    public function storeDOC()
    {

        // dd($this->all());
        try {
            // Validate the form input data
            $this->validate();

            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            $supplier = Partner::where('id', $this->supplierSelect)->first();
            $kandang = Kandang::where('id', $this->selectedKandang)->first();
            $farm = Farm::where('id', $kandang->farm_id)->first();
            $doc = Item::where('id', $this->docSelect)->first();

            // Validate that qty does not exceed kandang capacity
            if ($this->qty > $kandang->kapasitas) {
                throw ValidationException::withMessages([
                    'qty' => 'Jumlah DOC tidak boleh melebihi kapasitas kandang (' . $kandang->kapasitas . ').'
                ]);
            }

            // Prepare the data for creating/updating
            $data = [
                'jenis' => 'DOC',
                'faktur' => $this->faktur,
                'tanggal' => $this->tanggal,
                'partner_id' => $this->supplierSelect,
                'farm_id' => $kandang->farm_id,
                'kandang_id' => $this->selectedKandang,
                'pic' => $this->pic,
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

            $transaksi = $this->createDocPurchase($farm, $kandang, $data, $supplier);

            // // $transaksi = Transaksi::create($data);
            // $transaksi = TransaksiBeli::updateOrCreate(['id' => $this->transaksi_id], $data);
            // $kandang->update(
            //     [
            //         'status' => 'Digunakan',
            //         'jumlah' => $transaksi->total_qty,
            //         'berat' => $transaksi->total_berat,
            //     ]
            // );

            // if($transaksi->kelompokTernak()->exists()){
            //     $kelompokTernak = $transaksi->kelompokTernak;
            // }else{
            //     $kelompokTernak = $transaksi->kelompokTernak()->create([
            //         'transaksi_id' => $transaksi->id, // Ensure this is set
            //         'name' => 'PR-' . $farm->kode . '-' . $kandang->kode . '-' . Carbon::parse($transaksi->tanggal)->format('dmY'),
            //         'breed' => 'DOC',
            //         'start_date' => $transaksi->tanggal,
            //         'populasi_awal' => $transaksi->total_qty,
            //         'berat_awal' => $transaksi->total_berat,
            //         'hpp' => $transaksi->harga,
            //         'status' => 'Aktif',
            //         'keterangan' => null,
            //         'created_by' => auth()->user()->id,
            //     ]);

            //     $historyTernak = $kelompokTernak->historyTernaks()->create([
            //         'transaksi_id' => $transaksi->id,
            //         'kelompok_ternak_id' => $kelompokTernak->id,
            //         'parent_id' => null,
            //         'farm_id' => $transaksi->farm_id,
            //         'kandang_id' => $transaksi->kandang_id,
            //         'tanggal' => $transaksi->tanggal,
            //         'jenis' => 'Masuk',
            //         'perusahaan_nama' => $transaksi->rekanans->nama,
            //         'hpp' => $transaksi->sub_total,
            //         'stok_awal' => 0,
            //         'stok_akhir' => $transaksi->total_qty,
            //         'stok_masuk' => $transaksi->total_qty,
            //         'stok_keluar' => 0,
            //         'total_berat' => $kelompokTernak->berat_beli,
            //         'status' => 'hidup',
            //         'keterangan' => null,
            //         'created_by' => auth()->user()->id,
            //     ]);

            //     $transaksi->kelompok_ternak_id = $kelompokTernak->id;
            //     $transaksi->save();
            // }

            // // Data yang akan disimpan atau diperbarui
            // $transaksiDetailData = [
            //     'transaksi_id' => $transaksi->id,
            //     'jenis' => 'Pembelian',
            //     'jenis_barang' => 'DOC',
            //     'tanggal' => $transaksi->tanggal,
            //     'rekanan_id' => $transaksi->rekanan_id,
            //     'farm_id' => $transaksi->farm_id,
            //     'kandang_id' => $kandang->id,
            //     'item_id' => $doc->id,
            //     'item_name' => $doc->name,
            //     'harga' => $transaksi->harga,
            //     'qty' => $transaksi->total_qty,
            //     'berat' => $transaksi->total_berat,
            //     'terpakai' => 0,
            //     'sisa' => $transaksi->total_qty,
            //     'satuan_besar' => $doc->satuan_besar,
            //     'satuan_kecil' => $doc->satuan_kecil,
            //     'sub_total' => $transaksi->sub_total,
            //     'konversi' => $doc->konversi,
            //     'status' => 'Aktif',
            //     'user_id' => auth()->user()->id,
            //     'created_by' => auth()->user()->id,
            // ];

            // // Gunakan updateOrCreate untuk membuat atau memperbarui TransaksiDetail
            // $transaksiDetail = $transaksi->transaksiDetail()->updateOrCreate(
            //     ['transaksi_id' => $transaksi->id, 'item_id' => $doc->id], // Kondisi untuk mencari record yang sudah ada
            //     $transaksiDetailData // Data yang akan disimpan atau diperbarui
            // );


            // $transaksi = Transaksi::where('id', $this->transaksi_id)->first() ?? Transaksi::create($data);

            // dd($transaksi);

            DB::commit();
            if ($this->transaksi_id) {
                $this->dispatch('success', 'Data Pembelian DOC ' . $transaksi->faktur . ' berhasil diubah');
            } else {
                $this->dispatch('success', 'Data Pembelian DOC ' . $transaksi->faktur . ' berhasil ditambahkan');
            }

            // Emit success event if no errors occurred
            $this->resetFormAndErrors();
            // $this->reset();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();

            // Detailed error message for logging (optional)
            $detailedErrorMessage = 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ', File: ' . $e->getFile() . ')';

            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage());
            // Optionally log the error: Log::error($e->getMessage());

            // Log the detailed error for debugging
            Log::error($detailedErrorMessage);
        } finally {
            // Reset the form in all cases to prepare for new data
            // $this->reset();
        }
    }

    public function editDoc($id)
    {
        // Ambil data ternak
        $ternak = Livestock::findOrFail($id);

        // Ambil data pembelian berdasarkan ternak → join lewat LivestockPurchaseItem
        $pembelianItem = LivestockPurchaseItem::with(['livestockPurchase'])
            ->where('livestock_id', $id)
            ->firstOrFail();

        $pembelian = $pembelianItem->livestockPurchase;

        // dd($ternak->kandang_id);

        // Mapping data ke property Livewire
        $this->livestockId = $ternak->id;
        $this->faktur = $pembelian->invoice_number;
        $this->tanggal = $pembelian->tanggal;
        $this->supplierSelect = $pembelian->vendor_id;

        $this->docSelect = $ternak->livestock_breed_id  ?? null; // asumsi `doc_id` di ternak
        $this->selectedKandang = $ternak->kandang_id ?? null;
        $this->standarDocSelect = $ternak->livestock_breed_standard_id;
        $this->qty = $pembelianItem->jumlah;
        $this->harga = $pembelianItem->harga_per_ekor;
        $this->periode = $ternak->name;
        $this->pic = $ternak->pic;
        $this->berat = $ternak->berat_awal;

        $this->edit_mode = true;
    }



    // public function editDoc($id)
    // {
    //     $ternak = Ternak::where('id',$id)->first();
    //     $pembelian = TransaksiBeli::with('transaksiDetails')->where('id',$ternak->transaksi_id)->first();

    //     // dd($pembelian);

    //     // Format the date using Carbon
    //     // $formattedTanggal = $this->formatDateTime($pembelian->tanggal);

    //     $this->transaksi_id = $id;
    //     $this->faktur = $pembelian->faktur;
    //     $this->tanggal = $pembelian->tanggal;
    //     $this->supplierSelect = $pembelian->rekanan_id;
    //     $this->docSelect = $pembelian->transaksiDetails[0]['item_id'];
    //     $this->selectedKandang = $pembelian->kandang_id;
    //     $this->standarDocSelect = $ternak->standar_bobot_id;
    //     $this->qty = $pembelian->total_qty;
    //     $this->harga = $pembelian->harga;
    //     $this->periode = $pembelian->periode;
    //     $this->pic = $ternak->pic;
    //     $this->berat = $ternak->berat_awal;
    //     $this->periode = $ternak->name;

    //     $this->edit_mode = true;

    //     // dd($this->selectedKandang);
    //     // $this->openModal();
    // }

    public function deleteTransaksiDoc($id)
    {
        try {
            DB::beginTransaction();

            $ternak = Ternak::where('id', $id)->first();
            $currentTernak = CurrentTernak::where('kelompok_ternak_id', $id)->first();
            $transaksi = TransaksiBeli::with('transaksiDetails')->findOrFail($ternak->transaksi_id);
            $transaksiDetail = $transaksi->transaksiDetails()->first();
            $kandang = Kandang::find($transaksi->kandang_id);

            if ($ternak->populasi_awal !== $currentTernak->quantity) {
                $this->dispatch('error', 'Terjadi kesalahan, populasi awal ternak tidak sama dengan populasi saat ini / Sudah ada data transaksi');
                return;
            }

            $this->updateKandangStatusToAktif($kandang, $transaksi->kandang_id);

            $this->deleteRelatedRecords($transaksi->kelompok_ternak_id);

            if ($transaksiDetail) {
                $transaksiDetail->delete();
            }
            $transaksi->delete();

            DB::commit();

            $this->dispatch('success', 'Data berhasil dihapus');
            $this->reset();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage());
        } finally {
            $this->reset();
        }
    }
    public function lockDoc($id)
    {
        try {
            DB::beginTransaction();

            $transaksi = TransaksiBeli::with('transaksiDetails')->findOrFail($id);
            $ternak = Ternak::find($transaksi->kelompok_ternak_id);

            $ternak->status = 'Locked';
            $ternak->save();
            DB::commit();

            $this->dispatch('success', 'Data berhasil dikunci');
            $this->reset();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    public function unlockDoc($id)
    {
        try {
            DB::beginTransaction();

            $transaksi = TransaksiBeli::with('transaksiDetails')->findOrFail($id);
            $ternak = Ternak::find($transaksi->kelompok_ternak_id);

            $ternak->status = 'Aktif';
            $ternak->save();
            DB::commit();

            $this->dispatch('success', 'Data berhasil dibuka');
            $this->reset();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage());
        } finally {
            $this->reset();
        }
    }

    private function updateKandangStatusToAktif($kandang, $kandangId)
    {
        if ($kandang) {
            $kandang->update([
                'status' => 'Aktif',
                'jumlah' => '0',
                'berat' => '0',
                'kelompok_ternak_id' => null,
                'updated_by' => auth()->user()->id,
            ]);
        } else {
            $this->dispatch('error', "Kandang not found for id: {$kandangId}");
        }
    }

    private function deleteRelatedRecords($kelompokTernakId)
    {
        $this->deleteTernakAndHistory($kelompokTernakId);
        $this->deleteCurrentTernak($kelompokTernakId);
        $this->deleteTransaksiTernak($kelompokTernakId);
    }

    private function deleteTernakAndHistory($kelompokTernakId)
    {
        $kelompokTernak = Ternak::find($kelompokTernakId);
        if ($kelompokTernak) {
            try {
                $historyTernak = TernakHistory::where('kelompok_ternak_id', $kelompokTernak->id)->first();
                if ($historyTernak) {
                    $historyTernak->delete();
                }
            } catch (\Exception $e) {
                // Log error if needed
            }
            $kelompokTernak->delete();
        }
    }

    private function deleteCurrentTernak($kelompokTernakId)
    {
        CurrentTernak::where('kelompok_ternak_id', $kelompokTernakId)->delete();
    }

    private function deleteTransaksiTernak($kelompokTernakId)
    {
        TransaksiTernak::where('kelompok_ternak_id', $kelompokTernakId)->delete();
    }

    public function resetFormAndErrors()
    {
        $this->reset(); // Reset component data
        $this->resetErrorBag(); // Clear validation errors
        $this->resetValidation(); // Additional step to clear validation state
    }

    public function mount(Ternak $kelompokTernak)
    {
        // If creating new, $this->post = new Post();
        // If editing, $this->post = $post; (or Post::find($id))
        $this->kelompokTernak = $kelompokTernak ?? new kelompokTernak();
        $this->resetFormAndErrors();
    }

    public function closeModalDOC()
    {
        $this->dispatch('closeFormPembelian');
        $this->resetFormAndErrors();
    }

    private function createDocPurchase($farm, $kandang, $data, $supplier)
    {
        $docItem = Item::whereHas('category', function ($q) {
            $q->where('name', 'DOC');
        })->first();

        // Create purchase transaction
        $purchase = TransaksiBeli::create($data);

        $purchaseDetail = $this->createDocPurchaseDetail($purchase, $docItem, $data);

        // Create kelompok ternak
        $kelompokTernak = $this->createTernak($purchase, $farm, $kandang);

        // Create transaksi ternak record
        $this->createTransaksiTernak($kelompokTernak, $purchase, $farm, $kandang);

        // Update purchase with kelompok_ternak_id
        $purchase->update([
            'kelompok_ternak_id' => $kelompokTernak->id
        ]);

        // Create current ternak record
        $this->createCurrentTernak($kelompokTernak, $farm, $kandang, $data);

        // Update kandang status
        $this->updateKandangStatus($kandang, $data, $kelompokTernak);

        return $purchase;
    }

    public function updateDOC()
    {
        $this->validate();

        // dd($this->all());

        try {
            $kandang = Kandang::findOrFail($this->selectedKandang);
            $farmId = $kandang->farm_id;

            $data = [
                'tanggal' => $this->tanggal,
                'qty' => $this->qty,
                'berat' => $this->berat,
                'harga' => $this->harga,
                'sub_total' => $this->qty * $this->harga,
                'partner_id' => $this->supplierSelect,
                'kandang_id' => $this->selectedKandang,
                'farm_id' => $farmId,
                'pic' => $this->pic,
                'standar_bobot_id' => $this->standarDocSelect,
            ];

            app(LivestockPurchaseService::class)->updateDOC($data, $this->livestockId);

            $this->dispatch('success', 'Data pembelian DOC berhasil diperbarui');
            $this->reset();
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            $this->dispatch('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    // public function updateDOC()
    // {
    //     $this->validate();

    //     $validatedData = $this->validate();
    //     $validatedData['rekanan_id'] = $this->supplierSelect;
    //     $validatedData['kandang_id'] = $this->selectedKandang;

    //     // dd($this->transaksi_id);
    //     // dd($validatedData);

    //     try {
    //         DB::beginTransaction();

    //         $ternak = Livestock::where('id',$this->livestockId)->first();
    //         $purchase = TransaksiBeli::with(['transaksiDetails', 'kelompokTernak'])->findOrFail($ternak->transaksi_id);
    //         $kandang = Kandang::where('id', $this->selectedKandang)->first();
    //         $farm = Farm::where('id', $kandang->farm_id)->first();
    //         $validatedData['farm_id'] = $farm->id;

    //         // Check if quantity has changed and if there are any TransaksiHarian records
    //         if ($purchase->total_qty != $validatedData['qty']) {
    //             $hasTransaksiHarian = TransaksiHarian::where('kelompok_ternak_id', $purchase->kelompok_ternak_id)->exists();
    //             if ($hasTransaksiHarian) {
    //                 throw new \Exception('Tidak dapat mengubah jumlah DOC karena sudah ada transaksi.');
    //             }
    //         }

    //         // Update TransaksiBeli
    //         $purchase->update([
    //             'tanggal' => $validatedData['tanggal'],
    //             'faktur' => $validatedData['faktur'],
    //             'rekanan_id' => $validatedData['rekanan_id'],
    //             'farm_id' => $validatedData['farm_id'],
    //             'kandang_id' => $validatedData['kandang_id'],
    //             'total_qty' => $validatedData['qty'],
    //             'total_berat' => $validatedData['qty'] * $validatedData['berat'],
    //             'harga' => $validatedData['harga'],
    //             'sub_total' => $validatedData['qty'] * $validatedData['harga'],
    //             'pic' => $validatedData['pic'],
    //             'updated_by' => auth()->id(),
    //         ]);

    //         // Update TransaksiBeliDetail
    //         $purchase->transaksiDetails->first()->update([
    //             'tanggal' => $validatedData['tanggal'],
    //             'qty' => $validatedData['qty'],
    //             'berat' => $validatedData['qty'] * $validatedData['berat'],
    //             'harga' => $validatedData['harga'],
    //             'sub_total' => $validatedData['qty'] * $validatedData['harga'],
    //             'sisa' => $validatedData['qty'],
    //             'updated_by' => auth()->id(),
    //         ]);


    //         $data = [
    //             'ternak_id'=> $purchase->kelompok_ternak_id,
    //             'standar_bobot_id'=> $validatedData['standarDocSelect'],
    //         ];

    //         // Get the kelompok ternak instance
    //         $kelompokTernak = Ternak::find($purchase->kelompok_ternak_id);

    //         // Check if standar_bobot_id has changed
    //         if ($kelompokTernak && $kelompokTernak->standar_bobot_id != $validatedData['standarDocSelect']) {
    //             app(TernakService::class)->updateStandarTernak($data);
    //         }

    //         // Update Ternak
    //         $purchase->kelompokTernak->update([
    //             'start_date' => $validatedData['tanggal'],
    //             'populasi_awal' => $validatedData['qty'],
    //             'berat_awal' => $validatedData['berat'],
    //             'harga' => $validatedData['harga'],
    //             'standar_bobot_id' => $validatedData['standarDocSelect'],
    //             'pic' => $validatedData['pic'],
    //             'updated_by' => auth()->id(),
    //         ]);

    //         // Update TransaksiTernak
    //         TransaksiTernak::where('kelompok_ternak_id', $purchase->kelompok_ternak_id)->update([
    //             'tanggal' => $validatedData['tanggal'],
    //             'farm_id' => $validatedData['farm_id'],
    //             'kandang_id' => $validatedData['kandang_id'],
    //             'quantity' => $validatedData['qty'],
    //             'berat_total' => $validatedData['qty'] * 0.1,
    //             'berat_rata' => 0.1,
    //             'harga_satuan' => $validatedData['harga'],
    //             'total_harga' => $validatedData['qty'] * $validatedData['harga'],
    //             'updated_by' => auth()->id(),
    //         ]);

    //         // Update CurrentTernak
    //         CurrentTernak::where('kelompok_ternak_id', $purchase->kelompok_ternak_id)->update([
    //             'farm_id' => $validatedData['farm_id'],
    //             'kandang_id' => $validatedData['kandang_id'],
    //             'quantity' => $validatedData['qty'],
    //             'berat_total' => $validatedData['qty'] * 0.1,
    //             'avg_berat' => 0.1,
    //             'updated_by' => auth()->id(),
    //         ]);

    //         // Update Kandang
    //         Kandang::find($validatedData['kandang_id'])->update([
    //             'jumlah' => $validatedData['qty'],
    //             'berat' => $validatedData['qty'] * 0.1,
    //             'kelompok_ternak_id' => $purchase->kelompok_ternak_id,
    //             'status' => 'Digunakan',
    //             'updated_by' => auth()->id(),
    //         ]);

    //         DB::commit();

    //         $this->dispatch('success', 'Data pembelian DOC berhasil diperbarui');
    //         $this->reset();
    //     } catch (ValidationException $e) {
    //         $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
    //         $this->setErrorBag($e->validator->errors());
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->dispatch('error', 'Terjadi kesalahan saat memperbarui data. ' . $e->getMessage());
    //     } finally {
    //         // $this->reset();
    //     }
    // }


    private function createDocPurchaseDetail($purchase, $docItem, $data)
    {
        return TransaksiBeliDetail::create([
            'transaksi_id' => $purchase->id,
            'jenis' => 'Pembelian',
            'jenis_barang' => 'DOC',
            'tanggal' => $purchase->tanggal,
            'item_id' => $docItem->id,
            'item_name' => $docItem->name,
            'qty' => $purchase->total_qty,
            'berat' => $purchase->total_qty * 0.1,
            'harga' => $this->harga,
            'sub_total' => $purchase->total_qty * $purchase->harga,
            'terpakai' =>  0,
            'sisa' => $purchase->total_qty,
            'satuan_besar' => $docItem->satuan_besar,
            'satuan_kecil' => $docItem->satuan_kecil,
            'konversi' => $docItem->konversi,
            'status' => 'Aktif',
            'created_by' => 3,
        ]);
    }

    private function createTernak($purchase, $farm, $kandang)
    {
        // dd($purchase->total_qty);
        $periodeFormat = 'PR-' . $farm->kode . '-' . $kandang->kode . '-' . Carbon::parse($purchase->tanggal)->format('dmY');
        $periode = $this->periode ?? $periodeFormat;
        return Ternak::create([
            'transaksi_id' => $purchase->id,
            'farm_id' => $farm->id,
            'kandang_id' => $kandang->id,
            'name' => $periode,
            'breed' => $this->docSelect,
            'start_date' => $purchase->tanggal,
            'populasi_awal' => $purchase->total_qty,
            'berat_awal' => $purchase->total_berat,
            'harga' => $this->harga,
            'pic' => $this->pic,
            'status' => 'Aktif',
            'keterangan' => 'DOC Purchase',
            'created_by' => auth()->id()
        ]);
    }

    private function createTransaksiTernak($kelompokTernak, $purchase, $farm, $kandang)
    {
        return TransaksiTernak::create([
            'kelompok_ternak_id' => $kelompokTernak->id,
            'jenis_transaksi' => 'Pembelian',
            'tanggal' => $purchase->tanggal,
            'farm_id' => $farm->id,
            'kandang_id' => $kandang->id,
            'quantity' => $purchase->total_qty,
            'berat_total' => $purchase->total_berat,
            'berat_rata' => $purchase->total_berat / $purchase->total_qty,
            'harga_satuan' => $purchase->harga,
            'total_harga' => $purchase->sub_total,
            'status' => 'Aktif',
            'keterangan' => 'Pembelian DOC Batch ' . $kelompokTernak->name,
            'created_by' => auth()->id()
        ]);
    }

    private function createCurrentTernak($kelompokTernak, $farm, $kandang, $data)
    {

        $tanggalMasuk = Carbon::parse($kelompokTernak->start_date);
        $HariIni = Carbon::now();
        $umur = $tanggalMasuk->diffInDays($HariIni);

        // dd($kelompokTernak->id);
        return CurrentTernak::create([
            'kelompok_ternak_id' => $kelompokTernak->id,
            'farm_id' => $farm->id,
            'kandang_id' => $kandang->id,
            'quantity' => $data['total_qty'],
            'berat_total' => $data['total_berat'],
            'avg_berat' => $data['total_berat'] / $data['total_qty'],
            'umur' => $umur,
            'status' => 'Aktif',
            'created_by' => auth()->id()
        ]);
    }

    private function updateKandangStatus($kandang, $data, $kelompokTernak)
    {
        $kandang->update([
            'jumlah' => $data['total_qty'],
            'berat' => $data['total_berat'],
            'kelompok_ternak_id' => $kelompokTernak->id,
            'status' => 'Digunakan',
            'updated_by' => auth()->id()
        ]);
    }
}
