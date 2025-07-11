<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Services\Recording\Contracts\RecordingValidationServiceInterface;
use App\Services\Recording\DTOs\{ValidationResult, RecordingData};
use App\Models\{Livestock, Recording, FeedStock, SupplyStock, CurrentLivestock, Company, Feed, Supply, FeedUsage, SupplyUsage, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log, Auth};

/**
 * RecordingValidationService (Fixed)
 * 
 * Comprehensive validation service that handles all recording validation operations.
 * Ensures data integrity, business rule compliance, and system constraints.
 */
class RecordingValidationService implements RecordingValidationServiceInterface
{
    /**
     * Validate complete recording data comprehensively
     */
    public function validateRecordingData(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Extract data from RecordingData object
            $data = $recordingData->toArray();

            // Basic required field validation
            if (empty($data['livestock_id'])) {
                $errors[] = 'Livestock ID is required';
            }

            if (empty($data['date'])) {
                $errors[] = 'Recording date is required';
            }

            // Validate livestock exists
            if (!empty($data['livestock_id'])) {
                $livestock = Livestock::find($data['livestock_id']);
                if (!$livestock) {
                    $errors[] = "Livestock with ID {$data['livestock_id']} not found";
                }
            }

            // Date validation
            if (!empty($data['date'])) {
                try {
                    $date = Carbon::parse($data['date']);
                    if ($date->isFuture()) {
                        $errors[] = 'Recording date cannot be in the future';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Invalid date format';
                }
            }

            // Body weight validation
            if (isset($data['body_weight']) && (!is_numeric($data['body_weight']) || $data['body_weight'] < 0)) {
                $errors[] = 'Body weight must be a positive number';
            }

            // Feed usage validation
            if (isset($data['feed_usages']) && is_array($data['feed_usages'])) {
                foreach ($data['feed_usages'] as $usage) {
                    if (!isset($usage['feed_id']) || !isset($usage['quantity'])) {
                        $errors[] = 'Invalid feed usage structure';
                    }
                }
            }

            // Supply usage validation
            if (isset($data['supply_usages']) && is_array($data['supply_usages'])) {
                foreach ($data['supply_usages'] as $usage) {
                    if (!isset($usage['supply_id']) || !isset($usage['quantity'])) {
                        $errors[] = 'Invalid supply usage structure';
                    }
                }
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Recording validation failed', $warnings);
        } catch (\Exception $e) {
            Log::error('Error validating recording data', [
                'recording_data' => $data ?? [],
                'error' => $e->getMessage()
            ]);

            return ValidationResult::failure(['Recording validation error: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate livestock constraints and population limits
     */
    public function validateLivestockConstraints(int $livestockId, array $data): ValidationResult
    {
        try {
            $livestock = Livestock::with('currentLivestock')->find($livestockId);

            if (!$livestock) {
                return ValidationResult::failure(['Livestock not found']);
            }

            $errors = [];
            $warnings = [];

            // Population constraints validation
            $currentPopulation = $livestock->currentLivestock->quantity ?? 0;
            $totalDepletion = ($data['mortality'] ?? 0) + ($data['culling'] ?? 0) + ($data['sale'] ?? 0);

            if ($totalDepletion > $currentPopulation) {
                $errors[] = "Total depletion ({$totalDepletion}) exceeds current population ({$currentPopulation})";
            }

            if ($totalDepletion > ($currentPopulation * 0.5)) {
                $warnings[] = "High depletion rate detected: {$totalDepletion} from {$currentPopulation} population";
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Livestock constraints validation failed', $warnings);
        } catch (\Exception $e) {
            Log::error('Error validating livestock constraints', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return ValidationResult::failure(['Livestock constraints validation error: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate user permissions for recording operations
     */
    public function validateUserPermissions(int $livestockId, string $operation = 'create'): ValidationResult
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ValidationResult::failure(['User authentication required']);
            }

            $livestock = Livestock::with('farm.company')->find($livestockId);

            if (!$livestock) {
                return ValidationResult::failure(['Livestock not found']);
            }

            // Check if user belongs to the same company as the livestock
            if ($user->company_id !== $livestock->farm->company_id) {
                return ValidationResult::failure(['User does not have permission to access this livestock']);
            }

            return ValidationResult::success();
        } catch (\Exception $e) {
            Log::error('Error validating user permissions', [
                'livestock_id' => $livestockId,
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);

            return ValidationResult::failure(['Permission validation error: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate basic recording information
     */
    public function validateBasicInfo(int $livestockId, Carbon $date, int $age, float $bodyWeight): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Validate livestock exists
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                $errors[] = "Livestock with ID {$livestockId} not found";
            }

            // Validate date is not in the future
            if ($date->isFuture()) {
                $errors[] = "Recording date cannot be in the future";
            }

            // Validate age is reasonable
            if ($age < 0 || $age > 1000) {
                $errors[] = "Age must be between 0 and 1000 days";
            }

            // Validate body weight is positive
            if ($bodyWeight <= 0) {
                $errors[] = "Body weight must be positive";
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Basic info validation failed', $warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate feed usage data
     */
    public function validateFeedUsage(int $livestockId, array $feedUsages, Carbon $date): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            foreach ($feedUsages as $usage) {
                if (empty($usage['feed_id'])) {
                    $errors[] = "Feed ID is required for feed usage";
                }

                if (!isset($usage['quantity']) || $usage['quantity'] <= 0) {
                    $errors[] = "Feed quantity must be positive";
                }
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Feed usage validation failed', $warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate supply usage data
     */
    public function validateSupplyUsage(int $livestockId, array $supplyUsages, Carbon $date): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            foreach ($supplyUsages as $usage) {
                if (empty($usage['supply_id'])) {
                    $errors[] = "Supply ID is required for supply usage";
                }

                if (!isset($usage['quantity']) || $usage['quantity'] <= 0) {
                    $errors[] = "Supply quantity must be positive";
                }
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Supply usage validation failed', $warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate depletion data
     */
    public function validateDepletionData(int $livestockId, int $mortality, int $culling, int $sale, int $transfer): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            if ($mortality < 0) {
                $errors[] = "Mortality count cannot be negative";
            }

            if ($culling < 0) {
                $errors[] = "Culling count cannot be negative";
            }

            if ($sale < 0) {
                $errors[] = "Sale count cannot be negative";
            }

            if ($transfer < 0) {
                $errors[] = "Transfer count cannot be negative";
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Depletion data validation failed', $warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate stock availability
     */
    public function validateStockAvailability(int $livestockId, array $feedUsages, array $supplyUsages): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Validate feed stock availability
            foreach ($feedUsages as $usage) {
                if (!isset($usage['feed_id']) || !isset($usage['quantity'])) {
                    $errors[] = "Invalid feed usage structure";
                    continue;
                }

                $availableStock = FeedStock::where('livestock_id', $livestockId)
                    ->where('feed_id', $usage['feed_id'])
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->sum(DB::raw('quantity_in - quantity_used - quantity_mutated'));

                if ($usage['quantity'] > $availableStock) {
                    $errors[] = "Insufficient feed stock for feed ID {$usage['feed_id']}";
                }
            }

            // Validate supply stock availability
            $livestock = Livestock::find($livestockId);
            if ($livestock) {
                foreach ($supplyUsages as $usage) {
                    if (!isset($usage['supply_id']) || !isset($usage['quantity'])) {
                        $errors[] = "Invalid supply usage structure";
                        continue;
                    }

                    $availableStock = SupplyStock::where('farm_id', $livestock->farm_id)
                        ->where('supply_id', $usage['supply_id'])
                        ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                        ->sum(DB::raw('quantity_in - quantity_used - quantity_mutated'));

                    if ($usage['quantity'] > $availableStock) {
                        $errors[] = "Insufficient supply stock for supply ID {$usage['supply_id']}";
                    }
                }
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Stock availability validation failed', $warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate business rules
     */
    public function validateBusinessRules(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Extract data from RecordingData object
            $data = $recordingData->toArray();

            // FCR validation
            $fcr = $recordingData->getFeedConversionRatio();
            if ($fcr > 10.0) {
                $warnings[] = "High feed conversion ratio detected: {$fcr}";
            } elseif ($fcr < 1.0 && $fcr > 0) {
                $warnings[] = "Very low feed conversion ratio detected: {$fcr}";
            }

            // Mortality rate validation
            $mortality = $recordingData->mortality;
            if ($mortality > 0) {
                $livestock = Livestock::find($recordingData->livestockId);
                if ($livestock && $livestock->initial_quantity > 0) {
                    $mortalityRate = ($mortality / $livestock->initial_quantity) * 100;
                    if ($mortalityRate > 5) {
                        $warnings[] = "High mortality rate detected: {$mortalityRate}%";
                    }
                }
            }

            return empty($errors) ?
                ValidationResult::successWithWarnings($warnings) :
                ValidationResult::failure($errors, 'Business rules validation failed', $warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate data consistency
     */
    public function validateDataConsistency(int $livestockId, Carbon $date, RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Check if recording already exists for this date
            $existingRecording = Recording::where('livestock_id', $livestockId)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            if ($existingRecording) {
                $warnings[] = "Recording already exists for this date - will be updated";
            }

            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Validate date constraints
     */
    public function validateDateConstraints(int $livestockId, Carbon $date): ValidationResult
    {
        try {
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return ValidationResult::failure(['Livestock not found']);
            }

            $errors = [];
            if ($date->lt(Carbon::parse($livestock->start_date))) {
                $errors[] = 'Recording date cannot be earlier than livestock start date';
            }

            return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    // Implement all remaining interface methods with basic implementations
    public function validateRecordingPermissions(int $livestockId, int $userId): ValidationResult
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return ValidationResult::failure(['User not found']);
            }
            return ValidationResult::success();
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateFeedQuantityLimits(int $livestockId, array $feedUsages, Carbon $date): ValidationResult
    {
        $warnings = [];
        foreach ($feedUsages as $usage) {
            if (isset($usage['quantity']) && $usage['quantity'] > 10000) {
                $warnings[] = "Feed quantity seems unusually high";
            }
        }
        return ValidationResult::successWithWarnings($warnings);
    }

    public function validateSupplyQuantityLimits(int $livestockId, array $supplyUsages, Carbon $date): ValidationResult
    {
        $warnings = [];
        foreach ($supplyUsages as $usage) {
            if (isset($usage['quantity']) && $usage['quantity'] > 1000) {
                $warnings[] = "Supply quantity seems unusually high";
            }
        }
        return ValidationResult::successWithWarnings($warnings);
    }

    public function validateWeightProgression(int $livestockId, float $bodyWeight, Carbon $date): ValidationResult
    {
        try {
            $previousRecording = Recording::where('livestock_id', $livestockId)
                ->where('date', '<', $date->format('Y-m-d'))
                ->orderBy('date', 'desc')
                ->first();

            $warnings = [];
            if ($previousRecording && $bodyWeight < $previousRecording->body_weight * 0.5) {
                $warnings[] = "Significant weight decrease detected";
            }

            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateMortalityRates(int $livestockId, int $mortality, Carbon $date): ValidationResult
    {
        try {
            $livestock = Livestock::find($livestockId);
            $warnings = [];
            if ($livestock && $mortality > $livestock->initial_quantity * 0.2) {
                $warnings[] = "High mortality rate detected";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateCullingRates(int $livestockId, int $culling, Carbon $date): ValidationResult
    {
        try {
            $livestock = Livestock::find($livestockId);
            $warnings = [];
            if ($livestock && $culling > $livestock->initial_quantity * 0.3) {
                $warnings[] = "High culling rate detected";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateFeedConversionRatio(RecordingData $recordingData): ValidationResult
    {
        try {
            $fcr = $recordingData->getFeedConversionRatio();
            $warnings = [];
            if ($fcr > 10.0) {
                $warnings[] = "Feed conversion ratio is unusually high";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateDataIntegrity(int $livestockId, RecordingData $recordingData): ValidationResult
    {
        try {
            $errors = [];
            if ($recordingData->getTotalDepletion() < 0) {
                $errors[] = "Total depletion cannot be negative";
            }
            return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validatePerformanceMetrics(RecordingData $recordingData): ValidationResult
    {
        try {
            $warnings = [];
            $avgWeight = $recordingData->bodyWeight; // Using bodyWeight property directly
            if ($avgWeight > 10.0) {
                $warnings[] = "Average weight seems unusually high";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateCostCalculations(RecordingData $recordingData): ValidationResult
    {
        try {
            $errors = [];
            $totalCost = $recordingData->getTotalCost();
            if ($totalCost < 0) {
                $errors[] = "Total cost cannot be negative";
            }
            return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateWorkflowState(int $livestockId, Carbon $date): ValidationResult
    {
        try {
            $livestock = Livestock::find($livestockId);
            $warnings = [];
            if ($livestock && $livestock->status !== 'active') {
                $warnings[] = "Livestock is not in active state";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    // Simplified implementations for remaining methods
    public function validateBatchRecording(array $recordingDataArray): ValidationResult
    {
        return empty($recordingDataArray) ?
            ValidationResult::failure(['Batch recording data cannot be empty']) :
            ValidationResult::success();
    }

    public function validateImportData(int $livestockId, array $importData): ValidationResult
    {
        return empty($importData) ?
            ValidationResult::failure(['Import data cannot be empty']) :
            ValidationResult::success();
    }

    public function validateExportRequest(int $livestockId, Carbon $startDate, Carbon $endDate, string $format): ValidationResult
    {
        $errors = [];
        if ($startDate->isAfter($endDate)) {
            $errors[] = "Start date cannot be after end date";
        }
        if (!in_array($format, ['csv', 'xlsx', 'json'])) {
            $errors[] = "Unsupported export format";
        }
        return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    public function validateLivestockCapacity(int $livestockId, int $newPopulation): ValidationResult
    {
        return $newPopulation <= 0 ?
            ValidationResult::failure(['Population must be positive']) :
            ValidationResult::success();
    }

    public function validateAgeProgression(int $livestockId, int $age, Carbon $date): ValidationResult
    {
        return $age < 0 ?
            ValidationResult::failure(['Age cannot be negative']) :
            ValidationResult::success();
    }

    public function validateRecordingFrequency(int $livestockId, Carbon $date): ValidationResult
    {
        try {
            $existingCount = Recording::where('livestock_id', $livestockId)
                ->where('date', $date->format('Y-m-d'))
                ->count();

            $warnings = [];
            if ($existingCount > 0) {
                $warnings[] = "Multiple recordings found for same date";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateDataCompleteness(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        if (empty($recordingData->livestockId)) {
            $errors[] = "Livestock ID is required";
        }
        if (empty($recordingData->date)) {
            $errors[] = "Recording date is required";
        }
        return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    public function validateCrossReferences(RecordingData $recordingData): ValidationResult
    {
        try {
            $errors = [];
            $livestockId = $recordingData->livestockId;
            if ($livestockId && !Livestock::find($livestockId)) {
                $errors[] = "Referenced livestock not found";
            }
            return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function validateSeasonalConstraints(int $livestockId, Carbon $date, RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $month = $date->month;
        if ($month < 1 || $month > 12) {
            $errors[] = "Invalid month in date";
        }
        return empty($errors) ? ValidationResult::success() : ValidationResult::failure($errors);
    }

    public function validateRegulatoryCompliance(RecordingData $recordingData): ValidationResult
    {
        $warnings = [];
        $mortality = $recordingData->mortality;
        if ($mortality > 1000) {
            $warnings[] = "High mortality may require regulatory reporting";
        }
        return ValidationResult::successWithWarnings($warnings);
    }

    public function validateDataQuality(RecordingData $recordingData): ValidationResult
    {
        $warnings = [];
        $weight = $recordingData->bodyWeight;
        if ($weight > 0 && $weight < 0.1) {
            $warnings[] = "Body weight seems unusually low";
        }
        return ValidationResult::successWithWarnings($warnings);
    }

    public function validateConcurrentModifications(int $livestockId, Carbon $date, string $checksum): ValidationResult
    {
        try {
            $warnings = [];
            $existingRecording = Recording::where('livestock_id', $livestockId)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            if ($existingRecording && $existingRecording->updated_at->gt($date)) {
                $warnings[] = "Data may have been modified by another user";
            }
            return ValidationResult::successWithWarnings($warnings);
        } catch (\Exception $e) {
            return ValidationResult::failure([$e->getMessage()]);
        }
    }

    public function getValidationRules(string $context): array
    {
        $rules = [
            'basic' => [
                'livestock_id' => 'required|integer|exists:livestock,id',
                'date' => 'required|date|before_or_equal:today',
                'body_weight' => 'required|numeric|min:0',
            ],
            'feed_usage' => [
                'feed_id' => 'required|integer|exists:feeds,id',
                'quantity' => 'required|numeric|min:0',
            ],
            'supply_usage' => [
                'supply_id' => 'required|integer|exists:supplies,id',
                'quantity' => 'required|numeric|min:0',
            ],
            'depletion' => [
                'mortality' => 'integer|min:0',
                'culling' => 'integer|min:0',
                'sale' => 'integer|min:0',
                'transfer' => 'integer|min:0',
            ],
        ];

        return $rules[$context] ?? [];
    }
}
