<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Recording\Contracts\RecordingDataServiceInterface;
use App\Models\Livestock;
use Carbon\Carbon;

class TestModularDataLoadingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:modular-data-loading {livestock_id} {--date= : The date to test in YYYY-MM-DD format. Defaults to today.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performs an isolated test of the new modular RecordingDataService.';

    /**
     * Execute the console command.
     */
    public function handle(RecordingDataServiceInterface $dataService)
    {
        $livestockId = $this->argument('livestock_id');
        if (!Livestock::find($livestockId)) {
            $this->error("Livestock with ID {$livestockId} not found.");
            return 1;
        }

        $dateInput = $this->option('date');
        $testDate = $dateInput ? Carbon::parse($dateInput) : Carbon::today();

        $this->info("--- Starting Test for RecordingDataService ---");
        $this->line("Livestock ID: {$livestockId}");
        $this->line("Testing for Date: <fg=yellow>{$testDate->format('Y-m-d')}</>");

        // --- Test 1: loadCurrentDateData ---
        $this->testLoadCurrentDateData($dataService, $livestockId, $testDate);

        // --- Test 2: loadYesterdayData ---
        $this->testLoadYesterdayData($dataService, $livestockId, $testDate);

        // --- Test 3: loadRecordingDataForTable ---
        $this->testLoadRecordingDataForTable($dataService, $livestockId);

        $this->info("--- Test Completed ---");

        return 0;
    }

    private function testLoadCurrentDateData(RecordingDataServiceInterface $dataService, string $livestockId, Carbon $testDate)
    {
        $this->line("\n[1] Testing loadCurrentDateData for date: <fg=yellow>{$testDate->format('Y-m-d')}</>");
        $date = $testDate->format('Y-m-d');
        $result = $dataService->loadCurrentDateData($livestockId, $date);

        $this->printServiceResult($result);
        if ($result->isSuccess()) {
            $this->line("Data Sample: " . json_encode(array_slice($result->getData(), 0, 3)));
        }
    }

    private function testLoadYesterdayData(RecordingDataServiceInterface $dataService, string $livestockId, Carbon $testDate)
    {
        $yesterday = $testDate->copy()->subDay();
        $this->line("\n[2] Testing loadYesterdayData for date: <fg=yellow>{$yesterday->format('Y-m-d')}</>");
        $yesterdayString = $yesterday->format('Y-m-d');
        $result = $dataService->loadYesterdayData($livestockId, $yesterdayString);

        $this->printServiceResult($result);
        if ($result->isSuccess()) {
            $this->line("Data Sample: " . json_encode($result->getData()));
        }
    }

    private function testLoadRecordingDataForTable(RecordingDataServiceInterface $dataService, string $livestockId)
    {
        $this->line("\n[3] Testing loadRecordingDataForTable...");
        $result = $dataService->loadRecordingDataForTable($livestockId);

        $this->printServiceResult($result);
        if ($result->isSuccess()) {
            $recordings = $result->getData()['recordings'];
            $this->line("Total records found: " . count($recordings));
            if (!empty($recordings)) {
                $this->line("First record sample: " . json_encode($recordings[0]));
            }
        }
    }

    private function printServiceResult($result)
    {
        if ($result->isSuccess()) {
            $this->info("✅ SUCCESS: " . $result->getMessage());
        } else {
            $this->error("❌ ERROR: " . $result->getMessage());
            if ($result->getException()) {
                $this->comment("Exception: " . $result->getException()->getMessage());
            }
        }
    }
}
