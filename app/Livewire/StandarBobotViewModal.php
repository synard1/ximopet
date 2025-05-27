<?php

namespace App\Livewire;

use Livewire\Component;
// use App\Models\StandarBobot;
use App\Models\LivestockStrainStandard as StandarBobot;


class StandarBobotViewModal extends Component
{
    public $standards = [];
    public $strain_name = '';
    public $description = '';
    public $index = '';
    public $keterangan = '';
    public $isEditing = false;
    public $standarBobotId;

    protected $listeners = [
        'viewStandarBobot' => 'viewStandarBobot',
        'confirmDelete' => 'confirmDelete',
        'deleteStandard' => 'deleteStandard',
    ];

    public function mount($standarBobotId = null)
    {
        // dd($this->all());
        // $this->breed = $standarBobotId;
        // if ($standarBobotId) {
        //     $this->standarBobotId = $standarBobotId;
        //     $this->isEditing = true;
        //     $this->loadStandarBobot($standarBobotId);
        // }
    }

    public function render()
    {
        // $standarBobot = StandarBobot::findOrFail($standarBobotId);

        // Check if the data is valid
        // if (!$standarBobot) {
        //     session()->flash('error', 'Standar Bobot not found.');
        //     return;
        // }

        // // Prepare the data for display
        // $this->breed = '$standarBobot->breed';
        // $this->keterangan = $standarBobot->keterangan;
        // $this->standarBobotId = $standarBobot->id;

        // // Ensure standar_data is an associative array with umur as keys
        // if (is_array($standarBobot->standar_data)) {
        //     $this->standards = []; // Clear existing standards
        //     foreach ($standarBobot->standar_data as $umur => $data) {
        //         $this->standards[] = [
        //             'umur' => $umur,
        //             'standar_data' => $data
        //         ];
        //     }
        // } else {
        //     $this->standards = []; // Initialize as empty if not an array
        // }
        // return view('livewire.standar-bobot-view-modal');
        return view('livewire.standar-bobot-view-modal', [
            'strain_name' => $this->strain_name,
            'description' => $this->description
        ]);
    }

    public function viewStandarBobot($standarBobotId)
    {
        $standarBobot = StandarBobot::findOrFail($standarBobotId);

        // Prepare the data for display
        $this->strain_name = $standarBobot->livestock_strain_name;
        $this->description = $standarBobot->description;
        $this->standarBobotId = $standarBobot->id;

        // Ensure standar_data is an associative array with umur as keys
        if (is_array($standarBobot->standar_data)) {
            $this->standards = []; // Clear existing standards
            foreach ($standarBobot->standar_data as $umur => $data) {
                $this->standards[] = [
                    'umur' => $umur,
                    'standar_data' => $data
                ];
            }
        } else {
            $this->standards = []; // Initialize as empty if not an array
        }

        // Dispatch an event to show the detail modal
        $this->dispatch('showDetailModal');
    }

    public function confirmDelete($index)
    {
        // dd($index);   
        $this->dispatch('confirmDelete', $index);
        // $this->dispatch('success', 'Data berhasil disimpan');

    }

    public function deleteStandard($umur)
    {
        // Find the index of the standard to delete
        $index = array_search($umur, array_column($this->standards, 'umur'));

        if ($index !== false) {
            // Remove the standard from the array
            unset($this->standards[$index]);
            $this->standards = array_values($this->standards); // Re-index the array

            // Optionally, you can also save the changes to the database here
            // StandarBobot::find($id)->delete(); // Example for deleting from the database

            session()->flash('message-data', 'Standard deleted successfully.');
            $this->dispatch('refreshDataTable'); // Dispatch an event to refresh the DataTable
        }
    }
}
