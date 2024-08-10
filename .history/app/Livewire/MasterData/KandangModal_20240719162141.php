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
    public $kandangs, $farms, $kandang_id, $kode_kandang, $nama, $alamat, $telp, $pic, $telp_pic, $status = 'Aktif';
    public $isOpen = 0;


    protected $rules = [
        'kode_kandang' => 'required|unique:master_kandangs,kode',
        'nama' => 'required|string',
    ];

    public function mount()
    {
        $user = auth()->user();

        $this->locationName = Reported::distinct('location')
                                        ->where('user_cid',$user->cid)
                                        ->pluck('location')
                                        ->filter()
                                        ->toArray();
    }

    public function render()
    {
        $this->farms = Farm::where('status', 'Aktif')->get();
        return view('livewire.master-data.kandang-modal', ['farms' => $this->farms]);

    }

    public function storeKandang()
    {
        try {
            // Validate the form input data
            $this->validate(); 
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();
        
            // Prepare the data for creating/updating the kandang
            $data = [
                'kode' => $this->kode_kandang,
                'nama' => $this->nama,
                'status' => 'Aktif',
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
    }

    public function closeModalKandang()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}
