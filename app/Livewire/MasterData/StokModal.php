<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Farm;
use App\Models\Stok;

class StokModal extends Component
{
    public $stoks, $stok_id, $kode_stok, $nama, $konversi, $satuan_besar, $satuan_kecil, $status = 'Aktif';
    public $isOpen = 0;
    public $jenis = ''; // Define the property for the selected value
    public $availableJenis = ['DOC', 'Pakan', 'Obat', 'Vaksin', 'Lainnya']; // List of available options

    protected $rules = [
        'kode_stok' => 'required|unique:master_stoks,kode',
        'jenis' => 'required',
        'nama' => 'required|string',
        'satuan_kecil' => 'required|string',
        'satuan_besar' => 'required|string',
        'konversi' => 'required|integer',
    ];

    public function mount()
    {

    }

    public function render()
    {
        return view('livewire.master-data.stok-modal', ['availableJenis' => $this->availableJenis
    ]);

    }

    public function storeStok()
    {
        try {
            // Validate the form input data
            $this->validate(); 
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();
        
            // Prepare the data for creating/updating the stok
            $data = [
                'jenis' => $this->jenis,
                'kode' => $this->kode_stok,
                'nama' => $this->nama,
                'satuan_besar' => $this->satuan_besar,
                'satuan_kecil' => $this->satuan_kecil,
                'konversi' => $this->konversi,
                'status' => 'Aktif',
                'user_id' => auth()->user()->id,
            ];
        
            $stok = Stok::where('id', $this->stok_id)->first() ?? Stok::create($data);
        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Stok '. $stok->nama .' berhasil ditambahkan');
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

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModalStok()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}