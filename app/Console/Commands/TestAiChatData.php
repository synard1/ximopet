<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiDatabaseServiceRefactored;
use App\Services\AiChatService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestAiChatData extends Command
{
    protected $signature = 'test:ai-chat-data {user_id?}';
    protected $description = 'Test AI chat database integration';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);
        } else {
            $user = User::first();
        }

        if (!$user) {
            $this->error('No user found');
            return 1;
        }

        Auth::login($user);

        $this->info("Testing AI chat data with user: {$user->name}");
        $this->info("Company ID: " . ($user->company_id ?: 'None'));

        $databaseService = app(AiDatabaseServiceRefactored::class);

        // Test different queries
        $testQueries = [
            'berapa jumlah ternak',
            'tampilkan data keuangan',
            'berapa pakan yang digunakan',
            'status kandang',
            'laporan ternak bulan ini'
        ];

        foreach ($testQueries as $query) {
            $this->info("\n=== Testing Query: '$query' ===");

            try {
                $results = $databaseService->searchData($query);

                if (!empty($results)) {
                    $formatted = $databaseService->formatDataForAI($results);
                    $this->line($formatted);
                } else {
                    $this->warn('No results found for this query');
                }

            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        }

        // Test direct data methods
        $this->info("\n=== Testing Direct Data Access ===");

        try {
            $livestock = $databaseService->getLivestockSummary();
            $this->info("Livestock Summary:");
            $this->table(
                ['Metric', 'Value'],
                collect($livestock)->map(fn($value, $key) => [$key, is_array($value) ? json_encode($value) : $value])
            );

            $financial = $databaseService->getFinancialSummary();
            $this->info("Financial Summary:");
            $this->table(
                ['Metric', 'Value'],
                collect($financial)->map(fn($value, $key) => [$key, is_array($value) ? json_encode($value) : $value])
            );

        } catch (\Exception $e) {
            $this->error("Error testing direct access: " . $e->getMessage());
        }

        $this->info("\n=== Test Complete ===");

        return 0;
    }
}
