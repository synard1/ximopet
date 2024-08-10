<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\DB;

use App\Models\Rekanan;

class Supplier extends Component
{
    public $supplier_id, $kode_supplier, $name, $alamat, $telp, $pic, $telp_pic, $email, $status = 'Aktif';

    
    public $edit_mode = false;

    protected $rules = [
        'kode_supplier' => 'required|unique:master_rekanan,kode',
        'name' => 'required|string',
        'alamat' => 'string',
        'telp' => 'numeric',
        'pic' => 'string|max:255',
        'telp_pic' => 'numeric',
        'email' => 'required|email|unique:master_rekanan,email', // Add table and column for email uniqueness
    ];

    protected $listeners = [
        'delete_supplier' => 'deleteSupplier',
        'update_supplier' => 'updateSupplier',
        'new_supplier' => 'hydrate',
    ];

    // public function mount($supplierId = null)
    // {
    //     if ($supplierId) {
    //         $this->supplier_id = $supplierId;
    //         $supplier = Rekanan::findOrFail($supplierId);
    //         $this->fill($supplier->toArray());
    //     }
    // }

    public function mount($supplier_id = null)
    {
        
        if ($id) {
            $this->supplier_id = $id;
            $supplier = Rekanan::find($id);
            
            $this->kode_supplier = $supplier->kode;
            $this->name = $supplier->nama;
            $this->email = $supplier->email;
            $this->alamat = $supplier->alamat;
            $this->telp = $supplier->telp;
            $this->pic = $supplier->pic;
            $this->telp_pic = $supplier->telp_pic;
            $this->status = $supplier->status;
        }
    }

    public function submit()
    {

        $this->dispatch('error', 'mode edit ='. $this->supplier_id .' - - '. $this->edit_mode);

        if ($this->edit_mode) {
            $this->dispatch('error', 'mode edit ='. $this->supplier_id);

            // $this->dispatch('error', 'mode edit');
        }
        // try {
        //     // Validate the form input data
        //     // $this->validate(); 
        
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
        
        //     // Handle update logic (if applicable)
        //     if ($this->edit_mode) {
        //         $this->dispatch('error', 'Terjadi kesalahan saat update data. ');
        //         // Update the existing supplier
        //         $rekanan->update($data);
        //     } else {
        //         // Create a new supplier (already done above with Rekanan::create($data))
        //     }
        
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
    }

    

    public function deleteSupplier($id)
    {
        // Prevent deletion of current user
        // if ($id == Auth::id()) {
        //     $this->dispatch('error', 'User cannot be deleted');
        //     return;
        // }

        // Delete the user record with the specified ID
        Rekanan::destroy($id);

        // Emit a success event with a message
        $this->dispatch('success', 'Data berhasil dihapus');
    }

    public function render()
    {
        return view('livewire.master-data.supplier');
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->reset();
    }

    public function updateSupplier($id)
    {
        $this->supplier_id = $id;

        // $supplier = Rekanan::where('id', $id)->first();
        $supplier = Rekanan::find($id);

        if($supplier){
            // $this->supplier_id = $supplier->id;
            $this->name = $supplier->nama;
            $this->email = $supplier->email;
            $this->kode_supplier = $supplier->kode;
            $this->alamat = $supplier->alamat;
            $this->telp = $supplier->telp;
            $this->pic = $supplier->pic;
            $this->telp_pic = $supplier->telp_pic;
            $this->status = $supplier->status;
            $this->edit_mode = true;

            // Trigger a re-render of the component
            $this->render(); 
        }
    }

    public function update()
    {

    $data = [
        'id' => $this->supplier_id,
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

    dd($data);

        // Fetch the existing supplier record using supplier_id
        $supplier = Rekanan::find($this->supplier_id);

        if ($supplier) {
            $this->dispatch('success', 'ada data ');

            // Update the supplier record
            // $supplier->update([
            //     'kode' => $this->kode_supplier,
            //     'jenis' => "Supplier",
            //     'nama' => $this->name,
            //     'alamat' => $this->alamat,
            //     'telp' => $this->telp,
            //     'pic' => $this->pic,
            //     'telp_pic' => $this->telp_pic,
            //     'email' => $this->email,
            // ]);

            // ... other logic ...
        }else{
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data. ');
        }

        // try {
        //     // Validate the form input data
        //     // $this->validate(); 
        
        //     // Wrap database operation in a transaction (if applicable)
        //     DB::beginTransaction();
        
        //     // Prepare the data for creating/updating the supplier
            // $data = [
            //     'kode' => $this->kode_supplier,
            //     'jenis' => "Supplier",
            //     'nama' => $this->name,
            //     'alamat' => $this->alamat,
            //     'telp' => $this->telp,
            //     'pic' => $this->pic,
            //     'telp_pic' => $this->telp_pic,
            //     'email' => $this->email,
            // ];
        
        //     $rekanan = Rekanan::where('id', $this->supplier_id)->first() ?? Rekanan::create($data);
        
        //     // Handle update logic (if applicable)
        //     if ($this->edit_mode) {
        //         $this->dispatch('error', 'Terjadi kesalahan saat update data. ');
        //         // Update the existing supplier
        //         $rekanan->update($data);
        //     } else {
        //         // Create a new supplier (already done above with Rekanan::create($data))
        //     }
        
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
    }

    
}
