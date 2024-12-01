<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Farm;
use App\Models\Kandang;

class KandangModal extends Component
{
    public $kandangs, $farms, $selectedFarm, $kandang_id, $kode_kandang, $nama, $kapasitas, $status = 'Aktif';
    public $isOpen = 0;
    public $dynamicNumber, $currentUrl, $referer;

    public $noFarmMessage = ''; // Add a property to store the message

    protected $rules = [
        'kode_kandang' => 'required|regex:/^[A-Za-z0-9@#%&*_-]+$/',
        'nama' => 'required|string',
        'kapasitas' => 'required|numeric',
        'selectedFarm' => 'required',
    ];

    public function mount()
    {
        $this->farms = Farm::where('status', 'Aktif')->get();

        // Check if there are any active farms
        if ($this->farms->isEmpty()) {
            $this->noFarmMessage = 'Belum Ada Data Farm';
        }
    }

    public function render()
    {
        $this->farms = Farm::where('status', 'Aktif')->get();
        return view('livewire.master-data.kandang-modal', ['farms' => $this->farms,
        'noFarmMessage' => $this->noFarmMessage, // Pass the message to the view
        ]);
    }

    public function storeKandang()
    {
        try {
            // Validate the form input data
            $this->validate();

            // dd($this->selectedFarm);

            // Check if kode_kandang already exists for the selected farm
            $existingKandang = Kandang::where('farm_id', $this->selectedFarm)
                ->where('kode', $this->kode_kandang)
                // ->where('id', '!=', $this->kandang_id) // Exclude current kandang when editing
                ->first();

            if ($existingKandang) {
                throw ValidationException::withMessages([
                    'kode_kandang' => 'Kode kandang sudah digunakan di farm ini'
                ]);
            }
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();
        
            // Prepare the data for creating/updating the kandang
            $data = [
                'farm_id' => $this->selectedFarm,
                'kode' => $this->kode_kandang,
                'nama' => $this->nama,
                'kapasitas' => $this->kapasitas,
                'status' => 'Aktif',
                'user_id' => auth()->user()->id,
            ];
        
            $kandang = Kandang::where('id', $this->kandang_id)->first() ?? Kandang::create($data);
        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Kandang '. $kandang->nama .' berhasil ditambahkan');
        } catch (ValidationException $e) {
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
    
            // Handle validation and general errors
            // $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. '.$e->getMessage());

            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            // $this->reset();
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->dynamicNumber = rand(100, 999); // Generate random number

        $this->farms = Farm::where('status', 'Aktif')->get();
    }

    public function closeModalKandang()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}
