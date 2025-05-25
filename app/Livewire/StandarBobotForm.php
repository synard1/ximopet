<?php

namespace App\Livewire;

use Livewire\Component;
// use App\Models\StandarBobot;
use App\Models\LivestockStrainStandard as StandarBobot;

class StandarBobotForm extends Component
{
    public $standards = [];
    public $breed = '';
    public $keterangan = '';
    public $isEditing = false;
    public $standarBobotId;

    protected $rules = [
        'breed' => 'required|string',
        'keterangan' => 'nullable|string',
        'standards.*.umur' => 'required|numeric|min:0',
        'standards.*.standar_data.bobot.min' => 'required|numeric|min:0',
        'standards.*.standar_data.bobot.max' => 'required|numeric|min:0',
        'standards.*.standar_data.bobot.target' => 'required|numeric|min:0',
        'standards.*.standar_data.feed_intake.min' => 'required|numeric|min:0',
        'standards.*.standar_data.feed_intake.max' => 'required|numeric|min:0',
        'standards.*.standar_data.feed_intake.target' => 'required|numeric|min:0',
        'standards.*.standar_data.fcr.min' => 'required|numeric|min:0',
        'standards.*.standar_data.fcr.max' => 'required|numeric|min:0',
        'standards.*.standar_data.fcr.target' => 'required|numeric|min:0',
    ];

    protected $listeners = [
        'editStandarBobot' => 'loadStandarBobot',
        'removeStandard' => 'removeStandard',
        'resetInputBobot' => 'resetInputBobot',
    ];

    public function mount($standarBobotId = null)
    {
        if ($standarBobotId) {
            $this->standarBobotId = $standarBobotId;
            $this->isEditing = true;
            $this->loadStandarBobot($standarBobotId);
        }
    }

    public function loadStandarBobot($standarBobotId)
    {
        $standarBobot = StandarBobot::findOrFail($standarBobotId);
        $this->breed = $standarBobot->breed;
        $this->keterangan = $standarBobot->keterangan;
        $this->standarBobotId = $standarBobot->id;

        // Ensure standar_data is an associative array with umur as keys
        if (is_array($standarBobot->standar_data)) {
            // Convert the existing standar_data to the expected format
            foreach ($standarBobot->standar_data as $umur => $data) {
                $this->standards[] = [
                    'umur' => $umur,
                    'standar_data' => $data
                ];
            }
        } else {
            $this->standards = []; // Initialize as empty if not an array
        }

        $this->isEditing = true;
        $this->dispatch('standarBobotEdit');
    }


    // public function loadStandarBobot($standarBobotId)
    // {
    //     $standarBobot = StandarBobot::findOrFail($standarBobotId);
    //     $this->breed = $standarBobot->breed;
    //     $this->keterangan = $standarBobot->keterangan;

    //     // Ensure standar_data is an associative array with umur as keys
    //     if (is_array($standarBobot->standar_data)) {
    //         $this->standards = $standarBobot->standar_data;
    //     } else {
    //         $this->standards = []; // Initialize as empty if not an array
    //     }

    //     $this->isEditing = true;
    //     $this->dispatch('standarBobotEdit');
    // }

    // public function loadStandarBobot()
    // {
    //     $standarBobot = StandarBobot::findOrFail($this->standarBobotId);
    //     $this->breed = $standarBobot->breed;
    //     $this->keterangan = $standarBobot->keterangan;
    //     // Load existing standards if any
    //     if ($standarBobot->standar_data) {
    //         $this->standards = [$standarBobot->standar_data];
    //     }
    // }

    public function createStandarBobot()
    {
        $this->validate([
            'breed' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        $standarBobot = StandarBobot::create([
            'breed' => $this->breed,
            'keterangan' => $this->keterangan,
            'standar_data' => [], // Initialize as empty array
            'status' => 'Aktif'
            // 'standar_data' => StandarBobot::$defaultStandarFormat
        ]);

        $this->isEditing = true;
        $this->standarBobotId = $standarBobot->id;
        $this->addStandard();

        session()->flash('message', 'Standar Bobot created successfully.');
        $this->dispatch('standarBobotCreated');
    }

    // public function addStandard()
    // {
    //     $this->standards[] = [
    //         'umur' => '',
    //         'standar_data' => StandarBobot::$defaultStandarFormat
    //     ];
    // }

    // public function removeStandard($index)
    // {
    //     unset($this->standards[$index]);
    //     $this->standards = array_values($this->standards);
    // }

    public function removeStandard($index)
    {
        // Get the current StandarBobot
        $standarBobot = StandarBobot::findOrFail($this->standarBobotId);

        // Get the current standar_data
        $currentStandarData = $standarBobot->standar_data;

        // Get the umur of the standard to be removed
        $umur = (int)$this->standards[$index]['umur'];

        // Remove the standard from the standar_data
        if (isset($currentStandarData[$umur])) {
            unset($currentStandarData[$umur]); // Remove the entry
            $standarBobot->standar_data = $currentStandarData; // Assign the modified data back
            $standarBobot->save(); // Save changes to the database
        }

        // Remove from the standards array
        unset($this->standards[$index]);
        $this->standards = array_values($this->standards); // Re-index the array

        session()->flash('message', 'Standard removed successfully.');
        $this->dispatch('success', 'Standard removed successfully');
    }

    public function saveStandard($index)
    {
        // dd($this->all());
        // Validate the standard data
        $this->validate([
            "standards.{$index}.umur" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.bobot.min" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.bobot.max" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.bobot.target" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.feed_intake.min" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.feed_intake.max" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.feed_intake.target" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.fcr.min" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.fcr.max" => 'required|numeric|min:0',
            "standards.{$index}.standar_data.fcr.target" => 'required|numeric|min:0',
        ]);


        // Ensure umur is set correctly, allowing 0 as a valid value
        $umur = (int)$this->standards[$index]['umur']; // Cast to integer
        if (!isset($umur)) { // Check if umur is not set
            session()->flash("message-{$index}", 'Umur cannot be empty.');
            return;
        }

        // Retrieve the existing StandarBobot
        $standarBobot = StandarBobot::findOrFail($this->standarBobotId);

        // Get the current standar_data
        $currentStandarData = $standarBobot->standar_data;

        // Prepare the standard data for saving
        $standardData = [
            'umur' => $umur, // Ensure umur is an integer
            'bobot' => [
                'min' => (float)$this->standards[$index]['standar_data']['bobot']['min'], // Cast to float
                'max' => (float)$this->standards[$index]['standar_data']['bobot']['max'], // Cast to float
                'target' => (float)$this->standards[$index]['standar_data']['bobot']['target'], // Cast to float
            ],
            'feed_intake' => [
                'min' => (float)$this->standards[$index]['standar_data']['feed_intake']['min'], // Cast to float
                'max' => (float)$this->standards[$index]['standar_data']['feed_intake']['max'], // Cast to float
                'target' => (float)$this->standards[$index]['standar_data']['feed_intake']['target'], // Cast to float
            ],
            'fcr' => [
                'min' => (float)$this->standards[$index]['standar_data']['fcr']['min'], // Cast to float
                'max' => (float)$this->standards[$index]['standar_data']['fcr']['max'], // Cast to float
                'target' => (float)$this->standards[$index]['standar_data']['fcr']['target'], // Cast to float
            ],
        ];

        // Check if the umur already exists in the current data
        if (isset($currentStandarData[$umur])) {
            // Update the existing entry
            $currentStandarData[$umur] = $standardData;
        } else {
            // Add a new entry
            $currentStandarData[$umur] = $standardData;
        }

        // Assign the modified data back to the model
        $standarBobot->standar_data = $currentStandarData;

        // Save the model
        $standarBobot->save();

        session()->flash("message-{$index}", 'Standard saved successfully.');
        // $this->dispatch('success', 'Data berhasil disimpan');

    }

    public function addStandard()
    {
        $this->standards[] = [
            'umur' => '', // Initialize as empty, will be set by user input
            'standar_data' => StandarBobot::$defaultStandarFormat
        ];
    }

    // public function saveStandard($index)
    // {
    //     $this->validate([
    //         "standards.{$index}.umur" => $this->rules['standards.*.umur'],
    //         "standards.{$index}.standar_data.bobot.min" => $this->rules['standards.*.standar_data.bobot.min'],
    //         "standards.{$index}.standar_data.bobot.max" => $this->rules['standards.*.standar_data.bobot.max'],
    //         "standards.{$index}.standar_data.bobot.target" => $this->rules['standards.*.standar_data.bobot.target'],
    //         "standards.{$index}.standar_data.feed_intake.min" => $this->rules['standards.*.standar_data.feed_intake.min'],
    //         "standards.{$index}.standar_data.feed_intake.max" => $this->rules['standards.*.standar_data.feed_intake.max'],
    //         "standards.{$index}.standar_data.feed_intake.target" => $this->rules['standards.*.standar_data.feed_intake.target'],
    //         "standards.{$index}.standar_data.fcr.min" => $this->rules['standards.*.standar_data.fcr.min'],
    //         "standards.{$index}.standar_data.fcr.max" => $this->rules['standards.*.standar_data.fcr.max'],
    //         "standards.{$index}.standar_data.fcr.target" => $this->rules['standards.*.standar_data.fcr.target'],
    //     ]);

    //     StandarBobot::updateOrCreate(
    //         ['id' => $this->standarBobotId],
    //         [
    //             'breed' => $this->breed,
    //             'keterangan' => $this->keterangan,
    //             'standar_data' => $this->standards[$index]
    //         ]
    //     );

    //     session()->flash("message-{$index}", 'Standard saved successfully.');
    // }

    public function render()
    {
        return view('livewire.standar-bobot-form');
    }

    private function resetForm()
    {
        // Reset the standards array
        $this->standards = [];

        // Reset other properties if needed
        $this->breed = '';
        $this->keterangan = '';
    }

    public function resetInputBobot()
    {
        // Reset the form fields after successful save
        $this->resetForm();
    }

    // public function viewStandarBobot($standarBobotId)
    // {
    //     // $standarBobot = StandarBobot::findOrFail($standarBobotId);

    //     // // Check if the data is valid
    //     // if (!$standarBobot) {
    //     //     session()->flash('error', 'Standar Bobot not found.');
    //     //     return;
    //     // }

    //     // // Prepare the data for display
    //     $this->breed = '$standarBobot->breed';
    //     // $this->keterangan = $standarBobot->keterangan;
    //     // $this->standarBobotId = $standarBobot->id;

    //     // // Ensure standar_data is an associative array with umur as keys
    //     // if (is_array($standarBobot->standar_data)) {
    //     //     $this->standards = []; // Clear existing standards
    //     //     foreach ($standarBobot->standar_data as $umur => $data) {
    //     //         $this->standards[] = [
    //     //             'umur' => $umur,
    //     //             'standar_data' => $data
    //     //         ];
    //     //     }
    //     // } else {
    //     //     $this->standards = []; // Initialize as empty if not an array
    //     // }

    //     // Dispatch an event to show the detail modal or view
    //     $this->dispatch('showDetailModal');
    //     // $this->dispatch('success', 'Modal Open');

    // }
}
