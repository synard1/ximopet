<?php

namespace App\AiChatV2\Livewire;

use Livewire\Component;
use App\AiChatV2\Services\ChatSessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class SessionList extends Component
{
    public $sessions = [];
    public $searchTerm = '';
    public $showArchived = false;
    public $currentSessionId = null;
    public $isLoading = false;
    public $error = null;
    
    protected ChatSessionService $chatSessionService;

    public function boot(ChatSessionService $chatSessionService)
    {
        $this->chatSessionService = $chatSessionService;
    }

    public function mount($currentSessionId = null)
    {
        $this->currentSessionId = $currentSessionId;
        $this->loadSessions();
    }

    public function loadSessions()
    {
        $this->isLoading = true;
        $this->error = null;
        
        try {
            $this->sessions = $this->chatSessionService->getUserSessions(
                Auth::id(), 
                $this->searchTerm, 
                $this->showArchived
            );
        } catch (Exception $e) {
            Log::error('Failed to load chat sessions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->error = 'Failed to load sessions';
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectSession($sessionId)
    {
        $this->currentSessionId = $sessionId;
        $this->dispatch('session-selected', sessionId: $sessionId);
    }

    public function archiveSession($sessionId)
    {
        try {
            $this->chatSessionService->archiveSession($sessionId, Auth::id());
            $this->loadSessions();
            $this->dispatch('notify', message: 'Session archived successfully', type: 'success');
        } catch (Exception $e) {
            Log::error('Failed to archive session', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('notify', message: 'Failed to archive session', type: 'error');
        }
    }

    public function restoreSession($sessionId)
    {
        try {
            $this->chatSessionService->restoreSession($sessionId, Auth::id());
            $this->loadSessions();
            $this->dispatch('notify', message: 'Session restored successfully', type: 'success');
        } catch (Exception $e) {
            Log::error('Failed to restore session', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('notify', message: 'Failed to restore session', type: 'error');
        }
    }

    public function deleteSession($sessionId)
    {
        try {
            $this->chatSessionService->deleteSession($sessionId, Auth::id());
            $this->loadSessions();
            
            // If we deleted the current session, notify parent to create new one
            if ($sessionId === $this->currentSessionId) {
                $this->dispatch('session-deleted');
            }
            
            $this->dispatch('notify', message: 'Session deleted successfully', type: 'success');
        } catch (Exception $e) {
            Log::error('Failed to delete session', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('notify', message: 'Failed to delete session', type: 'error');
        }
    }

    public function exportSession($sessionId)
    {
        // TODO: Implement session export functionality
        $this->dispatch('notify', message: 'Session export feature coming soon!', type: 'info');
    }

    public function toggleArchived()
    {
        $this->showArchived = !$this->showArchived;
        $this->loadSessions();
    }

    public function clearSearch()
    {
        $this->searchTerm = '';
        $this->loadSessions();
    }

    public function updatedSearchTerm()
    {
        $this->loadSessions();
    }

    public function getFilteredSessions()
    {
        return collect($this->sessions)->filter(function ($session) {
            if (!$this->searchTerm) {
                return true;
            }
            
            return stripos($session['title'], $this->searchTerm) !== false || 
                   ($session['summary'] && stripos($session['summary'], $this->searchTerm) !== false);
        })->toArray();
    }

    public function render()
    {
        return view('ai-chat-v2.livewire.session-list');
    }
}