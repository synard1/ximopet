<?php

namespace App\Services\Recording;

use App\Models\Livestock;
use App\Models\Recording;
use App\Models\LivestockDepletion;
use App\Models\FeedUsage;
use App\Config\LivestockDepletionConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Helper service untuk menangani transisi metode pencatatan
 * Memastikan metadata tracking yang proper dan backward compatibility
 */
class RecordingMethodTransitionHelper
{
    /**
     * Apply configuration change dengan proper transition handling
     */
    public function applyConfigurationChange(Livestock $livestock, array $newConfig, array $validationResult): array
    {
        try {
            DB::beginTransaction();

            Log::info('🔄 RecordingMethodTransition: Starting configuration change', [
                'livestock_id' => $livestock->id,
                'new_config' => $newConfig
            ]);

            $backup = $this->createConfigurationBackup($livestock);
            $configResult = $this->applyNewConfiguration($livestock, $newConfig, $backup);
            $metadataResult = $this->updateMetadataTracking($livestock, $newConfig, $backup);
            $transitionRecord = $this->createTransitionRecord($livestock, $newConfig, $backup, $validationResult);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Configuration change applied successfully',
                'backup' => $backup,
                'transition_record' => $transitionRecord,
                'new_config' => $configResult,
                'metadata_update' => $metadataResult
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ RecordingMethodTransition: Configuration change failed', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Configuration change failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create backup of current configuration
     */
    private function createConfigurationBackup(Livestock $livestock): array
    {
        $currentConfig = $livestock->getConfiguration();
        $backup = [
            'id' => uniqid('backup_'),
            'livestock_id' => $livestock->id,
            'timestamp' => now()->toIso8601String(),
            'config' => $currentConfig,
            'created_by' => auth()->id()
        ];

        $livestock->updateDataColumn('config_backups', array_merge(
            $livestock->getDataColumn('config_backups') ?? [],
            [$backup]
        ));

        return $backup;
    }

    /**
     * Apply new configuration with proper metadata
     */
    private function applyNewConfiguration(Livestock $livestock, array $newConfig, array $backup): array
    {
        $currentConfig = $livestock->getConfiguration();
        $mergedConfig = array_merge($currentConfig, $newConfig);
        $mergedConfig['last_updated'] = now()->toIso8601String();
        $mergedConfig['updated_by'] = auth()->id();
        $mergedConfig['backup_reference'] = $backup['id'];

        $livestock->updateDataColumn('config', $mergedConfig);
        return $mergedConfig;
    }

    /**
     * Update metadata tracking for existing records
     */
    private function updateMetadataTracking(Livestock $livestock, array $newConfig, array $backup): array
    {
        $updatedRecords = ['recordings' => 0, 'depletions' => 0, 'feed_usages' => 0];

        $recordings = Recording::where('livestock_id', $livestock->id)->get();
        foreach ($recordings as $recording) {
            $payload = $recording->payload ?? [];
            $payload['method_transition'] = [
                'transition_id' => $backup['id'],
                'pre_transition_method' => $backup['config'],
                'post_transition_method' => $newConfig,
                'transition_timestamp' => now()->toIso8601String()
            ];
            $recording->update(['payload' => $payload]);
            $updatedRecords['recordings']++;
        }

        return $updatedRecords;
    }

    /**
     * Create transition record for audit trail
     */
    private function createTransitionRecord(Livestock $livestock, array $newConfig, array $backup, array $validationResult): array
    {
        $transitionRecord = [
            'id' => uniqid('transition_'),
            'livestock_id' => $livestock->id,
            'timestamp' => now()->toIso8601String(),
            'from_config' => $backup['config'],
            'to_config' => $newConfig,
            'validation_result' => $validationResult,
            'applied_by' => auth()->id()
        ];

        $livestock->updateDataColumn('transition_history', array_merge(
            $livestock->getDataColumn('transition_history') ?? [],
            [$transitionRecord]
        ));

        return $transitionRecord;
    }

    /**
     * Rollback configuration to a previous backup
     */
    public function rollbackConfiguration(Livestock $livestock, string $backupId): array
    {
        try {
            DB::beginTransaction();

            $backups = $livestock->getDataColumn('config_backups') ?? [];
            $targetBackup = collect($backups)->firstWhere('id', $backupId);

            if (!$targetBackup) {
                throw new Exception("Backup with ID {$backupId} not found");
            }

            // Create backup of current config before rollback
            $currentBackup = $this->createConfigurationBackup($livestock);

            // Apply the backup configuration
            $livestock->updateDataColumn('config', $targetBackup['config']);

            // Create rollback record
            $rollbackRecord = [
                'id' => uniqid('rollback_'),
                'livestock_id' => $livestock->id,
                'timestamp' => now()->toIso8601String(),
                'rollback_to_backup_id' => $backupId,
                'rollback_from_backup_id' => $currentBackup['id'],
                'rolled_back_by' => auth()->id(),
                'rolled_back_by_name' => auth()->user()->name ?? 'Unknown User'
            ];

            $livestock->updateDataColumn('rollback_history', array_merge(
                $livestock->getDataColumn('rollback_history') ?? [],
                [$rollbackRecord]
            ));

            DB::commit();

            Log::info('🔄 Configuration rollback completed', [
                'livestock_id' => $livestock->id,
                'rollback_id' => $rollbackRecord['id'],
                'target_backup_id' => $backupId
            ]);

            return [
                'success' => true,
                'message' => 'Configuration rollback completed successfully',
                'rollback_record' => $rollbackRecord,
                'restored_config' => $targetBackup['config']
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('❌ Configuration rollback failed', [
                'livestock_id' => $livestock->id,
                'backup_id' => $backupId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Configuration rollback failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get configuration history for a livestock
     */
    public function getConfigurationHistory(Livestock $livestock): array
    {
        return [
            'current_config' => $livestock->getConfiguration(),
            'backups' => $livestock->getDataColumn('config_backups') ?? [],
            'transitions' => $livestock->getDataColumn('transition_history') ?? [],
            'rollbacks' => $livestock->getDataColumn('rollback_history') ?? []
        ];
    }

    /**
     * Validate that records created after transition use correct method
     */
    public function validatePostTransitionRecords(Livestock $livestock, string $transitionId): array
    {
        $validationResults = [
            'valid' => true,
            'issues' => [],
            'records_checked' => 0
        ];

        // Get transition record
        $transitions = $livestock->getDataColumn('transition_history') ?? [];
        $transition = collect($transitions)->firstWhere('id', $transitionId);

        if (!$transition) {
            return [
                'valid' => false,
                'message' => "Transition record {$transitionId} not found"
            ];
        }

        $transitionTime = Carbon::parse($transition['timestamp']);
        $expectedConfig = $transition['to_config'];

        // Check depletion records created after transition
        if (isset($expectedConfig['depletion_method'])) {
            $postTransitionDepletions = LivestockDepletion::where('livestock_id', $livestock->id)
                ->where('created_at', '>', $transitionTime)
                ->get();

            foreach ($postTransitionDepletions as $depletion) {
                $metadata = $depletion->metadata ?? [];
                $recordMethod = $metadata['depletion_method'] ?? 'unknown';

                if ($recordMethod !== $expectedConfig['depletion_method']) {
                    $validationResults['issues'][] = [
                        'type' => 'depletion_method_mismatch',
                        'record_id' => $depletion->id,
                        'expected_method' => $expectedConfig['depletion_method'],
                        'actual_method' => $recordMethod
                    ];
                    $validationResults['valid'] = false;
                }
                $validationResults['records_checked']++;
            }
        }

        // Check feed usage records created after transition
        if (isset($expectedConfig['feed_usage_method'])) {
            $postTransitionFeedUsages = FeedUsage::where('livestock_id', $livestock->id)
                ->where('created_at', '>', $transitionTime)
                ->get();

            foreach ($postTransitionFeedUsages as $feedUsage) {
                $metadata = $feedUsage->metadata ?? [];
                $recordMethod = $metadata['feed_usage_method'] ?? 'unknown';

                if ($recordMethod !== $expectedConfig['feed_usage_method']) {
                    $validationResults['issues'][] = [
                        'type' => 'feed_usage_method_mismatch',
                        'record_id' => $feedUsage->id,
                        'expected_method' => $expectedConfig['feed_usage_method'],
                        'actual_method' => $recordMethod
                    ];
                    $validationResults['valid'] = false;
                }
                $validationResults['records_checked']++;
            }
        }

        return $validationResults;
    }

    /**
     * Generate method indicator for UI display
     */
    public function getMethodIndicator(Livestock $livestock): array
    {
        $config = $livestock->getConfiguration();

        return [
            'depletion_method' => [
                'current' => $config['depletion_method'] ?? 'manual',
                'display_name' => $this->getMethodDisplayName($config['depletion_method'] ?? 'manual'),
                'color' => $this->getMethodColor($config['depletion_method'] ?? 'manual')
            ],
            'feed_usage_method' => [
                'current' => $config['feed_usage_method'] ?? 'manual',
                'display_name' => $this->getMethodDisplayName($config['feed_usage_method'] ?? 'manual'),
                'color' => $this->getMethodColor($config['feed_usage_method'] ?? 'manual')
            ]
        ];
    }

    private function getMethodDisplayName(string $method): string
    {
        return match ($method) {
            'manual' => 'Manual Entry',
            'fifo' => 'FIFO Processing',
            'traditional' => 'Traditional',
            'total' => 'Total Method',
            default => ucfirst($method)
        };
    }

    private function getMethodColor(string $method): string
    {
        return match ($method) {
            'manual' => 'blue',
            'fifo' => 'green',
            'traditional' => 'gray',
            'total' => 'purple',
            default => 'gray'
        };
    }
}
