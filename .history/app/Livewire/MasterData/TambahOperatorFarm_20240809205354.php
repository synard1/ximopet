<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class TambahOperatorFarm extends Component
{
    public $isOpen = 0;
    public $id, $farms, $nama_farm, $status;
    public $selectedFarm;
    public $selectedOperator;
    // Initialize $operators as an empty collection
    public $operators = [];

    protected $rules = [
        'selectedFarm' => 'required', // Unique except on update
        'farms' => 'required', // Unique except on update
        'selectedOperator' => 'required',
        'status' => 'required',
    ];

    public function mount()
    {
        // Get all available farms
        $this->availableFarm = Farm::where('status', 'Aktif')->get();

        $this->operators = User::role('Operator')->pluck('name', 'id');
    }

    public function render()
    {
        return view('livewire.master-data.tambah-operator-farm');
    }

    // public function updatedSelectedFarm()
    // {
    //     // Load operators that DON'T belong to the selected farm
    //     $this->operators = User::role('Operator') // Get users with 'Operator' role
    //         ->whereDoesntHave('farmOperators', function ($query) {
    //             $query->where('farm_id', $this->selectedFarm);
    //         })
    //         ->pluck('name', 'id');

    //     $this->emit('refresh-operator-select'); // Signal to frontend to re-render Choices.js select
    // }

    public function storeFarmOperator()
    {
        $this->validate(); 

        // $farm = Farm::where('id', $this->selectedFarm)->first();
        //     $user = User::where('id', $this->selectedOperator)->first();
        
        //     // Prepare the data for creating/updating the supplier
        //     $data = [
        //         'farm_id' => $this->selectedFarm,
        //         'nama_farm' => $farm->nama,
        //         'user_id' => $user->id,
        //         'nama_operator' => $user->name,
        //         'status' => $this->status,
        //     ];

        //     // dd($data);
        
        //     $farmOperator = FarmOperator::where('id', $this->id)->first() ?? FarmOperator::create($data);
        try {
        //     // Validate the form input data
            // $this->validate(); 
        
        //     // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            $farm = Farm::where('id', $this->selectedFarm)->first();
            $user = User::where('id', $this->selectedOperator)->first();
        
            // Prepare the data for creating/updating the supplier
            $data = [
                'farm_id' => $this->selectedFarm,
                'nama_farm' => $farm->nama,
                'user_id' => $user->id,
                'nama_operator' => $user->name,
                'status' => $this->status,
            ];

            // dd($data);
        
            $farmOperator = FarmOperator::where('id', $this->id)->first() ?? FarmOperator::create($data);

        //     // Log::info("Edit mode ID: " .  $this->supplier_id);
        
        //     // // Handle update logic (if applicable)
        //     // if ($this->edit_mode) {
                
        //     //     $this->dispatch('error', 'Terjadi kesalahan saat update data. ');
        //     //     // Update the existing supplier
        //     //     $rekanan->update($data);
        //     // } else {
        //     //     // Create a new supplier (already done above with Rekanan::create($data))
        //     // }
        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Data berhasil ditambahkan');
            $this->reset();
        } catch (\Throwable $th) {
            DB::rollBack();
    
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
            // Optionally log the error: Log::error($e->getMessage());
        } finally {
            // Reset the form in all cases to prepare for new data
            // $this->reset();
        }

        // if($farmOperator){
        //     $this->dispatch('success', __('Data Operator Berhasil Ditambahkan'));

        // }else{
        //         $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');

        // }

        //     } catch (\Throwable $th) {
        //         //throw $th;
        //         // Handle validation and general errors
        // }

        // $this->validate();

        // // session()->flash('message', $this->nama ? 'Contact updated successfully.' : 'Contact created successfully.');
        
        // // Emit a success event with a message

        // $this->closeModalSupplier();
        // $this->resetInputFields();
    }
}
