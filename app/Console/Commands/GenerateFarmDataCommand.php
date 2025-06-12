<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use App\Models\{Recording, Livestock, FeedUsage, LivestockDepletion};

class GenerateFarmDataCommand extends Command
{
    protected $signature = 'farm:generate-data 
                            {--fresh : Clear existing data first}
                            {--farms=3 : Number of farms to create}
                            {--days=45 : Number of recording days}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Generate comprehensive farm dummy data for Smart Analytics';

    public function handle()
    {
        $this->info('🚀 Starting Farm Data Generation');
        $this->info('📊 This will create comprehensive dummy data for Smart Analytics');

        // Show configuration
        $this->table(['Setting', 'Value'], [
            ['Farms', $this->option('farms')],
            ['Recording Days', $this->option('days')],
            ['Fresh Install', $this->option('fresh') ? 'Yes' : 'No'],
            ['Estimated Time', '2-5 minutes'],
            ['Estimated Records', '~600+ records']
        ]);

        // Confirmation
        if (!$this->option('force') && !$this->confirm('Do you want to proceed?')) {
            $this->info('❌ Operation cancelled');
            return 0;
        }

        $startTime = microtime(true);

        try {
            // Fresh install option
            if ($this->option('fresh')) {
                $this->freshInstall();
            }

            // Pre-flight checks
            $this->preflightChecks();

            // Generate data
            $this->generateData();

            // Post-generation verification
            $this->verifyGeneration();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("✅ Farm data generation completed successfully in {$duration} seconds");
            $this->showSummary();

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error generating farm data: ' . $e->getMessage());
            Log::error('Farm data generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function freshInstall()
    {
        $this->info('🗑️ Clearing existing data...');

        if (!$this->option('force') && !$this->confirm('This will delete ALL existing data. Are you sure?')) {
            $this->info('❌ Operation cancelled');
            exit(0);
        }

        $this->info('📤 Running fresh migration...');
        Artisan::call('migrate:fresh', ['--force' => true]);

        $this->info('📦 Running basic seeders...');
        Artisan::call('db:seed', [
            '--class' => 'BasicDataSeeder',
            '--force' => true
        ]);

        $this->info('✅ Fresh installation completed');
    }

    private function preflightChecks()
    {
        $this->info('🔍 Running pre-flight checks...');

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection: OK');
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        // Check required tables
        $requiredTables = [
            'users',
            'farms',
            'coops',
            'partners',
            'feeds',
            'supplies',
            'livestocks',
            'recordings',
            'feed_usages',
            'livestock_depletions'
        ];

        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new \Exception("Required table '{$table}' not found. Run migrations first.");
            }
        }
        $this->info('✅ Required tables: OK');

        // Check for admin user
        if (!\App\Models\User::exists()) {
            throw new \Exception('No users found. Create admin user first.');
        }
        $this->info('✅ Admin user: OK');

        // Check for basic units
        if (!\App\Models\Unit::exists()) {
            throw new \Exception('No units found. Run basic data seeder first.');
        }
        $this->info('✅ Basic units: OK');

        $this->info('✅ All pre-flight checks passed');
    }

    private function generateData()
    {
        $this->info('📊 Generating comprehensive farm data...');
        $this->info('⏱️ This may take several minutes...');

        // Create progress bar
        $bar = $this->output->createProgressBar(5);
        $bar->setFormat('verbose');

        // Step 1: Basic infrastructure
        $bar->setMessage('Creating basic infrastructure...');
        $bar->advance();

        // Step 2: Livestock purchases
        $bar->setMessage('Generating livestock purchases...');
        $bar->advance();

        // Step 3: Feed purchases
        $bar->setMessage('Generating feed purchases...');
        $bar->advance();

        // Step 4: Supply purchases
        $bar->setMessage('Generating supply purchases...');
        $bar->advance();

        // Step 5: Daily recordings
        $bar->setMessage('Generating daily recordings...');
        $bar->advance();

        $bar->finish();
        $this->newLine();

        // Run the actual seeder
        $this->info('🔄 Running ComprehensiveFarmDataSeeder...');

        $result = Artisan::call('db:seed', [
            '--class' => 'ComprehensiveFarmDataSeeder',
            '--force' => true
        ]);

        if ($result !== 0) {
            throw new \Exception('Seeder execution failed');
        }

        $this->info('✅ Data generation completed');
    }

    private function verifyGeneration()
    {
        $this->info('🔍 Verifying generated data...');

        // Count records
        $counts = [
            'Livestock Batches' => Livestock::count(),
            'Daily Recordings' => Recording::count(),
            'Feed Usages' => FeedUsage::count(),
            'Depletion Records' => LivestockDepletion::count(),
        ];

        // Verify data quality
        $verification = [];

        // Check date ranges
        $firstRecording = Recording::orderBy('tanggal')->first();
        $lastRecording = Recording::orderBy('tanggal', 'desc')->first();

        if ($firstRecording && $lastRecording) {
            $dateRange = Carbon::parse($firstRecording->tanggal)->diffInDays(Carbon::parse($lastRecording->tanggal));
            $verification['Recording Period'] = "{$dateRange} days";
            $verification['Date Range'] = $firstRecording->tanggal->format('Y-m-d') . ' to ' . $lastRecording->tanggal->format('Y-m-d');
        }

        // Check data consistency
        $livestockWithRecordings = Livestock::has('recordings')->count();
        $verification['Livestock with Data'] = "{$livestockWithRecordings}/{$counts['Livestock Batches']}";

        // Check performance metrics
        $avgMortalityRate = Recording::whereNotNull('payload->mortality')
            ->selectRaw('AVG(JSON_EXTRACT(payload, "$.mortality")) as avg_mortality')
            ->value('avg_mortality');

        if ($avgMortalityRate !== null) {
            $verification['Avg Daily Mortality'] = round($avgMortalityRate, 2) . ' birds/day';
        }

        // Display results
        $this->table(['Metric', 'Count'], collect($counts)->map(function ($value, $key) {
            return [$key, $value];
        })->toArray());

        $this->table(['Verification', 'Result'], collect($verification)->map(function ($value, $key) {
            return [$key, $value];
        })->toArray());

        // Quality checks
        if ($counts['Livestock Batches'] === 0) {
            throw new \Exception('No livestock batches generated');
        }

        if ($counts['Daily Recordings'] === 0) {
            throw new \Exception('No recordings generated');
        }

        $this->info('✅ Data verification completed');
    }

    private function showSummary()
    {
        $this->info('📈 Generation Summary:');

        // Performance summary
        $livestock = Livestock::with(['recordings' => function ($query) {
            $query->orderBy('tanggal', 'desc')->limit(1);
        }])->get();

        $performanceData = [];

        foreach ($livestock as $batch) {
            $latestRecording = $batch->recordings->first();
            if ($latestRecording) {
                $performanceData[] = [
                    'Batch' => substr($batch->name, 0, 20) . '...',
                    'Age' => $latestRecording->age . ' days',
                    'Population' => number_format($latestRecording->stock_akhir),
                    'Weight' => round($latestRecording->berat_hari_ini, 2) . ' kg',
                    'Survival' => round(($latestRecording->stock_akhir / $batch->initial_quantity) * 100, 1) . '%'
                ];
            }
        }

        if (!empty($performanceData)) {
            $this->table(['Batch', 'Age', 'Population', 'Weight', 'Survival %'], $performanceData);
        }

        $this->info('🎯 Next Steps:');
        $this->info('1. Visit Smart Analytics: /report/smart-analytics');
        $this->info('2. Check Laravel logs: storage/logs/laravel.log');
        $this->info('3. Review recording data in admin panel');

        $this->info('📝 Logging:');
        $this->info('- All operations logged to Laravel logs');
        $this->info('- Detailed progress tracking available');
        $this->info('- Performance metrics calculated');

        // Log completion
        Log::info('🎉 Farm data generation completed via command', [
            'livestock_count' => Livestock::count(),
            'recording_count' => Recording::count(),
            'command_user' => 'CLI',
            'execution_time' => microtime(true),
            'options' => $this->options()
        ]);
    }
}
