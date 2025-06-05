<?php

namespace App\Livewire\MasterData\Worker;

use App\Models\BatchWorker;
use App\Models\Livestock;
use App\Models\Worker;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AssignWorker extends Component
{
    public $showFormAssignWorker = false;
    public $editMode = false;
    public $batchWorkerId;
    public $livestockId = null;
    public $livestockName;
    public $livestockStartDate;
    public $workerId;
    public $startDate;
    public $endDate;
    public $role;
    public $notes;
    public $endDateToDelete;
    public $showEndDateModal = false;
    public $showDeleteOptionsModal = false;
    public $batchWorkerIdToDelete;

    public $livestocks = [];
    public $workers = [];
    public $workersData = [];

    public $indexToDelete = null;
    public $errorMessage = null;

    public $existingBatchWorkers = [];

    protected $listeners = [
        'showAssignWorkerForm' => 'showFormAssignWorker',
        'showEditAssignWorkerForm' => 'showEditAssignWorkerForm',
        'cancelAssignWorkerForm' => 'closeForm',
        'setLivestockId' => 'setLivestock',
        'closeFormWorkerAssign' => 'closeFormWorkerAssign',
    ];

    protected $rules = [
        'livestockId' => 'required|uuid|exists:livestocks,id',
        'workersData' => 'required|array|min:1',
        'workersData.*.worker_id' => 'required|uuid|exists:workers,id',
        'workersData.*.start_date' => 'required|date|after_or_equal:livestockStartDate',
        'workersData.*.endDate' => 'nullable|date|after_or_equal:workersData.*.start_date',
        'workersData.*.role' => 'nullable|string|max:255',
        'workersData.*.notes' => 'nullable|string',
    ];

    protected $messages = [
        'livestockId.required' => 'Livestock harus dipilih.',
        'livestockId.uuid' => 'ID Livestock tidak valid.',
        'livestockId.exists' => 'Livestock yang dipilih tidak valid.',
        'workersData.required' => 'Setidaknya harus ada satu pekerja.',
        'workersData.min' => 'Setidaknya harus ada satu pekerja.',
        'workersData.*.worker_id.required' => 'Pekerja harus dipilih.',
        'workersData.*.worker_id.uuid' => 'ID Pekerja tidak valid.',
        'workersData.*.worker_id.exists' => 'Pekerja yang dipilih tidak valid.',
        'workersData.*.start_date.required' => 'Tanggal Mulai harus diisi.',
        'workersData.*.start_date.date' => 'Format Tanggal Mulai tidak valid.',
        'workersData.*.start_date.after_or_equal' => 'Tanggal Mulai Kerja tidak boleh lebih kecil dari Tanggal Mulai Livestock.',
        'workersData.*.endDate.date' => 'Format Tanggal Berakhir tidak valid.',
        'workersData.*.endDate.after_or_equal' => 'Tanggal Berakhir harus setelah atau sama dengan Tanggal Mulai.',
        'workersData.*.role.max' => 'Peran tidak boleh lebih dari 255 karakter.',
        'endDateToDelete.required' => 'Tanggal Berakhir harus diisi untuk mengakhiri tugas pekerja.',
        'endDateToDelete.date' => 'Format Tanggal Berakhir tidak valid.',
        'endDateToDelete.after_or_equal' => 'Tanggal Berakhir harus setelah atau sama dengan tanggal mulai pekerja.',
    ];

    public function mount()
    {
        $this->workers = Worker::orderBy('name')->get();
        $this->resetForm();
    }

    public function showFormAssignWorker()
    {
        $this->resetForm();
        $this->showFormAssignWorker = true;
        $this->editMode = false;
        $this->dispatch('hide-datatable');
    }

    public function showEditMapForm($id)
    {
        $batchWorker = BatchWorker::findOrFail($id);
        $this->batchWorkerId = $batchWorker->id;
        $this->livestockId = $batchWorker->livestock_id;
        $this->workerId = $batchWorker->worker_id;
        $this->startDate = $batchWorker->start_date->format('Y-m-d');
        $this->endDate = $batchWorker->end_date ? $batchWorker->end_date->format('Y-m-d') : null;
        $this->role = $batchWorker->role;
        $this->notes = $batchWorker->notes;
        $this->showFormAssignWorker = true;
        $this->editMode = true;
        $this->dispatch('hide-datatable');
    }

    public function resetForm()
    {
        $this->reset([
            'batchWorkerId',
            // 'livestockId',
            // 'livestockName',
            // 'livestockStartDate',
            'workerId',
            'startDate',
            'endDate',
            'role',
            'notes',
            // 'workersData'
        ]);
        $this->resetErrorBag();
        // $this->workersData = [];
    }

    public function closeForm()
    {
        $this->resetForm();
        $this->showFormAssignWorker = false;
        $this->dispatch('show-datatable');
    }

    public function closeFormWorkerAssign()
    {
        $this->resetForm();
        $this->showFormAssignWorker = false;
        $this->dispatch('hide-worker-assign-form');
    }

    public function setLivestock($livestockId)
    {
        try {
            if (!$livestockId) {
                throw new \Exception('Livestock ID tidak ditemukan');
            }

            $livestock = Livestock::with('farm')->findOrFail($livestockId);

            $this->livestockId = $livestockId;
            $this->livestockName = $livestock->name;
            $this->livestockStartDate = $livestock->start_date->format('Y-m-d');

            // Ambil data batch worker yang sudah ada
            $batchWorkers = BatchWorker::where('livestock_id', $livestockId)->orderBy('start_date')->get();

            $this->showFormAssignWorker();

            $this->workersData = [];
            foreach ($batchWorkers as $bw) {
                $this->workersData[] = [
                    'worker_id' => $bw->worker_id,
                    'start_date' => $bw->start_date ? $bw->start_date->format('Y-m-d') : '',
                    'endDate' => $bw->end_date ? $bw->end_date->format('Y-m-d') : '',
                    'role' => $bw->role,
                    'notes' => $bw->notes,
                    'id' => $bw->id, // simpan id untuk proses edit/hapus
                ];
            }

            // Jika tidak ada data, tambahkan baris kosong
            if (count($this->workersData) === 0) {
                $this->addWorkerRow();
            }

            // $this->dispatch('success', 'Livestock berhasil dipilih');
            $this->showFormAssignWorker = true;
            $this->dispatch('show-worker-assign-form');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Livestock tidak ditemukan: ' . $e->getMessage());
            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage());
        }
    }

    public function hideWorkerAssignForm()
    {
        $this->showFormAssignWorker = false;
        $this->dispatch('hide-worker-assign-form');
    }

    public function addWorkerRow()
    {
        $this->workersData[] = [
            'worker_id' => '',
            'start_date' => '',
            'role' => '',
            'notes' => '',
        ];
    }

    // public function confirmDeleteWorker($index)
    // {
    //     if (!isset($this->workersData[$index])) {
    //         $this->errorMessage = "Data pekerja tidak ditemukan pada index {$index}.";
    //         $this->dispatch('error', $this->errorMessage);
    //         return;
    //     }

    //     $worker = $this->workersData[$index];
    //     // Jika end_date belum ada, wajib input end_date dulu (tampilkan modal)
    //     if (empty($worker['endDate'])) {
    //         $this->indexToDelete = $index;
    //         $this->showEndDateModal = true;
    //         return;
    //     }

    //     // Jika sudah ada end_date, update status menjadi inactive
    //     if (!empty($worker['id'])) {
    //         BatchWorker::where('id', $worker['id'])->update([
    //             'status' => 'inactive',
    //             'updated_by' => auth()->id(),
    //         ]);
    //         // Update di array agar reflect di UI
    //         $this->workersData[$index]['status'] = 'inactive';
    //     }
    //     // Refresh data agar sinkron
    //     $this->setLivestock($this->livestockId);
    // }

    public function confirmDeleteWorker($index)
    {
        if (!isset($this->workersData[$index])) {
            $this->errorMessage = "Data pekerja tidak ditemukan pada index {$index}.";
            $this->dispatch('error', $this->errorMessage);
            return;
        }

        $worker = $this->workersData[$index];
        $this->indexToDelete = $index;

        if (empty($worker['endDate'])) {
            // Belum ada end_date, wajib input end_date
            $this->showEndDateModal = true;
            return;
        }

        // Sudah ada end_date, tampilkan opsi: update end_date atau hapus permanen
        $this->showDeleteOptionsModal = true;
    }

    public function updateEndDateOption()
    {
        $this->showDeleteOptionsModal = false;
        $this->showEndDateModal = true;
    }

    public function deleteWorkerPermanent()
    {
        if (!Auth::user()->can('delete worker assignment')) {
            $this->dispatch('error', 'You do not have permission to delete worker assignments.');
            $this->showDeleteOptionsModal = false;
            $this->indexToDelete = null;
            return;
        }

        $index = $this->indexToDelete;
        if (isset($this->workersData[$index]) && !empty($this->workersData[$index]['id'])) {
            BatchWorker::where('id', $this->workersData[$index]['id'])->delete();
        }
        unset($this->workersData[$index]);
        $this->workersData = array_values($this->workersData);
        $this->showDeleteOptionsModal = false;
        $this->indexToDelete = null;
        $this->setLivestock($this->livestockId);
    }

    public function confirmEndWorker()
    {
        if (!Auth::user()->can('update worker assignment')) {
            $this->dispatch('error', 'You do not have permission to update worker assignments.');
            $this->closeEndDateModal();
            return;
        }

        try {
            $this->validate([
                'endDateToDelete' => 'required|date|after_or_equal:workersData.' . $this->indexToDelete . '.start_date',
            ]);

            $worker = $this->workersData[$this->indexToDelete] ?? null;
            if ($worker && !empty($worker['id'])) {
                $updateData = [
                    'end_date' => $this->endDateToDelete,
                    'status' => 'inactive',
                    'updated_by' => auth()->id(),
                ];

                // Preserve existing role and notes
                if (isset($worker['role'])) {
                    $updateData['role'] = $worker['role'];
                }
                if (isset($worker['notes'])) {
                    $updateData['notes'] = $worker['notes'];
                }

                BatchWorker::where('id', $worker['id'])->update($updateData);
                $this->workersData[$this->indexToDelete]['endDate'] = $this->endDateToDelete;
                $this->workersData[$this->indexToDelete]['status'] = 'inactive';
            }

            $this->closeEndDateModal();
            // Refresh data dari database agar sinkron
            $this->setLivestock($this->livestockId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('error', 'Validasi gagal: ' . collect($e->errors())->first()[0]);
        }
    }

    public function closeEndDateModal()
    {
        $this->showEndDateModal = false;
        $this->endDateToDelete = null;
        $this->indexToDelete = null;
    }

    public function save()
    {
        try {
            $this->validate();

            DB::beginTransaction();

            foreach ($this->workersData as $workerData) {
                if (empty($workerData['worker_id']) || empty($workerData['start_date'])) {
                    continue;
                }

                if (!empty($workerData['id'])) {
                    // Update
                    if (!Auth::user()->can('update worker assignment')) {
                        DB::rollBack();
                        $this->dispatch('error', 'You do not have permission to update worker assignments.');
                        return;
                    }

                    $updateData = [
                        'worker_id' => $workerData['worker_id'],
                        'start_date' => $workerData['start_date'],
                        'role' => $workerData['role'] ?? null,
                        'notes' => $workerData['notes'] ?? null,
                        'updated_by' => auth()->id(),
                    ];

                    // Only update end_date if it's being changed
                    if (isset($workerData['end_date']) && $workerData['end_date'] !== null) {
                        $updateData['end_date'] = $workerData['end_date'];
                    }

                    BatchWorker::where('id', $workerData['id'])->update($updateData);
                } else {
                    // Insert baru
                    if (!Auth::user()->can('create worker assignment')) {
                        DB::rollBack();
                        $this->dispatch('error', 'You do not have permission to create worker assignments.');
                        return;
                    }

                    BatchWorker::create([
                        'id' => Str::uuid(),
                        'livestock_id' => $this->livestockId,
                        'worker_id' => $workerData['worker_id'],
                        'start_date' => $workerData['start_date'],
                        'end_date' => $workerData['end_date'] ?? null,
                        'role' => $workerData['role'] ?? null,
                        'notes' => $workerData['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            $this->dispatch('success', 'Pemetaan pekerja ke ternak berhasil disimpan.');
            $this->dispatch('workerLivestockMappingCreated');
            $this->dispatch('hide-worker-assign-form');
            $this->closeForm();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('error', 'Validasi gagal: ' . collect($e->errors())->first()[0]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage());
            Log::debug("[" . __CLASS__ . "::" . __FUNCTION__ . "] Stack trace: " . $e->getTraceAsString());
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function render()
    {
        // Jika livestockId sudah ada, ambil semua batch worker untuk livestock tersebut
        if ($this->livestockId) {
            $this->existingBatchWorkers = BatchWorker::with('worker')
                ->where('livestock_id', $this->livestockId)
                ->orderBy('start_date')
                ->get();
        } else {
            $this->existingBatchWorkers = [];
        }
        return view('livewire.master-data.worker.assign-worker');
    }
}
