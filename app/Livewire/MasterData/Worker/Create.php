<?php

namespace App\Livewire\MasterData\Worker;

use App\Models\Worker;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Create extends Component
{
    public $showForm = false;
    public $edit_mode = false;
    public $workerId;
    public $name;
    public $address;
    public $phone;
    public $status = 'active'; // Set default status

    protected $listeners = [
        'showEditForm' => 'showEditForm',
        'showCreateForm' => 'showCreateForm',
        'cancel' => 'cancel',
        'edit' => 'edit',
        'delete_worker' => 'delete',
        
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'status' => 'required|in:active,inactive,on_leave,suspended,terminated,probation,part_time,full_time,contract,temporary,resigned,retired,deceased,blacklist',
    ];

    public function showCreateForm()
    {
        if (!Auth::user()->can('create worker master data')) {
            $this->addError('create', 'You do not have permission to create worker master data.');
            return;
        }

        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function showEditForm($id)
    {
        if (!Auth::user()->can('update worker master data')) {
            $this->addError('edit', 'You do not have permission to edit worker master data.');
            return;
        }

        $worker = Worker::with('batchWorkers')->findOrFail($id);

        $this->workerId = $worker->id;
        $this->name = $worker->name;
        $this->address = $worker->address;
        $this->phone = $worker->phone;
        $this->status = $worker->status;

        $this->showForm = true;
        $this->edit_mode = true;
        $this->dispatch('hide-datatable');
    }

    public function resetForm()
    {
        $this->reset(['workerId', 'name', 'address', 'phone', 'status']);
        $this->resetErrorBag();
    }

    public function close()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('show-datatable');
    }

    public function save()
    {
        if (!Auth::user()->can('create worker master data') && !$this->edit_mode) {
            $this->addError('create', 'You do not have permission to create worker master data.');
            return;
        }

        if (!Auth::user()->can('update worker master data') && $this->edit_mode) {
            $this->addError('edit', 'You do not have permission to edit worker master data.');
            return;
        }

        $this->validate();

        DB::beginTransaction();
        try {
            if ($this->edit_mode) {
                $worker = Worker::findOrFail($this->workerId);
                $worker->update([
                    'name' => $this->name,
                    'address' => $this->address,
                    'phone' => $this->phone,
                    'status' => $this->status,
                    'updated_by' => auth()->id(), // Assuming you have authentication
                ]);
                $this->dispatch('success', 'Data pekerja berhasil diperbarui.');
                $this->dispatch('workerUpdated'); // Emit event for data table refresh
            } else {
                Worker::create([
                    'id' => Str::uuid(),
                    'name' => $this->name,
                    'address' => $this->address,
                    'phone' => $this->phone,
                    'status' => $this->status,
                    'created_by' => auth()->id(), // Assuming you have authentication
                ]);
                $this->dispatch('success', 'Data pekerja berhasil disimpan.');
                $this->dispatch('workerCreated'); // Emit event for data table refresh
            }

            DB::commit();
            $this->close();

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on any other exception

            $class = __CLASS__;
            $method = __FUNCTION__;
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $errorMessage = 'Terjadi kesalahan saat menyimpan data pekerja. Silakan coba lagi.';

            // Dispatch user-friendly error
            $this->dispatch('error', $errorMessage);

            // Log detailed error for debugging
            Log::error("[$class::$method] Error: $message | Line: $line | File: $file");

            // Optionally: log stack trace
            Log::debug("[$class::$method] Stack trace: " . $e->getTraceAsString());
        }
    }

    public function delete($id)
    {
        if (!Auth::user()->can('delete worker master data')) {
            $this->addError('delete', 'You do not have permission to delete worker master data.');
            return;
        }

        try {
            DB::beginTransaction();

            // Cek apakah pekerja masih aktif di batch worker
            $activeBatchWorkers = DB::table('batch_workers')
                ->where('worker_id', $id)
                ->whereNull('end_date')
                ->exists();

            if ($activeBatchWorkers) {
                throw new \Exception('Pekerja tidak dapat dihapus karena masih aktif di batch ternak.');
            }

            // Jika tidak ada batch worker aktif, hapus data pekerja
            Worker::find($id)->delete();
            
            DB::commit();
            $this->dispatch('success', 'Data pekerja berhasil dihapus.');
            $this->dispatch('workerDeleted'); // Emit event for data table refresh

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.master-data.worker.create');
    }
}
