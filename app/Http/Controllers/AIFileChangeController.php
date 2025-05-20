<?php

namespace App\Http\Controllers;

use App\Services\AIFileChangeService;
use Illuminate\Http\Request;

class AIFileChangeController extends Controller
{
    protected $aiFileChangeService;

    public function __construct(AIFileChangeService $aiFileChangeService)
    {
        $this->aiFileChangeService = $aiFileChangeService;
    }

    public function index()
    {
        $changes = $this->aiFileChangeService->getRecentChanges();
        return view('ai-changes.index', compact('changes'));
    }

    public function showByDate(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $changes = $this->aiFileChangeService->getChangesByDate($date);
        return view('ai-changes.index', compact('changes', 'date'));
    }
}
