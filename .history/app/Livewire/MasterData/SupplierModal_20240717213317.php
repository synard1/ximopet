<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Rekanan;

class SupplierModal extends Component
{
    public $suppliers,$supplier_id, $kode_supplier, $name, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';
    public $isOpen = 0;


    protected $rules = [
        'kode_supplier' => 'required|unique:master_rekanan,kode',
        'name' => 'required|string',
        'alamat' => 'string',
        'telp' => 'numeric',
        'pic' => 'string|max:255',
        'telp_pic' => 'numeric',
        'email' => 'required|email|unique:master_rekanan,email', // Add table and column for email uniqueness
    ];

    public function render()
    {
        return view('livewire.master-data.supplier-modal');
    }

    public function storeSupplier()
    {
        try {
            // Validate the form input data
            $this->validate(); 
        
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();
        
            // Prepare the data for creating/updating the supplier
            $data = [
                'kode' => $this->kode_supplier,
                'jenis' => "Supplier",
                'nama' => $this->name,
                'alamat' => $this->alamat,
                'telp' => $this->telp,
                'pic' => $this->pic,
                'telp_pic' => $this->telp_pic,
                'email' => $this->email,
                'status' => 'Aktif',
            ];
        
            $rekanan = Rekanan::where('id', $this->supplier_id)->first() ?? Rekanan::create($data);

            Log::info("Edit mode ID: " .  $this->supplier_id);
        
            // Handle update logic (if applicable)
            if ($this->edit_mode) {
                
                $this->dispatch('error', 'Terjadi kesalahan saat update data. ');
                // Update the existing supplier
                $rekanan->update($data);
            } else {
                // Create a new supplier (already done above with Rekanan::create($data))
            }
        
            DB::commit();
    
            // Emit success event if no errors occurred
            $this->dispatch('success', 'Supplier '. $rekanan->nama .' berhasil ditambahkan');
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
        
        // $this->validate();

        // // session()->flash('message', $this->nama ? 'Contact updated successfully.' : 'Contact created successfully.');
        
        // // Emit a success event with a message
        // $this->dispatch('success', __('Data Supplier Berhasil Dibuat'));

        // $this->closeModalSupplier();
        // $this->resetInputFields();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModalSupplier()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->nama = ''; // Generate UUID for new contacts
    }
}
