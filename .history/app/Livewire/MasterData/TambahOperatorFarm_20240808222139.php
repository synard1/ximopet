<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\User;

class TambahOperatorFarm extends Component
{
    public $isOpen = 0;
    public $farms, $nama_farm, $status;
    public $selectedFarm;
    public $selectedOperator;
    // Initialize $operators as an empty collection
    public $operators = [];

    protected $rules = [
        'farms' => 'required', // Unique except on update
        'nama_farm' => 'required',
        'nama_operator' => 'required',
        'status' => 'required',
    ];

    public function mount()
    {
        // Get all available farms
        $this->farms = Farm::where('status', 'Aktif')->get();

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
        // try {
        //     // Validate the form input data
        //     $this->validate(); 
        
        //     // Wrap database operation in a transaction (if applicable)
        //     DB::beginTransaction();
        
        //     // Prepare the data for creating/updating the supplier
        //     $data = [
        //         'kode' => $this->kode_supplier,
        //         'jenis' => "Supplier",
        //         'nama' => $this->name,
        //         'alamat' => $this->alamat,
        //         'telp' => $this->telp,
        //         'pic' => $this->pic,
        //         'telp_pic' => $this->telp_pic,
        //         'email' => $this->email,
        //         'status' => 'Aktif',
        //     ];
        
        //     $rekanan = Rekanan::where('id', $this->supplier_id)->first() ?? Rekanan::create($data);

        //     // Log::info("Edit mode ID: " .  $this->supplier_id);
        
        //     // // Handle update logic (if applicable)
        //     // if ($this->edit_mode) {
                
        //     //     $this->dispatch('error', 'Terjadi kesalahan saat update data. ');
        //     //     // Update the existing supplier
        //     //     $rekanan->update($data);
        //     // } else {
        //     //     // Create a new supplier (already done above with Rekanan::create($data))
        //     // }
        
        //     DB::commit();
    
        //     // Emit success event if no errors occurred
        //     $this->dispatch('success', 'Supplier '. $rekanan->nama .' berhasil ditambahkan');
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

        // $this->validate();

        // // session()->flash('message', $this->nama ? 'Contact updated successfully.' : 'Contact created successfully.');
        
        // // Emit a success event with a message
        $this->dispatch('success', __('Data Supplier Berhasil Dibuat'));

        // $this->closeModalSupplier();
        // $this->resetInputFields();
    }
}
