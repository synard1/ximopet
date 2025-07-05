<?php

namespace App\Services\Report;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk menangani kalkulasi bisnis logic dalam laporan
 * Memisahkan complex calculations dari controller untuk maintainability
 */
class ReportCalculationService
{
    /**
     * Calculate FCR (Feed Conversion Ratio)
     * FCR = Total Feed Consumed (kg) / Total Weight Gained (kg)
     * 
     * @param float $totalFeedConsumption Total pakan yang dikonsumsi (kg)
     * @param float $totalWeight Total berat ternak (kg)
     * @return float|null FCR value atau null jika tidak dapat dihitung
     */
    public function calculateFCR(float $totalFeedConsumption, float $totalWeight): ?float
    {
        if ($totalWeight <= 0) {
            Log::debug('FCR calculation: Total weight is zero or negative', [
                'totalFeedConsumption' => $totalFeedConsumption,
                'totalWeight' => $totalWeight
            ]);
            return null;
        }

        $fcr = round($totalFeedConsumption / $totalWeight, 3);

        Log::debug('FCR calculated', [
            'totalFeedConsumption' => $totalFeedConsumption,
            'totalWeight' => $totalWeight,
            'fcr' => $fcr
        ]);

        return $fcr;
    }

    /**
     * Calculate IP (Index Performance)
     * IP = (Survival Rate % × Average Weight kg) / (FCR × Age in days) × 100
     * 
     * @param float $survivalRate Survival rate percentage
     * @param float $averageWeight Average weight in grams
     * @param int $ageInDays Age in days
     * @param float $fcr Feed Conversion Ratio
     * @return int|null IP value atau null jika tidak dapat dihitung
     */
    public function calculateIP(float $survivalRate, float $averageWeight, int $ageInDays, float $fcr): ?int
    {
        if ($fcr <= 0 || $ageInDays <= 0 || $averageWeight <= 0) {
            Log::debug('IP calculation: Invalid parameters', [
                'survivalRate' => $survivalRate,
                'averageWeight' => $averageWeight,
                'ageInDays' => $ageInDays,
                'fcr' => $fcr
            ]);
            return null;
        }

        // Convert weight from grams to kg
        $weightInKg = $averageWeight / 1000;

        $ip = round(($survivalRate * $weightInKg) / ($fcr * $ageInDays) * 100, 0);

        Log::debug('IP calculated', [
            'survivalRate' => $survivalRate,
            'averageWeight' => $averageWeight,
            'weightInKg' => $weightInKg,
            'ageInDays' => $ageInDays,
            'fcr' => $fcr,
            'ip' => $ip
        ]);

        return (int) $ip;
    }

    /**
     * Calculate Survival Rate
     * Survival Rate = (Current Stock / Initial Stock) × 100
     * 
     * @param int $currentStock Current livestock count
     * @param int $initialStock Initial livestock count
     * @return float Survival rate percentage
     */
    public function calculateSurvivalRate(int $currentStock, int $initialStock): float
    {
        if ($initialStock <= 0) {
            Log::warning('Survival rate calculation: Initial stock is zero or negative', [
                'currentStock' => $currentStock,
                'initialStock' => $initialStock
            ]);
            return 0.0;
        }

        $survivalRate = round(($currentStock / $initialStock) * 100, 2);

        Log::debug('Survival rate calculated', [
            'currentStock' => $currentStock,
            'initialStock' => $initialStock,
            'survivalRate' => $survivalRate
        ]);

        return $survivalRate;
    }

    /**
     * Calculate Depletion Percentage
     * Depletion % = (Total Depletion / Initial Stock) × 100
     * 
     * @param int $totalDepletion Total depletion count
     * @param int $initialStock Initial livestock count
     * @return float Depletion percentage
     */
    public function calculateDepletionPercentage(int $totalDepletion, int $initialStock): float
    {
        if ($initialStock <= 0) {
            Log::warning('Depletion percentage calculation: Initial stock is zero or negative', [
                'totalDepletion' => $totalDepletion,
                'initialStock' => $initialStock
            ]);
            return 0.0;
        }

        $depletionPercentage = round(($totalDepletion / $initialStock) * 100, 2);

        Log::debug('Depletion percentage calculated', [
            'totalDepletion' => $totalDepletion,
            'initialStock' => $initialStock,
            'depletionPercentage' => $depletionPercentage
        ]);

        return $depletionPercentage;
    }

    /**
     * Calculate Average Weight
     * Average Weight = Total Weight / Total Count
     * 
     * @param float $totalWeight Total weight
     * @param int $totalCount Total count
     * @return float Average weight
     */
    public function calculateAverageWeight(float $totalWeight, int $totalCount): float
    {
        if ($totalCount <= 0) {
            Log::warning('Average weight calculation: Total count is zero or negative', [
                'totalWeight' => $totalWeight,
                'totalCount' => $totalCount
            ]);
            return 0.0;
        }

        $averageWeight = round($totalWeight / $totalCount, 2);

        Log::debug('Average weight calculated', [
            'totalWeight' => $totalWeight,
            'totalCount' => $totalCount,
            'averageWeight' => $averageWeight
        ]);

        return $averageWeight;
    }

    /**
     * Calculate Age in Days
     * 
     * @param string|Carbon $startDate Start date
     * @param string|Carbon $currentDate Current date
     * @return int Age in days
     */
    public function calculateAgeInDays($startDate, $currentDate = null): int
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $current = $currentDate instanceof Carbon ? $currentDate : Carbon::parse($currentDate ?? now());

        $ageInDays = $start->diffInDays($current);

        Log::debug('Age calculated', [
            'startDate' => $start->format('Y-m-d'),
            'currentDate' => $current->format('Y-m-d'),
            'ageInDays' => $ageInDays
        ]);

        return $ageInDays;
    }

    /**
     * Calculate Feed Consumption per Head
     * 
     * @param float $totalFeedConsumption Total feed consumption
     * @param int $livestockCount Current livestock count
     * @return float Feed consumption per head (grams)
     */
    public function calculateFeedConsumptionPerHead(float $totalFeedConsumption, int $livestockCount): float
    {
        if ($livestockCount <= 0) {
            Log::warning('Feed consumption per head calculation: Livestock count is zero or negative', [
                'totalFeedConsumption' => $totalFeedConsumption,
                'livestockCount' => $livestockCount
            ]);
            return 0.0;
        }

        // Convert kg to grams and calculate per head
        $feedPerHead = round(($totalFeedConsumption * 1000) / $livestockCount, 0);

        Log::debug('Feed consumption per head calculated', [
            'totalFeedConsumption' => $totalFeedConsumption,
            'livestockCount' => $livestockCount,
            'feedPerHead' => $feedPerHead
        ]);

        return $feedPerHead;
    }

    /**
     * Get FCR Standards array based on strain
     * Based on industry research data
     * 
     * @param bool $isRoss
     * @param bool $isCobb
     * @return array FCR standards by week
     */
    public function getFCRStandards(bool $isRoss = false, bool $isCobb = false): array
    {
        if ($isRoss) {
            $standards = [
                1 => 1.272, // 0-7 days
                2 => 1.229, // 7-14 days  
                3 => 1.312, // 14-21 days
                4 => 1.385, // 21-28 days
                5 => 1.445, // 28-35 days
                6 => 1.775, // 35-42 days
            ];
        } elseif ($isCobb) {
            $standards = [
                1 => 1.267, // 0-7 days
                2 => 1.242, // 7-14 days
                3 => 1.330, // 14-21 days
                4 => 1.398, // 21-28 days
                5 => 1.447, // 28-35 days
                6 => 1.801, // 35-42 days
            ];
        } else {
            // Generic standards (average of Ross and Cobb)
            $standards = [
                1 => 1.270,
                2 => 1.236,
                3 => 1.321,
                4 => 1.392,
                5 => 1.446,
                6 => 1.788,
            ];
        }

        Log::debug('FCR standards array retrieved', [
            'is_ross' => $isRoss,
            'is_cobb' => $isCobb,
            'standards_count' => count($standards)
        ]);

        return $standards;
    }

    /**
     * Get FCR Standards based on strain and age
     * Based on industry research data
     * 
     * @param int $ageInDays Age in days
     * @param string $strain Livestock strain
     * @return float FCR standard
     */
    public function getFCRStandard(int $ageInDays, string $strain = ''): float
    {
        $weekNumber = ceil($ageInDays / 7);

        $isRoss = stripos($strain, 'ross') !== false;
        $isCobb = stripos($strain, 'cobb') !== false;

        $standards = [];

        if ($isRoss) {
            $standards = [
                1 => 1.272, // 0-7 days
                2 => 1.229, // 7-14 days  
                3 => 1.312, // 14-21 days
                4 => 1.385, // 21-28 days
                5 => 1.445, // 28-35 days
                6 => 1.775, // 35-42 days
            ];
        } elseif ($isCobb) {
            $standards = [
                1 => 1.267, // 0-7 days
                2 => 1.242, // 7-14 days
                3 => 1.330, // 14-21 days
                4 => 1.398, // 21-28 days
                5 => 1.447, // 28-35 days
                6 => 1.801, // 35-42 days
            ];
        } else {
            // Generic standards (average of Ross and Cobb)
            $standards = [
                1 => 1.270,
                2 => 1.236,
                3 => 1.321,
                4 => 1.392,
                5 => 1.446,
                6 => 1.788,
            ];
        }

        $standard = $standards[$weekNumber] ?? $standards[6]; // Default to week 6 if beyond

        Log::debug('FCR standard retrieved', [
            'ageInDays' => $ageInDays,
            'weekNumber' => $weekNumber,
            'strain' => $strain,
            'standard' => $standard
        ]);

        return $standard;
    }

    /**
     * Get Standard Weight based on age and strain
     * 
     * @param int $ageInDays Age in days
     * @param string $strain Livestock strain
     * @return float Standard weight in grams
     */
    public function getStandardWeight(int $ageInDays, string $strain = ''): float
    {
        // Standard weight targets (in grams) based on age
        $weightStandards = [
            0 => 42,    // Day 0 (DOC)
            7 => 180,   // Week 1
            14 => 450,  // Week 2
            21 => 900,  // Week 3
            28 => 1500, // Week 4
            35 => 2200, // Week 5
            42 => 2800, // Week 6
        ];

        // Find the closest age in standards
        $closestAge = 0;
        foreach ($weightStandards as $standardAge => $weight) {
            if ($ageInDays >= $standardAge) {
                $closestAge = $standardAge;
            }
        }

        $standardWeight = $weightStandards[$closestAge] ?? 42;

        Log::debug('Standard weight retrieved', [
            'ageInDays' => $ageInDays,
            'closestAge' => $closestAge,
            'strain' => $strain,
            'standardWeight' => $standardWeight
        ]);

        return $standardWeight;
    }

    /**
     * Calculate comprehensive performance metrics
     * 
     * @param array $data Input data containing livestock metrics
     * @return array Calculated performance metrics
     */
    public function calculatePerformanceMetrics(array $data): array
    {
        $metrics = [];

        // Basic calculations
        $metrics['survival_rate'] = $this->calculateSurvivalRate(
            $data['current_stock'] ?? 0,
            $data['initial_stock'] ?? 0
        );

        $metrics['depletion_percentage'] = $this->calculateDepletionPercentage(
            $data['total_depletion'] ?? 0,
            $data['initial_stock'] ?? 0
        );

        $metrics['average_weight'] = $this->calculateAverageWeight(
            $data['total_weight'] ?? 0,
            $data['total_count'] ?? 0
        );

        $metrics['age_in_days'] = $this->calculateAgeInDays(
            $data['start_date'] ?? now(),
            $data['current_date'] ?? now()
        );

        // Advanced calculations
        $metrics['fcr_actual'] = $this->calculateFCR(
            $data['total_feed_consumption'] ?? 0,
            $data['total_weight'] ?? 0
        );

        $metrics['fcr_standard'] = $this->getFCRStandard(
            $metrics['age_in_days'],
            $data['strain'] ?? ''
        );

        $metrics['ip_actual'] = $this->calculateIP(
            $metrics['survival_rate'],
            $metrics['average_weight'],
            $metrics['age_in_days'],
            $metrics['fcr_actual'] ?? 0
        );

        $metrics['feed_per_head'] = $this->calculateFeedConsumptionPerHead(
            $data['total_feed_consumption'] ?? 0,
            $data['current_stock'] ?? 0
        );

        $metrics['standard_weight'] = $this->getStandardWeight(
            $metrics['age_in_days'],
            $data['strain'] ?? ''
        );

        Log::info('Performance metrics calculated', [
            'input_data_keys' => array_keys($data),
            'calculated_metrics' => array_keys($metrics)
        ]);

        return $metrics;
    }
}
