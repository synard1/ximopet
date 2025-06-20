<?php

namespace App\Livewire\MasterData;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Farm;
use App\Models\Kandang;
use Ramsey\Uuid\Uuid; // Import the UUID library
use Illuminate\Support\Facades\Auth;


class KandangList extends Component
{
    public $kandangs, $farms, $selectedFarm, $kode, $nama, $kapasitas, $status;

    public $coop_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;
    public $noFarmMessage = ''; // Add a property to store the message

    // protected $listeners = ['edit','create']; // Add the listener

    protected $listeners = [
        'delete_kandang' => 'deleteKandangList',
        'editKandang' => 'editKandang',
        'create_kandang' => 'create_kandang',
    ];

    public function mount()
    {
        if (!Auth::user()->can('read kandang management')) {
            $this->dispatch('error', 'You do not have permission to view kandang management.');
            return;
        }

        $this->farms = Farm::where('status', 'Aktif')->get();
        // Check if there are any active farms
        if ($this->farms->isEmpty()) {
            $this->noFarmMessage = 'Belum Ada Data Farm';
        }
    }

    public function render()
    {
        if (!Auth::user()->can('read kandang management')) {
            return view('livewire.unauthorized');
        }

        $this->kandangs = Kandang::all();
        return view('livewire.master-data.kandang-list', [
            'kandangs' => $this->kandangs,
            'farms' => $this->farms,
        ]);
    }

    public function create_kandang()
    {
        if (!Auth::user()->can('create kandang management')) {
            $this->dispatch('error', 'You do not have permission to create kandang.');
            return;
        }

        $this->resetInputFields();
        // $this->kode = (string) Uuid::uuid4(); // Generate and cast to string
        $this->openModal();
    }

    public function store()
    {
        if ($this->coop_id) {
            if (!Auth::user()->can('update kandang management')) {
                $this->dispatch('error', 'You do not have permission to update kandang.');
                return;
            }
        } else {
            if (!Auth::user()->can('create kandang management')) {
                $this->dispatch('error', 'You do not have permission to create kandang.');
                return;
            }
        }

        $this->validate();

        $kandang = Kandang::find($this->coop_id);

        if ($kandang && $this->kapasitas < $kandang->jumlah) {
            $this->dispatch('error', __('Kapasitas tidak boleh kurang dari jumlah ternak saat ini'));
            return;
        }

        Kandang::updateOrCreate(['id' => $this->coop_id], [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kapasitas' => $this->kapasitas,
            'status' => $this->status,
            'created_by' => auth()->user()->id,
        ]);

        if ($this->coop_id) {
            $this->dispatch('success', __('Data Kandang Berhasil Diubah'));
        } else {
            $this->dispatch('success', __('Data Kandang Berhasil Dibuat'));
        }

        $this->closeModal();
        $this->resetInputFields();
    }

    public function editKandang($id)
    {
        if (!Auth::user()->can('update kandang management')) {
            $this->dispatch('error', 'You do not have permission to edit kandang.');
            return;
        }

        $kandang = Kandang::where('id', $id)->first();
        $this->coop_id = $id;
        $this->kode = $kandang->kode;
        $this->nama = $kandang->nama;
        $this->kapasitas = $kandang->kapasitas;
        $this->status = $kandang->status;



        $this->openModal();
    }

    public function deleteKandangList($id)
    {
        if (!Auth::user()->can('delete kandang management')) {
            $this->dispatch('error', 'You do not have permission to delete kandang.');
            return;
        }

        $kandang = Kandang::find($id);

        if (!$kandang) {
            $this->dispatch('error', 'Kandang tidak ditemukan');
            return;
        }

        if ($kandang->status !== 'Aktif') {
            $this->dispatch('error', 'Hanya kandang dengan status Aktif dan kosong yang dapat dihapus');
            return;
        }

        // Delete the kandang record with the specified ID
        $kandang->delete();

        // Emit a success event with a message
        $this->dispatch('success', 'Data berhasil dihapus');
    }

    public function openModal()
    {
        $this->isOpen = true;
        // $this->farms = Farm::where('status', 'Aktif')->get();

    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields()
    {
        // $this->kode = Str::uuid(); // Generate UUID for new kandangs
        $this->kode = ''; // Generate UUID for new kandangs
        $this->nama = '';
        $this->coop_id = '';
    }

    protected $rules = [
        'kode' => 'required', // Unique except on update
        'nama' => 'required',
    ];

    public function updatingKode()
    {
        if ($this->coop_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}
