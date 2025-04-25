<?php

namespace App\Livewire\Livestock\Mutation;

use Livewire\Component;
use App\Models\Livestock;
use App\Services\Livestock\LivestockMutationService;
use Illuminate\Validation\Rule;

class MutationForm extends Component
{
    public $date, $from_livestock_id, $to_livestock_id, $quantity, $weight;


    public $livestocks = [];

    protected $rules = [
        'date' => 'required|date',
        'from_livestock_id' => 'required|uuid|different:to_livestock_id',
        'to_livestock_id' => 'required|uuid|different:from_livestock_id',
        'quantity' => 'required|integer|min:1',
        'weight' => 'nullable|numeric|min:0',

    ];

    public function mount()
    {
        $this->date = now()->toDateString();
        $this->livestocks = Livestock::all();
    }

    public function save()
    {
        $validated = $this->validate();

        // dd($validated);

        // $mutationService->mutate($validated);
        app(LivestockMutationService::class)->mutate($validated);


        $this->reset(['from_livestock_id', 'to_livestock_id', 'quantity']);

        // session()->flash('success', 'Mutasi berhasil dicatat.');
        $this->dispatch('success', 'Mutasi berhasil disimpan.');
        $this->resetForm();
        $this->dispatch('closeForm');
    }

    public function resetForm()
    {
        $this->reset();
    }

    public function render()
    {
        return view('livewire.livestock.mutation.mutation-form');
    }
}

