<?php

namespace App\Services\Recording;

use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Modular Payload Builder for Recording System
 * 
 * This class builds a modular payload structure that can accommodate
 * data from multiple components while maintaining consistency and
 * extensibility.
 */
class ModularPayloadBuilder
{
    private array $components = [];
    private array $coreData = [];
    private array $metadata = [];
    private array $livestockContext = [];
    private array $calculatedMetrics = [];
    private array $historicalData = [];
    private array $environmentData = [];
    private array $validationErrors = [];

    /**
     * Add a component to the payload
     * 
     * @param PayloadComponentInterface $component
     * @return self
     */
    public function addComponent(PayloadComponentInterface $component): self
    {
        if ($component->hasData()) {
            $this->components[$component->getComponentName()] = $component;

            Log::info('Component added to payload builder', [
                'component_name' => $component->getComponentName(),
                'has_data' => $component->hasData(),
                'priority' => $component->getPriority(),
            ]);
        } else {
            Log::info('Component skipped - no data', [
                'component_name' => $component->getComponentName(),
            ]);
        }

        return $this;
    }

    /**
     * Set core recording data
     * 
     * @param array $data
     * @return self
     */
    public function setCoreData(array $data): self
    {
        $this->coreData = $data;
        return $this;
    }

    /**
     * Set livestock context
     * 
     * @param mixed $livestock Livestock model or array
     * @param int|null $age Current age in days
     * @return self
     */
    public function setLivestockContext($livestock, ?int $age = null): self
    {
        if ($livestock instanceof Livestock) {
            $this->livestockContext = [
                'livestock_details' => [
                    'id' => $livestock->id,
                    'name' => $livestock->name,
                    'strain' => $livestock->strain ?? 'Unknown Strain',
                    'farm_id' => $livestock->farm_id,
                    'farm_name' => $livestock->farm->name ?? 'Unknown Farm',
                    'coop_id' => $livestock->coop_id,
                    'kandang_name' => $livestock->kandang->name ?? 'Unknown Kandang',
                    'start_date' => $livestock->start_date,
                    'initial_population' => $livestock->initial_quantity,
                ],
                'age_days' => $age ?? $this->calculateAge($livestock),
            ];
        } else if (is_array($livestock)) {
            $this->livestockContext = [
                'livestock_details' => $livestock,
                'age_days' => $age,
            ];
        }

        return $this;
    }

    /**
     * Set calculated metrics
     * 
     * @param array $metrics
     * @return self
     */
    public function setCalculatedMetrics(array $metrics): self
    {
        $this->calculatedMetrics = $metrics;
        return $this;
    }

    /**
     * Set historical data
     * 
     * @param array $data
     * @return self
     */
    public function setHistoricalData(array $data): self
    {
        $this->historicalData = $data;
        return $this;
    }

    /**
     * Set environment data
     * 
     * @param array $data
     * @return self
     */
    public function setEnvironmentData(array $data): self
    {
        $this->environmentData = $data;
        return $this;
    }

    /**
     * Build the final payload
     * 
     * @return array
     */
    public function build(): array
    {
        // Sort components by priority
        $sortedComponents = collect($this->components)
            ->sortBy(fn($component) => $component->getPriority())
            ->toArray();

        // Validate all components
        $this->validateComponents();

        // Build component data
        $componentData = [];
        foreach ($sortedComponents as $component) {
            if ($component->validateComponentData()) {
                $componentData[$component->getComponentName()] = $component->getComponentData();
            } else {
                $this->validationErrors = array_merge(
                    $this->validationErrors,
                    $component->getValidationErrors()
                );
            }
        }

        // Build the final payload structure
        $payload = [
            'version' => '3.0',
            'recording_metadata' => $this->buildRecordingMetadata(),
            'livestock_context' => $this->livestockContext,
            'core_recording' => $this->coreData,
            'component_data' => $componentData,
            'calculated_metrics' => $this->calculatedMetrics,
            'historical_data' => $this->historicalData,
            'environment' => $this->environmentData,
            'validation_summary' => [
                'components_count' => count($componentData),
                'has_validation_errors' => !empty($this->validationErrors),
                'validation_errors' => $this->validationErrors,
                'validated_at' => now()->toIso8601String(),
            ],
            'data_integrity' => $this->generateDataIntegrityHash($componentData),
        ];

        Log::info('Modular payload built successfully', [
            'version' => $payload['version'],
            'components_count' => count($componentData),
            'component_names' => array_keys($componentData),
            'has_errors' => !empty($this->validationErrors),
            'payload_size' => strlen(json_encode($payload)),
        ]);

        return $payload;
    }

    /**
     * Get components for inspection
     * 
     * @return array
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Check if builder has validation errors
     * 
     * @return bool
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Calculate age from livestock start date
     * 
     * @param Livestock $livestock
     * @return int
     */
    private function calculateAge(Livestock $livestock): int
    {
        if (!$livestock->start_date) {
            return 0;
        }

        return Carbon::parse($livestock->start_date)->diffInDays(Carbon::now());
    }

    /**
     * Build recording metadata
     * 
     * @return array
     */
    private function buildRecordingMetadata(): array
    {
        $user = Auth::user();

        return [
            'recorded_at' => now()->toIso8601String(),
            'recorded_by' => [
                'id' => $user ? $user->id : null,
                'name' => $user ? $user->name : 'Unknown User',
                'role' => $user && $user->roles->first() ? $user->roles->first()->name : 'Unknown Role',
            ],
            'builder_version' => '1.0',
            'payload_format' => 'modular_v3',
            'generated_by' => 'ModularPayloadBuilder',
        ];
    }

    /**
     * Validate all components
     * 
     * @return void
     */
    private function validateComponents(): void
    {
        foreach ($this->components as $component) {
            if (!$component->validateComponentData()) {
                $this->validationErrors = array_merge(
                    $this->validationErrors,
                    array_map(
                        fn($error) => "[{$component->getComponentName()}] {$error}",
                        $component->getValidationErrors()
                    )
                );
            }
        }
    }

    /**
     * Generate data integrity hash
     * 
     * @param array $componentData
     * @return string
     */
    private function generateDataIntegrityHash(array $componentData): string
    {
        $dataString = json_encode($componentData) . json_encode($this->coreData);
        return hash('sha256', $dataString);
    }

    /**
     * Create a new builder instance
     * 
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add multiple components at once
     * 
     * @param array $components
     * @return self
     */
    public function addComponents(array $components): self
    {
        foreach ($components as $component) {
            if ($component instanceof PayloadComponentInterface) {
                $this->addComponent($component);
            }
        }

        return $this;
    }

    /**
     * Remove a component
     * 
     * @param string $componentName
     * @return self
     */
    public function removeComponent(string $componentName): self
    {
        if (isset($this->components[$componentName])) {
            unset($this->components[$componentName]);

            Log::info('Component removed from payload builder', [
                'component_name' => $componentName,
            ]);
        }

        return $this;
    }

    /**
     * Check if a component exists
     * 
     * @param string $componentName
     * @return bool
     */
    public function hasComponent(string $componentName): bool
    {
        return isset($this->components[$componentName]);
    }

    /**
     * Get a specific component
     * 
     * @param string $componentName
     * @return PayloadComponentInterface|null
     */
    public function getComponent(string $componentName): ?PayloadComponentInterface
    {
        return $this->components[$componentName] ?? null;
    }

    /**
     * Clear all components
     * 
     * @return self
     */
    public function clearComponents(): self
    {
        $this->components = [];
        return $this;
    }

    /**
     * Get summary of builder state
     * 
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'components_count' => count($this->components),
            'component_names' => array_keys($this->components),
            'has_core_data' => !empty($this->coreData),
            'has_livestock_context' => !empty($this->livestockContext),
            'has_calculated_metrics' => !empty($this->calculatedMetrics),
            'has_historical_data' => !empty($this->historicalData),
            'has_environment_data' => !empty($this->environmentData),
            'validation_errors_count' => count($this->validationErrors),
        ];
    }
}
