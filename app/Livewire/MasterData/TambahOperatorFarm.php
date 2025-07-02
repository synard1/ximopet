<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use App\Models\Farm;
use App\Models\FarmOperator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TambahOperatorFarm extends Component
{
    public $isOpen = 0;
    public $id, $farms, $nama_farm, $status;
    public $selectedFarm;
    public $selectedOperator;
    public $operators = [];

    protected $rules = [
        'selectedFarm' => 'required',
        'farms' => 'required',
        'selectedOperator' => 'required',
    ];

    public function mount()
    {
        // User hanya bisa melihat farm dengan company_id yang sama,
        // kecuali SuperAdmin bisa melihat semua farm


    }

    public function render()
    {
        // Check if user has permission to read operator assignment
        // if (!auth()->user()->can('read operator assignment')) {
        //     abort(403, 'Unauthorized action.');
        // }

        // dd(Auth::user()->company_id);
        if (Auth::user()->hasRole('SuperAdmin')) {
            $this->farms = Farm::where('status', 'active')->get();
        } else {
            $this->farms = Farm::where('company_id', Auth::user()->company_id)
                // ->where('status', 'active')
                ->get();
        }

        Log::info('User company_id', ['company_id' => Auth::user()->company_id]);
        Log::info('Farms found', $this->farms->toArray());

        $this->operators = User::role('Operator')
            ->where('company_id', Auth::user()->company_id)
            ->pluck('name', 'id');

        return view('livewire.master-data.tambah-operator-farm', [
            'farms' => $this->farms,
            'operators' => $this->operators,
        ]);
    }

    public function storeFarmOperator()
    {
        // Check if user has permission to create operator assignment
        if (!Auth::user()->can('create operator assignment')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $farm = Farm::where('id', $this->selectedFarm)->first();
            $user = User::where('id', $this->selectedOperator)->first();

            if (!$farm || !$user) {
                throw new \Exception('Farm or Operator not found');
            }

            // Check if operator is already assigned to this farm
            $existingAssignment = FarmOperator::where('farm_id', $this->selectedFarm)
                ->where('user_id', $this->selectedOperator)
                ->first();

            if ($existingAssignment) {
                throw new \Exception('Operator is already assigned to this farm');
            }

            $data = [
                'farm_id' => $this->selectedFarm,
                'user_id' => $user->id,
            ];

            $farmOperator = FarmOperator::create($data);

            DB::commit();

            $this->dispatch('success', __('Data Operator Berhasil Ditambahkan'));
            $this->reset(['selectedFarm', 'selectedOperator']);
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->dispatch('validation-errors', ['errors' => $e->validator->errors()->all()]);
            $this->setErrorBag($e->validator->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
        }
    }
}
