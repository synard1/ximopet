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
    public $id, $farms, $nama_farm, $status, $availableFarm;
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

        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Data berhasil ditambahkan');
            // $this->reset();
        } catch (\Throwable $th) {
            DB::rollBack();
    
            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
        }
    }
}
