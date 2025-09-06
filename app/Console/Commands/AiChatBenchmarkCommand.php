<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Support\Facades\DB;

class AiChatBenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:chat-benchmark 
                            {--requests=5 : Number of requests to send}
                            {--concurrent=1 : Number of concurrent requests}
                            {--user= : User ID to simulate}
                            {--provider= : AI provider to use}
                            {--model= : Specific AI model to use}
                            {--message="Hello, how can you help me today?" : Test message to send}
                            {--debug : Enable detailed logging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark AI chat performance';

    /**
     * Execute the console command.
     */
    public function handle(AiChatService $aiChatService)
    {
        $requests = (int) $this->option('requests');
        $concurrent = (int) $this->option('concurrent');
        $userId = $this->option('user');
        $provider = $this->option('provider');
        $model = $this->option('model');
        $message = $this->option('message');
        $debug = $this->option('debug');

        // Validate options
        if ($requests < 1) {
            $this->error("Requests must be at least 1");
            return 1;
        }

        if ($concurrent < 1) {
            $this->error("Concurrent requests must be at least 1");
            return 1;
        }

        if ($concurrent > $requests) {
            $this->error("Concurrent requests cannot exceed total requests");
            return 1;
        }

        $this->info("🚀 AI Chat Performance Benchmark");
        $this->line("🔁 Requests: {$requests}");
        $this->line("🔄 Concurrent: {$concurrent}");
        $this->line("📝 Message: {$message}");

        // Set up authentication context
        $user = $this->setupAuthContext($userId);
        if (!$user) {
            $this->error("❌ Unable to set up authentication context");
            return 1;
        }

        if ($debug) {
            $this->line("🔑 Authenticated as: {$user->name} (ID: {$user->id})");
        }

        // Run benchmark
        $results = $this->runBenchmark($aiChatService, $requests, $concurrent, $message, $provider, $model, $debug);

        // Display results
        $this->displayResults($results);

        return 0;
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
     * Run the benchmark test
     */
    private function runBenchmark(
        AiChatService $aiChatService,
        int $requests,
        int $concurrent,
        string $message,
        ?string $provider,
        ?string $model,
        bool $debug
    ): array {
        $this->info("⏱️  Starting benchmark...");

        $results = [
            'total_requests' => $requests,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_time' => 0,
            'processing_times' => [],
            'database_queries' => [],
            'errors' => []
        ];

        // Track database queries
        $initialQueryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        if (DB::logging()) {
            DB::flushQueryLog();
        } else {
            DB::enableQueryLog();
        }

        $startTime = microtime(true);

        // For simplicity, we'll run requests sequentially rather than truly concurrently
        // A full implementation would use something like ReactPHP or Swoole for true concurrency
        for ($i = 1; $i <= $requests; $i++) {
            if ($debug) {
                $this->line("📤 Sending request {$i}/{$requests}...");
            }

            $requestStartTime = microtime(true);
            
            try {
                // Send message
                $response = $aiChatService->sendMessage($message, null, $provider, $model);
                
                $requestTime = microtime(true) - $requestStartTime;
                $results['processing_times'][] = $requestTime;
                
                if ($response['success']) {
                    $results['successful_requests']++;
                    if ($debug) {
                        $this->line("✅ Request {$i} successful ({$requestTime}s)");
                    }
                } else {
                    $results['failed_requests']++;
                    $results['errors'][] = $response['error'];
                    if ($debug) {
                        $this->line("❌ Request {$i} failed: " . $response['error']);
                    }
                }
            } catch (\Exception $e) {
                $requestTime = microtime(true) - $requestStartTime;
                $results['processing_times'][] = $requestTime;
                $results['failed_requests']++;
                $results['errors'][] = $e->getMessage();
                
                if ($debug) {
                    $this->line("❌ Request {$i} failed with exception: " . $e->getMessage());
                }
            }
        }

        $results['total_time'] = microtime(true) - $startTime;

        // Collect database query information
        if (DB::logging()) {
            $queries = DB::getQueryLog();
            $results['database_queries'] = [
                'total' => count($queries),
                'time' => array_sum(array_column($queries, 'time')) / 1000 // Convert ms to seconds
            ];
        }

        return $results;
    }

    /**
     * Display benchmark results
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info("📊 Benchmark Results");

        // Success rate
        $successRate = ($results['successful_requests'] / $results['total_requests']) * 100;
        $this->line("✅ Success Rate: " . number_format($successRate, 2) . "% ({$results['successful_requests']}/{$results['total_requests']})");

        // Timing statistics
        if (!empty($results['processing_times'])) {
            $times = $results['processing_times'];
            $avgTime = array_sum($times) / count($times);
            $minTime = min($times);
            $maxTime = max($times);

            $this->line("⏱️  Processing Time:");
            $this->line("  • Average: " . number_format($avgTime, 3) . "s");
            $this->line("  • Min: " . number_format($minTime, 3) . "s");
            $this->line("  • Max: " . number_format($maxTime, 3) . "s");
            $this->line("  • Total: " . number_format($results['total_time'], 3) . "s");
        }

        // Throughput
        if ($results['total_time'] > 0) {
            $requestsPerSecond = $results['total_requests'] / $results['total_time'];
            $this->line("📈 Throughput: " . number_format($requestsPerSecond, 2) . " requests/second");
        }

        // Database queries
        if (!empty($results['database_queries'])) {
            $db = $results['database_queries'];
            $this->line("🗄️  Database:");
            $this->line("  • Queries: {$db['total']}");
            $this->line("  • Time: " . number_format($db['time'], 3) . "s");
        }

        // Errors
        if (!empty($results['errors'])) {
            $this->line("❌ Errors ({$results['failed_requests']}):");
            $uniqueErrors = array_count_values($results['errors']);
            foreach ($uniqueErrors as $error => $count) {
                $this->line("  • {$error} ({$count} times)");
            }
        }
    }
}