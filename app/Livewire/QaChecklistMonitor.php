<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\QaChecklistMonitorService;

class QaChecklistMonitor extends Component
{
    public $url;
    public $checklists = [];

    public function mount($url = null)
    {
        $this->url = $url ?? request()->path();
        $this->checklists = QaChecklistMonitorService::getForUrl($this->url);
    }

    public function render()
    {
        return view('livewire.qa-checklist-monitor');
    }
}
