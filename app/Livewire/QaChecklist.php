<?php

namespace App\Livewire;

use App\Models\QaChecklist;
use Livewire\Component;
use Livewire\WithPagination;

class QaChecklist extends Component
{
    use WithPagination;

    public $feature_name;
    public $feature_category;
    public $test_case;
    public $test_type;
    public $status = 'Not Tested';
    public $notes;
    public $tester_name;
    public $test_date;
    public $editingId;

    protected $rules = [
        'feature_name' => 'required|string|max:255',
        'feature_category' => 'required|string|max:255',
        'test_case' => 'required|string',
        'test_type' => 'required|in:CRUD,UI/UX,Functionality,Performance,Security',
        'status' => 'required|in:Passed,Failed,Not Tested',
        'notes' => 'nullable|string',
        'tester_name' => 'required|string|max:255',
        'test_date' => 'required|date'
    ];

    public function render()
    {
        return view('livewire.qa checklist', [
            'checklists' => QaChecklist::latest()->paginate(10)
        ]);
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            QaChecklist::find($this->editingId)->update([
                'feature_name' => $this->feature_name,
                'feature_category' => $this->feature_category,
                'test_case' => $this->test_case,
                'test_type' => $this->test_type,
                'status' => $this->status,
                'notes' => $this->notes,
                'tester_name' => $this->tester_name,
                'test_date' => $this->test_date
            ]);
        } else {
            QaChecklist::create([
                'feature_name' => $this->feature_name,
                'feature_category' => $this->feature_category,
                'test_case' => $this->test_case,
                'test_type' => $this->test_type,
                'status' => $this->status,
                'notes' => $this->notes,
                'tester_name' => $this->tester_name,
                'test_date' => $this->test_date
            ]);
        }

        $this->reset();
        session()->flash('message', 'Checklist saved successfully.');
    }

    public function edit($id)
    {
        $checklist = QaChecklist::find($id);
        $this->editingId = $id;
        $this->feature_name = $checklist->feature_name;
        $this->feature_category = $checklist->feature_category;
        $this->test_case = $checklist->test_case;
        $this->test_type = $checklist->test_type;
        $this->status = $checklist->status;
        $this->notes = $checklist->notes;
        $this->tester_name = $checklist->tester_name;
        $this->test_date = $checklist->test_date->format('Y-m-d');
    }

    public function delete($id)
    {
        QaChecklist::find($id)->delete();
        session()->flash('message', 'Checklist deleted successfully.');
        $this->dispatch('success', 'Checklist deleted successfully.');
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
            $content .= "Test Case: {$checklist->test_case}\n";
            $content .= "Type: {$checklist->test_type}\n";
            $content .= "Status: {$checklist->status}\n";
            $content .= "Notes: {$checklist->notes}\n";
            $content .= "Tester: {$checklist->tester_name}\n";
            $content .= "Date: {$checklist->test_date}\n";
            $content .= "----------------------------------------\n";
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'qa-checklist-' . now()->format('Y-m-d') . '.txt');
    }
}
