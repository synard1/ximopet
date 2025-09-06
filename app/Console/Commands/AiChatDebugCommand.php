<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\AiChatService;
use App\Services\ChatContextService;
use App\Models\ChatSession;
use Exception;

class AiChatDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:chat-debug
                            {message : The message to send to the AI}
                            {--user= : User ID to simulate for authentication context}
                            {--provider= : AI provider to use (ollama|openwebui)}
                            {--model= : Specific AI model to use}
                            {--session= : Session ID to continue an existing chat}
                            {--context-type= : Type of context to build}
                            {--no-context : Disable context building}
                            {--debug : Enable detailed logging}
                            {--verbose-output : Enable verbose output}
                            {--interactive : Enable interactive mode}
                            {--inspect-context : Show exactly what context is being sent}
                            {--analyze : Analyze response quality}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug AI chat functionality via command line using the same services as the web interface';

    /**
     * Execute the console command.
     */
    public function handle(AiChatService $aiChatService, ChatContextService $contextService)
    {
        $message = $this->argument('message');
        $userId = $this->option('user');
        $provider = $this->option('provider');
        $model = $this->option('model');
        $sessionId = $this->option('session');
        $contextType = $this->option('context-type');
        $noContext = $this->option('no-context');
        $debug = $this->option('debug');
        $verbose = $this->option('verbose-output');  // Changed from 'verbose' to 'verbose-output'
        $interactive = $this->option('interactive');
        $inspectContext = $this->option('inspect-context');
        $analyze = $this->option('analyze');

        // Log initial command execution
        if ($debug) {
            $this->info("🚀 Starting AI Chat Debug Command");
            $this->line("📝 Message: {$message}");
            if ($userId) $this->line("👤 User ID: {$userId}");
            if ($provider) $this->line("🤖 Provider: {$provider}");
            if ($model) $this->line("🧠 Model: {$model}");
            if ($sessionId) $this->line("💬 Session ID: {$sessionId}");
            if ($contextType) $this->line("📄 Context Type: {$contextType}");
            if ($noContext) $this->line("🚫 No Context: Enabled");
        }

        try {
            // Set up authentication context
            $user = $this->setupAuthContext($userId);
            if (!$user) {
                $this->error("❌ Unable to set up authentication context");
                return 1;
            }

            // Log user information
            if ($debug) {
                $this->line("🔑 Authenticated as: {$user->name} (ID: {$user->id})");
                if ($user->company_id) {
                    $this->line("🏢 Company ID: {$user->company_id}");
                }
            }

            // Handle context inspection
            if ($inspectContext) {
                $this->inspectContext($contextService, $message, $user, $contextType, $noContext, $debug);
                return 0;
            }

            // Handle interactive mode
            if ($interactive) {
                return $this->handleInteractiveMode($aiChatService, $message, $provider, $model, $sessionId, $contextType, $noContext, $debug, $verbose, $analyze);
            }

            // Send single message
            $result = $this->sendSingleMessage($aiChatService, $message, $provider, $model, $sessionId, $contextType, $noContext, $debug, $verbose);

            // Handle response analysis
            if ($analyze && $result['success']) {
                $this->analyzeResponse($result['assistant_message']['content'], $message, $debug);
            }

            // Output result
            if ($result['success']) {
                $this->info("✅ AI Response:");
                $this->line($result['assistant_message']['content']);

                if ($verbose) {
                    $this->newLine();
                    $this->line("📊 Session ID: {$result['session_id']}");
                    $this->line("⏱️  Processing Time: " . number_format($result['processing_time'], 3) . "s");
                }

                return 0;
            } else {
                $this->error("❌ Error: " . $result['error']);
                return 1;
            }

        } catch (Exception $e) {
            $this->error("❌ Unexpected Error: " . $e->getMessage());
            if ($debug) {
                $this->line("🔍 Stack trace: " . $e->getTraceAsString());
            }
            return 1;
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
        } catch (Exception $e) {
            $this->error("Error setting up authentication context: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a single message to the AI chat service
     */
    private function sendSingleMessage(
        AiChatService $aiChatService,
        string $message,
        ?string $provider,
        ?string $model,
        ?string $sessionId,
        ?string $contextType,
        bool $noContext,
        bool $debug,
        bool $verbose
    ): array {
        try {
            if ($debug) {
                $this->line("📤 Sending message to AI chat service...");
            }

            // Prepare context filters
            $contextFilters = [];
            if ($noContext) {
                $contextFilters['no_context'] = true;
            }

            // Use default context type if not specified
            $contextType = $contextType ?: config('chat.context.default_context_type', 'general');

            // Send message through the AI chat service
            $result = $aiChatService->sendMessage(
                $message,
                $sessionId,
                $provider,
                $model,
                $contextFilters,
                $contextType
            );

            if ($debug) {
                if ($result['success']) {
                    $this->line("✅ Message sent successfully");
                    $this->line("💬 Session ID: {$result['session_id']}");
                } else {
                    $this->line("❌ Failed to send message: " . $result['error']);
                }
            }

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle interactive mode for continuous conversation
     */
    private function handleInteractiveMode(
        AiChatService $aiChatService,
        string $initialMessage,
        ?string $provider,
        ?string $model,
        ?string $sessionId,
        ?string $contextType,
        bool $noContext,
        bool $debug,
        bool $verbose,
        bool $analyze
    ): int {
        $this->info("🔄 Entering interactive mode (type 'exit' to quit)");

        // Send initial message
        $result = $this->sendSingleMessage($aiChatService, $initialMessage, $provider, $model, $sessionId, $contextType, $noContext, $debug, $verbose);

        if (!$result['success']) {
            $this->error("❌ Failed to send initial message: " . $result['error']);
            return 1;
        }

        // Store session ID for continued conversation
        $currentSessionId = $result['session_id'];

        // Display initial response
        $this->info("🤖 AI Response:");
        $this->line($result['assistant_message']['content']);

        if ($analyze) {
            $this->analyzeResponse($result['assistant_message']['content'], $initialMessage, $debug);
        }

        // Interactive loop
        while (true) {
            $this->newLine();
            $userInput = $this->ask("You");

            // Exit condition
            if (strtolower($userInput) === 'exit') {
                $this->info("👋 Exiting interactive mode");
                break;
            }

            // Send user input
            $result = $this->sendSingleMessage($aiChatService, $userInput, $provider, $model, $currentSessionId, $contextType, $noContext, $debug, $verbose);

            if ($result['success']) {
                $this->info("🤖 AI Response:");
                $this->line($result['assistant_message']['content']);

                if ($analyze) {
                    $this->analyzeResponse($result['assistant_message']['content'], $userInput, $debug);
                }

                // Update session ID
                $currentSessionId = $result['session_id'];
            } else {
                $this->error("❌ Error: " . $result['error']);
            }
        }

        return 0;
    }

    /**
     * Inspect the context that would be sent to the AI
     */
    private function inspectContext(
        ChatContextService $contextService,
        string $message,
        User $user,
        ?string $contextType,
        bool $noContext,
        bool $debug
    ): void {
        $this->info("🔍 Context Inspection");

        if ($noContext) {
            $this->line("🚫 Context building is disabled");
            return;
        }

        try {
            $contextType = $contextType ?: config('chat.context.default_context_type', 'general');

            if ($debug) {
                $this->line("📄 Building context of type: {$contextType}");
            }

            // Build secure context
            $context = $contextService->buildSecureContext($message, $user, $contextType);

            $this->line("📄 Context Data:");
            $this->line($context);

            // Show context size
            $contextSize = strlen($context);
            $this->line("📊 Context Size: {$contextSize} characters");

        } catch (Exception $e) {
            $this->error("❌ Error building context: " . $e->getMessage());
        }
    }

    /**
     * Analyze the quality of the AI response
     */
    private function analyzeResponse(string $response, string $userMessage, bool $debug): void
    {
        $this->info("🔍 Response Analysis");

        // Check for common issues
        $issues = [];

        // Check for thinking artifacts
        if (preg_match('/\[think\]/i', $response) || preg_match('/\[reason\]/i', $response)) {
            $issues[] = "Contains thinking artifacts";
        }

        // Check for placeholder text
        if (preg_match('/\[context\]/i', $response) || preg_match('/\[data\]/i', $response)) {
            $issues[] = "Contains placeholder text";
        }

        // Check response length
        $wordCount = str_word_count($response);
        if ($wordCount < 5) {
            $issues[] = "Very short response ({$wordCount} words)";
        }

        // Check for repetition
        if (strlen($response) > 100) {
            $words = explode(' ', $response);
            $uniqueWords = array_unique($words);
            $repetitionRate = 1 - (count($uniqueWords) / count($words));
            if ($repetitionRate > 0.3) {
                $issues[] = "High repetition rate (" . round($repetitionRate * 100, 1) . "%)";
            }
        }

        // Display analysis
        if (empty($issues)) {
            $this->line("✅ Response appears to be of good quality");
        } else {
            $this->line("⚠️  Potential issues detected:");
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
        }

        // Show statistics
        $charCount = strlen($response);
        $wordCount = str_word_count($response);
        $lineCount = substr_count($response, "\n") + 1;

        $this->line("📊 Statistics:");
        $this->line("  • Characters: {$charCount}");
        $this->line("  • Words: {$wordCount}");
        $this->line("  • Lines: {$lineCount}");
    }
}
