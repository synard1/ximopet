<?php

namespace App\Livewire\Transaksi;

use Livewire\Component;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Farm;
use App\Models\KelompokTernak;
use App\Models\Transaksi;
use App\Models\Rekanan;
use App\Models\KematianTernak as kTernak;


class KematianTernak extends Component
{
    public $tanggal, $farms, $jumlah, $selectedFarm, $selectedKandang, $total_berat, $penyebab;
    public $noFarmMessage, $keterangan = ''; // Add a property to store the message

    protected function rules()
    {
        $rules = [
            'selectedFarm' => 'required',
            'selectedKandang' => 'required',
            'total_berat' => 'required',
            'jumlah' => 'required',
            'penyebab' => 'required',
        ];

        return $rules;
    }

    protected $listeners = [
        'delete' => 'delete',
    ];

    public function mount()
    {
        // $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

        // // Fetch operators not associated with the selected farm
        // $farms = Farm::whereIn('id', $farmIds)->get(['id', 'nama']);

        // $result = ['farms' => $farms];

        // // $this->farms = Farm::whereIn('id', $farmIds)->get();
        // $this->farms = $result;

    }

    public function render()
    {
        $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

        // Fetch operators not associated with the selected farm
        $farms = Farm::whereIn('id', $farmIds)->get(['id', 'nama']);

        // $result = ['farms' => $farms];

        // $this->farms = Farm::whereIn('id', $farmIds)->get();
        $this->farms = $farms;

        return view('livewire.transaksi.kematian-ternak', ['farms' => $this->farms]);
    }

    public function store()
    {
        try {
            $this->validate(); 
            DB::beginTransaction();

            $ternak = KelompokTernak::where('farm_id', $this->selectedFarm)
            ->where('kandang_id', $this->selectedKandang)->first();

            // Prepare the data for creating/updating
            $data = [
                'kelompok_ternak_id' => $ternak->id,
                'tanggal' => $this->tanggal,
                'farm_id' => $this->selectedFarm,
                'kandang_id' => $this->selectedKandang,
                'quantity' => $this->jumlah,
                'total_berat' => $this->total_berat,
                'penyebab' => $this->penyebab,
                'keterangan' => $this->keterangan ?? null,
                'created_by' => auth()->user()->id,
            ];

            $kTernak = $ternak->kematianTernak()->create($data);

            // Update the total quantity in the kelompok_ternak table
            $ternak->jumlah_mati += $kTernak->quantity;
            $ternak->stok_akhir -= $kTernak->quantity;
            $ternak->save();

            DB::commit();
            $this->dispatch('success', 'Data Kematian Ternak berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat menyimpan data.'. $e);

        }

        // $this->reset();

    }
    public function delete($kematianTernakId)
    {
        try {
            DB::beginTransaction();

            $kematianTernak = kTernak::findOrFail($kematianTernakId);
            $ternak = $kematianTernak->kelompokTernaks;

            // Reverse the changes in kelompok_ternak table
            $ternak->jumlah_mati -= $kematianTernak->quantity;
            $ternak->stok_akhir += $kematianTernak->quantity;
            $ternak->save();

            // Delete the kematian ternak record
            $kematianTernak->delete();

            DB::commit();
            $this->dispatch('success', 'Data Kematian Ternak berhasil dibatalkan');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan saat membatalkan data: ' . $e->getMessage());
        }
    }
}