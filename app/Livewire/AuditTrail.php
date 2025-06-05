<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DataAuditTrail;
use App\Models\User;

class AuditTrail extends Component
{
    use WithPagination;

    public $modelType = '';
    public $user = '';
    public $date = '';
    public $search = '';
    public $showDetailId = null;
    public $confirmRollbackId = null;

    public function updating($field)
    {
        $this->resetPage();
    }

    public function showDetail($id)
    {
        $this->showDetailId = $id;
    }

    public function hideDetail()
    {
        $this->showDetailId = null;
    }

    public function confirmRollback($id)
    {
        $this->confirmRollbackId = $id;
    }

    public function cancelRollback()
    {
        $this->confirmRollbackId = null;
    }

    public function rollback($id)
    {
        $audit = DataAuditTrail::findOrFail($id);
        $modelClass = $audit->model_type;
        $model = $modelClass::findOrFail($audit->model_id);
        $before = $audit->before_data;
        $after = $model->toArray();
        $model->fill($before);
        $model->save();
        // Simpan audit rollback
        DataAuditTrail::create([
            'model_type' => $audit->model_type,
            'model_id' => $audit->model_id,
            'action' => 'rollback',
            'before_data' => $after,
            'after_data' => $before,
            'user_id' => auth()->id(),
            'rollback_to_id' => $id,
        ]);
        $this->confirmRollbackId = null;
        session()->flash('success', 'Rollback berhasil!');
    }

    public function render()
    {
        $query = DataAuditTrail::query();
        if ($this->modelType) $query->where('model_type', 'like', "%{$this->modelType}%");
        if ($this->user) $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->user}%"));
        if ($this->date) $query->whereDate('created_at', $this->date);
        if ($this->search) $query->where(function ($q) {
            $q->where('model_id', 'like', "%{$this->search}%")
                ->orWhere('before_data', 'like', "%{$this->search}%")
                ->orWhere('after_data', 'like', "%{$this->search}%");
        });
        $auditTrails = $query->orderByDesc('created_at')->paginate(20);
        $users = User::pluck('name');
        return view('livewire.audit-trail', compact('auditTrails', 'users'));
    }
}
