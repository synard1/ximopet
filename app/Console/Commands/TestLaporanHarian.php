<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestLaporanHarian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:laporan-harian {--calculations : Run calculation tests only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tests for Laporan Harian refactor to validate fixes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Starting Laporan Harian Tests...');
        $this->info('================================');

        try {
            // Include the test class
            require_once base_path('testing/laporan-harian-test.php');

            $tester = new \LaporanHarianTest();

            if ($this->option('calculations')) {
                // Run calculation tests only
                $this->info('🧮 Running calculation tests only...');
                $tester->testCalculations();
            } else {
                // Run all tests
                $this->info('🎯 Running all test scenarios...');
                $tester->runAllTests();
                $tester->testCalculations();
            }

            // Log results
            $tester->logTestResults();

            $this->info('✅ Test execution completed!');
            $this->info('📝 Check testing/logs/ for detailed results');
        } catch (\Exception $e) {
            $this->error('❌ Test execution failed:');
            $this->error($e->getMessage());
            $this->info('Stack trace:');
            $this->line($e->getTraceAsString());

            return 1;
        }

        return 0;
    }
}
