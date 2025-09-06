<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\ChatMessage;

class AiChatSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:chat-sessions 
                            {--list : List chat sessions}
                            {--info= : Show details for specific session}
                            {--delete= : Delete specific session}
                            {--user= : User ID to filter sessions (default: first user)}
                            {--limit=20 : Limit number of sessions to show}
                            {--all : Show all sessions (not just active)}
                            {--debug : Enable detailed logging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage AI chat sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $list = $this->option('list');
        $info = $this->option('info');
        $delete = $this->option('delete');
        $userId = $this->option('user');
        $limit = (int) $this->option('limit');
        $all = $this->option('all');
        $debug = $this->option('debug');

        // Set up authentication context
        $user = $this->setupAuthContext($userId);
        if (!$user) {
            $this->error("❌ Unable to set up authentication context");
            return 1;
        }

        if ($debug) {
            $this->line("🔑 Authenticated as: {$user->name} (ID: {$user->id})");
        }

        // Handle different operations
        if ($info) {
            return $this->showSessionInfo($info, $user);
        } elseif ($delete) {
            return $this->deleteSession($delete, $user, $debug);
        } else {
            return $this->listSessions($user, $limit, $all, $debug);
        }
    }

    /**
     * Set up authentication context for the command
     */
    private function setupAuthContext(?string $userId): ?User
    {
        try {
            // If user ID provided, use that user
            if ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    $this->error("User with ID {$userId} not found");
                    return null;
                }
            } else {
                // Otherwise, use the first user in the database
                $user = User::first();
                if (!$user) {
                    $this->error("No users found in the database");
                    return null;
                }
            }

            // Set the authenticated user
            Auth::login($user);
            return $user;
        } catch (\Exception $e) {
            $this->error("Error setting up authentication context: " . $e->getMessage());
            return null;
        }
    }

    /**
     * List chat sessions
     */
    private function listSessions(User $user, int $limit, bool $all, bool $debug): int
    {
        $this->info("💬 AI Chat Sessions");
        
        try {
            // Build query
            $query = ChatSession::where('user_id', $user->id);
            
            // Handle company scoping
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereNull('company_id');
            }
            
            // Filter by active status unless --all is specified
            if (!$all) {
                $query->active();
            }
            
            // Order by last activity
            $query->latest('last_activity_at');
            
            // Apply limit
            $sessions = $query->take($limit)->get();
            
            if ($sessions->isEmpty()) {
                $this->line("No chat sessions found.");
                return 0;
            }
            
            // Display sessions in a table
            $headers = ['ID', 'Title', 'Provider', 'Model', 'Messages', 'Last Activity'];
            $rows = [];
            
            foreach ($sessions as $session) {
                $rows[] = [
                    substr($session->id, 0, 8) . '...',
                    $session->title,
                    $session->ai_provider,
                    $session->model_name,
                    $session->messages()->count(),
                    $session->last_activity_at ? $session->last_activity_at->diffForHumans() : 'Never'
                ];
            }
            
            $this->table($headers, $rows);
            
            if ($debug) {
                $this->line("📊 Total sessions: " . $sessions->count());
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error listing sessions: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show details for a specific session
     */
    private function showSessionInfo(string $sessionId, User $user): int
    {
        $this->info("📄 Session Details");
        
        try {
            // Find session
            $query = ChatSession::where('id', $sessionId)
                ->where('user_id', $user->id);
            
            // Handle company scoping
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereNull('company_id');
            }
            
            $session = $query->first();
            
            if (!$session) {
                $this->error("Session not found or access denied.");
                return 1;
            }
            
            // Display session details
            $this->line("ID: {$session->id}");
            $this->line("Title: {$session->title}");
            $this->line("Provider: {$session->ai_provider}");
            $this->line("Model: {$session->model_name}");
            $this->line("Status: " . ($session->is_active ? 'Active' : 'Inactive'));
            $this->line("Created: {$session->created_at->diffForHumans()}");
            $this->line("Last Activity: " . ($session->last_activity_at ? $session->last_activity_at->diffForHumans() : 'Never'));
            
            // Show message count
            $messageCount = $session->messages()->count();
            $this->line("Messages: {$messageCount}");
            
            // Show recent messages if any
            if ($messageCount > 0) {
                $this->newLine();
                $this->line("🗨️  Recent Messages:");
                
                $messages = $session->messages()
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
                
                foreach ($messages as $message) {
                    $type = ucfirst($message->message_type);
                    $time = $message->created_at->format('Y-m-d H:i:s');
                    $preview = substr($message->content, 0, 50) . (strlen($message->content) > 50 ? '...' : '');
                    $this->line("[{$time}] {$type}: {$preview}");
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error showing session info: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Delete a specific session
     */
    private function deleteSession(string $sessionId, User $user, bool $debug): int
    {
        try {
            // Find session
            $query = ChatSession::where('id', $sessionId)
                ->where('user_id', $user->id);
            
            // Handle company scoping
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereNull('company_id');
            }
            
            $session = $query->first();
            
            if (!$session) {
                $this->error("Session not found or access denied.");
                return 1;
            }
            
            // Confirm deletion
            $title = $session->title;
            if (!$this->confirm("Delete session '{$title}' (ID: {$sessionId})?")) {
                $this->line("Session deletion cancelled.");
                return 0;
            }
            
            // Delete session
            $session->delete();
            
            $this->info("✅ Session deleted successfully.");
            
            if ($debug) {
                $this->line("🗑️  Deleted session: {$sessionId}");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error deleting session: " . $e->getMessage());
            return 1;
        }
    }
}