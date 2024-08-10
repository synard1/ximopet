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
    public $stoks, $farms, $selectedFarm, $stok_id, $kode_stok, $nama, $konversi, $satuan_besar, $satuan_kecil, $status = 'Aktif';
    public $isOpen = 0;
    public $dynamicNumber, $currentUrl, $referer;

    // public $rand = 0;

    public $noFarmMessage = ''; // Add a property to store the message


    protected $rules = [
        'kode_stok' => 'required|unique:master_stoks,kode',
        'nama' => 'required|string',
        'satuan_kecil' => 'required|string',
        'satuan_besar' => 'required|string',
        'konversi' => 'required|integer',
    ];

    public function mount()
    {
        $this->farms = Farm::where('status', 'Aktif')->get();
        // $this->rand = rand(1, 10000);

        // Check if there are any active farms
        if ($this->farms->isEmpty()) {
            $this->noFarmMessage = 'Belum Ada Data Farm';
        }
    }

    public function render()
    {
        $this->farms = Farm::where('status', 'Aktif')->get();
        return view('livewire.master-data.stok-modal', ['farms' => $this->farms,
        'noFarmMessage' => $this->noFarmMessage, // Pass the message to the view
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
                'farm_id' => $this->selectedFarm,
                'kode' => $this->kode_stok,
                'nama' => $this->nama,
                'satuan_besar' => $this->satuan_besar,
                'satuan_kecil' => $this->satuan_kecil,
                'konversi' => $this->nama,
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
            $this->reset();
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->dynamicNumber = rand(100, 999); // Generate random number

        $this->farms = Farm::where('status', 'Aktif')->get();

    }

    public function closeModalStok()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}
