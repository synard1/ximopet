<?php

namespace App\Livewire\MasterData\LivestockStrain;

use Livewire\Component;
use App\Models\LivestockStrain;
use App\Models\LivestockStrainStandard;

class Standard extends Component
{
    public $standards = [];
    public $strainId;
    public $strain_id = '';
    public $strain_name = '';
    public $description = '';
    public $isEditing = false;
    public $strainStandardId;
    public $strains = [];

    protected $rules = [
        'strain_id' => 'required|exists:livestock_strains,id',
        'description' => 'nullable|string',
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
        'editStrainStandard' => 'loadStrainStandard',
        'removeStandard' => 'removeStandard',
        'resetInputBobot' => 'resetInputBobot',
        'delete_strain' => 'deleteStrain',
        'delete_standar_bobot' => 'deleteStandarBobot',
    ];

    public function mount($strainStandardId = null)
    {
        // Load all available strains for dropdown
        $this->strains = LivestockStrain::where('status', 'active')->get();

        if ($strainStandardId) {
            $this->strainStandardId = $strainStandardId;
            $this->isEditing = true;
            $this->loadStrainStandard($strainStandardId);
        }
    }

    public function loadStrainStandard($strainStandardId)
    {
        $strainStandard = LivestockStrainStandard::findOrFail($strainStandardId);
        $this->strain_id = $strainStandard->livestock_strain_id;
        $this->strain_name = $strainStandard->livestock_strain_name;
        $this->description = $strainStandard->description;
        $this->strainStandardId = $strainStandard->id;

        // Ensure standar_data is an associative array with umur as keys
        if (is_array($strainStandard->standar_data)) {
            // Convert the existing standar_data to the expected format
            foreach ($strainStandard->standar_data as $umur => $data) {
                $this->standards[] = [
                    'umur' => $umur,
                    'standar_data' => $data
                ];
            }
        } else {
            $this->standards = []; // Initialize as empty if not an array
        }

        $this->isEditing = true;
        $this->dispatch('strainStandardEdit');
        // dd($this->strainStandardId);
    }

    public function saveOrUpdateStrainStandard()
    {
        $this->validate([
            'strain_id' => 'required|exists:livestock_strains,id',
            'description' => 'nullable|string',
        ]);

        $standarData = [];
        foreach ($this->standards as $item) {
            $umur = $item['umur'];
            $standarData[$umur] = [
                'umur' => $umur,
                'bobot' => [
                    'min' => (float)($item['standar_data']['bobot']['min'] ?? 0),
                    'max' => (float)($item['standar_data']['bobot']['max'] ?? 0),
                    'target' => (float)($item['standar_data']['bobot']['target'] ?? 0),
                ],
                'feed_intake' => [
                    'min' => (float)($item['standar_data']['feed_intake']['min'] ?? 0),
                    'max' => (float)($item['standar_data']['feed_intake']['max'] ?? 0),
                    'target' => (float)($item['standar_data']['feed_intake']['target'] ?? 0),
                ],
                'fcr' => [
                    'min' => (float)($item['standar_data']['fcr']['min'] ?? 0),
                    'max' => (float)($item['standar_data']['fcr']['max'] ?? 0),
                    'target' => (float)($item['standar_data']['fcr']['target'] ?? 0),
                ],
            ];
        }

        if ($this->isEditing && $this->strainStandardId) {
            // Update
            $strainStandard = LivestockStrainStandard::findOrFail($this->strainStandardId);
            $strainStandard->update([
                'description' => $this->description,
                'standar_data' => $standarData,
            ]);
            session()->flash('message', 'Strain Standard updated successfully.');
            $this->dispatch('success', 'Strain Standard updated successfully.');
        } else {
            // Create
            $strain = LivestockStrain::findOrFail($this->strain_id);
            $strainStandard = LivestockStrainStandard::create([
                'livestock_strain_id' => $this->strain_id,
                'livestock_strain_name' => $strain->name,
                'description' => $this->description,
                'standar_data' => $standarData,
                'status' => 'active',
            ]);
            $this->isEditing = true;
            $this->strainId = $strain->id;
            $this->strainStandardId = $strainStandard->id;
            session()->flash('message', 'Strain Standard created successfully.');
            $this->dispatch('success', 'Strain Standard created successfully.');
        }
        $this->dispatch('strainStandardSaved');
    }

    public function removeStandard($index)
    {
        // Get the current StrainStandard
        $strainStandard = LivestockStrainStandard::findOrFail($this->strainStandardId);

        // Get the current standar_data
        $currentStandarData = $strainStandard->standar_data;

        // Get the umur of the standard to be removed
        $umur = (int)$this->standards[$index]['umur'];

        // Remove the standard from the standar_data
        if (isset($currentStandarData[$umur])) {
            unset($currentStandarData[$umur]); // Remove the entry
            $strainStandard->standar_data = $currentStandarData; // Assign the modified data back
            $strainStandard->save(); // Save changes to the database
        }

        // Remove from the standards array
        unset($this->standards[$index]);
        $this->standards = array_values($this->standards); // Re-index the array

        session()->flash('message', 'Standard removed successfully.');
        $this->dispatch('success', 'Standard removed successfully');
    }

    public function saveStandard($index)
    {
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

        // Retrieve the existing StrainStandard
        $strainStandard = LivestockStrainStandard::findOrFail($this->strainStandardId);

        // Get the current standar_data
        $currentStandarData = $strainStandard->standar_data;

        // Prepare the standard data for saving
        $standardData = [
            'umur' => $umur,
            'bobot' => [
                'min' => (float)$this->standards[$index]['standar_data']['bobot']['min'],
                'max' => (float)$this->standards[$index]['standar_data']['bobot']['max'],
                'target' => (float)$this->standards[$index]['standar_data']['bobot']['target'],
            ],
            'feed_intake' => [
                'min' => (float)$this->standards[$index]['standar_data']['feed_intake']['min'],
                'max' => (float)$this->standards[$index]['standar_data']['feed_intake']['max'],
                'target' => (float)$this->standards[$index]['standar_data']['feed_intake']['target'],
            ],
            'fcr' => [
                'min' => (float)$this->standards[$index]['standar_data']['fcr']['min'],
                'max' => (float)$this->standards[$index]['standar_data']['fcr']['max'],
                'target' => (float)$this->standards[$index]['standar_data']['fcr']['target'],
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
        $strainStandard->standar_data = $currentStandarData;

        // Save the model
        $strainStandard->save();

        session()->flash("message-{$index}", 'Standard saved successfully.');
    }

    public function addStandard()
    {
        $this->standards[] = [
            'umur' => '',
            'standar_data' => [
                'bobot' => ['min' => 0, 'max' => 0, 'target' => 0],
                'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
            ]
        ];
    }

    public function render()
    {
        return view('livewire.master-data.livestock-strain.standard');
    }

    private function resetForm()
    {
        $this->standards = [];
        $this->strain_id = '';
        $this->description = '';
    }

    public function resetInputBobot()
    {
        $this->resetForm();
    }

    public function deleteStrain($strainId)
    {
        $strain = LivestockStrain::findOrFail($strainId);
        $strain->delete();
        $this->dispatch('success', 'Strain deleted successfully');
    }

    public function deleteStandarBobot($standarBobotId)
    {
        $standarBobot = LivestockStrainStandard::findOrFail($standarBobotId);
        $standarBobot->delete();
        $this->dispatch('success', 'Standar Ayam deleted successfully');
    }
}
