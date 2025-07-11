<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Recording\RecordingDataService;
use App\Services\Recording\RecordingPersistenceService;
use App\Services\Recording\RecordingValidationService;
use App\Services\Recording\RecordingCalculationService;
use App\Services\Recording\RecordsIntegrationService;

class TestModularServices extends Command
{
    protected $signature = 'recording:test-modular 
                            {--livestock-id= : Test with specific livestock ID}';

    protected $description = 'Test modular recording services availability and functionality';

    public function handle()
    {
        $this->info('🧪 Testing Modular Recording Services...');

        try {
            // Check configuration
            $useModular = config('recording.features.use_modular_services');
            $useFallback = config('recording.features.use_legacy_fallback');

            $this->info("Configuration:");
            $this->line("  - use_modular_services: " . ($useModular ? 'true' : 'false'));
            $this->line("  - use_legacy_fallback: " . ($useFallback ? 'true' : 'false'));

            if (!$useModular) {
                $this->error('❌ Modular services are disabled in configuration.');
                return 1;
            }

            // Test service availability
            $this->info("\n🔍 Testing Service Availability:");

            $services = [
                'RecordingDataService' => RecordingDataService::class,
                'RecordingPersistenceService' => RecordingPersistenceService::class,
                'RecordingValidationService' => RecordingValidationService::class,
                'RecordingCalculationService' => RecordingCalculationService::class,
                'RecordsIntegrationService' => RecordsIntegrationService::class,
            ];

            $availableServices = [];

            foreach ($services as $name => $class) {
                try {
                    $service = app($class);
                    $availableServices[$name] = $service;
                    $this->info("  ✅ {$name}: Available");
                } catch (\Exception $e) {
                    $this->error("  ❌ {$name}: Not available - " . $e->getMessage());
                }
            }

            if (empty($availableServices)) {
                $this->error('❌ No modular services are available.');
                return 1;
            }

            $this->info("\n✅ Modular services test completed successfully!");
            $this->info("Available services: " . count($availableServices) . "/" . count($services));

            // Test with specific livestock if provided
            $livestockId = $this->option('livestock-id');
            if ($livestockId) {
                $this->info("\n🔍 Testing with livestock ID: {$livestockId}");

                try {
                    $dataService = app(RecordingDataService::class);
                    $data = $dataService->loadCurrentDateData($livestockId, now()->format('Y-m-d'));
                    $this->info("  ✅ DataService::loadCurrentDateData: Success");
                } catch (\Exception $e) {
                    $this->error("  ❌ DataService::loadCurrentDateData: Failed - " . $e->getMessage());
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error testing modular services: ' . $e->getMessage());
            return 1;
        }
    }
}
