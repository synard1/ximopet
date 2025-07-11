<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Services\Recording\DTOs\RecordingData;
use App\Services\Recording\DTOs\ValidationResult;
use Carbon\Carbon;

/**
 * RecordingValidationServiceInterface
 * 
 * Contract for recording validation service that handles all validation operations.
 * Ensures data integrity, business rule compliance, and system constraints.
 */
interface RecordingValidationServiceInterface
{
    /**
     * Validate complete recording data comprehensively.
     * This is the main validation method that orchestrates all validation checks.
     *
     * @param RecordingData $recordingData
     * @return ValidationResult
     */
    public function validateRecordingData(RecordingData $recordingData): ValidationResult;

    /**
     * Validate basic recording information
     */
    public function validateBasicInfo(int $livestockId, Carbon $date, int $age, float $bodyWeight): ValidationResult;

    /**
     * Validate feed usage data
     */
    public function validateFeedUsage(int $livestockId, array $feedUsages, Carbon $date): ValidationResult;

    /**
     * Validate supply usage data
     */
    public function validateSupplyUsage(int $livestockId, array $supplyUsages, Carbon $date): ValidationResult;

    /**
     * Validate depletion data
     */
    public function validateDepletionData(int $livestockId, int $mortality, int $culling, int $sale, int $transfer): ValidationResult;

    /**
     * Validate stock availability
     */
    public function validateStockAvailability(int $livestockId, array $feedUsages, array $supplyUsages): ValidationResult;

    /**
     * Validate business rules
     */
    public function validateBusinessRules(RecordingData $recordingData): ValidationResult;

    /**
     * Validate data consistency
     */
    public function validateDataConsistency(int $livestockId, Carbon $date, RecordingData $recordingData): ValidationResult;

    /**
     * Validate recording permissions
     */
    public function validateRecordingPermissions(int $livestockId, int $userId): ValidationResult;

    /**
     * Validate date constraints
     */
    public function validateDateConstraints(int $livestockId, Carbon $date): ValidationResult;

    /**
     * Validate feed quantity limits
     */
    public function validateFeedQuantityLimits(int $livestockId, array $feedUsages, Carbon $date): ValidationResult;

    /**
     * Validate supply quantity limits
     */
    public function validateSupplyQuantityLimits(int $livestockId, array $supplyUsages, Carbon $date): ValidationResult;

    /**
     * Validate weight progression
     */
    public function validateWeightProgression(int $livestockId, float $bodyWeight, Carbon $date): ValidationResult;

    /**
     * Validate mortality rates
     */
    public function validateMortalityRates(int $livestockId, int $mortality, Carbon $date): ValidationResult;

    /**
     * Validate culling rates
     */
    public function validateCullingRates(int $livestockId, int $culling, Carbon $date): ValidationResult;

    /**
     * Validate feed conversion ratio
     */
    public function validateFeedConversionRatio(RecordingData $recordingData): ValidationResult;

    /**
     * Validate data integrity
     */
    public function validateDataIntegrity(int $livestockId, RecordingData $recordingData): ValidationResult;

    /**
     * Validate performance metrics
     */
    public function validatePerformanceMetrics(RecordingData $recordingData): ValidationResult;

    /**
     * Validate cost calculations
     */
    public function validateCostCalculations(RecordingData $recordingData): ValidationResult;

    /**
     * Validate workflow state
     */
    public function validateWorkflowState(int $livestockId, Carbon $date): ValidationResult;

    /**
     * Validate batch recording
     */
    public function validateBatchRecording(array $recordingDataArray): ValidationResult;

    /**
     * Validate import data
     */
    public function validateImportData(int $livestockId, array $importData): ValidationResult;

    /**
     * Validate export request
     */
    public function validateExportRequest(int $livestockId, Carbon $startDate, Carbon $endDate, string $format): ValidationResult;

    /**
     * Validate livestock capacity
     */
    public function validateLivestockCapacity(int $livestockId, int $newPopulation): ValidationResult;

    /**
     * Validate age progression
     */
    public function validateAgeProgression(int $livestockId, int $age, Carbon $date): ValidationResult;

    /**
     * Validate recording frequency
     */
    public function validateRecordingFrequency(int $livestockId, Carbon $date): ValidationResult;

    /**
     * Validate data completeness
     */
    public function validateDataCompleteness(RecordingData $recordingData): ValidationResult;

    /**
     * Validate cross-references
     */
    public function validateCrossReferences(RecordingData $recordingData): ValidationResult;

    /**
     * Validate seasonal constraints
     */
    public function validateSeasonalConstraints(int $livestockId, Carbon $date, RecordingData $recordingData): ValidationResult;

    /**
     * Validate regulatory compliance
     */
    public function validateRegulatoryCompliance(RecordingData $recordingData): ValidationResult;

    /**
     * Validate data quality
     */
    public function validateDataQuality(RecordingData $recordingData): ValidationResult;

    /**
     * Validate concurrent modifications
     */
    public function validateConcurrentModifications(int $livestockId, Carbon $date, string $checksum): ValidationResult;

    /**
     * Get validation rules for specific context
     */
    public function getValidationRules(string $context): array;

    /**
     * Validate livestock constraints and population limits
     * This method is used by the orchestrator and tests
     */
    public function validateLivestockConstraints(int $livestockId, array $data): ValidationResult;

    /**
     * Validate user permissions for recording operations
     * This method is used by the provider for permission checks
     */
    public function validateUserPermissions(int $livestockId, string $operation = 'create'): ValidationResult;
}
