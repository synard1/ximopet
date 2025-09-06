<?php

namespace App\Livewire\Admin;

use App\Services\ChatRatingService;
use Livewire\Component;

class ChatRatingStats extends Component
{
    public $stats = [];
    public $period = 'week';

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $ratingService = app(ChatRatingService::class);
        $this->stats = $ratingService->getRatingStats(null, $this->period);
    }

    public function updatedPeriod()
    {
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.admin.chat-rating-stats');
    }
}
