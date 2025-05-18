<?php

namespace App\Livewire;

use App\Models\QaChecklist;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class QaChecklistForm extends Component
{
    use WithPagination;

    public $feature_name;
    public $feature_category;
    public $feature_subcategory;
    public $test_case;
    public $test_steps;
    public $expected_result;
    public $test_type;
    public $priority = 'Medium';
    public $status = 'Not Tested';
    public $notes;
    public $error_details;
    public $tester_name;
    public $test_date;
    public $environment = 'Development';
    public $browser;
    public $device;
    public $editingId;

    protected $rules = [
        'feature_name' => 'required|string|max:255',
        'feature_category' => 'required|string|max:255',
        'feature_subcategory' => 'nullable|string|max:255',
        'test_case' => 'required|string',
        'test_steps' => 'nullable|string',
        'expected_result' => 'nullable|string',
        'test_type' => 'required|in:CRUD,UI/UX,Functionality,Performance,Security,Data Validation,Error Handling,Integration,Business Logic',
        'priority' => 'required|in:Low,Medium,High,Critical',
        'status' => 'required|in:Passed,Failed,Not Tested,Blocked',
        'notes' => 'nullable|string',
        'error_details' => 'nullable|string',
        'tester_name' => 'required|string|max:255',
        'test_date' => 'required|date',
        'environment' => 'required|string|max:255',
        'browser' => 'nullable|string|max:255',
        'device' => 'nullable|string|max:255'
    ];

    public function render()
    {
        return view('livewire.qa-checklist-form', [
            'checklists' => QaChecklist::latest()->paginate(10),
            'featureCategories' => QaChecklist::getFeatureCategories()
        ]);
    }

    public function updatedFeatureCategory($value)
    {
        $this->feature_subcategory = null;
        $this->dispatch('feature-category-updated', $value);
    }

    public function mount()
    {
        $this->test_date = now()->format('Y-m-d');

        // Set tester name from logged in user
        if (Auth::check()) {
            $this->tester_name = Auth::user()->name;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'feature_name' => $this->feature_name,
            'feature_category' => $this->feature_category,
            'feature_subcategory' => $this->feature_subcategory,
            'test_case' => $this->test_case,
            'test_steps' => $this->test_steps,
            'expected_result' => $this->expected_result,
            'test_type' => $this->test_type,
            'priority' => $this->priority,
            'status' => $this->status,
            'notes' => $this->notes,
            'error_details' => $this->error_details,
            'tester_name' => $this->tester_name,
            'test_date' => $this->test_date,
            'environment' => $this->environment,
            'browser' => $this->browser,
            'device' => $this->device
        ];

        if ($this->editingId) {
            QaChecklist::find($this->editingId)->update($data);
        } else {
            QaChecklist::create($data);
        }

        $this->reset();
        $this->test_date = now()->format('Y-m-d');

        // Reset user info after save
        if (Auth::check()) {
            $this->tester_name = Auth::user()->name;
        }

        session()->flash('message', 'Checklist saved successfully.');
    }

    public function edit($id)
    {
        $checklist = QaChecklist::find($id);
        $this->editingId = $id;
        $this->feature_name = $checklist->feature_name;
        $this->feature_category = $checklist->feature_category;
        $this->feature_subcategory = $checklist->feature_subcategory;
        $this->test_case = $checklist->test_case;
        $this->test_steps = $checklist->test_steps;
        $this->expected_result = $checklist->expected_result;
        $this->test_type = $checklist->test_type;
        $this->priority = $checklist->priority;
        $this->status = $checklist->status;
        $this->notes = $checklist->notes;
        $this->error_details = $checklist->error_details;
        $this->tester_name = $checklist->tester_name;
        $this->test_date = $checklist->test_date->format('Y-m-d');
        $this->environment = $checklist->environment;
        $this->browser = $checklist->browser;
        $this->device = $checklist->device;
    }

    public function delete($id)
    {
        QaChecklist::find($id)->delete();
        session()->flash('message', 'Checklist deleted successfully.');
    }

    public function exportToJson()
    {
        $checklists = QaChecklist::all();
        $json = json_encode($checklists, JSON_PRETTY_PRINT);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, 'qa-checklist-' . now()->format('Y-m-d') . '.json');
    }

    public function exportToTxt()
    {
        $checklists = QaChecklist::all();
        $content = '';

        foreach ($checklists as $checklist) {
            $content .= "Feature: {$checklist->feature_name}\n";
            $content .= "Category: {$checklist->feature_category}\n";
            if ($checklist->feature_subcategory) {
                $content .= "Subcategory: {$checklist->feature_subcategory}\n";
            }
            $content .= "Test Case: {$checklist->test_case}\n";
            if ($checklist->test_steps) {
                $content .= "Test Steps:\n{$checklist->test_steps}\n";
            }
            if ($checklist->expected_result) {
                $content .= "Expected Result:\n{$checklist->expected_result}\n";
            }
            $content .= "Type: {$checklist->test_type}\n";
            $content .= "Priority: {$checklist->priority}\n";
            $content .= "Status: {$checklist->status}\n";
            if ($checklist->notes) {
                $content .= "Notes: {$checklist->notes}\n";
            }
            if ($checklist->error_details) {
                $content .= "Error Details: {$checklist->error_details}\n";
            }
            $content .= "Tester: {$checklist->tester_name}\n";
            $content .= "Date: {$checklist->test_date}\n";
            $content .= "Environment: {$checklist->environment}\n";
            if ($checklist->browser) {
                $content .= "Browser: {$checklist->browser}\n";
            }
            if ($checklist->device) {
                $content .= "Device: {$checklist->device}\n";
            }
            $content .= "----------------------------------------\n";
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'qa-checklist-' . now()->format('Y-m-d') . '.txt');
    }
}
