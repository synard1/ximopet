<?php

declare(strict_types=1);

namespace App\Services\Recording\Processors;

use App\Services\Recording\DTOs\{RecordingData, ValidationResult};
use App\Services\Recording\Exceptions\RecordingValidationException;
use App\Models\{Livestock, Recording, FeedStock, SupplyStock, CompanyConfig};
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * RecordingValidationProcessor
 * 
 * Processor class for handling complex recording validation logic.
 * Provides comprehensive validation for recording data.
 */
class RecordingValidationProcessor
{
    /**
     * Validate recording data comprehensively
     */
    public function validateComprehensive(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Basic validation
        $basicValidation = $this->validateBasicData($recordingData);
        $errors = array_merge($errors, $basicValidation->errors);
        $warnings = array_merge($warnings, $basicValidation->warnings);

        // Business rules validation
        $businessValidation = $this->validateBusinessRules($recordingData);
        $errors = array_merge($errors, $businessValidation->errors);
        $warnings = array_merge($warnings, $businessValidation->warnings);

        // Livestock constraints validation
        $constraintValidation = $this->validateLivestockConstraints($recordingData);
        $errors = array_merge($errors, $constraintValidation->errors);
        $warnings = array_merge($warnings, $constraintValidation->warnings);

        // Feed/Supply availability validation
        $availabilityValidation = $this->validateResourceAvailability($recordingData);
        $errors = array_merge($errors, $availabilityValidation->errors);
        $warnings = array_merge($warnings, $availabilityValidation->warnings);

        // Historical data validation
        $historicalValidation = $this->validateHistoricalConsistency($recordingData);
        $errors = array_merge($errors, $historicalValidation->errors);
        $warnings = array_merge($warnings, $historicalValidation->warnings);

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate basic data
     */
    private function validateBasicData(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Required fields validation
        if (empty($recordingData->livestockId)) {
            $errors[] = 'Livestock ID is required';
        }

        if (empty($recordingData->date)) {
            $errors[] = 'Date is required';
        }

        if (empty($recordingData->age)) {
            $errors[] = 'Age is required';
        }

        if (empty($recordingData->bodyWeight)) {
            $errors[] = 'Body weight is required';
        }

        if (empty($recordingData->population)) {
            $errors[] = 'Population is required';
        }

        // Data type validation
        if ($recordingData->age && !is_numeric($recordingData->age)) {
            $errors[] = 'Age must be a number';
        }

        if ($recordingData->bodyWeight && !is_numeric($recordingData->bodyWeight)) {
            $errors[] = 'Body weight must be a number';
        }

        if ($recordingData->population && !is_numeric($recordingData->population)) {
            $errors[] = 'Population must be a number';
        }

        // Range validation
        if ($recordingData->age && ($recordingData->age < 0 || $recordingData->age > 365)) {
            $errors[] = 'Age must be between 0 and 365 days';
        }

        if ($recordingData->bodyWeight && ($recordingData->bodyWeight < 0 || $recordingData->bodyWeight > 10)) {
            $errors[] = 'Body weight must be between 0 and 10 kg';
        }

        if ($recordingData->population && ($recordingData->population < 0 || $recordingData->population > 100000)) {
            $errors[] = 'Population must be between 0 and 100,000';
        }

        // Date validation
        if ($recordingData->date) {
            $date = Carbon::parse($recordingData->date);
            $today = Carbon::today();

            if ($date->isFuture()) {
                $errors[] = 'Recording date cannot be in the future';
            }

            if ($date->lt($today->subMonths(6))) {
                $warnings[] = 'Recording date is more than 6 months old';
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Get configuration
        $config = config('recording.validation');

        // FCR validation
        if ($recordingData->feedUsage && $recordingData->bodyWeight) {
            $totalFeedConsumed = collect($recordingData->feedUsage)->sum('quantity');
            if ($totalFeedConsumed > 0) {
                $fcr = $totalFeedConsumed / $recordingData->bodyWeight;

                if ($fcr < $config['min_fcr']) {
                    $warnings[] = "FCR ({$fcr}) is unusually low";
                }

                if ($fcr > $config['max_fcr']) {
                    $errors[] = "FCR ({$fcr}) exceeds maximum allowed value";
                }
            }
        }

        // Mortality rate validation
        if ($recordingData->depletions && $recordingData->population) {
            $totalMortality = collect($recordingData->depletions)
                ->where('type', 'mortality')
                ->sum('quantity');

            $mortalityRate = ($totalMortality / $recordingData->population) * 100;

            if ($mortalityRate > $config['max_mortality_percentage']) {
                $errors[] = "Mortality rate ({$mortalityRate}%) exceeds maximum allowed";
            }
        }

        // Weight gain validation
        if ($recordingData->bodyWeight && $recordingData->livestockId) {
            $previousRecording = Recording::where('livestock_id', $recordingData->livestockId)
                ->where('tanggal', '<', $recordingData->date)
                ->orderBy('tanggal', 'desc')
                ->first();

            if ($previousRecording) {
                $weightGain = $recordingData->bodyWeight - $previousRecording->berat_hari_ini;
                $daysDiff = Carbon::parse($recordingData->date)
                    ->diffInDays(Carbon::parse($previousRecording->tanggal));

                if ($daysDiff > 0) {
                    $dailyWeightGain = $weightGain / $daysDiff;

                    if ($dailyWeightGain > $config['max_weight_gain_per_day']) {
                        $warnings[] = "Daily weight gain ({$dailyWeightGain} kg) is unusually high";
                    }

                    if ($dailyWeightGain < 0) {
                        $warnings[] = "Weight loss detected ({$dailyWeightGain} kg per day)";
                    }
                }
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate livestock constraints
     */
    private function validateLivestockConstraints(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            $livestock = Livestock::find($recordingData->livestockId);

            if (!$livestock) {
                $errors[] = 'Livestock not found';
                return new ValidationResult(false, $errors, $warnings);
            }

            // Check if livestock is active
            if ($livestock->status !== 'active') {
                $errors[] = 'Cannot record data for inactive livestock';
            }

            // Check population against current livestock
            if ($livestock->currentLivestock && $recordingData->population > $livestock->currentLivestock->quantity) {
                $errors[] = 'Population cannot exceed current livestock quantity';
            }

            // Check duplicate recording
            $existingRecording = Recording::where('livestock_id', $recordingData->livestockId)
                ->whereDate('tanggal', $recordingData->date)
                ->first();

            if ($existingRecording) {
                $warnings[] = 'Recording already exists for this date';
            }
        } catch (\Exception $e) {
            Log::error('Error validating livestock constraints', [
                'livestock_id' => $recordingData->livestockId,
                'error' => $e->getMessage()
            ]);

            $errors[] = 'Error validating livestock constraints';
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate resource availability
     */
    private function validateResourceAvailability(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validate feed availability
        if ($recordingData->feedUsage) {
            foreach ($recordingData->feedUsage as $usage) {
                $feedStock = FeedStock::where('feed_id', $usage['feed_id'])
                    ->where('quantity', '>=', $usage['quantity'])
                    ->first();

                if (!$feedStock) {
                    $errors[] = "Insufficient feed stock for feed ID {$usage['feed_id']}";
                }
            }
        }

        // Validate supply availability
        if ($recordingData->supplyUsage) {
            foreach ($recordingData->supplyUsage as $usage) {
                $supplyStock = SupplyStock::where('supply_id', $usage['supply_id'])
                    ->where('quantity', '>=', $usage['quantity'])
                    ->first();

                if (!$supplyStock) {
                    $errors[] = "Insufficient supply stock for supply ID {$usage['supply_id']}";
                }
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate historical consistency
     */
    private function validateHistoricalConsistency(RecordingData $recordingData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        try {
            // Get recent recordings
            $recentRecordings = Recording::where('livestock_id', $recordingData->livestockId)
                ->where('tanggal', '>=', Carbon::parse($recordingData->date)->subDays(7))
                ->orderBy('tanggal', 'desc')
                ->get();

            if ($recentRecordings->isNotEmpty()) {
                // Check for significant weight changes
                $avgWeight = $recentRecordings->avg('berat_hari_ini');
                $weightDifference = abs($recordingData->bodyWeight - $avgWeight);

                if ($weightDifference > ($avgWeight * 0.2)) { // 20% difference
                    $warnings[] = 'Body weight differs significantly from recent recordings';
                }

                // Check for population inconsistencies
                $lastRecording = $recentRecordings->first();
                if ($lastRecording && $recordingData->population > $lastRecording->population) {
                    $warnings[] = 'Population increase detected - verify livestock additions';
                }
            }
        } catch (\Exception $e) {
            Log::error('Error validating historical consistency', [
                'livestock_id' => $recordingData->livestockId,
                'error' => $e->getMessage()
            ]);

            $warnings[] = 'Could not validate historical consistency';
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }

    /**
     * Validate batch data
     */
    public function validateBatch(array $recordingDataArray): ValidationResult
    {
        $errors = [];
        $warnings = [];

        foreach ($recordingDataArray as $index => $data) {
            try {
                $recordingData = RecordingData::fromArray($data);
                $validation = $this->validateComprehensive($recordingData);

                if (!$validation->isValid) {
                    foreach ($validation->errors as $error) {
                        $errors[] = "Index {$index}: {$error}";
                    }
                }

                foreach ($validation->warnings as $warning) {
                    $warnings[] = "Index {$index}: {$warning}";
                }
            } catch (\Exception $e) {
                $errors[] = "Index {$index}: " . $e->getMessage();
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            $warnings
        );
    }
}
